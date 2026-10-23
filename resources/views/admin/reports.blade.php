@extends('layouts.app')

@section('title', 'Reportes y Auditoría de Ventas - PuntoÁgil')

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
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Reportes y Auditoría</h1>
            <p class="text-xs text-slate-500 mt-0.5">Historial consolidado de comprobantes emitidos, facturación y resumen contable diario.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.reports.export') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 shadow-sm transition-all active:scale-95 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <i class="bi bi-file-earmark-spreadsheet text-sm text-emerald-600"></i>
                <span>Descargar CSV / Excel</span>
            </a>
            <a href="{{ route('sales.index') }}"
               class="btn-shine inline-flex items-center gap-2 px-4 py-2.5 text-xs font-semibold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-sm hover:shadow transition-all active:scale-95 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                <i class="bi bi-calculator text-sm"></i>
                <span>Abrir Terminal POS</span>
            </a>
        </div>
    </div>

    <!-- Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <!-- Ingresos del Día -->
        <div class="group saas-card-hover animate-fade-up stagger-1 bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ingresos del Día</span>
                    <div class="text-2xl font-mono font-bold text-slate-900 mt-1">
                        ${{ number_format($dailyTotal ?? 0, 2) }}
                    </div>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-300">
                    <i class="bi bi-cash-stack text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-1.5 text-xs text-emerald-600 font-medium">
                <i class="bi bi-calendar-check"></i>
                <span>Facturado el día de hoy (UTC/Local)</span>
            </div>
        </div>

        <!-- Boletas Emitidas -->
        <div class="group saas-card-hover animate-fade-up stagger-2 bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Comprobantes Emitidos</span>
                    <div class="text-2xl font-mono font-bold text-slate-900 mt-1">
                        {{ $invoicesCount ?? 0 }}
                    </div>
                </div>
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white transition-all duration-300">
                    <i class="bi bi-receipt-cutoff text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-1.5 text-xs text-slate-500">
                <i class="bi bi-activity text-blue-500"></i>
                <span>Transacciones registradas</span>
            </div>
        </div>

        <!-- Ticket Promedio -->
        <div class="group saas-card-hover animate-fade-up stagger-3 bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ticket Promedio</span>
                    <div class="text-2xl font-mono font-bold text-slate-900 mt-1">
                        ${{ number_format($averageTicket ?? 0, 2) }}
                    </div>
                </div>
                <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:scale-110 group-hover:bg-purple-600 group-hover:text-white transition-all duration-300">
                    <i class="bi bi-graph-up-arrow text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-1.5 text-xs text-slate-500">
                <i class="bi bi-cart3 text-purple-500"></i>
                <span>Promedio de consumo por cliente</span>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="animate-fade-up stagger-4 bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="bi bi-clock-history text-emerald-600"></i>
                <span>Historial de Transacciones Recientes</span>
            </h3>
            <span class="text-xs text-slate-500">
                Mostrando <strong class="text-slate-800 font-mono">{{ method_exists($transactions, 'total') ? $transactions->total() : $transactions->count() }}</strong> transacciones
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Nº Factura</th>
                        <th class="py-3.5 px-6">Fecha / Hora</th>
                        <th class="py-3.5 px-6">Cajero</th>
                        <th class="py-3.5 px-6 text-center">Ítems</th>
                        <th class="py-3.5 px-6 text-right">Subtotal</th>
                        <th class="py-3.5 px-6 text-right">Total</th>
                        <th class="py-3.5 px-6 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-6 font-mono font-bold text-slate-900">
                                <a href="{{ route('sales.show', $tx) }}" class="hover:text-emerald-600 transition-colors">
                                    {{ $tx->invoice_number }}
                                </a>
                            </td>
                            <td class="py-3.5 px-6 font-mono text-slate-500 text-[11px]">
                                {{ $tx->created_at->format('d/m/Y') }} <span class="text-slate-400">{{ $tx->created_at->format('H:i') }}</span>
                            </td>
                            <td class="py-3.5 px-6">
                                <span class="inline-flex items-center gap-1.5 text-slate-700 font-medium">
                                    <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">
                                        {{ strtoupper(substr($tx->user->name ?? 'C', 0, 1)) }}
                                    </span>
                                    <span>{{ $tx->user->name ?? 'Cajero' }}</span>
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-mono bg-slate-100 text-slate-700 font-medium">
                                    {{ $tx->items_count ?? ($tx->items ? $tx->items->count() : 0) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-right font-mono text-slate-600">
                                ${{ number_format($tx->subtotal, 2) }}
                            </td>
                            <td class="py-3.5 px-6 text-right font-mono font-bold text-emerald-600">
                                ${{ number_format($tx->total, 2) }}
                            </td>
                            <td class="py-3.5 px-6 text-center">
                                <a href="{{ route('sales.show', $tx) }}"
                                   title="Ver Recibo Completo"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 transition-colors">
                                    <i class="bi bi-eye"></i>
                                    <span>Ver</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center">
                                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <i class="bi bi-receipt text-xl"></i>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-700">No hay ventas registradas aún</h3>
                                <p class="text-xs text-slate-400 mt-1">Las ventas efectuadas en la caja rápida se consolidarán aquí automáticamente.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($transactions, 'links') && $transactions->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
