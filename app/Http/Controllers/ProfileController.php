<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Muestra la pantalla de perfil y seguridad del usuario autenticado.
     */
    public function edit(): View
    {
        $user = Auth::user();
        $user->load('businesses');

        return view('profile.edit', compact('user'));
    }

    /**
     * Actualiza el nombre y correo electrónico del propio usuario autenticado.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'email.unique' => 'Este correo electrónico ya está en uso por otra cuenta.',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        AuditLogger::record(
            'profile.updated',
            "El usuario {$user->email} actualizó sus datos de perfil personal.",
            $user,
            ['name' => $user->name, 'email' => $user->email],
            $user->current_business_id
        );

        return redirect()->route('profile.edit')
            ->with('success', 'Tus datos de perfil han sido actualizados correctamente.');
    }

    /**
     * Cambia la contraseña del propio usuario verificando su contraseña actual.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'La contraseña actual ingresada es incorrecta.',
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        AuditLogger::record(
            'profile.password_changed',
            "El usuario {$user->email} actualizó su contraseña desde su perfil personal.",
            $user,
            [],
            $user->current_business_id
        );

        return redirect()->route('profile.edit')
            ->with('success', 'Tu contraseña ha sido actualizada exitosamente.');
    }
}
