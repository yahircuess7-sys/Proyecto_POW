<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use App\Services\AuditLogger;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Supplier::class);

        $query = Supplier::query();

        if ($search = $request->input('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('tax_id', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $suppliers = $query->withCount(['purchases', 'products'])
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $kpis = [
            'total' => Supplier::count(),
            'active' => Supplier::where('status', Supplier::STATUS_ACTIVE)->count(),
            'inactive' => Supplier::where('status', Supplier::STATUS_INACTIVE)->count(),
            'with_purchases' => Supplier::has('purchases')->count(),
        ];

        return view('suppliers.index', compact('suppliers', 'kpis'));
    }

    public function create(): View
    {
        Gate::authorize('create', Supplier::class);

        return view('suppliers.form');
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        Gate::authorize('create', Supplier::class);

        $validated = $request->validated();
        $validated['status'] = $validated['status'] ?? Supplier::STATUS_ACTIVE;

        $supplier = Supplier::create($validated);

        AuditLogger::record(
            'supplier.created',
            "Se registró al proveedor {$supplier->name} (RUC/Identificación: {$supplier->tax_id}).",
            $supplier,
            [
                'name' => $supplier->name,
                'tax_id' => $supplier->tax_id,
                'contact_name' => $supplier->contact_name,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
            ],
            $supplier->business_id
        );

        return redirect()->route('suppliers.index')->with('success', 'Proveedor registrado correctamente.');
    }

    public function show(Supplier $supplier): View
    {
        Gate::authorize('view', $supplier);

        $supplier->load(['purchases' => function ($q) {
            $q->with('user')->orderByDesc('purchase_date')->limit(25);
        }, 'products']);

        $totalPurchased = (float) $supplier->purchases()->sum('total');
        $purchasesCount = $supplier->purchases()->count();
        $productsCount = $supplier->products()->count();

        return view('suppliers.show', compact('supplier', 'totalPurchased', 'purchasesCount', 'productsCount'));
    }

    public function edit(Supplier $supplier): View
    {
        Gate::authorize('update', $supplier);

        return view('suppliers.form', compact('supplier'));
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        Gate::authorize('update', $supplier);

        $validated = $request->validated();
        $supplier->update($validated);

        AuditLogger::record(
            'supplier.updated',
            "Se actualizaron los datos del proveedor {$supplier->name} ({$supplier->tax_id}).",
            $supplier,
            [
                'name' => $supplier->name,
                'tax_id' => $supplier->tax_id,
                'email' => $supplier->email,
                'status' => $supplier->status,
            ],
            $supplier->business_id
        );

        return redirect()->route('suppliers.index')->with('success', 'Proveedor actualizado correctamente.');
    }

    public function toggleStatus(Supplier $supplier): RedirectResponse
    {
        Gate::authorize('delete', $supplier);

        $previousStatus = $supplier->status;
        $newStatus = $previousStatus === Supplier::STATUS_ACTIVE ? Supplier::STATUS_INACTIVE : Supplier::STATUS_ACTIVE;

        $supplier->update(['status' => $newStatus]);

        AuditLogger::record(
            'supplier.status_toggled',
            "Se cambió el estado del proveedor {$supplier->name} de {$previousStatus} a {$newStatus}.",
            $supplier,
            ['previous' => $previousStatus, 'new' => $newStatus],
            $supplier->business_id
        );

        $statusText = $newStatus === Supplier::STATUS_ACTIVE ? 'activado' : 'desactivado';

        return redirect()->back()->with('success', "El proveedor {$supplier->name} ha sido {$statusText} correctamente.");
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        Gate::authorize('delete', $supplier);

        if ($supplier->purchases()->exists()) {
            return redirect()->route('suppliers.index')->with(
                'error',
                "No se puede eliminar al proveedor {$supplier->name} porque tiene compras registradas en el sistema. Puedes desactivarlo para suspender compras futuras sin perder el historial."
            );
        }

        $supplierName = $supplier->name;
        $taxId = $supplier->tax_id;
        $businessId = $supplier->business_id;

        $supplier->delete();

        AuditLogger::record(
            'supplier.deleted',
            "Se eliminó el registro del proveedor {$supplierName} ({$taxId}).",
            null,
            ['name' => $supplierName, 'tax_id' => $taxId],
            $businessId
        );

        return redirect()->route('suppliers.index')->with('success', 'Proveedor eliminado correctamente.');
    }
}
