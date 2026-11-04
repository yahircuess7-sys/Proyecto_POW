<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionWebhookEvent;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionWebhookController extends Controller
{
    /**
     * Endpoint receptor de eventos webhook de proveedores de pago (ej. Stripe, MercadoPago).
     * Implementa verificación criptográfica de firma (cuando esté configurada) y control estricto de idempotencia.
     */
    public function handle(Request $request, string $provider = 'stripe'): JsonResponse
    {
        $payload = $request->all();
        $rawContent = $request->getContent();

        // 1. Verificación de Autenticidad de la Firma (si está configurada la clave secreta)
        $secret = config("services.{$provider}.webhook_secret") ?: env(strtoupper($provider) . '_WEBHOOK_SECRET');

        if ($secret) {
            $signature = $request->header('Stripe-Signature') ?: $request->header('X-Signature');
            if (! $this->verifySignature($rawContent, $signature, $secret, $provider)) {
                Log::warning("Fallo de autenticación en webhook de {$provider}: Firma no válida.");
                return response()->json(['error' => 'Firma no válida'], 401);
            }
        }

        // 2. Extraer Identificador Único del Evento y Tipo
        $eventId = $request->input('id') ?: ($request->input('event_id') ?: md5($rawContent));
        $eventType = $request->input('type') ?: ($request->input('action') ?: 'unknown');

        // 3. Control de Idempotencia: Verificar si el evento ya fue procesado con anterioridad
        $existingEvent = SubscriptionWebhookEvent::where('provider', $provider)
            ->where('event_id', $eventId)
            ->first();

        if ($existingEvent) {
            return response()->json([
                'status' => 'ignored',
                'message' => 'Evento ya procesado previamente (idempotencia garantizada)',
                'event_id' => $eventId,
            ], 200);
        }

        // 4. Procesamiento Atómico del Evento
        DB::beginTransaction();

        try {
            $status = 'processed';
            $errorMessage = null;

            // Purgar cualquier dato de tarjeta antes de almacenar el log del webhook
            $sanitizedPayload = $this->sanitizeWebhookPayload($payload);

            // Registrar el evento para trazabilidad e idempotencia
            $webhookRecord = SubscriptionWebhookEvent::create([
                'provider' => $provider,
                'event_id' => $eventId,
                'event_type' => $eventType,
                'payload' => $sanitizedPayload,
                'status' => 'processing',
                'processed_at' => now(),
            ]);

            // Procesar el evento según su tipo
            $this->processEvent($provider, $eventType, $payload);

            $webhookRecord->update(['status' => 'processed']);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'event_id' => $eventId,
                'event_type' => $eventType,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error procesando webhook de suscripción [{$provider}/{$eventType}]: " . $e->getMessage());

            SubscriptionWebhookEvent::updateOrCreate(
                ['provider' => $provider, 'event_id' => $eventId],
                [
                    'event_type' => $eventType,
                    'payload' => $this->sanitizeWebhookPayload($payload),
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'processed_at' => now(),
                ]
            );

            return response()->json(['error' => 'Error al procesar el webhook', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Aplica los cambios de estado correspondientes en la suscripción de la empresa.
     */
    protected function processEvent(string $provider, string $eventType, array $payload): void
    {
        $dataObject = $payload['data']['object'] ?? $payload;
        $providerSubId = $dataObject['id'] ?? ($dataObject['subscription'] ?? null);
        $providerCustId = $dataObject['customer'] ?? null;
        $businessId = $dataObject['metadata']['business_id'] ?? null;

        // Buscar la suscripción por id del proveedor o por business_id
        $subscription = null;
        if ($providerSubId) {
            $subscription = Subscription::where('provider_subscription_id', $providerSubId)->first();
        }

        if (! $subscription && $businessId) {
            $subscription = Subscription::where('business_id', $businessId)->latest()->first();
        }

        if (! $subscription) {
            Log::info("Webhook recibido para suscripción no registrada en la base local ({$providerSubId}).");
            return;
        }

        switch ($eventType) {
            case 'invoice.payment_succeeded':
            case 'payment.succeeded':
                $subscription->update([
                    'status' => Subscription::STATUS_ACTIVE,
                    'ends_at' => ($subscription->billing_cycle === 'yearly') ? now()->addYear() : now()->addMonth(),
                    'payment_provider' => $provider,
                    'provider_subscription_id' => $providerSubId ?: $subscription->provider_subscription_id,
                    'provider_customer_id' => $providerCustId ?: $subscription->provider_customer_id,
                    'cancelled_at' => null,
                ]);

                AuditLogger::record(
                    'subscription.payment_succeeded',
                    "Cobro de suscripción confirmado vía {$provider} para {$subscription->business?->name}.",
                    $subscription,
                    ['provider' => $provider, 'subscription_id' => $providerSubId],
                    $subscription->business_id
                );
                break;

            case 'invoice.payment_failed':
            case 'payment.failed':
                $subscription->update([
                    'status' => Subscription::STATUS_PAST_DUE,
                    'notes' => 'Último intento de pago automático fallido a través del proveedor.',
                ]);

                AuditLogger::record(
                    'subscription.payment_failed',
                    "Intento de pago fallido para la suscripción de {$subscription->business?->name}.",
                    $subscription,
                    ['provider' => $provider],
                    $subscription->business_id
                );
                break;

            case 'customer.subscription.deleted':
            case 'subscription.cancelled':
                $subscription->update([
                    'status' => Subscription::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                ]);

                AuditLogger::record(
                    'subscription.cancelled_by_provider',
                    "Suscripción cancelada en {$provider} para {$subscription->business?->name}.",
                    $subscription,
                    ['provider' => $provider],
                    $subscription->business_id
                );
                break;
        }
    }

    /**
     * Valida la firma del webhook según el proveedor.
     */
    protected function verifySignature(string $rawContent, ?string $signature, string $secret, string $provider): bool
    {
        if (! $signature) {
            return false;
        }

        if ($provider === 'stripe') {
            // Verificación HMAC SHA-256 estándar
            $parts = [];
            foreach (explode(',', $signature) as $segment) {
                $sub = explode('=', trim($segment), 2);
                if (count($sub) === 2) {
                    $parts[$sub[0]] = $sub[1];
                }
            }

            if (! isset($parts['t'], $parts['v1'])) {
                return false;
            }

            $signedPayload = "{$parts['t']}.{$rawContent}";
            $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

            return hash_equals($expectedSignature, $parts['v1']);
        }

        return hash_equals(hash_hmac('sha256', $rawContent, $secret), $signature);
    }

    /**
     * Purga cualquier dato financiero o sensible antes de persistir el payload.
     */
    protected function sanitizeWebhookPayload(array $payload): array
    {
        $sensitivePatterns = ['card', 'cvv', 'cvc', 'password', 'secret', 'token'];

        array_walk_recursive($payload, function (&$val, $key) use ($sensitivePatterns) {
            $lowerKey = strtolower((string) $key);
            foreach ($sensitivePatterns as $pattern) {
                if (str_contains($lowerKey, $pattern) || $lowerKey === 'number') {
                    $val = '[REDACTED_SECURE]';
                    break;
                }
            }
        });

        return $payload;
    }
}
