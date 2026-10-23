@extends('layouts.app')

@section('title', 'Caja Rápida POS - PuntoÁgil')

@section('content')
<div class="space-y-5">
    
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                <span>Terminal de Ventas</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500 text-slate-950 font-mono">POS</span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Selecciona o busca materiales para agregar al ticket de mostrador.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-600 font-medium">Cajero activo:</span>
            <span class="px-2.5 py-1 rounded-xl bg-white border border-slate-200 text-xs font-semibold text-slate-800 shadow-subtle">
                <i class="bi bi-person-fill text-emerald-500 me-1"></i> {{ auth()->user()->name ?? 'Cajero' }}
            </span>
        </div>
    </div>

    <!-- Main Two-Column POS Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Panel: Product Catalog (7 cols) -->
        <div class="lg:col-span-7 xl:col-span-8 space-y-4">
            
            <!-- Search & Category Filters -->
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-subtle space-y-3">
                
                <!-- Search bar -->
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i class="bi bi-search text-base"></i>
                    </span>
                    <input type="text" id="posSearch" 
                           class="w-full pl-10 pr-10 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800 placeholder-slate-400" 
                           placeholder="Buscar por nombre, SKU, marca o medidas (ej. tornillo, clavos, 50kg)..." 
                           autofocus>
                    <button type="button" id="clearSearchBtn" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 hidden">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>

                <!-- Category Fast Pills (Touch-Friendly) -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none text-xs font-semibold" id="categoryPills">
                    <button type="button" onclick="filterByCategory('')" class="cat-pill active px-3 py-1.5 rounded-xl bg-slate-900 text-white whitespace-nowrap transition-all shadow-sm">
                        Todos
                    </button>
                    <button type="button" onclick="filterByCategory('Fijaciones y Tornillos')" class="cat-pill px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 whitespace-nowrap transition-all">
                        Fijaciones
                    </button>
                    <button type="button" onclick="filterByCategory('Herramientas Manuales')" class="cat-pill px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 whitespace-nowrap transition-all">
                        H. Manuales
                    </button>
                    <button type="button" onclick="filterByCategory('Herramientas Eléctricas')" class="cat-pill px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 whitespace-nowrap transition-all">
                        H. Eléctricas
                    </button>
                    <button type="button" onclick="filterByCategory('Construcción y Áridos')" class="cat-pill px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 whitespace-nowrap transition-all">
                        Construcción
                    </button>
                    <button type="button" onclick="filterByCategory('Pinturas')" class="cat-pill px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 whitespace-nowrap transition-all">
                        Pinturas
                    </button>
                </div>
            </div>

            <!-- Product Grid -->
            <div id="productGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3.5 max-h-[580px] overflow-y-auto pr-1">
                <!-- Products injected by JavaScript -->
            </div>
        </div>

        <!-- Right Panel: Cart & Checkout (5 cols) -->
        <div class="lg:col-span-5 xl:col-span-4 sticky top-20">
            <div class="rounded-3xl bg-white border border-slate-200/80 shadow-floating overflow-hidden flex flex-col">
                
                <!-- Ticket Header -->
                <div class="px-5 py-4 bg-[#0f172a] text-white flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                            <i class="bi bi-receipt text-base"></i>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-white tracking-tight">Comprobante de Venta</h2>
                            <p class="text-[10px] text-slate-400 font-mono">TICKET ELECTRÓNICO</p>
                        </div>
                    </div>
                    <button type="button" onclick="clearCart()" class="text-[11px] font-semibold text-rose-400 hover:text-rose-300 transition-colors" title="Vaciar Carrito">
                        Vaciar
                    </button>
                </div>

                <!-- Customer Info -->
                <div class="p-4 bg-slate-50 border-b border-slate-200/80 space-y-2.5">
                    <div class="flex items-center justify-between text-[11px] font-semibold uppercase text-slate-400">
                        <span>Datos del Cliente</span>
                        <span>Consumidor Final</span>
                    </div>
                    <div class="grid grid-cols-12 gap-2">
                        <div class="col-span-5">
                            <input type="text" id="customer_dni" 
                                   class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" 
                                   placeholder="DNI / RUC" 
                                   value="0932184912">
                        </div>
                        <div class="col-span-7">
                            <input type="text" id="customer_name" 
                                   class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" 
                                   placeholder="Nombre de cliente" 
                                   value="Consumidor Final">
                        </div>
                    </div>
                </div>

                <!-- Cart Items List -->
                <div class="p-4 flex-1">
                    <div id="cartItemsContainer" class="space-y-2 max-h-56 overflow-y-auto pr-1">
                        <div id="emptyCartMessage" class="py-12 text-center text-slate-400 space-y-2">
                            <i class="bi bi-cart-x text-3xl opacity-60"></i>
                            <p class="text-xs">No hay productos agregados al ticket.</p>
                            <p class="text-[11px] text-slate-400">Haz clic en cualquier material del catálogo para comenzar.</p>
                        </div>
                    </div>
                </div>

                <!-- Financial Calculation Summary -->
                <div class="p-5 border-t border-slate-200/80 bg-slate-50/60 space-y-2 text-xs">
                    <div class="flex items-center justify-between text-slate-500">
                        <span>Subtotal Neto:</span>
                        <span class="font-mono font-semibold text-slate-700" id="val_subtotal">$0.00</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500">
                        <span>IVA (12%):</span>
                        <span class="font-mono font-semibold text-slate-700" id="val_tax">$0.00</span>
                    </div>
                    
                    <div class="pt-2 border-t border-slate-200 flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-900 uppercase">Total a Cobrar:</span>
                        <span class="text-2xl font-extrabold font-mono text-emerald-600" id="val_total">$0.00</span>
                    </div>

                    <!-- Checkout Button -->
                    <button type="button" 
                            id="submitSaleButton" 
                            onclick="submitSale()" 
                            disabled
                            class="w-full mt-3 flex items-center justify-center gap-2 py-3.5 px-4 rounded-2xl text-sm font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 disabled:opacity-50 disabled:cursor-not-allowed shadow-md shadow-emerald-500/20 transition-all active:scale-98">
                        <i class="bi bi-check2-circle text-lg"></i>
                        <span>EMITIR BOLETA / COBRAR</span>
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

    const productGrid = document.getElementById('productGrid');
    const cartItemsContainer = document.getElementById('cartItemsContainer');
    const emptyCartMessage = document.getElementById('emptyCartMessage');
    const subtotalEl = document.getElementById('val_subtotal');
    const taxEl = document.getElementById('val_tax');
    const totalEl = document.getElementById('val_total');
    const posSearch = document.getElementById('posSearch');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const submitButton = document.getElementById('submitSaleButton');

    function formatCurrency(value) {
        return '$' + Number(value).toFixed(2);
    }

    function filterByCategory(category) {
        activeCategory = category;
        
        // Update pills UI
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
                product.category.toLowerCase().includes(term) ||
                product.sku.toLowerCase().includes(term) ||
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

        productList.forEach(product => {
            const disabled = product.stock <= 0;
            const card = document.createElement('div');
            card.className = `p-4 rounded-2xl bg-white border border-slate-200/80 transition-all flex flex-col justify-between ${disabled ? 'opacity-55 cursor-not-allowed bg-slate-50' : 'hover:border-emerald-500/50 hover:shadow-card cursor-pointer group active:scale-98'}`;
            
            if (!disabled) {
                card.onclick = (e) => {
                    // Prevent double triggering if clicked directly on add button
                    if (!e.target.closest('button')) {
                        addProductToCart(product.id);
                    }
                };
            }

            card.innerHTML = `
                <div>
                    <div class="flex items-center justify-between gap-1 mb-2">
                        <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold font-mono ${disabled ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700'}">
                            ${product.sku}
                        </span>
                        <span class="text-[10px] text-slate-400 truncate max-w-[110px]">
                            ${product.category}
                        </span>
                    </div>

                    <h3 class="text-sm font-bold text-slate-900 group-hover:text-emerald-700 transition-colors line-clamp-2 ${disabled ? 'line-through text-slate-400' : ''}">
                        ${product.name}
                    </h3>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="text-base font-extrabold font-mono text-slate-900 block">
                            ${formatCurrency(product.price)}
                        </span>
                        <span class="text-[10px] text-slate-400">
                            Disp: <strong class="${product.stock <= 5 ? 'text-amber-600' : 'text-slate-700'}">${product.stock}</strong> ${product.unit}
                        </span>
                    </div>

                    <button type="button" 
                            ${disabled ? 'disabled' : ''} 
                            onclick="addProductToCart(${product.id})"
                            class="inline-flex items-center justify-center w-8 h-8 rounded-xl ${disabled ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-500 hover:text-white'} transition-colors shadow-sm">
                        <i class="bi bi-plus text-lg"></i>
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
            itemRow.className = 'flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100 gap-2';
            itemRow.innerHTML = `
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-slate-900 truncate">${item.name}</p>
                    <p class="text-[10px] text-slate-500 font-mono">${formatCurrency(item.unit_price)} / ${item.unit}</p>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Qty Steppers -->
                    <div class="inline-flex items-center border border-slate-200 rounded-lg bg-white overflow-hidden shadow-subtle">
                        <button type="button" onclick="decrementCartItem(${item.id})" class="px-2 py-0.5 text-xs text-slate-500 hover:bg-slate-100">-</button>
                        <span class="px-2 py-0.5 text-xs font-mono font-bold text-slate-800">${item.quantity}</span>
                        <button type="button" onclick="incrementCartItem(${item.id})" class="px-2 py-0.5 text-xs text-slate-500 hover:bg-slate-100">+</button>
                    </div>

                    <span class="font-mono font-bold text-xs text-slate-900 w-16 text-right">${formatCurrency(item.subtotal)}</span>

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
        if (confirm('¿Deseas vaciar el carrito actual?')) {
            cart = [];
            renderCart();
        }
    }

    function updateTotals() {
        const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
        const tax = subtotal * 0.12;
        const total = subtotal + tax;

        subtotalEl.textContent = formatCurrency(subtotal);
        taxEl.textContent = formatCurrency(tax);
        totalEl.textContent = formatCurrency(total);

        submitButton.disabled = cart.length === 0;
    }

    function getCsrfToken() {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        return tokenMeta ? tokenMeta.getAttribute('content') : '';
    }

    async function submitSale() {
        if (!cart.length) {
            alert('Agrega al menos un producto antes de guardar la venta.');
            return;
        }

        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="inline-block animate-spin mr-2"><i class="bi bi-arrow-repeat"></i></span> Procesando Ticket...';

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
                    items: cart.map(item => ({
                        product_id: item.id,
                        quantity: item.quantity,
                        unit_price: item.unit_price,
                    })),
                }),
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Error al guardar la venta.');
            }

            // Descarga de boleta PDF
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
                link.download = `boleta_${data.sale_id}.pdf`;
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(downloadUrl);
            }

            alert('¡Venta registrada con éxito! La boleta se ha descargado.');
            setTimeout(() => {
                window.location.reload();
            }, 600);
        } catch (error) {
            alert(error.message);
            submitButton.disabled = cart.length === 0;
            submitButton.innerHTML = '<i class="bi bi-check2-circle text-lg"></i> <span>EMITIR BOLETA / COBRAR</span>';
        }
    }

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
    });
</script>
@endsection
