<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Registra un evento sensible en la bitácora de auditoría protegida.
     *
     * @param  string       $action
     * @param  string       $description
     * @param  Model|null   $target
     * @param  array<mixed> $metadata
     * @param  int|null     $businessId
     */
    public static function record(
        string $action,
        string $description,
        ?Model $target = null,
        array $metadata = [],
        ?int $businessId = null
    ): AuditLog {
        $actor = Auth::user();
        $resolvedBusinessId = $businessId
            ?: (app(TenantManager::class)->getTenantId() ?: $actor?->current_business_id);

        // Purgar cualquier clave sensible accidental antes de persistir (incluso anidada)
        $sensitivePatterns = ['password', 'token', 'secret', 'card', 'cvv', 'cvc'];
        array_walk_recursive($metadata, function (&$val, $key) use ($sensitivePatterns) {
            $lowerKey = strtolower((string) $key);
            foreach ($sensitivePatterns as $pattern) {
                if (str_contains($lowerKey, $pattern)) {
                    $val = '[REDACTED]';
                    break;
                }
            }
        });
        unset(
            $metadata['password'],
            $metadata['password_confirmation'],
            $metadata['token'],
            $metadata['remember_token'],
            $metadata['client_secret']
        );

        $request = request();

        return AuditLog::create([
            'business_id' => $resolvedBusinessId,
            'user_id' => $actor?->id,
            'action' => $action,
            'target_type' => $target ? class_basename($target) : null,
            'target_id' => $target?->getKey(),
            'description' => $description,
            'metadata' => ! empty($metadata) ? $metadata : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
            'created_at' => now(),
        ]);
    }
}
