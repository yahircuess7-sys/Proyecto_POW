<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessInvitation;
use App\Models\CashRegister;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    /**
     * Verifica si las credenciales OAuth 2.0 de Google están configuradas en el servidor.
     */
    public static function isConfigured(): bool
    {
        return ! empty(config('services.google.client_id'))
            && ! empty(config('services.google.client_secret'));
    }

    /**
     * Resuelve dinámicamente el Redirect URI de Google OAuth evitando literales ${APP_URL} no expandidos en Vercel.
     */
    protected function resolveRedirectUri(Request $request): string
    {
        $configured = trim((string) config('services.google.redirect', ''));

        if ($configured === '' || str_contains($configured, '${') || str_contains($configured, 'MY_APP_URL')) {
            return $request->getSchemeAndHttpHost() . '/auth/google/callback';
        }

        if (! app()->environment('local', 'testing') && (str_contains($configured, 'localhost') || str_contains($configured, '127.0.0.1'))) {
            return $request->getSchemeAndHttpHost() . '/auth/google/callback';
        }

        return $configured;
    }

    /**
     * Redirige al usuario a la pantalla de consentimiento OAuth 2.0 de Google.
     * No simula autenticaciones si las credenciales no están configuradas.
     */
    public function redirect(Request $request): RedirectResponse
    {
        if (! self::isConfigured()) {
            return redirect()->route('login')->with(
                'error',
                'La autenticación con Google no está configurada en este entorno. Configure GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET.'
            );
        }

        $redirectUri = $this->resolveRedirectUri($request);

        if (class_exists(\Laravel\Socialite\Facades\Socialite::class)) {
            return \Laravel\Socialite\Facades\Socialite::driver('google')
                ->redirectUrl($redirectUri)
                ->redirect();
        }

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    /**
     * Procesa el callback OAuth 2.0 de Google, valida estado CSRF y autentica o registra usuarios.
     */
    public function callback(Request $request): RedirectResponse
    {
        if (! self::isConfigured()) {
            return redirect()->route('login')->with(
                'error',
                'La autenticación con Google no está configurada en este entorno.'
            );
        }

        if ($request->filled('error')) {
            return redirect()->route('login')->with(
                'error',
                'El inicio de sesión con Google fue cancelado o denegado.'
            );
        }

        $redirectUri = $this->resolveRedirectUri($request);

        try {
            if (class_exists(\Laravel\Socialite\Facades\Socialite::class)) {
                $socialiteUser = \Laravel\Socialite\Facades\Socialite::driver('google')
                    ->redirectUrl($redirectUri)
                    ->user();
                $googleId = (string) $socialiteUser->getId();
                $email = strtolower(trim((string) $socialiteUser->getEmail()));
                $name = trim((string) ($socialiteUser->getName() ?: explode('@', $email)[0]));
                $avatar = $socialiteUser->getAvatar();
                $emailVerified = (bool) ($socialiteUser->user['email_verified'] ?? false);
            } else {
                $expectedState = $request->session()->pull('google_oauth_state');
                $receivedState = (string) $request->input('state', '');

                if (empty($expectedState) || ! hash_equals($expectedState, $receivedState)) {
                    return redirect()->route('login')->with(
                        'error',
                        'Estado de seguridad OAuth inválido o expirado. Intenta nuevamente.'
                    );
                }

                $code = (string) $request->input('code', '');
                if ($code === '') {
                    return redirect()->route('login')->with(
                        'error',
                        'No se recibió un código de autorización válido desde Google.'
                    );
                }

                $tokenResponse = Http::asForm()
                    ->when(app()->environment('local'), fn ($http) => $http->withoutVerifying())
                    ->timeout(15)
                    ->post('https://oauth2.googleapis.com/token', [
                        'client_id' => config('services.google.client_id'),
                        'client_secret' => config('services.google.client_secret'),
                        'redirect_uri' => $redirectUri,
                        'grant_type' => 'authorization_code',
                        'code' => $code,
                    ]);

                if (! $tokenResponse->successful() || empty($tokenResponse->json('access_token'))) {
                    return redirect()->route('login')->with(
                        'error',
                        'No fue posible validar el token de acceso con Google. Verifica que el URI de redirección autorizado sea: ' . $redirectUri
                    );
                }

                $profileResponse = Http::withToken($tokenResponse->json('access_token'))
                    ->when(app()->environment('local'), fn ($http) => $http->withoutVerifying())
                    ->timeout(15)
                    ->get('https://www.googleapis.com/oauth2/v3/userinfo');

                if (! $profileResponse->successful()) {
                    return redirect()->route('login')->with(
                        'error',
                        'No fue posible obtener la información de perfil desde Google.'
                    );
                }

                $profile = $profileResponse->json();
                $googleId = (string) ($profile['sub'] ?? '');
                $email = strtolower(trim((string) ($profile['email'] ?? '')));
                $name = trim((string) ($profile['name'] ?? explode('@', $email)[0]));
                $avatar = $profile['picture'] ?? null;
                $emailVerified = (bool) ($profile['email_verified'] ?? false);
            }
        } catch (\Throwable $e) {
            return redirect()->route('login')->with(
                'error',
                'Ocurrió un error al comunicarse con el proveedor de Google. Intenta nuevamente.'
            );
        }

        if ($googleId === '' || $email === '' || ! $emailVerified) {
            return redirect()->route('login')->with(
                'error',
                'La cuenta de Google debe contar con una dirección de correo electrónico verificada.'
            );
        }

        try {
            // Prevenir colisión de google_id con un correo distinto
            $existingByGoogleId = User::where('google_id', $googleId)->first();
            $existingByEmail = User::whereRaw('LOWER(email) = ?', [$email])->first();

            if ($existingByGoogleId && $existingByEmail && $existingByGoogleId->id !== $existingByEmail->id) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Esta cuenta de Google ya está vinculada a otro usuario registrado.',
                ]);
            }

            $user = $existingByGoogleId ?: $existingByEmail;
            $isNewRegistration = false;

            if (! $user) {
                $isNewRegistration = true;
                $user = DB::transaction(function () use ($name, $email, $googleId, $avatar, $request) {
                    $pendingInvitation = BusinessInvitation::whereRaw('LOWER(email) = ?', [$email])
                        ->whereNull('accepted_at')
                        ->where('expires_at', '>', now())
                        ->latest('id')
                        ->first();

                    if ($pendingInvitation) {
                        $newUser = User::create([
                            'name' => $name ?: 'Usuario PuntoÁgil',
                            'email' => $email,
                            'google_id' => $googleId,
                            'avatar' => $avatar,
                            'password' => Hash::make(Str::random(32)),
                            'role' => $pendingInvitation->role,
                            'status' => 'active',
                            'current_business_id' => $pendingInvitation->business_id,
                            'email_verified_at' => now(),
                            'last_login_at' => now(),
                            'last_login_ip' => $request->ip(),
                        ]);

                        $newUser->businesses()->attach($pendingInvitation->business_id, [
                            'role' => $pendingInvitation->role,
                            'is_default' => true,
                        ]);

                        $pendingInvitation->update([
                            'status' => 'accepted',
                            'accepted_at' => now(),
                        ]);

                        AuditLogger::record(
                            'auth.google_invitation_accepted',
                            "El usuario {$email} aceptó invitación y creó cuenta mediante Google OAuth.",
                            $newUser,
                            ['business_id' => $pendingInvitation->business_id, 'role' => $pendingInvitation->role],
                            $pendingInvitation->business_id
                        );

                        return $newUser;
                    }

                    // Auto-registro de nuevo propietario y su negocio inicial en PuntoÁgil
                    $businessName = "Negocio de " . ($name ?: explode('@', $email)[0]);
                    $baseSlug = Str::slug($businessName) ?: 'negocio';
                    $slug = $baseSlug;
                    $counter = 1;
                    while (Business::where('slug', $slug)->exists()) {
                        $slug = $baseSlug . '-' . $counter++;
                    }

                    $business = Business::create([
                        'name' => $businessName,
                        'slug' => $slug,
                        'email' => $email,
                        'status' => 'active',
                        'settings' => [
                            'commercial_name' => $businessName,
                            'tax_regime' => 'regimen_general',
                            'municipality' => 'Managua, Nicaragua',
                            'currency' => 'NIO',
                            'tax_percentage' => 15.0,
                            'timezone' => 'America/Managua',
                            'receipt_format' => 'ticket_80mm',
                            'receipt_footer_note' => 'Comprobante interno emitido por PuntoÁgil Cloud POS.',
                        ],
                    ]);

                    $newUser = User::create([
                        'name' => $name ?: 'Propietario PuntoÁgil',
                        'email' => $email,
                        'google_id' => $googleId,
                        'avatar' => $avatar,
                        'password' => Hash::make(Str::random(32)),
                        'role' => User::ROLE_OWNER,
                        'status' => 'active',
                        'current_business_id' => $business->id,
                        'email_verified_at' => now(),
                        'last_login_at' => now(),
                        'last_login_ip' => $request->ip(),
                    ]);

                    $newUser->businesses()->attach($business->id, [
                        'role' => User::ROLE_OWNER,
                        'is_default' => true,
                    ]);

                    CashRegister::withoutBusinessScope()->create([
                        'business_id' => $business->id,
                        'name' => 'Caja Principal #1',
                        'status' => CashRegister::STATUS_CLOSED,
                    ]);

                    $defaultPlan = Plan::where('price', 30.00)->where('is_active', true)->first()
                        ?: Plan::where('slug', 'puntoagil')->first()
                        ?: Plan::first();

                    if ($defaultPlan) {
                        $trialDays = max(1, (int) ($defaultPlan->trial_days ?: 14));
                        Subscription::create([
                            'business_id' => $business->id,
                            'plan_id' => $defaultPlan->id,
                            'status' => Subscription::STATUS_TRIALING,
                            'billing_cycle' => 'monthly',
                            'price' => $defaultPlan->price,
                            'starts_at' => now(),
                            'trial_ends_at' => now()->addDays($trialDays),
                            'ends_at' => now()->addDays($trialDays),
                            'payment_provider' => 'manual',
                            'notes' => 'Registro inicial con Google OAuth (Período de prueba).',
                        ]);
                    }

                    AuditLogger::record(
                        'auth.google_registered',
                        "Se registró automáticamente el usuario {$email} y su empresa '{$business->name}' mediante Google OAuth.",
                        $business,
                        ['email' => $email, 'google_id' => $googleId],
                        $business->id
                    );

                    return $newUser;
                });
            } else {
                if ($user->status !== 'active') {
                    return redirect()->route('login')->withErrors([
                        'email' => 'Esta cuenta se encuentra inactiva. Por favor, contacte al administrador.',
                    ]);
                }

                // Vincular identificadores OAuth y marcar correo como verificado
                $user->google_id = $googleId;
                if ($avatar) {
                    $user->avatar = $avatar;
                }
                if (! $user->email_verified_at) {
                    $user->email_verified_at = now();
                }
                $user->last_login_at = now();
                $user->last_login_ip = $request->ip();
                $user->save();

                AuditLogger::record(
                    'auth.google_login',
                    "Inicio de sesión mediante Google OAuth para {$user->email}.",
                    $user,
                    ['ip' => $request->ip()],
                    $user->current_business_id
                );
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            if ($isNewRegistration && $user->isOwner()) {
                return redirect()->route('admin.business.settings')
                    ->with('success', '¡Bienvenido a PuntoÁgil! Hemos creado tu negocio con 14 días de prueba. Completa los datos fiscales de tu empresa.');
            }

            return redirect()->intended('/dashboard');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->with(
                'error',
                'No fue posible conectar con la base de datos para completar el acceso. Verifica que las variables DB_HOST, DB_USERNAME y DB_PASSWORD estén guardadas y hayas pulsado Redeploy en Vercel.'
            );
        }
    }
}
