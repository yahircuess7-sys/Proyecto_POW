<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now();

        $salesToday = Sale::whereDate('created_at', $today)->get();
        $ordersToday = $salesToday->count();
        $dailyTotal = $salesToday->sum('total');
        $averageTicket = $ordersToday ? $salesToday->avg('total') : 0;

        $totalProducts = Product::count();
        $totalCategories = Product::distinct('category')->count('category');
        $lowStockCount = Product::whereBetween('stock', [1, 10])->count();
        $outOfStockCount = Product::where('stock', '<=', 0)->count();

        return view('dashboard', compact(
            'dailyTotal',
            'ordersToday',
            'averageTicket',
            'totalProducts',
            'totalCategories',
            'lowStockCount',
            'outOfStockCount'
        ));
    }
}
