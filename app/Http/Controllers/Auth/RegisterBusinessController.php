<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeBusinessMail;
use App\Models\Business;
use App\Models\CashRegister;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisterBusinessController extends Controller
{
    /**
     * Muestra el formulario público de registro de nuevo negocio y propietario.
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * Registra un nuevo negocio (Tenant), su usuario Propietario, caja principal y suscripción inicial.
     */
    public function register(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'tax_id' => $request->filled('tax_id') ? strtoupper(trim((string) $request->input('tax_id'))) : null,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'business_name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'max:10'],
        ], [
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $currency = 'NIO';

        try {
            $request->validate([
                'email' => [Rule::unique('users', 'email')],
                'tax_id' => ['nullable', Rule::unique('businesses', 'tax_id')],
            ], [
                'email.unique' => 'Ya existe una cuenta registrada con este correo electrónico.',
                'tax_id.unique' => 'Ya existe un negocio registrado con este número RUC / Identificación Fiscal.',
            ]);

            [$user, $business] = DB::transaction(function () use ($validated, $currency, $request) {
                $baseSlug = Str::slug($validated['business_name']) ?: 'negocio';
                $slug = $baseSlug;
                $counter = 1;
                while (Business::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }

                $biz = Business::create([
                    'name' => $validated['business_name'],
                    'slug' => $slug,
                    'tax_id' => $validated['tax_id'] ?? null,
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'status' => 'active',
                    'settings' => [
                        'commercial_name' => $validated['business_name'],
                        'tax_regime' => 'regimen_general',
                        'municipality' => 'Managua, Nicaragua',
                        'currency' => $currency,
                        'tax_percentage' => 15.0,
                        'timezone' => 'America/Managua',
                        'receipt_format' => 'ticket_80mm',
                        'receipt_footer_note' => 'Comprobante interno emitido por PuntoÁgil Cloud POS.',
                    ],
                ]);

                $owner = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'role' => User::ROLE_OWNER,
                    'status' => 'active',
                    'current_business_id' => $biz->id,
                    'last_login_at' => now(),
                    'last_login_ip' => $request->ip(),
                ]);

                $owner->businesses()->attach($biz->id, [
                    'role' => User::ROLE_OWNER,
                    'is_default' => true,
                ]);

                CashRegister::withoutBusinessScope()->create([
                    'business_id' => $biz->id,
                    'name' => 'Caja Principal #1',
                    'status' => CashRegister::STATUS_CLOSED,
                ]);

                $defaultPlan = Plan::where('price', 30.00)->where('is_active', true)->first()
                    ?: Plan::where('slug', 'puntoagil')->first()
                    ?: Plan::first();

                if ($defaultPlan) {
                    $trialDays = max(1, (int) ($defaultPlan->trial_days ?: 14));
                    Subscription::create([
                        'business_id' => $biz->id,
                        'plan_id' => $defaultPlan->id,
                        'status' => Subscription::STATUS_TRIALING,
                        'billing_cycle' => 'monthly',
                        'price' => $defaultPlan->price,
                        'starts_at' => now(),
                        'trial_ends_at' => now()->addDays($trialDays),
                        'ends_at' => now()->addDays($trialDays),
                        'payment_provider' => 'manual',
                        'notes' => 'Suscripción inicial de prueba (Auto-registro web).',
                    ]);
                }

                return [$owner, $biz];
            });

            app(TenantManager::class)->setTenant($business);

            AuditLogger::record(
                'business.self_registered',
                "Auto-registro completado para el negocio '{$business->name}' por {$user->email}.",
                $business,
                ['slug' => $business->slug, 'currency' => $currency],
                $business->id
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with(
                    'error',
                    'No fue posible conectar con la base de datos de Supabase en este entorno. Verifique que la variable DB_PASSWORD esté configurada en Vercel (Settings > Environment Variables) y realice Redeploy.'
                );
        }

        try {
            if (
                class_exists(WelcomeBusinessMail::class)
                && (app()->environment('testing') || ! empty(config('mail.mailers.smtp.username')))
            ) {
                Mail::to($user->email)->send(new WelcomeBusinessMail($user, $business));
            }
        } catch (\Throwable) {
            // No interrumpir el registro si el servidor SMTP aún no está configurado
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', "¡Bienvenido a PuntoÁgil! Tu empresa '{$business->name}' ha sido creada con 14 días de prueba.");
    }
}
