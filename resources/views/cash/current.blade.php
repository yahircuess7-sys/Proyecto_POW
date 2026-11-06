@extends('layouts.app')

@section('title', 'Control de Caja y Turno - PuntoÁgil')

@section('content')
@php
    $canPeekExpected = auth()->check() && (auth()->user()->isOwner() || auth()->user()->isAdmin() || auth()->user()->isSuperAdmin());
    $reopenCloseModal = $errors->has('notes') || $errors->has('closing_cash');
@endphp
<div class="space-y-6" x-data="{
    openMovementModal: false,
    movementType: 'in',
    openCloseShiftModal: {{ $reopenCloseModal ? 'true' : 'false' }},
    peekExpected: false,
    showBillHelper: false,
    countEvaluated: {{ $reopenCloseModal ? 'true' : 'false' }},
    enteredClosingCash: '{{ old('closing_cash', '') }}',
    expectedCash: {{ $metrics['expected_cash'] ?? 0 }},
    bills: { b1000: 0, b500: 0, b200: 0, b100: 0, b50: 0, b20: 0, b10: 0, m5: 0, m1: 0 },
    recalcBills() {
        const sum = (Number(this.bills.b1000) || 0) * 1000
                  + (Number(this.bills.b500) || 0) * 500
                  + (Number(this.bills.b200) || 0) * 200
                  + (Number(this.bills.b100) || 0) * 100
                  + (Number(this.bills.b50) || 0) * 50
                  + (Number(this.bills.b20) || 0) * 20
                  + (Number(this.bills.b10) || 0) * 10
                  + (Number(this.bills.m5) || 0) * 5
                  + (Number(this.bills.m1) || 0) * 1;
        this.enteredClosingCash = sum.toFixed(2);
        this.countEvaluated = false;
    },
    diffAmount() {
        const entered = parseFloat(this.enteredClosingCash);
        if (isNaN(entered)) return 0;
        return Math.round((entered - this.expectedCash) * 100) / 100;
    },
    formatCordobas(val) {
        return 'C$ ' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}">

    <!-- Header -->
    <div class="animate-fade-up flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                <span>Gestión de Caja y Turno</span>
                @if($shift && $shift->isOpen())
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span> Turno Activo #{{ $shift->id }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                        Sin turno en curso
                    </span>
                @endif
            </h1>
            <p class="text-xs text-slate-500 mt-1">Apertura de caja en Córdobas (C$), control de movimientos y Cierre de Caja Ciego (Arqueo Real).</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('pos.create') }}" class="btn-shine inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-500 text-slate-950 hover:bg-emerald-400 shadow-sm transition-all">
                <i class="bi bi-cart4 text-sm"></i>
                <span>Ir al POS</span>
            </a>
            <a href="{{ route('cash.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-all">
                <i class="bi bi-clock-history text-sm text-slate-500"></i>
                <span>Historial de Cortes</span>
            </a>
        </div>
    </div>

    @if(!$shift || !$shift->isOpen())
        <!-- Formulario de Apertura de Turno -->
        <div class="animate-fade-up max-w-xl mx-auto rounded-3xl bg-white border border-slate-200/80 shadow-card p-6 space-y-5">
            <div class="flex items-center gap-3.5 border-b border-slate-100 pb-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/15 text-emerald-600 flex items-center justify-center text-2xl shadow-sm">
                    <i class="bi bi-key-fill"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Apertura de Turno de Caja</h2>
                    <p class="text-xs text-slate-500">Ingresa el fondo inicial de sencillo en Córdobas para comenzar a cobrar en el POS.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('cash.open') }}" class="space-y-4 text-xs">
                @csrf

                <div>
                    <label for="cash_register_id" class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Caja Registradora</label>
                    <select id="cash_register_id" name="cash_register_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        @foreach($registers as $reg)
                            <option value="{{ $reg->id }}">{{ $reg->name }} ({{ $reg->isOpen() ? 'En uso' : 'Disponible' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="initial_cash" class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Fondo Inicial en Efectivo (C$)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 font-mono font-bold">C$</span>
                        <input id="initial_cash" type="number" name="initial_cash" step="0.01" min="0" required value="{{ old('initial_cash', '500.00') }}"
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white font-mono text-sm font-bold text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                               placeholder="0.00">
                    </div>
                    <span class="text-[10px] text-slate-400 mt-1 block">Monto en monedas y billetes entregado al cajero para dar vuelto.</span>
                </div>

                <div>
                    <label for="open_notes" class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Observaciones / Notas (Opcional)</label>
                    <textarea id="open_notes" name="notes" rows="2" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                              placeholder="Ej. Billetes de C$ 10, C$ 20 y C$ 100 para cambio en mostrador...">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="btn-shine w-full py-3 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm shadow-md transition-all">
                    <i class="bi bi-unlock-fill me-1"></i> ABRIR TURNO DE CAJA
                </button>
            </form>
        </div>

    @else
        <!-- Panel Operativo del Turno en Curso -->
        <div class="animate-fade-up grid grid-cols-1 md:grid-cols-4 gap-4">

            <!-- Tarjeta 1: Gaveta en Modo Arqueo Ciego -->
            <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-950 text-white shadow-card flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-extrabold font-mono text-emerald-400 uppercase tracking-wider">Gaveta de Efectivo</span>
                        @if($canPeekExpected)
                            <button type="button" @click="peekExpected = !peekExpected"
                                    class="text-[10px] text-slate-400 hover:text-white flex items-center gap-1"
                                    title="Mostrar/ocultar saldo teórico (Solo Admin)">
                                <i class="bi" :class="peekExpected ? 'bi-eye-slash' : 'bi-eye'"></i>
                                <span x-text="peekExpected ? 'Ocultar' : 'Admin'"></span>
                            </button>
                        @endif
                    </div>

                    <template x-if="!peekExpected">
                        <div class="mt-2">
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/10 text-emerald-300 text-xs font-bold">
                                <i class="bi bi-shield-lock-fill"></i>
                                <span>Arqueo Ciego Activo</span>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1.5 leading-snug">
                                El saldo esperado se comparará automáticamente al ingresar tu conteo físico de cierre.
                            </p>
                        </div>
                    </template>

                    @if($canPeekExpected)
                    <template x-if="peekExpected">
                        <div class="mt-1">
                            <h3 class="text-xs text-slate-300">Efectivo Esperado (Vista Admin)</h3>
                            <p class="text-2xl font-black font-mono text-white mt-1">C$ {{ number_format($metrics['expected_cash'], 2) }}</p>
                        </div>
                    </template>
                    @endif
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Fondo Inicial:</span>
                    <strong class="font-mono text-slate-200">C$ {{ number_format($metrics['initial_cash'], 2) }}</strong>
                </div>
            </div>

            <!-- Tarjeta 2: Canales Electrónicos y Operaciones -->
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-subtle flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Cobros No-Efectivo</span>
                    <div class="mt-2 space-y-1.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 flex items-center gap-1"><i class="bi bi-credit-card text-blue-600"></i> Tarjeta:</span>
                            <span class="font-mono font-bold text-slate-900">C$ {{ number_format($metrics['card_sales'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 flex items-center gap-1"><i class="bi bi-arrow-left-right text-indigo-600"></i> Transferencia:</span>
                            <span class="font-mono font-bold text-slate-900">C$ {{ number_format($metrics['transfer_sales'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 flex items-center gap-1"><i class="bi bi-receipt text-emerald-600"></i> Ventas Turno:</span>
                            <span class="font-mono font-bold text-slate-900">{{ $shift->sales()->count() }} tickets</span>
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                    <span class="text-slate-500 font-semibold">Apertura:</span>
                    <span class="font-mono font-bold text-slate-700">{{ $shift->opened_at->format('H:i') }}</span>
                </div>
            </div>

            <!-- Tarjeta 3: Movimientos Manuales -->
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-subtle flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Entradas / Salidas</span>
                    <div class="mt-2 space-y-1 text-xs">
                        <div class="flex items-center justify-between text-emerald-700">
                            <span>(+) Entradas Efectivo:</span>
                            <span class="font-mono font-bold">C$ {{ number_format($metrics['cash_in'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-rose-700">
                            <span>(-) Salidas / Retiros:</span>
                            <span class="font-mono font-bold">C$ {{ number_format($metrics['cash_out'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-amber-700">
                            <span>(-) Devoluciones Efectivo:</span>
                            <span class="font-mono font-bold">C$ {{ number_format($metrics['cash_returns'], 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-slate-100">
                    <button type="button" @click="openMovementModal = true" class="w-full py-1.5 px-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors flex items-center justify-center gap-1.5">
                        <i class="bi bi-plus-slash-minus text-sm"></i> Registrar Entrada / Salida
                    </button>
                </div>
            </div>

            <!-- Tarjeta 4: Cierre de Caja Ciego -->
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 shadow-subtle flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-800 block">Fin de Jornada</span>
                    <h3 class="text-xs font-bold text-slate-900 mt-1">Cierre de Caja Ciego (Arqueo)</h3>
                    <p class="text-[11px] text-slate-600 mt-1">Cuenta físicamente los billetes y monedas en gaveta; el sistema verificará si cuadra, sobra o falta.</p>
                </div>
                <div class="mt-3 pt-2">
                    <button type="button" @click="openCloseShiftModal = true" class="w-full py-2.5 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-colors shadow-sm flex items-center justify-center gap-2">
                        <i class="bi bi-safe2 text-sm text-emerald-400"></i> Realizar Corte Ciego
                    </button>
                </div>
            </div>

        </div>

        <!-- Tablas: Movimientos de Turno y Ventas Recientes -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <!-- Movimientos de Efectivo -->
            <div class="rounded-2xl bg-white border border-slate-200/80 shadow-subtle p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                        <i class="bi bi-arrow-left-right text-emerald-600"></i> Movimientos de Efectivo
                    </h3>
                    <button type="button" @click="openMovementModal = true" class="text-xs text-emerald-700 hover:underline font-semibold">
                        + nuevo movimiento
                    </button>
                </div>

                <div class="overflow-y-auto max-h-64 space-y-2">
                    @forelse($movements as $m)
                        <div class="flex items-center justify-between p-2.5 rounded-xl border {{ $m->type === 'in' ? 'bg-emerald-50/50 border-emerald-100' : 'bg-rose-50/50 border-rose-100' }} text-xs">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold font-mono {{ $m->type === 'in' ? 'bg-emerald-200 text-emerald-900' : 'bg-rose-200 text-rose-900' }}">
                                        {{ $m->type === 'in' ? 'INGRESO' : 'RETIRO' }}
                                    </span>
                                    <span class="font-medium text-slate-800 truncate">{{ $m->reason }}</span>
                                </div>
                                <span class="text-[10px] text-slate-400 mt-0.5 block">
                                    {{ $m->created_at->format('H:i') }} &bull; Por: {{ $m->user->name ?? 'Usuario' }}
                                </span>
                            </div>
                            <span class="font-mono font-extrabold text-xs {{ $m->type === 'in' ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $m->type === 'in' ? '+' : '-' }}C$ {{ number_format($m->amount, 2) }}
                            </span>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400 text-xs">
                            <i class="bi bi-wallet2 text-xl opacity-60"></i>
                            <p class="mt-1">Sin movimientos manuales registrados en este turno.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Ventas de este Turno -->
            <div class="rounded-2xl bg-white border border-slate-200/80 shadow-subtle p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                        <i class="bi bi-receipt text-emerald-600"></i> Últimas Ventas del Turno
                    </h3>
                    <a href="{{ route('sales.history') }}" class="text-xs text-emerald-700 hover:underline font-semibold">
                        Ver todas
                    </a>
                </div>

                <div class="overflow-y-auto max-h-64 space-y-2">
                    @forelse($recentSales as $sale)
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('sales.show', $sale) }}" class="font-bold text-slate-900 hover:text-emerald-700">
                                        {{ $sale->invoice_number }}
                                    </a>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-white border border-slate-200 text-slate-600">
                                        {{ strtoupper($sale->payment_method) }}
                                    </span>
                                </div>
                                <span class="text-[10px] text-slate-500 block mt-0.5">
                                    {{ $sale->customer_name }} &bull; {{ $sale->created_at->format('H:i') }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="font-mono font-bold text-slate-900 block">C$ {{ number_format($sale->total, 2) }}</span>
                                <span class="text-[10px] text-emerald-600 font-medium">{{ $sale->status }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400 text-xs">
                            <i class="bi bi-cart-x text-xl opacity-60"></i>
                            <p class="mt-1">Aún no se han cobrado ventas en este turno.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- MODAL 1: Registrar Entrada/Salida de Efectivo -->
        <div x-show="openMovementModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm" x-cloak @keydown.escape.window="openMovementModal = false">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-floating space-y-4" @click.away="openMovementModal = false">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Registrar Movimiento de Efectivo</h3>
                    <button type="button" @click="openMovementModal = false" class="text-slate-400 hover:text-slate-600">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('cash.movements.store', $shift) }}" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <span class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Tipo de Movimiento</span>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="cursor-pointer p-2.5 rounded-xl border border-slate-200 flex items-center gap-2" :class="movementType === 'in' ? 'border-emerald-500 bg-emerald-50 text-emerald-900 font-bold' : 'bg-white'">
                                <input type="radio" name="type" value="in" x-model="movementType" class="sr-only">
                                <i class="bi bi-arrow-down-left-circle-fill text-emerald-600"></i>
                                <span>Entrada de Dinero</span>
                            </label>
                            <label class="cursor-pointer p-2.5 rounded-xl border border-slate-200 flex items-center gap-2" :class="movementType === 'out' ? 'border-rose-500 bg-rose-50 text-rose-900 font-bold' : 'bg-white'">
                                <input type="radio" name="type" value="out" x-model="movementType" class="sr-only">
                                <i class="bi bi-arrow-up-right-circle-fill text-rose-600"></i>
                                <span>Salida / Retiro</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label for="mov_amount" class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Monto (C$)</label>
                        <input id="mov_amount" type="number" name="amount" step="0.01" min="0.01" required
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 font-mono text-sm font-bold text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                               placeholder="0.00">
                    </div>

                    <div>
                        <label for="mov_reason" class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Motivo / Justificación</label>
                        <input id="mov_reason" type="text" name="reason" required minlength="3" maxlength="255"
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                               placeholder="Ej. Pago de flete urgente, recarga de sencillo...">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="openMovementModal = false" class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white font-bold hover:bg-slate-800">
                            Guardar Movimiento
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 2: Cierre de Caja Ciego (Arqueo Real) -->
        <div x-show="openCloseShiftModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm overflow-y-auto" x-cloak @keydown.escape.window="openCloseShiftModal = false">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-floating space-y-4 my-8" @click.away="openCloseShiftModal = false">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-slate-900 text-emerald-400 flex items-center justify-center">
                            <i class="bi bi-safe2-fill text-base"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Cierre de Caja Ciego &bull; Turno #{{ $shift->id }}</h3>
                            <p class="text-[11px] text-slate-500">Cuenta físicamente el dinero en gaveta. El sistema verificará si cuadra.</p>
                        </div>
                    </div>
                    <button type="button" @click="openCloseShiftModal = false" class="text-slate-400 hover:text-slate-600">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Calculadora Rápida Opcional de Billetes de Córdoba -->
                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 overflow-hidden text-xs">
                    <button type="button" @click="showBillHelper = !showBillHelper"
                            class="w-full px-3.5 py-2.5 flex items-center justify-between font-bold text-slate-700 hover:bg-slate-100/80 transition-colors">
                        <span class="flex items-center gap-1.5">
                            <i class="bi bi-calculator text-emerald-600"></i>
                            <span>Calculadora de Billetes y Monedas (Opcional)</span>
                        </span>
                        <i class="bi text-slate-400" :class="showBillHelper ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </button>
                    <div x-show="showBillHelper" class="p-3.5 border-t border-slate-200 bg-white grid grid-cols-3 gap-2">
                        @foreach([
                            ['b1000', 'C$ 1,000'],
                            ['b500', 'C$ 500'],
                            ['b200', 'C$ 200'],
                            ['b100', 'C$ 100'],
                            ['b50', 'C$ 50'],
                            ['b20', 'C$ 20'],
                            ['b10', 'C$ 10'],
                            ['m5', 'C$ 5'],
                            ['m1', 'C$ 1'],
                        ] as [$modelKey, $billLabel])
                        <div class="flex items-center justify-between gap-1.5 p-1.5 rounded-lg bg-slate-50 border border-slate-200">
                            <span class="font-mono font-bold text-[11px] text-slate-700">{{ $billLabel }}</span>
                            <input type="number" min="0" step="1" x-model.number="bills.{{ $modelKey }}" @input="recalcBills()"
                                   class="w-12 px-1.5 py-0.5 text-center font-mono text-xs rounded border border-slate-300 bg-white">
                        </div>
                        @endforeach
                    </div>
                </div>

                <form method="POST" action="{{ route('cash.close', $shift) }}" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label for="closing_cash_input" class="block text-[11px] font-bold text-slate-700 uppercase mb-1">
                            ¿Cuánto efectivo físico contaste en gaveta? (C$) <span class="text-rose-500">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 font-mono font-bold">C$</span>
                                <input id="closing_cash_input" type="number" name="closing_cash" step="0.01" min="0" required
                                       x-model="enteredClosingCash"
                                       @input="countEvaluated = false"
                                       class="w-full pl-10 pr-3 py-2.5 rounded-xl border border-slate-300 font-mono text-base font-black text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                       placeholder="0.00">
                            </div>
                            <button type="button"
                                    @click="if (enteredClosingCash !== '') countEvaluated = true"
                                    :disabled="enteredClosingCash === ''"
                                    class="px-3.5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 disabled:opacity-40 text-white font-bold text-xs whitespace-nowrap transition-colors">
                                <i class="bi bi-check2-square me-1 text-emerald-400"></i>Verificar Arqueo
                            </button>
                        </div>
                        <span class="text-[10px] text-slate-400 mt-1 block">Incluye el fondo inicial más los cobros en efectivo en caja.</span>
                    </div>

                    <!-- Resultado del Arqueo Ciego (Visible únicamente tras ingresar y verificar el conteo físico) -->
                    <template x-if="countEvaluated && enteredClosingCash !== ''">
                        <div class="p-4 rounded-2xl border space-y-2 text-xs"
                             :class="{
                                 'bg-emerald-50 border-emerald-300 text-emerald-950': Math.abs(diffAmount()) <= 0.05,
                                 'bg-teal-50 border-teal-300 text-teal-950': diffAmount() > 0.05,
                                 'bg-rose-50 border-rose-300 text-rose-950': diffAmount() < -0.05
                             }">
                            <div class="flex items-center justify-between text-[11px] opacity-80">
                                <span>Registrado por el sistema: <strong class="font-mono" x-text="formatCordobas(expectedCash)"></strong></span>
                                <span>Contado físico: <strong class="font-mono" x-text="formatCordobas(enteredClosingCash)"></strong></span>
                            </div>

                            <div class="pt-2 border-t border-current/15 flex items-center justify-between font-bold text-sm">
                                <span x-text="Math.abs(diffAmount()) <= 0.05 ? '¡CAJA CUADRADA!' : (diffAmount() > 0.05 ? 'SOBRANTE DE DINERO:' : 'FALTANTE DE DINERO:')"></span>
                                <span class="font-mono font-black text-base"
                                      x-text="(diffAmount() > 0 ? '+' : (diffAmount() < 0 ? '-' : '')) + formatCordobas(Math.abs(diffAmount()))"></span>
                            </div>

                            <p class="text-[11px]"
                               x-text="Math.abs(diffAmount()) <= 0.05
                                   ? 'Excelente trabajo. El efectivo contado coincide con las ventas registradas.'
                                   : 'Existe diferencia entre el conteo físico y las ventas registradas. Escribe una breve justificación abajo para cerrar el turno.'"></p>
                        </div>
                    </template>

                    <div>
                        <label for="close_notes" class="block text-[11px] font-bold text-slate-700 uppercase mb-1">
                            Observaciones / Justificación de Diferencia
                            <span x-show="countEvaluated && Math.abs(diffAmount()) > 0.05" class="text-rose-600">* (Requerido por diferencia)</span>
                        </label>
                        <textarea id="close_notes" name="notes" rows="2"
                                  :required="countEvaluated && Math.abs(diffAmount()) > 0.05"
                                  class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                  placeholder="Ej. Cierre sin novedades, o motivo de sobrante/faltante en vuelto...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-[11px] text-rose-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="openCloseShiftModal = false" class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                @click="if (!countEvaluated && enteredClosingCash !== '' && Math.abs(diffAmount()) > 0.05 && !$el.form.notes.value.trim()) { $event.preventDefault(); countEvaluated = true; $nextTick(() => document.getElementById('close_notes')?.focus()); }"
                                class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-sm transition-colors">
                            <i class="bi bi-lock-fill me-1"></i> Confirmar Corte y Cerrar Caja
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
@endsection
