@extends('layouts.app')

@section('title', 'Reportes Contables y Ventas')

@section('content')
<div class="row align-items-center mb-4 fade-in">
    <div class="col-md-6">
        <h4 class="fw-bold mb-0 text-dark">
            <i class="bi bi-graph-up-arrows text-warning me-2"></i>
            Reportes Contables y Ventas
        </h4>
        <p class="text-secondary small mb-0 mt-1">Historial consolidado de comprobantes emitidos y resumen financiero.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end">
        <button class="btn btn-outline-secondary fw-bold bg-white shadow-sm" style="border-radius: 6px;">
            <i class="bi bi-calendar3 me-2"></i> Hoy
        </button>
        <a href="{{ route('admin.reports.export') }}" class="btn btn-dark fw-bold text-white shadow-sm" style="border-radius: 6px;">
            <i class="bi bi-file-earmark-excel-fill me-2 text-success"></i> Exportar a Excel
        </a>
    </div>
</div>

<!-- Resumen Estadistico de Tarjetas -->
<div class="row g-3 mb-4 fade-in" style="animation-delay: 0.1s;">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 12px;">
            <div class="d-flex align-items-center mb-2 text-muted fw-semibold">
                <i class="bi bi-cash-stack me-2 fs-5 text-success"></i> Ingresos del Día
            </div>
            <h3 class="fw-bold text-dark mb-0">$<span class="font-monospace">{{ number_format($dailyTotal ?? 0, 2) }}</span></h3>
            <p class="small text-success mb-0 mt-1"><i class="bi bi-arrow-up-right me-1"></i> +12% sobre ayer</p>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 12px;">
            <div class="d-flex align-items-center mb-2 text-muted fw-semibold">
                <i class="bi bi-receipt me-2 fs-5 text-primary"></i> Boletas Emitidas
            </div>
            <h3 class="fw-bold text-dark mb-0"><span class="font-monospace">{{ $invoicesCount ?? 0 }}</span></h3>
            <p class="small text-muted mb-0 mt-1">Transacciones procesadas hoy</p>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 12px;">
            <div class="d-flex align-items-center mb-2 text-muted fw-semibold">
                <i class="bi bi-box-seam me-2 fs-5 text-warning"></i> Ticket Promedio
            </div>
            <h3 class="fw-bold text-dark mb-0">$<span class="font-monospace">{{ number_format($averageTicket ?? 0, 2) }}</span></h3>
            <p class="small text-muted mb-0 mt-1">Por cada cobro en POS</p>
        </div>
    </div>
</div>

<!-- Listado de Transacciones Restantes -->
<div class="card border-0 shadow-sm fade-in mb-4" style="border-radius: 12px; overflow: hidden; animation-delay: 0.2s;">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-secondary">Últimas Transacciones Registradas</h6>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 text-nowrap">
            <thead class="bg-light text-muted" style="font-size: 0.8rem;">
                <tr>
                    <th class="ps-4 fw-semibold text-uppercase">Nº Recibo</th>
                    <th class="fw-semibold text-uppercase">Fecha y Hora</th>
                    <th class="fw-semibold text-uppercase">Atendido Por</th>
                    <th class="fw-semibold text-uppercase text-center">Ítems</th>
                    <th class="fw-semibold text-uppercase">Subtotal</th>
                    <th class="fw-semibold text-uppercase text-end pe-4">Total</th>
                </tr>
            </thead>
            <tbody style="font-size: 0.9rem;">
                @forelse($transactions as $tx)
                <tr>
                    <td class="ps-4 fw-bold text-dark font-monospace">{{ $tx->invoice_number }}</td>
                    <td class="text-secondary font-monospace">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <span class="badge bg-light text-dark border"><i class="bi bi-person-fill text-muted me-1"></i> {{ $tx->user->name ?? 'Cajero' }}</span>
                    </td>
                    <td class="text-center font-monospace bg-light text-secondary rounded">{{ $tx->items_count ?? 0 }}</td>
                    <td class="font-monospace text-muted">${{ number_format($tx->subtotal, 2) }}</td>
                    <td class="text-end pe-4 font-monospace fw-bold text-success">${{ number_format($tx->total, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">No existen transacciones recientes.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($transactions, 'links'))
<div class="d-flex justify-content-center mt-4">
    {{ $transactions->links() }}
</div>
@endif
@endsection