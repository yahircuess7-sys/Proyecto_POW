@extends('layouts.app')

@section('title', 'Devoluciones y Notas de Crédito - PuntoÁgil')

@section('content')
<div class="space-y-6" x-data="{ newReturnOpen: {{ !empty($invoiceQuery) ? 'true' : 'false' }} }">

    <!-- Header -->
    <div class="animate-fade-up flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Devoluciones de Ventas</h1>
            <p class="text-xs text-slate-500 mt-1">Procesa devoluciones totales o parciales, reintegra el stock automáticamente y genera su comprobante.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <button type="button"
                    @click="newReturnOpen = !newReturnOpen"
                    class="btn-shine inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-rose-600 text-white hover:bg-rose-700 shadow-sm transition-all">
                <i class="bi bi-arrow-counterclockwise text-sm"></i>
                <span>Nueva Devolución</span>
            </button>
            <a href="{{ route('sales.history') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 shadow-subtle transition-all">
                <i class="bi bi-receipt text-slate-500"></i>
                <span>Historial de Ventas</span>
            </a>
        </div>
    </div>

    <!-- Quick Start Return Panel (Seleccionar Venta o Buscar Factura) -->
    <div x-show="newReturnOpen || {{ $returns->total() === 0 ? 'true' : 'false' }}"
         x-transition
         class="rounded-2xl bg-white border border-rose-200/80 shadow-card overflow-hidden">
        <div class="px-5 py-4 bg-gradient-to-r from-rose-50/80 via-white to-amber-50/40 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <i class="bi bi-box-arrow-in-left text-base"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">¿Qué venta deseas devolver?</h2>
                    <p class="text-xs text-slate-500">Escanea o escribe el número de factura (ej. FAC-...) o elige directamente una venta reciente.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('sales.returns.index') }}" class="flex items-center gap-2 w-full sm:w-auto">
                <div class="relative flex-1 sm:w-64">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <i class="bi bi-upc-scan"></i>
                    </span>
                    <input type="text" name="invoice_lookup" value="{{ $invoiceQuery ?? '' }}"
                           placeholder="Nro. de factura o cliente..."
                           class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-xs focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500">
                </div>
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition-colors">
                    Buscar
                </button>
                @if(!empty($invoiceQuery))
                    <a href="{{ route('sales.returns.index') }}" class="p-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-100" title="Limpiar búsqueda">
                        <i class="bi bi-x-lg text-xs"></i>
                    </a>
                @endif
            </form>
        </div>

        <div class="p-4">
            @if(isset($refundableSales) && $refundableSales->count() > 0)
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2.5 px-1">Ventas recientes disponibles para devolución</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    @foreach($refundableSales as $rSale)
                        <a href="{{ route('sales.returns.create', $rSale) }}"
                           class="group p-3.5 rounded-xl border border-slate-200 hover:border-rose-400 hover:bg-rose-50/30 transition-all flex flex-col justify-between gap-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="font-mono text-xs font-bold text-slate-900 group-hover:text-rose-700">{{ $rSale->invoice_number }}</span>
                                    <span class="block text-[11px] text-slate-500 truncate max-w-[150px]">{{ $rSale->customer_name ?: 'Consumidor Final' }}</span>
                                </div>
                                <span class="font-mono text-xs font-bold text-emerald-700">C$ {{ number_format($rSale->total, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-[11px]">
                                <span class="text-slate-400 font-mono">{{ $rSale->created_at->format('d/m/Y H:i') }}</span>
                                <span class="font-bold text-rose-600 inline-flex items-center gap-1 group-hover:translate-x-0.5 transition-transform">
                                    Devolver <i class="bi bi-arrow-right"></i>
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="py-5 text-center text-xs text-slate-500">
                    <i class="bi bi-receipt text-xl text-slate-300 block mb-1"></i>
                    No se encontraron ventas recientes pendientes de devolución{{ !empty($invoiceQuery) ? " para «{$invoiceQuery}»" : '' }}.
                </div>
            @endif
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-subtle">
        <form method="GET" action="{{ route('sales.returns.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end text-xs">
            <div class="md:col-span-2">
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Buscar en historial de devoluciones</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                           placeholder="Nro devolución (DEV-...), factura, motivo...">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Método de Reembolso</label>
                <select name="refund_method" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <option value="">Todos los métodos</option>
                    <option value="cash" {{ request('refund_method') === 'cash' ? 'selected' : '' }}>Efectivo en Caja</option>
                    <option value="credit_note" {{ request('refund_method') === 'credit_note' ? 'selected' : '' }}>Nota de Crédito Interna</option>
                    <option value="card_reversal" {{ request('refund_method') === 'card_reversal' ? 'selected' : '' }}>Reverso Electrónico</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Desde</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="w-full py-2 px-3 rounded-xl bg-slate-900 text-white font-semibold hover:bg-slate-800 transition-colors">
                    <i class="bi bi-funnel me-1"></i> Filtrar
                </button>
                @if(request()->anyFilled(['search', 'refund_method', 'date_from', 'date_to']))
                    <a href="{{ route('sales.returns.index') }}" class="p-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-100" title="Limpiar">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Returns Table -->
    <div class="rounded-2xl bg-white border border-slate-200/80 shadow-subtle overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Historial de Devoluciones Registradas</h2>
            <span class="text-xs text-slate-500 font-mono">{{ $returns->total() }} registros</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Nro. Devolución</th>
                        <th class="px-4 py-3">Venta Original</th>
                        <th class="px-4 py-3">Fecha y Hora</th>
                        <th class="px-4 py-3">Atendido Por</th>
                        <th class="px-4 py-3">Método Reembolso</th>
                        <th class="px-4 py-3">Motivo</th>
                        <th class="px-4 py-3 text-right">Total Reembolsado</th>
                        <th class="px-4 py-3 text-center">Comprobante</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($returns as $ret)
                        @php
                            $methodLabel = match($ret->refund_method) {
                                'cash' => 'Efectivo',
                                'credit_note' => 'Nota de Crédito',
                                'card_reversal' => 'Reverso Tarjeta',
                                default => $ret->refund_method,
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3 font-semibold text-slate-900 font-mono">
                                <a href="{{ route('sales.returns.show', $ret) }}" class="text-rose-700 hover:underline">
                                    {{ $ret->return_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('sales.show', $ret->sale) }}" class="text-emerald-700 hover:underline font-mono font-semibold">
                                    {{ $ret->sale->invoice_number }}
                                </a>
                                <span class="block text-[10px] text-slate-400">{{ $ret->sale->customer_name }}</span>
                            </td>
                            <td class="px-4 py-3 font-mono text-[11px]">
                                {{ $ret->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $ret->user->name ?? 'Usuario' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                    {{ $ret->refund_method === 'cash' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $methodLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-700 italic max-w-xs truncate">
                                {{ $ret->reason }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-rose-600">
                                -C$ {{ number_format($ret->totalWithTax(), 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('sales.returns.show', $ret) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-slate-600 hover:text-rose-700 hover:bg-rose-50 transition-colors" title="Ver Comprobante">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-400">
                                <i class="bi bi-arrow-counterclockwise text-2xl"></i>
                                <p class="text-xs mt-1">Aún no se han procesado devoluciones. Selecciona una venta arriba para registrar una devolución.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($returns->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $returns->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
