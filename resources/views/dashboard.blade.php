@extends('layouts.app')

@section('title', 'Panel de Control - Ferretería El Tornillo')

@section('content')
<div class="container-fluid">
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h1 class="h3 fw-bold text-dark mb-0">¡Bienvenido de vuelta, {{ auth()->user()->name ?? 'Administrador' }}!</h1>
            <p class="text-muted">Aquí tienes un resumen general del movimiento técnico de hoy en la ferretería.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <span class="badge bg-white text-dark shadow-sm p-3 border rounded-3 font-monospace">
                <i class="bi bi-calendar3 text-warning me-2"></i>
                {{ date('d/M/Y - H:i') }}
            </span>
        </div>
    </div>

    <!-- Panel de Tarjetas de Indicadores Rápidos -->
    <div class="row g-4 mb-4">
        <!-- Venta del Día -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 shadow-sm border-start border-primary border-4 rounded-3">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted text-uppercase fw-bold text-xs" style="font-size: 0.75rem; letter-spacing: 0.05em;">Ventas del Día</span>
                        <div class="rounded-3 bg-primary bg-opacity-10 p-2 text-primary" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-currency-dollar fs-4"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-1 font-mono">${{ number_format($dailyTotal, 2) }}</h3>
                    <small class="text-success fw-bold d-flex align-items-center gap-1">
                        <i class="bi bi-graph-up"></i>
                        <span>{{ $ordersToday }} transacción{{ $ordersToday === 1 ? '' : 'es' }} hoy</span>
                    </small>
                </div>
            </div>
        </div>

        <!-- Productos Registrados -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 shadow-sm border-start border-warning border-4 rounded-3">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted text-uppercase fw-bold text-xs" style="font-size: 0.75rem; letter-spacing: 0.05em;">Total Productos</span>
                        <div class="rounded-3 bg-warning bg-opacity-10 p-2 text-warning" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-boxes fs-4"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-1 font-mono">{{ $totalProducts }}</h3>
                    <small class="text-muted d-block">En {{ $totalCategories }} categorías</small>
                </div>
            </div>
        </div>

        <!-- Alerta de Stocks Bajos -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 shadow-sm border-start border-danger border-4 rounded-3">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted text-uppercase fw-bold text-xs" style="font-size: 0.75rem; letter-spacing: 0.05em;">Stock Crítico</span>
                        <div class="rounded-3 bg-danger bg-opacity-10 p-2 text-danger" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-exclamation-triangle fs-4"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-danger mb-1 font-mono">{{ $lowStockCount + $outOfStockCount }} Artículos</h3>
                    <small class="text-danger fw-bold d-block">{{ $outOfStockCount }} sin stock, {{ $lowStockCount }} bajo stock</small>
                </div>
            </div>
        </div>

        <!-- Transacciones del Día -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 shadow-sm border-start border-success border-4 rounded-3">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted text-uppercase fw-bold text-xs" style="font-size: 0.75rem; letter-spacing: 0.05em;">Órdenes del Día</span>
                        <div class="rounded-3 bg-success bg-opacity-10 p-2 text-success" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-receipt-cutoff fs-4"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-1 font-mono">${{ number_format($averageTicket, 2) }}</h3>
                    <small class="text-muted d-block">Promedio por boleta</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Menú de Accesos Rápidos (POS e Inventario) -->
    <div class="row g-4 mb-4">
        <!-- Tarjeta Izquierda: Acceso Rápido a Venta POS -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 bg-primary text-white position-relative overflow-hidden rounded-3">
                <div class="position-absolute" style="right: -20px; bottom: -20px; opacity: 0.15;">
                    <i class="bi bi-cart4" style="font-size: 11rem;"></i>
                </div>
                <div class="card-body p-0">
                    <span class="badge bg-warning text-dark text-uppercase mb-2 fw-bold">Módulo Transaccional</span>
                    <h4 class="fw-bold mb-2">Punto de Venta Integrado</h4>
                    <p class="text-light mb-4" style="max-width: 440px; opacity: 0.9;">
                        Apertura directa al carrito interactivo de facturación. Ideal para cajeros y personal de mostrador. Carga productos con un clic, calcula impuestos y emite tickets.
                    </p>
                    <a href="{{ route('pos.create') }}" class="btn btn-warning fw-bold px-4 py-2">
                        <i class="bi bi-calculator me-2"></i>Ir a Vender (POS)
                    </a>
                </div>
            </div>
        </div>

        <!-- Tarjeta Derecha: Acceso Rápido a Inventario -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white position-relative overflow-hidden rounded-3">
                <div class="position-absolute" style="right: -20px; bottom: -20px; opacity: 0.15;">
                    <i class="bi bi-boxes" style="font-size: 11rem;"></i>
                </div>
                <div class="card-body p-0">
                    <span class="badge bg-orange text-white text-uppercase mb-2 fw-bold" style="background-color: #f97316;">Bodega y Auditoría</span>
                    <h4 class="fw-bold mb-2">Administrar Artículos y Precios</h4>
                    <p class="text-muted mb-4" style="max-width: 440px; color: #cbd5e1 !important; opacity: 0.9;">
                        Inspecciona stock de clavería, cementos, herramientas. Actualiza precios, genera nuevos registros con códigos SKU, y saca estimaciones de almacenamiento real.
                    </p>
                    <a href="{{ route('inventory.index') }}" class="btn btn-outline-light fw-bold px-4 py-2">
                        <i class="bi bi-eye-fill me-2"></i>Ir al Inventario
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection