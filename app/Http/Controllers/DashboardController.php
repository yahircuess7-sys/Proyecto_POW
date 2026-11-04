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
        $businessId = $this->tenantManager->getTenantId() ?: Auth::user()?->current_business_id;
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
            'recentSales'
        ));
    }
}
