<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * Muestra el formulario para solicitar el enlace de recuperación de contraseña.
     */
    public function showLinkRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Envía el enlace de recuperación de contraseña al correo del usuario activo.
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $user = User::where('email', $request->input('email'))->first();
            if ($user && $user->status !== 'active') {
                return back()->withInput($request->only('email'))->withErrors([
                    'email' => 'Esta cuenta se encuentra inactiva. Contacte al administrador de su empresa.',
                ]);
            }

            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'No se pudo procesar la solicitud de recuperación. Verifique la conexión a la base de datos y configuración SMTP.',
            ]);
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Hemos enviado un enlace de recuperación a tu correo electrónico.');
        }

        return back()->withInput($request->only('email'))->withErrors([
            'email' => 'No pudimos encontrar un usuario registrado con ese correo electrónico.',
        ]);
    }

    /**
     * Muestra el formulario para definir la nueva contraseña con el token recibido.
     */
    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Procesa el restablecimiento de la contraseña validando el token en password_reset_token.
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with(
                'status',
                'Tu contraseña ha sido restablecida correctamente. Ahora puedes iniciar sesión.'
            );
        }

        return back()->withInput($request->only('email'))->withErrors([
            'email' => 'El token de recuperación es inválido o ha expirado.',
        ]);
    }
}
