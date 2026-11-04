@extends('layouts.app')

@section('title', 'Registrar Compra - PuntoÁgil')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="purchaseForm()">

    <!-- Top Navigation -->
    <div class="flex items-center justify-between">
        <a href="{{ route('purchases.index') }}" 
           class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="bi bi-arrow-left text-sm"></i>
            <span>Volver a Compras</span>
        </a>

        <div class="text-xs text-slate-400 font-medium">
            Entrada de mercadería directa a inventario
        </div>
    </div>

    <!-- Main Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
        
        <!-- Header -->
        <div class="p-6 sm:p-8 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 text-lg">
                    <i class="bi bi-cart-plus-fill"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Nueva Orden / Factura de Compra</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Los productos ingresados incrementarán su stock automáticamente y actualizarán su costo unitario.</p>
                </div>
            </div>
        </div>

        @if ($errors->any())
        <div class="p-5 bg-rose-50 border-b border-rose-100 flex items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill text-rose-500 text-base mt-0.5"></i>
            <div class="text-xs text-rose-800 space-y-1">
                <div class="font-bold">Por favor corrige los siguientes inconvenientes antes de continuar:</div>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <form action="{{ route('purchases.store') }}" method="POST" class="p-6 sm:p-8 space-y-8" @submit="isSubmitting = true">
            @csrf

            <!-- Section 1: Supplier & Invoice Metadata -->
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                    <i class="bi bi-building"></i>
                    <span>Información del Proveedor y Factura</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-5">
                    
                    <!-- Supplier Select -->
                    <div class="sm:col-span-6">
                        <label for="supplier_id" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Proveedor Seleccionado <span class="text-rose-500">*</span>
                        </label>
                        <select name="supplier_id" 
                                id="supplier_id" 
                                required
                                class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all bg-white @error('supplier_id') border-rose-300 ring-2 ring-rose-500/20 @enderror">
                            <option value="">-- Seleccionar Proveedor --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ (old('supplier_id', request('supplier_id')) == $supplier->id) ? 'selected' : '' }}>
                                    {{ $supplier->name }} (RUC/NIT: {{ $supplier->tax_id }})
                                </option>
                            @endforeach
                        </select>
                        <div class="mt-1 flex items-center justify-between text-[11px]">
                            <span class="text-slate-400">¿No encuentras el proveedor?</span>
                            <a href="{{ route('suppliers.create') }}" target="_blank" class="text-emerald-600 font-semibold hover:underline">
                                + Registrar Nuevo Proveedor
                            </a>
                        </div>
                    </div>

                    <!-- Supplier Invoice Number -->
                    <div class="sm:col-span-3">
                        <label for="supplier_invoice_number" class="block text-xs font-bold text-slate-700 mb-1.5">
                            N° Factura Proveedor
                        </label>
                        <input type="text" 
                               name="supplier_invoice_number" 
                               id="supplier_invoice_number" 
                               value="{{ old('supplier_invoice_number') }}" 
                               placeholder="Ej. 001-002-0001234"
                               class="w-full px-3.5 py-2.5 text-xs font-mono rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all @error('supplier_invoice_number') border-rose-300 ring-2 ring-rose-500/20 @enderror">
                        <span class="text-[11px] text-slate-400 mt-1 block">Comprobante físico o electrónico</span>
                    </div>

                    <!-- Purchase Date -->
                    <div class="sm:col-span-3">
                        <label for="purchase_date" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Fecha de Compra <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" 
                               name="purchase_date" 
                               id="purchase_date" 
                               required
                               value="{{ old('purchase_date', date('Y-m-d')) }}" 
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all bg-white @error('purchase_date') border-rose-300 ring-2 ring-rose-500/20 @enderror">
                    </div>

                </div>
            </div>

            <!-- Section 2: Items Table -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <i class="bi bi-box-seam"></i>
                        <span>Detalle de Productos Recibidos</span>
                    </h2>
                    
                    <button type="button" 
                            @click="addItem()" 
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-all shadow-sm">
                        <i class="bi bi-plus-circle"></i>
                        <span>Agregar Fila</span>
                    </button>
                </div>

                <div class="border border-slate-200/80 rounded-2xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50/75 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                                    <th class="py-3 px-4 w-5/12">Producto del Catálogo</th>
                                    <th class="py-3 px-4 w-2/12 text-center">Cantidad</th>
                                    <th class="py-3 px-4 w-2/12 text-right">Costo Unitario ($)</th>
                                    <th class="py-3 px-4 w-2/12 text-right">Subtotal</th>
                                    <th class="py-3 px-4 w-1/12 text-center"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(item, index) in items" :key="index">
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <!-- Product Selection -->
                                        <td class="py-3 px-4">
                                            <select :name="`items[${index}][product_id]`" 
                                                    x-model="item.product_id" 
                                                    @change="onProductSelect(index)" 
                                                    required 
                                                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 bg-white">
                                                <option value="">-- Seleccionar Producto --</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" data-cost="{{ $product->cost }}" data-stock="{{ $product->stock }}">
                                                        {{ $product->name }} (Stock: {{ $product->stock }} | Costo: ${{ number_format($product->cost, 2) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <!-- Quantity -->
                                        <td class="py-3 px-4">
                                            <input type="number" 
                                                   :name="`items[${index}][quantity]`" 
                                                   x-model.number="item.quantity" 
                                                   @input="calculateTotals()" 
                                                   min="1" 
                                                   step="1" 
                                                   required 
                                                   class="w-full text-center px-3 py-2 text-xs font-mono font-bold rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                                        </td>

                                        <!-- Unit Cost -->
                                        <td class="py-3 px-4">
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-mono text-xs">$</span>
                                                <input type="number" 
                                                       :name="`items[${index}][unit_cost]`" 
                                                       x-model.number="item.unit_cost" 
                                                       @input="calculateTotals()" 
                                                       min="0" 
                                                       step="0.01" 
                                                       required 
                                                       class="w-full text-right pl-6 pr-3 py-2 text-xs font-mono font-semibold rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                                            </div>
                                        </td>

                                        <!-- Row Subtotal -->
                                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-800">
                                            $<span x-text="(item.quantity * item.unit_cost).toFixed(2)"></span>
                                        </td>

                                        <!-- Delete row -->
                                        <td class="py-3 px-4 text-center">
                                            <button type="button" 
                                                    @click="removeItem(index)" 
                                                    :disabled="items.length === 1" 
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors disabled:opacity-30 disabled:pointer-events-none" 
                                                    title="Eliminar fila">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Section 3: Notes & Totals Summary -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 pt-4 border-t border-slate-100">
                
                <!-- Notes -->
                <div class="md:col-span-7">
                    <label for="notes" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Observaciones / Condiciones de Pago
                    </label>
                    <textarea name="notes" 
                              id="notes" 
                              rows="4" 
                              placeholder="Ej. Factura a crédito 30 días, mercadería recibida conforme en bodega principal..." 
                              class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700 placeholder-slate-400">{{ old('notes') }}</textarea>
                </div>

                <!-- Financial Totals -->
                <div class="md:col-span-5">
                    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3 text-xs">
                        
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Subtotal Bruto:</span>
                            <span class="font-mono font-bold text-slate-800">$<span x-text="subtotal.toFixed(2)"></span></span>
                        </div>

                        <div class="flex items-center justify-between text-slate-600">
                            <span>Impuesto IVA (12%):</span>
                            <span class="font-mono font-bold text-slate-800">$<span x-text="tax.toFixed(2)"></span></span>
                        </div>

                        <div class="pt-3 border-t border-slate-200/80 flex items-center justify-between text-sm">
                            <span class="font-bold text-slate-900">Total Liquidado:</span>
                            <span class="font-mono text-xl font-extrabold text-emerald-600">$<span x-text="total.toFixed(2)"></span></span>
                        </div>

                    </div>
                </div>

            </div>

            <!-- Footer Buttons -->
            <div class="pt-6 border-t border-slate-100 flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
                <a href="{{ route('purchases.index') }}" 
                   class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 text-center transition-colors">
                    Cancelar
                </a>

                <button type="submit" 
                        :disabled="isSubmitting || items.length === 0" 
                        class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm shadow-emerald-500/20 transition-all flex items-center justify-center gap-2 disabled:opacity-50">
                    <i class="bi bi-check-lg text-base"></i>
                    <span x-text="isSubmitting ? 'Procesando entrada...' : 'Confirmar e Ingresar a Inventario'"></span>
                </button>
            </div>

        </form>
    </div>

</div>

<script>
function purchaseForm() {
    return {
        isSubmitting: false,
        taxRate: 0.12,
        subtotal: 0,
        tax: 0,
        total: 0,
        items: [
            { product_id: '', quantity: 1, unit_cost: 0 }
        ],
        addItem() {
            this.items.push({ product_id: '', quantity: 1, unit_cost: 0 });
        },
        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
                this.calculateTotals();
            }
        },
        onProductSelect(index) {
            const select = event.target;
            const selectedOption = select.options[select.selectedIndex];
            if (selectedOption && selectedOption.dataset.cost) {
                this.items[index].unit_cost = parseFloat(selectedOption.dataset.cost) || 0;
            }
            this.calculateTotals();
        },
        calculateTotals() {
            let sum = 0;
            for (let item of this.items) {
                const qty = parseFloat(item.quantity) || 0;
                const cost = parseFloat(item.unit_cost) || 0;
                sum += qty * cost;
            }
            this.subtotal = sum;
            this.tax = sum * this.taxRate;
            this.total = this.subtotal + this.tax;
        },
        init() {
            this.calculateTotals();
        }
    }
}
</script>
@endsection
