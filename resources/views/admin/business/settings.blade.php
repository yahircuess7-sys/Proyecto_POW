@extends('layouts.app')

@section('title', 'Configuración del Negocio - PuntoÁgil')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <span>Administración</span>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-emerald-600 font-semibold">Configuración de Empresa</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $business->name }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">Parámetros comerciales, fiscales, formato de tickets y zona horaria configurables por el Propietario.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-mono font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Slug: {{ $business->slug }}
            </span>
            <a href="{{ route('admin.subscription.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-sm transition-all flex items-center gap-1.5">
                <i class="bi bi-patch-check text-emerald-600"></i>
                <span>Mi Suscripción</span>
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="bi bi-check-circle-fill text-emerald-600 text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <i class="bi bi-exclamation-triangle-fill"></i>
                Por favor corrige los siguientes errores:
            </div>
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Summary KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Miembros Autorizados</span>
            <p class="text-2xl font-bold font-mono text-slate-900 mt-1">{{ $business->users_count ?? 0 }}</p>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Productos en Catálogo</span>
            <p class="text-2xl font-bold font-mono text-slate-900 mt-1">{{ $business->products_count ?? 0 }}</p>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Ventas Emitidas</span>
            <p class="text-2xl font-bold font-mono text-slate-900 mt-1">{{ $business->sales_count ?? 0 }}</p>
        </div>
    </div>

    <!-- Configuration Form -->
    <form action="{{ route('admin.business.update') }}" method="POST"
          class="bg-white border border-slate-200/80 rounded-3xl shadow-sm overflow-hidden text-xs">
        @csrf
        @method('PUT')

        <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Datos Comerciales y Fiscales (Normativa DGI Nicaragua - Ley No. 822)</h2>
                <p class="text-xs text-slate-500">Se reflejan automáticamente en los tickets de venta, comprobantes internos y reportes contables.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="applyNicaraguaPreset()"
                        class="px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-bold text-[11px] transition-colors flex items-center gap-1.5">
                    <i class="bi bi-flag-fill"></i>
                    <span>Aplicar Perfil Fiscal Nicaragua (C$ / IVA 15%)</span>
                </button>
            </div>
        </div>

        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block font-semibold text-slate-700 mb-1.5">Razón Social / Nombre Comercial <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $business->name) }}" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1.5">Número RUC (DGI Nicaragua - 14 caracteres) / Identificación Fiscal</label>
                <input type="text" name="tax_id" id="input_tax_id" value="{{ old('tax_id', $business->tax_id) }}"
                       placeholder="ej. J0310000000001 o 0010101900001A"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono focus:bg-white focus:outline-none">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1.5">Régimen Tributario (Ley No. 822 LCT)</label>
                @php
                    $currentRegime = old('tax_regime', $business->settings['tax_regime'] ?? 'regimen_general');
                @endphp
                <select name="tax_regime" id="input_tax_regime" onchange="handleTaxRegimeChange(this.value)"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
                    <option value="regimen_general" {{ $currentRegime === 'regimen_general' ? 'selected' : '' }}>Régimen General (Responsable Recaudador IVA - 15%)</option>
                    <option value="cuota_fija" {{ $currentRegime === 'cuota_fija' ? 'selected' : '' }}>Régimen Simplificado de Cuota Fija (No traslada IVA - 0%)</option>
                    <option value="exento" {{ $currentRegime === 'exento' ? 'selected' : '' }}>Exento de IVA (0%)</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1.5">Departamento / Municipio</label>
                <input type="text" name="municipality" id="input_municipality" value="{{ old('municipality', $business->settings['municipality'] ?? 'Managua, Nicaragua') }}"
                       placeholder="ej. Managua, Nicaragua"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
            </div>

            <div class="sm:col-span-2">
                <label class="block font-semibold text-slate-700 mb-1.5">Lema Comercial / Slogan</label>
                <input type="text" name="slogan" value="{{ old('slogan', $business->settings['slogan'] ?? '') }}"
                       placeholder="ej. Lo mejor para tu hogar y tu negocio"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1.5">Correo Corporativo</label>
                <input type="email" name="email" value="{{ old('email', $business->email) }}"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1.5">Teléfono de Contacto</label>
                <input type="text" name="phone" value="{{ old('phone', $business->phone) }}"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
            </div>

            <div class="sm:col-span-2">
                <label class="block font-semibold text-slate-700 mb-1.5">Dirección de Sucursal / Casa Matriz</label>
                <input type="text" name="address" value="{{ old('address', $business->address) }}"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1.5">Moneda Operativa de Facturación</label>
                <input type="hidden" name="currency" id="input_currency" value="NIO">
                <div class="w-full px-3.5 py-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200 text-emerald-800 font-semibold flex items-center justify-between">
                    <span>Córdoba Nicaragüense (C$ - NIO)</span>
                    <span class="text-[10px] font-mono bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-md">Oficial</span>
                </div>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1.5">Alícuota IVA (%) <span class="text-rose-500">*</span></label>
                <input type="number" step="0.01" name="tax_percentage" id="input_tax_percentage" value="{{ old('tax_percentage', $business->settings['tax_percentage'] ?? 15) }}" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono focus:bg-white focus:outline-none">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1.5">Zona Horaria <span class="text-rose-500">*</span></label>
                <input type="text" name="timezone" id="input_timezone" value="{{ old('timezone', $business->settings['timezone'] ?? 'America/Managua') }}" required
                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono focus:bg-white focus:outline-none">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1.5">URL de Logo para Ticket / Factura (Opcional)</label>
                <input type="url" name="logo_url" value="{{ old('logo_url', $business->settings['logo_url'] ?? '') }}"
                       placeholder="https://ejemplo.com/mi-logo.png"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
            </div>
        </div>

        <!-- Receipt & Printing Options (Módulo 16) -->
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Opciones de Impresión y Comprobantes</h3>
            <p class="text-[11px] text-slate-500">Personaliza la presentación de los tickets generados en el Punto de Venta (POS).</p>
        </div>

        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5 pt-4">
            <div>
                <label class="block font-semibold text-slate-700 mb-1.5">Formato de Ticket / Impresión</label>
                @php
                    $currentFormat = old('receipt_format', $business->settings['receipt_format'] ?? 'ticket_80mm');
                @endphp
                <select name="receipt_format"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
                    <option value="ticket_80mm" {{ $currentFormat === 'ticket_80mm' ? 'selected' : '' }}>Ticket Térmico 80mm (Estándar)</option>
                    <option value="ticket_58mm" {{ $currentFormat === 'ticket_58mm' ? 'selected' : '' }}>Ticket Térmico 58mm (Compacto)</option>
                    <option value="a4_pdf" {{ $currentFormat === 'a4_pdf' ? 'selected' : '' }}>Comprobante Formato A4 / Carta</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1.5">Leyenda al Pie del Ticket</label>
                <input type="text" name="receipt_footer_note"
                       value="{{ old('receipt_footer_note', $business->settings['receipt_footer_note'] ?? 'Comprobante interno emitido por PuntoÁgil Cloud POS.') }}"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
            </div>
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition-all">
                <i class="bi bi-check-lg"></i>
                <span>Guardar Configuración</span>
            </button>
        </div>
    </form>
</div>

<script>
    function applyNicaraguaPreset() {
        document.getElementById('input_tax_regime').value = 'regimen_general';
        document.getElementById('input_currency').value = 'NIO';
        document.getElementById('input_tax_percentage').value = '15';
        document.getElementById('input_timezone').value = 'America/Managua';
        const munInput = document.getElementById('input_municipality');
        if (munInput && !munInput.value.trim()) {
            munInput.value = 'Managua, Nicaragua';
        }
    }

    function handleTaxRegimeChange(regime) {
        const taxInput = document.getElementById('input_tax_percentage');
        if (!taxInput) return;
        if (regime === 'cuota_fija' || regime === 'exento') {
            taxInput.value = '0';
        } else if (parseFloat(taxInput.value) === 0) {
            taxInput.value = '15';
        }
    }
</script>
@endsection
