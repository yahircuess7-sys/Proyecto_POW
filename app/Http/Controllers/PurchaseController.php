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
            'pending_purchases' => Purchase::where('status', Purchase::STATUS_PENDING)->count(),
            'received_purchases' => Purchase::whereIn('status', [Purchase::STATUS_RECEIVED, Purchase::STATUS_COMPLETED])->count(),
            'total_amount' => (float) Purchase::whereIn('status', [Purchase::STATUS_RECEIVED, Purchase::STATUS_COMPLETED])->sum('total'),
            'this_month' => (float) Purchase::whereIn('status', [Purchase::STATUS_RECEIVED, Purchase::STATUS_COMPLETED])
                ->whereMonth('purchase_date', now()->month)
                ->whereYear('purchase_date', now()->year)
                ->sum('total'),
        ];

        return view('purchases.index', compact('purchases', 'suppliers', 'kpis'));
    }

    public function create(): View
    {
        Gate::authorize('create', Purchase::class);

        $business = $this->tenantManager->getTenant() ?: Auth::user()?->currentBusiness;
        $taxPercentage = $business ? $business->effectiveTaxPercentage(15.0) : 15.0;

        $suppliers = Supplier::where('status', Supplier::STATUS_ACTIVE)->orderBy('name')->get();
        $products = Product::where('status', Product::STATUS_ACTIVE)->orderBy('name')->get();

        return view('purchases.create', compact('suppliers', 'products', 'taxPercentage'));
    }

    public function store(PurchaseRequest $request): RedirectResponse
    {
        Gate::authorize('create', Purchase::class);

        $validated = $request->validated();
        $user = Auth::user();
        $supplier = Supplier::findOrFail($validated['supplier_id']);

        DB::beginTransaction();

        try {
            $grossSubtotal = 0;
            $itemsDiscounts = 0;

            foreach ($validated['items'] as $item) {
                $lineGross = $item['quantity'] * $item['unit_cost'];
                $lineDiscount = (float) ($item['discount'] ?? 0);
                $grossSubtotal += $lineGross;
                $itemsDiscounts += $lineDiscount;
            }

            $globalDiscount = (float) ($validated['discount'] ?? 0);
            $totalDiscount = $itemsDiscounts + $globalDiscount;
            $netSubtotal = max(0, $grossSubtotal - $totalDiscount);

            $business = $this->tenantManager->getTenant() ?: $user->currentBusiness;
            $taxPercentage = $business ? $business->effectiveTaxPercentage(15.0) : 15.0;
            $tax = $netSubtotal * ($taxPercentage / 100);
            $total = $netSubtotal + $tax;

            $purchaseNumber = 'COM-' . date('Ymd') . '-' . Str::upper(Str::random(4));
            $status = $validated['status'] ?? Purchase::STATUS_RECEIVED;
            $isReceivedImmediately = in_array($status, [Purchase::STATUS_RECEIVED, Purchase::STATUS_COMPLETED], true);

            $purchase = Purchase::create([
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
                'purchase_number' => $purchaseNumber,
                'supplier_invoice_number' => $validated['supplier_invoice_number'] ?? null,
                'purchase_date' => $validated['purchase_date'],
                'subtotal' => $grossSubtotal,
                'discount' => $totalDiscount,
                'tax' => $tax,
                'total' => $total,
                'status' => $status,
                'received_at' => $isReceivedImmediately ? now() : null,
                'received_by' => $isReceivedImmediately ? $user->id : null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                /** @var Product $product */
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->firstOrFail();

                $lineDiscount = (float) ($item['discount'] ?? 0);
                $itemSubtotal = max(0, ($item['quantity'] * $item['unit_cost']) - $lineDiscount);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'discount' => $lineDiscount,
                    'subtotal' => $itemSubtotal,
                ]);

                // Actualizar inventario ÚNICAMENTE si la mercancía ya fue recibida
                if ($isReceivedImmediately) {
                    $previousStock = $product->stock;
                    $newStock = $previousStock + $item['quantity'];

                    $product->update([
                        'cost' => $item['unit_cost'],
                        'stock' => $newStock,
                        'supplier_id' => $supplier->id,
                    ]);

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
            }

            AuditLogger::record(
                'purchase.created',
                "Se registró la compra #{$purchase->purchase_number} a {$supplier->name} por un total de C$ " . number_format((float) $total, 2) . " (" . ($isReceivedImmediately ? 'Mercancía Recibida e Ingresada' : 'Pendiente de Recepción') . ").",
                $purchase,
                [
                    'purchase_number' => $purchase->purchase_number,
                    'supplier_name' => $supplier->name,
                    'total' => $total,
                    'status' => $purchase->status,
                    'items_count' => count($validated['items']),
                ],
                $purchase->business_id
            );

            DB::commit();

            $successMsg = $isReceivedImmediately
                ? "Compra #{$purchase->purchase_number} registrada correctamente e inventario actualizado."
                : "Orden de compra #{$purchase->purchase_number} guardada como PENDIENTE. El inventario se actualizará cuando se registre la recepción de la mercancía.";

            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', $successMsg);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al procesar la compra: ' . $e->getMessage());
        }
    }

    /**
     * Recepción física de mercancía para órdenes de compra pendientes.
     * Incrementa inventario, actualiza costos y genera movimientos de Kardex.
     */
    public function receive(Request $request, Purchase $purchase): RedirectResponse
    {
        Gate::authorize('receive', $purchase);

        // Guard: Evitar registrar dos veces una recepción
        if ($purchase->isReceived()) {
            $formattedDate = $purchase->received_at ? $purchase->received_at->format('d/m/Y H:i') : 'previamente';
            return redirect()->back()->with('error', "Esta compra ya fue recibida {$formattedDate}. No es posible duplicar el ingreso de mercadería.");
        }

        // Guard: No recibir órdenes canceladas
        if ($purchase->isCancelled()) {
            return redirect()->back()->with('error', 'No se puede recibir mercancía de una orden de compra cancelada.');
        }

        DB::beginTransaction();

        try {
            $user = Auth::user();
            $purchase->load(['items.product', 'supplier']);

            foreach ($purchase->items as $item) {
                /** @var Product $product */
                $product = Product::where('id', $item->product_id)->lockForUpdate()->firstOrFail();

                $previousStock = $product->stock;
                $newStock = $previousStock + $item->quantity;

                $product->update([
                    'cost' => $item->unit_cost,
                    'stock' => $newStock,
                    'supplier_id' => $purchase->supplier_id,
                ]);

                InventoryMovement::create([
                    'business_id' => $purchase->business_id,
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'type' => InventoryMovement::TYPE_IN,
                    'quantity' => $item->quantity,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'unit_cost' => $item->unit_cost,
                    'reason' => "Recepción de Mercancía - Compra #{$purchase->purchase_number} (Factura: " . ($purchase->supplier_invoice_number ?: 'S/N') . " - Proveedor: " . ($purchase->supplier?->name ?? 'N/D') . ")",
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                ]);
            }

            $purchase->update([
                'status' => Purchase::STATUS_RECEIVED,
                'received_at' => now(),
                'received_by' => $user->id,
            ]);

            AuditLogger::record(
                'purchase.received',
                "Se recepcionó la mercadería de la compra #{$purchase->purchase_number} y se cargó a inventario.",
                $purchase,
                [
                    'purchase_number' => $purchase->purchase_number,
                    'total' => $purchase->total,
                    'items_count' => $purchase->items->count(),
                ],
                $purchase->business_id
            );

            DB::commit();

            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', "Mercancía de la orden #{$purchase->purchase_number} recibida con éxito e ingresada al inventario.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al procesar la recepción: ' . $e->getMessage());
        }
    }

    /**
     * Cancelación de órdenes de compra pendientes sin recepción física.
     */
    public function cancel(Request $request, Purchase $purchase): RedirectResponse
    {
        Gate::authorize('cancel', $purchase);

        // Guard: Prohibido cancelar compras ya recibidas e ingresadas a inventario
        if ($purchase->isReceived()) {
            return redirect()->back()->with('error', 'No se puede cancelar una compra que ya fue recibida e incorporada al inventario.');
        }

        if ($purchase->isCancelled()) {
            return redirect()->back()->with('error', 'Esta orden de compra ya se encuentra cancelada.');
        }

        $purchase->update([
            'status' => Purchase::STATUS_CANCELLED,
        ]);

        AuditLogger::record(
            'purchase.cancelled',
            "Se canceló la orden de compra pendiente #{$purchase->purchase_number}.",
            $purchase,
            ['purchase_number' => $purchase->purchase_number],
            $purchase->business_id
        );

        return redirect()->route('purchases.show', $purchase->id)
            ->with('success', "Orden de compra #{$purchase->purchase_number} cancelada exitosamente.");
    }

    public function show(Purchase $purchase): View
    {
        Gate::authorize('view', $purchase);

        $purchase->load(['supplier', 'user', 'receiver', 'items.product']);

        return view('purchases.show', compact('purchase'));
    }
}
