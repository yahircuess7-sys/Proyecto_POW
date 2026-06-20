<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario - Ferretería El Tornillo</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 4px 0 0; font-size: 12px; color: #555; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Inventario - Ferretería El Tornillo</h1>
        <p>Reporte de stock actual y precios de venta sugeridos.</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Unidad</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr>
                <td>{{ $product['sku'] }}</td>
                <td>{{ $product['name'] }}</td>
                <td>{{ $product['category'] }}</td>
                <td>{{ $product['price'] }}</td>
                <td>{{ $product['stock'] }}</td>
                <td>{{ $product['unit'] }}</td>
                <td>{{ $product['description'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
