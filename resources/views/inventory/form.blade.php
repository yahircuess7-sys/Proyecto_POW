@extends('layouts.app')

@section('title', isset($product) ? 'Editar Producto - PuntoÁgil' : 'Nuevo Producto - PuntoÁgil')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Back Navigation -->
    <div class="flex items-center justify-between">
        <a href="{{ route('inventory.index') }}" 
           class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="bi bi-arrow-left text-sm"></i>
            <span>Volver al Inventario</span>
        </a>

        <span class="text-xs text-slate-400 font-mono">
            {{ isset($product) ? 'ID #' . $product->id : 'Registro Nuevo' }}
        </span>
    </div>

    <!-- Form Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-subtle overflow-hidden">
        
        <!-- Header -->
        <div class="px-6 py-5 border-b border-slate-200/80 bg-slate-50/50 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-slate-900">
                    {{ isset($product) ? 'Editar Ficha de Material' : 'Ingresar Nuevo Producto a Bodega' }}
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Completa la información técnica, categorización y niveles de inventario.
                </p>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                <i class="bi bi-boxes"></i>
            </div>
        </div>

        <!-- Form Body -->
        <form action="{{ isset($product) ? route('inventory.update', $product->id) : route('inventory.store') }}" 
              method="POST" 
              class="p-6 sm:p-8 space-y-6">
            @csrf
            @if(isset($product))
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- SKU -->
                <div>
                    <label for="sku" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Código SKU / Referencia <span class="text-slate-400 font-normal font-sans">(Opcional)</span>
                    </label>
                    <input type="text" name="sku" id="sku" 
                           class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('sku') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-slate-50/50' }} font-mono text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800 uppercase"
                           placeholder="Ej: TOR-001 (o déjalo en blanco para auto-generar)" 
                           value="{{ old('sku', $product->sku ?? '') }}">
                    <p class="mt-1 text-[11px] text-slate-600">Si se omite, el sistema generará un correlativo según la familia.</p>
                    @error('sku')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Category -->
                <div>
                    <label for="category" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Categoría / Familia <span class="text-rose-500">*</span>
                    </label>
                    <select name="category" id="category" 
                            class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('category') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-slate-50/50' }} text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700" 
                            required>
                        <option value="">Seleccione una Categoría</option>
                        <option value="Fijaciones y Tornillos" {{ old('category', $product->category ?? '') === 'Fijaciones y Tornillos' ? 'selected' : '' }}>Fijaciones y Tornillos</option>
                        <option value="Herramientas Manuales" {{ old('category', $product->category ?? '') === 'Herramientas Manuales' ? 'selected' : '' }}>Herramientas Manuales</option>
                        <option value="Herramientas Eléctricas" {{ old('category', $product->category ?? '') === 'Herramientas Eléctricas' ? 'selected' : '' }}>Herramientas Eléctricas</option>
                        <option value="Construcción y Áridos" {{ old('category', $product->category ?? '') === 'Construcción y Áridos' ? 'selected' : '' }}>Construcción y Áridos</option>
                        <option value="Plomería e Hidráulica" {{ old('category', $product->category ?? '') === 'Plomería e Hidráulica' ? 'selected' : '' }}>Plomería e Hidráulica</option>
                        <option value="Pinturas" {{ old('category', $product->category ?? '') === 'Pinturas' ? 'selected' : '' }}>Pinturas</option>
                    </select>
                    @error('category')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Product Name -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Nombre del Material o Herramienta <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" 
                           class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('name') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-slate-50/50' }} text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                           placeholder="Ej: Cemento Gris Portland Especial 50Kg" 
                           value="{{ old('name', $product->name ?? '') }}" 
                           required>
                    @error('name')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Descripción Técnica y Almacenamiento
                    </label>
                    <textarea name="description" id="description" rows="3" 
                              class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800 placeholder-slate-400"
                              placeholder="Indique especificaciones, calibración, resistencia o marca del fabricante...">{{ old('description', $product->description ?? '') }}</textarea>
                    <p class="mt-1 text-[11px] text-slate-600">Este campo es guardado y se muestra en el POS y en reportes de bodega.</p>
                </div>

                <!-- Price -->
                <div>
                    <label for="price" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Precio Unitario (USD) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 font-mono font-bold">
                            $
                        </span>
                        <input type="number" step="0.01" min="0" name="price" id="price" 
                               class="w-full pl-8 pr-4 py-2.5 rounded-xl border {{ $errors->has('price') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-slate-50/50' }} font-mono text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                               placeholder="0.00" 
                               value="{{ old('price', $product->price ?? '') }}" 
                               required>
                    </div>
                    @error('price')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Stock -->
                <div>
                    <label for="stock" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Stock Físico Inicial <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" min="0" name="stock" id="stock" 
                           class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('stock') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-slate-50/50' }} font-mono text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                           placeholder="0" 
                           value="{{ old('stock', $product->stock ?? '') }}" 
                           required>
                    @error('stock')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Unit -->
                <div class="md:col-span-2">
                    <label for="unit" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Unidad de Venta y Empaque <span class="text-rose-500">*</span>
                    </label>
                    <select name="unit" id="unit" 
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700" 
                            required>
                        <option value="Unidades" {{ old('unit', $product->unit ?? '') === 'Unidades' ? 'selected' : '' }}>Unidades (Un)</option>
                        <option value="Bolsas" {{ old('unit', $product->unit ?? '') === 'Bolsas' ? 'selected' : '' }}>Bolsas / Sacos (50Kg)</option>
                        <option value="Rollos" {{ old('unit', $product->unit ?? '') === 'Rollos' ? 'selected' : '' }}>Rollos (Metros)</option>
                        <option value="Cajas" {{ old('unit', $product->unit ?? '') === 'Cajas' ? 'selected' : '' }}>Cajas (Cj)</option>
                        <option value="Kilos" {{ old('unit', $product->unit ?? '') === 'Kilos' ? 'selected' : '' }}>Kilos (Kg)</option>
                        <option value="Galón" {{ old('unit', $product->unit ?? '') === 'Galón' ? 'selected' : '' }}>Galón (Gal)</option>
                    </select>
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="pt-6 border-t border-slate-200/80 flex items-center justify-end gap-3">
                <a href="{{ route('inventory.index') }}" 
                   class="px-5 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors">
                    Cancelar
                </a>
                <button type="submit" 
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-500 hover:bg-emerald-600 shadow-sm shadow-emerald-500/25 transition-all">
                    <i class="bi bi-check2 text-base"></i>
                    <span>{{ isset($product) ? 'Actualizar Ficha' : 'Guardar Producto' }}</span>
                </button>
            </div>
        </form>

    </div>
</div>
@endsection
