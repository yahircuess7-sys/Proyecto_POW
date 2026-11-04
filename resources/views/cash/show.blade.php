@extends('layouts.app')

@section('title', 'Comprobante de Cierre de Caja #' . $shift->id . ' - PuntoÁgil')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Actions Bar -->
    <div class="flex items-center justify-between gap-3 print:hidden">
        <a href="{{ route('cash.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
            <i class="bi bi-arrow-left"></i> Volver a Turnos
        </a>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 shadow-subtle transition-all">
                <i class="bi bi-printer"></i> Imprimir Arqueo
            </button>
            <a href="{{ route('cash.current') }}" class="btn-shine inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-500 text-slate-950 hover:bg-emerald-400 shadow-sm transition-all">
                <i class="bi bi-wallet2"></i> Mi Turno
            </a>
        </div>
    </div>

    <!-- Printable Voucher Container -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-card p-8 space-y-6 text-slate-800">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 pb-5 gap-3">
            <div>
                <span class="text-xs font-extrabold font-mono text-emerald-600 uppercase tracking-wider block">PUNTOÁGIL SAAS &bull; GESTIÓN DE CAJA</span>
                <h1 class="text-xl font-bold text-slate-900 mt-1">Comprobante de Cierre y Arqueo #{{ $shift->id }}</h1>
                <p class="text-xs text-slate-500">{{ $shift->cashRegister->name ?? 'Caja' }}</p>
            </div>
            <div class="sm:text-right">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $shift->isClosed() ? 'bg-slate-100 text-slate-700' : 'bg-emerald-100 text-emerald-800' }}">
                    {{ $shift->isClosed() ? 'TURNO FINALIZADO' : 'TURNO EN CURSO' }}
                </span>
                <div class="text-[11px] text-slate-400 mt-1">Generado: {{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </div>

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
                <strong class="text-slate-900 font-mono text-sm">${{ number_format($shift->initial_cash, 2) }}</strong>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 font-bold uppercase block">Cant. Ventas</span>
                <strong class="text-slate-900 font-mono text-sm">{{ $shift->sales->count() }} operaciones</strong>
            </div>
        </div>

        <!-- Arqueo Financiero Detallado -->
        <div class="space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Resumen Financiero del Turno</h3>
            
            <div class="rounded-2xl border border-slate-200 overflow-hidden text-xs">
                <table class="w-full">
                    <tbody class="divide-y divide-slate-100">
                        <tr class="bg-slate-50/50">
                            <td class="px-4 py-2.5 text-slate-600 font-medium">Fondo Inicial en Efectivo</td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-slate-900">${{ number_format($shift->initial_cash, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2.5 text-slate-600">Ventas en Efectivo (+)</td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-emerald-700">+${{ number_format($shift->cashSalesTotal(), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2.5 text-slate-600">Entradas autorizadas de Efectivo (+)</td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-emerald-700">+${{ number_format($shift->cashInTotal(), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2.5 text-slate-600">Salidas / Retiros de Efectivo (-)</td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-rose-700">-${{ number_format($shift->cashOutTotal(), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2.5 text-slate-600">Devoluciones pagadas en Efectivo (-)</td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-rose-700">-${{ number_format($shift->cashReturnsTotal(), 2) }}</td>
                        </tr>
                        <tr class="bg-slate-50 font-bold border-t-2 border-slate-200">
                            <td class="px-4 py-3 text-slate-900 uppercase">Efectivo Teórico Esperado</td>
                            <td class="px-4 py-3 text-right font-mono text-base text-slate-900">${{ number_format($shift->expected_cash ?? $shift->calculateExpectedCash(), 2) }}</td>
                        </tr>
                        <tr class="bg-emerald-50/60 font-bold">
                            <td class="px-4 py-3 text-emerald-900 uppercase">Efectivo Físico Arqueado (Contado)</td>
                            <td class="px-4 py-3 text-right font-mono text-base text-emerald-900">
                                ${{ number_format($shift->closing_cash ?? 0, 2) }}
                            </td>
                        </tr>
                        <tr class="font-bold {{ ($shift->difference > 0) ? 'bg-teal-50 text-teal-900' : (($shift->difference < 0) ? 'bg-rose-50 text-rose-900' : 'bg-slate-50 text-slate-700') }}">
                            <td class="px-4 py-3 uppercase">Diferencia de Arqueo (Contado - Esperado)</td>
                            <td class="px-4 py-3 text-right font-mono text-base">
                                @if($shift->difference > 0)
                                    +${{ number_format($shift->difference, 2) }} (Sobrante)
                                @elseif($shift->difference < 0)
                                    -${{ number_format(abs($shift->difference), 2) }} (Faltante)
                                @else
                                    $0.00 (Exacto)
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
                <span class="text-slate-500 font-medium">Ventas con Tarjeta (Electrónico):</span>
                <strong class="font-mono text-slate-800 block text-sm">${{ number_format($shift->cardSalesTotal(), 2) }}</strong>
            </div>
            <div>
                <span class="text-slate-500 font-medium">Ventas por Transferencia:</span>
                <strong class="font-mono text-slate-800 block text-sm">${{ number_format($shift->transferSalesTotal(), 2) }}</strong>
            </div>
        </div>

        @if($shift->notes)
            <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-200/80 text-xs">
                <span class="text-[10px] font-bold uppercase text-amber-800 block">Observaciones y Justificación de Arqueo:</span>
                <p class="text-slate-700 mt-1">{{ $shift->notes }}</p>
            </div>
        @endif

        <!-- Footer Firma -->
        <div class="pt-8 border-t border-slate-200 grid grid-cols-2 gap-8 text-center text-xs">
            <div>
                <div class="border-b border-slate-300 pb-1 mb-1 font-semibold text-slate-700">{{ $shift->user->name ?? 'Cajero' }}</div>
                <span class="text-[10px] text-slate-400 uppercase">Firma del Cajero</span>
            </div>
            <div>
                <div class="border-b border-slate-300 pb-1 mb-1 font-semibold text-slate-700">{{ $shift->closedByUser->name ?? 'Supervisor / Administrador' }}</div>
                <span class="text-[10px] text-slate-400 uppercase">Firma de Conformidad Arqueo</span>
            </div>
        </div>

        <div class="text-center text-[10px] text-slate-400 italic pt-2">
            Comprobante de arqueo y control interno emitido por la plataforma PuntoÁgil.
        </div>

    </div>

</div>
@endsection
