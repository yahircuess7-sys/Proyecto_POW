@extends('layouts.app')

@section('title', 'Recibo de Venta ' . $sale->invoice_number . ' - PuntoÁgil')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <a href="{{ route('reports.index') }}" class="hover:text-emerald-600 transition-colors">Reportes y Auditoría</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span>Comprobante</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <span>Comprobante Digital</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <i class="bi bi-check-circle-fill text-[11px] mr-1"></i> Registrada
                </span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Detalle fiscal y desglose de artículos de la transacción.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <i class="bi bi-printer text-sm text-slate-500"></i>
                <span>Imprimir Recibo</span>
            </button>
            <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <i class="bi bi-arrow-left text-sm text-slate-500"></i>
                <span>Volver a Reportes</span>
            </a>
            <a href="{{ route('sales.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-sm hover:shadow transition-all focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                <i class="bi bi-cart-plus text-sm"></i>
                <span>Nueva Venta</span>
            </a>
        </div>
    </div>

    <!-- Voucher & Invoice Card layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Invoice Metadata -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <span class="text-[11px] uppercase tracking-wider font-bold text-slate-400">Nº de Factura</span>
                    <div class="text-lg font-mono font-bold text-slate-900 mt-0.5">{{ $sale->invoice_number }}</div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
                    <i class="bi bi-receipt text-lg"></i>
                </div>
            </div>

            <div class="space-y-4 pt-4 text-xs">
                <div>
                    <span class="text-slate-400 block font-medium">Fecha y Hora de Emisión</span>
                    <div class="text-slate-700 font-mono font-medium mt-0.5 flex items-center gap-1.5">
                        <i class="bi bi-calendar-event text-slate-400"></i>
                        {{ $sale->created_at->format('d/m/Y') }} · {{ $sale->created_at->format('H:i:s') }}
                    </div>
                </div>

                <div>
                    <span class="text-slate-400 block font-medium">Cajero / Operador</span>
                    <div class="text-slate-800 font-medium mt-0.5 flex items-center gap-1.5">
                        <div class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center text-[10px] font-bold">
                            {{ strtoupper(substr($sale->user->name ?? 'C', 0, 1)) }}
                        </div>
                        <span>{{ $sale->user->name ?? 'Cajero Desconocido' }}</span>
                        <span class="text-[10px] text-slate-400">({{ $sale->user->email ?? 'pos@puntoagil.com' }})</span>
                    </div>
                </div>

                <div>
                    <span class="text-slate-400 block font-medium">Establecimiento / Emisor</span>
                    <div class="text-slate-700 font-medium mt-0.5">
                        PuntoÁgil Cloud POS
                    </div>
                    <div class="text-[11px] text-slate-400">Facturación Electrónica &bull; Sistema Certificado</div>
                </div>

                <div>
                    <span class="text-slate-400 block font-medium">Total de Artículos</span>
                    <div class="text-slate-800 font-medium mt-0.5">
                        {{ $sale->items->sum('quantity') }} unidades ({{ $sale->items->count() }} ítems distintos)
                    </div>
                </div>
            </div>
        </div>

        <!-- Totals Financial Summary -->
        <div class="lg:col-span-2 bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700 rounded-2xl p-6 shadow-sm text-white flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-700/80">
                    <div>
                        <span class="text-xs uppercase tracking-wider font-semibold text-emerald-400">Liquidación Fiscal</span>
                        <h2 class="text-lg font-bold text-white">Resumen Financiero</h2>
                    </div>
                    <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-medium bg-white/10 text-slate-200">
                        IVA 12% Vigente
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 py-6">
                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                        <span class="text-xs text-slate-300 block mb-1">Base Imponible (Subtotal)</span>
                        <div class="text-2xl font-mono font-bold text-white">
                            ${{ number_format($sale->subtotal, 2) }}
                        </div>
                    </div>
                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                        <span class="text-xs text-slate-300 block mb-1">Impuesto Agregado (IVA 12%)</span>
                        <div class="text-2xl font-mono font-bold text-emerald-400">
                            ${{ number_format($sale->tax, 2) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-700/80 flex items-center justify-between">
                <div>
                    <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Total Cancelado</span>
                    <p class="text-[11px] text-slate-400">Pago en efectivo / procesado de inmediato</p>
                </div>
                <div class="text-right">
                    <span class="text-3xl font-mono font-extrabold text-white tracking-tight">
                        ${{ number_format($sale->total, 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Table Card -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="bi bi-box-seam text-emerald-600"></i>
                <span>Artículos de la Factura</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                    {{ $sale->items->count() }}
                </span>
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Producto</th>
                        <th class="py-3.5 px-6 text-center">Cantidad</th>
                        <th class="py-3.5 px-6 text-right">Precio Unitario</th>
                        <th class="py-3.5 px-6 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($sale->items as $item)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-6">
                                <div class="font-semibold text-slate-900">{{ $item->product->name ?? 'Producto no disponible / eliminado' }}</div>
                                @if(isset($item->product->sku))
                                    <div class="text-[11px] font-mono text-slate-400">SKU: {{ $item->product->sku }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-6 text-center font-mono font-medium text-slate-700">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-800">
                                    {{ $item->quantity }}
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-right font-mono text-slate-600">
                                ${{ number_format($item->unit_price, 2) }}
                            </td>
                            <td class="py-3.5 px-6 text-right font-mono font-bold text-slate-900">
                                ${{ number_format($item->subtotal, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400">
                                No se encontraron artículos asociados a este comprobante.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-50/80 border-t border-slate-200/80 text-xs font-semibold">
                    <tr>
                        <td colspan="3" class="py-3 px-6 text-right text-slate-500">Subtotal:</td>
                        <td class="py-3 px-6 text-right font-mono text-slate-800">${{ number_format($sale->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="py-2 px-6 text-right text-slate-500">IVA 12%:</td>
                        <td class="py-2 px-6 text-right font-mono text-emerald-600">+${{ number_format($sale->tax, 2) }}</td>
                    </tr>
                    <tr class="border-t border-slate-200">
                        <td colspan="3" class="py-3 px-6 text-right text-sm font-bold text-slate-900">Total Facturado:</td>
                        <td class="py-3 px-6 text-right font-mono text-base font-bold text-emerald-600">${{ number_format($sale->total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
