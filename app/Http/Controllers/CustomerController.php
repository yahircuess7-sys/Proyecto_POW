<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\AuditLogger;
use App\Services\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        Gate::authorize('viewAny', Customer::class);

        $query = Customer::query();

        if ($search = $request->input('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($documentType = $request->input('document_type')) {
            $query->where('document_type', $documentType);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Si es petición JSON (para búsqueda rápida en el terminal POS)
        if ($request->wantsJson() || $request->ajax()) {
            $customers = $query->where('status', Customer::STATUS_ACTIVE)
                ->orderBy('name')
                ->limit(20)
                ->get(['id', 'name', 'document_type', 'document_number', 'phone', 'email', 'address']);

            return response()->json($customers);
        }

        $customers = $query->withCount('sales')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $kpis = [
            'total' => Customer::count(),
            'active' => Customer::where('status', Customer::STATUS_ACTIVE)->count(),
            'inactive' => Customer::where('status', Customer::STATUS_INACTIVE)->count(),
            'with_sales' => Customer::has('sales')->count(),
        ];

        return view('customers.index', compact('customers', 'kpis'));
    }

    public function create(): View
    {
        Gate::authorize('create', Customer::class);

        return view('customers.form');
    }

    public function store(CustomerRequest $request): RedirectResponse|JsonResponse
    {
        Gate::authorize('create', Customer::class);

        $validated = $request->validated();
        $validated['status'] = $validated['status'] ?? Customer::STATUS_ACTIVE;

        $customer = Customer::create($validated);

        AuditLogger::record(
            'customer.created',
            "Se registró al cliente {$customer->name} ({$customer->document_type}: {$customer->document_number}).",
            $customer,
            [
                'name' => $customer->name,
                'document_type' => $customer->document_type,
                'document_number' => $customer->document_number,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ],
            $customer->business_id
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Cliente registrado exitosamente.',
                'customer' => $customer,
            ], 201);
        }

        return redirect()->route('customers.index')->with('success', 'Cliente registrado correctamente.');
    }

    public function show(Customer $customer): View
    {
        Gate::authorize('view', $customer);

        $customer->load(['sales' => function ($q) {
            $q->with('user')->orderByDesc('created_at')->limit(25);
        }]);

        $totalSpent = (float) $customer->sales()->sum('total');
        $salesCount = $customer->sales()->count();
        $averageTicket = $salesCount > 0 ? ($totalSpent / $salesCount) : 0.00;

        return view('customers.show', compact('customer', 'totalSpent', 'salesCount', 'averageTicket'));
    }

    public function edit(Customer $customer): View
    {
        Gate::authorize('update', $customer);

        return view('customers.form', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        Gate::authorize('update', $customer);

        $validated = $request->validated();
        $customer->update($validated);

        AuditLogger::record(
            'customer.updated',
            "Se actualizaron los datos del cliente {$customer->name} ({$customer->document_number}).",
            $customer,
            [
                'name' => $customer->name,
                'document_number' => $customer->document_number,
                'email' => $customer->email,
                'status' => $customer->status,
            ],
            $customer->business_id
        );

        return redirect()->route('customers.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function toggleStatus(Customer $customer): RedirectResponse
    {
        Gate::authorize('delete', $customer);

        $previousStatus = $customer->status;
        $newStatus = $previousStatus === Customer::STATUS_ACTIVE ? Customer::STATUS_INACTIVE : Customer::STATUS_ACTIVE;

        $customer->update(['status' => $newStatus]);

        AuditLogger::record(
            'customer.status_toggled',
            "Se cambió el estado del cliente {$customer->name} de {$previousStatus} a {$newStatus}.",
            $customer,
            ['previous' => $previousStatus, 'new' => $newStatus],
            $customer->business_id
        );

        $statusText = $newStatus === Customer::STATUS_ACTIVE ? 'activado' : 'desactivado';

        return redirect()->back()->with('success', "El cliente {$customer->name} ha sido {$statusText} correctamente.");
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        Gate::authorize('delete', $customer);

        if ($customer->sales()->exists()) {
            return redirect()->route('customers.index')->with(
                'error',
                "No se puede eliminar al cliente {$customer->name} porque tiene transacciones de venta asociadas. Puedes desactivarlo para suspender su uso sin romper la trazabilidad fiscal."
            );
        }

        $customerName = $customer->name;
        $document = $customer->document_number;
        $businessId = $customer->business_id;

        $customer->delete();

        AuditLogger::record(
            'customer.deleted',
            "Se eliminó el registro del cliente {$customerName} ({$document}).",
            null,
            ['name' => $customerName, 'document' => $document],
            $businessId
        );

        return redirect()->route('customers.index')->with('success', 'Cliente eliminado correctamente.');
    }
}
