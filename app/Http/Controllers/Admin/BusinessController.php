<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * Panel de configuración del negocio activo (Accesible por Owner del negocio o Super Admin).
     */
    public function settings(): View
    {
        $actor = Auth::user();
        $business = $this->tenantManager->getTenant() ?: $actor->currentBusiness;
        abort_if(! $business, 404);

        Gate::authorize('update', $business);

        $business->loadCount([
            'users',
            'products' => fn ($q) => $q->withoutBusinessScope(),
            'sales' => fn ($q) => $q->withoutBusinessScope(),
        ]);

        return view('admin.business.settings', compact('business'));
    }

    /**
     * Actualiza la configuración fiscal y operativa del negocio activo (Solo Owner o Super Admin).
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $actor = Auth::user();
        $business = $this->tenantManager->getTenant() ?: $actor->currentBusiness;
        abort_if(! $business, 404);

        Gate::authorize('update', $business);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:10'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'receipt_format' => ['nullable', 'string', 'in:ticket_80mm,ticket_58mm,a4_pdf'],
            'receipt_footer_note' => ['nullable', 'string', 'max:500'],
        ]);

        $settings = array_merge($business->settings ?? [], [
            'currency' => strtoupper(trim($validated['currency'] ?? ($business->settings['currency'] ?? 'USD'))),
            'tax_percentage' => (float) ($validated['tax_percentage'] ?? ($business->settings['tax_percentage'] ?? 12)),
            'timezone' => $validated['timezone'] ?? ($business->settings['timezone'] ?? 'America/Guayaquil'),
            'slogan' => $validated['slogan'] ?? ($business->settings['slogan'] ?? null),
            'receipt_format' => $validated['receipt_format'] ?? ($business->settings['receipt_format'] ?? 'ticket_80mm'),
            'receipt_footer_note' => $validated['receipt_footer_note'] ?? ($business->settings['receipt_footer_note'] ?? 'Comprobante interno emitido por PuntoÁgil Cloud POS.'),
        ]);

        $business->update([
            'name' => $validated['name'],
            'tax_id' => $validated['tax_id'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'settings' => $settings,
        ]);

        AuditLogger::record(
            'business.updated',
            "Se actualizó la configuración del negocio {$business->name}.",
            $business,
            [
                'name' => $business->name,
                'tax_id' => $business->tax_id,
                'currency' => $settings['currency'],
                'tax_percentage' => $settings['tax_percentage'],
            ],
            $business->id
        );

        return redirect()->route('admin.business.settings')
            ->with('success', 'La configuración del negocio ha sido actualizada correctamente.');
    }

    /**
     * Cambia el negocio activo de la sesión validando estrictamente la membresía autorizada.
     */
    public function switchBusiness(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
        ]);

        $user = Auth::user();
        $targetBusiness = Business::findOrFail($validated['business_id']);

        Gate::authorize('view', $targetBusiness);

        if (! $targetBusiness->isActive() && ! $user->isSuperAdmin()) {
            abort(403, 'El negocio seleccionado se encuentra suspendido.');
        }

        $user->update(['current_business_id' => $targetBusiness->id]);
        $this->tenantManager->setTenant($targetBusiness);

        AuditLogger::record(
            'business.switched',
            "El usuario {$user->email} cambió su contexto activo a {$targetBusiness->name}.",
            $targetBusiness,
            ['business_id' => $targetBusiness->id],
            $targetBusiness->id
        );

        return redirect()->route('dashboard')
            ->with('success', "Contexto cambiado a {$targetBusiness->name}.");
    }

    /**
     * Administración Global de Empresas (Exclusivo para Super Admin).
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Business::class);

        $query = Business::withCount([
            'users',
            'products' => fn ($q) => $q->withoutBusinessScope(),
            'sales' => fn ($q) => $q->withoutBusinessScope(),
        ]);

        if ($search = $request->input('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('tax_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $businesses = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('admin.businesses.index', compact('businesses'));
    }

    /**
     * Formulario para registrar un nuevo negocio (Super Admin).
     */
    public function create(): View
    {
        Gate::authorize('create', Business::class);

        return view('admin.businesses.create');
    }

    /**
     * Registra un nuevo negocio en la plataforma y asocia su Owner inicial (Super Admin).
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Business::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255'],
            'owner_password' => ['nullable', 'string', 'min:8'],
        ]);

        $baseSlug = Str::slug($validated['name']) ?: 'negocio';
        $slug = $baseSlug;
        $counter = 1;
        while (Business::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $business = DB::transaction(function () use ($validated, $slug) {
            $biz = Business::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'tax_id' => $validated['tax_id'] ?? null,
                'email' => $validated['email'] ?? $validated['owner_email'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'status' => 'active',
                'settings' => [
                    'currency' => 'USD',
                    'tax_percentage' => 12,
                    'timezone' => 'America/Guayaquil',
                ],
            ]);

            $ownerEmail = strtolower(trim($validated['owner_email']));
            $owner = User::where('email', $ownerEmail)->first();

            if (! $owner) {
                $owner = User::create([
                    'name' => $validated['owner_name'],
                    'email' => $ownerEmail,
                    'password' => Hash::make($validated['owner_password'] ?: Str::random(16)),
                    'role' => User::ROLE_OWNER,
                    'status' => 'active',
                    'current_business_id' => $biz->id,
                    'email_verified_at' => now(),
                ]);
            } elseif (! $owner->current_business_id) {
                $owner->update(['current_business_id' => $biz->id]);
            }

            if (! $owner->businesses()->where('businesses.id', $biz->id)->exists()) {
                $owner->businesses()->attach($biz->id, [
                    'role' => User::ROLE_OWNER,
                    'is_default' => $owner->businesses()->count() === 0,
                ]);
            }

            $defaultPlan = Plan::where('price', 30.00)->where('is_active', true)->first()
                ?: Plan::where('slug', 'puntoagil')->first()
                ?: Plan::where('is_active', true)->first()
                ?: Plan::first();

            if ($defaultPlan) {
                Subscription::create([
                    'business_id' => $biz->id,
                    'plan_id' => $defaultPlan->id,
                    'status' => Subscription::STATUS_ACTIVE,
                    'billing_cycle' => 'monthly',
                    'price' => $defaultPlan->price,
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                    'payment_provider' => 'manual',
                    'notes' => 'Suscripción inicial asignada al registrar la empresa.',
                ]);
            }

            return $biz;
        });

        AuditLogger::record(
            'business.created',
            "Se registró la empresa {$business->name} con propietario {$validated['owner_email']}.",
            $business,
            [
                'slug' => $business->slug,
                'owner_email' => $validated['owner_email'],
            ],
            $business->id
        );

        return redirect()->route('admin.businesses.index')
            ->with('success', "La empresa {$business->name} y su propietario fueron registrados exitosamente.");
    }

    /**
     * Activa o suspende un negocio en la plataforma (Solo Super Admin).
     */
    public function toggleStatus(Business $business): RedirectResponse
    {
        Gate::authorize('delete', $business);

        $previousStatus = $business->status;
        $newStatus = $previousStatus === 'active' ? 'suspended' : 'active';

        $business->update(['status' => $newStatus]);

        AuditLogger::record(
            'business.status_toggled',
            "Se cambió el estado de la empresa {$business->name} de {$previousStatus} a {$newStatus}.",
            $business,
            [
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
            ],
            $business->id
        );

        return back()
            ->with('success', "Estado de {$business->name} actualizado a {$newStatus}.");
    }
}
