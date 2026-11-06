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
            {{ isset($product) ? 'ID #' . $product->id . ' · ' . $product->sku : 'Nuevo Registro de Catálogo' }}
        </span>
    </div>

    <!-- Form Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
        
        <!-- Header -->
        <div class="px-6 py-5 border-b border-slate-200/80 bg-slate-50/50 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-slate-900">
                    {{ isset($product) ? 'Editar Ficha de Producto' : 'Ingresar Nuevo Producto a Bodega' }}
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Especificaciones técnicas, precios, costos, código de barras y niveles de stock.
                </p>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                <i class="bi bi-boxes"></i>
            </div>
        </div>

        <!-- Form Body -->
        <form action="{{ isset($product) ? route('inventory.update', $product->id) : route('inventory.store') }}" 
              method="POST" 
              class="p-6 sm:p-8 space-y-6 text-xs">
            @csrf
            @if(isset($product))
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- SKU -->
                <div>
                    <label for="sku" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Código SKU / Referencia <span class="text-slate-400 font-normal font-sans">(Opcional)</span>
                    </label>
                    <input type="text" name="sku" id="sku" 
                           class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('sku') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-slate-50/50' }} font-mono text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800 uppercase"
                           placeholder="Ej: HER-001 (en blanco para auto-generar)" 
                           value="{{ old('sku', $product->sku ?? '') }}">
                    <p class="mt-1 text-[11px] text-slate-400">Si se omite, el sistema generará un SKU automático según la categoría.</p>
                    @error('sku')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Barcode -->
                <div>
                    <label for="barcode" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Código de Barras (EAN / UPC) <span class="text-slate-400 font-normal font-sans">(Opcional)</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i class="bi bi-upc-scan"></i>
                        </span>
                        <input type="text" name="barcode" id="barcode" 
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 font-mono text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                               placeholder="Ej: 7501234567890" 
                               value="{{ old('barcode', $product->barcode ?? '') }}">
                    </div>
                    @error('barcode')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Product Name -->
                <div class="md:col-span-2">
                    <label for="name" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Nombre del Producto o Material <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" 
                           class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('name') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-slate-50/50' }} text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800 font-medium"
                           placeholder="Ej: Taladro Percutor Inalámbrico 20V Industrial" 
                           value="{{ old('name', $product->name ?? '') }}" 
                           required>
                    @error('name')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Category -->
                <div>
                    <label for="category" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Categoría / Familia <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="category" id="category" list="categoryList"
                           class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('category') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-slate-50/50' }} text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700" 
                           placeholder="Ej. Herramientas Eléctricas"
                           value="{{ old('category', $product->category ?? '') }}"
                           required>
                    <datalist id="categoryList">
                        <option value="Herramientas Manuales">
                        <option value="Herramientas Eléctricas">
                        <option value="Fijaciones y Anclajes">
                        <option value="Construcción y Áridos">
                        <option value="Plomería e Hidráulica">
                        <option value="Pinturas y Acabados">
                        <option value="Electricidad e Iluminación">
                        <option value="Seguridad Industrial">
                    </datalist>
                    @error('category')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Unit -->
                <div>
                    <label for="unit" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Unidad de Medida / Empaque <span class="text-rose-500">*</span>
                    </label>
                    <select name="unit" id="unit" 
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700" 
                            required>
                        <option value="Unidades" {{ old('unit', $product->unit ?? '') === 'Unidades' ? 'selected' : '' }}>Unidades (Un)</option>
                        <option value="Cajas" {{ old('unit', $product->unit ?? '') === 'Cajas' ? 'selected' : '' }}>Cajas (Cj)</option>
                        <option value="Bolsas" {{ old('unit', $product->unit ?? '') === 'Bolsas' ? 'selected' : '' }}>Bolsas / Sacos (50Kg)</option>
                        <option value="Rollos" {{ old('unit', $product->unit ?? '') === 'Rollos' ? 'selected' : '' }}>Rollos (Metros)</option>
                        <option value="Kilos" {{ old('unit', $product->unit ?? '') === 'Kilos' ? 'selected' : '' }}>Kilos (Kg)</option>
                        <option value="Litros" {{ old('unit', $product->unit ?? '') === 'Litros' ? 'selected' : '' }}>Litros (L)</option>
                        <option value="Galón" {{ old('unit', $product->unit ?? '') === 'Galón' ? 'selected' : '' }}>Galón (Gal)</option>
                        <option value="Metros" {{ old('unit', $product->unit ?? '') === 'Metros' ? 'selected' : '' }}>Metros (m)</option>
                    </select>
                </div>

                <!-- Cost -->
                <div>
                    <label for="cost" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Costo Unitario de Adquisición (C$)
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 font-mono font-bold text-xs">
                            C$
                        </span>
                        <input type="number" step="0.01" min="0" name="cost" id="cost" 
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 font-mono text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                               placeholder="0.00" 
                               value="{{ old('cost', $product->cost ?? '0.00') }}">
                    </div>
                    <p class="mt-1 text-[11px] text-slate-400">Utilizado para calcular márgenes brutos y valoración contable en Córdobas.</p>
                    @error('cost')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Price -->
                <div>
                    <label for="price" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Precio de Venta al Público (C$) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 font-mono font-bold text-xs">
                            C$
                        </span>
                        <input type="number" step="0.01" min="0" name="price" id="price" 
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl border {{ $errors->has('price') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-slate-50/50' }} font-mono text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800 font-bold"
                               placeholder="0.00" 
                               value="{{ old('price', $product->price ?? '') }}" 
                               required>
                    </div>
                    @error('price')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Stock Handling -->
                @if(!isset($product))
                <div>
                    <label for="stock" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Stock Físico Inicial
                    </label>
                    <input type="number" min="0" name="stock" id="stock" 
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 font-mono text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                           placeholder="0" 
                           value="{{ old('stock', 0) }}">
                    <p class="mt-1 text-[11px] text-emerald-600 font-medium flex items-center gap-1">
                        <i class="bi bi-shield-check"></i> Generará un movimiento inicial de inventario auditado.
                    </p>
                    @error('stock')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>
                @else
                <div>
                    <label class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Existencias Actuales en Bodega
                    </label>
                    <div class="flex items-center gap-3 p-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                        <span class="font-mono font-bold text-slate-900 text-sm pl-2">
                            {{ $product->stock }} {{ $product->unit }}
                        </span>
                        <a href="{{ route('inventory.show', $product->id) }}" class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors">
                            <i class="bi bi-arrow-left-right"></i> Ajustar con Motivo
                        </a>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-400">Por trazabilidad, los cambios de stock se registran mediante la opción de ajuste.</p>
                </div>
                @endif

                <!-- Min Stock -->
                <div>
                    <label for="min_stock" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Stock Mínimo de Alerta
                    </label>
                    <input type="number" min="0" name="min_stock" id="min_stock" 
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 font-mono text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                           placeholder="5" 
                           value="{{ old('min_stock', $product->min_stock ?? 5) }}">
                    <p class="mt-1 text-[11px] text-slate-400">El sistema alertará cuando las existencias sean iguales o inferiores a este valor.</p>
                    @error('min_stock')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Estado en Catálogo
                    </label>
                    <select name="status" id="status" 
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700">
                        <option value="active" {{ old('status', $product->status ?? 'active') === 'active' ? 'selected' : '' }}>Activo (Disponible en POS e Inventario)</option>
                        <option value="inactive" {{ old('status', $product->status ?? 'active') === 'inactive' ? 'selected' : '' }}>Desactivado (Oculto en POS, preserva histórico)</option>
                    </select>
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label for="description" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Descripción Técnica y Ubicación de Almacenamiento
                    </label>
                    <textarea name="description" id="description" rows="3" 
                              class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800 placeholder-slate-400"
                              placeholder="Indique especificaciones, calibración, resistencia, ubicación en anaquel o marca...">{{ old('description', $product->description ?? '') }}</textarea>
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="pt-6 border-t border-slate-200/80 flex items-center justify-end gap-3">
                <a href="{{ route('inventory.index') }}" 
                   class="pa-btn-secondary text-xs">
                    Cancelar
                </a>
                <button type="submit" 
                        class="pa-btn-primary text-xs">
                    <i class="bi bi-check2 text-base"></i>
                    <span>{{ isset($product) ? 'Guardar Cambios' : 'Registrar Producto' }}</span>
                </button>
            </div>
        </form>

    </div>
</div>
@endsection
