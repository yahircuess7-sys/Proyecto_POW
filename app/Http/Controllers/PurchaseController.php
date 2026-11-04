<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Services\AuditLogger;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Purchase::class);

        $query = Purchase::with(['supplier', 'user']);

        if ($search = $request->input('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('purchase_number', 'like', "%{$search}%")
                    ->orWhere('supplier_invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('tax_id', 'like', "%{$search}%");
                    });
            });
        }

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('purchase_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('purchase_date', '<=', $dateTo);
        }

        $purchases = $query->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::where('status', Supplier::STATUS_ACTIVE)->orderBy('name')->get();

        $kpis = [
            'total_purchases' => Purchase::count(),
            'total_amount' => (float) Purchase::where('status', Purchase::STATUS_COMPLETED)->sum('total'),
            'this_month' => (float) Purchase::where('status', Purchase::STATUS_COMPLETED)
                ->whereMonth('purchase_date', now()->month)
                ->whereYear('purchase_date', now()->year)
                ->sum('total'),
        ];

        return view('purchases.index', compact('purchases', 'suppliers', 'kpis'));
    }

    public function create(): View
    {
        Gate::authorize('create', Purchase::class);

        $suppliers = Supplier::where('status', Supplier::STATUS_ACTIVE)->orderBy('name')->get();
        $products = Product::where('status', Product::STATUS_ACTIVE)->orderBy('name')->get();

        return view('purchases.create', compact('suppliers', 'products'));
    }

    public function store(PurchaseRequest $request): RedirectResponse
    {
        Gate::authorize('create', Purchase::class);

        $validated = $request->validated();
        $user = Auth::user();
        $supplier = Supplier::findOrFail($validated['supplier_id']);

        DB::beginTransaction();

        try {
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_cost'];
            }

            $business = $this->tenantManager->getTenant() ?: $user->currentBusiness;
            $taxPercentage = (float) ($business?->settings['tax_percentage'] ?? 12.0);
            $tax = $subtotal * ($taxPercentage / 100);
            $total = $subtotal + $tax;

            $purchaseNumber = 'COM-' . date('Ymd') . '-' . Str::upper(Str::random(4));

            $purchase = Purchase::create([
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
                'purchase_number' => $purchaseNumber,
                'supplier_invoice_number' => $validated['supplier_invoice_number'] ?? null,
                'purchase_date' => $validated['purchase_date'],
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'status' => Purchase::STATUS_COMPLETED,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                /** @var Product $product */
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->firstOrFail();

                $itemSubtotal = $item['quantity'] * $item['unit_cost'];

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $itemSubtotal,
                ]);

                // Actualizar costo unitario del producto con el valor más reciente de compra
                $previousStock = $product->stock;
                $newStock = $previousStock + $item['quantity'];

                $product->update([
                    'cost' => $item['unit_cost'],
                    'stock' => $newStock,
                    'supplier_id' => $supplier->id, // Asocia el proveedor a la ficha del producto
                ]);

                // Registrar movimiento de inventario de entrada con trazabilidad a la compra
                InventoryMovement::create([
                    'business_id' => $purchase->business_id,
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'type' => InventoryMovement::TYPE_IN,
                    'quantity' => $item['quantity'],
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'unit_cost' => $item['unit_cost'],
                    'reason' => "Compra #{$purchaseNumber} - Factura: " . ($purchase->supplier_invoice_number ?: 'S/N') . " (Proveedor: {$supplier->name})",
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                ]);
            }

            AuditLogger::record(
                'purchase.created',
                "Se registró la compra #{$purchase->purchase_number} a {$supplier->name} por un total de \${$total}.",
                $purchase,
                [
                    'purchase_number' => $purchase->purchase_number,
                    'supplier_name' => $supplier->name,
                    'total' => $total,
                    'items_count' => count($validated['items']),
                ],
                $purchase->business_id
            );

            DB::commit();

            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', "Compra #{$purchase->purchase_number} registrada correctamente e inventario actualizado.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al procesar la compra: ' . $e->getMessage());
        }
    }

    public function show(Purchase $purchase): View
    {
        Gate::authorize('view', $purchase);

        $purchase->load(['supplier', 'user', 'items.product']);

        return view('purchases.show', compact('purchase'));
    }
}
