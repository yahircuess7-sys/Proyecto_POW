<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Dompdf\Dompdf;
use Dompdf\Options;

class SaleController extends Controller
{
    public function index()
    {
        $products = Product::where('stock', '>', 0)->get();
        return view('sales.index', compact('products'));
    }

    public function create()
    {
        return $this->index();
    }

    public function store(StoreSaleRequest $request)
    {
        $items = $request->validated()['items'];

        DB::beginTransaction();

        try {
            $subtotal = 0;

            foreach ($items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $tax = $subtotal * 0.12; // IVA 12%
            $total = $subtotal + $tax;

            $sale = Sale::create([
                'invoice_number' => 'INV-' . time(),
                'user_id' => Auth::id() ?: 1,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ]);

            foreach ($items as $item) {
                $product = Product::find($item['product_id']);

                if (! $product) {
                    throw new \Exception("Producto no autorizado o no encontrado en el inventario de este negocio.");
                }

                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Stock insuficiente para: " . $product->name);
                }

                $product->stock -= $item['quantity'];
                $product->save();

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'sale_id' => $sale->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function show(Sale $sale)
    {
        $sale->load(['items.product', 'user']);
        return view('sales.show', compact('sale'));
    }

    public function exportPdf(Sale $sale)
    {
        $sale->load(['items.product', 'user']);

        $customerName = $this->normalizeText(request()->input('customer_name', 'Consumidor Final'));
        $customerDocument = $this->normalizeText(request()->input('customer_document', '---'));

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('sales.invoice_pdf', compact('sale', 'customerName', 'customerDocument'))->render());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="boleta_' . $sale->invoice_number . '.pdf"',
        ]);
    }

    private function normalizeText(string $value): string
    {
        $value = trim($value);

        if (function_exists('transliterator_transliterate')) {
            $value = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9 ] remove', $value);
        } else {
            $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
            $value = preg_replace('/[^A-Za-z0-9 ]/', '', $value);
        }

        $value = preg_replace('/\s+/', ' ', $value);

        return $value === '' ? 'Consumidor Final' : $value;
    }

    public function reports()
    {
        $transactions = Sale::with('user')->withCount('items')->orderByDesc('created_at')->take(12)->get();
        $dailyTotal = Sale::whereDate('created_at', now())->sum('total');
        $invoicesCount = Sale::whereDate('created_at', now())->count();
        $averageTicket = $invoicesCount ? Sale::whereDate('created_at', now())->avg('total') : 0;

        return view('admin.reports', compact('transactions', 'dailyTotal', 'invoicesCount', 'averageTicket'));
    }

    public function exportReports()
    {
        $sales = Sale::with('user')->withCount('items')->orderByDesc('created_at')->get();

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fwrite($handle, "sep=;\r\n");

        fputcsv($handle, ['Nro Recibo', 'Fecha y Hora', 'Atendido Por', 'Items', 'Subtotal', 'Impuesto', 'Total'], ';', '"');

        foreach ($sales as $sale) {
            fputcsv($handle, [
                $this->escapeCsvValue($sale->invoice_number),
                $this->escapeCsvValue($sale->created_at->format('Y-m-d H:i:s')),
                $this->escapeCsvValue($sale->user->name ?? 'Cajero'),
                $sale->items_count,
                number_format($sale->subtotal, 2, '.', ''),
                number_format($sale->tax, 2, '.', ''),
                number_format($sale->total, 2, '.', ''),
            ], ';', '"');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="reportes_transacciones.csv"',
            'Pragma' => 'public',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        ]);
    }

    private function escapeCsvValue(?string $value): string
    {
        $value = $this->normalizeCsvText($value);
        $value = str_replace('"', '""', $value);
        $value = str_replace(["\r\n", "\n", "\r", '\\r\\n', '\\n', '\\r'], ' ', $value);
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
