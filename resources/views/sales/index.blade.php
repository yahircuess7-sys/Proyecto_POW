@extends('layouts.app')

@section('title', 'Punto de Venta POS - Ferretería El Tornillo')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h4 fw-bold text-dark mb-1"><i class="bi bi-printer-fill me-2 text-primary"></i>Caja Registradora POS</h1>
            <p class="text-muted mb-0">Agrega productos haciendo clic en el catálogo para generar un ticket de entrega rápida.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Panel Izquierdo: Catálogo de Productos Disponibles -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-tags-fill me-2 text-warning"></i>Catálogo de Materiales</h5>
                    <span class="badge bg-primary">Venta Express</span>
                </div>
                
                <div class="card-body p-4">
                    <!-- Buscador Rápido del POS -->
                    <div class="input-group mb-4">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="posSearch" class="form-control border-start-0 bg-light" placeholder="Filtra por nombre, categoría o medidas de clavo...">
                    </div>

                    <!-- Grid de Materiales -->
                    <div class="row g-3" id="productGrid" style="max-height: 480px; overflow-y: auto;"></div>
                </div>
            </div>
        </div>

        <!-- Panel Derecho: Carrito de Compras / Factura del Cliente -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-dark text-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                        <i class="bi bi-receipt text-warning"></i> Resumen de Boleta
                    </h5>
                    <span class="badge bg-warning text-dark font-mono">FAC-TURACIÓN</span>
                </div>

                <div class="card-body p-4">
                    <!-- Datos de Facturación del Cliente -->
                    <div class="row g-2 mb-4 bg-light p-3 rounded-3" style="font-size: 0.85rem;">
                        <h6 class="fw-bold text-dark mb-2 small text-uppercase" style="letter-spacing:0.05em;">Datos de Pago e Identificación</h6>
                        <div class="col-5">
                            <input type="text" class="form-control form-control-sm" id="customer_dni" placeholder="Cédula/RUC" value="0932184912">
                        </div>
                        <div class="col-7">
                            <input type="text" class="form-control form-control-sm" id="customer_name" placeholder="Cliente Consumidor Final" value="Consumidor Final">
                        </div>
                    </div>

                    <!-- Lista de Ítems en Carrito -->
                    <div class="mb-4">
                        <label class="form-label text-muted fw-bold small text-uppercase">Productos Cargados</label>
                        <div id="cartItemsContainer" style="max-height: 220px; overflow-y: auto;" class="border rounded-3 p-3 bg-light">
                            <div class="text-center text-muted py-4" id="emptyCartMessage">No hay productos seleccionados.</div>
                        </div>
                    </div>

                    <!-- Calculadora de Totales -->
                    <div class="border-top pt-3 text-dark bg-opacity-10">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted smaill">Subtotal Neto:</span>
                            <span class="fw-semibold font-mono" id="val_subtotal">$18.20</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">IVA (12%):</span>
                            <span class="fw-semibold font-mono" id="val_tax">$2.18</span>
                        </div>
                        <hr class="my-2 text-muted">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="h6 fw-bold text-dark mb-0">TOTAL A COBRAR:</span>
                            <span class="h4 fw-bold text-primary font-mono mb-0" id="val_total">$20.38</span>
                        </div>
                    </div>

                    <!-- Botón para finalizar transacción -->
                    <button id="submitSaleButton" class="btn btn-warning w-100 py-3 fw-bold fs-6 text-dark shadow-sm d-flex align-items-center justify-content-center gap-2" onclick="submitSale()" style="border-radius: 8px;">
                        <i class="bi bi-cash-coin fs-5"></i>
                        <span>EMITIR BOLETA / GUARDAR VENTA</span>
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

    const productGrid = document.getElementById('productGrid');
    const cartItemsContainer = document.getElementById('cartItemsContainer');
    const emptyCartMessage = document.getElementById('emptyCartMessage');
    const subtotalEl = document.getElementById('val_subtotal');
    const taxEl = document.getElementById('val_tax');
    const totalEl = document.getElementById('val_total');
    const posSearch = document.getElementById('posSearch');
    const submitButton = document.getElementById('submitSaleButton');

    function formatCurrency(value) {
        return '$' + Number(value).toFixed(2);
    }

    function renderProducts(productList) {
        if (!productGrid) return;
        productGrid.innerHTML = '';

        if (!productList.length) {
            productGrid.innerHTML = '<div class="col-12 text-center text-muted py-5">No se encontraron productos para mostrar.</div>';
            return;
        }

        productList.forEach(product => {
            const disabled = product.stock <= 0;
            const card = document.createElement('div');
            card.className = 'col-md-6 pos-item-card';
            card.innerHTML = `
                <div class="card h-100 border border-opacity-10 shadow-none ${disabled ? 'bg-light opacity-75' : 'hover-shadow'} transition p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge ${disabled ? 'bg-danger text-white' : 'bg-primary bg-opacity-10 text-primary'}">${product.category}</span>
                        <span class="font-mono text-muted text-xs" style="font-size:0.75rem;">${product.sku}</span>
                    </div>
                    <h6 class="fw-bold text-dark mb-1 ${disabled ? 'text-decoration-line-through' : ''}">${product.name}</h6>
                    <p class="text-muted small mb-3">Stock: ${product.stock} ${product.unit}</p>
                    <div class="d-flex justify-content-between align-items-center mt-auto">
                        <span class="fw-bold fs-5 text-dark font-mono">${formatCurrency(product.price)}</span>
                        <button type="button" class="btn btn-sm ${disabled ? 'btn-secondary disabled' : 'btn-warning'} fw-bold px-3 py-1 text-dark" ${disabled ? 'disabled' : ''} onclick="addProductToCart(${product.id})">
                            <i class="bi bi-plus-lg"></i> ${disabled ? 'Agotado' : 'Agregar'}
                        </button>
                    </div>
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
            itemRow.className = 'd-flex justify-content-between align-items-center mb-3 text-dark pb-2 border-bottom';
            itemRow.innerHTML = `
                <div>
                    <span class="fw-bold d-block small">${item.name}</span>
                    <small class="text-muted">${formatCurrency(item.unit_price)} x ${item.quantity} ${item.unit}</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold font-mono">${formatCurrency(item.subtotal)}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="removeCartItem(${item.id})">
                        <i class="bi bi-x-circle"></i>
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
                alert('No hay suficiente stock disponible.');
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

    function removeCartItem(productId) {
        cart = cart.filter(item => item.id !== productId);
        renderCart();
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
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Guardando...';

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

            const pdfUrl = new URL(`{{ url('sales') }}/${data.sale_id}/pdf`);
            pdfUrl.searchParams.set('customer_name', customerName);
            pdfUrl.searchParams.set('customer_document', customerDocument);

            const pdfResponse = await fetch(pdfUrl.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/pdf',
                },
            });

            if (!pdfResponse.ok) {
                throw new Error('No se pudo descargar la boleta PDF.');
            }

            const blob = await pdfResponse.blob();
            const downloadUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `boleta_${data.sale_id}.pdf`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(downloadUrl);

            alert('Venta registrada correctamente. Boleta descargada.');
            setTimeout(() => {
                window.location.reload();
            }, 600);
        } catch (error) {
            alert(error.message);
            submitButton.disabled = cart.length === 0;
            submitButton.innerHTML = '<i class="bi bi-cash-coin fs-5"></i> <span>EMITIR BOLETA / GUARDAR VENTA</span>';
        }
    }

    posSearch.addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        const filtered = products.filter(product =>
            product.name.toLowerCase().includes(term) ||
            product.category.toLowerCase().includes(term) ||
            product.sku.toLowerCase().includes(term) ||
            (product.description || '').toLowerCase().includes(term)
        );
        renderProducts(filtered);
    });

    document.addEventListener('DOMContentLoaded', function () {
        renderProducts(products);
        renderCart();
    });
</script>
@endsection