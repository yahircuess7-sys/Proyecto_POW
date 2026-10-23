@extends('layouts.app')

@section('title', $product->name . ' - PuntoÁgil')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Top Navigation & Action -->
    <div class="flex items-center justify-between">
        <a href="{{ route('inventory.index') }}" 
           class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="bi bi-arrow-left text-sm"></i>
            <span>Volver al Inventario</span>
        </a>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('inventory.edit', $product->id) }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 transition-all shadow-sm">
                <i class="bi bi-pencil"></i>
                <span>Editar Ficha</span>
            </a>
        </div>
    </div>

    <!-- Product Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-subtle p-6 sm:p-8">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
            
            <!-- Left: Info -->
            <div class="md:col-span-7 space-y-6">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-block px-2.5 py-1 rounded-lg bg-slate-100 font-mono text-xs font-bold text-slate-700">
                            {{ $product->sku }}
                        </span>
                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                            {{ $product->category }}
                        </span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $product->name }}</h1>
                </div>

                <div class="space-y-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Descripción Técnica</h3>
                    <p class="text-sm text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        {{ $product->description ?: 'No se especificaron notas o detalles técnicos adicionales para este producto.' }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-100">
                    <div>
                        <span class="text-xs text-slate-400 block font-medium">Unidad de Venta</span>
                        <span class="text-sm font-semibold text-slate-800 mt-0.5 block">{{ $product->unit }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block font-medium">Última Actualización</span>
                        <span class="text-sm font-mono text-slate-800 mt-0.5 block">{{ $product->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Right: Stock & Pricing Box -->
            <div class="md:col-span-5">
                <div class="rounded-2xl p-6 bg-slate-50 border border-slate-200/80 space-y-6">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Precio de Lista</span>
                        <div class="text-3xl font-extrabold font-mono text-slate-900 mt-1">
                            ${{ number_format($product->price, 2) }}
                        </div>
                        <span class="text-xs text-slate-500 mt-0.5 block">Precio unitario sugerido en mostrador</span>
                    </div>

                    <div class="pt-4 border-t border-slate-200/80">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Existencias en Almacén</span>
                        <div class="text-2xl font-bold font-mono text-slate-800 mt-1">
                            {{ $product->stock }} <span class="text-sm font-normal text-slate-500 font-sans">{{ $product->unit }}</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-200/80">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block mb-2">Estado Operativo</span>
                        @if($product->stock <= 0)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                Agotado (Sin Stock Disponible)
                            </span>
                        @elseif($product->stock <= 10)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                Stock Crítico (Reabastecer Pronto)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Stock Óptimo en Bodega
                            </span>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
