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
    }
}">

    <!-- Header Actions -->
    <div class="animate-fade-up flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Inventario General de Bodega</h1>
            <p class="text-sm text-slate-500 mt-1">Control de existencias, materiales de construcción, fijaciones y herramientas.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('inventory.export.pdf') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200/80 transition-all active:scale-95 shadow-subtle">
                <i class="bi bi-file-earmark-pdf-fill text-rose-500 text-sm"></i>
                <span>Exportar PDF</span>
            </a>

            <a href="{{ route('inventory.export.csv') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200/80 transition-all active:scale-95 shadow-subtle">
                <i class="bi bi-file-earmark-spreadsheet-fill text-emerald-600 text-sm"></i>
                <span>Exportar CSV</span>
            </a>

            <a href="{{ route('inventory.create') }}" 
               class="btn-shine inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-500 hover:bg-emerald-600 shadow-sm shadow-emerald-500/25 transition-all active:scale-95">
                <i class="bi bi-plus-lg"></i>
                <span>Nuevo Producto</span>
            </a>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="animate-fade-up stagger-1 p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/80 shadow-subtle">
        <form action="{{ route('inventory.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5">
            <!-- Search Input -->
            <div class="lg:col-span-5">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Buscar Producto</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" 
                           class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800 placeholder-slate-400"
                           placeholder="Escribe el nombre, código SKU o material..." 
                           value="{{ request('search') }}">
                </div>
            </div>

            <!-- Category Filter -->
            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Familia / Categoría</label>
                <select name="category" 
                        class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700">
                    <option value="" {{ request('category') === '' ? 'selected' : '' }}>Todas las Familias</option>
                    <option value="Herramientas Manuales" {{ request('category') === 'Herramientas Manuales' ? 'selected' : '' }}>Herramientas Manuales</option>
                    <option value="Herramientas Eléctricas" {{ request('category') === 'Herramientas Eléctricas' ? 'selected' : '' }}>Herramientas Eléctricas</option>
                    <option value="Fijaciones y Anclajes" {{ request('category') === 'Fijaciones y Anclajes' ? 'selected' : '' }}>Fijaciones y Anclajes</option>
                    <option value="Construcción y Áridos" {{ request('category') === 'Construcción y Áridos' ? 'selected' : '' }}>Construcción y Áridos</option>
                    <option value="Plomería" {{ request('category') === 'Plomería' ? 'selected' : '' }}>Plomería</option>
                    <option value="Pinturas" {{ request('category') === 'Pinturas' ? 'selected' : '' }}>Pinturas</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Nivel de Stock</label>
                <select name="status" 
                        class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700">
                    <option value="" {{ request('status') === '' ? 'selected' : '' }}>Todos</option>
                    <option value="disponible" {{ request('status') === 'disponible' ? 'selected' : '' }}>Disponible (>10)</option>
                    <option value="critico" {{ request('status') === 'critico' ? 'selected' : '' }}>Bajo Stock (1-10)</option>
                    <option value="sin_stock" {{ request('status') === 'sin_stock' ? 'selected' : '' }}>Agotado (0)</option>
                </select>
            </div>

            <!-- Submit Button -->
            <div class="lg:col-span-2 flex items-end">
                <button type="submit" 
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 transition-all shadow-sm">
                    <i class="bi bi-funnel-fill text-emerald-400"></i>
                    <span>Filtrar</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Products Table Card -->
    <div class="animate-fade-up stagger-2 rounded-2xl bg-white border border-slate-200/80 shadow-subtle overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/80 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Código SKU</th>
                        <th class="py-3.5 px-4">Material / Producto</th>
                        <th class="py-3.5 px-4 text-center">Categoría</th>
                        <th class="py-3.5 px-4 text-right">Precio Unitario</th>
                        <th class="py-3.5 px-4 text-center">Stock</th>
                        <th class="py-3.5 px-4 text-center">Estado</th>
                        <th class="py-3.5 px-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($products as $product)
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        
                        <!-- SKU -->
                        <td class="py-4 px-6 font-mono text-xs font-bold text-slate-800">
                            <span class="inline-block px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200/80 text-slate-700">
                                {{ $product->sku }}
                            </span>
                        </td>

                        <!-- Name & Desc -->
                        <td class="py-4 px-4 max-w-xs">
                            <a href="{{ route('inventory.show', $product->id) }}" class="font-semibold text-slate-900 hover:text-emerald-600 transition-colors block">
                                {{ $product->name }}
                            </a>
                            <p class="text-xs text-slate-600 truncate mt-0.5">
                                {{ $product->description ?: 'Sin descripción detallada.' }}
                            </p>
                        </td>

                        <!-- Category -->
                        <td class="py-4 px-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                {{ $product->category }}
                            </span>
                        </td>

                        <!-- Price -->
                        <td class="py-4 px-4 text-right font-mono font-bold text-slate-900">
                            ${{ number_format($product->price, 2) }}
                            <span class="block text-[11px] font-normal text-slate-600 font-sans">por {{ $product->unit }}</span>
                        </td>

                        <!-- Stock -->
                        <td class="py-4 px-4 text-center font-mono font-bold text-slate-800">
                            {{ $product->stock }}
                        </td>

                        <!-- Status Badge -->
                        <td class="py-4 px-4 text-center">
                            @if($product->stock <= 0)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200/60">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    Sin Stock
                                </span>
                            @elseif($product->stock <= 10)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200/60">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    Stock Crítico
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Disponible
                                </span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-6 text-right">
                            <div class="inline-flex items-center gap-1">
                                <a href="{{ route('inventory.show', $product->id) }}" 
                                   class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors" 
                                   title="Ver Detalle">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <a href="{{ route('inventory.edit', $product->id) }}" 
                                   class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" 
                                   title="Editar Producto">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <button type="button" 
                                        @click="confirmDelete('{{ route('inventory.destroy', $product->id) }}', '{{ addslashes($product->name) }}')"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" 
                                        title="Eliminar Producto">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <div class="flex items-center justify-center w-12 h-12 rounded-2xl bg-slate-100 text-slate-400">
                                    <i class="bi bi-box-seam text-2xl"></i>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-900">No se encontraron productos</h3>
                                <p class="text-xs text-slate-500 max-w-sm">
                                    No hay materiales registrados que coincidan con los filtros de búsqueda aplicados.
                                </p>
                                <a href="{{ route('inventory.create') }}" 
                                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-colors">
                                    <i class="bi bi-plus-lg"></i>
                                    <span>Registrar Material Nuevo</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Real Laravel Pagination Bar -->
        @if(method_exists($products, 'links'))
        <div class="px-6 py-4 border-t border-slate-200/80 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-4">
            <span class="text-xs text-slate-500">
                Mostrando <strong class="text-slate-800">{{ $products->firstItem() ?? 0 }}</strong> a <strong class="text-slate-800">{{ $products->lastItem() ?? 0 }}</strong> de <strong class="text-slate-800">{{ $products->total() }}</strong> productos
            </span>
            <div class="scale-90 origin-right">
                {{ $products->links() }}
            </div>
        </div>
        @endif
    </div>

    <!-- Alpine.js Delete Confirmation Modal -->
    <div x-show="deleteModalOpen" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         x-cloak>
        <div class="flex min-h-screen items-center justify-center p-4 text-center">
            
            <!-- Backdrop -->
            <div x-show="deleteModalOpen" 
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm transition-opacity" 
                 @click="deleteModalOpen = false">
            </div>

            <!-- Modal Panel -->
            <div x-show="deleteModalOpen" 
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 class="relative transform overflow-hidden rounded-3xl bg-white p-6 text-left shadow-floating transition-all w-full max-w-md border border-slate-200">
                
                <div class="flex items-center gap-3.5 mb-4">
                    <div class="flex items-center justify-center w-11 h-11 rounded-2xl bg-rose-50 text-rose-600 shrink-0">
                        <i class="bi bi-trash3-fill text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Confirmar Eliminación</h3>
                        <p class="text-xs text-slate-500">Esta acción no se puede deshacer.</p>
                    </div>
                </div>

                <p class="text-sm text-slate-600 mb-4">
                    ¿Estás seguro de que deseas eliminar permanentemente el producto <strong class="text-slate-900 font-semibold" x-text="productName"></strong>?
                </p>

                <div class="p-3 mb-5 rounded-xl bg-amber-50 border border-amber-200/70 text-amber-800 text-xs">
                    <i class="bi bi-info-circle-fill me-1.5 text-amber-600"></i>
                    Si el producto tiene ventas asociadas, el sistema rechazará la eliminación por integridad.
                </div>

                <div class="flex items-center justify-end gap-2.5">
                    <button type="button" 
                            @click="deleteModalOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors">
                        Cancelar
                    </button>

                    <form :action="deleteUrl" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-sm transition-all">
                            <span>Sí, Eliminar Producto</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
