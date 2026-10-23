<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Services\SkuGeneratorService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function __construct(
        protected SkuGeneratorService $skuService
    ) {}

    public function index(Request $request)
    {
        $query = Product::query();

        if ($search = $request->input('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($status = $request->input('status')) {
            if ($status === 'disponible') {
                $query->where('stock', '>', 10);
            } elseif ($status === 'critico') {
                $query->whereBetween('stock', [1, 10]);
            } elseif ($status === 'sin_stock') {
                $query->where('stock', '<=', 0);
            }
        }

        $products = $query->orderBy('category')->paginate(15)->withQueryString();

        return view('inventory.index', compact('products'));
    }

    public function create()
    {
        return view('inventory.form');
    }

    public function store(ProductRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['sku'] = $this->skuService->resolveSku(
            $validatedData['sku'] ?? null,
            $validatedData['name'],
            $validatedData['category']
        );

        try {
            DB::beginTransaction();
            Product::create($validatedData);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'No se pudo guardar el producto. Verifique los datos e intente nuevamente.');
        }

        return redirect()->route('inventory.index')->with('success', 'Producto registrado correctamente.');
    }

    public function show(Product $product)
    {
        return view('inventory.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('inventory.form', compact('product'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $validatedData = $request->validated();
        $validatedData['sku'] = $this->skuService->resolveSku(
            $validatedData['sku'] ?? null,
            $validatedData['name'],
            $validatedData['category'],
            $product->id
        );

        try {
            DB::beginTransaction();
            $product->update($validatedData);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'No se pudo actualizar el producto. Verifique los datos e intente nuevamente.');
        }

        return redirect()->route('inventory.index')->with('success', 'Producto actualizado.');
    }

    public function destroy(Product $product)
    {
        if ($product->saleItems()->exists()) {
            return redirect()->route('inventory.index')
                ->with('error', 'No se puede eliminar este producto porque tiene ventas asociadas.');
        }

        $product->delete();

        return redirect()->route('inventory.index')->with('success', 'Producto eliminado.');
    }

    public function exportPdf()
    {
        $products = Product::orderBy('category')->get()->map(function (Product $product) {
            return [
                'sku' => $this->sanitizePdfValue($product->sku),
                'name' => $this->sanitizePdfValue($product->name),
                'category' => $this->sanitizePdfValue($product->category),
                'price' => number_format($product->price, 2),
                'stock' => $product->stock,
                'unit' => $this->sanitizePdfValue($product->unit),
                'description' => $this->sanitizePdfValue($product->description),
            ];
        });

        $html = view('inventory.export_pdf', compact('products'))->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="inventario_tornillo.pdf"',
        ]);
    }

    private function sanitizePdfValue(?string $value): string
    {
        $value = trim($value ?? '');
        $value = str_replace(["\r\n", "\n", "\r"], ' ', $value);
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function exportCsv()
    {
        $products = Product::orderBy('category')->orderBy('name')->get();
        $handle = fopen('php://temp', 'r+');

        fputs($handle, "\xEF\xBB\xBF");
        fputs($handle, "sep=;\r\n");

        fputcsv($handle, ['SKU', 'Nombre', 'Categoria', 'Precio', 'Stock', 'Unidad', 'Descripcion'], ';', '"');

        foreach ($products as $product) {
            fputcsv($handle, [
                $this->escapeCsvValue($product->sku),
                $this->escapeCsvValue($product->name),
                $this->escapeCsvValue($product->category),
                number_format($product->price, 2, '.', ''),
                $product->stock,
                $this->escapeCsvValue($product->unit),
                $this->escapeCsvValue($product->description),
            ], ';', '"');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="inventario_tornillo.csv"',
            'Pragma' => 'public',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        ]);
    }

    private function escapeCsvValue(?string $value): string
    {
        $value = $value ?? '';
        $value = str_replace(["\\r\\n", "\\n", "\\r", "\r\n", "\n", "\r", "\\t", "\t"], ' ', $value);
        $value = $this->normalizeCsvText($value);
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
