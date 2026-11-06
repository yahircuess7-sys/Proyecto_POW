@extends('layouts.app')

@section('title', 'Punto de Venta (POS) - PuntoÁgil')

@section('content')
@php
    $posEffectiveTaxPercentage = isset($currentBusiness) ? $currentBusiness->effectiveTaxPercentage(15.0) : 15.0;
    $posReceiptFormat = $currentBusiness->settings['receipt_format'] ?? 'ticket_80mm';
@endphp
<div class="space-y-4">

    <!-- Aviso de Validez Operativa Interna y Atajos Rápidos -->
    <div class="px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs flex flex-wrap items-center justify-between gap-3 shadow-subtle">
        <div class="flex items-center gap-2">
            <i class="bi bi-info-circle-fill text-amber-600 text-sm"></i>
            <span><strong>Control Operativo (C$):</strong> Comprobantes para control administrativo interno sin validez fiscal DGI. Escáner de código de barras activo automáticamente.</span>
        </div>
        <div class="hidden md:flex items-center gap-1.5 text-[11px] font-mono text-amber-900">
            <span class="px-2 py-0.5 rounded bg-white border border-amber-200 font-bold">F2 Buscar</span>
            <span class="px-2 py-0.5 rounded bg-white border border-amber-200 font-bold">Enter Agregar Código</span>
            <span class="px-2 py-0.5 rounded bg-white border border-amber-200 font-bold">F4 / Espacio Cobrar</span>
            <span class="px-2 py-0.5 rounded bg-white border border-amber-200 font-bold">Esc Limpiar / Cerrar</span>
        </div>
    </div>

    <!-- Header & Cash Register Status Banner -->
    <div class="animate-fade-up flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                <span>Terminal Punto de Venta</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500 text-slate-950 font-mono">POS C$</span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Cobro ultrarrápido en Córdobas, captura automática de lector de barras e impresión térmica 58mm/80mm.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($activeShift)
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-900 shadow-subtle">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span><strong>{{ $activeShift->cashRegister->name ?? 'Caja' }}</strong> (Turno #{{ $activeShift->id }})</span>
                    <span class="text-slate-400">|</span>
                    <span>Fondo: <strong>C$ {{ number_format($activeShift->initial_cash, 2) }}</strong></span>
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

                <!-- Search / Barcode Scanner Bar -->
                <div class="relative">
                    <label for="posSearch" class="sr-only">Buscar producto o escanear código de barras</label>
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-emerald-600">
                        <i class="bi bi-upc-scan text-base"></i>
                    </span>
                    <input type="text" id="posSearch"
                           autocorrect="off" autocapitalize="off" spellcheck="false" enterkeyhint="search"
                           class="w-full pl-10 pr-24 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800 placeholder-slate-400"
                           placeholder="Escanea código de barras o busca por SKU / nombre... [F2]"
                           autofocus>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2.5 gap-1.5">
                        <span id="scanFeedbackBadge" class="hidden px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 font-mono text-[10px] font-bold">
                            +1 Agregado
                        </span>
                        <button type="button" id="clearSearchBtn" aria-label="Limpiar búsqueda" class="p-1 text-slate-400 hover:text-slate-600 hidden">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </div>
                </div>

                <!-- Category Fast Selector -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none text-xs font-semibold" id="categoryPills">
                    <button type="button" onclick="filterByCategory('', this)" class="cat-pill active px-3 py-1.5 rounded-xl bg-slate-900 text-white whitespace-nowrap transition-all shadow-sm">
                        Todos
                    </button>
                    @php
                        $uniqueCategories = $products->pluck('category')->filter()->unique()->take(8);
                    @endphp
                    @foreach($uniqueCategories as $cat)
                        <button type="button" onclick="filterByCategory('{{ addslashes($cat) }}', this)" class="cat-pill px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 whitespace-nowrap transition-all">
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
                            <p class="text-[10px] text-emerald-400 font-mono">CÓRDOBAS (C$ &bull; NIO)</p>
                        </div>
                    </div>
                    <button type="button" onclick="clearCart()" class="text-[11px] font-semibold text-rose-400 hover:text-rose-300 transition-colors" title="Vaciar Carrito">
                        <i class="bi bi-trash3 me-1"></i>Vaciar
                    </button>
                </div>

                <!-- Customer Selection -->
                <div class="p-3.5 bg-slate-50 border-b border-slate-200/80 space-y-2">
                    <div class="flex items-center justify-between text-[11px] font-semibold uppercase text-slate-500">
                        <label for="customer_selector" class="flex items-center gap-1.5 cursor-pointer">
                            <i class="bi bi-person-fill text-slate-400"></i>
                            <span>Cliente Destinatario</span>
                        </label>
                        <a href="{{ route('customers.create') }}" target="_blank" class="text-emerald-600 hover:underline lowercase text-[11px] font-medium">
                            + registrar
                        </a>
                    </div>

                    <div>
                        <select id="customer_selector" onchange="handleCustomerSelect(this)" class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            <option value="" data-name="Consumidor Final" data-doc="---">Consumidor Final (Mostrador)</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" data-name="{{ $c->name }}" data-doc="{{ $c->document_number }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->document_type }}: {{ $c->document_number }})
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" id="customer_id" value="">
                    </div>

                    <div class="grid grid-cols-12 gap-2">
                        <div class="col-span-5">
                            <label for="customer_dni" class="sr-only">Cédula o RUC del cliente</label>
                            <input type="text" id="customer_dni" autocorrect="off" autocapitalize="off" spellcheck="false"
                                   class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 bg-white font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                   placeholder="Cédula / RUC"
                                   value="---">
                        </div>
                        <div class="col-span-7">
                            <label for="customer_name" class="sr-only">Nombre del cliente</label>
                            <input type="text" id="customer_name"
                                   class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                   placeholder="Nombre cliente"
                                   value="Consumidor Final">
                        </div>
                    </div>
                </div>

                <!-- Cart Items List -->
                <div class="p-3.5 flex-1 min-h-[150px] max-h-[230px] overflow-y-auto">
                    <div id="cartItemsContainer" class="space-y-1.5">
                        <div id="emptyCartMessage" class="py-8 text-center text-slate-400 space-y-1.5">
                            <i class="bi bi-upc-scan text-2xl opacity-60"></i>
                            <p class="text-xs font-medium text-slate-500">Escanea un producto o selecciónalo del catálogo</p>
                            <p class="text-[11px] text-slate-400">Pulsa <strong class="font-mono">F2</strong> para buscar o <strong class="font-mono">F4</strong> para cobrar.</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Method, Quick Cash Buttons & Discount -->
                <div class="p-3.5 bg-slate-50 border-t border-slate-200/80 space-y-2.5">
                    <div class="space-y-1">
                        <span class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider">Método de Pago</span>
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
                            <label for="sale_discount" class="block text-[10px] font-bold text-slate-500 uppercase mb-0.5">Descuento (C$)</label>
                            <input type="number" step="0.01" min="0" id="sale_discount" value="0.00" oninput="onDiscountInput()"
                                   class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white font-mono text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        </div>
                        <div id="cashPaidWrapper">
                            <label for="sale_paid_amount" class="block text-[10px] font-bold text-slate-500 uppercase mb-0.5">Efectivo Recibido (C$)</label>
                            <input type="number" step="0.01" min="0" id="sale_paid_amount" value="0.00" oninput="onManualPaidInput()"
                                   class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white font-mono text-xs font-bold text-emerald-700 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        </div>
                    </div>

                    <!-- Botones de Cobro Rápido en Córdobas -->
                    <div id="quickCashButtons" class="space-y-1">
                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Cobro Rápido en Efectivo</span>
                        <div class="grid grid-cols-5 gap-1 text-[11px] font-mono font-bold">
                            <button type="button" onclick="setExactCash()"
                                    class="py-1.5 px-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors shadow-2xs"
                                    title="Pagar monto exacto sin vuelto">
                                Exacto
                            </button>
                            <button type="button" onclick="setQuickBill(100)"
                                    class="py-1.5 px-1 rounded-lg bg-white hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 border border-slate-200 hover:border-emerald-300 transition-colors">
                                C$100
                            </button>
                            <button type="button" onclick="setQuickBill(200)"
                                    class="py-1.5 px-1 rounded-lg bg-white hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 border border-slate-200 hover:border-emerald-300 transition-colors">
                                C$200
                            </button>
                            <button type="button" onclick="setQuickBill(500)"
                                    class="py-1.5 px-1 rounded-lg bg-white hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 border border-slate-200 hover:border-emerald-300 transition-colors">
                                C$500
                            </button>
                            <button type="button" onclick="setQuickBill(1000)"
                                    class="py-1.5 px-1 rounded-lg bg-white hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 border border-slate-200 hover:border-emerald-300 transition-colors">
                                C$1000
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Financial Calculation Summary -->
                <div class="p-4 border-t border-slate-200/80 bg-white space-y-1.5 text-xs">
                    <div class="flex items-center justify-between text-slate-500">
                        <span>Subtotal:</span>
                        <span class="font-mono font-semibold text-slate-700" id="val_subtotal">C$ 0.00</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500" id="row_discount" style="display: none;">
                        <span>Descuento aplicado:</span>
                        <span class="font-mono font-semibold text-rose-600" id="val_discount">-C$ 0.00</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500">
                        <span>IVA ({{ $posEffectiveTaxPercentage }}%):</span>
                        <span class="font-mono font-semibold text-slate-700" id="val_tax">C$ 0.00</span>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-900 uppercase">Total a Cobrar:</span>
                        <span class="text-2xl font-extrabold font-mono text-emerald-600" id="val_total">C$ 0.00</span>
                    </div>

                    <!-- Cambio / Vuelto -->
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-200" id="row_change">
                        <span class="text-xs font-bold text-slate-700" id="lbl_change">Cambio / Vuelto:</span>
                        <span class="font-mono font-extrabold text-sm text-slate-900" id="val_change">C$ 0.00</span>
                    </div>

                    <!-- Inline Error Banner (Non-blocking) -->
                    <div id="posErrorAlert" role="alert" aria-live="polite" class="hidden p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-[11px] font-semibold flex items-center gap-2">
                        <i class="bi bi-exclamation-octagon-fill text-rose-600"></i>
                        <span id="posErrorText"></span>
                    </div>

                    <!-- Checkout Button -->
                    <button type="button"
                            id="submitSaleButton"
                            onclick="submitSale()"
                            disabled
                            class="btn-shine w-full mt-2 flex items-center justify-center gap-2 py-3 px-4 rounded-2xl text-sm font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 disabled:opacity-50 disabled:cursor-not-allowed shadow-md shadow-emerald-500/20 transition-all active:scale-98">
                        <i class="bi bi-check2-circle text-lg"></i>
                        <span id="submitSaleLabel">COBRAR Y REGISTRAR VENTA [F4]</span>
                    </button>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Native Top-Layer Modal Dialog for Post-Sale Receipt & Change (Modern Web Guidance) -->
