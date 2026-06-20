<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Arial', sans-serif; color: #1f2937; margin: 0; padding: 24px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .brand { font-size: 20px; font-weight: 700; color: #111827; }
        .brand span { display: block; font-size: 12px; color: #6b7280; margin-top: 4px; }
        .invoice-meta { text-align: right; }
        .invoice-meta strong { display: block; font-size: 12px; color: #374151; }
        .invoice-meta span { font-size: 12px; color: #6b7280; }
        .section-title { font-size: 14px; font-weight: 700; color: #111827; margin-bottom: 12px; }
        .box { background: #f8fafc; padding: 14px 16px; border-radius: 12px; margin-bottom: 18px; }
        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .table th, .table td { border: 1px solid #e5e7eb; padding: 10px 12px; }
        .table th { background: #eef2ff; color: #1e293b; font-weight: 700; text-align: left; }
        .table tbody tr:nth-child(even) { background: #fbfbff; }
        .total-row { background: #f8fafc; font-weight: 700; }
        .text-right { text-align: right; }
        .small { font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">Ferretería El Tornillo</div>
            <span>Comprobante de Venta / Boleta</span>
        </div>
        <div class="invoice-meta">
            <strong>Boleta: {{ $sale->invoice_number }}</strong>
            <span>Fecha: {{ $sale->created_at->format('d/m/Y H:i') }}</span>
            <span>Cajero: {{ $sale->user->name ?? 'Cajero' }}</span>
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
        <div class="section-title">Detalle de Productos</div>
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
                <div class="small">Gracias por su compra. Revise su boleta antes de retirar sus productos.</div>
            </div>
            <div>
                <table class="table">
                    <tbody>
                        <tr>
                            <td>Subtotal</td>
                            <td class="text-right">${{ number_format($sale->subtotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td>IVA 12%</td>
                            <td class="text-right">${{ number_format($sale->tax, 2) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td>Total</td>
                            <td class="text-right">${{ number_format($sale->total, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
