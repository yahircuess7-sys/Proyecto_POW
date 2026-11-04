@extends('layouts.app')

@section('title', 'Registrar Compra o Abastecimiento - PuntoÁgil')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="purchaseForm()">

    <!-- Top Navigation -->
    <div class="flex items-center justify-between">
        <a href="{{ route('purchases.index') }}" 
           class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="bi bi-arrow-left text-sm"></i>
            <span>Volver a Compras</span>
        </a>

        <div class="text-xs text-slate-400 font-medium">
            Módulo 9 &bull; Compras y Abastecimiento Multi-Tenant
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
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Registro de Compra y Abastecimiento</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Control de órdenes de compra, descuentos, costos y recepción controlada de mercadería en bodega.</p>
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

            <!-- Section 1: Reception Status & Logistics -->
            <div class="p-5 rounded-2xl bg-slate-50/75 border border-slate-200/80 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                        <i class="bi bi-box2 text-emerald-600"></i>
                        <span>Estado de Recepción de Mercancía</span>
                    </h2>
                    <span class="text-[11px] text-slate-400">Control de ingreso a existencias</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Option 1: Received immediately -->
                    <label class="relative flex p-4 rounded-xl border cursor-pointer transition-all"
                           :class="status === 'received' ? 'border-emerald-500 bg-emerald-50/30 ring-2 ring-emerald-500/10 shadow-sm' : 'border-slate-200 bg-white hover:bg-slate-50'">
                        <input type="radio" name="status" value="received" x-model="status" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                        <div class="ml-3">
                            <span class="block text-xs font-bold text-slate-900">Mercancía Recibida en Bodega</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5 leading-snug">
                                La mercadería ya está física y conforme. Se sumará inmediatamente al stock de inventario y se generará movimiento de Kardex.
                            </span>
                        </div>
                    </label>

                    <!-- Option 2: Pending reception -->
                    <label class="relative flex p-4 rounded-xl border cursor-pointer transition-all"
                           :class="status === 'pending' ? 'border-amber-500 bg-amber-50/30 ring-2 ring-amber-500/10 shadow-sm' : 'border-slate-200 bg-white hover:bg-slate-50'">
                        <input type="radio" name="status" value="pending" x-model="status" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                        <div class="ml-3">
                            <span class="block text-xs font-bold text-slate-900">Orden de Compra Pendiente</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5 leading-snug">
                                Pedido emitido al proveedor. <strong>NO alterará el stock</strong> hasta que confirmes la recepción física en el sistema.
                            </span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Section 2: Supplier & Invoice Metadata -->
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
                            Fecha de Operación <span class="text-rose-500">*</span>
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

            <!-- Section 3: Items Table (Quantities, Costs, Line Discounts) -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <i class="bi bi-box-seam"></i>
                        <span>Detalle de Artículos, Costos y Descuentos</span>
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
                                    <th class="py-3 px-4 w-4/12">Producto del Catálogo</th>
                                    <th class="py-3 px-4 w-2/12 text-center">Cantidad</th>
                                    <th class="py-3 px-4 w-2/12 text-right">Costo Unit. ($)</th>
                                    <th class="py-3 px-4 w-2/12 text-right">Descto. Línea ($)</th>
                                    <th class="py-3 px-4 w-2/12 text-right">Subtotal Neto</th>
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

                                        <!-- Line Discount -->
                                        <td class="py-3 px-4">
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-mono text-xs">$</span>
                                                <input type="number" 
                                                       :name="`items[${index}][discount]`" 
                                                       x-model.number="item.discount" 
                                                       @input="calculateTotals()" 
                                                       min="0" 
                                                       step="0.01" 
                                                       class="w-full text-right pl-6 pr-3 py-2 text-xs font-mono rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                                       placeholder="0.00">
                                            </div>
                                        </td>

                                        <!-- Row Subtotal -->
                                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-800">
                                            $<span x-text="Math.max(0, (item.quantity * item.unit_cost) - (parseFloat(item.discount) || 0)).toFixed(2)"></span>
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

            <!-- Section 4: Notes & Financial Totals Summary -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 pt-4 border-t border-slate-100">
                
                <!-- Notes -->
                <div class="md:col-span-7 space-y-4">
                    <div>
                        <label for="notes" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Observaciones / Condiciones Comerciales
                        </label>
                        <textarea name="notes" 
                                  id="notes" 
                                  rows="3" 
                                  placeholder="Ej. Factura a crédito 30 días, mercadería transportada por Encomiendas Express..." 
                                  class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700 placeholder-slate-400">{{ old('notes') }}</textarea>
                    </div>

                    <!-- Global Discount Input -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                        <label for="global_discount" class="block text-xs font-bold text-slate-700 mb-1">
                            Descuento Global Adicional ($)
                        </label>
                        <div class="relative max-w-xs">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-mono text-xs">$</span>
                            <input type="number" 
                                   name="discount" 
                                   id="global_discount" 
                                   x-model.number="globalDiscount" 
                                   @input="calculateTotals()" 
                                   min="0" 
                                   step="0.01" 
                                   placeholder="0.00" 
                                   class="w-full pl-6 pr-3 py-2 text-xs font-mono rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        </div>
                        <span class="text-[11px] text-slate-400 mt-1 block">Aplica sobre el subtotal acumulado de la factura.</span>
                    </div>
                </div>

                <!-- Financial Totals -->
                <div class="md:col-span-5">
                    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3 text-xs">
                        
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Subtotal Bruto:</span>
                            <span class="font-mono font-bold text-slate-800">$<span x-text="grossSubtotal.toFixed(2)"></span></span>
                        </div>

                        <div class="flex items-center justify-between text-rose-600 font-medium">
                            <span>Total Descuentos:</span>
                            <span class="font-mono font-bold">-$<span x-text="totalDiscount.toFixed(2)"></span></span>
                        </div>

                        <div class="flex items-center justify-between text-slate-600">
                            <span>Base Imponible (Neto):</span>
                            <span class="font-mono font-bold text-slate-800">$<span x-text="netSubtotal.toFixed(2)"></span></span>
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
                    <span x-text="isSubmitting ? 'Procesando operación...' : (status === 'received' ? 'Guardar y Cargar a Inventario' : 'Guardar Orden de Compra Pendiente')"></span>
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
        status: '{{ old('status', 'received') }}',
        grossSubtotal: 0,
        globalDiscount: {{ old('discount', 0) }},
        totalDiscount: 0,
        netSubtotal: 0,
        tax: 0,
        total: 0,
        items: [
            { product_id: '', quantity: 1, unit_cost: 0, discount: 0 }
        ],
        addItem() {
            this.items.push({ product_id: '', quantity: 1, unit_cost: 0, discount: 0 });
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
            let sumGross = 0;
            let sumItemDiscounts = 0;

            for (let item of this.items) {
                const qty = parseFloat(item.quantity) || 0;
                const cost = parseFloat(item.unit_cost) || 0;
                const disc = parseFloat(item.discount) || 0;
                sumGross += (qty * cost);
                sumItemDiscounts += disc;
            }

            const globalDisc = parseFloat(this.globalDiscount) || 0;
            this.grossSubtotal = sumGross;
            this.totalDiscount = sumItemDiscounts + globalDisc;
            this.netSubtotal = Math.max(0, this.grossSubtotal - this.totalDiscount);
            this.tax = this.netSubtotal * this.taxRate;
            this.total = this.netSubtotal + this.tax;
        },
        init() {
            this.calculateTotals();
        }
    }
}
</script>
@endsection
