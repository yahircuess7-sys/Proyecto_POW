@extends('layouts.app')

@section('title', 'Corte de Caja #' . $shift->id . ' - PuntoÁgil')

@section('content')
@php
    $expectedVal = (float) ($shift->expected_cash ?? $shift->calculateExpectedCash());
    $countedVal = (float) ($shift->closing_cash ?? 0);
    $diffVal = (float) ($shift->difference ?? ($countedVal - $expectedVal));
    $cashSalesVal = $shift->cashSalesTotal();
    $cardSalesVal = $shift->cardSalesTotal();
    $transferSalesVal = $shift->transferSalesTotal();
    $totalShiftSales = $cashSalesVal + $cardSalesVal + $transferSalesVal;
    $bizName = $currentBusiness->name ?? 'PuntoÁgil';

    $verdictText = abs($diffVal) <= 0.05
        ? 'CAJA CUADRADA EXACTA (C$ 0.00)'
        : ($diffVal > 0 ? 'SOBRANTE: +C$ ' . number_format($diffVal, 2) : 'FALTANTE: -C$ ' . number_format(abs($diffVal), 2));

    $waLines = [
        "*CORTE DE CAJA #{$shift->id} - {$bizName}*",
        "Cajero: " . ($shift->user->name ?? 'Cajero'),
        "Fecha: " . ($shift->closed_at ? $shift->closed_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i')),
        "--------------------------",
        "Ventas Efectivo: C$ " . number_format($cashSalesVal, 2),
        "Ventas Tarjeta: C$ " . number_format($cardSalesVal, 2),
        "Transferencias: C$ " . number_format($transferSalesVal, 2),
        "*Total Ventas Turno: C$ " . number_format($totalShiftSales, 2) . "*",
        "--------------------------",
        "Fondo Inicial: C$ " . number_format($shift->initial_cash, 2),
        "Efectivo Esperado: C$ " . number_format($expectedVal, 2),
        "Efectivo Contado: C$ " . number_format($countedVal, 2),
        "*Resultado Arqueo: {$verdictText}*",
    ];
    if ($shift->notes) {
        $waLines[] = "Nota: " . $shift->notes;
    }
    $whatsappShareUrl = 'https://wa.me/?text=' . rawurlencode(implode("\n", $waLines));
@endphp
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Actions Bar -->
    <div class="flex flex-wrap items-center justify-between gap-3 print:hidden">
        <a href="{{ route('cash.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
            <i class="bi bi-arrow-left"></i> Volver a Turnos
        </a>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ $whatsappShareUrl }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-50 border border-emerald-200 text-emerald-800 hover:bg-emerald-100 transition-all">
                <i class="bi bi-whatsapp text-emerald-600"></i> Enviar Resumen por WhatsApp
            </a>
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-900 text-white hover:bg-slate-800 shadow-subtle transition-all">
                <i class="bi bi-printer-fill text-emerald-400"></i> Imprimir Corte del Día (1 Pág.)
            </button>
            <a href="{{ route('cash.current') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-all">
                <i class="bi bi-wallet2"></i> Mi Turno
            </a>
        </div>
    </div>

    <!-- Printable 1-Page Voucher Container -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-card p-6 sm:p-8 space-y-5 text-slate-800">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 pb-4 gap-3">
            <div>
                <span class="text-xs font-extrabold font-mono text-emerald-600 uppercase tracking-wider block">{{ $bizName }} &bull; CORTE DE CAJA (C$)</span>
                <h1 class="text-xl font-bold text-slate-900 mt-1">Comprobante de Cierre y Arqueo Ciego #{{ $shift->id }}</h1>
                <p class="text-xs text-slate-500">{{ $shift->cashRegister->name ?? 'Caja Principal' }}</p>
            </div>
            <div class="sm:text-right">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $shift->isClosed() ? 'bg-slate-900 text-white' : 'bg-emerald-100 text-emerald-800' }}">
                    {{ $shift->isClosed() ? 'CORTE CERRADO' : 'TURNO EN CURSO' }}
                </span>
                <div class="text-[11px] text-slate-400 mt-1">Emitido: {{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        @if($shift->isClosed())
            <!-- Prominent Arqueo Verdict Banner -->
            <div class="p-4 rounded-2xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3
                {{ abs($diffVal) <= 0.05 ? 'bg-emerald-50 border-emerald-200 text-emerald-950' : ($diffVal > 0 ? 'bg-teal-50 border-teal-200 text-teal-950' : 'bg-rose-50 border-rose-200 text-rose-950') }}">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg
                        {{ abs($diffVal) <= 0.05 ? 'bg-emerald-500 text-slate-950' : ($diffVal > 0 ? 'bg-teal-600 text-white' : 'bg-rose-600 text-white') }}">
                        <i class="bi {{ abs($diffVal) <= 0.05 ? 'bi-check2-all' : 'bi-exclamation-diamond-fill' }}"></i>
                    </div>
                    <div>
                        <div class="text-xs font-extrabold uppercase tracking-wider">
                            @if(abs($diffVal) <= 0.05)
                                ¡Arqueo Cuadrado Exactamente!
                            @elseif($diffVal > 0)
                                Sobrante de Efectivo Detectado
                            @else
                                Faltante de Efectivo Detectado
                            @endif
                        </div>
                        <p class="text-[11px] opacity-80">Comparación real entre conteo físico del cajero y ventas registradas.</p>
                    </div>
                </div>
                <div class="font-mono font-black text-xl">
                    @if(abs($diffVal) <= 0.05)
                        C$ 0.00
                    @elseif($diffVal > 0)
                        +C$ {{ number_format($diffVal, 2) }}
                    @else
                        -C$ {{ number_format(abs($diffVal), 2) }}
                    @endif
                </div>
            </div>
        @endif

        <!-- Meta Data Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-2xl bg-slate-50 text-xs">
            <div>
                <span class="text-[10px] text-slate-400 font-bold uppercase block">Apertura</span>
                <strong class="text-slate-800 font-mono">{{ $shift->opened_at->format('d/m/Y H:i') }}</strong>
                <span class="text-[11px] text-slate-500 block">Por: {{ $shift->user->name ?? 'Cajero' }}</span>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 font-bold uppercase block">Cierre</span>
                <strong class="text-slate-800 font-mono">{{ $shift->closed_at ? $shift->closed_at->format('d/m/Y H:i') : 'En curso' }}</strong>
                <span class="text-[11px] text-slate-500 block">Por: {{ $shift->closedByUser->name ?? ($shift->isClosed() ? 'Cajero' : 'Pendiente') }}</span>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 font-bold uppercase block">Fondo Inicial</span>
                <strong class="text-slate-900 font-mono text-sm">C$ {{ number_format($shift->initial_cash, 2) }}</strong>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 font-bold uppercase block">Total Ventas Turno</span>
                <strong class="text-emerald-700 font-mono text-sm">C$ {{ number_format($totalShiftSales, 2) }}</strong>
                <span class="text-[10px] text-slate-400 block">{{ $shift->sales->count() }} tickets</span>
            </div>
        </div>

        <!-- Arqueo Financiero Detallado -->
        <div class="space-y-2.5">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Cuadre de Efectivo en Gaveta</h3>

            <div class="rounded-2xl border border-slate-200 overflow-hidden text-xs">
                <table class="w-full">
                    <tbody class="divide-y divide-slate-100">
                        <tr class="bg-slate-50/50">
                            <td class="px-4 py-2.5 text-slate-600 font-medium">Fondo Inicial en Efectivo</td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-slate-900">C$ {{ number_format($shift->initial_cash, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2.5 text-slate-600">Ventas cobradas en Efectivo (+)</td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-emerald-700">+C$ {{ number_format($cashSalesVal, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2.5 text-slate-600">Entradas autorizadas de Efectivo (+)</td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-emerald-700">+C$ {{ number_format($shift->cashInTotal(), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2.5 text-slate-600">Salidas / Retiros de Efectivo (-)</td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-rose-700">-C$ {{ number_format($shift->cashOutTotal(), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2.5 text-slate-600">Devoluciones pagadas en Efectivo (-)</td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-rose-700">-C$ {{ number_format($shift->cashReturnsTotal(), 2) }}</td>
                        </tr>
                        <tr class="bg-slate-50 font-bold border-t-2 border-slate-200">
                            <td class="px-4 py-3 text-slate-900 uppercase">Efectivo Esperado según Sistema</td>
                            <td class="px-4 py-3 text-right font-mono text-base text-slate-900">C$ {{ number_format($expectedVal, 2) }}</td>
                        </tr>
                        <tr class="bg-emerald-50/60 font-bold">
                            <td class="px-4 py-3 text-emerald-900 uppercase">Efectivo Físico Contado por Cajero</td>
                            <td class="px-4 py-3 text-right font-mono text-base text-emerald-900">
                                C$ {{ number_format($countedVal, 2) }}
                            </td>
                        </tr>
                        <tr class="font-bold {{ ($diffVal > 0.05) ? 'bg-teal-50 text-teal-900' : (($diffVal < -0.05) ? 'bg-rose-50 text-rose-900' : 'bg-slate-50 text-emerald-800') }}">
                            <td class="px-4 py-3 uppercase">Diferencia Final (Contado - Esperado)</td>
                            <td class="px-4 py-3 text-right font-mono text-base">
                                @if($diffVal > 0.05)
                                    +C$ {{ number_format($diffVal, 2) }} (Sobrante)
                                @elseif($diffVal < -0.05)
                                    -C$ {{ number_format(abs($diffVal), 2) }} (Faltante)
                                @else
                                    C$ 0.00 (Cuadrado)
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Desglose de otros métodos de pago -->
        <div class="grid grid-cols-2 gap-3 text-xs p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
            <div>
                <span class="text-slate-500 font-medium">Cobros con Tarjeta (POS Bancario):</span>
                <strong class="font-mono text-slate-800 block text-sm">C$ {{ number_format($cardSalesVal, 2) }}</strong>
            </div>
            <div>
                <span class="text-slate-500 font-medium">Cobros por Transferencia / Móvil:</span>
                <strong class="font-mono text-slate-800 block text-sm">C$ {{ number_format($transferSalesVal, 2) }}</strong>
            </div>
        </div>

        @if($shift->notes)
            <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-200/80 text-xs">
                <span class="text-[10px] font-bold uppercase text-amber-800 block">Observaciones / Justificación del Corte:</span>
                <p class="text-slate-700 mt-1">{{ $shift->notes }}</p>
            </div>
        @endif

        <!-- Footer Firma -->
        <div class="pt-6 border-t border-slate-200 grid grid-cols-2 gap-8 text-center text-xs">
            <div>
                <div class="border-b border-slate-300 pb-1 mb-1 font-semibold text-slate-700">{{ $shift->user->name ?? 'Cajero' }}</div>
                <span class="text-[10px] text-slate-400 uppercase">Firma del Cajero</span>
            </div>
            <div>
                <div class="border-b border-slate-300 pb-1 mb-1 font-semibold text-slate-700">{{ $shift->closedByUser->name ?? 'Propietario / Supervisor' }}</div>
                <span class="text-[10px] text-slate-400 uppercase">Conformidad de Arqueo</span>
            </div>
        </div>

    </div>

</div>
@endsection
