<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario General - PuntoÁgil</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #1e293b; margin: 0; padding: 12px; }
        .header { text-align: left; margin-bottom: 14px; border-bottom: 2px solid #10b981; padding-bottom: 8px; }
        .header h1 { margin: 0; font-size: 16px; color: #0f172a; }
        .header p { margin: 3px 0 0; font-size: 10px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 6px; text-align: left; }
        th { background-color: #f1f5f9; color: #334155; font-size: 9px; text-transform: uppercase; font-weight: bold; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: monospace; }
        .footer { margin-top: 15px; font-size: 9px; color: #94a3b8; text-align: right; }
        .badge-active { color: #047857; font-weight: bold; }
        .badge-inactive { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PuntoÁgil &bull; Reporte Consolidado de Inventario y Existencias</h1>
        <p>Catálogo de artículos, costos, precios, unidades de empaque y niveles de existencias físicas.</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Cód. Barras</th>
                <th>Nombre del Producto</th>
                <th>Categoría</th>
                <th class="text-right">Costo (C$)</th>
                <th class="text-right">Precio (C$)</th>
                <th class="text-center">Stock</th>
                <th class="text-center">Mínimo</th>
                <th>Unidad</th>
                <th class="text-center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr>
                <td class="font-mono">{{ $product['sku'] }}</td>
                <td class="font-mono">{{ $product['barcode'] ?: '---' }}</td>
                <td><strong>{{ $product['name'] }}</strong></td>
                <td>{{ $product['category'] }}</td>
                <td class="text-right font-mono">C$ {{ $product['cost'] }}</td>
                <td class="text-right font-mono"><strong>C$ {{ $product['price'] }}</strong></td>
                <td class="text-center font-mono font-bold">{{ $product['stock'] }}</td>
                <td class="text-center font-mono">{{ $product['min_stock'] }}</td>
                <td>{{ $product['unit'] }}</td>
                <td class="text-center {{ $product['status'] === 'Activo' ? 'badge-active' : 'badge-inactive' }}">
                    {{ $product['status'] }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">
        Generado automáticamente por PuntoÁgil Cloud Platform &bull; {{ date('d/m/Y H:i') }}
    </div>
</body>
</html>
