<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $sale->invoice_number }} - {{ $business->name ?? 'PuntoÁgil' }}</title>
    @php
        $is58mm = ($format ?? 'ticket_80mm') === 'ticket_58mm';
        $paperWidth = $is58mm ? '58mm' : '80mm';
        $contentWidth = $is58mm ? '52mm' : '74mm';
        $taxPercentage = $business ? $business->effectiveTaxPercentage(15.0) : 15.0;
        $paymentLabels = [
            'cash' => 'EFECTIVO',
            'card' => 'TARJETA',
            'transfer' => 'TRANSFERENCIA',
        ];
    @endphp
    <style>
        @page {
            size: {{ $paperWidth }} auto;
            margin: 0;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 16px 0 32px;
            background: #f1f5f9;
            font-family: 'Courier New', Courier, monospace;
            color: #000000;
            font-size: {{ $is58mm ? '11px' : '12px' }};
            line-height: 1.35;
        }
        .toolbar {
            max-width: 420px;
            margin: 0 auto 14px;
            padding: 10px 14px;
            background: #0f172a;
            color: #f8fafc;
            border-radius: 12px;
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25);
        }
        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .toolbar a, .toolbar button {
            border: none;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 10px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-size {
            background: #1e293b;
            color: #cbd5e1;
        }
        .btn-size.active {
            background: #10b981;
            color: #022c22;
        }
        .btn-print {
            background: #10b981;
            color: #022c22;
        }
        .btn-close {
            background: #334155;
            color: #f8fafc;
        }
        .ticket {
            width: {{ $contentWidth }};
            margin: 0 auto;
            background: #ffffff;
            padding: {{ $is58mm ? '3mm 2.5mm' : '4mm 3.5mm' }};
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 700; }
        .logo {
            max-width: {{ $is58mm ? '32mm' : '42mm' }};
            max-height: 18mm;
            object-fit: contain;
            margin: 0 auto 4px;
            display: block;
        }
        .biz-name {
            font-size: {{ $is58mm ? '13px' : '15px' }};
            font-weight: 700;
            text-transform: uppercase;
            margin: 0 0 2px;
        }
        .divider {
            border-top: 1px dashed #000000;
            margin: 6px 0;
        }
        .meta-row, .tot-row {
            display: flex;
            justify-content: space-between;
            gap: 6px;
            margin: 2px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th {
            text-align: left;
            font-size: {{ $is58mm ? '10px' : '11px' }};
            border-bottom: 1px dashed #000000;
            padding-bottom: 3px;
        }
        .items-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .item-name {
            font-weight: 700;
            display: block;
        }
        .item-meta {
            font-size: {{ $is58mm ? '10px' : '11px' }};
        }
        .grand-total {
            font-size: {{ $is58mm ? '14px' : '16px' }};
            font-weight: 700;
            padding: 4px 0;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            margin: 4px 0;
        }
        .footer-note {
            margin-top: 8px;
            font-size: {{ $is58mm ? '10px' : '11px' }};
            text-align: center;
        }
        .legal-note {
            margin-top: 6px;
            font-size: 9.5px;
            text-align: center;
            line-height: 1.25;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .toolbar {
                display: none !important;
            }
            .ticket {
                box-shadow: none;
                margin: 0;
                width: {{ $contentWidth }};
                padding: 2mm 1.5mm;
            }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <div class="toolbar-group">
            <a href="{{ route('sales.ticket', ['sale' => $sale->id, 'format' => 'ticket_80mm']) }}"
               class="btn-size {{ !$is58mm ? 'active' : '' }}">80mm</a>
            <a href="{{ route('sales.ticket', ['sale' => $sale->id, 'format' => 'ticket_58mm']) }}"
               class="btn-size {{ $is58mm ? 'active' : '' }}">58mm</a>
        </div>
        <div class="toolbar-group">
            <button type="button" class="btn-print" onclick="window.print()">Imprimir [Enter]</button>
            <button type="button" class="btn-close" onclick="window.close(); history.length > 1 && history.back();">Cerrar [Esc]</button>
        </div>
    </div>

    <div class="ticket">
        <div class="center">
            @if(!empty($business->settings['logo_url']))
                <img src="{{ $business->settings['logo_url'] }}" alt="{{ $business->name }}" class="logo">
            @endif
            <div class="biz-name">{{ $business->name ?? 'PuntoÁgil' }}</div>
            @if(!empty($business->settings['slogan']))
                <div>{{ $business->settings['slogan'] }}</div>
            @endif
            @if(!empty($business->tax_id))
                <div>RUC: {{ $business->tax_id }}</div>
            @endif
            @if(!empty($business->phone))
                <div>Tel: {{ $business->phone }}</div>
            @endif
            @if(!empty($business->address))
                <div>{{ $business->address }}</div>
            @endif
        </div>

        <div class="divider"></div>

        <div class="meta-row">
            <span>RECIBO:</span>
            <span class="bold">{{ $sale->invoice_number }}</span>
        </div>
        <div class="meta-row">
            <span>FECHA:</span>
            <span>{{ $sale->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="meta-row">
            <span>CAJERO:</span>
            <span>{{ $sale->user->name ?? 'Mostrador' }}</span>
        </div>
        <div class="meta-row">
            <span>CLIENTE:</span>
            <span>{{ $sale->customer_name ?: 'Consumidor Final' }}</span>
        </div>
        @if(!empty($sale->customer_document) && $sale->customer_document !== '---' && $sale->customer_document !== '9999999999')
            <div class="meta-row">
                <span>DOC/RUC:</span>
                <span>{{ $sale->customer_document }}</span>
            </div>
        @endif

        <div class="divider"></div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>DESCRIPCIÓN</th>
                    <th class="right">IMPORTE</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                    <tr>
                        <td>
                            <span class="item-name">{{ $item->product->name ?? 'Artículo' }}</span>
                            <span class="item-meta">{{ $item->quantity }} x C$ {{ number_format($item->unit_price, 2) }}</span>
                        </td>
                        <td class="right bold">C$ {{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider"></div>

        <div class="tot-row">
            <span>SUBTOTAL:</span>
            <span>C$ {{ number_format($sale->subtotal, 2) }}</span>
        </div>
        @if($sale->discount > 0)
            <div class="tot-row">
                <span>DESCUENTO:</span>
                <span>-C$ {{ number_format($sale->discount, 2) }}</span>
            </div>
        @endif
        <div class="tot-row">
            <span>IVA ({{ $taxPercentage }}%):</span>
            <span>C$ {{ number_format($sale->tax, 2) }}</span>
        </div>

        <div class="tot-row grand-total">
            <span>TOTAL A PAGAR:</span>
            <span>C$ {{ number_format($sale->total, 2) }}</span>
        </div>

        <div class="tot-row">
            <span>PAGO ({{ $paymentLabels[$sale->payment_method] ?? strtoupper($sale->payment_method ?? 'EFECTIVO') }}):</span>
            <span>C$ {{ number_format($sale->paid_amount ?: $sale->total, 2) }}</span>
        </div>
        @if(($sale->payment_method ?? 'cash') === 'cash')
            <div class="tot-row bold">
                <span>CAMBIO / VUELTO:</span>
                <span>C$ {{ number_format($sale->change_amount, 2) }}</span>
            </div>
        @endif

        <div class="divider"></div>

        <div class="footer-note bold">
            {{ $business->settings['receipt_footer_note'] ?? '¡Gracias por su compra!' }}
        </div>
        <div class="legal-note">
            COMPROBANTE INTERNO DE CONTROL OPERATIVO.<br>
            Sin validez de pie de imprenta fiscal DGI.
        </div>
    </div>

    <script>
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                window.print();
            } else if (e.key === 'Escape') {
                window.close();
            }
        });

        @if($autoprint)
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 180);
        });
        @endif
    </script>
</body>
</html>
