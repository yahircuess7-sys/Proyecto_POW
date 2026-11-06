@extends('layouts.app')

@section('title', 'Reportes Directos y Corte Diario - PuntoÁgil')

@section('content')
@php
    $bizName = ($activeBusiness ?? $currentBusiness)?->name ?? 'Mi Negocio';
    $todayStr = now()->toDateString();
    $yesterdayStr = now()->subDay()->toDateString();
    $weekStartStr = now()->startOfWeek()->toDateString();
    $monthStartStr = now()->startOfMonth()->toDateString();

    $topFirst = $topProducts->first();
    $waSummaryLines = [
        "*RESUMEN OPERATIVO - {$bizName}*",
        "Período: {$dateFrom} al {$dateTo}",
        "--------------------------",
        "Ventas Cobradas ({$invoicesCount}): C$ " . number_format($totalSales, 2),
        "Ventas Netas: C$ " . number_format($netSales, 2),
        "Utilidad Bruta Est.: C$ " . number_format($grossMargin, 2),
        "--------------------------",
        "Efectivo: C$ " . number_format($cashSales, 2),
        "Tarjeta: C$ " . number_format($cardSales, 2),
        "Transferencia: C$ " . number_format($transferSales, 2),
    ];
    if ($topFirst) {
        $waSummaryLines[] = "Más vendido: {$topFirst->name} ({$topFirst->total_quantity} un.)";
    }
    if (isset($lowStockProducts) && $lowStockProducts->count() > 0) {
        $waSummaryLines[] = "Alerta reposición: {$lowStockProducts->count()} productos en bajo stock/agotados";
    }
    $waReportUrl = 'https://wa.me/?text=' . rawurlencode(implode("\n", $waSummaryLines));
