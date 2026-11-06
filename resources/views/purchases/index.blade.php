@extends('layouts.app')

@section('title', 'Registro de Compras y Abastecimiento - PuntoÁgil')

@section('content')
<div class="space-y-6" x-data="{ receiveModalOpen: false, receiveActionUrl: '', purchaseNumber: '', confirmReceive(url, number) { this.receiveActionUrl = url; this.purchaseNumber = number; this.receiveModalOpen = true; } }">

    <!-- Top Header & Actions -->
    <div class="animate-fade-up flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <span>Operaciones</span>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <a href="{{ route('suppliers.index') }}" class="hover:text-emerald-600 transition-colors">Proveedores</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-emerald-600 font-semibold">Compras y Abastecimiento</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Ingreso de Mercadería y Compras</h1>
            <p class="text-xs text-slate-500 mt-0.5">Control de órdenes de compra, descuentos, costos unitarios y recepción controlada de stock en bodega.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('suppliers.index') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 transition-all shadow-sm">
                <i class="bi bi-truck text-emerald-600"></i>
                <span>Ver Proveedores</span>
            </a>

            @can('create', \App\Models\Purchase::class)
            <a href="{{ route('purchases.create') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-500/20 transition-all active:scale-95">
                <i class="bi bi-cart-plus-fill text-sm"></i>
                <span>Registrar Nueva Compra</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- KPI Summary Cards (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Total Compras</span>
                <div class="text-2xl font-extrabold font-mono text-slate-900 mt-0.5">
                    {{ number_format($kpis['total_purchases']) }}
                </div>
                <span class="text-[11px] text-slate-500 mt-0.5 block">Órdenes registradas</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 text-lg">
                <i class="bi bi-receipt-cutoff"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-500 block">Por Recepcionar</span>
                <div class="text-2xl font-extrabold font-mono text-amber-600 mt-0.5">
                    {{ number_format($kpis['pending_purchases']) }}
                </div>
                <span class="text-[11px] text-slate-500 mt-0.5 block">Sin ingreso a stock aún</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 text-lg">
                <i class="bi bi-hourglass-split"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-600 block">Recibidas en Bodega</span>
                <div class="text-2xl font-extrabold font-mono text-emerald-700 mt-0.5">
                    {{ number_format($kpis['received_purchases']) }}
                </div>
                <span class="text-[11px] text-slate-500 mt-0.5 block">Mercadería en existencias</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 text-lg">
                <i class="bi bi-box2-check"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Inversión Recibida</span>
                <div class="text-2xl font-extrabold font-mono text-slate-900 mt-0.5">
                    C$ {{ number_format($kpis['total_amount'], 2) }}
                </div>
                <span class="text-[11px] text-slate-500 mt-0.5 block">Monto en compras recibidas</span>
            </div>
            <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 text-lg">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
        <form method="GET" action="{{ route('purchases.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            
            <div class="sm:col-span-3">
                <label for="search" class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">
                    Buscar Compra o Proveedor
                </label>
                <div class="relative">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           name="search" 
                           id="search"
                           value="{{ request('search') }}" 
                           placeholder="N° Compra, Factura, RUC..." 
                           class="w-full pl-8 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                </div>
            </div>

            <div class="sm:col-span-3">
                <label for="supplier_id" class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">
                    Proveedor
                </label>
                <select name="supplier_id" id="supplier_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all bg-white">
                    <option value="">Todos los Proveedores</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }} ({{ $supplier->tax_id }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label for="status" class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">
                    Estado Recepción
                </label>
                <select name="status" id="status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all bg-white">
                    <option value="">Todos los Estados</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendiente de Recepción</option>
                    <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Recibida en Bodega</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                </select>
            </div>

            <div class="sm:col-span-3 grid grid-cols-2 gap-2">
                <div>
                    <label for="date_from" class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">Desde</label>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="w-full px-2.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all bg-white">
                </div>
                <div>
                    <label for="date_to" class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">Hasta</label>
                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="w-full px-2.5 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all bg-white">
                </div>
            </div>

            <div class="sm:col-span-1 flex items-center gap-1.5">
                <button type="submit" 
                        title="Aplicar filtros"
                        class="w-full py-2 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition-colors flex items-center justify-center">
                    <i class="bi bi-funnel"></i>
                </button>
                @if(request()->anyFilled(['search', 'supplier_id', 'status', 'date_from', 'date_to']))
                <a href="{{ route('purchases.index') }}" 
                   title="Limpiar filtros"
                   class="py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-xs transition-colors flex items-center justify-center">
                    <i class="bi bi-x-lg"></i>
                </a>
                @endif
            </div>

        </form>
    </div>

    <!-- Purchases Table -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/75 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-5">N° Compra</th>
                        <th class="py-3.5 px-5">Proveedor</th>
                        <th class="py-3.5 px-5">Factura Proveedor</th>
                        <th class="py-3.5 px-5">Fecha Emisión</th>
                        <th class="py-3.5 px-5 text-right">Subtotal</th>
                        <th class="py-3.5 px-5 text-right">Descto.</th>
                        <th class="py-3.5 px-5 text-right">Total</th>
                        <th class="py-3.5 px-5 text-center">Estado Recepción</th>
                        <th class="py-3.5 px-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($purchases as $purchase)
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="py-3.5 px-5 font-mono font-bold text-slate-900">
                            {{ $purchase->purchase_number }}
                        </td>
                        <td class="py-3.5 px-5">
                            @if($purchase->supplier)
                                <a href="{{ route('suppliers.show', $purchase->supplier->id) }}" class="font-semibold text-slate-900 hover:text-emerald-600 transition-colors">
                                    {{ $purchase->supplier->name }}
                                </a>
                                <div class="font-mono text-[11px] text-slate-400">RUC/NIT: {{ $purchase->supplier->tax_id }}</div>
                            @else
                                <span class="text-slate-400">Proveedor no disponible</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-5 font-mono text-slate-600">
                            {{ $purchase->supplier_invoice_number ?: 'S/F' }}
                        </td>
                        <td class="py-3.5 px-5 text-slate-600">
                            {{ $purchase->purchase_date ? $purchase->purchase_date->format('d/m/Y') : '—' }}
                        </td>
                        <td class="py-3.5 px-5 text-right font-mono text-slate-500">C$ {{ number_format($purchase->subtotal, 2) }}</td>
                        <td class="py-3.5 px-5 text-right font-mono text-rose-500">
                            {{ $purchase->discount > 0 ? '-C$ ' . number_format($purchase->discount, 2) : '—' }}
                        </td>
                        <td class="py-3.5 px-5 text-right font-mono font-bold text-slate-900">C$ {{ number_format($purchase->total, 2) }}</td>
                        <td class="py-3.5 px-5 text-center">
                            @if($purchase->isReceived())
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Recibida en Bodega
                                </span>
                            @elseif($purchase->isPending())
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    Pendiente Recepción
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                    Cancelada
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-5 text-right space-x-1">
                            @can('receive', $purchase)
                                @if($purchase->canBeReceived())
                                <button type="button" 
                                        @click="confirmReceive('{{ route('purchases.receive', $purchase->id) }}', '{{ $purchase->purchase_number }}')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-colors shadow-2xs"
                                        title="Recibir mercadería e ingresar a inventario">
                                    <i class="bi bi-box-arrow-in-down"></i>
                                    <span>Recibir</span>
                                </button>
                                @endif
                            @endcan

                            <a href="{{ route('purchases.show', $purchase->id) }}" 
                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200/80 transition-colors">
                                <i class="bi bi-eye"></i>
                                <span>Ver</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-slate-400">
                            <i class="bi bi-cart-x text-3xl block mb-2 opacity-50"></i>
                            <p class="font-medium">No se encontraron compras registradas</p>
                            <p class="text-xs text-slate-400 mt-1">Registra órdenes de compra y recibe mercancía controlando tu inventario.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchases->hasPages())
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            {{ $purchases->links() }}
        </div>
        @endif
    </div>

    <!-- Modal de Confirmación de Recepción -->
    <div x-show="receiveModalOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-xs" 
         x-cloak>
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-xl border border-slate-100"
             @click.away="receiveModalOpen = false">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl mx-auto">
                <i class="bi bi-box2-check"></i>
            </div>
            <div class="text-center space-y-1">
                <h3 class="text-base font-bold text-slate-900">¿Confirmar Recepción de Mercancía?</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Vas a recibir la compra <span class="font-mono font-bold text-slate-800" x-text="purchaseNumber"></span>. Esta acción incrementará el stock de inventario con las cantidades indicadas y registrará los movimientos de Kardex.
                </p>
            </div>
            <form :action="receiveActionUrl" method="POST" class="pt-2 flex items-center justify-end gap-2">
                @csrf
                @method('PATCH')
                <button type="button" 
                        @click="receiveModalOpen = false" 
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-500/20 transition-all">
                    Confirmar e Ingresar a Stock
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
