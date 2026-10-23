@extends('layouts.app')

@section('title', 'Panel de Control - PuntoÁgil')

@section('content')
<div class="space-y-8">

    <!-- Welcome & Header -->
    <div class="animate-fade-up flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                ¡Bienvenido de vuelta, {{ auth()->user()->name ?? 'Administrador' }}!
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Monitorea el flujo operativo, métricas de ventas y el estado del stock en tiempo real.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-semibold text-slate-600 shadow-subtle font-mono">
                <i class="bi bi-clock-history text-emerald-600"></i>
                {{ date('d/M/Y - H:i') }}
            </span>
            <a href="{{ route('pos.create') }}" 
               class="btn-shine inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-500 hover:bg-emerald-600 shadow-sm shadow-emerald-500/25 transition-all active:scale-95">
                <i class="bi bi-cart-plus-fill"></i>
                <span>Abrir Terminal POS</span>
            </a>
        </div>
    </div>

    <!-- 4 KPI Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- KPI 1: Ventas del Día -->
        <div class="group saas-card-hover animate-fade-up stagger-1 p-5 rounded-2xl bg-white border border-slate-200/80 shadow-subtle">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">Ventas Hoy</span>
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 group-hover:scale-110 group-hover:bg-emerald-500 group-hover:text-white transition-all duration-300">
                    <i class="bi bi-cash-stack text-lg"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold font-mono text-slate-900 tracking-tight">
                    ${{ number_format($dailyTotal, 2) }}
                </h3>
                <p class="flex items-center gap-1.5 mt-2 text-xs font-semibold text-emerald-600">
                    <i class="bi bi-receipt"></i>
                    <span>{{ $ordersToday }} comprobante{{ $ordersToday === 1 ? '' : 's' }} emitido{{ $ordersToday === 1 ? '' : 's' }}</span>
                </p>
            </div>
        </div>

        <!-- KPI 2: Ticket Promedio -->
        <div class="group saas-card-hover animate-fade-up stagger-2 p-5 rounded-2xl bg-white border border-slate-200/80 shadow-subtle">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">Ticket Promedio</span>
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white transition-all duration-300">
                    <i class="bi bi-receipt-cutoff text-lg"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold font-mono text-slate-900 tracking-tight">
                    ${{ number_format($averageTicket, 2) }}
                </h3>
                <p class="flex items-center gap-1.5 mt-2 text-xs text-slate-600 font-medium">
                    <i class="bi bi-graph-up text-blue-500"></i>
                    <span>Por transacción en caja</span>
                </p>
            </div>
        </div>

        <!-- KPI 3: Catálogo Total -->
        <div class="group saas-card-hover animate-fade-up stagger-3 p-5 rounded-2xl bg-white border border-slate-200/80 shadow-subtle">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">Catálogo Activo</span>
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 group-hover:scale-110 group-hover:bg-purple-600 group-hover:text-white transition-all duration-300">
                    <i class="bi bi-boxes text-lg"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold font-mono text-slate-900 tracking-tight">
                    {{ $totalProducts }}
                </h3>
                <p class="flex items-center gap-1.5 mt-2 text-xs text-slate-600 font-medium">
                    <i class="bi bi-tags text-purple-500"></i>
                    <span>En {{ $totalCategories }} categoría{{ $totalCategories === 1 ? '' : 's' }}</span>
                </p>
            </div>
        </div>

        <!-- KPI 4: Stock Crítico / Alertas -->
        <div class="group saas-card-hover animate-fade-up stagger-4 p-5 rounded-2xl bg-white border border-slate-200/80 shadow-subtle">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">Atención Stock</span>
                <div class="flex items-center justify-center w-10 h-10 rounded-xl group-hover:scale-110 transition-all duration-300 {{ ($lowStockCount + $outOfStockCount) > 0 ? 'bg-amber-50 text-amber-600 border border-amber-100' : 'bg-slate-50 text-slate-400 border border-slate-200' }}">
                    <i class="bi bi-exclamation-triangle-fill text-lg"></i>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold font-mono {{ ($lowStockCount + $outOfStockCount) > 0 ? 'text-amber-600' : 'text-slate-900' }} tracking-tight">
                    {{ $lowStockCount + $outOfStockCount }}
                </h3>
                <p class="mt-2 text-xs font-semibold text-slate-600">
                    <span class="text-rose-600">{{ $outOfStockCount }} agotado{{ $outOfStockCount === 1 ? '' : 's' }}</span>, 
                    <span class="text-amber-600">{{ $lowStockCount }} crítico{{ $lowStockCount === 1 ? '' : 's' }}</span>
                </p>
            </div>
        </div>
    </div>

    <!-- 2 Featured Quick Action Hubs -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Action Card 1: Punto de Venta Express -->
        <div class="group saas-card-hover animate-fade-up stagger-4 relative overflow-hidden p-6 md:p-8 rounded-3xl bg-gradient-to-br from-[#0f172a] via-slate-900 to-slate-950 text-white shadow-card border border-slate-800">
            <div class="absolute -right-6 -bottom-6 text-slate-800/40 pointer-events-none group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-500">
                <i class="bi bi-cart4 text-[160px]"></i>
            </div>
            <div class="relative z-10 flex flex-col justify-between h-full space-y-6">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-mono uppercase tracking-wide">
                        <i class="bi bi-lightning-charge-fill"></i> Operación Rápida
                    </span>
                    <h2 class="text-xl md:text-2xl font-bold text-white mt-3">Terminal Punto de Venta (POS)</h2>
                    <p class="text-slate-400 text-sm mt-1 max-w-md">
                        Interfaz táctil para búsqueda ágil de materiales, carrito en tiempo real, desglose de IVA 12% y descarga automática de boleta en PDF.
                    </p>
                </div>
                <div>
                    <a href="{{ route('pos.create') }}" 
                       class="btn-shine inline-flex items-center gap-2.5 px-5 py-3 rounded-xl text-sm font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 shadow-lg shadow-emerald-500/25 transition-all active:scale-95">
                        <span>Iniciar Nueva Venta</span>
                        <i class="bi bi-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Action Card 2: Control de Inventario -->
        <div class="saas-card-hover animate-fade-up stagger-5 relative overflow-hidden p-6 md:p-8 rounded-3xl bg-white border border-slate-200/80 shadow-card flex flex-col justify-between space-y-6">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 font-mono uppercase tracking-wide">
                    <i class="bi bi-boxes"></i> Almacén y Precios
                </span>
                <h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-3">Gestión de Catálogo y Bodega</h2>
                <p class="text-slate-500 text-sm mt-1 max-w-md">
                    Registra nuevos productos, actualiza existencias, calcula correlativos automáticos de SKU y exporta listas en PDF y Excel/CSV.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('inventory.index') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 transition-all active:scale-95">
                    <i class="bi bi-list-check"></i>
                    <span>Ver Todo el Inventario</span>
                </a>
                <a href="{{ route('inventory.create') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-all active:scale-95">
                    <i class="bi bi-plus-lg"></i>
                    <span>Nuevo Producto</span>
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
