@extends('layouts.app')

@section('title', 'Comprobante de Compra #' . $purchase->purchase_number . ' - PuntoÁgil')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{ receiveModalOpen: false, cancelModalOpen: false }">

    <!-- Top Navigation & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:hidden">
        <div class="flex items-center gap-3">
            <a href="{{ route('purchases.index') }}" 
               class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
                <i class="bi bi-arrow-left text-sm"></i>
                <span>Volver a Compras</span>
            </a>
            @if($purchase->supplier)
            <span class="text-slate-300">|</span>
            <a href="{{ route('suppliers.show', $purchase->supplier->id) }}" 
               class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition-colors">
                Ver Proveedor: {{ $purchase->supplier->name }}
            </a>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('receive', $purchase)
                @if($purchase->canBeReceived())
                <button type="button" 
                        @click="receiveModalOpen = true"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-500/20 transition-all">
                    <i class="bi bi-box2-check"></i>
                    <span>Confirmar Recepción</span>
                </button>
                @endif
            @endcan

            @can('cancel', $purchase)
                @if($purchase->canBeCancelled())
                <button type="button" 
                        @click="cancelModalOpen = true"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-all shadow-sm">
                    <i class="bi bi-slash-circle"></i>
                    <span>Cancelar Orden</span>
                </button>
                @endif
            @endcan

            <button onclick="window.print()" 
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 transition-all shadow-sm">
                <i class="bi bi-printer"></i>
                <span>Imprimir</span>
            </button>

            @can('create', \App\Models\Purchase::class)
            <a href="{{ route('purchases.create') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 transition-all shadow-sm">
                <i class="bi bi-plus-lg text-emerald-600"></i>
                <span>Nueva Compra</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- Status Alert Banner -->
    @if($purchase->isPending())
    <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200/80 flex items-start gap-3">
        <i class="bi bi-hourglass-split text-amber-600 text-lg mt-0.5"></i>
        <div class="text-xs text-amber-800 space-y-1">
            <span class="font-bold block">Orden de Compra Pendiente de Recepción</span>
            <p class="leading-relaxed text-amber-700">
                Esta compra está guardada pero <strong>los productos NO han ingresado a inventario</strong>. Cuando la mercadería arribe físicamente a bodega, pulsa en <strong class="font-bold">"Confirmar Recepción"</strong> para actualizar el stock y registrar el Kardex de forma atómica.
            </p>
        </div>
    </div>
    @elseif($purchase->isReceived())
    <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200/80 flex items-start gap-3">
        <i class="bi bi-check2-circle text-emerald-600 text-lg mt-0.5"></i>
        <div class="text-xs text-emerald-900 space-y-0.5">
            <span class="font-bold block">Mercancía Recibida e Ingresada a Inventario</span>
            <p class="text-emerald-700 text-[11px]">
                Recepción confirmada el {{ $purchase->received_at ? $purchase->received_at->format('d/m/Y H:i:s') : 'N/D' }}
                por <strong class="font-semibold">{{ $purchase->receiver->name ?? $purchase->user->name ?? 'Usuario del Sistema' }}</strong>.
                Existencias y costos actualizados con trazabilidad completa.
            </p>
        </div>
    </div>
    @elseif($purchase->isCancelled())
    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200/80 flex items-start gap-3">
        <i class="bi bi-x-circle text-rose-600 text-lg mt-0.5"></i>
        <div class="text-xs text-rose-900 space-y-0.5">
            <span class="font-bold block">Orden de Compra Cancelada</span>
            <p class="text-rose-700 text-[11px]">
                Esta orden fue anulada antes de recibir la mercancía. No se realizaron cambios en las existencias ni costos de los artículos.
            </p>
        </div>
    </div>
    @endif

    <!-- Voucher / Invoice Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden p-6 sm:p-10 space-y-8">
        
        <!-- Voucher Header -->
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6 pb-6 border-b border-slate-100">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-800 border border-slate-200">
                        COMPRA / ABASTECIMIENTO
                    </span>
                    @if($purchase->isReceived())
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span> Recibida en Bodega
                        </span>
                    @elseif($purchase->isPending())
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1"></span> Pendiente de Recepción
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1"></span> Cancelada
                        </span>
                    @endif
                </div>

                <h1 class="text-2xl font-black font-mono text-slate-900 tracking-tight">
                    #{{ $purchase->purchase_number }}
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Registrado el {{ $purchase->purchase_date ? $purchase->purchase_date->format('d/m/Y') : $purchase->created_at->format('d/m/Y') }}
                    por <span class="font-semibold text-slate-700">{{ $purchase->user->name ?? 'Usuario del Sistema' }}</span>
                </p>
            </div>

            <div class="text-left sm:text-right">
                <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold block">Total Liquidado</span>
                <div class="text-3xl font-black font-mono text-emerald-600 mt-0.5">
                    ${{ number_format($purchase->total, 2) }}
                </div>
                @if($purchase->supplier_invoice_number)
                <div class="mt-1 text-xs text-slate-500 font-mono">
                    Factura Prov: <strong class="text-slate-800">{{ $purchase->supplier_invoice_number }}</strong>
                </div>
                @endif
            </div>
        </div>

        <!-- Supplier & Metadata Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs">
            
            <!-- Supplier Details -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-2">
                <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Datos del Proveedor</span>
                @if($purchase->supplier)
                    <div class="text-sm font-bold text-slate-900">{{ $purchase->supplier->name }}</div>
                    <div class="font-mono text-slate-600">RUC/NIT/ID: {{ $purchase->supplier->tax_id }}</div>
                    @if($purchase->supplier->contact_name)
                        <div class="text-slate-600">Contacto: {{ $purchase->supplier->contact_name }}</div>
                    @endif
                    @if($purchase->supplier->email)
                        <div class="text-slate-600">Email: {{ $purchase->supplier->email }}</div>
                    @endif
                    @if($purchase->supplier->phone)
                        <div class="text-slate-600">Tel: {{ $purchase->supplier->phone }}</div>
                    @endif
                    @if($purchase->supplier->address)
                        <div class="text-slate-600">Dirección: {{ $purchase->supplier->address }}</div>
                    @endif
                @else
                    <span class="text-slate-400 italic">Proveedor no especificado o eliminado.</span>
                @endif
            </div>

            <!-- Transaction Details -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-2">
                <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Detalles de Operación y Recepción</span>
                <div class="flex justify-between py-1 border-b border-slate-200/60">
                    <span class="text-slate-500">Fecha de Operación:</span>
                    <span class="font-semibold text-slate-800">{{ $purchase->purchase_date ? $purchase->purchase_date->format('d/m/Y') : '—' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60">
                    <span class="text-slate-500">Factura del Proveedor:</span>
                    <span class="font-mono font-semibold text-slate-800">{{ $purchase->supplier_invoice_number ?: 'Sin registrar' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60">
                    <span class="text-slate-500">Estado de Recepción:</span>
                    <span class="font-semibold {{ $purchase->isReceived() ? 'text-emerald-700' : ($purchase->isPending() ? 'text-amber-700' : 'text-rose-700') }}">
                        {{ $purchase->isReceived() ? 'Recibida en Bodega' : ($purchase->isPending() ? 'Pendiente' : 'Cancelada') }}
                    </span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-500">Recepción Física:</span>
                    <span class="text-slate-800">
                        @if($purchase->isReceived())
                            {{ $purchase->received_at ? $purchase->received_at->format('d/m/Y H:i') : 'Sí' }}
                            @if($purchase->receiver)
                                ({{ $purchase->receiver->name }})
                            @endif
                        @elseif($purchase->isPending())
                            <span class="text-amber-600 font-medium">Por recibir</span>
                        @else
                            <span class="text-rose-600 font-medium">Anulada</span>
                        @endif
                    </span>
                </div>
            </div>

        </div>

        <!-- Line Items Table -->
        <div class="border border-slate-200/80 rounded-2xl overflow-hidden">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4 w-1/12 text-center">#</th>
                        <th class="py-3 px-4 w-5/12">Producto</th>
                        <th class="py-3 px-4 w-2/12 text-center">Cantidad</th>
                        <th class="py-3 px-4 w-2/12 text-right">Costo Unit.</th>
                        <th class="py-3 px-4 w-1/12 text-right">Descto.</th>
                        <th class="py-3 px-4 w-2/12 text-right">Subtotal Neto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($purchase->items as $index => $item)
                    <tr>
                        <td class="py-3.5 px-4 text-center font-mono text-slate-400">{{ $index + 1 }}</td>
                        <td class="py-3.5 px-4">
                            @if($item->product)
                                <div class="font-semibold text-slate-900">{{ $item->product->name }}</div>
                                <div class="font-mono text-[11px] text-slate-400">SKU/Código: {{ $item->product->barcode ?? $item->product->sku ?? '—' }}</div>
                            @else
                                <span class="text-slate-400 italic">Producto eliminado</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-800">
                            {{ $item->quantity }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono text-slate-600">
                            ${{ number_format($item->unit_cost, 2) }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono text-rose-500">
                            {{ $item->discount > 0 ? '-$' . number_format($item->discount, 2) : '—' }}
                        </td>
                        <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">
                            ${{ number_format($item->subtotal, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals & Notes -->
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-6 pt-2">
            <div class="sm:col-span-7">
                @if($purchase->notes)
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs">
                    <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block mb-1">Notas / Observaciones</span>
                    <p class="text-slate-700 leading-relaxed">{{ $purchase->notes }}</p>
                </div>
                @endif
            </div>

            <div class="sm:col-span-5">
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal Bruto:</span>
                        <span class="font-mono font-bold text-slate-800">${{ number_format($purchase->subtotal, 2) }}</span>
                    </div>

                    @if($purchase->discount > 0)
                    <div class="flex justify-between text-rose-600 font-medium">
                        <span>Total Descuentos:</span>
                        <span class="font-mono font-bold">-${{ number_format($purchase->discount, 2) }}</span>
                    </div>
                    @endif

                    <div class="flex justify-between text-slate-600">
                        <span>Base Imponible (Neto):</span>
                        <span class="font-mono font-bold text-slate-800">${{ number_format(max(0, $purchase->subtotal - $purchase->discount), 2) }}</span>
                    </div>

                    <div class="flex justify-between text-slate-600">
                        <span>Impuesto / IVA (12%):</span>
                        <span class="font-mono font-bold text-slate-800">${{ number_format($purchase->tax, 2) }}</span>
                    </div>

                    <div class="pt-2 border-t border-slate-200/80 flex justify-between text-sm">
                        <span class="font-bold text-slate-900">Total Liquidado:</span>
                        <span class="font-mono text-lg font-black text-emerald-600">${{ number_format($purchase->total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal de Confirmación de Recepción -->
    @if($purchase->canBeReceived())
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
                    Al confirmar, el sistema incrementará el stock físico de los artículos especificados y actualizará sus costos unitarios en el inventario.
                </p>
            </div>
            <form action="{{ route('purchases.receive', $purchase->id) }}" method="POST" class="pt-2 flex items-center justify-end gap-2">
                @csrf
                @method('PATCH')
                <button type="button" 
                        @click="receiveModalOpen = false" 
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-500/20 transition-all">
                    Confirmar e Ingresar a Inventario
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- Modal de Cancelación de Orden -->
    @if($purchase->canBeCancelled())
    <div x-show="cancelModalOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-xs" 
         x-cloak>
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-xl border border-slate-100"
             @click.away="cancelModalOpen = false">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl mx-auto">
                <i class="bi bi-exclamation-octagon"></i>
            </div>
            <div class="text-center space-y-1">
                <h3 class="text-base font-bold text-slate-900">¿Cancelar Orden de Compra?</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    La orden #{{ $purchase->purchase_number }} quedará marcada como <strong class="text-rose-600">Cancelada</strong>. Como nunca fue recibida, tu inventario no se verá afectado.
                </p>
            </div>
            <form action="{{ route('purchases.cancel', $purchase->id) }}" method="POST" class="pt-2 flex items-center justify-end gap-2">
                @csrf
                @method('PATCH')
                <button type="button" 
                        @click="cancelModalOpen = false" 
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                    Volver
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-sm shadow-rose-500/20 transition-all">
                    Confirmar Cancelación
                </button>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
