@extends('layouts.app')

@section('title', $supplier->name . ' - PuntoÁgil')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <!-- Top Navigation & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <a href="{{ route('suppliers.index') }}" 
           class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="bi bi-arrow-left text-sm"></i>
            <span>Volver a Proveedores</span>
        </a>

        <div class="flex flex-wrap items-center gap-2">
            @can('update', $supplier)
            <a href="{{ route('suppliers.edit', $supplier->id) }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 transition-all shadow-sm">
                <i class="bi bi-pencil"></i>
                <span>Editar Ficha</span>
            </a>
            @endcan

            @can('delete', $supplier)
            <form action="{{ route('suppliers.toggle-status', $supplier->id) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <button type="submit" 
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold {{ $supplier->isActive() ? 'text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200' }} transition-all shadow-sm">
                    <i class="bi {{ $supplier->isActive() ? 'bi-slash-circle' : 'bi-check-circle' }}"></i>
                    <span>{{ $supplier->isActive() ? 'Desactivar Proveedor' : 'Reactivar Proveedor' }}</span>
                </button>
            </form>
            @endcan

            @can('create', \App\Models\Purchase::class)
            <a href="{{ route('purchases.create') }}?supplier_id={{ $supplier->id }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-500/20 transition-all">
                <i class="bi bi-cart-plus-fill"></i>
                <span>Registrar Compra</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- Supplier Profile Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
            
            <!-- Left Info -->
            <div class="md:col-span-7 space-y-6">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="inline-block px-2.5 py-1 rounded-lg bg-slate-100 font-mono text-xs font-bold text-slate-800 border border-slate-200">
                            RUC/NIT/ID: {{ $supplier->tax_id }}
                        </span>
                        @if($supplier->isActive())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span> Activo
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1"></span> Desactivado
                            </span>
                        @endif
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $supplier->name }}</h1>
                    @if($supplier->contact_name)
                    <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                        <i class="bi bi-person-badge"></i>
                        <span>Contacto directo: <strong class="text-slate-700 font-semibold">{{ $supplier->contact_name }}</strong></span>
                    </p>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 block font-medium">Correo Electrónico</span>
                        <span class="font-mono text-slate-800 mt-0.5 block truncate">{{ $supplier->email ?: 'No registrado' }}</span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 block font-medium">Teléfono / WhatsApp</span>
                        <span class="font-mono text-slate-800 mt-0.5 block">{{ $supplier->phone ?: 'No registrado' }}</span>
                    </div>
                    <div class="sm:col-span-2 p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 block font-medium">Dirección Fiscal / Despacho</span>
                        <span class="text-slate-800 mt-0.5 block">{{ $supplier->address ?: 'Sin dirección física especificada' }}</span>
                    </div>
                    @if($supplier->notes)
                    <div class="sm:col-span-2 p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 block font-medium">Condiciones Comerciales y Notas</span>
                        <p class="text-slate-700 mt-0.5 leading-relaxed">{{ $supplier->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Right KPIs -->
            <div class="md:col-span-5">
                <div class="rounded-2xl p-6 bg-slate-50 border border-slate-200/80 space-y-5">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Total Comprado</span>
                        <div class="text-3xl font-extrabold font-mono text-emerald-600 mt-1">
                            C$ {{ number_format($totalPurchased, 2) }}
                        </div>
                        <span class="text-xs text-slate-500 mt-0.5 block">Monto histórico facturado por este proveedor</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-4 border-t border-slate-200/80 text-xs">
                        <div class="p-3 rounded-xl bg-white border border-slate-100 shadow-sm">
                            <span class="text-slate-400 block font-medium">Órdenes / Facturas</span>
                            <span class="text-lg font-bold font-mono text-slate-800 mt-0.5 block">{{ $purchasesCount }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-white border border-slate-100 shadow-sm">
                            <span class="text-slate-400 block font-medium">Productos Asignados</span>
                            <span class="text-lg font-bold font-mono text-slate-800 mt-0.5 block">{{ $productsCount }}</span>
                        </div>
                    </div>

                    <div class="pt-2 text-[11px] text-slate-400">
                        Registrado el {{ $supplier->created_at ? $supplier->created_at->format('d/m/Y') : 'N/D' }}
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Products Supplied Section -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Productos Suministrados</h2>
                <p class="text-xs text-slate-500 mt-0.5">Artículos del catálogo vinculados a este proveedor principal.</p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                {{ $supplier->products->count() }} referencias
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/75 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-5">Código / SKU</th>
                        <th class="py-3 px-5">Producto</th>
                        <th class="py-3 px-5">Categoría</th>
                        <th class="py-3 px-5 text-right">Costo</th>
                        <th class="py-3 px-5 text-right">PVP</th>
                        <th class="py-3 px-5 text-center">Stock Actual</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($supplier->products as $product)
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="py-3 px-5 font-mono text-slate-500">{{ $product->barcode ?? $product->sku ?? '—' }}</td>
                        <td class="py-3 px-5">
                            <div class="font-semibold text-slate-900">{{ $product->name }}</div>
                        </td>
                        <td class="py-3 px-5 text-slate-600">{{ is_string($product->category) ? ($product->category ?: 'Sin categoría') : ($product->category->name ?? 'Sin categoría') }}</td>
                        <td class="py-3 px-5 text-right font-mono text-slate-600">C$ {{ number_format($product->cost, 2) }}</td>
                        <td class="py-3 px-5 text-right font-mono font-bold text-slate-900">C$ {{ number_format($product->price, 2) }}</td>
                        <td class="py-3 px-5 text-center">
                            @if($product->stock <= 0)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-200">Agotado (0)</span>
                            @elseif($product->stock <= ($product->min_stock ?? 5))
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">{{ $product->stock }} (Bajo)</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $product->stock }} unid.</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            <i class="bi bi-box-seam text-2xl block mb-2 opacity-50"></i>
                            No hay productos asociados directamente a este proveedor aún.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Purchases History Table -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Historial de Compras y Abastecimiento</h2>
                <p class="text-xs text-slate-500 mt-0.5">Últimas órdenes o facturas de compra registradas con este proveedor.</p>
            </div>
            @can('create', \App\Models\Purchase::class)
            <a href="{{ route('purchases.create') }}?supplier_id={{ $supplier->id }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-colors">
                <i class="bi bi-plus-lg"></i>
                <span>Nueva Orden</span>
            </a>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/75 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-5">N° Compra</th>
                        <th class="py-3.5 px-5">Factura Proveedor</th>
                        <th class="py-3.5 px-5">Fecha</th>
                        <th class="py-3.5 px-5">Registrado Por</th>
                        <th class="py-3.5 px-5 text-right">Subtotal</th>
                        <th class="py-3.5 px-5 text-right">Impuesto</th>
                        <th class="py-3.5 px-5 text-right">Total</th>
                        <th class="py-3.5 px-5 text-center">Estado</th>
                        <th class="py-3.5 px-5 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($supplier->purchases as $purchase)
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="py-3 px-5 font-mono font-bold text-slate-800">
                            #{{ $purchase->purchase_number }}
                        </td>
                        <td class="py-3 px-5 font-mono text-slate-600">
                            {{ $purchase->supplier_invoice_number ?: 'S/F' }}
                        </td>
                        <td class="py-3 px-5 text-slate-600">
                            {{ $purchase->purchase_date ? $purchase->purchase_date->format('d/m/Y') : '—' }}
                        </td>
                        <td class="py-3 px-5 text-slate-600">
                            {{ $purchase->user->name ?? 'Usuario' }}
                        </td>
                        <td class="py-3 px-5 text-right font-mono text-slate-500">C$ {{ number_format($purchase->subtotal, 2) }}</td>
                        <td class="py-3 px-5 text-right font-mono text-slate-500">C$ {{ number_format($purchase->tax, 2) }}</td>
                        <td class="py-3 px-5 text-right font-mono font-bold text-emerald-600">C$ {{ number_format($purchase->total, 2) }}</td>
                        <td class="py-3 px-5 text-center">
                            @if($purchase->isReceived())
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Recibida</span>
                            @elseif($purchase->isPending())
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Pendiente</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-200">Cancelada</span>
                            @endif
                        </td>
                        <td class="py-3 px-5 text-right">
                            <a href="{{ route('purchases.show', $purchase->id) }}" 
                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200/80 transition-colors">
                                <i class="bi bi-eye"></i>
                                <span>Ver</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-400">
                            <i class="bi bi-receipt text-2xl block mb-2 opacity-50"></i>
                            No hay compras registradas con este proveedor todavía.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
