<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    public function index(): View
    {
        $activeBusiness = $this->tenantManager->getTenant() ?: Auth::user()?->currentBusiness;
        $businessId = $activeBusiness?->id ?: Auth::user()?->current_business_id;
        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();

        // 1. Métricas de Ventas de Hoy
        $salesToday = Sale::where('business_id', $businessId)
            ->whereDate('created_at', $today)
            ->where('status', '!=', Sale::STATUS_CANCELLED)
            ->get();

        $ordersToday = $salesToday->count();
        $dailyTotal = (float) $salesToday->sum('total');
        $averageTicket = $ordersToday ? ($dailyTotal / $ordersToday) : 0;

        // 2. Métricas del Mes en Curso
        $salesMonth = Sale::where('business_id', $businessId)
            ->whereDate('created_at', '>=', $startOfMonth)
            ->where('status', '!=', Sale::STATUS_CANCELLED)
            ->get();

        $monthlyTotal = (float) $salesMonth->sum('total');
        $monthlyOrders = $salesMonth->count();

        // 3. Ganancia Bruta Real del Mes (Subtotal - Costo de Artículos)
        $monthlyGrossProfit = (float) DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.business_id', $businessId)
            ->whereDate('sales.created_at', '>=', $startOfMonth)
            ->where('sales.status', '!=', Sale::STATUS_CANCELLED)
            ->selectRaw('SUM(sale_items.subtotal - (sale_items.quantity * sale_items.unit_cost)) as profit')
            ->value('profit') ?? 0.0;

        // 4. Estado de Caja Registradora / Turno Activo
        $activeShift = CashShift::with(['cashRegister', 'user'])
            ->where('business_id', $businessId)
            ->where('status', CashShift::STATUS_OPEN)
            ->latest('opened_at')
            ->first();

        // 5. Inventario y Alertas
        $totalProducts = Product::where('business_id', $businessId)->count();
        $totalCategories = Product::where('business_id', $businessId)
            ->whereNotNull('category')
            ->distinct('category')
            ->count('category');

        $lowStockProducts = Product::where('business_id', $businessId)
            ->where('status', Product::STATUS_ACTIVE)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->take(6)
            ->get();

        $lowStockCount = Product::where('business_id', $businessId)
            ->where('status', Product::STATUS_ACTIVE)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        $outOfStockCount = Product::where('business_id', $businessId)
            ->where('stock', '<=', 0)
            ->count();

        // 6. Top 5 Productos más vendidos
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.business_id', $businessId)
            ->where('sales.status', '!=', Sale::STATUS_CANCELLED)
            ->select(
                'products.name',
                'products.sku',
                DB::raw('SUM(sale_items.quantity) as total_sold'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        // 7. Movimientos Recientes de Inventario
        $recentMovements = InventoryMovement::with(['product', 'user'])
            ->where('business_id', $businessId)
            ->latest('id')
            ->take(6)
            ->get();

        // 8. Ventas Recientes
        $recentSales = Sale::with(['user', 'customer'])
            ->where('business_id', $businessId)
            ->latest('created_at')
            ->take(6)
            ->get();

        // 9. Onboarding Checklist ("Primeros Pasos en 3 minutos")
        $hasConfiguredTicket = $activeBusiness && (
            ! empty($activeBusiness->settings['ticket_configured'])
            || (! empty($activeBusiness->phone) && ! empty($activeBusiness->address))
        );
        $hasProducts = $totalProducts > 0;
        $hasOpenedShift = $activeShift !== null || CashShift::where('business_id', $businessId)->exists();
        $hasFirstSale = $recentSales->where('status', '!=', Sale::STATUS_CANCELLED)->isNotEmpty()
            || Sale::where('business_id', $businessId)->where('status', '!=', Sale::STATUS_CANCELLED)->exists();

        $onboardingSteps = [
            [
                'key' => 'business_created',
                'title' => 'Negocio creado y activado',
                'description' => 'Tu espacio empresarial y base de datos en la nube están listos.',
                'done' => true,
                'url' => null,
                'cta' => 'Completado',
                'icon' => 'bi-building-check',
            ],
            [
                'key' => 'ticket_configured',
                'title' => 'Configura los datos de tu ticket y factura',
                'description' => 'Agrega teléfono, dirección, RUC opcional y logo para tus recibos.',
                'done' => $hasConfiguredTicket,
                'url' => Auth::user()?->can('manage-business-settings') ? route('admin.business.settings') : null,
                'cta' => 'Configurar ticket',
                'icon' => 'bi-receipt-cutoff',
            ],
            [
                'key' => 'first_product',
                'title' => 'Agrega tu primer producto o importa tu inventario',
                'description' => 'Crea un artículo rápido o sube tu plantilla Excel/CSV en segundos.',
                'done' => $hasProducts,
                'url' => route('inventory.index'),
                'cta' => 'Cargar inventario',
                'icon' => 'bi-box-seam',
            ],
            [
                'key' => 'first_shift',
                'title' => 'Abre tu primera caja registradora',
                'description' => 'Inicia un turno indicando el fondo inicial de efectivo en gaveta.',
                'done' => $hasOpenedShift,
                'url' => route('cash.current'),
                'cta' => 'Abrir caja',
                'icon' => 'bi-safe2',
            ],
            [
                'key' => 'first_sale',
                'title' => 'Realiza tu primera venta en el POS',
                'description' => 'Prueba el cobro rápido en Córdobas (C$) e imprime tu primer ticket.',
                'done' => $hasFirstSale,
                'url' => route('pos.create'),
                'cta' => 'Ir al POS',
                'icon' => 'bi-cart-check',
            ],
        ];

        $completedStepsCount = collect($onboardingSteps)->where('done', true)->count();
        $showOnboarding = $completedStepsCount < count($onboardingSteps);

        return view('dashboard', compact(
            'dailyTotal',
            'ordersToday',
            'averageTicket',
            'monthlyTotal',
            'monthlyOrders',
            'monthlyGrossProfit',
            'activeShift',
            'totalProducts',
            'totalCategories',
            'lowStockCount',
            'lowStockProducts',
            'outOfStockCount',
            'topProducts',
            'recentMovements',
            'recentSales',
            'onboardingSteps',
            'completedStepsCount',
            'showOnboarding'
        ));
    }
}