@endphp
<div class="space-y-6">

    <!-- Print-Only Executive 1-Page Header -->
    <div class="hidden print:flex items-center justify-between border-b-2 border-slate-900 pb-3 mb-2">
        <div>
            <h1 class="text-lg font-black uppercase text-slate-900">{{ $bizName }} &bull; Resumen Ejecutivo de Ventas e Inventario</h1>
            <p class="text-xs text-slate-600">Período: {{ $dateFrom }} al {{ $dateTo }} &bull; Moneda: Córdoba Nicaragüense (C$)</p>
        </div>
        <div class="text-right text-xs font-mono text-slate-600">
            Impreso: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <!-- Header & Action Bar -->
    <div class="animate-fade-up flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 print:hidden">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <span>Administración</span>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-emerald-600 font-semibold">Reportes Directos al Grano (C$)</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <span>Corte Diario, Ventas y Reposición</span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Resumen claro en 1 página para el dueño: cuánto entró, ganancia real, qué se vende más y qué pedir hoy al proveedor.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ $waReportUrl }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1.5 px-3.5 py-2.5 text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100 transition-all">
                <i class="bi bi-whatsapp text-emerald-600 text-sm"></i>
                <span>Enviar Resumen WhatsApp</span>
            </a>

            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2.5 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-xl shadow-sm transition-all">
                <i class="bi bi-printer-fill text-emerald-400 text-sm"></i>
                <span>Imprimir Corte / PDF 1 Pág.</span>
            </button>

            <a href="{{ route('admin.reports.export', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'user_id' => $userId]) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 shadow-subtle transition-all">
                <i class="bi bi-file-earmark-spreadsheet text-sm text-emerald-600"></i>
                <span>Excel / CSV</span>
            </a>
        </div>
    </div>

    <!-- Quick Date Pills + Filters Bar -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-subtle space-y-3 print:hidden">
        <div class="flex flex-wrap items-center gap-1.5 text-xs">
            <span class="text-[11px] font-bold text-slate-400 uppercase mr-1">Accesos rápidos:</span>
            <a href="{{ route('reports.index', ['date_from' => $todayStr, 'date_to' => $todayStr, 'user_id' => $userId]) }}"
               class="px-3 py-1 rounded-lg font-semibold transition-colors {{ ($dateFrom === $todayStr && $dateTo === $todayStr) ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Corte de Hoy
            </a>
            <a href="{{ route('reports.index', ['date_from' => $yesterdayStr, 'date_to' => $yesterdayStr, 'user_id' => $userId]) }}"
               class="px-3 py-1 rounded-lg font-semibold transition-colors {{ ($dateFrom === $yesterdayStr && $dateTo === $yesterdayStr) ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Ayer
            </a>
            <a href="{{ route('reports.index', ['date_from' => $weekStartStr, 'date_to' => $todayStr, 'user_id' => $userId]) }}"
               class="px-3 py-1 rounded-lg font-semibold transition-colors {{ ($dateFrom === $weekStartStr && $dateTo === $todayStr) ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Esta Semana
            </a>
            <a href="{{ route('reports.index', ['date_from' => $monthStartStr, 'date_to' => $todayStr, 'user_id' => $userId]) }}"
               class="px-3 py-1 rounded-lg font-semibold transition-colors {{ ($dateFrom === $monthStartStr && $dateTo === $todayStr) ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Este Mes
            </a>
        </div>

        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end text-xs pt-2 border-t border-slate-100">
            <div>
                <label for="rep_date_from" class="block text-[11px] font-semibold text-slate-600 mb-1">Fecha Desde</label>
                <input id="rep_date_from" type="date" name="date_from" value="{{ $dateFrom }}"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
            </div>

            <div>
                <label for="rep_date_to" class="block text-[11px] font-semibold text-slate-600 mb-1">Fecha Hasta</label>
                <input id="rep_date_to" type="date" name="date_to" value="{{ $dateTo }}"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
            </div>

            <div>
                <label for="rep_user_id" class="block text-[11px] font-semibold text-slate-600 mb-1">Cajero / Responsable</label>
                <select id="rep_user_id" name="user_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <option value="">Todos los cajeros</option>
                    @foreach($cashiers as $c)
                        <option value="{{ $c->id }}" {{ $userId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="w-full py-2 px-3 rounded-xl bg-slate-900 text-white font-semibold hover:bg-slate-800 transition-colors">
                    <i class="bi bi-funnel me-1"></i> Actualizar Reporte
                </button>
            </div>
        </form>
    </div>

    <!-- Main Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- Ventas Brutas -->
        <div class="saas-card-hover bg-white border border-slate-200/80 rounded-2xl p-4 shadow-subtle flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Ventas Cobradas</span>
                    <div class="text-xl font-mono font-bold text-slate-900 mt-1">
                        C$ {{ number_format($totalSales, 2) }}
                    </div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center print:hidden">
                    <i class="bi bi-cash-stack text-lg"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-100 text-[11px] text-slate-500 flex items-center justify-between">
                <span>{{ $invoicesCount }} tickets emitidos</span>
                <span class="font-mono">Prom: C$ {{ number_format($averageTicket, 2) }}</span>
            </div>
        </div>

        <!-- Ventas Netas -->
        <div class="saas-card-hover bg-white border border-slate-200/80 rounded-2xl p-4 shadow-subtle flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Ventas Netas</span>
                    <div class="text-xl font-mono font-bold text-emerald-700 mt-1">
                        C$ {{ number_format($netSales, 2) }}
                    </div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center print:hidden">
                    <i class="bi bi-shield-check text-lg"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-100 text-[11px] text-rose-600 font-medium">
                <span>Devoluciones: -C$ {{ number_format($totalRefunded, 2) }}</span>
            </div>
        </div>

        <!-- Ganancia Bruta Real -->
        <div class="saas-card-hover bg-gradient-to-br from-slate-900 to-slate-950 text-white rounded-2xl p-4 shadow-card flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-extrabold text-emerald-400 uppercase tracking-wider">Ganancia Bruta Est.</span>
                    <div class="text-xl font-mono font-black text-white mt-1">
                        C$ {{ number_format($grossMargin, 2) }}
                    </div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-white/10 text-emerald-400 flex items-center justify-center print:hidden">
                    <i class="bi bi-graph-up-arrow text-lg"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-800 text-[10px] text-slate-400">
                <span>Ventas sin IVA menos costo de compra</span>
            </div>
        </div>

        <!-- Desglose Compacto de Canales de Cobro -->
        <div class="saas-card-hover bg-white border border-slate-200/80 rounded-2xl p-4 shadow-subtle flex flex-col justify-between text-xs">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Dinero por Canal</span>
            <div class="mt-2 space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Efectivo:</span>
                    <strong class="font-mono text-slate-900">C$ {{ number_format($cashSales, 2) }}</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Tarjeta:</span>
                    <strong class="font-mono text-slate-900">C$ {{ number_format($cardSales, 2) }}</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Transferencia:</span>
                    <strong class="font-mono text-slate-900">C$ {{ number_format($transferSales, 2) }}</strong>
                </div>
            </div>
        </div>

    </div>

    <!-- Actionable Grid: Productos Más Vendidos vs. Bajo Stock ("Qué pedir hoy al proveedor") -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        <!-- Productos Más Vendidos (5 cols) -->
        <div class="lg:col-span-5 rounded-2xl bg-white border border-slate-200/80 shadow-subtle p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                    <i class="bi bi-trophy-fill text-amber-500"></i>
                    <span>Productos Más Vendidos</span>
                </h3>
                <span class="text-[11px] text-slate-400">Ranking del período</span>
            </div>

            <div tabindex="0" role="region" aria-label="Productos más vendidos" class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3">Producto</th>
                            <th class="py-2.5 px-2 text-center">Vendidos</th>
                            <th class="py-2.5 px-2 text-center">Stock Hoy</th>
                            <th class="py-2.5 px-3 text-right">Ingresos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topProducts as $tp)
                            <tr>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900">{{ $tp->name }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">{{ $tp->sku }}</div>
                                </td>
                                <td class="py-2.5 px-2 text-center font-mono font-bold text-slate-800">
                                    {{ $tp->total_quantity }}
                                </td>
                                <td class="py-2.5 px-2 text-center font-mono">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ ($tp->stock ?? 0) <= ($tp->min_stock ?? 5) ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $tp->stock ?? 0 }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-700">
                                    C$ {{ number_format($tp->total_revenue, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400">
                                    Sin ventas registradas en este rango de fechas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bajo Stock y Agotados: Qué Pedir al Proveedor Hoy (7 cols) -->
        <div class="lg:col-span-7 rounded-2xl bg-white border border-slate-200/80 shadow-subtle p-5 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-rose-800 flex items-center gap-2">
                        <i class="bi bi-truck text-rose-600"></i>
                        <span>Qué Pedir Hoy al Proveedor (Bajo Stock y Agotados)</span>
                    </h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Artículos activos cuyo stock actual llegó o bajó del mínimo permitido.</p>
                </div>
                <a href="{{ route('purchases.create') }}" class="print:hidden inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-bold transition-colors">
                    <i class="bi bi-cart-plus-fill"></i> Registrar Compra
                </a>
            </div>

            <div tabindex="0" role="region" aria-label="Productos en bajo stock para reposición" class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3">Producto / SKU</th>
                            <th class="py-2.5 px-3">Proveedor Habitual</th>
                            <th class="py-2.5 px-2 text-center">Actual / Mín.</th>
                            <th class="py-2.5 px-2 text-center">Sugerido Pedir</th>
                            <th class="py-2.5 px-3 text-right">Costo Ref.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($lowStockProducts ?? [] as $item)
                            @php
                                $suggestedQty = max(5, ($item->min_stock * 2) - $item->stock);
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="py-2.5 px-3">
                                    <a href="{{ route('inventory.show', $item->id) }}" class="font-bold text-slate-900 hover:text-emerald-700">
                                        {{ $item->name }}
                                    </a>
                                    <div class="text-[10px] font-mono text-slate-400">{{ $item->sku }}</div>
                                </td>
                                <td class="py-2.5 px-3">
                                    @if($item->supplier)
                                        <span class="font-medium text-slate-800 block">{{ $item->supplier->name }}</span>
                                        @if($item->supplier->phone)
                                            <span class="text-[10px] font-mono text-slate-400">{{ $item->supplier->phone }}</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400 italic">Por asignar</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-2 text-center font-mono">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $item->stock <= 0 ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $item->stock }} / {{ $item->min_stock }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-2 text-center font-mono font-extrabold text-emerald-700">
                                    +{{ $suggestedQty }} {{ $item->unit }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-700">
                                    C$ {{ number_format((float) $item->cost, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-emerald-700 bg-emerald-50/40 font-semibold">
                                    <i class="bi bi-check2-circle text-lg mr-1"></i> ¡Todo al día! Ningún producto activo está en nivel crítico o agotado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Transactions Table (Hidden on compact 1-page print so printed report fits cleanly on 1 sheet) -->
    <div class="rounded-2xl bg-white border border-slate-200/80 shadow-subtle overflow-hidden print:hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                Últimas Ventas del Período
            </h3>
            <a href="{{ route('sales.history') }}" class="text-xs text-emerald-700 hover:underline font-semibold">
                Ver Historial Completo
            </a>
        </div>

        <div tabindex="0" role="region" aria-label="Últimas ventas del período" class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Comprobante</th>
                        <th class="py-3 px-4">Fecha / Hora</th>
                        <th class="py-3 px-4">Cajero</th>
                        <th class="py-3 px-4">Cliente</th>
                        <th class="py-3 px-4">Método</th>
                        <th class="py-3 px-4 text-center">Ítems</th>
                        <th class="py-3 px-4 text-right">Subtotal</th>
                        <th class="py-3 px-4 text-right">Total</th>
                        <th class="py-3 px-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                <a href="{{ route('sales.show', $tx) }}" class="text-emerald-700 hover:underline">
                                    {{ $tx->invoice_number }}
                                </a>
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-500 text-[11px]">
                                {{ $tx->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3 px-4">{{ $tx->user->name ?? 'Cajero' }}</td>
                            <td class="py-3 px-4">{{ $tx->customer_name }}</td>
                            <td class="py-3 px-4 uppercase font-semibold text-[10px] text-slate-700">
                                {{ $tx->payment_method ?? 'CASH' }}
                            </td>
                            <td class="py-3 px-4 text-center font-mono">{{ $tx->items_count }}</td>
                            <td class="py-3 px-4 text-right font-mono">C$ {{ number_format($tx->subtotal, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">C$ {{ number_format($tx->total, 2) }}</td>
                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('sales.show', $tx) }}" class="p-1 rounded text-slate-500 hover:text-emerald-700" title="Ver comprobante">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400">
                                Sin ventas en el período especificado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
