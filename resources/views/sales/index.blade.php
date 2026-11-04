@extends('layouts.app')

@section('title', 'Punto de Venta (POS) - PuntoÁgil')

@section('content')
<div class="space-y-4">
    
    <!-- Aviso de Validez Operativa Interna (Requisito Módulo 10 & 12) -->
    <div class="px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs flex items-center justify-between gap-3 shadow-subtle">
        <div class="flex items-center gap-2">
            <i class="bi bi-info-circle-fill text-amber-600 text-sm"></i>
            <span><strong>Control Operativo:</strong> Los comprobantes emitidos en este terminal son para control administrativo y gestión interna sin validez fiscal ni certificación tributaria oficial.</span>
        </div>
        <div class="hidden sm:flex items-center gap-2 text-[11px] font-mono text-amber-800">
            <span class="px-1.5 py-0.5 rounded bg-white border border-amber-200 font-bold">F2 Buscar</span>
            <span class="px-1.5 py-0.5 rounded bg-white border border-amber-200 font-bold">F4 Cobrar</span>
            <span class="px-1.5 py-0.5 rounded bg-white border border-amber-200 font-bold">Esc Limpiar</span>
        </div>
    </div>

    <!-- Header & Cash Register Status Banner -->
    <div class="animate-fade-up flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                <span>Terminal Punto de Venta</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500 text-slate-950 font-mono">POS</span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Venta rápida, control de stock concurrente y arqueo de turno integrado.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($activeShift)
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-900 shadow-subtle">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span><strong>{{ $activeShift->cashRegister->name ?? 'Caja' }}</strong> (Turno #{{ $activeShift->id }})</span>
                    <span class="text-slate-400">|</span>
                    <span>Fondo: <strong>${{ number_format($activeShift->initial_cash, 2) }}</strong></span>
                    <a href="{{ route('cash.current') }}" class="ml-1 text-emerald-700 hover:text-emerald-800 underline font-semibold text-[11px]">Ver Caja</a>
                </div>
            @else
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-800 shadow-subtle">
                    <i class="bi bi-exclamation-triangle-fill text-rose-600"></i>
                    <span>Sin turno de caja abierto</span>
                    <a href="{{ route('cash.current') }}" class="ml-2 px-2.5 py-1 rounded-lg bg-rose-600 text-white font-bold hover:bg-rose-700 transition-colors text-[11px]">
                        Abrir Turno
                    </a>
                </div>
            @endif

            <span class="px-2.5 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-semibold text-slate-800 shadow-subtle">
                <i class="bi bi-person-fill text-emerald-600 me-1"></i> {{ auth()->user()->name ?? 'Cajero' }}
            </span>
        </div>
    </div>

    <!-- Main Two-Column POS Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        
        <!-- Left Panel: Product Catalog (7 cols) -->
        <div class="animate-fade-up stagger-1 lg:col-span-7 xl:col-span-8 space-y-3">
            
            <!-- Search & Filters Bar -->
            <div class="p-3.5 rounded-2xl bg-white border border-slate-200/80 shadow-subtle space-y-2.5">
                
                <!-- Search bar -->
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i class="bi bi-search text-base"></i>
                    </span>
                    <input type="text" id="posSearch" 
                           class="w-full pl-10 pr-10 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800 placeholder-slate-400" 
                           placeholder="Buscar por código SKU, código de barras, nombre o categoría... [F2]" 
                           autofocus>
                    <button type="button" id="clearSearchBtn" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 hidden">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>

                <!-- Category Fast Selector -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none text-xs font-semibold" id="categoryPills">
                    <button type="button" onclick="filterByCategory('')" class="cat-pill active px-3 py-1.5 rounded-xl bg-slate-900 text-white whitespace-nowrap transition-all shadow-sm">
                        Todos
                    </button>
                    @php
                        $uniqueCategories = $products->pluck('category')->filter()->unique()->take(6);
                    @endphp
                    @foreach($uniqueCategories as $cat)
                        <button type="button" onclick="filterByCategory('{{ addslashes($cat) }}')" class="cat-pill px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 whitespace-nowrap transition-all">
                            {{ $cat }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Product Grid -->
            <div id="productGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 max-h-[600px] overflow-y-auto pr-1">
                <!-- Injected via JavaScript -->
            </div>
        </div>

        <!-- Right Panel: Cart & Payment Checkout (5 cols) -->
        <div class="animate-fade-up stagger-2 lg:col-span-5 xl:col-span-4 sticky top-16 space-y-3">
            <div class="rounded-3xl bg-white border border-slate-200/80 shadow-floating overflow-hidden flex flex-col">
                
                <!-- Ticket Header -->
                <div class="px-4 py-3.5 bg-[#0f172a] text-white flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                            <i class="bi bi-receipt text-base"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-white tracking-tight">Ticket de Venta</h2>
                            <p class="text-[10px] text-emerald-400 font-mono">COMPROBANTE INTERNO</p>
                        </div>
                    </div>
                    <button type="button" onclick="clearCart()" class="text-[11px] font-semibold text-rose-400 hover:text-rose-300 transition-colors" title="Vaciar Carrito">
                        <i class="bi bi-trash3 me-1"></i>Vaciar
                    </button>
                </div>

                <!-- Customer Selection -->
                <div class="p-3.5 bg-slate-50 border-b border-slate-200/80 space-y-2">
                    <div class="flex items-center justify-between text-[11px] font-semibold uppercase text-slate-500">
                        <span class="flex items-center gap-1.5">
                            <i class="bi bi-person-fill text-slate-400"></i>
                            <span>Cliente Destinatario</span>
                        </span>
                        <a href="{{ route('customers.create') }}" target="_blank" class="text-emerald-600 hover:underline lowercase text-[11px] font-medium">
                            + registrar
                        </a>
                    </div>
                    
                    <div>
                        <select id="customer_selector" onchange="handleCustomerSelect(this)" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            <option value="" data-name="Consumidor Final" data-doc="9999999999">Consumidor Final (Sin documento)</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" data-name="{{ $c->name }}" data-doc="{{ $c->document_number }}">
                                    {{ $c->name }} ({{ $c->document_type }}: {{ $c->document_number }})
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" id="customer_id" value="">
                    </div>

                    <div class="grid grid-cols-12 gap-2">
                        <div class="col-span-5">
                            <input type="text" id="customer_dni" 
                                   class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 bg-white font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" 
                                   placeholder="Cédula / RUC" 
                                   value="9999999999">
                        </div>
                        <div class="col-span-7">
                            <input type="text" id="customer_name" 
                                   class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" 
                                   placeholder="Nombre cliente" 
                                   value="Consumidor Final">
                        </div>
                    </div>
                </div>

                <!-- Cart Items List -->
                <div class="p-3.5 flex-1 min-h-[160px] max-h-[240px] overflow-y-auto">
                    <div id="cartItemsContainer" class="space-y-1.5">
                        <div id="emptyCartMessage" class="py-10 text-center text-slate-400 space-y-1.5">
                            <i class="bi bi-cart-x text-2xl opacity-60"></i>
                            <p class="text-xs font-medium">El carrito está vacío</p>
                            <p class="text-[11px] text-slate-400">Haz clic en productos del catálogo o pulsa F2 para buscar.</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Method & Discount Section -->
                <div class="p-3.5 bg-slate-50 border-t border-slate-200/80 space-y-2.5">
                    <div class="space-y-1">
                        <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider">Método de Pago</label>
                        <div class="grid grid-cols-3 gap-1.5 text-xs">
                            <button type="button" onclick="setPaymentMethod('cash')" id="btnMethodCash" class="pay-method-btn active py-2 px-2 rounded-xl border border-emerald-500 bg-emerald-50 font-bold text-emerald-900 flex flex-col items-center gap-0.5">
                                <i class="bi bi-cash-stack text-sm text-emerald-600"></i>
                                <span>Efectivo</span>
                            </button>
                            <button type="button" onclick="setPaymentMethod('card')" id="btnMethodCard" class="pay-method-btn py-2 px-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 font-semibold text-slate-700 flex flex-col items-center gap-0.5">
                                <i class="bi bi-credit-card text-sm text-slate-500"></i>
                                <span>Tarjeta</span>
                            </button>
                            <button type="button" onclick="setPaymentMethod('transfer')" id="btnMethodTransfer" class="pay-method-btn py-2 px-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 font-semibold text-slate-700 flex flex-col items-center gap-0.5">
                                <i class="bi bi-arrow-left-right text-sm text-slate-500"></i>
                                <span>Transfer.</span>
                            </button>
                        </div>
                    </div>

                    <!-- Descuento General y Efectivo Recibido -->
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase">Descuento ($)</label>
                            <input type="number" step="0.01" min="0" id="sale_discount" value="0.00" oninput="updateTotals()"
                                   class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white font-mono text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        </div>
                        <div id="cashPaidWrapper">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase">Efectivo Recibido ($)</label>
                            <input type="number" step="0.01" min="0" id="sale_paid_amount" value="0.00" oninput="updateTotals()"
                                   class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white font-mono text-xs font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        </div>
                    </div>
                </div>

                <!-- Financial Calculation Summary -->
                <div class="p-4 border-t border-slate-200/80 bg-white space-y-1.5 text-xs">
                    <div class="flex items-center justify-between text-slate-500">
                        <span>Subtotal:</span>
                        <span class="font-mono font-semibold text-slate-700" id="val_subtotal">$0.00</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500" id="row_discount" style="display: none;">
                        <span>Descuento aplicado:</span>
                        <span class="font-mono font-semibold text-rose-600" id="val_discount">-$0.00</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500">
                        <span>IVA (12%):</span>
                        <span class="font-mono font-semibold text-slate-700" id="val_tax">$0.00</span>
                    </div>
                    
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-900 uppercase">Total a Cobrar:</span>
                        <span class="text-2xl font-extrabold font-mono text-emerald-600" id="val_total">$0.00</span>
                    </div>

                    <!-- Cambio / Vuelto -->
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-200" id="row_change">
                        <span class="text-xs font-bold text-slate-700">Cambio / Vuelto:</span>
                        <span class="font-mono font-extrabold text-sm text-slate-900" id="val_change">$0.00</span>
                    </div>

                    <!-- Checkout Button -->
                    <button type="button" 
                            id="submitSaleButton" 
                            onclick="submitSale()" 
                            disabled
                            class="btn-shine w-full mt-2 flex items-center justify-center gap-2 py-3 px-4 rounded-2xl text-sm font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 disabled:opacity-50 disabled:cursor-not-allowed shadow-md shadow-emerald-500/20 transition-all active:scale-98">
                        <i class="bi bi-check2-circle text-lg"></i>
                        <span>COBRAR Y REGISTRAR VENTA [F4]</span>
                    </button>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
    const rawProducts = @json($products->toArray());

    const products = rawProducts.map(product => ({
        ...product,
        stock: Number(product.stock),
        price: Number(product.price),
    }));

    let cart = [];
    let activeCategory = '';
    let selectedPaymentMethod = 'cash';
    let currentIdempotencyKey = generateUUID();

    const productGrid = document.getElementById('productGrid');
    const cartItemsContainer = document.getElementById('cartItemsContainer');
    const emptyCartMessage = document.getElementById('emptyCartMessage');
    const subtotalEl = document.getElementById('val_subtotal');
    const discountEl = document.getElementById('val_discount');
    const rowDiscountEl = document.getElementById('row_discount');
    const taxEl = document.getElementById('val_tax');
    const totalEl = document.getElementById('val_total');
    const changeEl = document.getElementById('val_change');
    const rowChangeEl = document.getElementById('row_change');
    const posSearch = document.getElementById('posSearch');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const submitButton = document.getElementById('submitSaleButton');
    const saleDiscountInput = document.getElementById('sale_discount');
    const salePaidAmountInput = document.getElementById('sale_paid_amount');
    const cashPaidWrapper = document.getElementById('cashPaidWrapper');

    function generateUUID() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        return 'pos-' + Date.now() + '-' + Math.random().toString(36).substring(2, 10);
    }

    function formatCurrency(value) {
        return '$' + Number(value).toFixed(2);
    }

    function setPaymentMethod(method) {
        selectedPaymentMethod = method;
        document.querySelectorAll('.pay-method-btn').forEach(btn => {
            btn.classList.remove('border-emerald-500', 'bg-emerald-50', 'text-emerald-900', 'font-bold');
            btn.classList.add('border-slate-200', 'bg-white', 'text-slate-700', 'font-semibold');
        });

        const activeBtn = method === 'cash' ? document.getElementById('btnMethodCash') :
                         (method === 'card' ? document.getElementById('btnMethodCard') : document.getElementById('btnMethodTransfer'));
        if (activeBtn) {
            activeBtn.classList.remove('border-slate-200', 'bg-white', 'text-slate-700');
            activeBtn.classList.add('border-emerald-500', 'bg-emerald-50', 'text-emerald-900', 'font-bold');
        }

        if (method === 'cash') {
            cashPaidWrapper.style.display = 'block';
            rowChangeEl.style.display = 'flex';
        } else {
            cashPaidWrapper.style.display = 'none';
            rowChangeEl.style.display = 'none';
        }

        updateTotals();
    }

    function filterByCategory(category) {
        activeCategory = category;
        
        document.querySelectorAll('.cat-pill').forEach(btn => {
            btn.classList.remove('bg-slate-900', 'text-white', 'shadow-sm');
            btn.classList.add('bg-slate-100', 'text-slate-700');
        });
        
        event.target.classList.remove('bg-slate-100', 'text-slate-700');
        event.target.classList.add('bg-slate-900', 'text-white', 'shadow-sm');

        applyFilters();
    }

    function applyFilters() {
        const term = posSearch.value.toLowerCase().trim();
        const filtered = products.filter(product => {
            const matchesCategory = !activeCategory || product.category === activeCategory;
            const matchesSearch = !term || 
                product.name.toLowerCase().includes(term) ||
                (product.category && product.category.toLowerCase().includes(term)) ||
                (product.sku && product.sku.toLowerCase().includes(term)) ||
                (product.description || '').toLowerCase().includes(term);

            return matchesCategory && matchesSearch;
        });

        renderProducts(filtered);
    }

    function renderProducts(productList) {
        if (!productGrid) return;
        productGrid.innerHTML = '';

        if (!productList.length) {
            productGrid.innerHTML = `
                <div class="col-span-full py-12 text-center text-slate-400 space-y-2 bg-white rounded-2xl border border-slate-200/80">
                    <i class="bi bi-search text-2xl text-slate-300"></i>
                    <p class="text-xs font-semibold text-slate-600">No se encontraron productos coincidentes</p>
                    <p class="text-[11px] text-slate-400">Intenta con otro término de búsqueda o categoría.</p>
                </div>
            `;
            return;
        }

        productList.forEach((product, idx) => {
            const disabled = product.stock <= 0;
            const card = document.createElement('div');
            card.style.animationDelay = Math.min(idx * 25, 200) + 'ms';
            card.className = `animate-scale-in p-3.5 rounded-2xl bg-white border border-slate-200/80 transition-all flex flex-col justify-between ${disabled ? 'opacity-55 cursor-not-allowed bg-slate-50' : 'saas-card-hover hover:border-emerald-500/50 cursor-pointer group active:scale-98'}`;
            
            if (!disabled) {
                card.onclick = (e) => {
                    if (!e.target.closest('button')) {
                        addProductToCart(product.id);
                    }
                };
            }

            card.innerHTML = `
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1.5">
                        <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold font-mono ${disabled ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700'}">
                            ${product.sku || 'SKU'}
                        </span>
                        <span class="text-[10px] text-slate-400 truncate max-w-[100px]">
                            ${product.category || 'General'}
                        </span>
                    </div>

                    <h3 class="text-xs font-bold text-slate-900 group-hover:text-emerald-700 transition-colors line-clamp-2 ${disabled ? 'line-through text-slate-400' : ''}">
                        ${product.name}
                    </h3>
                </div>

                <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="text-sm font-extrabold font-mono text-slate-900 block">
                            ${formatCurrency(product.price)}
                        </span>
                        <span class="text-[10px] text-slate-400">
                            Stock: <strong class="${product.stock <= 5 ? 'text-amber-600' : 'text-slate-700'}">${product.stock}</strong> ${product.unit || 'un'}
                        </span>
                    </div>

                    <button type="button" 
                            ${disabled ? 'disabled' : ''} 
                            onclick="addProductToCart(${product.id})"
                            class="inline-flex items-center justify-center w-7 h-7 rounded-lg ${disabled ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-500 hover:text-white'} transition-colors shadow-sm">
                        <i class="bi bi-plus text-base"></i>
                    </button>
                </div>
            `;
            productGrid.appendChild(card);
        });
    }

    function renderCart() {
        cartItemsContainer.innerHTML = '';
        if (!cart.length) {
            cartItemsContainer.appendChild(emptyCartMessage);
            updateTotals();
            return;
        }

        cart.forEach(item => {
            const itemRow = document.createElement('div');
            itemRow.className = 'animate-scale-in flex items-center justify-between p-2 rounded-xl bg-slate-50 hover:bg-slate-100/80 border border-slate-100 transition-colors gap-2';
            itemRow.innerHTML = `
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-slate-900 truncate">${item.name}</p>
                    <p class="text-[10px] text-slate-500 font-mono">${formatCurrency(item.unit_price)} / ${item.unit}</p>
                </div>

                <div class="flex items-center gap-1.5">
                    <!-- Qty Steppers -->
                    <div class="inline-flex items-center border border-slate-200 rounded-lg bg-white overflow-hidden shadow-subtle">
                        <button type="button" onclick="decrementCartItem(${item.id})" class="px-2 py-0.5 text-xs text-slate-500 hover:bg-slate-100">-</button>
                        <span class="px-2 py-0.5 text-xs font-mono font-bold text-slate-800">${item.quantity}</span>
                        <button type="button" onclick="incrementCartItem(${item.id})" class="px-2 py-0.5 text-xs text-slate-500 hover:bg-slate-100">+</button>
                    </div>

                    <span class="font-mono font-bold text-xs text-slate-900 w-14 text-right">${formatCurrency(item.subtotal)}</span>

                    <button type="button" onclick="removeCartItem(${item.id})" class="text-slate-400 hover:text-rose-600 p-1" title="Quitar item">
                        <i class="bi bi-trash3 text-xs"></i>
                    </button>
                </div>
            `;
            cartItemsContainer.appendChild(itemRow);
        });

        updateTotals();
    }

    function addProductToCart(productId) {
        const product = products.find(item => item.id === productId);
        if (!product || product.stock <= 0) return;

        const existingItem = cart.find(item => item.id === productId);
        if (existingItem) {
            if (existingItem.quantity + 1 > product.stock) {
                alert(`Existencias limitadas. Solo hay ${product.stock} disponibles de este producto.`);
                return;
            }
            existingItem.quantity += 1;
            existingItem.subtotal = existingItem.quantity * existingItem.unit_price;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                unit_price: product.price,
                quantity: 1,
                subtotal: product.price,
                unit: product.unit || 'un',
            });
        }

        renderCart();
    }

    function incrementCartItem(productId) {
        const product = products.find(item => item.id === productId);
        const item = cart.find(item => item.id === productId);
        if (!product || !item) return;

        if (item.quantity + 1 > product.stock) {
            alert(`Límite de stock alcanzado: máximo ${product.stock} unidades.`);
            return;
        }

        item.quantity += 1;
        item.subtotal = item.quantity * item.unit_price;
        renderCart();
    }

    function decrementCartItem(productId) {
        const item = cart.find(item => item.id === productId);
        if (!item) return;

        if (item.quantity > 1) {
            item.quantity -= 1;
            item.subtotal = item.quantity * item.unit_price;
        } else {
            removeCartItem(productId);
            return;
        }

        renderCart();
    }

    function removeCartItem(productId) {
        cart = cart.filter(item => item.id !== productId);
        renderCart();
    }

    function clearCart() {
        if (!cart.length) return;
        if (confirm('¿Deseas vaciar los productos del ticket?')) {
            cart = [];
            currentIdempotencyKey = generateUUID();
            renderCart();
        }
    }

    function updateTotals() {
        const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
        const discountVal = Math.max(0, parseFloat(saleDiscountInput.value) || 0);

        if (discountVal > subtotal && subtotal > 0) {
            saleDiscountInput.value = subtotal.toFixed(2);
        }

        const effectiveDiscount = Math.min(discountVal, subtotal);
        const taxableBase = Math.max(0, subtotal - effectiveDiscount);
        const tax = taxableBase * 0.12;
        const total = taxableBase + tax;

        subtotalEl.textContent = formatCurrency(subtotal);
        
        if (effectiveDiscount > 0) {
            rowDiscountEl.style.display = 'flex';
            discountEl.textContent = '-' + formatCurrency(effectiveDiscount);
        } else {
            rowDiscountEl.style.display = 'none';
        }

        taxEl.textContent = formatCurrency(tax);
        totalEl.textContent = formatCurrency(total);

        // Actualizar monto pagado si está vacío o es menor
        if (selectedPaymentMethod === 'cash') {
            const paid = parseFloat(salePaidAmountInput.value) || 0;
            const change = Math.max(0, paid - total);
            changeEl.textContent = formatCurrency(change);
            if (paid < total && paid > 0) {
                changeEl.classList.add('text-rose-600');
                changeEl.classList.remove('text-slate-900');
            } else {
                changeEl.classList.remove('text-rose-600');
                changeEl.classList.add('text-slate-900');
            }
        }

        submitButton.disabled = cart.length === 0;
    }

    function handleCustomerSelect(sel) {
        const opt = sel.options[sel.selectedIndex];
        const custId = opt.value || '';
        const custName = opt.dataset.name || 'Consumidor Final';
        const custDoc = opt.dataset.doc || '9999999999';

        document.getElementById('customer_id').value = custId;
        document.getElementById('customer_name').value = custName;
        document.getElementById('customer_dni').value = custDoc;
    }

    function getCsrfToken() {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        return tokenMeta ? tokenMeta.getAttribute('content') : '';
    }

    async function submitSale() {
        if (!cart.length) {
            alert('Agrega al menos un producto antes de registrar la venta.');
            return;
        }

        const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
        const discount = Math.max(0, parseFloat(saleDiscountInput.value) || 0);
        const total = Math.max(0, subtotal - discount) * 1.12;
        let paidAmount = total;

        if (selectedPaymentMethod === 'cash') {
            paidAmount = parseFloat(salePaidAmountInput.value) || 0;
            if (paidAmount < total) {
                alert(`El efectivo recibido ($${paidAmount.toFixed(2)}) es insuficiente para cubrir el total ($${total.toFixed(2)}).`);
                salePaidAmountInput.focus();
                return;
            }
        }

        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="inline-block animate-spin mr-2"><i class="bi bi-arrow-repeat"></i></span> Registrando Venta...';

        const customerId = document.getElementById('customer_id').value || null;
        const customerName = document.getElementById('customer_name').value.trim() || 'Consumidor Final';
        const customerDocument = document.getElementById('customer_dni').value.trim() || '---';

        try {
            const response = await fetch('{{ route('sales.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    idempotency_key: currentIdempotencyKey,
                    customer_id: customerId,
                    customer_name: customerName,
                    customer_document: customerDocument,
                    payment_method: selectedPaymentMethod,
                    discount: discount,
                    paid_amount: paidAmount,
                    items: cart.map(item => ({
                        product_id: item.id,
                        quantity: item.quantity,
                        unit_price: item.unit_price,
                        discount: 0,
                    })),
                }),
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Error al procesar la venta en el servidor.');
            }

            // Descarga automática del Comprobante Interno PDF
            try {
                const pdfUrl = new URL(`{{ url('sales') }}/${data.sale_id}/pdf`);
                pdfUrl.searchParams.set('customer_name', customerName);
                pdfUrl.searchParams.set('customer_document', customerDocument);

                const pdfResponse = await fetch(pdfUrl.toString(), {
                    method: 'GET',
                    headers: { 'Accept': 'application/pdf' },
                });

                if (pdfResponse.ok) {
                    const blob = await pdfResponse.blob();
                    const downloadUrl = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = downloadUrl;
                    link.download = `comprobante_${data.sale_id}.pdf`;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    URL.revokeObjectURL(downloadUrl);
                }
            } catch (pdfErr) {
                console.warn('No se pudo descargar automáticamente el PDF:', pdfErr);
            }

            const changeText = (selectedPaymentMethod === 'cash' && data.change_amount > 0)
                ? `\nCambio / Vuelto a entregar: $${Number(data.change_amount).toFixed(2)}`
                : '';

            alert(`¡Venta registrada exitosamente! (Comprobante: ${data.invoice_number})${changeText}`);

            // Resetear carrito y regenerar clave de idempotencia para la siguiente venta
            cart = [];
            currentIdempotencyKey = generateUUID();
            saleDiscountInput.value = '0.00';
            salePaidAmountInput.value = '0.00';
            renderCart();
            window.location.reload();
        } catch (error) {
            alert('Error en la venta: ' + error.message);
            submitButton.disabled = cart.length === 0;
            submitButton.innerHTML = '<i class="bi bi-check2-circle text-lg"></i> <span>COBRAR Y REGISTRAR VENTA [F4]</span>';
        }
    }

    // Atajos de teclado para agilizar operación de mostrador
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F2') {
            e.preventDefault();
            posSearch.focus();
            posSearch.select();
        } else if (e.key === 'F4') {
            e.preventDefault();
            if (!submitButton.disabled) {
                submitSale();
            }
        } else if (e.key === 'Escape') {
            if (document.activeElement === posSearch) {
                posSearch.value = '';
                clearSearchBtn.classList.add('hidden');
                applyFilters();
            }
        }
    });

    posSearch.addEventListener('input', function () {
        clearSearchBtn.classList.toggle('hidden', !this.value.trim());
        applyFilters();
    });

    clearSearchBtn.addEventListener('click', function () {
        posSearch.value = '';
        this.classList.add('hidden');
        applyFilters();
        posSearch.focus();
    });

    document.addEventListener('DOMContentLoaded', function () {
        renderProducts(products);
        renderCart();
        setPaymentMethod('cash');
    });
</script>
@endsection
