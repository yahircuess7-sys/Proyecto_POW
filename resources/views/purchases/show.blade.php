@extends('layouts.app')

@section('title', 'Comprobante de Compra #' . $purchase->purchase_number . ' - PuntoÁgil')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

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

        <div class="flex items-center gap-2">
            <button onclick="window.print()" 
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 transition-all shadow-sm">
                <i class="bi bi-printer"></i>
                <span>Imprimir Comprobante</span>
            </button>

            @can('create', \App\Models\Purchase::class)
            <a href="{{ route('purchases.create') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-500/20 transition-all">
                <i class="bi bi-plus-lg"></i>
                <span>Nueva Compra</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- Voucher / Invoice Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden p-6 sm:p-10 space-y-8">
        
        <!-- Voucher Header -->
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6 pb-6 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
                        ORDEN DE COMPRA & INGRESO
                    </span>
                    @if($purchase->status === 'completed')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span> Completada
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
                <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block">Detalles de Operación</span>
                <div class="flex justify-between py-1 border-b border-slate-200/60">
                    <span class="text-slate-500">Fecha de Operación:</span>
                    <span class="font-semibold text-slate-800">{{ $purchase->purchase_date ? $purchase->purchase_date->format('d/m/Y') : '—' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60">
                    <span class="text-slate-500">Factura del Proveedor:</span>
                    <span class="font-mono font-semibold text-slate-800">{{ $purchase->supplier_invoice_number ?: 'Sin registrar' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60">
                    <span class="text-slate-500">Responsable de Recepción:</span>
                    <span class="font-semibold text-slate-800">{{ $purchase->user->name ?? 'Sistema' }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-slate-500">Trazabilidad en Kardex:</span>
                    <span class="inline-flex items-center text-emerald-600 font-semibold gap-1">
                        <i class="bi bi-check2-circle"></i> Stock Ingresado
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
                        <th class="py-3 px-4 w-6/12">Producto</th>
                        <th class="py-3 px-4 w-2/12 text-center">Cantidad Recibida</th>
                        <th class="py-3 px-4 w-2/12 text-right">Costo Unitario</th>
                        <th class="py-3 px-4 w-2/12 text-right">Subtotal</th>
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
                        <span>Subtotal:</span>
                        <span class="font-mono font-bold text-slate-800">${{ number_format($purchase->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Impuesto / IVA:</span>
                        <span class="font-mono font-bold text-slate-800">${{ number_format($purchase->tax, 2) }}</span>
                    </div>
                    <div class="pt-2 border-t border-slate-200/80 flex justify-between text-sm">
                        <span class="font-bold text-slate-900">Total Compra:</span>
                        <span class="font-mono text-lg font-black text-emerald-600">${{ number_format($purchase->total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
