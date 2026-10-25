@extends('layouts.app')

@section('title', $product->name . ' - PuntoÁgil')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{ adjustModalOpen: false }">

    <!-- Top Navigation & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <a href="{{ route('inventory.index') }}" 
           class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="bi bi-arrow-left text-sm"></i>
            <span>Volver al Catálogo</span>
        </a>

        @can('update', $product)
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="adjustModalOpen = true"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-all shadow-sm">
                <i class="bi bi-arrow-left-right text-emerald-600"></i>
                <span>Ajustar Existencias</span>
            </button>

            <form action="{{ route('inventory.toggle-status', $product->id) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <button type="submit" 
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold {{ $product->isActive() ? 'text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200' }} transition-all shadow-sm">
                    <i class="bi {{ $product->isActive() ? 'bi-slash-circle' : 'bi-check-circle' }}"></i>
                    <span>{{ $product->isActive() ? 'Desactivar Producto' : 'Reactivar Producto' }}</span>
                </button>
            </form>

            <a href="{{ route('inventory.edit', $product->id) }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 transition-all shadow-sm">
                <i class="bi bi-pencil"></i>
                <span>Editar Ficha</span>
            </a>
        </div>
        @endcan
    </div>

    <!-- Product Technical Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
            
            <!-- Left: Info -->
            <div class="md:col-span-7 space-y-6">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="inline-block px-2.5 py-1 rounded-lg bg-slate-100 font-mono text-xs font-bold text-slate-700 border border-slate-200">
                            SKU: {{ $product->sku }}
                        </span>
                        @if($product->barcode)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-50 font-mono text-xs text-slate-600 border border-slate-200">
                            <i class="bi bi-upc-scan text-slate-400"></i> {{ $product->barcode }}
                        </span>
                        @endif
                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                            {{ $product->category }}
                        </span>
                        @if($product->isActive())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span> Activo
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1"></span> Desactivado
                            </span>
                        @endif
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $product->name }}</h1>
                </div>

                <div class="space-y-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Descripción Técnica & Almacenamiento</h3>
                    <p class="text-xs text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        {{ $product->description ?: 'No se especificaron notas o detalles técnicos adicionales para este producto.' }}
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-4 pt-4 border-t border-slate-100 text-xs">
                    <div>
                        <span class="text-slate-400 block font-medium">Unidad de Medida</span>
                        <span class="font-semibold text-slate-800 mt-0.5 block">{{ $product->unit }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Stock Mínimo Alerta</span>
                        <span class="font-mono font-semibold text-slate-800 mt-0.5 block">{{ $product->min_stock }} {{ $product->unit }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Última Actualización</span>
                        <span class="font-mono text-slate-700 mt-0.5 block">{{ $product->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Right: Stock & Pricing Box -->
            <div class="md:col-span-5">
                <div class="rounded-2xl p-6 bg-slate-50 border border-slate-200/80 space-y-5">
                    
                    <!-- Precios y Márgenes -->
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Precio de Venta</span>
                        <div class="text-3xl font-extrabold font-mono text-slate-900 mt-1">
                            ${{ number_format((float) $product->price, 2) }}
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2 mt-2 pt-2 border-t border-slate-200/60 text-xs">
                            <div>
                                <span class="text-slate-400 text-[11px] block">Costo de Compra:</span>
                                <span class="font-mono text-slate-700 font-bold">${{ number_format((float) $product->cost, 2) }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[11px] block">Margen Bruto Estimado:</span>
                                <span class="font-mono font-bold {{ ($product->grossMarginPercentage() ?? 0) > 20 ? 'text-emerald-600' : 'text-amber-600' }}">
                                    {{ $product->grossMarginPercentage() !== null ? $product->grossMarginPercentage() . '%' : 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Existencias Físicas -->
                    <div class="pt-4 border-t border-slate-200/80">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Existencias Actuales</span>
                        <div class="text-2xl font-bold font-mono text-slate-800 mt-1">
                            {{ $product->stock }} <span class="text-sm font-normal text-slate-500 font-sans">{{ $product->unit }}</span>
                        </div>
                    </div>

                    <!-- Estado Operativo -->
                    <div class="pt-4 border-t border-slate-200/80">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block mb-2">Estado de Existencias</span>
                        @if($product->isOutOfStock())
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                Agotado (Sin Stock Disponible)
                            </span>
                        @elseif($product->isCriticalStock())
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                Stock Crítico (&le;{{ $product->min_stock }} {{ $product->unit }})
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Stock Óptimo en Bodega
                            </span>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Inventory Movements History (Trazabilidad) -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Historial de Movimientos y Auditoría de Stock</h3>
                <p class="text-xs text-slate-500 mt-0.5">Registro inmutable de entradas, salidas, ventas y ajustes físicos para este producto.</p>
            </div>
            <span class="pa-badge-slate font-mono text-xs">
                {{ $product->movements->count() }} transacciones
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                        <th class="py-3 px-6">Fecha / Hora</th>
                        <th class="py-3 px-4">Tipo Movimiento</th>
                        <th class="py-3 px-4 text-center">Variación</th>
                        <th class="py-3 px-4 text-center">Previo &rarr; Nuevo</th>
                        <th class="py-3 px-6">Motivo / Justificación</th>
                        <th class="py-3 px-6">Usuario Responsable</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($product->movements as $mov)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-3 px-6 font-mono text-slate-500">
                            {{ $mov->created_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $mov->typeBadgeClass() }}">
                                {{ $mov->typeLabel() }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center font-mono font-bold {{ $mov->quantity >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $mov->quantity > 0 ? '+' . $mov->quantity : $mov->quantity }} {{ $product->unit }}
                        </td>
                        <td class="py-3 px-4 text-center font-mono text-slate-600">
                            {{ $mov->previous_stock }} &rarr; <strong class="text-slate-900">{{ $mov->new_stock }}</strong>
                        </td>
                        <td class="py-3 px-6 text-slate-700">
                            {{ $mov->reason }}
                        </td>
                        <td class="py-3 px-6 text-slate-600 font-medium">
                            <i class="bi bi-person text-slate-400 mr-1"></i>
                            {{ $mov->user->name ?? 'Sistema' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            <i class="bi bi-clock-history text-2xl mb-1 opacity-40 block"></i>
                            No hay movimientos registrados para este producto todavía.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Ajuste de Stock -->
    <div x-show="adjustModalOpen" x-cloak class="pa-modal-backdrop" @keydown.escape.window="adjustModalOpen = false">
        <div class="pa-modal max-w-md" @click.away="adjustModalOpen = false">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                            <i class="bi bi-arrow-left-right"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Ajuste de Existencias Físicas</h3>
                            <p class="text-[11px] text-slate-500">{{ $product->name }}</p>
                        </div>
                    </div>
                    <button type="button" @click="adjustModalOpen = false" class="text-slate-400 hover:text-slate-700">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="mb-4 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs flex justify-between items-center">
                    <span class="text-slate-500">Stock Actual en Bodega:</span>
                    <span class="font-bold font-mono text-slate-900 text-sm">{{ $product->stock }} {{ $product->unit }}</span>
                </div>

                <form action="{{ route('inventory.adjust', $product->id) }}" method="POST" class="space-y-4 text-xs" x-data="{ adjustType: 'in' }">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tipo de Movimiento <span class="text-rose-500">*</span></label>
                        <select name="type" x-model="adjustType" required class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white">
                            <option value="in">Entrada / Recepción de Mercadería (+)</option>
                            <option value="out">Salida / Merma / Daño (-)</option>
                            <option value="adjustment">Conteo Físico / Corrección de Inventario (=)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1" x-text="adjustType === 'adjustment' ? 'Nuevo Stock Real Físico' : 'Cantidad a Mover'"></label>
                        <input type="number" name="amount" min="0" required
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 font-mono text-sm"
                               placeholder="Ej. 10">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Motivo / Justificación Obligatoria <span class="text-rose-500">*</span></label>
                        <input type="text" name="reason" required minlength="3"
                               class="w-full px-3 py-2 rounded-xl border border-slate-200"
                               placeholder="Ej. Conteo físico trimestral, factura proveedor #123, merma...">
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="adjustModalOpen = false" class="pa-btn-secondary py-2 px-3.5 text-xs">Cancelar</button>
                        <button type="submit" class="pa-btn-primary py-2 px-4 text-xs">
                            <i class="bi bi-check2"></i> Confirmar Ajuste
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
