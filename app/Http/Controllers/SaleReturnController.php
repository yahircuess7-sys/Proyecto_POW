<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SaleReturnController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected TenantManager $tenantManager
    ) {}

    /**
     * Listado de devoluciones y notas de crédito de ventas.
     */
    public function index(Request $request): View|RedirectResponse
    {
        Gate::authorize('viewAny', SaleReturn::class);

        $businessId = $this->tenantManager->getTenantId() ?: Auth::user()?->current_business_id;

        // Búsqueda rápida de factura para iniciar una devolución desde esta misma pantalla
        $invoiceQuery = trim((string) $request->input('invoice_lookup', ''));
        $refundableSalesQuery = Sale::with(['items', 'customer', 'user'])
            ->where('business_id', $businessId)
            ->whereIn('status', [Sale::STATUS_COMPLETED, Sale::STATUS_PARTIALLY_REFUNDED])
            ->orderByDesc('created_at');

        if ($invoiceQuery !== '') {
            $exactSale = (clone $refundableSalesQuery)
                ->whereRaw('LOWER(invoice_number) = ?', [strtolower($invoiceQuery)])
                ->first();

            if ($exactSale && $exactSale->canBeRefunded()) {
                return redirect()->route('sales.returns.create', $exactSale);
            }

            $refundableSalesQuery->where(function ($q) use ($invoiceQuery) {
                $q->where('invoice_number', 'like', "%{$invoiceQuery}%")
                    ->orWhere('customer_name', 'like', "%{$invoiceQuery}%");
            });
        }

        $refundableSales = $refundableSalesQuery->take(8)->get()->filter(fn (Sale $s) => $s->canBeRefunded())->values();

        $query = SaleReturn::with(['sale', 'user', 'cashShift', 'items.product'])
            ->orderByDesc('created_at');

        if ($search = $request->input('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('return_number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('sale', function ($q) use ($search) {
                        $q->where('invoice_number', 'like', "%{$search}%")
                            ->orWhere('customer_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($refundMethod = $request->input('refund_method')) {
            $query->where('refund_method', $refundMethod);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $returns = $query->paginate(15)->withQueryString();

        return view('sales.returns.index', compact('returns', 'refundableSales', 'invoiceQuery'));
    }

    /**
     * Muestra el formulario o modal para registrar una devolución de una venta.
     */
    public function create(Sale $sale): View|RedirectResponse
    {
        Gate::authorize('create', [SaleReturn::class, $sale]);

        if (! $sale->canBeRefunded()) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Esta venta ya ha sido devuelta en su totalidad o se encuentra anulada.');
        }

        $sale->load(['items.product', 'customer', 'user']);

        $businessId = $this->tenantManager->getTenantId() ?: Auth::user()?->current_business_id;
        $activeShift = CashShift::where('business_id', $businessId)
            ->where('status', CashShift::STATUS_OPEN)
            ->where('user_id', Auth::id())
            ->first();

        return view('sales.returns.create', compact('sale', 'activeShift'));
    }

    /**
     * Procesa atómicamente la devolución de mercancía y reposición de stock.
     */
    public function store(Request $request, Sale $sale): RedirectResponse
    {
        Gate::authorize('create', [SaleReturn::class, $sale]);

        if (! $sale->canBeRefunded()) {
            return back()->with('error', 'La venta ya fue devuelta en su totalidad y no admite devoluciones adicionales.');
        }

        $validated = $request->validate([
            'refund_method' => ['required', 'in:cash,credit_note,card_reversal'],
            'reason' => ['required', 'string', 'min:4', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:0'],
        ], [
            'reason.required' => 'Por favor indica el motivo de la devolución.',
            'reason.min' => 'El motivo de la devolución debe tener al menos 4 caracteres.',
            'refund_method.required' => 'Selecciona el método de reembolso.',
        ]);

        // Filtrar solo items con cantidad > 0
        $itemsToReturn = array_filter($validated['items'], fn ($item) => (int) ($item['quantity'] ?? 0) > 0);

        if (empty($itemsToReturn)) {
            return back()->withErrors(['items' => 'Debe seleccionar al menos un producto y una cantidad mayor a cero para devolver.'])->withInput();
        }

        $businessId = $this->tenantManager->getTenantId() ?: Auth::user()?->current_business_id;

        // Si el reembolso es en efectivo, asociar con turno activo si existe
        $activeShift = CashShift::where('business_id', $businessId)
            ->where('status', CashShift::STATUS_OPEN)
            ->where('user_id', Auth::id())
            ->first();

        if (! $activeShift && (Auth::user()->isAdmin() || Auth::user()->isOwner())) {
            $activeShift = CashShift::where('business_id', $businessId)
                ->where('status', CashShift::STATUS_OPEN)
                ->first();
        }

        try {
            $saleReturn = DB::transaction(function () use ($sale, $itemsToReturn, $validated, $businessId, $activeShift) {
                // Bloquear la venta para evitar devoluciones concurrentes
                $lockedSale = Sale::where('id', $sale->id)->lockForUpdate()->firstOrFail();

                $totalRefunded = 0;
                $returnRecords = [];

                foreach ($itemsToReturn as $itemData) {
                    /** @var SaleItem $saleItem */
                    $saleItem = SaleItem::where('id', $itemData['sale_item_id'])
                        ->where('sale_id', $lockedSale->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $quantity = (int) $itemData['quantity'];
                    $available = $saleItem->availableForRefund();

                    if ($quantity > $available) {
                        throw new \Exception("La cantidad ({$quantity}) a devolver del producto '{$saleItem->product->name}' supera la cantidad disponible ({$available}).");
                    }

                    $itemSubtotal = $quantity * (float) $saleItem->unit_price;
                    $totalRefunded += $itemSubtotal;

                    $returnRecords[] = [
                        'sale_item' => $saleItem,
                        'quantity' => $quantity,
                        'subtotal' => $itemSubtotal,
                    ];
                }

                // Generar número de devolución trazable
                $returnNumber = 'DEV-' . strtoupper(Str::random(8));

                $saleReturn = SaleReturn::create([
                    'business_id' => $businessId,
                    'sale_id' => $lockedSale->id,
                    'user_id' => Auth::id(),
                    'cash_shift_id' => $activeShift?->id,
                    'return_number' => $returnNumber,
                    'total_refunded' => $totalRefunded,
                    'refund_method' => $validated['refund_method'],
                    'reason' => $validated['reason'],
                ]);

                foreach ($returnRecords as $record) {
                    /** @var SaleItem $saleItem */
                    $saleItem = $record['sale_item'];
                    $qty = $record['quantity'];
                    $subtotal = $record['subtotal'];

                    // Actualizar cantidad devuelta en el ítem de la venta original
                    $saleItem->increment('refunded_quantity', $qty);

                    // Registrar el ítem devuelto
                    SaleReturnItem::create([
                        'business_id' => $businessId,
                        'sale_return_id' => $saleReturn->id,
                        'sale_item_id' => $saleItem->id,
                        'product_id' => $saleItem->product_id,
                        'quantity' => $qty,
                        'unit_price' => $saleItem->unit_price,
                        'subtotal' => $subtotal,
                    ]);

                    // Reponer el inventario con registro atómico en Kardex
                    /** @var Product $product */
                    $product = Product::where('id', $saleItem->product_id)->lockForUpdate()->firstOrFail();
                    $this->inventoryService->recordReturnRestock($product, $qty, $saleReturn, Auth::user());
                }

                // Evaluar si todos los ítems fueron devueltos en su totalidad
                $remainingItems = SaleItem::where('sale_id', $lockedSale->id)
                    ->get()
                    ->sum(fn ($i) => $i->availableForRefund());

                $newStatus = ($remainingItems === 0) ? Sale::STATUS_REFUNDED : Sale::STATUS_PARTIALLY_REFUNDED;
                $lockedSale->update(['status' => $newStatus]);

                AuditLogger::record(
                    'sale_return_registered',
                    "Devolución {$returnNumber} por C$ " . number_format($totalRefunded, 2) . " asociada a venta {$lockedSale->invoice_number}. Motivo: {$validated['reason']}",
                    $saleReturn,
                    [
                        'sale_id' => $lockedSale->id,
                        'invoice_number' => $lockedSale->invoice_number,
                        'total_refunded' => $totalRefunded,
                        'refund_method' => $validated['refund_method'],
                    ]
                );

                return $saleReturn;
            });

            return redirect()->route('sales.returns.show', $saleReturn)->with('success', 'Devolución registrada exitosamente. Se ha restituido el stock al inventario.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Muestra el comprobante y detalle de una devolución.
     */
    public function show(SaleReturn $saleReturn): View
    {
        Gate::authorize('view', $saleReturn);

        $saleReturn->load([
            'sale.customer',
            'sale.user',
            'user',
            'cashShift',
            'items.product',
        ]);

        return view('sales.returns.show', compact('saleReturn'));
    }
}
