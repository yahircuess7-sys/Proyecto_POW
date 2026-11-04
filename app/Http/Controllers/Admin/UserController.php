<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\BusinessInvitation;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $actor = Auth::user();
        $businessId = $this->tenantManager->getTenantId() ?: $actor->current_business_id;

        $query = User::whereHas('businesses', function ($q) use ($businessId) {
            $q->where('businesses.id', $businessId);
        });

        if ($search = $request->input('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleFilter = $request->input('role')) {
            $query->whereHas('businesses', function ($q) use ($businessId, $roleFilter) {
                $q->where('businesses.id', $businessId)
                    ->where('business_user.role', $roleFilter);
            });
        }

        if ($statusFilter = $request->input('status')) {
            $query->where('status', $statusFilter);
        }

        $users = $query->with('businesses')->orderByDesc('created_at')->paginate(15)->withQueryString();

        $pendingInvitations = BusinessInvitation::where('business_id', $businessId)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with('inviter')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.users', compact('users', 'pendingInvitations'));
    }

    public function create()
    {
        Gate::authorize('create', User::class);

        return view('admin.users.create');
    }

    public function store(UserRequest $request)
    {
        Gate::authorize('create', User::class);

        $validated = $request->validated();
        Gate::authorize('assignRole', [User::class, $validated['role']]);

        $actor = Auth::user();
        $business = $this->tenantManager->getTenant() ?: $actor->currentBusiness;
        $businessId = $business?->id ?: ($this->tenantManager->getTenantId() ?: $actor->current_business_id);

        if ($business && ! $business->canAddUser() && ! $actor->isSuperAdmin()) {
            $planName = $business->currentPlan()?->name ?? 'actual';
            return redirect()->back()
                ->withInput()
                ->with('error', "Has alcanzado el límite de usuarios permitidos en tu plan ({$planName}). Mejora tu suscripción para añadir más miembros.");
        }

        $createdUser = DB::transaction(function () use ($validated, $businessId, $actor) {
            $payload = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'status' => $validated['status'],
                'current_business_id' => $businessId,
                'is_super_admin' => $actor->isSuperAdmin() && $validated['role'] === User::ROLE_SUPER_ADMIN,
                'email_verified_at' => now(),
            ];

            $user = User::create($payload);

            if ($businessId) {
                $user->businesses()->attach($businessId, [
                    'role' => $validated['role'],
                    'is_default' => true,
                ]);
            }

            return $user;
        });

        AuditLogger::record(
            'user.created',
            "Se creó el usuario {$createdUser->email} con rol {$validated['role']}.",
            $createdUser,
            [
                'email' => $createdUser->email,
                'assigned_role' => $validated['role'],
                'status' => $createdUser->status,
            ],
            $businessId
        );

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Envía una invitación para asociar un usuario nuevo o existente al negocio activo.
     */
    public function invite(Request $request)
    {
        Gate::authorize('create', User::class);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:owner,admin,cajero'],
        ]);

        Gate::authorize('assignRole', [User::class, $validated['role']]);

        $actor = Auth::user();
        $business = $this->tenantManager->getTenant() ?: $actor->currentBusiness;

        if ($business && ! $business->canAddUser() && ! $actor->isSuperAdmin()) {
            $planName = $business->currentPlan()?->name ?? 'actual';
            return redirect()->route('admin.users.index')
                ->with('error', "Has alcanzado el límite de usuarios de tu plan ({$planName}). Mejora tu suscripción para invitar más miembros.");
        }

        $businessId = $business->id;
        $email = strtolower(trim($validated['email']));

        // Verificar si el usuario ya pertenece a este negocio
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $existingUser->businesses()->where('businesses.id', $businessId)->exists()) {
            return redirect()->route('admin.users.index')
                ->with('error', "El usuario {$email} ya forma parte de {$business->name}.");
        }

        // Revocar invitaciones previas pendientes para el mismo correo en este negocio
        BusinessInvitation::where('business_id', $businessId)
            ->where('email', $email)
            ->where('status', 'pending')
            ->update(['status' => 'revoked']);

        $invitation = BusinessInvitation::create([
            'business_id' => $businessId,
            'invited_by' => $actor->id,
            'email' => $email,
            'name' => $validated['name'] ?? ($existingUser?->name),
            'role' => $validated['role'],
            'token' => Str::random(48),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        $acceptUrl = route('invitations.show', $invitation->token);
        $mailDelivered = false;

        try {
            $body = "Hola,\n\nHas sido invitado por {$actor->name} para unirte al negocio \"{$business->name}\" en PuntoÁgil con el rol de " . strtoupper($invitation->role) . ".\n\n"
                . "Acepta tu invitación antes del {$invitation->expires_at->format('d/m/Y H:i')} en el siguiente enlace seguro:\n{$acceptUrl}\n";

            Mail::raw($body, function ($message) use ($email, $business) {
                $message->to($email)
                    ->subject("Invitación a {$business->name} - PuntoÁgil SaaS");
            });
            $mailDelivered = true;
        } catch (\Throwable $e) {
            $mailDelivered = false;
        }

        AuditLogger::record(
            'invitation.sent',
            "Se generó invitación para {$email} con rol {$invitation->role}.",
            $invitation,
            [
                'email' => $email,
                'role' => $invitation->role,
                'mail_delivered' => $mailDelivered,
            ],
            $businessId
        );

        $message = $mailDelivered
            ? "Invitación enviada a {$email}. También puedes compartir el enlace directo de activación."
            : "Invitación registrada para {$email}. El servidor de correo externo no está configurado; comparte el enlace de invitación generado.";

        return redirect()->route('admin.users.index')
            ->with('success', $message)
            ->with('invitation_link', $acceptUrl);
    }

    /**
     * Revoca una invitación pendiente de la empresa.
     */
    public function revokeInvitation(BusinessInvitation $invitation)
    {
        $actor = Auth::user();
        $businessId = $this->tenantManager->getTenantId() ?: $actor->current_business_id;

        if (! $actor->isSuperAdmin()) {
            abort_unless((int) $invitation->business_id === (int) $businessId, 404);
        }

        Gate::authorize('assignRole', [User::class, $invitation->role]);

        $invitation->update(['status' => 'revoked']);

        AuditLogger::record(
            'invitation.revoked',
            "Se revocó la invitación pendiente de {$invitation->email}.",
            $invitation,
            ['email' => $invitation->email, 'role' => $invitation->role],
            $invitation->business_id
        );

        return redirect()->route('admin.users.index')
            ->with('success', "La invitación para {$invitation->email} ha sido revocada.");
    }

    public function edit(User $user)
    {
        $actor = Auth::user();
        $businessId = $this->tenantManager->getTenantId() ?: $actor->current_business_id;

        if (! $actor->isSuperAdmin()) {
            abort_unless($user->businesses()->where('businesses.id', $businessId)->exists(), 404);
        }

        Gate::authorize('update', $user);

        return view('admin.users.edit', compact('user'));
    }

    public function update(UserRequest $request, User $user)
    {
        $actor = Auth::user();
        $businessId = $this->tenantManager->getTenantId() ?: $actor->current_business_id;

        if (! $actor->isSuperAdmin()) {
            abort_unless($user->businesses()->where('businesses.id', $businessId)->exists(), 404);
        }

        Gate::authorize('update', $user);

        $validated = $request->validated();
        $previousRole = $user->roleInBusiness($businessId);
        $previousStatus = $user->status;

        // Impedir que un usuario en sesión activa se desactive a sí mismo o se degrade de rol por error
        if ($actor->id === $user->id) {
            if ($validated['status'] !== 'active') {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'No puedes desactivar tu propia cuenta en sesión activa.');
            }

            if ($validated['role'] !== $previousRole && ! $actor->isSuperAdmin()) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'No puedes degradar tu propio rol operativo en sesión activa.');
            }
        }

        if ($validated['role'] !== $previousRole && $validated['role'] !== $user->role) {
            Gate::authorize('assignRole', [User::class, $validated['role']]);
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'status' => $validated['status'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        if ($actor->isSuperAdmin()) {
            $updateData['is_super_admin'] = $validated['role'] === User::ROLE_SUPER_ADMIN;
        }

        DB::transaction(function () use ($user, $updateData, $validated, $businessId) {
            $user->update($updateData);

            if ($businessId && $user->businesses()->where('businesses.id', $businessId)->exists()) {
                $user->businesses()->updateExistingPivot($businessId, [
                    'role' => $validated['role'],
                ]);
            }
        });

        AuditLogger::record(
            'user.updated',
            "Se actualizó la cuenta de {$user->email}.",
            $user,
            [
                'previous_role' => $previousRole,
                'new_role' => $validated['role'],
                'previous_status' => $previousStatus,
                'new_status' => $validated['status'],
                'password_changed' => ! empty($validated['password']),
            ],
            $businessId
        );

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Activa o desactiva rápidamente una cuenta de usuario respetando la jerarquía de permisos.
     */
    public function toggleStatus(User $user)
    {
        $actor = Auth::user();
        $businessId = $this->tenantManager->getTenantId() ?: $actor->current_business_id;

        if (! $actor->isSuperAdmin()) {
            abort_unless($user->businesses()->where('businesses.id', $businessId)->exists(), 404);
        }

        if ($actor->id === $user->id) {
            abort(403, 'No puedes desactivar tu propia cuenta en sesión activa.');
        }

        Gate::authorize('update', $user);

        $previousStatus = $user->status;
        $newStatus = $previousStatus === 'active' ? 'inactive' : 'active';

        $user->update(['status' => $newStatus]);

        AuditLogger::record(
            'user.status_toggled',
            "Se cambió el estado de {$user->email} de {$previousStatus} a {$newStatus}.",
            $user,
            [
                'email' => $user->email,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
            ],
            $businessId
        );

        $statusLabel = $newStatus === 'active' ? 'activada' : 'desactivada';

        return redirect()->route('admin.users.index')
            ->with('success', "La cuenta de {$user->name} ha sido {$statusLabel} correctamente.");
    }

    public function destroy(User $user)
    {
        $actor = Auth::user();
        $businessId = $this->tenantManager->getTenantId() ?: $actor->current_business_id;

        if (! $actor->isSuperAdmin()) {
            abort_unless($user->businesses()->where('businesses.id', $businessId)->exists(), 404);
        }

        if (Auth::id() === $user->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No puedes eliminar tu propia cuenta de usuario en sesión activa.');
        }

        Gate::authorize('delete', $user);

        if ($user->sales()->where('business_id', $businessId)->exists()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No se puede eliminar este usuario porque tiene transacciones de venta asociadas en esta empresa.');
        }

        $removedEmail = $user->email;
        $removedRole = $user->roleInBusiness($businessId);

        DB::transaction(function () use ($user, $businessId) {
            $user->businesses()->detach($businessId);

            if ($user->businesses()->count() === 0 && ! $user->sales()->withoutBusinessScope()->exists()) {
                $user->delete();
            } elseif ((int) $user->current_business_id === (int) $businessId) {
                $nextBiz = $user->businesses()->first();
                $user->update(['current_business_id' => $nextBiz?->id]);
            }
        });

        AuditLogger::record(
            'user.access_revoked',
            "Se revocó el acceso de {$removedEmail} (rol: {$removedRole}) en la empresa.",
            null,
            [
                'email' => $removedEmail,
                'revoked_role' => $removedRole,
            ],
            $businessId
        );

        return redirect()->route('admin.users.index')->with('success', 'Acceso del usuario revocado de la empresa correctamente.');
    }
}
