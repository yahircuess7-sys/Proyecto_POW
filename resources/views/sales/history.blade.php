@extends('layouts.app')

@section('title', 'Historial de Ventas y Comprobantes - PuntoÁgil')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="animate-fade-up flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                <span>Historial de Ventas</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-500/15 text-emerald-700 font-mono">M12</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Consulta de comprobantes internos, reimpresión, detalle de cobros y devoluciones.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('sales.returns.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 shadow-subtle transition-all">
                <i class="bi bi-arrow-counterclockwise text-emerald-600"></i>
                <span>Ver Devoluciones</span>
            </a>
            <a href="{{ route('pos.create') }}" class="btn-shine inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-500 text-slate-950 hover:bg-emerald-400 shadow-sm transition-all">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Nueva Venta (POS)</span>
            </a>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-subtle">
        <form method="GET" action="{{ route('sales.history') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3 items-end text-xs">
            <div class="md:col-span-2">
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Buscar</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                           placeholder="Nro comprobante, cliente, cédula...">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Método de Pago</label>
                <select name="payment_method" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <option value="">Todos los métodos</option>
                    <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Efectivo</option>
                    <option value="card" {{ request('payment_method') === 'card' ? 'selected' : '' }}>Tarjeta</option>
                    <option value="transfer" {{ request('payment_method') === 'transfer' ? 'selected' : '' }}>Transferencia</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Estado</label>
                <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <option value="">Todos los estados</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completada</option>
                    <option value="partially_refunded" {{ request('status') === 'partially_refunded' ? 'selected' : '' }}>Devolución Parcial</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Devuelta Total</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelada</option>
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
                @if(request()->anyFilled(['search', 'payment_method', 'status', 'date_from', 'date_to', 'user_id']))
                    <a href="{{ route('sales.history') }}" class="p-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-100" title="Limpiar">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Sales Table -->
    <div class="rounded-2xl bg-white border border-slate-200/80 shadow-subtle overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Comprobantes de Venta</h2>
            <span class="text-xs text-slate-500 font-mono">{{ $sales->total() }} registros</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Nro. Comprobante</th>
                        <th class="px-4 py-3">Fecha y Hora</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Atendido Por</th>
                        <th class="px-4 py-3">Método Pago</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($sales as $sale)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3 font-semibold text-slate-900">
                                <a href="{{ route('sales.show', $sale) }}" class="text-emerald-700 hover:underline font-mono">
                                    {{ $sale->invoice_number }}
                                </a>
                                @if($sale->cash_shift_id)
                                    <span class="block text-[10px] text-slate-400">Turno #{{ $sale->cash_shift_id }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-[11px]">
                                {{ $sale->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $sale->customer_name }}</div>
                                <span class="text-[10px] font-mono text-slate-400">{{ $sale->customer_document }}</span>
                            </td>
                            <td class="px-4 py-3">
                                {{ $sale->user->name ?? 'Cajero' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase
                                    {{ $sale->payment_method === 'cash' ? 'bg-emerald-100 text-emerald-800' : ($sale->payment_method === 'card' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800') }}">
                                    {{ $sale->payment_method ?? 'EFECTIVO' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-slate-700">
                                ${{ number_format($sale->subtotal, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-slate-900">
                                ${{ number_format($sale->total, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($sale->status === 'completed')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        Completada
                                    </span>
                                @elseif($sale->status === 'partially_refunded')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                        Dev. Parcial
                                    </span>
                                @elseif($sale->status === 'refunded')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                        Devuelta
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                        {{ $sale->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ route('sales.show', $sale) }}" class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 transition-colors" title="Ver Detalle">
                                        <i class="bi bi-eye text-sm"></i>
                                    </a>
                                    <a href="{{ route('sales.pdf', $sale) }}" class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors" title="Descargar Comprobante PDF">
                                        <i class="bi bi-file-earmark-pdf text-sm"></i>
                                    </a>
                                    @if($sale->canBeRefunded())
                                        <a href="{{ route('sales.returns.create', $sale) }}" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-700 hover:bg-rose-50 transition-colors" title="Procesar Devolución">
                                            <i class="bi bi-arrow-counterclockwise text-sm"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-slate-400">
                                <i class="bi bi-receipt text-2xl"></i>
                                <p class="text-xs mt-1">No se encontraron ventas que coincidan con los filtros.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $sales->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
