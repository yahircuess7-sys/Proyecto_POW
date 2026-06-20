<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    public function index()
    {
        $ventas = Venta::with('user')->orderBy('created_at', 'desc')->get();
        return view('ventas.index', compact('ventas'));
    }

    public function create()
    {
        $productos = Producto::where('stock', '>', 0)->get();
        return view('pos.index', compact('productos')); // Vista del POS
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'detalles' => 'required|array',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $subtotal = 0;

            foreach ($request->detalles as $item) {
                $subtotal += $item['cantidad'] * $item['precio_unitario'];
            }

            $tax = $subtotal * 0.12; // IVA 12%
            $total = $subtotal + $tax;

            $venta = Venta::create([
                'numero_factura' => 'FACT-' . time(),
                'user_id' => Auth::id() ?? 1, // Usuario por defecto en demo
                'subtotal' => $subtotal,
                'impuesto' => $tax,
                'total' => $total,
            ]);

            foreach ($request->detalles as $item) {
                $producto = Producto::find($item['producto_id']);
                
                if ($producto->stock < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para: " . $producto->nombre);
                }

                $producto->stock -= $item['cantidad'];
                $producto->save();

                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['cantidad'] * $item['precio_unitario'],
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'venta_id' => $venta->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function show(Venta $venta)
    {
        $venta->load(['detalles.product', 'user']);
        return view('ventas.show', compact('venta'));
    }

    public function reports()
    {
        return view('admin.reportes');
    }
}
