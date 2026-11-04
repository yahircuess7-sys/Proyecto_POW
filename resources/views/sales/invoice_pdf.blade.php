<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Venta - PuntoÁgil</title>
    <style>
        body { font-family: 'Arial', sans-serif; color: #1f2937; margin: 0; padding: 24px; font-size: 13px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .brand { font-size: 22px; font-weight: 700; color: #059669; }
        .brand span { display: block; font-size: 12px; color: #6b7280; margin-top: 4px; font-weight: normal; }
        .invoice-meta { text-align: right; }
        .invoice-meta strong { display: block; font-size: 13px; color: #111827; }
        .invoice-meta span { font-size: 12px; color: #6b7280; }
        .section-title { font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 8px; }
        .box { background: #f8fafc; padding: 14px 16px; border-radius: 8px; margin-bottom: 16px; border: 1px solid #e2e8f0; }
        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .table th, .table td { border: 1px solid #e5e7eb; padding: 8px 10px; }
        .table th { background: #f1f5f9; color: #1e293b; font-weight: 700; text-align: left; font-size: 11px; text-transform: uppercase; }
        .table tbody tr:nth-child(even) { background: #fafafa; }
        .total-row { background: #ecfdf5; font-weight: 700; color: #065f46; }
        .text-right { text-align: right; }
        .small { font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">PuntoÁgil</div>
            <span>Comprobante de Venta Electrónico &bull; Plataforma SaaS</span>
        </div>
        <div class="invoice-meta">
            <strong>Factura / Recibo: {{ $sale->invoice_number }}</strong>
            <span>Fecha: {{ $sale->created_at->format('d/m/Y H:i') }}</span>
            <span>Atendido por: {{ $sale->user->name ?? 'Cajero' }}</span>
        </div>
    </div>

    <div class="box">
        <div class="section-title">Datos del Cliente</div>
        <div class="grid">
            <div>
                <strong>Cliente</strong>
                <div class="small">{{ $customerName }}</div>
            </div>
            <div>
                <strong>Documento</strong>
                <div class="small">{{ $customerDocument }}</div>
            </div>
        </div>
    </div>

    <div class="box">
        <div class="section-title">Detalle de Productos Facturados</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="text-right">Cantidad</th>
                    <th class="text-right">Precio Unitario</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                    <tr>
                        <td>{{ $item->product->name ?? 'Producto eliminado' }}</td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">${{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="box">
        <div class="grid">
            <div>
                <strong>Observaciones</strong>
                <div class="small">Gracias por su compra. Comprobante electrónico generado por PuntoÁgil Cloud POS.</div>
            </div>
            <div>
                <table class="table">
                    <tbody>
                        <tr>
                            <td>Subtotal</td>
                            <td class="text-right">${{ number_format($sale->subtotal, 2) }}</td>
                        </tr>
                        @if($sale->discount > 0)
                        <tr>
                            <td>Descuento</td>
                            <td class="text-right text-rose-600">-${{ number_format($sale->discount, 2) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td>IVA (12%)</td>
                            <td class="text-right">${{ number_format($sale->tax, 2) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td>Total Pagado</td>
                            <td class="text-right">${{ number_format($sale->total, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Método de Pago</td>
                            <td class="text-right">{{ strtoupper($sale->payment_method ?? 'EFECTIVO') }}</td>
                        </tr>
                        @if(($sale->payment_method ?? 'cash') === 'cash' && $sale->paid_amount)
                        <tr>
                            <td>Efectivo Recibido</td>
                            <td class="text-right">${{ number_format($sale->paid_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Cambio / Vuelto</td>
                            <td class="text-right">${{ number_format($sale->change_amount, 2) }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 20px; padding: 10px 14px; font-size: 10.5px; color: #64748b; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px;">
        <strong>AVISO DE CONTROL INTERNO:</strong> Este comprobante ha sido generado para control de gestión y entrega de mercancía interna. No constituye una factura electrónica fiscal ni confiere crédito tributario ante entidades fiscales.
    </div>
</body>
</html>
