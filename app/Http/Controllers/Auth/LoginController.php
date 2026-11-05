<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withInput($request->only('email'))->withErrors([
                'email' => "Demasiados intentos de inicio de sesión. Por favor, intente de nuevo en {$seconds} segundos.",
            ]);
        }

        $attemptCredentials = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'status' => 'active',
        ];

        try {
            if (Auth::attempt($attemptCredentials, $request->filled('remember'))) {
                RateLimiter::clear($throttleKey);
                $request->session()->regenerate();

                $authenticatedUser = Auth::user();
                if ($authenticatedUser) {
                    $authenticatedUser->forceFill([
                        'last_login_at' => now(),
                        'last_login_ip' => $request->ip(),
                    ])->save();
                }

                return redirect()->intended('/dashboard');
            }

            RateLimiter::hit($throttleKey, 60);

            $user = User::where('email', $credentials['email'])->first();
            if ($user && $user->status === 'inactive' && Hash::check($credentials['password'], $user->password)) {
                return back()->withInput($request->only('email'))->withErrors([
                    'email' => 'Esta cuenta se encuentra inactiva. Por favor, contacte al administrador.',
                ]);
            }
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput($request->only('email'))->with(
                'error',
                'No fue posible conectar con la base de datos en este entorno. Si acabas de agregar las variables DB_HOST / DB_PASSWORD en Vercel, recuerda hacer clic en "Redeploy" en el panel de Vercel (o hacer git push) para que tomen efecto.'
            );
        }

        return back()->withInput($request->only('email'))->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        app(TenantManager::class)->clear();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect('/');
    }
}
