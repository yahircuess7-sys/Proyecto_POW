@extends('layouts.app')

@section('title', isset($product) ? 'Editar Producto - Ferretería El Tornillo' : 'Nuevo Producto - Ferretería El Tornillo')

@section('content')
<div class="container-fluid" style="max-width: 900px; margin: 0 auto;">
    
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary px-3" style="border-radius: 8px;">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="h3 fw-bold text-dark mb-0">
            {{ isset($product) ? 'Editar Producto Existente' : 'Ingresar Nuevo Producto a Bodega' }}
        </h1>
    </div>

    <!-- Bloque de Formulario Integrado -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="fw-bold mb-0 text-primary">
                <i class="bi bi-clipboard-data-fill me-2 text-warning"></i>
                Ficha Técnica de Material e Inventario
            </h5>
            <small class="text-muted">Asegúrese de rellenar los datos de SKU para evitar duplicados en el Punto de Venta.</small>
        </div>
        <div class="card-body p-4 p-md-5">
            
            <form action="{{ isset($product) ? route('inventory.update', $product->id) : route('inventory.store') }}" method="POST">
                @csrf
                @if(isset($product))
                    @method('PUT')
                @endif

                <div class="row g-4">
                    <!-- Código SKU -->
                    <div class="col-md-6">
                        <label for="sku" class="form-label text-dark fw-semibold">Código SKU / Referencia <span class="text-danger">*</span></label>
                        <input type="text" name="sku" id="sku" class="form-control font-mono @error('sku') is-invalid @enderror" 
                               placeholder="Ej: SKU-CEME-123" value="{{ old('sku', $product->sku ?? '') }}" required>
                        <small class="text-muted">Código único para lectores de barras o búsqueda rápida.</small>
                        @error('sku')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Categoría del Material -->
                    <div class="col-md-6">
                        <label for="category" class="form-label text-dark fw-semibold">Categoría del Material <span class="text-danger">*</span></label>
                        <select name="category" id="category" class="form-select @error('category') is-invalid @enderror" required>
                            <option value="">Seleccione una Categoría</option>
                            <option value="Fijaciones y Tornillos" {{ old('category', $product->category ?? '') === 'Fijaciones y Tornillos' ? 'selected' : '' }}>Fijaciones y Tornillos</option>
                            <option value="Herramientas Manuales" {{ old('category', $product->category ?? '') === 'Herramientas Manuales' ? 'selected' : '' }}>Herramientas Manuales</option>
                            <option value="Herramientas Eléctricas" {{ old('category', $product->category ?? '') === 'Herramientas Eléctricas' ? 'selected' : '' }}>Herramientas Eléctricas</option>
                            <option value="Construcción y Áridos" {{ old('category', $product->category ?? '') === 'Construcción y Áridos' ? 'selected' : '' }}>Construcción y Áridos</option>
                            <option value="Plomería e Hidráulica" {{ old('category', $product->category ?? '') === 'Plomería e Hidráulica' ? 'selected' : '' }}>Plomería e Hidráulica</option>
                            <option value="Pinturas" {{ old('category', $product->category ?? '') === 'Pinturas' ? 'selected' : '' }}>Pinturas</option>
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Nombre del Producto -->
                    <div class="col-12">
                        <label for="name" class="form-label text-dark fw-semibold">Nombre del Material o Herramienta <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                               placeholder="Ej: Clavos de Acero 3 Pulgadas (Bolsa de 1Kg)" value="{{ old('name', $product->name ?? '') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Descripción técnica -->
                    <div class="col-12">
                        <label for="description" class="form-label text-dark fw-semibold">Descripción del Producto</label>
                        <textarea name="description" id="description" rows="3" class="form-control" 
                                  placeholder="Indique dimensiones, marca, peso u otros factores técnicos de almacenamiento (Ej: Acero galvanizado antioxidante, marca Stanley)...">{{ old('description', $product->description ?? '') }}</textarea>
                    </div>

                    <!-- Precios -->
                    <div class="col-md-4">
                        <label for="price" class="form-label text-dark fw-semibold">Precio de Compra sugerido <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-currency-dollar"></i></span>
                            <input type="number" step="0.01" name="price" id="price" class="form-control font-mono @error('price') is-invalid @enderror" 
                                   placeholder="0.00" value="{{ old('price', $product->price ?? '') }}" required>
                        </div>
                        @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Stock Físico -->
                    <div class="col-md-4">
                        <label for="stock" class="form-label text-dark fw-semibold">Stock Inicial en Bodega <span class="text-danger">*</span></label>
                        <input type="number" name="stock" id="stock" class="form-control font-mono @error('stock') is-invalid @enderror" 
                               placeholder="Ej: 50" value="{{ old('stock', $product->stock ?? '') }}" required min="0">
                        @error('stock')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Unidad de Medida -->
                    <div class="col-md-4">
                        <label for="unit" class="form-label text-dark fw-semibold">Unidad de Medida <span class="text-danger">*</span></label>
                        <select name="unit" id="unit" class="form-select" required>
                            <option value="Unidades" {{ old('unit', $product->unit ?? '') === 'Unidades' ? 'selected' : '' }}>Unidades (Un)</option>
                            <option value="Bolsas" {{ old('unit', $product->unit ?? '') === 'Bolsas' ? 'selected' : '' }}>Bolsas (Saco 50Kg)</option>
                            <option value="Rollos" {{ old('unit', $product->unit ?? '') === 'Rollos' ? 'selected' : '' }}>Rollos (Mts)</option>
                            <option value="Cajas" {{ old('unit', $product->unit ?? '') === 'Cajas' ? 'selected' : '' }}>Cajas (Cj)</option>
                            <option value="Kilos" {{ old('unit', $product->unit ?? '') === 'Kilos' ? 'selected' : '' }}>Kilos (Kg)</option>
                        </select>
                    </div>
                </div>

                <!-- Botones del Formulario -->
                <div class="border-top pt-4 mt-5 d-flex justify-content-end gap-2">
                    <a href="{{ route('inventory.index') }}" class="btn btn-light px-4 py-2 text-dark font-medium border" style="border-radius: 8px;">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-warning px-4 py-2 fw-bold text-dark shadow-sm d-flex align-items-center gap-2" style="border-radius: 8px;">
                        <i class="bi bi-save-fill"></i>
                        <span>{{ isset($product) ? 'Guardar Cambios de Ficha' : 'Guardar Nuevo Producto' }}</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection