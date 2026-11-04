@extends('layouts.app')

@section('title', $customer->name . ' - PuntoÁgil')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <!-- Top Navigation & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <a href="{{ route('customers.index') }}" 
           class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="bi bi-arrow-left text-sm"></i>
            <span>Volver a Clientes</span>
        </a>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('customers.edit', $customer->id) }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 transition-all shadow-sm">
                <i class="bi bi-pencil"></i>
                <span>Editar Ficha</span>
            </a>

            @can('delete', $customer)
            <form action="{{ route('customers.toggle-status', $customer->id) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <button type="submit" 
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold {{ $customer->isActive() ? 'text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200' }} transition-all shadow-sm">
                    <i class="bi {{ $customer->isActive() ? 'bi-slash-circle' : 'bi-check-circle' }}"></i>
                    <span>{{ $customer->isActive() ? 'Desactivar Cliente' : 'Reactivar Cliente' }}</span>
                </button>
            </form>
            @endcan

            <a href="{{ route('pos.create') }}?customer_id={{ $customer->id }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-500/20 transition-all">
                <i class="bi bi-cart-plus"></i>
                <span>Nueva Venta</span>
            </a>
        </div>
    </div>

    <!-- Customer Profile Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
            
            <!-- Left Info -->
            <div class="md:col-span-7 space-y-6">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="inline-block px-2.5 py-1 rounded-lg bg-slate-100 font-mono text-xs font-bold text-slate-800 border border-slate-200">
                            {{ $customer->document_type }}: {{ $customer->document_number }}
                        </span>
                        @if($customer->isActive())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span> Activo
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1"></span> Desactivado
                            </span>
                        @endif
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $customer->name }}</h1>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 block font-medium">Correo Electrónico</span>
                        <span class="font-mono text-slate-800 mt-0.5 block truncate">{{ $customer->email ?: 'No registrado' }}</span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 block font-medium">Teléfono / Celular</span>
                        <span class="font-mono text-slate-800 mt-0.5 block">{{ $customer->phone ?: 'No registrado' }}</span>
                    </div>
                    <div class="sm:col-span-2 p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 block font-medium">Dirección Domiciliaria / Fiscal</span>
                        <span class="text-slate-800 mt-0.5 block">{{ $customer->address ?: 'Sin dirección física especificada' }}</span>
                    </div>
                    @if($customer->notes)
                    <div class="sm:col-span-2 p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 block font-medium">Notas Comerciales</span>
                        <p class="text-slate-700 mt-0.5 leading-relaxed">{{ $customer->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Right KPIs -->
            <div class="md:col-span-5">
                <div class="rounded-2xl p-6 bg-slate-50 border border-slate-200/80 space-y-5">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Total Facturado</span>
                        <div class="text-3xl font-extrabold font-mono text-emerald-600 mt-1">
                            ${{ number_format($totalSpent, 2) }}
                        </div>
                        <span class="text-xs text-slate-500 mt-0.5 block">Monto histórico consumido en esta empresa</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-4 border-t border-slate-200/80 text-xs">
                        <div>
                            <span class="text-slate-400 text-[11px] block">Ventas Emitidas:</span>
                            <span class="font-mono text-slate-900 font-bold text-lg">{{ $salesCount }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 text-[11px] block">Ticket Promedio:</span>
                            <span class="font-mono text-slate-900 font-bold text-lg">${{ number_format($averageTicket, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Sales History Table -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Historial de Comprobantes de Venta</h3>
                <p class="text-xs text-slate-500 mt-0.5">Tickets y facturas asociadas a este cliente en el negocio.</p>
            </div>
            <span class="pa-badge-slate font-mono text-xs">
                {{ $salesCount }} transacciones
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                        <th class="py-3 px-6">Comprobante #</th>
                        <th class="py-3 px-4">Fecha / Hora</th>
                        <th class="py-3 px-4">Atendido Por</th>
                        <th class="py-3 px-4 text-right">Subtotal</th>
                        <th class="py-3 px-4 text-right">IVA</th>
                        <th class="py-3 px-4 text-right">Total</th>
                        <th class="py-3 px-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customer->sales as $sale)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-6 font-mono font-bold text-slate-900">
                            {{ $sale->invoice_number }}
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-500">
                            {{ $sale->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="py-3 px-4 text-slate-600">
                            {{ $sale->user->name ?? 'Cajero' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-slate-600">
                            ${{ number_format($sale->subtotal, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-slate-500">
                            ${{ number_format($sale->tax, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">
                            ${{ number_format($sale->total, 2) }}
                        </td>
                        <td class="py-3 px-6 text-right">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                <a href="{{ route('sales.show', $sale->id) }}" class="pa-btn-ghost py-1 px-2 text-xs" title="Ver Comprobante">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('sales.pdf', $sale->id) }}" class="pa-btn-ghost py-1 px-2 text-xs text-rose-600 hover:bg-rose-50" title="Descargar PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">
                            <i class="bi bi-receipt text-2xl mb-1 opacity-40 block"></i>
                            Este cliente aún no registra transacciones de compra en este negocio.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
