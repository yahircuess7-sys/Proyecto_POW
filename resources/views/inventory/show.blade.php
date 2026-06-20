@extends('layouts.app')

@section('title', 'Detalle de Producto - Ferretería El Tornillo')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark">Detalle del Producto</h1>
            <p class="text-muted mb-0">Revisa la ficha completa del inventario y su estado actual.</p>
        </div>
        <a href="{{ route('inventory.index') }}" class="btn btn-light border text-dark">
            <i class="bi bi-arrow-left me-2"></i> Volver al Inventario
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-6">
                    <h5 class="fw-bold text-dark mb-3">{{ $product->name }}</h5>
                    <p class="text-muted mb-3">{{ $product->description ?? 'Sin descripción disponible.' }}</p>

                    <div class="mb-3">
                        <span class="text-muted">SKU:</span>
                        <strong class="d-block">{{ $product->sku }}</strong>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted">Categoría:</span>
                        <strong class="d-block">{{ $product->category }}</strong>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted">Unidad:</span>
                        <strong class="d-block">{{ $product->unit }}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border border-dashed rounded-3 p-4 h-100">
                        <div class="mb-3">
                            <span class="text-muted">Precio unitario</span>
                            <div class="h3 fw-bold mt-2">${{ number_format($product->price, 2) }}</div>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted">Stock disponible</span>
                            <div class="h3 fw-bold mt-2">{{ $product->stock }} {{ $product->unit }}</div>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted">Estado</span>
                            @if($product->stock <= 0)
                                <div class="badge bg-danger text-white mt-2">Sin Stock</div>
                            @elseif($product->stock <= 10)
                                <div class="badge bg-warning text-dark mt-2">Stock Crítico</div>
                            @else
                                <div class="badge bg-success text-white mt-2">Disponible</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
