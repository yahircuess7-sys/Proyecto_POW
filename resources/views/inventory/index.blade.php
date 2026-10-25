@extends('layouts.app')

@section('title', 'Inventario y Stock - PuntoÁgil')

@section('content')
<div class="space-y-6" x-data="{ 
    deleteModalOpen: false, 
    deleteUrl: '', 
    productName: '',
    confirmDelete(url, name) {
        this.deleteUrl = url;
        this.productName = name;
        this.deleteModalOpen = true;
    },
    // Modal Ajuste de Stock
    adjustModalOpen: false,
    adjustProductId: null,
    adjustProductName: '',
    adjustCurrentStock: 0,
    adjustProductUnit: 'Unidades',
    adjustType: 'in',
    adjustAmount: '',
    adjustReason: '',
    openAdjustModal(id, name, stock, unit) {
        this.adjustProductId = id;
        this.adjustProductName = name;
        this.adjustCurrentStock = stock;
        this.adjustProductUnit = unit;
        this.adjustType = 'in';
        this.adjustAmount = '';
        this.adjustReason = '';
        this.adjustModalOpen = true;
    },
    // Modal Importar CSV
    importModalOpen: false
}">

    <!-- Top Header & Actions -->
    <div class="animate-fade-up flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <span>Operaciones</span>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-emerald-600 font-semibold">Inventario de Mercaderías</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Catálogo de Productos y Existencias</h1>
            <p class="text-xs text-slate-500 mt-0.5">Control de artículos, precios, costos, stock crítico y trazabilidad de movimientos en bodega.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('create', \App\Models\Product::class)
            <button type="button" @click="importModalOpen = true"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 transition-all shadow-sm">
                <i class="bi bi-file-earmark-arrow-up text-emerald-600 text-sm"></i>
                <span>Importar CSV</span>
            </button>
            @endcan

            <a href="{{ route('inventory.export.csv') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-all shadow-sm">
                <i class="bi bi-file-earmark-spreadsheet-fill text-emerald-600 text-sm"></i>
                <span>Exportar CSV</span>
            </a>

            <a href="{{ route('inventory.export.pdf') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-all shadow-sm">
                <i class="bi bi-file-earmark-pdf-fill text-rose-500 text-sm"></i>
                <span>Exportar PDF</span>
            </a>

            @can('create', \App\Models\Product::class)
            <a href="{{ route('inventory.create') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-500/20 transition-all">
                <i class="bi bi-plus-lg"></i>
                <span>Nuevo Producto</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Artículos</span>
            <div class="flex items-baseline justify-between mt-1">
                <p class="text-2xl font-bold font-mono text-slate-900">{{ $kpi['total_items'] }}</p>
                <span class="text-xs text-slate-400 font-mono">{{ $kpi['active_items'] }} activos</span>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-600 flex items-center gap-1">
                <i class="bi bi-exclamation-triangle"></i> Bajo Stock
            </span>
            <p class="text-2xl font-bold font-mono text-amber-600 mt-1">{{ $kpi['critical_stock'] }}</p>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-rose-600 flex items-center gap-1">
                <i class="bi bi-x-circle"></i> Agotados
            </span>
            <p class="text-2xl font-bold font-mono text-rose-600 mt-1">{{ $kpi['out_of_stock'] }}</p>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm col-span-2 sm:col-span-1 lg:col-span-2">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Valor Estimado en Mostrador</span>
            <p class="text-2xl font-bold font-mono text-emerald-600 mt-1">${{ number_format($kpi['inventory_value'], 2) }}</p>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
        <form action="{{ route('inventory.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5">
            <!-- Search Input -->
            <div class="lg:col-span-4">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Buscar Producto</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" 
                           class="w-full pl-10 pr-4 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                           placeholder="Nombre, SKU, código de barras o descripción..." 
                           value="{{ request('search') }}">
                </div>
            </div>

            <!-- Category Filter -->
            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Familia / Categoría</label>
                <select name="category" 
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700">
                    <option value="">Todas las Categorías</option>
                    @foreach($availableCategories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Stock Filter -->
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Nivel Existencias</label>
                <select name="stock_status" 
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700">
                    <option value="">Todos los Niveles</option>
                    <option value="disponible" {{ request('stock_status') === 'disponible' ? 'selected' : '' }}>Disponible (> Mínimo)</option>
                    <option value="critico" {{ request('stock_status') === 'critico' ? 'selected' : '' }}>Stock Crítico (&le; Mínimo)</option>
                    <option value="sin_stock" {{ request('stock_status') === 'sin_stock' ? 'selected' : '' }}>Agotado (0)</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Estado Catálogo</label>
                <select name="status" 
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700">
                    <option value="">Todos los Estados</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Solo Activos</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Solo Desactivados</option>
                </select>
            </div>

            <!-- Submit Button -->
            <div class="lg:col-span-1 flex items-end">
                <button type="submit" 
                        class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 transition-all shadow-sm">
                    <i class="bi bi-funnel-fill text-emerald-400"></i>
                    <span>Filtrar</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Products Table Card -->
    <div class="rounded-2xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                        <th class="py-3.5 px-5">Código / SKU</th>
                        <th class="py-3.5 px-4">Material / Producto</th>
                        <th class="py-3.5 px-3 text-center">Categoría</th>
                        <th class="py-3.5 px-3 text-right">Costo</th>
                        <th class="py-3.5 px-4 text-right">Precio Venta</th>
                        <th class="py-3.5 px-4 text-center">Existencias</th>
                        <th class="py-3.5 px-3 text-center">Estado</th>
                        <th class="py-3.5 px-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                    <tr class="hover:bg-slate-50/80 transition-colors {{ ! $product->isActive() ? 'bg-slate-50/50 opacity-75' : '' }}">
                        
                        <!-- SKU & Barcode -->
                        <td class="py-3.5 px-5 font-mono">
                            <span class="inline-block px-2 py-0.5 rounded-lg bg-slate-100 border border-slate-200 font-bold text-slate-700">
                                {{ $product->sku }}
                            </span>
                            @if($product->barcode)
                                <p class="text-[10px] text-slate-400 font-mono mt-0.5 flex items-center gap-1">
                                    <i class="bi bi-upc-scan"></i> {{ $product->barcode }}
                                </p>
                            @endif
                        </td>

                        <!-- Name & Desc -->
                        <td class="py-3.5 px-4 max-w-xs">
                            <a href="{{ route('inventory.show', $product->id) }}" class="font-bold text-slate-900 hover:text-emerald-600 transition-colors block text-xs">
                                {{ $product->name }}
                            </a>
                            <p class="text-[11px] text-slate-500 truncate mt-0.5">
                                {{ $product->description ?: 'Sin especificaciones adicionales.' }}
                            </p>
                        </td>

                        <!-- Category -->
                        <td class="py-3.5 px-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-700">
                                {{ $product->category }}
                            </span>
                        </td>

                        <!-- Cost -->
                        <td class="py-3.5 px-3 text-right font-mono text-slate-500">
                            ${{ number_format((float) $product->cost, 2) }}
                        </td>

                        <!-- Price -->
                        <td class="py-3.5 px-4 text-right font-mono">
                            <span class="font-bold text-slate-900 text-xs">${{ number_format((float) $product->price, 2) }}</span>
                            <span class="block text-[10px] text-slate-400 font-sans">por {{ $product->unit }}</span>
                        </td>

                        <!-- Stock & Stock Status -->
                        <td class="py-3.5 px-4 text-center">
                            <div class="inline-flex items-center gap-1.5 font-mono font-bold text-xs {{ $product->isOutOfStock() ? 'text-rose-600' : ($product->isCriticalStock() ? 'text-amber-600' : 'text-slate-800') }}">
                                {{ $product->stock }}
                                <span class="text-[10px] text-slate-400 font-normal font-sans">{{ $product->unit }}</span>
                            </div>
                            <div class="mt-0.5">
                                @if($product->isOutOfStock())
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Agotado
                                    </span>
                                @elseif($product->isCriticalStock())
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200" title="Mínimo configurado: {{ $product->min_stock }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Stock Bajo (&le;{{ $product->min_stock }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Óptimo
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- Catalog Status (Active/Inactive) -->
                        <td class="py-3.5 px-3 text-center">
                            @if($product->isActive())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Activo
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                    Desactivado
                                </span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="py-3.5 px-5 text-right">
                            <div class="inline-flex items-center justify-end gap-1">
                                <a href="{{ route('inventory.show', $product->id) }}" 
                                   class="p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors" 
                                   title="Ver Detalle y Movimientos">
                                    <i class="bi bi-eye text-sm"></i>
                                </a>

                                @can('update', $product)
                                <button type="button" 
                                        @click="openAdjustModal({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->stock }}, '{{ $product->unit }}')"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" 
                                        title="Ajustar Stock con Motivo">
                                    <i class="bi bi-arrow-left-right text-sm"></i>
                                </button>

                                <form action="{{ route('inventory.toggle-status', $product->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="p-1.5 rounded-lg {{ $product->isActive() ? 'text-slate-400 hover:text-amber-600 hover:bg-amber-50' : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50' }} transition-colors"
                                            title="{{ $product->isActive() ? 'Desactivar producto para ventas' : 'Reactivar producto en catálogo' }}">
                                        <i class="bi {{ $product->isActive() ? 'bi-slash-circle' : 'bi-check-circle' }} text-sm"></i>
                                    </button>
                                </form>

                                <a href="{{ route('inventory.edit', $product->id) }}" 
                                   class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors" 
                                   title="Editar Ficha Técnica">
                                    <i class="bi bi-pencil text-sm"></i>
                                </a>
                                @endcan

                                @can('delete', $product)
                                <button type="button" 
                                        @click="confirmDelete('{{ route('inventory.destroy', $product->id) }}', '{{ addslashes($product->name) }}')"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" 
                                        title="Eliminar Producto">
                                    <i class="bi bi-trash text-sm"></i>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            <i class="bi bi-boxes text-3xl mb-2 opacity-40 block"></i>
                            No se encontraron productos registrados con los filtros seleccionados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $products->links() }}
        </div>
        @endif
    </div>

    <!-- Modal: Ajuste Manual de Stock con Motivo -->
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
                            <p class="text-[11px] text-slate-500 truncate max-w-[220px]" x-text="adjustProductName"></p>
                        </div>
                    </div>
                    <button type="button" @click="adjustModalOpen = false" class="text-slate-400 hover:text-slate-700">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="mb-4 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs flex justify-between items-center">
                    <span class="text-slate-500">Stock Actual en Bodega:</span>
                    <span class="font-bold font-mono text-slate-900 text-sm" x-text="`${adjustCurrentStock} ${adjustProductUnit}`"></span>
                </div>

                <form :action="`/inventory/${adjustProductId}/adjust`" method="POST" class="space-y-4 text-xs">
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
                        <input type="number" name="amount" min="0" required x-model="adjustAmount"
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 font-mono text-sm"
                               placeholder="Ej. 10">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Motivo / Justificación Obligatoria <span class="text-rose-500">*</span></label>
                        <input type="text" name="reason" required minlength="3" x-model="adjustReason"
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

    <!-- Modal: Importar Catálogo CSV -->
    <div x-show="importModalOpen" x-cloak class="pa-modal-backdrop" @keydown.escape.window="importModalOpen = false">
        <div class="pa-modal max-w-lg" @click.away="importModalOpen = false">
            <div class="p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                            <i class="bi bi-file-earmark-arrow-up"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Importación Masiva de Productos (CSV)</h3>
                            <p class="text-[11px] text-slate-500">Carga inventarios desde Excel o archivos delimitados por coma o punto y coma.</p>
                        </div>
                    </div>
                    <button type="button" @click="importModalOpen = false" class="text-slate-400 hover:text-slate-700">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl mb-4 text-xs space-y-1.5 text-slate-600">
                    <p class="font-bold text-slate-800">Formato de Columnas Requerido:</p>
                    <p class="font-mono text-[11px] text-slate-700 bg-white p-2 rounded border border-slate-200 break-all">
                        Nombre, Categoria, Precio, Costo, Stock, Stock_Minimo, Unidad, SKU, Codigo_Barras, Descripcion
                    </p>
                    <p class="text-[11px] text-slate-500 mt-1">Los productos importados se vincularán automáticamente a tu empresa activa con stock inicial auditado.</p>
                </div>

                <form action="{{ route('inventory.import.csv') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1.5">Seleccionar Archivo CSV <span class="text-rose-500">*</span></label>
                        <input type="file" name="file" accept=".csv,.txt" required
                               class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="importModalOpen = false" class="pa-btn-secondary py-2 px-3.5 text-xs">Cancelar</button>
                        <button type="submit" class="pa-btn-primary py-2 px-4 text-xs">
                            <i class="bi bi-cloud-arrow-up"></i> Procesar e Importar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Confirmar Eliminación -->
    <div x-show="deleteModalOpen" x-cloak class="pa-modal-backdrop" @keydown.escape.window="deleteModalOpen = false">
        <div class="pa-modal max-w-sm" @click.away="deleteModalOpen = false">
            <div class="p-6 text-center">
                <div class="w-12 h-12 rounded-2xl mx-auto flex items-center justify-center mb-4 bg-rose-50 text-rose-600 border border-rose-200">
                    <i class="bi bi-trash text-xl"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900">¿Eliminar producto?</h3>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Estás a punto de eliminar <strong class="text-slate-800" x-text="productName"></strong>.
                    Si el producto tiene ventas asociadas, el sistema bloqueará la eliminación para proteger la integridad contable (en su lugar podrás desactivarlo).
                </p>

                <form :action="deleteUrl" method="POST" class="mt-6 flex items-center justify-center gap-3">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="deleteModalOpen = false" class="pa-btn-secondary text-xs flex-1">
                        Cancelar
                    </button>
                    <button type="submit" class="pa-btn-danger text-xs flex-1">
                        Sí, Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
