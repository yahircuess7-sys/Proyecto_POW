<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\Business;
use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use App\Services\TenantManager;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected TenantManager $tenantManager
    ) {}

    /**
     * Pantalla del Punto de Venta (POS).
     */
    public function index(): View
    {
        Gate::authorize('viewAny', Sale::class);

        $products = Product::where('stock', '>', 0)
            ->where('status', Product::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        $customers = Customer::where('status', Customer::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        $businessId = $this->tenantManager->getTenantId() ?: Auth::user()?->current_business_id;

        // Turno de caja abierto para el cajero
        $activeShift = CashShift::with('cashRegister')
            ->where('business_id', $businessId)
            ->where('status', CashShift::STATUS_OPEN)
            ->where('user_id', Auth::id())
            ->first();

        if (! $activeShift) {
            $activeShift = CashShift::with('cashRegister')
                ->where('business_id', $businessId)
                ->where('status', CashShift::STATUS_OPEN)
                ->latest('opened_at')
                ->first();
        }

        return view('sales.index', compact('products', 'customers', 'activeShift'));
    }

    public function create(): View
    {
        Gate::authorize('create', Sale::class);

        return $this->index();
    }

    /**
     * Registra una venta atómicamente con soporte de idempotencia, descuentos,
     * múltiples formas de pago y deducción garantizada de inventario.
     */
    public function store(StoreSaleRequest $request): JsonResponse
    {
        Gate::authorize('create', Sale::class);

        $validated = $request->validated();
        $businessId = $this->tenantManager->getTenantId() ?: Auth::user()?->current_business_id;

        // Control de Idempotencia: previene doble venta por reintento o doble clic
        if (! empty($validated['idempotency_key'])) {
            $existingSale = Sale::where('business_id', $businessId)
                ->where('idempotency_key', $validated['idempotency_key'])
                ->first();

            if ($existingSale) {
                return response()->json([
                    'success' => true,
                    'sale_id' => $existingSale->id,
                    'invoice_number' => $existingSale->invoice_number,
                    'idempotent' => true,
                    'message' => 'Venta recuperada por clave de idempotencia existente.',
                ]);
            }
        }

        $items = $validated['items'];

        $customerId = $validated['customer_id'] ?? null;
        $customer = $customerId ? Customer::find($customerId) : null;
        $customerName = $customer ? $customer->name : ($validated['customer_name'] ?? 'Consumidor Final');
        $customerDocument = $customer ? $customer->document_number : ($validated['customer_document'] ?? '---');

        // Localizar turno de caja abierto para vincular la venta
        $activeShift = CashShift::where('business_id', $businessId)
            ->where('status', CashShift::STATUS_OPEN)
            ->where('user_id', Auth::id())
            ->first();

        if (! $activeShift) {
            $activeShift = CashShift::where('business_id', $businessId)
                ->where('status', CashShift::STATUS_OPEN)
                ->latest('opened_at')
                ->first();
        }

        DB::beginTransaction();

        try {
            $subtotal = 0;
            $itemsData = [];

            foreach ($items as $item) {
                /** @var Product|null $product */
                $product = Product::where('id', $item['product_id'])
                    ->where('business_id', $businessId)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    throw new \Exception("Producto no autorizado o no encontrado en el inventario de este negocio.");
                }

                if (! $product->isActive()) {
                    throw new \Exception("El producto '{$product->name}' se encuentra desactivado para ventas.");
                }

                $qty = (int) $item['quantity'];
                if ($product->stock < $qty) {
                    throw new \Exception("Stock insuficiente para '{$product->name}'. Existencias actuales: {$product->stock}, solicitadas: {$qty}.");
                }

                $itemDiscount = max(0, (float) ($item['discount'] ?? 0));
                // Validar precio real del catálogo en el servidor para impedir manipulación desde el cliente
                $unitPrice = (float) ($product->price > 0 ? $product->price : $item['unit_price']);
                $lineSubtotal = max(0, ($qty * $unitPrice) - $itemDiscount);

                $subtotal += $lineSubtotal;

                $itemsData[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_cost' => (float) ($product->cost ?? 0),
                    'discount' => $itemDiscount,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $saleDiscount = max(0, (float) ($validated['discount'] ?? 0));
            $taxableBase = max(0, $subtotal - $saleDiscount);

            $activeBusiness = $this->tenantManager->getTenant() ?: Business::find($businessId);
            $effectiveTaxRate = ($activeBusiness ? $activeBusiness->effectiveTaxPercentage(12.0) : 12.0) / 100.0;
            $tax = round($taxableBase * $effectiveTaxRate, 2);
            $total = round($taxableBase + $tax, 2);

            $paymentMethod = $validated['payment_method'] ?? 'cash';
            $paidAmount = (float) ($validated['paid_amount'] ?? $total);

            if ($paymentMethod === 'cash') {
                if ($paidAmount < $total) {
                    throw new \Exception("El efectivo entregado ($" . number_format($paidAmount, 2) . ") es menor al total de la venta ($" . number_format($total, 2) . ").");
                }
                $changeAmount = round($paidAmount - $total, 2);
            } else {
                $paidAmount = $total;
                $changeAmount = 0.00;
            }

            $sale = Sale::create([
                'business_id' => $businessId,
                'cash_shift_id' => $activeShift?->id,
                'invoice_number' => 'REC-' . strtoupper(dechex(time())) . '-' . rand(100, 999),
                'user_id' => Auth::id() ?: 1,
                'customer_id' => $customer?->id,
                'customer_name' => $customerName,
                'customer_document' => $customerDocument,
                'subtotal' => $subtotal,
                'discount' => $saleDiscount,
                'tax' => $tax,
                'total' => $total,
                'payment_method' => $paymentMethod,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'status' => Sale::STATUS_COMPLETED,
                'idempotency_key' => $validated['idempotency_key'] ?? null,
            ]);

            foreach ($itemsData as $row) {
                // Deduce stock y asienta movimiento de inventario trazable con lock
                $this->inventoryService->recordSaleDeduction($row['product'], $row['quantity'], $sale, Auth::user());

                SaleItem::create([
                    'business_id' => $businessId,
                    'sale_id' => $sale->id,
                    'product_id' => $row['product']->id,
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'unit_cost' => $row['unit_cost'],
                    'discount' => $row['discount'],
                    'refunded_quantity' => 0,
                    'subtotal' => $row['subtotal'],
                ]);
            }

            AuditLogger::record(
                'sale_created',
                "Venta {$sale->invoice_number} por $" . number_format($sale->total, 2) . " [Método: {$sale->payment_method}]",
                $sale,
                [
                    'total' => $sale->total,
                    'payment_method' => $sale->payment_method,
                    'items_count' => count($itemsData),
                    'shift_id' => $activeShift?->id,
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'total' => $sale->total,
                'change_amount' => $sale->change_amount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Historial de ventas y comprobantes internos con filtros avanzados.
     */
    public function history(Request $request): View
    {
        Gate::authorize('viewAny', Sale::class);

        $query = Sale::with(['user', 'customer', 'cashShift', 'returns'])
            ->withCount('items')
            ->orderByDesc('created_at');

        if ($search = $request->input('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_document', 'like', "%{$search}%");
            });
        }

        if ($paymentMethod = $request->input('payment_method')) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $sales = $query->paginate(15)->withQueryString();
        $cashiers = User::where('status', 'active')->orderBy('name')->get();

        return view('sales.history', compact('sales', 'cashiers'));
    }

    /**
     * Muestra el detalle de una venta y sus devoluciones vinculadas.
     */
    public function show(Sale $sale): View
    {
        Gate::authorize('view', $sale);

        $sale->load([
            'items.product',
            'user',
            'customer',
            'cashShift.cashRegister',
            'returns.items.product',
            'returns.user',
        ]);

        return view('sales.show', compact('sale'));
    }

    /**
     * Exportación de Comprobante Interno a PDF (con leyenda explícita sin validez fiscal).
     */
    public function exportPdf(Sale $sale): Response
    {
        Gate::authorize('view', $sale);

        $sale->load(['items.product', 'user', 'customer', 'cashShift', 'business']);
        $business = $sale->business ?: $this->tenantManager->getTenant();

        $customerName = $sale->customer
            ? $sale->customer->name
            : $this->normalizeText(request()->input('customer_name', $sale->customer_name ?: 'Consumidor Final'));

        $customerDocument = $sale->customer
            ? ($sale->customer->document_type . ': ' . $sale->customer->document_number)
            : $this->normalizeText(request()->input('customer_document', $sale->customer_document ?: '---'));

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('sales.invoice_pdf', compact('sale', 'business', 'customerName', 'customerDocument'))->render());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="comprobante_' . $sale->invoice_number . '.pdf"',
        ]);
    }

    /**
     * Dashboard de Reportes y métricas operativas reales (Módulo 13).
     */
    public function reports(Request $request): View
    {
        Gate::authorize('viewReports', Sale::class);

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $userId = $request->input('user_id');

        $baseSalesQuery = Sale::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->where('status', '!=', Sale::STATUS_CANCELLED);

        if ($userId) {
            $baseSalesQuery->where('user_id', $userId);
        }

        // Totales del período
        $totalSales = (float) $baseSalesQuery->sum('total');
        $invoicesCount = $baseSalesQuery->count();
        $averageTicket = $invoicesCount ? ($totalSales / $invoicesCount) : 0;

        // Desglose por método de pago
        $cashSales = (float) $baseSalesQuery->clone()->where('payment_method', 'cash')->sum('total');
        $cardSales = (float) $baseSalesQuery->clone()->where('payment_method', 'card')->sum('total');
        $transferSales = (float) $baseSalesQuery->clone()->where('payment_method', 'transfer')->sum('total');

        // Total reembolsado en el período
        $totalRefunded = (float) SaleReturn::whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->sum('total_refunded');

        $netSales = max(0, $totalSales - $totalRefunded);

        // Ganancia Bruta Real calculada sobre costo registrado de ítems
        $grossMargin = (float) DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.business_id', $this->tenantManager->getTenantId() ?: Auth::user()?->current_business_id)
            ->whereDate('sales.created_at', '>=', $dateFrom)
            ->whereDate('sales.created_at', '<=', $dateTo)
            ->where('sales.status', '!=', Sale::STATUS_CANCELLED)
            ->selectRaw('SUM(sale_items.subtotal - (sale_items.quantity * sale_items.unit_cost)) as gross_profit')
            ->value('gross_profit') ?? 0.0;

        // Top 5 productos más vendidos en el período
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $this->tenantManager->getTenantId() ?: Auth::user()?->current_business_id)
            ->whereDate('sales.created_at', '>=', $dateFrom)
            ->whereDate('sales.created_at', '<=', $dateTo)
            ->where('sales.status', '!=', Sale::STATUS_CANCELLED)
            ->select(
                'products.name',
                'products.sku',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_quantity')
            ->take(5)
            ->get();

        // Ventas recientes para la tabla
        $transactions = $baseSalesQuery->clone()
            ->with(['user', 'customer'])
            ->withCount('items')
            ->orderByDesc('created_at')
            ->take(15)
            ->get();

        $cashiers = User::where('status', 'active')->orderBy('name')->get();

        return view('admin.reports', compact(
            'transactions',
            'totalSales',
            'netSales',
            'totalRefunded',
            'invoicesCount',
            'averageTicket',
            'grossMargin',
            'cashSales',
            'cardSales',
            'transferSales',
            'topProducts',
            'dateFrom',
            'dateTo',
            'userId',
            'cashiers'
        ));
    }

    /**
     * Exportación de reportes a CSV con cálculo de margen bruto y filtros aplicados.
     */
    public function exportReports(Request $request): Response
    {
        Gate::authorize('exportReports', Sale::class);

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $userId = $request->input('user_id');

        $query = Sale::with(['user', 'customer', 'items'])
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $sales = $query->orderByDesc('created_at')->get();

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fwrite($handle, "sep=;\r\n");

        fputcsv($handle, [
            'Comprobante Interno',
            'Fecha y Hora',
            'Cajero / Responsable',
            'Cliente',
            'Método Pago',
            'Cant. Artículos',
            'Subtotal',
            'Descuento',
            'Impuesto (IVA)',
            'Total',
            'Costo Mercancía',
            'Margen Bruto Estimado',
            'Estado',
        ], ';', '"');

        foreach ($sales as $sale) {
            $totalCost = $sale->items->sum(fn ($i) => $i->quantity * $i->unit_cost);
            $margin = $sale->subtotal - $totalCost;

            fputcsv($handle, [
                $this->escapeCsvValue($sale->invoice_number),
                $this->escapeCsvValue($sale->created_at->format('Y-m-d H:i:s')),
                $this->escapeCsvValue($sale->user->name ?? 'Cajero'),
                $this->escapeCsvValue($sale->customer_name ?? 'Consumidor Final'),
                $this->escapeCsvValue(strtoupper($sale->payment_method ?? 'CASH')),
                $sale->items->sum('quantity'),
                number_format($sale->subtotal, 2, '.', ''),
                number_format($sale->discount, 2, '.', ''),
                number_format($sale->tax, 2, '.', ''),
                number_format($sale->total, 2, '.', ''),
                number_format($totalCost, 2, '.', ''),
                number_format($margin, 2, '.', ''),
                $this->escapeCsvValue(strtoupper($sale->status)),
            ], ';', '"');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="reportes_ventas_' . $dateFrom . '_a_' . $dateTo . '.csv"',
            'Pragma' => 'public',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        ]);
    }

    private function normalizeText(string $value): string
    {
        $value = trim($value);

        if (function_exists('transliterator_transliterate')) {
            $value = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9 ] remove', $value);
        } else {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
            $value = preg_replace('/[^A-Za-z0-9 ]/', '', $value);
        }

        $value = preg_replace('/\s+/', ' ', $value);

        return $value === '' ? 'Consumidor Final' : $value;
    }

    private function escapeCsvValue(?string $value): string
    {
        $value = $this->normalizeCsvText($value);
        $value = str_replace('"', '""', $value);
        $value = str_replace(["\r\n", "\n", "\r", '\\r\\n', '\\n', '\\r'], ' ', $value);
        return $value;
    }

    private function normalizeCsvText(?string $value): string
    {
        $value = trim($value ?? '');

        if (function_exists('transliterator_transliterate')) {
            $value = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9 \-\.,;:\/_() ] remove', $value);
        } else {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
            $value = preg_replace('/[^A-Za-z0-9 \-\.,;:\/_() ]/', '', $value);
        }

        $value = preg_replace('/\s+/', ' ', $value);

        return $value;
    }
}
