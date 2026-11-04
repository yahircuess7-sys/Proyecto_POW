@extends('layouts.app')

@section('title', 'Control de Caja y Turno - PuntoÁgil')

@section('content')
<div class="space-y-6" x-data="{ openMovementModal: false, movementType: 'in', openCloseShiftModal: false, enteredClosingCash: '', expectedCash: {{ $metrics['expected_cash'] ?? 0 }} }">

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
            <p class="text-xs text-slate-500 mt-1">Apertura de turno, control de efectivo en gaveta, arqueo y justificación de diferencias.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('pos.create') }}" class="btn-shine inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-500 text-slate-950 hover:bg-emerald-400 shadow-sm transition-all">
                <i class="bi bi-cart4 text-sm"></i>
                <span>Ir al POS</span>
            </a>
            <a href="{{ route('cash.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-all">
                <i class="bi bi-clock-history text-sm text-slate-500"></i>
                <span>Historial de Turnos</span>
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
                    <p class="text-xs text-slate-500">Ingresa el fondo inicial de sencillo para comenzar a cobrar en el POS.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('cash.open') }}" class="space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Caja Registradora</label>
                    <select name="cash_register_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        @foreach($registers as $reg)
                            <option value="{{ $reg->id }}">{{ $reg->name }} ({{ $reg->isOpen() ? 'En uso' : 'Disponible' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Fondo Inicial en Efectivo ($)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 font-bold">$</span>
                        <input type="number" name="initial_cash" step="0.01" min="0" required value="{{ old('initial_cash', '50.00') }}"
                               class="w-full pl-8 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white font-mono text-sm font-bold text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                               placeholder="0.00">
                    </div>
                    <span class="text-[10px] text-slate-400 mt-1 block">Monto en monedas y billetes entregado al cajero para dar cambio.</span>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Observaciones / Notas (Opcional)</label>
                    <textarea name="notes" rows="2" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                              placeholder="Ej. Billetes fraccionados para cambio en mostrador...">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="btn-shine w-full py-3 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm shadow-md transition-all">
                    <i class="bi bi-unlock-fill me-1"></i> ABRIR TURNO DE CAJA
                </button>
            </form>
        </div>

    @else
        <!-- Panel Operativo del Turno en Curso -->
        <div class="animate-fade-up grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <!-- Tarjeta 1: Efectivo Esperado en Gaveta -->
            <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-950 text-white shadow-card flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-extrabold font-mono text-emerald-400 uppercase tracking-wider block">Gaveta de Efectivo</span>
                    <h3 class="text-xs text-slate-300 mt-0.5">Efectivo Esperado (Teórico)</h3>
                    <p class="text-2xl font-black font-mono text-white mt-2">${{ number_format($metrics['expected_cash'], 2) }}</p>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Fondo Inicial:</span>
                    <strong class="font-mono text-slate-200">${{ number_format($metrics['initial_cash'], 2) }}</strong>
                </div>
            </div>

            <!-- Tarjeta 2: Ventas por Método de Pago -->
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-subtle flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Ventas de Turno</span>
                    <div class="mt-2 space-y-1 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 flex items-center gap-1"><i class="bi bi-cash-stack text-emerald-600"></i> Efectivo:</span>
                            <span class="font-mono font-bold text-slate-900">${{ number_format($metrics['cash_sales'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 flex items-center gap-1"><i class="bi bi-credit-card text-blue-600"></i> Tarjeta:</span>
                            <span class="font-mono font-bold text-slate-900">${{ number_format($metrics['card_sales'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 flex items-center gap-1"><i class="bi bi-arrow-left-right text-indigo-600"></i> Transfer:</span>
                            <span class="font-mono font-bold text-slate-900">${{ number_format($metrics['transfer_sales'], 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                    <span class="text-slate-500 font-semibold">Total Ventas:</span>
                    <span class="font-mono font-extrabold text-emerald-700">
                        ${{ number_format($metrics['cash_sales'] + $metrics['card_sales'] + $metrics['transfer_sales'], 2) }}
                    </span>
                </div>
            </div>

            <!-- Tarjeta 3: Movimientos Manuales -->
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-subtle flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Entradas / Salidas</span>
                    <div class="mt-2 space-y-1 text-xs">
                        <div class="flex items-center justify-between text-emerald-700">
                            <span>(+) Entradas Efectivo:</span>
                            <span class="font-mono font-bold">${{ number_format($metrics['cash_in'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-rose-700">
                            <span>(-) Salidas / Retiros:</span>
                            <span class="font-mono font-bold">${{ number_format($metrics['cash_out'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-amber-700">
                            <span>(-) Devoluciones Efectivo:</span>
                            <span class="font-mono font-bold">${{ number_format($metrics['cash_returns'], 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-slate-100">
                    <button type="button" @click="openMovementModal = true" class="w-full py-1.5 px-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors flex items-center justify-center gap-1.5">
                        <i class="bi bi-plus-slash-minus text-sm"></i> Registrar Movimiento
                    </button>
                </div>
            </div>

            <!-- Tarjeta 4: Control de Cierre / Arqueo -->
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 shadow-subtle flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-800 block">Acción de Cierre</span>
                    <h3 class="text-xs font-bold text-slate-900 mt-1">Cierre de Caja y Arqueo</h3>
                    <p class="text-[11px] text-slate-600 mt-1">Realiza el recuento físico de billetes y monedas para cuadrar la caja y justificar diferencias.</p>
                </div>
                <div class="mt-3 pt-2">
                    <button type="button" @click="openCloseShiftModal = true" class="w-full py-2.5 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-colors shadow-sm flex items-center justify-center gap-2">
                        <i class="bi bi-safe2 text-sm text-emerald-400"></i> Arqueo y Cerrar Turno
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
                                {{ $m->type === 'in' ? '+' : '-' }}${{ number_format($m->amount, 2) }}
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
                        <i class="bi bi-receipt text-emerald-600"></i> Ventas del Turno
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
                                <span class="font-mono font-bold text-slate-900 block">${{ number_format($sale->total, 2) }}</span>
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
        <div x-show="openMovementModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm" x-cloak>
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
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Tipo de Movimiento</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="cursor-pointer p-2.5 rounded-xl border border-slate-200 flex items-center gap-2" :class="movementType === 'in' ? 'border-emerald-500 bg-emerald-50 text-emerald-900 font-bold' : 'bg-white'">
                                <input type="radio" name="type" value="in" x-model="movementType" class="hidden">
                                <i class="bi bi-arrow-down-left-circle-fill text-emerald-600"></i>
                                <span>Entrada de Dinero</span>
                            </label>
                            <label class="cursor-pointer p-2.5 rounded-xl border border-slate-200 flex items-center gap-2" :class="movementType === 'out' ? 'border-rose-500 bg-rose-50 text-rose-900 font-bold' : 'bg-white'">
                                <input type="radio" name="type" value="out" x-model="movementType" class="hidden">
                                <i class="bi bi-arrow-up-right-circle-fill text-rose-600"></i>
                                <span>Salida / Retiro</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Monto ($)</label>
                        <input type="number" name="amount" step="0.01" min="0.01" required
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 font-mono text-sm font-bold text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                               placeholder="0.00">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Motivo / Justificación</label>
                        <input type="text" name="reason" required minlength="3" maxlength="255"
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

        <!-- MODAL 2: Arqueo y Cierre de Turno con Justificación -->
        <div x-show="openCloseShiftModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm" x-cloak>
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-floating space-y-4" @click.away="openCloseShiftModal = false">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Arqueo y Cierre de Turno #{{ $shift->id }}</h3>
                        <p class="text-[11px] text-slate-500">Recuento físico del efectivo existente en la gaveta.</p>
                    </div>
                    <button type="button" @click="openCloseShiftModal = false" class="text-slate-400 hover:text-slate-600">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5 text-xs">
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Efectivo Teórico Esperado:</span>
                        <strong class="font-mono text-slate-900">${{ number_format($metrics['expected_cash'], 2) }}</strong>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                        <span>(Fondo Inicial + Ventas Efectivo + Ingresos - Egresos - Devoluciones)</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('cash.close', $shift) }}" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Efectivo Físico Arqueado ($)</label>
                        <input type="number" name="closing_cash" step="0.01" min="0" required x-model="enteredClosingCash"
                               class="w-full px-3 py-2.5 rounded-xl border border-slate-200 font-mono text-base font-black text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                               placeholder="0.00">
                        <span class="text-[10px] text-slate-400 mt-1 block">Monto exacto de billetes y monedas contados en gaveta al finalizar.</span>
                    </div>

                    <!-- Indicador de Diferencia en Vivo -->
                    <template x-if="enteredClosingCash !== ''">
                        <div class="p-3 rounded-xl border text-xs"
                             :class="{
                                 'bg-emerald-50 border-emerald-200 text-emerald-900': (parseFloat(enteredClosingCash) - expectedCash) === 0,
                                 'bg-teal-50 border-teal-200 text-teal-900': (parseFloat(enteredClosingCash) - expectedCash) > 0,
                                 'bg-rose-50 border-rose-200 text-rose-900': (parseFloat(enteredClosingCash) - expectedCash) < 0
                             }">
                            <div class="flex items-center justify-between font-bold">
                                <span>Diferencia de Arqueo:</span>
                                <span class="font-mono font-black" x-text="((parseFloat(enteredClosingCash) - expectedCash) >= 0 ? '+' : '') + '$' + (parseFloat(enteredClosingCash) - expectedCash).toFixed(2)"></span>
                            </div>
                            <p class="text-[10px] mt-1" x-text="(parseFloat(enteredClosingCash) - expectedCash) === 0 ? '¡Caja cuadrada a la perfección!' : ((parseFloat(enteredClosingCash) - expectedCash) > 0 ? 'Sobrante detectado. Ingresa el motivo en las notas.' : 'Faltante detectado. Es obligatorio justificar la diferencia.')"></p>
                        </div>
                    </template>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">
                            Observaciones / Justificación de Cierre
                        </label>
                        <textarea name="notes" rows="3" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                  placeholder="Indica cualquier incidencia del turno o la causa de posibles diferencias de caja..."></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="openCloseShiftModal = false" class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 text-white font-bold hover:bg-rose-700">
                            Confirmar y Cerrar Turno
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
@endsection