<dialog id="postSaleDialog" aria-labelledby="postSaleDialogTitle" closedby="any"
        class="m-auto w-[calc(100%-2rem)] max-w-md rounded-3xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl open:animate-scale-in">
    <div class="p-6 space-y-5">
        <div class="text-center space-y-1.5">
            <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto shadow-sm">
                <i class="bi bi-check2-circle"></i>
            </div>
            <h2 id="postSaleDialogTitle" class="text-lg font-extrabold text-slate-900">¡Venta Registrada con Éxito!</h2>
            <p class="text-xs font-mono text-slate-500">Comprobante <strong id="modalInvoiceNumber" class="text-slate-800">---</strong></p>
        </div>

        <div class="p-4 rounded-2xl bg-slate-900 text-white space-y-2.5">
            <div class="flex items-center justify-between text-xs text-slate-300">
                <span>Total Cobrado:</span>
                <strong id="modalSaleTotal" class="font-mono text-base text-white">C$ 0.00</strong>
            </div>
            <div id="modalChangeWrapper" class="pt-2.5 border-t border-slate-800 flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Cambio a Entregar:</span>
                <strong id="modalSaleChange" class="font-mono text-2xl font-black text-emerald-400">C$ 0.00</strong>
            </div>
        </div>

        <div class="space-y-2">
            <button type="button" id="btnPrintThermalDirect" onclick="printThermalTicketDirect()" autofocus
                    class="w-full py-3 px-4 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs flex items-center justify-center gap-2 shadow-md transition-all">
                <i class="bi bi-printer-fill text-base"></i>
                <span>IMPRIMIR TICKET TÉRMICO ({{ $posReceiptFormat === 'ticket_58mm' ? '58mm' : '80mm' }})</span>
            </button>

            <div class="grid grid-cols-2 gap-2">
                <a id="btnOpenThermalPreview" href="#" target="_blank"
                   class="py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs flex items-center justify-center gap-1.5 transition-colors">
                    <i class="bi bi-receipt"></i>
                    <span>Ver Ticket 58/80mm</span>
                </a>
                <a id="btnDownloadA4Pdf" href="#"
                   class="py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs flex items-center justify-center gap-1.5 transition-colors">
                    <i class="bi bi-file-earmark-pdf text-rose-600"></i>
                    <span>Descargar PDF A4</span>
                </a>
            </div>
        </div>

        <form method="dialog" class="pt-1 border-t border-slate-100">
            <button value="new-sale" class="w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition-colors">
                Nueva Venta [Esc]
            </button>
        </form>
    </div>
