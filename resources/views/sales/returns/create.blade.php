@extends('layouts.app')

@section('title', 'Procesar Devolución - Venta ' . $sale->invoice_number . ' - PuntoÁgil')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <a href="{{ route('sales.show', $sale) }}" class="hover:text-emerald-600">Venta {{ $sale->invoice_number }}</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span>Procesar Devolución</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                <span>Registrar Devolución de Mercancía</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-rose-500/15 text-rose-700 font-mono">Control M12</span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">La mercancía devuelta se reintegrará automáticamente al inventario activo.</p>
        </div>

        <a href="{{ route('sales.show', $sale) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50 shadow-subtle transition-all">
            <i class="bi bi-arrow-left"></i> Cancelar
        </a>
    </div>

    <!-- Sale Reference Card -->
    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs flex flex-wrap items-center justify-between gap-4">
        <div>
            <span class="text-[10px] uppercase font-bold text-slate-400 block">Venta Origen</span>
            <strong class="text-sm font-mono text-slate-900">{{ $sale->invoice_number }}</strong>
            <span class="text-slate-500 block">Fecha: {{ $sale->created_at->format('d/m/Y H:i') }} &bull; Atendido por: {{ $sale->user->name ?? 'Cajero' }}</span>
        </div>
        <div>
            <span class="text-[10px] uppercase font-bold text-slate-400 block">Cliente</span>
            <strong class="text-slate-900">{{ $sale->customer_name }}</strong>
            <span class="text-slate-500 block font-mono">{{ $sale->customer_document }}</span>
        </div>
        <div class="text-right">
            <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Venta Original</span>
            <strong class="text-base font-mono text-emerald-700">${{ number_format($sale->total, 2) }}</strong>
            <span class="text-[11px] text-slate-500 block">Pagado vía: {{ strtoupper($sale->payment_method) }}</span>
        </div>
    </div>

    <!-- Return Form -->
    <form method="POST" action="{{ route('sales.returns.store', $sale) }}" class="rounded-3xl bg-white border border-slate-200/80 shadow-card p-6 space-y-6" id="returnForm">
        @csrf

        <!-- Products to Return Table -->
        <div class="space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                <i class="bi bi-box-arrow-in-left text-rose-600"></i>
                <span>Seleccionar Cantidades a Devolver</span>
            </h3>

            <div class="rounded-2xl border border-slate-200 overflow-hidden text-xs">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">Producto</th>
                            <th class="py-3 px-4 text-center">Vendida</th>
                            <th class="py-3 px-4 text-center">Ya Devuelta</th>
                            <th class="py-3 px-4 text-center">Disponible</th>
                            <th class="py-3 px-4 text-right">Precio Unitario</th>
                            <th class="py-3 px-4 text-center w-36">Cantidad a Devolver</th>
                            <th class="py-3 px-4 text-right">Reembolso Ítem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($sale->items as $idx => $item)
                            @php
                                $avail = $item->availableForRefund();
                            @endphp
                            <tr class="hover:bg-slate-50/60 {{ $avail === 0 ? 'opacity-40 bg-slate-50' : '' }}">
                                <td class="py-3 px-4">
                                    <input type="hidden" name="items[{{ $idx }}][sale_item_id]" value="{{ $item->id }}">
                                    <div class="font-bold text-slate-900">{{ $item->product->name ?? 'Producto eliminado' }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">SKU: {{ $item->product->sku ?? '---' }}</div>
                                </td>
                                <td class="py-3 px-4 text-center font-mono">{{ $item->quantity }}</td>
                                <td class="py-3 px-4 text-center font-mono text-rose-600 font-semibold">{{ $item->refunded_quantity }}</td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-slate-800">{{ $avail }}</td>
                                <td class="py-3 px-4 text-right font-mono">${{ number_format($item->unit_price, 2) }}</td>
                                <td class="py-3 px-4 text-center">
                                    @if($avail > 0)
                                        <input type="number" name="items[{{ $idx }}][quantity]" min="0" max="{{ $avail }}" value="0"
                                               data-price="{{ $item->unit_price }}"
                                               class="qty-input w-24 px-2.5 py-1.5 rounded-xl border border-slate-200 text-center font-mono font-bold text-slate-900 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                                               oninput="calcRefundTotals()">
                                    @else
                                        <span class="text-[11px] text-slate-400 italic">Totalmente devuelto</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 item-refund-display">
                                    $0.00
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Refund Method & Reasons -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Método de Reembolso</label>
                <select name="refund_method" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500">
                    <option value="cash" selected>Efectivo en Caja (Deduce del turno de caja activo)</option>
                    <option value="credit_note">Nota de Crédito Interna (Saldo a favor del cliente)</option>
                    <option value="card_reversal">Reverso a Tarjeta / Medio Electrónico</option>
                </select>
                @if($activeShift)
                    <span class="text-[10px] text-emerald-700 mt-1 block">
                        <i class="bi bi-info-circle me-1"></i> Se vinculará con tu turno activo #{{ $activeShift->id }} en {{ $activeShift->cashRegister->name ?? 'Caja' }}.
                    </span>
                @else
                    <span class="text-[10px] text-amber-700 mt-1 block">
                        <i class="bi bi-exclamation-triangle me-1"></i> No tienes turno de caja abierto; se registrará como movimiento contable directo.
                    </span>
                @endif
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Motivo Obligatorio de la Devolución</label>
                <input type="text" name="reason" required minlength="4" maxlength="255"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                       placeholder="Ej. Mercancía defectuosa de fábrica, cambio por otra medida...">
            </div>
        </div>

        <!-- Total Refund Summary -->
        <div class="p-4 rounded-2xl bg-rose-50/70 border border-rose-200 flex items-center justify-between text-xs">
            <div>
                <span class="text-xs font-bold text-rose-900 uppercase">Total a Reembolsar:</span>
                <p class="text-[11px] text-rose-700">Calculado sobre el valor de los artículos devueltos seleccionados.</p>
            </div>
            <span class="text-2xl font-black font-mono text-rose-700" id="totalRefundVal">$0.00</span>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <a href="{{ route('sales.show', $sale) }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-semibold text-xs hover:bg-slate-50 transition-colors">
                Cancelar
            </a>
            <button type="submit" class="btn-shine px-5 py-2.5 rounded-xl bg-rose-600 text-white font-bold text-xs hover:bg-rose-700 transition-all shadow-md">
                <i class="bi bi-arrow-counterclockwise me-1"></i> CONFIRMAR DEVOLUCIÓN Y REPONER STOCK
            </button>
        </div>
    </form>

</div>
@endsection

@section('scripts')
<script>
    function calcRefundTotals() {
        let total = 0;
        const inputs = document.querySelectorAll('.qty-input');
        const displays = document.querySelectorAll('.item-refund-display');

        inputs.forEach((input, index) => {
            const qty = parseInt(input.value) || 0;
            const price = parseFloat(input.dataset.price) || 0;
            const sub = qty * price;
            total += sub;

            if (displays[index]) {
                displays[index].textContent = '$' + sub.toFixed(2);
            }
        });

        document.getElementById('totalRefundVal').textContent = '$' + total.toFixed(2);
    }
</script>
@endsection
