<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario General - PuntoÁgil</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 15px; }
        .header { text-align: left; margin-bottom: 16px; border-bottom: 2px solid #10b981; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; color: #0f172a; }
        .header p { margin: 4px 0 0; font-size: 11px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background-color: #f1f5f9; color: #334155; font-size: 10px; text-transform: uppercase; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
        .footer { margin-top: 20px; font-size: 10px; color: #94a3b8; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PuntoÁgil &bull; Reporte Consolidado de Inventario</h1>
        <p>Catálogo de artículos, niveles de existencias y precios registrados en el sistema.</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Nombre del Producto</th>
                <th>Categoría</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Stock</th>
                <th>Unidad</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr>
                <td class="font-mono">{{ $product['sku'] }}</td>
                <td><strong>{{ $product['name'] }}</strong></td>
                <td>{{ $product['category'] }}</td>
                <td class="text-right font-mono">${{ $product['price'] }}</td>
                <td class="text-right font-mono">{{ $product['stock'] }}</td>
                <td>{{ $product['unit'] }}</td>
                <td>{{ $product['description'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">
        Generado automáticamente por PuntoÁgil Cloud Platform
    </div>
</body>
</html>