</dialog>

<!-- Hidden iframe for direct / silent thermal ticket printing -->
<iframe id="silentPrintFrame" title="Impresión directa de ticket térmico" class="hidden w-0 h-0 border-0"></iframe>
@endsection

@section('scripts')
<script>
    const rawProducts = @json($products->toArray());
    const posTaxRate = {{ $posEffectiveTaxPercentage / 100.0 }};
    const defaultReceiptFormat = @json($posReceiptFormat === 'ticket_58mm' ? 'ticket_58mm' : 'ticket_80mm');

    const products = rawProducts.map(product => ({
        ...product,
        stock: Number(product.stock),
        price: Number(product.price),
    }));

    let cart = [];
    let activeCategory = '';
    let selectedPaymentMethod = 'cash';
    let paidManuallyEdited = false;
    let currentIdempotencyKey = generateUUID();
    let lastCompletedSaleId = null;

    const productGrid = document.getElementById('productGrid');
    const cartItemsContainer = document.getElementById('cartItemsContainer');
    const emptyCartMessage = document.getElementById('emptyCartMessage');
    const subtotalEl = document.getElementById('val_subtotal');
    const discountEl = document.getElementById('val_discount');
    const rowDiscountEl = document.getElementById('row_discount');
    const taxEl = document.getElementById('val_tax');
    const totalEl = document.getElementById('val_total');
    const changeEl = document.getElementById('val_change');
    const lblChangeEl = document.getElementById('lbl_change');
    const rowChangeEl = document.getElementById('row_change');
    const posSearch = document.getElementById('posSearch');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const scanFeedbackBadge = document.getElementById('scanFeedbackBadge');
    const submitButton = document.getElementById('submitSaleButton');
    const submitSaleLabel = document.getElementById('submitSaleLabel');
    const saleDiscountInput = document.getElementById('sale_discount');
    const salePaidAmountInput = document.getElementById('sale_paid_amount');
    const cashPaidWrapper = document.getElementById('cashPaidWrapper');
    const quickCashButtons = document.getElementById('quickCashButtons');
    const posErrorAlert = document.getElementById('posErrorAlert');
    const posErrorText = document.getElementById('posErrorText');
    const postSaleDialog = document.getElementById('postSaleDialog');
    const silentPrintFrame = document.getElementById('silentPrintFrame');

    function generateUUID() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        return 'pos-' + Date.now() + '-' + Math.random().toString(36).substring(2, 10);
    }

    function formatCurrency(value) {
        return 'C$ ' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function showPosError(msg) {
        if (!msg) {
            posErrorAlert.classList.add('hidden');
            posErrorText.textContent = '';
            return;
        }
        posErrorText.textContent = msg;
        posErrorAlert.classList.remove('hidden');
    }

    function flashScanFeedback(label) {
        if (!scanFeedbackBadge) return;
        scanFeedbackBadge.textContent = label || '+1 Agregado';
        scanFeedbackBadge.classList.remove('hidden');
        clearTimeout(window.__scanBadgeTimer);
        window.__scanBadgeTimer = setTimeout(() => {
            scanFeedbackBadge.classList.add('hidden');
        }, 1200);
    }

    function computeCurrentTotal() {
        const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
        const discountVal = Math.max(0, parseFloat(saleDiscountInput.value) || 0);
        const effectiveDiscount = Math.min(discountVal, subtotal);
        const taxableBase = Math.max(0, subtotal - effectiveDiscount);
        const tax = Math.round(taxableBase * posTaxRate * 100) / 100;
        const total = Math.round((taxableBase + tax) * 100) / 100;
        return { subtotal, effectiveDiscount, tax, total };
    }

    function setExactCash() {
        paidManuallyEdited = false;
        const { total } = computeCurrentTotal();
        salePaidAmountInput.value = total.toFixed(2);
        showPosError('');
        updateTotals();
    }

    function setQuickBill(billAmount) {
        paidManuallyEdited = true;
        salePaidAmountInput.value = Number(billAmount).toFixed(2);
        showPosError('');
        updateTotals();
    }

    function onManualPaidInput() {
        paidManuallyEdited = true;
        showPosError('');
        updateTotals();
    }

    function onDiscountInput() {
        showPosError('');
        updateTotals();
    }

    function setPaymentMethod(method) {
        selectedPaymentMethod = method;
        showPosError('');
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
            quickCashButtons.style.display = 'block';
            rowChangeEl.style.display = 'flex';
        } else {
            cashPaidWrapper.style.display = 'none';
            quickCashButtons.style.display = 'none';
            rowChangeEl.style.display = 'none';
        }

        updateTotals();
    }

    function filterByCategory(category, btnEl) {
        activeCategory = category;

        document.querySelectorAll('.cat-pill').forEach(btn => {
            btn.classList.remove('bg-slate-900', 'text-white', 'shadow-sm');
            btn.classList.add('bg-slate-100', 'text-slate-700');
        });

        if (btnEl) {
            btnEl.classList.remove('bg-slate-100', 'text-slate-700');
            btnEl.classList.add('bg-slate-900', 'text-white', 'shadow-sm');
        }

        applyFilters();
    }

    function getFilteredProducts() {
        const term = posSearch.value.toLowerCase().trim();
        return products.filter(product => {
            const matchesCategory = !activeCategory || product.category === activeCategory;
            const matchesSearch = !term ||
                product.name.toLowerCase().includes(term) ||
                (product.category && product.category.toLowerCase().includes(term)) ||
                (product.sku && String(product.sku).toLowerCase().includes(term)) ||
                (product.barcode && String(product.barcode).toLowerCase().includes(term)) ||
                (product.description || '').toLowerCase().includes(term);

            return matchesCategory && matchesSearch;
        });
    }

    function applyFilters() {
        renderProducts(getFilteredProducts());
    }

    function renderProducts(productList) {
        if (!productGrid) return;
        productGrid.innerHTML = '';

        if (!productList.length) {
            productGrid.innerHTML = `
                <div class="col-span-full py-12 text-center text-slate-400 space-y-2 bg-white rounded-2xl border border-slate-200/80">
                    <i class="bi bi-search text-2xl text-slate-300"></i>
                    <p class="text-xs font-semibold text-slate-600">No se encontraron productos coincidentes</p>
                    <p class="text-[11px] text-slate-400">Verifica el código de barras, SKU o categoría seleccionada.</p>
                </div>
            `;
            return;
        }

        productList.forEach((product, idx) => {
            const disabled = product.stock <= 0;
            const card = document.createElement('div');
            card.style.animationDelay = Math.min(idx * 20, 160) + 'ms';
            card.className = `animate-scale-in p-3.5 rounded-2xl bg-white border border-slate-200/80 transition-all flex flex-col justify-between ${disabled ? 'opacity-55 cursor-not-allowed bg-slate-50' : 'saas-card-hover hover:border-emerald-500/50 cursor-pointer group active:scale-98'}`;

            if (!disabled) {
                card.onclick = (e) => {
                    if (!e.target.closest('button')) {
                        addProductToCart(product.id);
                    }
                };
            }

            const barcodeBadge = product.barcode
                ? `<span class="text-[10px] font-mono text-slate-400 flex items-center gap-0.5 truncate max-w-[110px]"><i class="bi bi-upc"></i>${escapeHtml(product.barcode)}</span>`
                : `<span class="text-[10px] text-slate-400 truncate max-w-[100px]">${escapeHtml(product.category || 'General')}</span>`;

            card.innerHTML = `
                <div>
                    <div class="flex items-center justify-between gap-1 mb-1.5">
                        <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold font-mono ${disabled ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700'}">
                            ${escapeHtml(product.sku || 'SKU')}
                        </span>
                        ${barcodeBadge}
                    </div>

                    <h3 class="text-xs font-bold text-slate-900 group-hover:text-emerald-700 transition-colors line-clamp-2 ${disabled ? 'line-through text-slate-400' : ''}">
                        ${escapeHtml(product.name)}
                    </h3>
                </div>

                <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="text-sm font-extrabold font-mono text-slate-900 block">
                            ${formatCurrency(product.price)}
                        </span>
                        <span class="text-[10px] text-slate-400">
                            Stock: <strong class="${product.stock <= 5 ? 'text-amber-600' : 'text-slate-700'}">${product.stock}</strong> ${escapeHtml(product.unit || 'un')}
                        </span>
                    </div>

                    <button type="button"
                            aria-label="Agregar ${escapeHtml(product.name)}"
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

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderCart() {
        cartItemsContainer.innerHTML = '';
        if (!cart.length) {
            cartItemsContainer.appendChild(emptyCartMessage);
            paidManuallyEdited = false;
            salePaidAmountInput.value = '0.00';
            updateTotals();
            return;
        }

        cart.forEach(item => {
            const itemRow = document.createElement('div');
            itemRow.className = 'animate-scale-in flex items-center justify-between p-2 rounded-xl bg-slate-50 hover:bg-slate-100/80 border border-slate-100 transition-colors gap-2';
            itemRow.innerHTML = `
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-slate-900 truncate">${escapeHtml(item.name)}</p>
                    <p class="text-[10px] text-slate-500 font-mono">${formatCurrency(item.unit_price)} / ${escapeHtml(item.unit)}</p>
                </div>

                <div class="flex items-center gap-1.5">
                    <div class="inline-flex items-center border border-slate-200 rounded-lg bg-white overflow-hidden shadow-subtle">
                        <button type="button" onclick="decrementCartItem(${item.id})" class="px-2 py-0.5 text-xs text-slate-500 hover:bg-slate-100" aria-label="Disminuir cantidad">-</button>
                        <span class="px-2 py-0.5 text-xs font-mono font-bold text-slate-800">${item.quantity}</span>
                        <button type="button" onclick="incrementCartItem(${item.id})" class="px-2 py-0.5 text-xs text-slate-500 hover:bg-slate-100" aria-label="Aumentar cantidad">+</button>
                    </div>

                    <span class="font-mono font-bold text-xs text-slate-900 w-20 text-right">${formatCurrency(item.subtotal)}</span>

                    <button type="button" onclick="removeCartItem(${item.id})" class="text-slate-400 hover:text-rose-600 p-1" title="Quitar artículo" aria-label="Quitar artículo">
                        <i class="bi bi-trash3 text-xs"></i>
                    </button>
                </div>
            `;
            cartItemsContainer.appendChild(itemRow);
        });

        updateTotals();
    }

    function addProductToCart(productId) {
        showPosError('');
        const product = products.find(item => item.id === productId);
        if (!product || product.stock <= 0) {
            showPosError('Este producto no tiene existencias disponibles.');
            return false;
        }

        const existingItem = cart.find(item => item.id === productId);
        if (existingItem) {
            if (existingItem.quantity + 1 > product.stock) {
                showPosError(`Existencias limitadas: solo hay ${product.stock} disponibles de "${product.name}".`);
                return false;
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
        return true;
    }

    function incrementCartItem(productId) {
        const product = products.find(item => item.id === productId);
        const item = cart.find(item => item.id === productId);
        if (!product || !item) return;

        if (item.quantity + 1 > product.stock) {
            showPosError(`Límite de stock alcanzado: máximo ${product.stock} unidades de "${product.name}".`);
            return;
        }

        showPosError('');
        item.quantity += 1;
        item.subtotal = item.quantity * item.unit_price;
        renderCart();
    }

    function decrementCartItem(productId) {
        showPosError('');
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
        showPosError('');
        cart = cart.filter(item => item.id !== productId);
        renderCart();
    }

    function clearCart() {
        if (!cart.length) return;
        cart = [];
        paidManuallyEdited = false;
        currentIdempotencyKey = generateUUID();
        saleDiscountInput.value = '0.00';
        salePaidAmountInput.value = '0.00';
        showPosError('');
        renderCart();
    }

    function updateTotals() {
        const discountVal = Math.max(0, parseFloat(saleDiscountInput.value) || 0);
        const rawSubtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);

        if (discountVal > rawSubtotal && rawSubtotal > 0) {
            saleDiscountInput.value = rawSubtotal.toFixed(2);
        }

        const { subtotal, effectiveDiscount, tax, total } = computeCurrentTotal();

        subtotalEl.textContent = formatCurrency(subtotal);

        if (effectiveDiscount > 0) {
            rowDiscountEl.style.display = 'flex';
            discountEl.textContent = '-' + formatCurrency(effectiveDiscount);
        } else {
            rowDiscountEl.style.display = 'none';
        }

        taxEl.textContent = formatCurrency(tax);
        totalEl.textContent = formatCurrency(total);

        if (selectedPaymentMethod === 'cash') {
            if (!paidManuallyEdited) {
                salePaidAmountInput.value = total.toFixed(2);
            }
            const paid = parseFloat(salePaidAmountInput.value) || 0;
            const diff = paid - total;

            if (diff < -0.009 && cart.length > 0) {
                lblChangeEl.textContent = 'Falta por cubrir:';
                changeEl.textContent = formatCurrency(Math.abs(diff));
                changeEl.classList.add('text-rose-600');
                changeEl.classList.remove('text-slate-900', 'text-emerald-700');
            } else {
                lblChangeEl.textContent = 'Cambio / Vuelto:';
                changeEl.textContent = formatCurrency(Math.max(0, diff));
                changeEl.classList.remove('text-rose-600');
                changeEl.classList.toggle('text-emerald-700', diff > 0.009);
                changeEl.classList.toggle('text-slate-900', diff <= 0.009);
            }
        }

        submitButton.disabled = cart.length === 0;
        if (submitSaleLabel) {
            submitSaleLabel.textContent = cart.length > 0
                ? `COBRAR ${formatCurrency(total)} [F4]`
                : 'COBRAR Y REGISTRAR VENTA [F4]';
        }
    }

    function handleCustomerSelect(sel) {
        const opt = sel.options[sel.selectedIndex];
        const custId = opt.value || '';
        const custName = opt.dataset.name || 'Consumidor Final';
        const custDoc = opt.dataset.doc || '---';

        document.getElementById('customer_id').value = custId;
        document.getElementById('customer_name').value = custName;
        document.getElementById('customer_dni').value = custDoc;
    }

    function getCsrfToken() {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        return tokenMeta ? tokenMeta.getAttribute('content') : '';
    }

    function printThermalTicketDirect() {
        if (!lastCompletedSaleId || !silentPrintFrame) return;
        const url = `{{ url('sales') }}/${lastCompletedSaleId}/ticket?format=${encodeURIComponent(defaultReceiptFormat)}&autoprint=0&_t=${Date.now()}`;
        silentPrintFrame.onload = function () {
            try {
                silentPrintFrame.contentWindow.focus();
                silentPrintFrame.contentWindow.print();
            } catch (err) {
                window.open(`{{ url('sales') }}/${lastCompletedSaleId}/ticket?format=${encodeURIComponent(defaultReceiptFormat)}&autoprint=1`, '_blank');
            }
        };
        silentPrintFrame.src = url;
    }

    async function submitSale() {
        if (!cart.length) {
            showPosError('Agrega al menos un producto antes de cobrar.');
            return;
        }

        showPosError('');
        const { effectiveDiscount, total } = computeCurrentTotal();
        let paidAmount = total;

        if (selectedPaymentMethod === 'cash') {
            paidAmount = parseFloat(salePaidAmountInput.value);
            if (isNaN(paidAmount) || paidAmount <= 0) {
                paidAmount = total;
                salePaidAmountInput.value = total.toFixed(2);
            }
            if (paidAmount + 0.009 < total) {
                showPosError(`El efectivo recibido (${formatCurrency(paidAmount)}) es menor al total a cobrar (${formatCurrency(total)}).`);
                salePaidAmountInput.focus();
                salePaidAmountInput.select();
                return;
            }
        }

        const soldItemsSnapshot = cart.map(item => ({ ...item }));

        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="inline-block animate-spin mr-2"><i class="bi bi-arrow-repeat"></i></span> Procesando cobro...';

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
                    discount: effectiveDiscount,
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

            // Descontar existencias localmente al instante para continuar vendiendo sin recargar
            if (!data.idempotent) {
                soldItemsSnapshot.forEach(sold => {
                    const p = products.find(prod => prod.id === sold.id);
                    if (p) {
                        p.stock = Math.max(0, p.stock - sold.quantity);
                    }
                });
            }

            lastCompletedSaleId = data.sale_id;
            const finalTotal = Number(data.total ?? total);
            const finalChange = selectedPaymentMethod === 'cash' ? Number(data.change_amount ?? Math.max(0, paidAmount - finalTotal)) : 0;

            document.getElementById('modalInvoiceNumber').textContent = data.invoice_number || ('#' + data.sale_id);
            document.getElementById('modalSaleTotal').textContent = formatCurrency(finalTotal);
            document.getElementById('modalSaleChange').textContent = formatCurrency(finalChange);
            document.getElementById('modalChangeWrapper').style.display = selectedPaymentMethod === 'cash' ? 'flex' : 'none';
            document.getElementById('btnOpenThermalPreview').href = `{{ url('sales') }}/${data.sale_id}/ticket?format=${encodeURIComponent(defaultReceiptFormat)}&autoprint=1`;

            const pdfUrl = new URL(`{{ url('sales') }}/${data.sale_id}/pdf`);
            pdfUrl.searchParams.set('customer_name', customerName);
            pdfUrl.searchParams.set('customer_document', customerDocument);
            document.getElementById('btnDownloadA4Pdf').href = pdfUrl.toString();

            // Limpiar carrito y dejar listo para la siguiente venta
            cart = [];
            paidManuallyEdited = false;
            currentIdempotencyKey = generateUUID();
            saleDiscountInput.value = '0.00';
            salePaidAmountInput.value = '0.00';
            submitButton.innerHTML = '<i class="bi bi-check2-circle text-lg"></i> <span id="submitSaleLabel">COBRAR Y REGISTRAR VENTA [F4]</span>';
            applyFilters();
            renderCart();

            if (postSaleDialog && typeof postSaleDialog.showModal === 'function') {
                postSaleDialog.showModal();
            }
        } catch (error) {
            showPosError(error.message);
            submitButton.disabled = cart.length === 0;
            submitButton.innerHTML = `<i class="bi bi-check2-circle text-lg"></i> <span id="submitSaleLabel">COBRAR ${formatCurrency(total)} [F4]</span>`;
        }
    }

    // Devolver el foco al buscador/lector de barras al cerrar el modal de éxito
    if (postSaleDialog) {
        postSaleDialog.addEventListener('close', () => {
            postSaleDialog.returnValue = '';
            setTimeout(() => posSearch.focus(), 40);
        });

        // Fallback para light-dismiss (closedby="any") en navegadores sin soporte nativo
        if (!('closedBy' in HTMLDialogElement.prototype)) {
            const outside = (e, dialog) => {
                const r = dialog.getBoundingClientRect();
                return e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
            };
            let pressedOutside = false;
            postSaleDialog.addEventListener('pointerdown', (e) => { pressedOutside = outside(e, postSaleDialog); });
            postSaleDialog.addEventListener('pointerup', (e) => { if (pressedOutside && outside(e, postSaleDialog)) postSaleDialog.close(); });
        }
    }

    // Soporte de lector de código de barras / Enter rápido en el buscador (IME-safe)
    posSearch.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.isComposing) {
            const rawTerm = posSearch.value.trim();
            if (!rawTerm) return;

            e.preventDefault();
            const lowerTerm = rawTerm.toLowerCase();

            // Prioridad 1: coincidencia exacta de código de barras o SKU
            let matchedProduct = products.find(p =>
                (p.barcode && String(p.barcode).toLowerCase() === lowerTerm) ||
                (p.sku && String(p.sku).toLowerCase() === lowerTerm)
            );

            // Prioridad 2: único resultado filtrado
            if (!matchedProduct) {
                const filtered = getFilteredProducts();
                if (filtered.length === 1) {
                    matchedProduct = filtered[0];
                }
            }

            if (matchedProduct) {
                if (addProductToCart(matchedProduct.id)) {
                    flashScanFeedback(`+1 ${matchedProduct.sku || 'Agregado'}`);
                    posSearch.value = '';
                    clearSearchBtn.classList.add('hidden');
                    applyFilters();
                }
            } else {
                showPosError(`No se encontró un producto exacto para "${rawTerm}".`);
            }
        }
    });

    // Atajos globales de teclado + Auto-captura para lectores de código de barras USB/Bluetooth
    document.addEventListener('keydown', function (e) {
        if (postSaleDialog && postSaleDialog.open) {
            return;
        }

        const activeTag = document.activeElement ? document.activeElement.tagName : '';
        const isTypingField = ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeTag);

        if (e.key === 'F2') {
            e.preventDefault();
            posSearch.focus();
            posSearch.select();
            return;
        }

        if (e.key === 'F4') {
            e.preventDefault();
            if (!submitButton.disabled) {
                submitSale();
            }
            return;
        }

        // Barra espaciadora para cobrar cuando no se está escribiendo en un input ni sobre un botón
        if (e.key === ' ' && !isTypingField && activeTag !== 'BUTTON' && activeTag !== 'A') {
            if (!submitButton.disabled) {
                e.preventDefault();
                submitSale();
            }
            return;
        }

        if (e.key === 'Escape') {
            if (posSearch.value) {
                posSearch.value = '';
                clearSearchBtn.classList.add('hidden');
                applyFilters();
            }
            posSearch.focus();
            return;
        }

        // Captura automática de lector de código de barras cuando el foco está fuera de inputs
        if (!isTypingField && !e.ctrlKey && !e.altKey && !e.metaKey && e.key.length === 1 && /[a-zA-Z0-9\-_]/.test(e.key)) {
            posSearch.focus();
        }
    });

    posSearch.addEventListener('input', function () {
        clearSearchBtn.classList.toggle('hidden', !this.value.trim());
        showPosError('');
        applyFilters();
    });

    clearSearchBtn.addEventListener('click', function () {
        posSearch.value = '';
        this.classList.add('hidden');
        applyFilters();
        posSearch.focus();
    });

    document.addEventListener('DOMContentLoaded', function () {
        const customerSelector = document.getElementById('customer_selector');
        if (customerSelector && customerSelector.value) {
            handleCustomerSelect(customerSelector);
        }
        renderProducts(products);
        renderCart();
        setPaymentMethod('cash');
    });
</script>
@endsection
