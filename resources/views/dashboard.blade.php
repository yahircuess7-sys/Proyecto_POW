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
                    <span class="font-mono text-emerald-800 font-bold">Efectivo Gaveta: C$ {{ number_format($activeShift->calculateExpectedCash(), 2) }}</span>
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

    {{-- Onboarding Checklist ("Primeros Pasos en 3 minutos") - Se oculta automáticamente al completar los 5 pasos --}}
    @if(!empty($showOnboarding) && !empty($onboardingSteps))
        @php
            $totalSteps = count($onboardingSteps);
            $progressPercent = (int) round(($completedStepsCount / max(1, $totalSteps)) * 100);
        @endphp
        <div x-data="{ collapsed: false }"
             class="animate-fade-up rounded-2xl bg-white border border-emerald-200/80 shadow-card overflow-hidden">
            <div class="px-5 py-4 bg-gradient-to-r from-emerald-50/90 via-white to-teal-50/50 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-600 text-white shadow-sm shrink-0">
                        <i class="bi bi-rocket-takeoff-fill text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold text-slate-900">Primeros Pasos en 3 Minutos</h2>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 font-mono">
                                {{ $completedStepsCount }}/{{ $totalSteps }} completados
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Sigue esta guía rápida para dejar tu punto de venta listo para facturar hoy mismo. Se ocultará automáticamente al terminar.</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-32 sm:w-36 bg-slate-200/80 rounded-full h-2 overflow-hidden">
                        <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
                    </div>
                    <span class="text-xs font-mono font-bold text-emerald-700">{{ $progressPercent }}%</span>
                    <button type="button" @click="collapsed = !collapsed" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors" title="Contraer/Expandir">
                        <i class="bi" :class="collapsed ? 'bi-chevron-down' : 'bi-chevron-up'"></i>
                    </button>
                </div>
            </div>

            <div x-show="!collapsed" x-transition class="p-4 sm:p-5 grid grid-cols-1 md:grid-cols-5 gap-3">
                @foreach($onboardingSteps as $idx => $step)
                    <div class="flex flex-col justify-between p-3.5 rounded-xl border transition-all {{ $step['done'] ? 'bg-emerald-50/40 border-emerald-200/70' : 'bg-slate-50/70 border-slate-200 hover:border-emerald-300 hover:bg-white' }}">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-mono font-bold uppercase tracking-wider {{ $step['done'] ? 'text-emerald-600' : 'text-slate-400' }}">
                                    Paso {{ $idx + 1 }}
                                </span>
                                @if($step['done'])
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500 text-white text-xs shadow-sm">
                                        <i class="bi bi-check2 font-bold"></i>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full border-2 border-slate-300 bg-white"></span>
                                @endif
                            </div>
                            <h3 class="text-xs font-bold {{ $step['done'] ? 'text-slate-700 line-through decoration-emerald-400/70' : 'text-slate-900' }}">
                                {{ $step['title'] }}
                            </h3>
                            <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                                {{ $step['description'] }}
                            </p>
                        </div>

                        <div class="mt-3 pt-2.5 border-t border-slate-200/60">
                            @if($step['done'])
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600">
                                    <i class="bi bi-check-circle-fill"></i> Listo
                                </span>
                            @elseif($step['url'])
                                <a href="{{ $step['url'] }}" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold bg-slate-900 hover:bg-emerald-600 text-white transition-colors">
                                    <i class="bi {{ $step['icon'] }}"></i>
                                    <span>{{ $step['cta'] }}</span>
                                </a>
                            @else
                                <span class="text-[11px] text-slate-400 font-medium">Pendiente del Propietario</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

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
                    C$ {{ number_format($dailyTotal, 2) }}
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
                    C$ {{ number_format($monthlyTotal, 2) }}
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
                    C$ {{ number_format($monthlyGrossProfit, 2) }}
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
                @can('view-reports')
                <a href="{{ route('reports.index') }}" class="text-xs text-emerald-700 hover:underline font-semibold">
                    Ver Reporte
                </a>
                @endcan
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
                                    C$ {{ number_format($tp->total_revenue, 2) }}
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

    <!-- Tertiary Grid: Movimientos Recientes de Inventario & Ventas Recientes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        <!-- Movimientos Recientes de Inventario -->
        <div class="rounded-2xl bg-white border border-slate-200/80 shadow-subtle p-4 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                    <i class="bi bi-arrow-left-right text-emerald-600"></i>
                    <span>Movimientos Recientes de Inventario (Kardex)</span>
                </h3>
                <a href="{{ route('inventory.index') }}" class="text-xs text-emerald-700 hover:underline font-semibold">
                    Catálogo
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3">Producto</th>
                            <th class="py-2.5 px-3">Motivo</th>
                            <th class="py-2.5 px-3 text-right">Cantidad</th>
                            <th class="py-2.5 px-3 text-right">Stock Final</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentMovements as $mv)
                            <tr>
                                <td class="py-2 px-3">
                                    <div class="font-bold text-slate-900">{{ $mv->product->name ?? 'Producto' }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $mv->user->name ?? 'Sistema' }}</div>
                                </td>
                                <td class="py-2 px-3 text-slate-600 truncate max-w-[160px]" title="{{ $mv->reason }}">
                                    {{ $mv->reason }}
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-bold {{ $mv->quantity >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $mv->quantity > 0 ? '+' . $mv->quantity : $mv->quantity }}
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-semibold text-slate-800">
                                    {{ $mv->new_stock }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400">
                                    Sin movimientos recientes en inventario.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ventas Recientes -->
        <div class="rounded-2xl bg-white border border-slate-200/80 shadow-subtle p-4 space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                    <i class="bi bi-receipt text-blue-600"></i>
                    <span>Ventas Recientes</span>
                </h3>
                <a href="{{ route('sales.history') }}" class="text-xs text-emerald-700 hover:underline font-semibold">
                    Ver Todas
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3">Comprobante</th>
                            <th class="py-2.5 px-3">Cliente</th>
                            <th class="py-2.5 px-3">Método</th>
                            <th class="py-2.5 px-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentSales as $rs)
                            <tr>
                                <td class="py-2 px-3">
                                    <a href="{{ route('sales.show', $rs) }}" class="font-mono font-bold text-emerald-700 hover:underline">
                                        {{ $rs->invoice_number }}
                                    </a>
                                    <div class="text-[10px] text-slate-400">{{ $rs->created_at->format('d/m H:i') }}</div>
                                </td>
                                <td class="py-2 px-3 font-medium text-slate-800">
                                    {{ $rs->customer_name }}
                                </td>
                                <td class="py-2 px-3 uppercase text-[10px] font-bold text-slate-600">
                                    {{ $rs->payment_method ?? 'CASH' }}
                                </td>
                                <td class="py-2 px-3 text-right font-mono font-bold text-slate-900">
                                    C$ {{ number_format($rs->total, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400">
                                    No hay ventas registradas recientemente.
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
