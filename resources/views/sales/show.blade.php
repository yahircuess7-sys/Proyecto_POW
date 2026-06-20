@extends('layouts.app')

@section('title', 'Detalle de Venta - Ferretería El Tornillo')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark">Detalle de Venta</h1>
            <p class="text-muted mb-0">Revisa el comprobante de la transacción registrada.</p>
        </div>
        <a href="{{ route('reports.index') }}" class="btn btn-light border text-dark">
            <i class="bi bi-arrow-left me-2"></i> Volver a Reportes
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 p-4">
                <h5 class="fw-bold mb-3 text-dark">Información de la Venta</h5>
                <div class="mb-2 text-muted">Nº Recibo</div>
                <div class="fw-semibold mb-3">{{ $sale->invoice_number }}</div>
                <div class="mb-2 text-muted">Fecha</div>
                <div class="fw-semibold mb-3">{{ $sale->created_at->format('d/m/Y H:i') }}</div>
                <div class="mb-2 text-muted">Atendido por</div>
                <div class="fw-semibold mb-3">{{ $sale->user->name ?? 'Cajero' }}</div>
                <div class="mb-2 text-muted">Productos</div>
                <div class="fw-semibold">{{ $sale->items->count() }} ítem{{ $sale->items->count() === 1 ? '' : 's' }}</div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 p-4">
                <h5 class="fw-bold mb-3 text-dark">Resumen de Totales</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span class="fw-semibold">${{ number_format($sale->subtotal, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">IVA 12%</span>
                    <span class="fw-semibold">${{ number_format($sale->tax, 2) }}</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between fw-bold fs-5">
                    <span>Total</span>
                    <span>${{ number_format($sale->total, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 mt-4">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-bold text-secondary">Detalle de productos vendidos</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase text-muted" style="font-size: 0.8rem;">
                    <tr>
                        <th>Producto</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-end">Precio Unitario</th>
                        <th class="text-end pe-4">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                        <tr>
                            <td>{{ $item->product->name ?? 'Producto eliminado' }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end pe-4">${{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
