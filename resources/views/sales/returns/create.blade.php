@extends('layouts.app')

@section('title', 'Procesar Devolución - Venta ' . $sale->invoice_number . ' - PuntoÁgil')

@section('content')
@php
    $effectiveTaxRatio = ((float) $sale->subtotal > 0) ? ((float) $sale->tax / (float) $sale->subtotal) : 0.0;
@endphp
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <a href="{{ route('sales.returns.index') }}" class="hover:text-emerald-600">Devoluciones</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <a href="{{ route('sales.show', $sale) }}" class="hover:text-emerald-600">Venta {{ $sale->invoice_number }}</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-rose-600 font-semibold">Procesar Devolución</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Registrar Devolución de Mercancía</h1>
            <p class="text-xs text-slate-500 mt-0.5">Elige las unidades a devolver; el stock regresará automáticamente a tu inventario.</p>
        </div>

        <a href="{{ route('sales.show', $sale) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50 shadow-subtle transition-all">
            <i class="bi bi-arrow-left"></i> Volver a la Venta
        </a>
    </div>

    <!-- Sale Reference Card -->
    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs flex flex-wrap items-center justify-between gap-4">
        <div>
            <span class="text-[10px] uppercase font-bold text-slate-400 block">Comprobante Original</span>
            <strong class="text-sm font-mono text-slate-900">{{ $sale->invoice_number }}</strong>
            <span class="text-slate-500 block">Fecha: {{ $sale->created_at->format('d/m/Y H:i') }} &bull; Cajero: {{ $sale->user->name ?? 'Cajero' }}</span>
        </div>
        <div>
            <span class="text-[10px] uppercase font-bold text-slate-400 block">Cliente</span>
            <strong class="text-slate-900">{{ $sale->customer_name ?: 'Consumidor Final' }}</strong>
            <span class="text-slate-500 block font-mono">{{ $sale->customer_document ?: '---' }}</span>
        </div>
        <div class="text-right">
            <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Pagado en Venta</span>
            <strong class="text-base font-mono text-emerald-700">C$ {{ number_format($sale->total, 2) }}</strong>
            <span class="text-[11px] text-slate-500 block">Método original: {{ strtoupper($sale->payment_method) }}</span>
        </div>
    </div>

    <!-- Return Form -->
    <form method="POST" action="{{ route('sales.returns.store', $sale) }}" class="rounded-3xl bg-white border border-slate-200/80 shadow-card p-6 space-y-6" id="returnForm">
        @csrf

        <!-- Products to Return Table -->
        <div class="space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                    <i class="bi bi-box-arrow-in-left text-rose-600"></i>
                    <span>1. Selecciona las cantidades a devolver</span>
                </h3>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="fillAllAvailable()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-[11px] font-bold transition-colors">
                        <i class="bi bi-check2-all"></i> Devolver toda la factura
                    </button>
                    <button type="button" onclick="clearAllQuantities()"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-[11px] font-semibold transition-colors">
                        Limpiar
                    </button>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 overflow-x-auto text-xs">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">Producto</th>
                            <th class="py-3 px-3 text-center">Comprado</th>
                            <th class="py-3 px-3 text-center">Devuelto</th>
                            <th class="py-3 px-3 text-center">Disponible</th>
                            <th class="py-3 px-4 text-right">Precio Unit.</th>
                            <th class="py-3 px-4 text-center w-44">Cantidad a Devolver</th>
                            <th class="py-3 px-4 text-right">Subtotal Ítem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($sale->items as $idx => $item)
                            @php
                                $avail = $item->availableForRefund();
                                $oldQty = (int) old("items.{$idx}.quantity", 0);
                            @endphp
                            <tr class="return-item-row hover:bg-slate-50/60 {{ $avail === 0 ? 'opacity-45 bg-slate-50' : '' }}">
                                <td class="py-3 px-4">
                                    <input type="hidden" name="items[{{ $idx }}][sale_item_id]" value="{{ $item->id }}">
                                    <div class="font-bold text-slate-900">{{ $item->product->name ?? 'Producto eliminado' }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">SKU: {{ $item->product->sku ?? '---' }}</div>
                                </td>
                                <td class="py-3 px-3 text-center font-mono">{{ $item->quantity }}</td>
                                <td class="py-3 px-3 text-center font-mono text-rose-600 font-semibold">{{ $item->refunded_quantity }}</td>
                                <td class="py-3 px-3 text-center font-mono font-bold text-slate-800">{{ $avail }}</td>
                                <td class="py-3 px-4 text-right font-mono">C$ {{ number_format($item->unit_price, 2) }}</td>
                                <td class="py-3 px-4 text-center">
                                    @if($avail > 0)
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" onclick="stepQty(this, -1)" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold">-</button>
                                            <input type="number" name="items[{{ $idx }}][quantity]" min="0" max="{{ $avail }}" value="{{ min($avail, max(0, $oldQty)) }}"
                                                   data-price="{{ $item->unit_price }}"
                                                   data-max="{{ $avail }}"
                                                   class="qty-input w-16 px-2 py-1.5 rounded-xl border border-slate-200 text-center font-mono font-bold text-slate-900 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                                                   oninput="calcRefundTotals()">
                                            <button type="button" onclick="stepQty(this, 1)" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold">+</button>
                                            <button type="button" onclick="setMaxQty(this)" class="px-2 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-[10px] font-bold">Máx</button>
                                        </div>
                                    @else
                                        <input type="hidden" name="items[{{ $idx }}][quantity]" value="0">
                                        <span class="text-[11px] text-slate-400 italic">Ya devuelto</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 item-refund-display">
                                    C$ 0.00
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
                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1.5">2. Método de Reembolso</label>
                @php
                    $defaultMethod = old('refund_method', $sale->payment_method === 'cash' ? 'cash' : 'credit_note');
                @endphp
                <select name="refund_method" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500">
                    <option value="cash" {{ $defaultMethod === 'cash' ? 'selected' : '' }}>Efectivo en Caja (Descuenta del turno activo)</option>
                    <option value="credit_note" {{ $defaultMethod === 'credit_note' ? 'selected' : '' }}>Nota de Crédito Interna (Cambio por otro artículo)</option>
                    <option value="card_reversal" {{ $defaultMethod === 'card_reversal' ? 'selected' : '' }}>Reverso a Tarjeta / Transferencia</option>
                </select>
                @if($activeShift)
                    <span class="text-[10px] text-emerald-700 mt-1.5 block">
                        <i class="bi bi-check-circle-fill me-1"></i> Vinculado a tu turno abierto #{{ $activeShift->id }} ({{ $activeShift->cashRegister->name ?? 'Caja Principal' }}).
                    </span>
                @else
                    <span class="text-[10px] text-amber-700 mt-1.5 block">
                        <i class="bi bi-info-circle me-1"></i> Sin turno de caja abierto: quedará registrado contablemente sin afectar arqueo en curso.
                    </span>
                @endif
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1.5">3. Motivo de la Devolución *</label>
                <input type="text" name="reason" id="reasonInput" value="{{ old('reason') }}" required minlength="4" maxlength="255"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500"
                       placeholder="Ej. Producto defectuoso, cambio de medida o error en cobro...">
                <div class="flex flex-wrap gap-1.5 mt-2">
                    @foreach(['Producto defectuoso', 'Cambio de medida o modelo', 'Error en facturación', 'Solicitud del cliente'] as $presetReason)
                        <button type="button" onclick="document.getElementById('reasonInput').value = '{{ $presetReason }}'"
                                class="px-2 py-0.5 rounded-md bg-slate-100 hover:bg-slate-200 text-[10px] font-medium text-slate-600 transition-colors">
                            {{ $presetReason }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Total Refund Summary (Subtotal + IVA Restituido = Total Real) -->
        <div class="p-4 rounded-2xl bg-rose-50/70 border border-rose-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs">
            <div class="space-y-1">
                <span class="text-xs font-bold text-rose-900 uppercase block">Resumen del Reembolso al Cliente</span>
                <div class="flex flex-wrap items-center gap-4 text-[11px] text-rose-800 font-mono">
                    <span>Subtotal: <strong id="subRefundVal">C$ 0.00</strong></span>
                    <span>IVA restituido: <strong id="taxRefundVal">C$ 0.00</strong></span>
                </div>
            </div>
            <div class="sm:text-right">
                <span class="text-[10px] uppercase font-bold text-rose-700 block">Total a Entregar / Acreditar</span>
                <span class="text-2xl font-black font-mono text-rose-700" id="totalRefundVal">C$ 0.00</span>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <a href="{{ route('sales.show', $sale) }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-semibold text-xs hover:bg-slate-50 transition-colors">
                Cancelar
            </a>
            <button type="submit" id="submitReturnBtn" class="btn-shine px-5 py-2.5 rounded-xl bg-rose-600 text-white font-bold text-xs hover:bg-rose-700 transition-all shadow-md">
                <i class="bi bi-arrow-counterclockwise me-1"></i> CONFIRMAR DEVOLUCIÓN Y REPONER STOCK
            </button>
        </div>
    </form>

</div>
@endsection

@section('scripts')
<script>
    const SALE_TAX_RATIO = {{ number_format($effectiveTaxRatio, 6, '.', '') }};

    function formatCordobas(val) {
        return 'C$ ' + Number(val || 0).toFixed(2);
    }

    function stepQty(btn, delta) {
        const input = btn.parentElement.querySelector('.qty-input');
        if (!input) return;
        const max = parseInt(input.dataset.max || input.max) || 0;
        const next = Math.min(max, Math.max(0, (parseInt(input.value) || 0) + delta));
        input.value = next;
        calcRefundTotals();
    }

    function setMaxQty(btn) {
        const input = btn.parentElement.querySelector('.qty-input');
        if (!input) return;
        input.value = parseInt(input.dataset.max || input.max) || 0;
        calcRefundTotals();
    }

    function fillAllAvailable() {
        document.querySelectorAll('.qty-input').forEach(input => {
            input.value = parseInt(input.dataset.max || input.max) || 0;
        });
        calcRefundTotals();
    }

    function clearAllQuantities() {
        document.querySelectorAll('.qty-input').forEach(input => {
            input.value = 0;
        });
        calcRefundTotals();
    }

    function calcRefundTotals() {
        let subtotal = 0;
        document.querySelectorAll('.return-item-row').forEach(row => {
            const input = row.querySelector('.qty-input');
            const display = row.querySelector('.item-refund-display');
            if (!input) {
                if (display) display.textContent = 'C$ 0.00';
                return;
            }
            const max = parseInt(input.dataset.max || input.max) || 0;
            let qty = parseInt(input.value) || 0;
            if (qty < 0) qty = 0;
            if (qty > max) {
                qty = max;
                input.value = max;
            }
            const price = parseFloat(input.dataset.price) || 0;
            const sub = qty * price;
            subtotal += sub;
            if (display) {
                display.textContent = formatCordobas(sub);
            }
        });

        const tax = subtotal * SALE_TAX_RATIO;
        const total = subtotal + tax;

        document.getElementById('subRefundVal').textContent = formatCordobas(subtotal);
        document.getElementById('taxRefundVal').textContent = formatCordobas(tax);
        document.getElementById('totalRefundVal').textContent = formatCordobas(total);
    }

    document.addEventListener('DOMContentLoaded', calcRefundTotals);
</script>
@endsection
