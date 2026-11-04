@extends('layouts.app')

@section('title', 'Panel de Control - PuntoÁgil')

@section('content')
<div class="space-y-6">

    <!-- Active Cash Shift Banner -->
    @if($activeShift)
        <div class="animate-fade-up p-3.5 rounded-2xl bg-gradient-to-r from-emerald-500/10 via-teal-500/10 to-emerald-500/5 border border-emerald-500/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-subtle">
            <div class="flex items-center gap-3">
                <span class="flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
                <div class="text-xs">
                    <span class="font-extrabold text-emerald-900 uppercase tracking-wide">Caja Activa:</span>
                    <strong class="text-slate-900 ml-1">{{ $activeShift->cashRegister->name ?? 'Caja Principal' }}</strong>
                    <span class="text-slate-400 mx-1.5">&bull;</span>
                    <span class="text-slate-600">Turno #{{ $activeShift->id }} &bull; Cajero: <strong>{{ $activeShift->user->name ?? 'Cajero' }}</strong></span>
                    <span class="text-slate-400 mx-1.5">&bull;</span>
                    <span class="font-mono text-emerald-800 font-bold">Efectivo Gaveta: ${{ number_format($activeShift->calculateExpectedCash(), 2) }}</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('cash.current') }}" class="px-3 py-1.5 rounded-xl bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition-colors shadow-sm">
                    Ver Turno
                </a>
                <a href="{{ route('pos.create') }}" class="px-3 py-1.5 rounded-xl bg-slate-900 text-white font-bold text-xs hover:bg-slate-800 transition-colors shadow-sm">
                    Ir al POS
                </a>
            </div>
        </div>
    @else
        <div class="animate-fade-up p-3.5 rounded-2xl bg-amber-50 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-subtle text-xs">
            <div class="flex items-center gap-2.5">
                <i class="bi bi-exclamation-circle-fill text-amber-600 text-base"></i>
                <span class="text-amber-900">
                    <strong>Atención operativa:</strong> No hay un turno de caja abierto en este momento. Las ventas en efectivo requieren abrir turno.
                </span>
            </div>
            <a href="{{ route('cash.current') }}" class="px-3 py-1.5 rounded-xl bg-amber-600 text-white font-bold text-xs hover:bg-amber-700 transition-colors whitespace-nowrap">
                Abrir Turno de Caja
            </a>
        </div>
    @endif

    <!-- Welcome & Header -->
    <div class="animate-fade-up flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                ¡Bienvenido, {{ auth()->user()->name ?? 'Administrador' }}!
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Monitoreo operativo de ventas, margen bruto estimado, estado de caja y existencias en tiempo real.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('sales.history') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold shadow-subtle transition-all">
                <i class="bi bi-receipt text-slate-500"></i>
                <span>Historial Ventas</span>
            </a>
            <a href="{{ route('pos.create') }}" 
               class="btn-shine inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 shadow-sm transition-all">
                <i class="bi bi-cart4 text-base"></i>
                <span>Terminal POS</span>
            </a>
        </div>
    </div>

    <!-- 4 KPI Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- KPI 1: Ventas del Día -->
        <div class="group saas-card-hover animate-fade-up stagger-1 p-4 rounded-2xl bg-white border border-slate-200/80 shadow-subtle flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Ventas Hoy</span>
                <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100">
                    <i class="bi bi-cash-stack text-base"></i>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-xl font-bold font-mono text-slate-900 tracking-tight">
                    ${{ number_format($dailyTotal, 2) }}
                </h3>
                <p class="mt-1 text-[11px] font-semibold text-emerald-600 flex items-center gap-1">
                    <i class="bi bi-receipt"></i>
                    <span>{{ $ordersToday }} comprobante{{ $ordersToday === 1 ? '' : 's' }} hoy</span>
                </p>
            </div>
        </div>

        <!-- KPI 2: Ventas del Mes -->
        <div class="group saas-card-hover animate-fade-up stagger-2 p-4 rounded-2xl bg-white border border-slate-200/80 shadow-subtle flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Facturación Mes</span>
                <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-blue-50 text-blue-600 border border-blue-100">
                    <i class="bi bi-calendar3 text-base"></i>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-xl font-bold font-mono text-slate-900 tracking-tight">
                    ${{ number_format($monthlyTotal, 2) }}
                </h3>
                <p class="mt-1 text-[11px] text-slate-500 font-medium">
                    <span>{{ $monthlyOrders }} ventas en el mes</span>
                </p>
            </div>
        </div>

        <!-- KPI 3: Ganancia Bruta Real del Mes -->
        <div class="group saas-card-hover animate-fade-up stagger-3 p-4 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-950 text-white shadow-card flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-400">Utilidad Bruta Mes</span>
                <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-white/10 text-emerald-400">
                    <i class="bi bi-graph-up-arrow text-base"></i>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-xl font-black font-mono text-white tracking-tight">
                    ${{ number_format($monthlyGrossProfit, 2) }}
                </h3>
                <p class="mt-1 text-[10px] text-slate-400">
                    <span>Sobre costo real de mercancía</span>
                </p>
            </div>
        </div>

        <!-- KPI 4: Stock Crítico / Alertas -->
        <div class="group saas-card-hover animate-fade-up stagger-4 p-4 rounded-2xl bg-white border border-slate-200/80 shadow-subtle flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Alertas de Stock</span>
                <div class="flex items-center justify-center w-9 h-9 rounded-xl {{ ($lowStockCount + $outOfStockCount) > 0 ? 'bg-amber-50 text-amber-600 border border-amber-100' : 'bg-slate-50 text-slate-400' }}">
                    <i class="bi bi-exclamation-triangle-fill text-base"></i>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-xl font-bold font-mono {{ ($lowStockCount + $outOfStockCount) > 0 ? 'text-amber-600' : 'text-slate-900' }} tracking-tight">
                    {{ $lowStockCount + $outOfStockCount }}
                </h3>
                <p class="mt-1 text-[11px] font-semibold text-slate-600">
                    <span class="text-rose-600">{{ $outOfStockCount }} agotados</span>, 
                    <span class="text-amber-600">{{ $lowStockCount }} en stock mínimo</span>
                </p>
            </div>
        </div>

    </div>

    <!-- Secondary Grid: Top Products & Stock Alerts Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        
        <!-- Top 5 Productos más Vendidos -->
        <div class="rounded-2xl bg-white border border-slate-200/80 shadow-subtle p-4 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                    <i class="bi bi-trophy-fill text-amber-500"></i>
                    <span>Productos más Vendidos</span>
                </h3>
                <a href="{{ route('reports.index') }}" class="text-xs text-emerald-700 hover:underline font-semibold">
                    Ver Reporte
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3">Producto</th>
                            <th class="py-2.5 px-3 text-center">Unidades</th>
                            <th class="py-2.5 px-3 text-right">Facturación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topProducts as $tp)
                            <tr>
                                <td class="py-2 px-3">
                                    <div class="font-bold text-slate-900">{{ $tp->name }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">{{ $tp->sku }}</div>
                                </td>
                                <td class="py-2 px-3 text-center font-mono font-bold text-slate-800">
                                    {{ $tp->total_sold }}
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-bold text-emerald-700">
                                    ${{ number_format($tp->total_revenue, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-slate-400">
                                    No hay registros de ventas recientes.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Alertas de Stock Bajo -->
        <div class="rounded-2xl bg-white border border-slate-200/80 shadow-subtle p-4 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                    <i class="bi bi-exclamation-triangle text-amber-500"></i>
                    <span>Productos con Stock Mínimo o Agotado</span>
                </h3>
                <a href="{{ route('inventory.index') }}" class="text-xs text-emerald-700 hover:underline font-semibold">
                    Ver Inventario
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3">Producto</th>
                            <th class="py-2.5 px-3 text-center">Stock Actual</th>
                            <th class="py-2.5 px-3 text-center">Stock Mínimo</th>
                            <th class="py-2.5 px-3 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($lowStockProducts as $lp)
                            <tr>
                                <td class="py-2 px-3">
                                    <div class="font-bold text-slate-900">{{ $lp->name }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">{{ $lp->sku }}</div>
                                </td>
                                <td class="py-2 px-3 text-center font-mono font-bold {{ $lp->stock <= 0 ? 'text-rose-600' : 'text-amber-600' }}">
                                    {{ $lp->stock }} {{ $lp->unit }}
                                </td>
                                <td class="py-2 px-3 text-center font-mono text-slate-500">
                                    {{ $lp->min_stock }} {{ $lp->unit }}
                                </td>
                                <td class="py-2 px-3 text-center">
                                    @if($lp->stock <= 0)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">
                                            Agotado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                            Crítico
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400">
                                    <i class="bi bi-check-circle text-emerald-500 text-base"></i>
                                    <p class="mt-1">Todos los productos cuentan con existencias óptimas.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection
