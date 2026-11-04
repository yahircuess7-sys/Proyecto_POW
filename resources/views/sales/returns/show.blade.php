@extends('layouts.app')

@section('title', 'Comprobante de Devolución ' . $saleReturn->return_number . ' - PuntoÁgil')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Actions Bar -->
    <div class="flex items-center justify-between gap-3 print:hidden">
        <a href="{{ route('sales.returns.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
            <i class="bi bi-arrow-left"></i> Volver a Devoluciones
        </a>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 shadow-subtle transition-all">
                <i class="bi bi-printer"></i> Imprimir Nota
            </button>
            <a href="{{ route('sales.show', $saleReturn->sale) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 shadow-subtle transition-all">
                <i class="bi bi-receipt"></i> Ver Venta Original
            </a>
        </div>
    </div>

    <!-- Voucher Container -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-card p-8 space-y-6 text-slate-800">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 pb-5 gap-3">
            <div>
                <span class="text-xs font-extrabold font-mono text-rose-600 uppercase tracking-wider block">PUNTOÁGIL SAAS &bull; COMPROBANTE DE REEMBOLSO</span>
                <h1 class="text-xl font-bold text-slate-900 mt-1">Nota de Devolución {{ $saleReturn->return_number }}</h1>
                <p class="text-xs text-slate-500">Asociada a venta: <strong>{{ $saleReturn->sale->invoice_number }}</strong></p>
            </div>
            <div class="sm:text-right">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                    MERCANCÍA RESTITUIDA
                </span>
                <div class="text-[11px] text-slate-400 mt-1">Fecha: {{ $saleReturn->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <!-- Meta Data Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-2xl bg-slate-50 text-xs">
            <div>
                <span class="text-[10px] text-slate-400 font-bold uppercase block">Venta Original</span>
                <strong class="text-slate-900 font-mono">{{ $saleReturn->sale->invoice_number }}</strong>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 font-bold uppercase block">Cliente</span>
                <strong class="text-slate-900">{{ $saleReturn->sale->customer_name }}</strong>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 font-bold uppercase block">Atendido Por</span>
                <strong class="text-slate-900">{{ $saleReturn->user->name ?? 'Usuario' }}</strong>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 font-bold uppercase block">Método Reembolso</span>
                <strong class="text-rose-700 font-semibold uppercase">{{ $saleReturn->refund_method }}</strong>
            </div>
        </div>

        <!-- Returned Items Table -->
        <div class="space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Artículos Reintegrados al Inventario</h3>
            
            <div class="rounded-2xl border border-slate-200 overflow-hidden text-xs">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-4">Producto</th>
                            <th class="py-2.5 px-4 text-center">Cantidad Reingresada</th>
                            <th class="py-2.5 px-4 text-right">Precio Unitario</th>
                            <th class="py-2.5 px-4 text-right">Subtotal Reembolsado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($saleReturn->items as $item)
                            <tr>
                                <td class="py-2.5 px-4">
                                    <div class="font-bold text-slate-900">{{ $item->product->name ?? 'Producto' }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">SKU: {{ $item->product->sku ?? '---' }}</div>
                                </td>
                                <td class="py-2.5 px-4 text-center font-mono font-bold text-slate-900">
                                    {{ $item->quantity }}
                                </td>
                                <td class="py-2.5 px-4 text-right font-mono text-slate-700">
                                    ${{ number_format($item->unit_price, 2) }}
                                </td>
                                <td class="py-2.5 px-4 text-right font-mono font-bold text-rose-700">
                                    -${{ number_format($item->subtotal, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                        <tr>
                            <td colspan="3" class="py-3 px-4 text-right uppercase text-slate-700">Total Reembolsado:</td>
                            <td class="py-3 px-4 text-right font-mono text-base text-rose-700">-${{ number_format($saleReturn->total_refunded, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Motivo y Justificación -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-1">
            <span class="text-[10px] font-bold uppercase text-slate-500 block">Motivo / Causa de la Devolución:</span>
            <p class="text-slate-800 italic">{{ $saleReturn->reason }}</p>
        </div>

        <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs flex items-center gap-2.5 text-emerald-900">
            <i class="bi bi-shield-check text-base text-emerald-600"></i>
            <span><strong>Trazabilidad de Inventario:</strong> Los productos devueltos fueron restituidos con registro tipo ENTRADA en la bitácora de movimientos (Kardex).</span>
        </div>

        <!-- Firmas -->
        <div class="pt-8 border-t border-slate-200 grid grid-cols-2 gap-8 text-center text-xs">
            <div>
                <div class="border-b border-slate-300 pb-1 mb-1 font-semibold text-slate-700">{{ $saleReturn->user->name ?? 'Responsable' }}</div>
                <span class="text-[10px] text-slate-400 uppercase">Firma del Cajero</span>
            </div>
            <div>
                <div class="border-b border-slate-300 pb-1 mb-1 font-semibold text-slate-700">{{ $saleReturn->sale->customer_name }}</div>
                <span class="text-[10px] text-slate-400 uppercase">Firma del Cliente (Conformidad)</span>
            </div>
        </div>

    </div>

</div>
@endsection
