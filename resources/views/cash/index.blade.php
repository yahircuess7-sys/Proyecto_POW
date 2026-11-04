@extends('layouts.app')

@section('title', 'Caja y Turnos - PuntoÁgil')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="animate-fade-up flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                <span>Caja y Turnos</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-500/15 text-emerald-700 font-mono">M11</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Control de aperturas, movimientos de efectivo, arqueos y cierres de turno.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('cash.current') }}" class="btn-shine inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-500 text-slate-950 hover:bg-emerald-400 shadow-sm transition-all">
                <i class="bi bi-wallet2 text-sm"></i>
                <span>Mi Turno Activo</span>
            </a>
            <a href="{{ route('pos.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 shadow-subtle transition-all">
                <i class="bi bi-cart4 text-sm text-emerald-600"></i>
                <span>Terminal POS</span>
            </a>
        </div>
    </div>

    <!-- Alert / Active Shift Summary Card -->
    @if($currentShift)
        <div class="animate-fade-up p-4 rounded-2xl bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-subtle">
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-emerald-500 text-slate-950 flex items-center justify-center font-bold text-lg shadow-sm">
                    <i class="bi bi-unlock-fill"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-800">Turno en curso #{{ $currentShift->id }}</span>
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    </div>
                    <p class="text-sm font-bold text-slate-900 mt-0.5">
                        {{ $currentShift->cashRegister->name ?? 'Caja' }} &bull; Abierto por {{ $currentShift->user->name ?? 'Cajero' }} el {{ $currentShift->opened_at->format('d/m/Y H:i') }}
                    </p>
                    <p class="text-xs text-slate-600 font-mono">
                        Fondo Inicial: <strong>${{ number_format($currentShift->initial_cash, 2) }}</strong> | Efectivo Esperado: <strong>${{ number_format($currentShift->calculateExpectedCash(), 2) }}</strong>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('cash.current') }}" class="px-3.5 py-2 rounded-xl bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition-colors shadow-sm">
                    Operar / Arqueo
                </a>
            </div>
        </div>
    @else
        <div class="animate-fade-up p-4 rounded-2xl bg-amber-50 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-subtle">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500 text-slate-950 flex items-center justify-center font-bold text-lg">
                    <i class="bi bi-lock-fill"></i>
                </div>
                <div>
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-amber-800">Sin turno abierto actualmente</h3>
                    <p class="text-xs text-amber-900 mt-0.5">Para asentar ventas en efectivo y controlar diferencias debes abrir un turno.</p>
                </div>
            </div>
            <a href="{{ route('cash.current') }}" class="px-3.5 py-2 rounded-xl bg-amber-600 text-white font-bold text-xs hover:bg-amber-700 transition-colors">
                Abrir Nuevo Turno
            </a>
        </div>
    @endif

    <!-- Filtros de Historial de Turnos -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-subtle">
        <form method="GET" action="{{ route('cash.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end text-xs">
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Caja Registradora</label>
                <select name="register_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <option value="">Todas las cajas</option>
                    @foreach($allRegisters as $reg)
                        <option value="{{ $reg->id }}" {{ request('register_id') == $reg->id ? 'selected' : '' }}>
                            {{ $reg->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Estado de Turno</label>
                <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <option value="">Todos los estados</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Abierto (En curso)</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Cerrado (Arqueado)</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Desde</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Hasta</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="w-full py-2 px-3 rounded-xl bg-slate-900 text-white font-semibold hover:bg-slate-800 transition-colors">
                    <i class="bi bi-funnel me-1"></i> Filtrar
                </button>
                @if(request()->anyFilled(['register_id', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('cash.index') }}" class="p-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-100" title="Limpiar">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabla de Turnos Históricos -->
    <div class="rounded-2xl bg-white border border-slate-200/80 shadow-subtle overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Historial de Turnos y Arqueos</h2>
            <span class="text-xs text-slate-500 font-mono">{{ $shifts->total() }} registros</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Turno / Caja</th>
                        <th class="px-4 py-3">Apertura</th>
                        <th class="px-4 py-3">Cajero Apertura</th>
                        <th class="px-4 py-3 text-right">Fondo Inicial</th>
                        <th class="px-4 py-3 text-right">Esperado</th>
                        <th class="px-4 py-3 text-right">Arqueado</th>
                        <th class="px-4 py-3 text-right">Diferencia</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($shifts as $shift)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3 font-semibold text-slate-900">
                                <div>#{{ $shift->id }} &bull; {{ $shift->cashRegister->name ?? 'Caja' }}</div>
                            </td>
                            <td class="px-4 py-3 font-mono text-[11px]">
                                {{ $shift->opened_at->format('d/m/Y H:i') }}
                                @if($shift->closed_at)
                                    <div class="text-[10px] text-slate-400">Cierre: {{ $shift->closed_at->format('d/m/Y H:i') }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div>{{ $shift->user->name ?? 'Cajero' }}</div>
                                @if($shift->closedByUser && $shift->closed_by_user_id !== $shift->user_id)
                                    <div class="text-[10px] text-slate-400">Cerró: {{ $shift->closedByUser->name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-medium text-slate-800">
                                ${{ number_format($shift->initial_cash, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-medium text-slate-800">
                                ${{ number_format($shift->expected_cash ?? $shift->calculateExpectedCash(), 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-medium text-slate-800">
                                @if($shift->closing_cash !== null)
                                    ${{ number_format($shift->closing_cash, 2) }}
                                @else
                                    <span class="text-slate-400 italic">En curso</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold">
                                @if($shift->difference !== null)
                                    @if($shift->difference > 0)
                                        <span class="text-emerald-600">+${{ number_format($shift->difference, 2) }} (Sobrante)</span>
                                    @elseif($shift->difference < 0)
                                        <span class="text-rose-600">-${{ number_format(abs($shift->difference), 2) }} (Faltante)</span>
                                    @else
                                        <span class="text-slate-500">$0.00 (Exacto)</span>
                                    @endif
                                @else
                                    <span class="text-slate-400">---</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($shift->isOpen())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span> Abierto
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                        Cerrado
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('cash.show', $shift) }}" class="inline-flex items-center justify-center p-1.5 rounded-lg text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 transition-colors" title="Ver Detalle y Arqueo">
                                    <i class="bi bi-eye text-sm"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-slate-400">
                                <i class="bi bi-inbox text-2xl"></i>
                                <p class="text-xs mt-1">No se encontraron turnos registrados en este período.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($shifts->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $shifts->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
