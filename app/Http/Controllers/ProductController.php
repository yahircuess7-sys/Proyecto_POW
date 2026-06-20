<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $query = Product::query();

        if ($search = request('search')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($category = request('category')) {
            $query->where('category', $category);
        }

        if ($status = request('status')) {
            if ($status === 'disponible') {
                $query->where('stock', '>', 10);
            } elseif ($status === 'critico') {
                $query->whereBetween('stock', [1, 10]);
            } elseif ($status === 'sin_stock') {
                $query->where('stock', '<=', 0);
            }
        }

        $products = $query->orderBy('category')->get();

        return view('inventory.index', compact('products'));
    }

    public function create()
    {
        return view('inventory.form');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'sku' => ['nullable', 'max:255', 'alpha_dash', Rule::unique('products', 'sku')],
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'unit' => 'required|string|max:100'
        ]);

        $validatedData['sku'] = $this->resolveSku($validatedData['sku'] ?? null, $validatedData['name'], $validatedData['category']);

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

    public function update(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'sku' => ['nullable', 'max:255', 'alpha_dash', Rule::unique('products', 'sku')->ignore($product->id)],
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'unit' => 'required|string|max:100'
        ]);

        $validatedData['sku'] = $this->resolveSku($validatedData['sku'] ?? null, $validatedData['name'], $validatedData['category'], $product->id);

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
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return $value;
    }

    private function resolveSku(?string $sku, string $name, string $category, ?int $productId = null): string
    {
        $sku = trim((string) ($sku ?? ''));

        if ($sku === '' || !preg_match('/^[A-Z]{2,}-\d{2,}$/', strtoupper($sku))) {
            return $this->generateProductSku($name, $category, $productId);
        }

        return $this->normalizeSku($sku);
    }

    private function generateProductSku(string $name, string $category, ?int $productId = null): string
    {
        $prefix = $this->skuPrefixFromCategoryOrName($name, $category);
        $prefix = strtoupper($this->normalizeSku($prefix));

        $existingSkus = Product::where('sku', 'like', "$prefix-%")
            ->when($productId, fn($query) => $query->where('id', '<>', $productId))
            ->pluck('sku')
            ->toArray();

        $nextNumber = 1;
        while (in_array(sprintf('%s-%03d', $prefix, $nextNumber), $existingSkus, true)) {
            $nextNumber++;
        }

        return sprintf('%s-%03d', $prefix, $nextNumber);
    }

    private function skuPrefixFromCategoryOrName(string $name, string $category): string
    {
        $keywords = [
            'cemento' => 'CEM',
            'clavo' => 'CLA',
            'tornillo' => 'TOR',
            'herramienta' => 'HER',
            'eléctrica' => 'ELE',
            'electrica' => 'ELE',
            'plomería' => 'PLO',
            'plomeria' => 'PLO',
            'hidráulica' => 'HID',
            'hidraulica' => 'HID',
            'pintura' => 'PIN',
            'alambre' => 'ALM',
            'madera' => 'MAD',
            'abrasivo' => 'ABR',
            'cementos' => 'CEM',
            'pinturas' => 'PIN',
        ];

        $searchText = mb_strtolower($name . ' ' . $category);
        foreach ($keywords as $term => $code) {
            if (str_contains($searchText, $term)) {
                return $code;
            }
        }

        $words = preg_split('/\s+/', preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name));
        $words = array_filter($words);

        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 3) . substr($words[1], 0, 1));
        }

        $first = $words[0] ?? $category;
        return strtoupper(substr($first, 0, 3));
    }

    private function normalizeSku(string $sku): string
    {
        $sku = strtoupper($sku);
        $sku = str_replace([' ', '_'], '-', $sku);
        $sku = preg_replace('/[^A-Z0-9\-]/', '', $sku);
        return preg_replace('/-+/', '-', trim($sku, '-'));
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
        $value = str_replace('"', '""', $value);
        return $value;
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

        $value = preg_replace('/\s+/', ' ', $value);

        return $value;
    }
}
