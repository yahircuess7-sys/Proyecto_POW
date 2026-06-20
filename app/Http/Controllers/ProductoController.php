<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::all();
        return view('inventario.index', compact('productos'));
    }

    public function create()
    {
        return view('inventario.form');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'sku' => 'required|unique:productos|max:255',
            'nombre' => 'required|max:255',
            'categoria' => 'required',
            'precio' => 'required|numeric',
            'stock' => 'required|integer',
            'unidad' => 'required'
        ]);

        Producto::create($validatedData);

        return redirect()->route('inventario.index')->with('success', 'Producto registrado correctamente.');
    }

    public function edit(Producto $producto)
    {
        return view('inventario.form', compact('producto'));
    }

    public function update(Request $request, Producto $producto)
    {
        $validatedData = $request->validate([
            'sku' => 'required|max:255|unique:productos,sku,' . $producto->id,
            'nombre' => 'required|max:255',
            'categoria' => 'required',
            'precio' => 'required|numeric',
            'stock' => 'required|integer',
            'unidad' => 'required'
        ]);

        $producto->update($validatedData);

        return redirect()->route('inventario.index')->with('success', 'Producto actualizado.');
    }

    public function destroy(Producto $producto)
    {
        $producto->delete();
        return redirect()->route('inventario.index')->with('success', 'Producto eliminado.');
    }
}
