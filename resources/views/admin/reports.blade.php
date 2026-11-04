@extends('layouts.app')

@section('title', 'Reportes y Métricas Operativas - PuntoÁgil')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Bar -->
    <div class="animate-fade-up flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <span>Administración</span>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-emerald-600 font-semibold">Reportes Contables</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <span>Reportes y Métricas</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-500/15 text-emerald-700 font-mono">M13</span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Indicadores reales basados en ventas, costos de inventario, márgenes brutos y turnos de caja.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.reports.export', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'user_id' => $userId]) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 shadow-subtle transition-all">
                <i class="bi bi-file-earmark-spreadsheet text-sm text-emerald-600"></i>
                <span>Exportar CSV con Margen</span>
            </a>
            <a href="{{ route('pos.create') }}"
               class="btn-shine inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl shadow-sm transition-all">
                <i class="bi bi-cart4 text-sm"></i>
                <span>Punto de Venta</span>
            </a>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-subtle">
        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end text-xs">
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Fecha Desde</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Fecha Hasta</label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Cajero / Responsable</label>
                <select name="user_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <option value="">Todos los cajeros</option>
                    @foreach($cashiers as $c)
                        <option value="{{ $c->id }}" {{ $userId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="w-full py-2 px-3 rounded-xl bg-slate-900 text-white font-semibold hover:bg-slate-800 transition-colors">
                    <i class="bi bi-funnel me-1"></i> Filtrar Período
                </button>
                @if(request()->anyFilled(['date_from', 'date_to', 'user_id']))
                    <a href="{{ route('reports.index') }}" class="p-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-100" title="Restablecer">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Main Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Ventas Brutas -->
        <div class="saas-card-hover bg-white border border-slate-200/80 rounded-2xl p-4 shadow-subtle flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Ventas Período</span>
                    <div class="text-xl font-mono font-bold text-slate-900 mt-1">
                        ${{ number_format($totalSales, 2) }}
                    </div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="bi bi-cash-stack text-lg"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-100 text-[11px] text-slate-500">
                <span>{{ $invoicesCount }} transacciones cobradas</span>
            </div>
        </div>

        <!-- Ventas Netas -->
        <div class="saas-card-hover bg-white border border-slate-200/80 rounded-2xl p-4 shadow-subtle flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Ventas Netas</span>
                    <div class="text-xl font-mono font-bold text-emerald-700 mt-1">
                        ${{ number_format($netSales, 2) }}
                    </div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                    <i class="bi bi-shield-check text-lg"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-100 text-[11px] text-rose-600 font-medium">
                <span>Devoluciones: -${{ number_format($totalRefunded, 2) }}</span>
            </div>
        </div>

        <!-- Ganancia Bruta Real -->
        <div class="saas-card-hover bg-gradient-to-br from-slate-900 to-slate-950 text-white rounded-2xl p-4 shadow-card flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-extrabold text-emerald-400 uppercase tracking-wider">Utilidad Bruta Estimada</span>
                    <div class="text-xl font-mono font-black text-white mt-1">
                        ${{ number_format($grossMargin, 2) }}
                    </div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-white/10 text-emerald-400 flex items-center justify-center">
                    <i class="bi bi-graph-up-arrow text-lg"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-800 text-[10px] text-slate-400">
                <span>Calculado: Subtotal - (Cantidad &times; Costo)</span>
            </div>
        </div>

        <!-- Ticket Promedio -->
        <div class="saas-card-hover bg-white border border-slate-200/80 rounded-2xl p-4 shadow-subtle flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Ticket Promedio</span>
                    <div class="text-xl font-mono font-bold text-slate-900 mt-1">
                        ${{ number_format($averageTicket, 2) }}
                    </div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <i class="bi bi-bag-check text-lg"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-100 text-[11px] text-slate-500">
                <span>Consumo promedio por ticket</span>
            </div>
        </div>

    </div>

    <!-- Secondary Grid: Desglose por Método de Pago & Top Productos -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        <!-- Desglose Métodos de Pago (5 cols) -->
        <div class="lg:col-span-5 rounded-2xl bg-white border border-slate-200/80 shadow-subtle p-5 space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                <i class="bi bi-wallet2 text-emerald-600"></i>
                <span>Canales de Cobro</span>
            </h3>

            <div class="space-y-2.5 text-xs">
                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div>
                            <span class="font-bold text-slate-800 block">Efectivo</span>
                            <span class="text-[10px] text-slate-400">Cobrado en gaveta de caja</span>
                        </div>
                    </div>
                    <span class="font-mono font-bold text-slate-900">${{ number_format($cashSales, 2) }}</span>
                </div>

                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center font-bold">
                            <i class="bi bi-credit-card"></i>
                        </div>
                        <div>
                            <span class="font-bold text-slate-800 block">Tarjetas</span>
                            <span class="text-[10px] text-slate-400">POS electrónico / datafast</span>
                        </div>
                    </div>
                    <span class="font-mono font-bold text-slate-900">${{ number_format($cardSales, 2) }}</span>
                </div>

                <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-800 flex items-center justify-center font-bold">
                            <i class="bi bi-arrow-left-right"></i>
                        </div>
                        <div>
                            <span class="font-bold text-slate-800 block">Transferencias</span>
                            <span class="text-[10px] text-slate-400">Depósitos directos</span>
                        </div>
                    </div>
                    <span class="font-mono font-bold text-slate-900">${{ number_format($transferSales, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Top 5 Productos más Vendidos (7 cols) -->
        <div class="lg:col-span-7 rounded-2xl bg-white border border-slate-200/80 shadow-subtle p-5 space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                <i class="bi bi-trophy text-amber-500"></i>
                <span>Top 5 Productos más Vendidos en el Período</span>
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3">Producto / SKU</th>
                            <th class="py-2.5 px-3 text-center">Unidades</th>
                            <th class="py-2.5 px-3 text-right">Facturación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topProducts as $tp)
                            <tr>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900">{{ $tp->name }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">{{ $tp->sku }}</div>
                                </td>
                                <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-800">
                                    {{ $tp->total_quantity }} un
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-700">
                                    ${{ number_format($tp->total_revenue, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-slate-400">
                                    Sin movimientos en este rango de fechas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Transactions Table -->
    <div class="rounded-2xl bg-white border border-slate-200/80 shadow-subtle overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                Ventas Recientes del Período Seleccionado
            </h3>
            <a href="{{ route('sales.history') }}" class="text-xs text-emerald-700 hover:underline font-semibold">
                Ver Historial Completo
            </a>
        </div>

        <div class="overflow-x-auto">
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
                            <td class="py-3 px-4 text-right font-mono">${{ number_format($tx->subtotal, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">${{ number_format($tx->total, 2) }}</td>
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
