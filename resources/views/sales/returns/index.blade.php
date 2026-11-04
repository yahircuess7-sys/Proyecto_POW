@extends('layouts.app')

@section('title', 'Devoluciones y Notas de Crédito - PuntoÁgil')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="animate-fade-up flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                <span>Devoluciones y Notas de Crédito</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-rose-500/15 text-rose-700 font-mono">M12</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Registro de mercancía devuelta, reposición de inventario y justificación contable.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('sales.history') }}" class="btn-shine inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-900 text-white hover:bg-slate-800 shadow-sm transition-all">
                <i class="bi bi-receipt"></i>
                <span>Historial de Ventas</span>
            </a>
            <a href="{{ route('pos.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 shadow-subtle transition-all">
                <i class="bi bi-cart4 text-emerald-600"></i>
                <span>Terminal POS</span>
            </a>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-subtle">
        <form method="GET" action="{{ route('sales.returns.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end text-xs">
            <div class="md:col-span-2">
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Buscar</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                           placeholder="Nro devolución, venta, motivo...">
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
            <h2 class="text-sm font-bold text-slate-900">Listado de Devoluciones</h2>
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
                        <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($returns as $ret)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3 font-semibold text-slate-900 font-mono">
                                <a href="{{ route('sales.returns.show', $ret) }}" class="text-rose-700 hover:underline">
                                    {{ $ret->return_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('sales.show', $ret->sale) }}" class="text-emerald-700 hover:underline font-mono">
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
                                    {{ $ret->refund_method }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-700 italic max-w-xs truncate">
                                {{ $ret->reason }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-rose-600">
                                -${{ number_format($ret->total_refunded, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('sales.returns.show', $ret) }}" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-700 hover:bg-rose-50 transition-colors" title="Ver Comprobante">
                                    <i class="bi bi-eye text-sm"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-400">
                                <i class="bi bi-arrow-counterclockwise text-2xl"></i>
                                <p class="text-xs mt-1">No se encontraron devoluciones registradas.</p>
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
