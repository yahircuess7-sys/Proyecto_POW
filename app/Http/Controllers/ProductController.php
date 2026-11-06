<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use App\Services\SkuGeneratorService;
use App\Services\TenantManager;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        protected SkuGeneratorService $skuService,
        protected InventoryService $inventoryService,
        protected TenantManager $tenantManager
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);

        $query = Product::query();

        // Búsqueda por nombre, SKU, código de barras o descripción
        if ($search = $request->input('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtro por categoría
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        // Filtro por nivel de existencias
        if ($stockFilter = $request->input('stock_status')) {
            if ($stockFilter === 'disponible') {
                $query->whereColumn('stock', '>', 'min_stock');
            } elseif ($stockFilter === 'critico') {
                $query->where('stock', '>', 0)->whereColumn('stock', '<=', 'min_stock');
            } elseif ($stockFilter === 'sin_stock') {
                $query->where('stock', '<=', 0);
            }
        }

        // Filtro por estado activo/inactivo
        if ($statusFilter = $request->input('status')) {
            $query->where('status', $statusFilter);
        }

        // Obtener categorías únicas presentes en la empresa para el filtro
        $availableCategories = Product::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $products = $query->orderBy('category')->orderBy('name')->paginate(15)->withQueryString();

        // Resumen KPI para el encabezado
        $kpi = [
            'total_items' => Product::count(),
            'active_items' => Product::where('status', Product::STATUS_ACTIVE)->count(),
            'critical_stock' => Product::where('stock', '>', 0)->whereColumn('stock', '<=', 'min_stock')->count(),
            'out_of_stock' => Product::where('stock', '<=', 0)->count(),
            'inventory_value' => (float) Product::selectRaw('SUM(stock * price) as val')->value('val') ?? 0,
        ];

        return view('inventory.index', compact('products', 'availableCategories', 'kpi'));
    }

    public function create(): View
    {
        Gate::authorize('create', Product::class);

        return view('inventory.form');
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        Gate::authorize('create', Product::class);

        $actor = Auth::user();
        $business = $this->tenantManager->getTenant() ?: $actor?->currentBusiness;

        if ($business && ! $business->canAddProduct() && ! $actor?->isSuperAdmin()) {
            $planName = $business->currentPlan()?->name ?? 'actual';
            return redirect()->back()
                ->withInput()
                ->with('error', "Has alcanzado el límite máximo de productos de tu plan ({$planName}). Actualiza tu suscripción para ampliar el catálogo.");
        }

        $validatedData = $request->validated();
        $validatedData['sku'] = $this->skuService->resolveSku(
            $validatedData['sku'] ?? null,
            $validatedData['name'],
            $validatedData['category']
        );

        $initialStock = (int) ($validatedData['stock'] ?? 0);
        $validatedData['stock'] = 0; // Se actualiza y audita a través de recordInitialStock
        $validatedData['cost'] = (float) ($validatedData['cost'] ?? 0.00);
        $validatedData['min_stock'] = (int) ($validatedData['min_stock'] ?? 5);
        $validatedData['status'] = $validatedData['status'] ?? Product::STATUS_ACTIVE;

        try {
            DB::beginTransaction();

            $product = Product::create($validatedData);

            if ($initialStock > 0) {
                $this->inventoryService->recordInitialStock($product, $initialStock, Auth::user());
            }

            AuditLogger::record(
                'product.created',
                "Se registró el producto {$product->name} (SKU: {$product->sku}) con precio C$ {$product->price} y stock inicial de {$initialStock}.",
                $product,
                [
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'cost' => $product->cost,
                    'initial_stock' => $initialStock,
                ],
                $product->business_id
            );

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'No se pudo guardar el producto: ' . $exception->getMessage());
        }

        return redirect()->route('inventory.index')->with('success', 'Producto registrado correctamente.');
    }

    public function show(Product $product): View
    {
        Gate::authorize('view', $product);

        $product->load(['movements.user', 'saleItems']);

        return view('inventory.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        Gate::authorize('update', $product);

        return view('inventory.form', compact('product'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        $validatedData = $request->validated();
        $validatedData['sku'] = $this->skuService->resolveSku(
            $validatedData['sku'] ?? null,
            $validatedData['name'],
            $validatedData['category'],
            $product->id
        );

        // El stock no se altera directamente desde la edición simple para preservar la trazabilidad
        unset($validatedData['stock']);

        try {
            DB::beginTransaction();
            $product->update($validatedData);

            AuditLogger::record(
                'product.updated',
                "Se actualizaron los datos técnicos y comerciales de {$product->name} (SKU: {$product->sku}).",
                $product,
                [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'cost' => $product->cost,
                    'min_stock' => $product->min_stock,
                    'status' => $product->status,
                ],
                $product->business_id
            );

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'No se pudo actualizar el producto. Verifique los datos e intente nuevamente.');
        }

        return redirect()->route('inventory.index')->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Edición rápida en línea desde el listado (Precio Venta, Costo, Stock Mínimo).
     */
    public function quickUpdate(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $product);

        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'min_stock' => ['required', 'integer', 'min:0', 'max:999999'],
        ], [
            'price.required' => 'El precio de venta es obligatorio.',
            'price.min' => 'El precio de venta no puede ser negativo.',
            'min_stock.required' => 'El stock mínimo es obligatorio.',
        ]);

        $previous = [
            'price' => (float) $product->price,
            'cost' => (float) $product->cost,
            'min_stock' => (int) $product->min_stock,
        ];

        $product->update([
            'price' => round((float) $validated['price'], 2),
            'cost' => isset($validated['cost']) ? round((float) $validated['cost'], 2) : $product->cost,
            'min_stock' => (int) $validated['min_stock'],
        ]);

        AuditLogger::record(
            'product.quick_updated',
            "Edición rápida de {$product->name} (SKU: {$product->sku}): PVP C$ " . number_format((float) $product->price, 2) . ", Costo C$ " . number_format((float) $product->cost, 2) . ", Stock Mín. {$product->min_stock}.",
            $product,
            [
                'previous' => $previous,
                'updated' => [
                    'price' => (float) $product->price,
                    'cost' => (float) $product->cost,
                    'min_stock' => (int) $product->min_stock,
                ],
            ],
            $product->business_id
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Producto «{$product->name}» actualizado al instante.",
                'product' => [
                    'id' => $product->id,
                    'price' => (float) $product->price,
                    'cost' => (float) $product->cost,
                    'min_stock' => (int) $product->min_stock,
                    'stock' => (int) $product->stock,
                    'stock_status' => $product->stockStatus(),
                ],
            ]);
        }

        return redirect()->back()->with('success', "Precio y stock mínimo de {$product->name} actualizados.");
    }

    /**
     * Activa o desactiva un producto del catálogo sin eliminar su historial.
     */
    public function toggleStatus(Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        $previousStatus = $product->status;
        $newStatus = $previousStatus === Product::STATUS_ACTIVE ? Product::STATUS_INACTIVE : Product::STATUS_ACTIVE;

        $product->update(['status' => $newStatus]);

        AuditLogger::record(
            'product.status_toggled',
            "Se cambió el estado del producto {$product->name} (SKU: {$product->sku}) de {$previousStatus} a {$newStatus}.",
            $product,
            ['previous_status' => $previousStatus, 'new_status' => $newStatus],
            $product->business_id
        );

        $statusLabel = $newStatus === Product::STATUS_ACTIVE ? 'activado' : 'desactivado';

        return redirect()->back()->with('success', "El producto {$product->name} ha sido {$statusLabel} correctamente.");
    }

    /**
     * Registra un ajuste de stock manual (entrada, salida o inventario físico) con motivo obligatorio.
     */
    public function adjustStock(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        $validated = $request->validate([
            'type' => ['required', 'in:in,out,adjustment'],
            'amount' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'type.required' => 'El tipo de movimiento es obligatorio.',
            'amount.required' => 'La cantidad o nuevo stock es obligatorio.',
            'reason.required' => 'Debe indicar el motivo o justificación del ajuste.',
            'reason.min' => 'El motivo debe tener al menos 3 caracteres.',
        ]);

        try {
            $movement = $this->inventoryService->adjustStock(
                $product,
                $validated['type'],
                (int) $validated['amount'],
                $validated['reason'],
                Auth::user()
            );

            return redirect()->back()->with(
                'success',
                "Movimiento registrado exitosamente. Stock actualizado de {$movement->previous_stock} a {$movement->new_stock} {$product->unit}."
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Ocurrió un error al procesar el ajuste: ' . $e->getMessage());
        }
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        if ($product->saleItems()->exists()) {
            return redirect()->route('inventory.index')
                ->with('error', 'No se puede eliminar este producto porque tiene ventas asociadas. Puedes desactivarlo para que no aparezca en el POS.');
        }

        $productName = $product->name;
        $sku = $product->sku;
        $businessId = $product->business_id;

        DB::transaction(function () use ($product, $productName, $sku, $businessId) {
            $product->movements()->delete();
            $product->delete();

            AuditLogger::record(
                'product.deleted',
                "Se eliminó el producto {$productName} (SKU: {$sku}).",
                null,
                ['sku' => $sku],
                $businessId
            );
        });

        return redirect()->route('inventory.index')->with('success', 'Producto eliminado correctamente.');
    }

    /**
     * Descarga plantilla CSV lista para abrir y rellenar en Microsoft Excel.
     */
    public function downloadTemplate(): Response
    {
        Gate::authorize('create', Product::class);

        $handle = fopen('php://temp', 'r+');
        fputs($handle, "\xEF\xBB\xBF");
        fputs($handle, "sep=;\r\n");

        fputcsv($handle, [
            'Nombre',
            'Codigo_SKU',
            'Categoria',
            'Precio_Compra',
            'Precio_Venta',
            'Stock_Inicial',
            'Stock_Minimo',
            'Codigo_Barras',
            'Unidad',
        ], ';', '"');

        $examples = [
            ['Martillo de Uña 16oz', 'HER-001', 'Herramientas', '180.00', '285.00', '15', '5', '750100010001', 'Unidades'],
            ['Cemento Gris 42.5Kg', 'CON-001', 'Construcción', '340.00', '410.00', '40', '10', '750100010002', 'Bolsas'],
            ['Tubo PVC Agua Fría 1/2"', 'PLO-001', 'Plomería', '75.00', '120.00', '25', '8', '750100010003', 'Unidades'],
        ];

        foreach ($examples as $row) {
            fputcsv($handle, $row, ';', '"');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="plantilla_inventario_puntoagil.csv"',
            'Pragma' => 'public',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        ]);
    }

    /**
     * Importación masiva de productos desde archivo Excel/CSV.
     */
    public function importCsv(Request $request): RedirectResponse
    {
        Gate::authorize('create', Product::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ], [
            'file.required' => 'Debe seleccionar un archivo CSV para importar.',
            'file.mimes' => 'El archivo debe tener formato CSV (.csv, .txt). Si tienes un Excel, guárdalo como "CSV UTF-8 (delimitado por comas)".',
            'file.max' => 'El archivo no debe exceder 4MB.',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $handle = fopen($path, 'r');
        if (! $handle) {
            return redirect()->back()->with('error', 'No se pudo leer el archivo cargado.');
        }

        // Detectar directiva sep= de Excel y delimitador real (, o ;)
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return redirect()->back()->with('error', 'El archivo CSV está vacío.');
        }

        $cleanFirst = trim(str_replace("\xEF\xBB\xBF", '', $firstLine));
        $delimiter = ',';

        if (stripos($cleanFirst, 'sep=') === 0) {
            $sepChar = substr($cleanFirst, 4, 1);
            if (in_array($sepChar, [';', ',', "\t"], true)) {
                $delimiter = $sepChar;
            }
            $headerLinePos = ftell($handle);
            $peekHeader = fgets($handle) ?: '';
            if (! in_array($sepChar ?? '', [';', ',', "\t"], true)) {
                $delimiter = substr_count($peekHeader, ';') >= substr_count($peekHeader, ',') ? ';' : ',';
            }
            fseek($handle, $headerLinePos);
        } else {
            $delimiter = substr_count($cleanFirst, ';') > substr_count($cleanFirst, ',') ? ';' : ',';
            rewind($handle);
        }

        $header = fgetcsv($handle, 0, $delimiter);
        if (! $header) {
            fclose($handle);
            return redirect()->back()->with('error', 'El archivo CSV está vacío.');
        }

        // Normalizar encabezados (sin acentos, minúsculas, espacios -> _)
        $headerMap = [];
        foreach ($header as $idx => $col) {
            $colClean = str_replace(["\xEF\xBB\xBF", '"', "'"], '', (string) $col);
            $colClean = strtr(mb_strtolower(trim($colClean), 'UTF-8'), [
                'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
            ]);
            $colClean = preg_replace('/[\s\/\-]+/', '_', $colClean);
            $headerMap[$colClean] = $idx;
        }

        $resolveCol = function (array $aliases) use ($headerMap): ?int {
            foreach ($aliases as $alias) {
                if (array_key_exists($alias, $headerMap)) {
                    return $headerMap[$alias];
                }
            }
            return null;
        };

        $nameIdx = $resolveCol(['nombre', 'name', 'producto', 'articulo', 'material']);
        $catIdx = $resolveCol(['categoria', 'category', 'familia']);
        $priceIdx = $resolveCol(['precio_venta', 'precio', 'price', 'pvp', 'valor_venta']);
        $costIdx = $resolveCol(['precio_compra', 'costo', 'cost', 'costo_compra', 'costo_unitario']);
        $stockIdx = $resolveCol(['stock_inicial', 'stock', 'existencias', 'cantidad']);
        $minStockIdx = $resolveCol(['stock_minimo', 'min_stock', 'minimo']);
        $skuIdx = $resolveCol(['codigo_sku', 'sku', 'codigo', 'referencia']);
        $barcodeIdx = $resolveCol(['codigo_barras', 'codigo_de_barras', 'barcode', 'ean', 'upc']);
        $unitIdx = $resolveCol(['unidad', 'unit', 'presentacion']);
        $descIdx = $resolveCol(['descripcion', 'description', 'notas']);

        if ($nameIdx === null || $priceIdx === null) {
            fclose($handle);
            return redirect()->back()->with(
                'error',
                'El archivo CSV debe incluir al menos las columnas Nombre y Precio_Venta (o Precio). Descarga la plantilla de ejemplo con 1 clic para ver el formato exacto.'
            );
        }

        $parseMoney = function (?string $raw): float {
            $clean = str_ireplace(['c$', '$', 'nio', ' '], '', trim((string) $raw));
            if (str_contains($clean, ',') && str_contains($clean, '.')) {
                $clean = str_replace(',', '', $clean);
            } elseif (str_contains($clean, ',')) {
                $clean = str_replace(',', '.', $clean);
            }
            return is_numeric($clean) ? (float) $clean : 0.0;
        };

        $importedCount = 0;
        $rowNum = 1;
        $errors = [];

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNum++;

                $name = trim((string) ($row[$nameIdx] ?? ''));
                if ($name === '') {
                    continue; // Saltar líneas en blanco
                }

                $category = $catIdx !== null ? (trim((string) ($row[$catIdx] ?? '')) ?: 'General') : 'General';
                $price = $parseMoney($row[$priceIdx] ?? '0');
                $cost = $costIdx !== null ? $parseMoney($row[$costIdx] ?? '0') : 0.00;
                $stock = $stockIdx !== null ? (int) round($parseMoney($row[$stockIdx] ?? '0')) : 0;
                $minStock = $minStockIdx !== null && trim((string) ($row[$minStockIdx] ?? '')) !== ''
                    ? (int) round($parseMoney($row[$minStockIdx]))
                    : 5;
                $unit = $unitIdx !== null ? (trim((string) ($row[$unitIdx] ?? '')) ?: 'Unidades') : 'Unidades';
                $description = $descIdx !== null ? (trim((string) ($row[$descIdx] ?? '')) ?: null) : null;
                $barcode = $barcodeIdx !== null ? (trim((string) ($row[$barcodeIdx] ?? '')) ?: null) : null;
                $sku = $skuIdx !== null ? (trim((string) ($row[$skuIdx] ?? '')) ?: null) : null;

                if ($price < 0) {
                    $errors[] = "Fila {$rowNum}: Precio no válido.";
                    continue;
                }

                $resolvedSku = $this->skuService->resolveSku($sku, $name, $category);

                $product = Product::create([
                    'sku' => $resolvedSku,
                    'barcode' => $barcode,
                    'name' => $name,
                    'category' => $category,
                    'description' => $description,
                    'cost' => max(0, $cost),
                    'price' => max(0, $price),
                    'stock' => 0,
                    'min_stock' => max(0, $minStock),
                    'unit' => $unit,
                    'status' => Product::STATUS_ACTIVE,
                ]);

                if ($stock > 0) {
                    $this->inventoryService->recordInitialStock($product, $stock, Auth::user());
                }

                $importedCount++;
            }

            fclose($handle);

            AuditLogger::record(
                'inventory.imported_csv',
                "Se importaron {$importedCount} productos desde archivo CSV.",
                null,
                ['count' => $importedCount, 'filename' => $file->getClientOriginalName()],
                Auth::user()->currentBusinessId()
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            return redirect()->back()->with('error', "Error al procesar la fila {$rowNum}: " . $e->getMessage());
        }

        $message = "Se importaron exitosamente {$importedCount} productos al inventario.";
        if (! empty($errors)) {
            $message .= " Se omitieron " . count($errors) . " filas con datos incompletos.";
        }

        return redirect()->route('inventory.index')->with('success', $message);
    }

    public function exportPdf(): Response
    {
        Gate::authorize('export', Product::class);

        $products = Product::orderBy('category')->orderBy('name')->get()->map(function (Product $product) {
            return [
                'sku' => $this->sanitizePdfValue($product->sku),
                'barcode' => $this->sanitizePdfValue($product->barcode),
                'name' => $this->sanitizePdfValue($product->name),
                'category' => $this->sanitizePdfValue($product->category),
                'cost' => number_format((float) $product->cost, 2),
                'price' => number_format((float) $product->price, 2),
                'stock' => $product->stock,
                'min_stock' => $product->min_stock,
                'unit' => $this->sanitizePdfValue($product->unit),
                'status' => $product->isActive() ? 'Activo' : 'Inactivo',
                'description' => $this->sanitizePdfValue($product->description),
            ];
        });

        $html = view('inventory.export_pdf', compact('products'))->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="inventario_puntoagil.pdf"',
        ]);
    }

    private function sanitizePdfValue(?string $value): string
    {
        $value = trim($value ?? '');
        $value = str_replace(["\r\n", "\n", "\r"], ' ', $value);
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function exportCsv(): Response
    {
        Gate::authorize('export', Product::class);

        $products = Product::orderBy('category')->orderBy('name')->get();
        $handle = fopen('php://temp', 'r+');

        fputs($handle, "\xEF\xBB\xBF");
        fputs($handle, "sep=;\r\n");

        fputcsv($handle, [
            'SKU',
            'Codigo_Barras',
            'Nombre',
            'Categoria',
            'Costo',
            'Precio',
            'Stock',
            'Stock_Minimo',
            'Unidad',
            'Estado',
            'Descripcion',
        ], ';', '"');

        foreach ($products as $product) {
            fputcsv($handle, [
                $this->escapeCsvValue($product->sku),
                $this->escapeCsvValue($product->barcode),
                $this->escapeCsvValue($product->name),
                $this->escapeCsvValue($product->category),
                number_format((float) $product->cost, 2, '.', ''),
                number_format((float) $product->price, 2, '.', ''),
                $product->stock,
                $product->min_stock,
                $this->escapeCsvValue($product->unit),
                $product->isActive() ? 'Activo' : 'Inactivo',
                $this->escapeCsvValue($product->description),
            ], ';', '"');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="inventario_puntoagil.csv"',
            'Pragma' => 'public',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        ]);
    }

    private function escapeCsvValue(?string $value): string
    {
        $value = $value ?? '';
        $value = str_replace(["\\r\\n", "\\n", "\\r", "\r\n", "\n", "\r", "\\t", "\t"], ' ', $value);
        $value = $this->normalizeCsvText($value);
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            $value = "'" . $value;
        }
        return str_replace('"', '""', $value);
    }

    private function normalizeCsvText(?string $value): string
    {
        $value = trim($value ?? '');

        if (function_exists('transliterator_transliterate')) {
            $value = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9 \-\.,;:\/_() ] remove', $value);
        } else {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
            $value = preg_replace('/[^A-Za-z0-9 \-\.,;:\/_() ]/', '', $value);
        }

        return preg_replace('/\s+/', ' ', $value);
    }
}
