@extends('layouts.app')

@section('title', 'Comprobante Interno ' . $sale->invoice_number . ' - PuntoÁgil')

@section('content')
<div class="space-y-6">

    <!-- Top Disclaimer Banner -->
    <div class="px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs flex items-center justify-between gap-3 shadow-subtle">
        <div class="flex items-center gap-2">
            <i class="bi bi-info-circle-fill text-amber-600"></i>
            <span><strong>Comprobante Interno:</strong> Documento emitido para control operativo y trazabilidad interna. Sin validez tributaria o certificación fiscal oficial.</span>
        </div>
        <span class="text-[10px] font-mono uppercase font-bold text-amber-800">Control Operativo</span>
    </div>

    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <a href="{{ route('sales.history') }}" class="hover:text-emerald-600 transition-colors">Historial de Ventas</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span>Comprobante {{ $sale->invoice_number }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <span>Comprobante de Venta</span>
                @if($sale->status === 'completed')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                        <i class="bi bi-check-circle-fill text-[11px] mr-1"></i> Completada
                    </span>
                @elseif($sale->status === 'partially_refunded')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                        <i class="bi bi-arrow-counterclockwise text-[11px] mr-1"></i> Devolución Parcial
                    </span>
                @elseif($sale->status === 'refunded')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                        <i class="bi bi-x-circle-fill text-[11px] mr-1"></i> Totalmente Devuelta
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                        {{ $sale->status }}
                    </span>
                @endif
            </h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('sales.ticket', ['sale' => $sale->id, 'autoprint' => 1]) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl shadow-subtle transition-all">
                <i class="bi bi-printer-fill"></i>
                <span>Ticket Térmico</span>
            </a>
            <a href="{{ route('sales.pdf', $sale) }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 shadow-subtle transition-all">
                <i class="bi bi-file-earmark-pdf text-rose-600"></i>
                <span>Descargar PDF A4</span>
            </a>
            @if($sale->canBeRefunded())
                <a href="{{ route('sales.returns.create', $sale) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-xl hover:bg-rose-100 transition-all">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <span>Procesar Devolución</span>
                </a>
            @endif
            <a href="{{ route('sales.history') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 shadow-subtle transition-all">
                <i class="bi bi-arrow-left"></i>
                <span>Volver</span>
            </a>
        </div>
    </div>

    <!-- Metadata & Financial Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Metadata Card -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-subtle space-y-4 text-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Nº Comprobante Interno</span>
                    <div class="text-base font-mono font-bold text-slate-900 mt-0.5">{{ $sale->invoice_number }}</div>
                </div>
                <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                    <i class="bi bi-receipt text-base"></i>
                </div>
            </div>

            <div class="space-y-3">
                <div>
                    <span class="text-slate-400 block font-medium">Fecha y Hora</span>
                    <div class="text-slate-800 font-mono font-medium mt-0.5">
                        {{ $sale->created_at->format('d/m/Y H:i:s') }}
                    </div>
                </div>

                <div>
                    <span class="text-slate-400 block font-medium">Cajero / Atendido Por</span>
                    <div class="text-slate-800 font-medium mt-0.5">
                        {{ $sale->user->name ?? 'Cajero' }}
                    </div>
                </div>

                <div>
                    <span class="text-slate-400 block font-medium">Cliente</span>
                    <div class="text-slate-800 font-medium mt-0.5">
                        {{ $sale->customer_name }}
                        @if($sale->customer_document)
                            <div class="font-mono text-[11px] text-slate-400">{{ $sale->customer_document }}</div>
                        @endif
                    </div>
                </div>

                <div>
                    <span class="text-slate-400 block font-medium">Turno de Caja Vinculado</span>
                    <div class="text-slate-800 font-medium mt-0.5">
                        @if($sale->cashShift)
                            <a href="{{ route('cash.show', $sale->cashShift) }}" class="text-emerald-700 hover:underline font-mono">
                                Turno #{{ $sale->cashShift->id }} &bull; {{ $sale->cashShift->cashRegister->name ?? 'Caja' }}
                            </a>
                        @else
                            <span class="text-slate-400 italic">Sin turno asociado</span>
                        @endif
                    </div>
                </div>

                <div>
                    <span class="text-slate-400 block font-medium">Método de Pago Registrado</span>
                    <div class="mt-0.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold uppercase
                            {{ $sale->payment_method === 'cash' ? 'bg-emerald-100 text-emerald-800' : ($sale->payment_method === 'card' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800') }}">
                            {{ $sale->payment_method ?? 'EFECTIVO' }}
                        </span>
                        @if($sale->payment_method === 'cash' && $sale->paid_amount)
                            <div class="text-[11px] text-slate-500 mt-1 font-mono">
                                Recibido: C$ {{ number_format($sale->paid_amount, 2) }} | Cambio: C$ {{ number_format($sale->change_amount, 2) }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Summary Card -->
        @php
            $saleBizTaxPct = ($sale->business ?? $currentBusiness ?? null)?->effectiveTaxPercentage(15.0) ?? 15.0;
        @endphp
        <div class="lg:col-span-2 bg-gradient-to-br from-slate-900 to-slate-950 border border-slate-800 rounded-2xl p-6 shadow-card text-white flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <div>
                        <span class="text-xs uppercase tracking-wider font-semibold text-emerald-400 font-mono">Control Administrativo (NIO)</span>
                        <h2 class="text-lg font-bold text-white">Liquidación de la Venta</h2>
                    </div>
                    <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-medium bg-white/10 text-slate-200">
                        IVA {{ $saleBizTaxPct }}% Desglosado
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 py-5 text-xs">
                    <div class="p-3.5 rounded-xl bg-white/5 border border-white/10">
                        <span class="text-slate-400 block mb-1">Subtotal</span>
                        <div class="text-xl font-mono font-bold text-white">
                            C$ {{ number_format($sale->subtotal, 2) }}
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-white/5 border border-white/10">
                        <span class="text-slate-400 block mb-1">Descuento</span>
                        <div class="text-xl font-mono font-bold {{ $sale->discount > 0 ? 'text-rose-400' : 'text-slate-400' }}">
                            -C$ {{ number_format($sale->discount, 2) }}
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-white/5 border border-white/10">
                        <span class="text-slate-400 block mb-1">IVA ({{ $saleBizTaxPct }}%)</span>
                        <div class="text-xl font-mono font-bold text-emerald-400">
                            C$ {{ number_format($sale->tax, 2) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
                <div>
                    <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Total Cobrado</span>
                    <p class="text-[11px] text-slate-400">Registrado en trazabilidad contable (Córdobas)</p>
                </div>
                <div class="text-right">
                    <span class="text-3xl font-mono font-black text-white tracking-tight">
                        C$ {{ number_format($sale->total, 2) }}
                    </span>
                </div>
            </div>
        </div>

    </div>

    <!-- Items Table Card -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-subtle overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                <i class="bi bi-boxes text-emerald-600"></i>
                <span>Artículos Facturados</span>
                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-mono">
                    {{ $sale->items->count() }}
                </span>
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-5">Producto / SKU</th>
                        <th class="py-3 px-5 text-center">Cant. Vendida</th>
                        <th class="py-3 px-5 text-center">Cant. Devuelta</th>
                        <th class="py-3 px-5 text-center">Disponible Devolver</th>
                        <th class="py-3 px-5 text-right">Precio Unitario</th>
                        <th class="py-3 px-5 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($sale->items as $item)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3 px-5">
                                <div class="font-bold text-slate-900">{{ $item->product->name ?? 'Producto eliminado' }}</div>
                                @if(isset($item->product->sku))
                                    <div class="text-[10px] font-mono text-slate-400">SKU: {{ $item->product->sku }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-5 text-center font-mono font-semibold text-slate-800">
                                {{ $item->quantity }}
                            </td>
                            <td class="py-3 px-5 text-center font-mono">
                                @if($item->refunded_quantity > 0)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-rose-100 text-rose-800 font-bold">
                                        {{ $item->refunded_quantity }}
                                    </span>
                                @else
                                    <span class="text-slate-400">0</span>
                                @endif
                            </td>
                            <td class="py-3 px-5 text-center font-mono">
                                <span class="font-bold text-slate-700">{{ $item->availableForRefund() }}</span>
                            </td>
                            <td class="py-3 px-5 text-right font-mono text-slate-700">
                                C$ {{ number_format($item->unit_price, 2) }}
                            </td>
                            <td class="py-3 px-5 text-right font-mono font-bold text-slate-900">
                                C$ {{ number_format($item->subtotal, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                Sin artículos asociados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Devoluciones Vinculadas -->
    @if($sale->returns && $sale->returns->isNotEmpty())
        <div class="bg-white border border-slate-200/80 rounded-2xl shadow-subtle overflow-hidden space-y-3 p-5">
            <h3 class="text-xs font-bold uppercase tracking-wider text-rose-800 flex items-center gap-2">
                <i class="bi bi-arrow-counterclockwise text-rose-600"></i>
                <span>Devoluciones Asociadas a esta Venta</span>
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-4">Nro Devolución</th>
                            <th class="py-2.5 px-4">Fecha</th>
                            <th class="py-2.5 px-4">Atendido Por</th>
                            <th class="py-2.5 px-4">Método Reembolso</th>
                            <th class="py-2.5 px-4">Motivo</th>
                            <th class="py-2.5 px-4 text-right">Monto Reembolsado</th>
                            <th class="py-2.5 px-4 text-center">Comprobante</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($sale->returns as $ret)
                            <tr>
                                <td class="py-2.5 px-4 font-mono font-bold text-slate-900">{{ $ret->return_number }}</td>
                                <td class="py-2.5 px-4 font-mono">{{ $ret->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-2.5 px-4">{{ $ret->user->name ?? 'Usuario' }}</td>
                                <td class="py-2.5 px-4">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase bg-slate-100 text-slate-700">
                                        {{ $ret->refund_method }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 italic text-slate-700">{{ $ret->reason }}</td>
                                <td class="py-2.5 px-4 text-right font-mono font-bold text-rose-600">
                                    -C$ {{ number_format($ret->totalWithTax(), 2) }}
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <a href="{{ route('sales.returns.show', $ret) }}" class="p-1 rounded text-slate-500 hover:text-emerald-700" title="Ver comprobante de devolución">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection
