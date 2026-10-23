<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    public function index(Request $request)
    {
        $businessId = $this->tenantManager->getTenantId() ?: Auth::user()->current_business_id;

        $query = User::whereHas('businesses', function ($q) use ($businessId) {
            $q->where('businesses.id', $businessId);
        });

        if ($search = $request->input('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(UserRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $businessId = $this->tenantManager->getTenantId() ?: Auth::user()->current_business_id;

        DB::transaction(function () use ($validated, $businessId) {
            $validated['current_business_id'] = $businessId;
            $user = User::create($validated);

            $user->businesses()->attach($businessId, [
                'role' => $validated['role'],
                'is_default' => true,
            ]);
        });

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user)
    {
        $businessId = $this->tenantManager->getTenantId() ?: Auth::user()->current_business_id;
        abort_unless($user->businesses()->where('businesses.id', $businessId)->exists(), 404);

        return view('admin.users.edit', compact('user'));
    }

    public function update(UserRequest $request, User $user)
    {
        $businessId = $this->tenantManager->getTenantId() ?: Auth::user()->current_business_id;
        abort_unless($user->businesses()->where('businesses.id', $businessId)->exists(), 404);

        $validated = $request->validated();

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        DB::transaction(function () use ($user, $validated, $businessId) {
            $user->update($validated);

            $user->businesses()->updateExistingPivot($businessId, [
                'role' => $validated['role'],
            ]);
        });

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        $businessId = $this->tenantManager->getTenantId() ?: Auth::user()->current_business_id;
        abort_unless($user->businesses()->where('businesses.id', $businessId)->exists(), 404);

        if (Auth::id() === $user->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No puedes eliminar tu propia cuenta de usuario en sesión activa.');
        }

        if ($user->sales()->where('business_id', $businessId)->exists()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No se puede eliminar este usuario porque tiene transacciones de venta asociadas en esta empresa.');
        }

        DB::transaction(function () use ($user, $businessId) {
            $user->businesses()->detach($businessId);

            // Si el usuario ya no tiene membresías en ninguna empresa, se elimina completamente
            if ($user->businesses()->count() === 0) {
                $user->delete();
            }
        });

        return redirect()->route('admin.users.index')->with('success', 'Usuario removido de la empresa correctamente.');
    }
}
