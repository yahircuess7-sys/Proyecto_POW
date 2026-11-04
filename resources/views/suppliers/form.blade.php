@extends('layouts.app')

@section('title', isset($supplier) ? 'Editar Proveedor - PuntoÁgil' : 'Nuevo Proveedor - PuntoÁgil')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Back Navigation -->
    <div class="flex items-center justify-between">
        <a href="{{ route('suppliers.index') }}" 
           class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="bi bi-arrow-left text-sm"></i>
            <span>Volver a Proveedores</span>
        </a>

        <span class="text-xs text-slate-400 font-mono">
            {{ isset($supplier) ? 'ID #' . $supplier->id . ' · ' . $supplier->tax_id : 'Nuevo Registro de Proveedor' }}
        </span>
    </div>

    <!-- Form Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
        
        <!-- Header -->
        <div class="px-6 py-5 border-b border-slate-200/80 bg-slate-50/50 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-slate-900">
                    {{ isset($supplier) ? 'Editar Ficha del Proveedor' : 'Registrar Nuevo Proveedor' }}
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Información fiscal, comercial, asesores de venta y canales de abastecimiento.
                </p>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                <i class="bi bi-building"></i>
            </div>
        </div>

        <!-- Form Body -->
        <form action="{{ isset($supplier) ? route('suppliers.update', $supplier->id) : route('suppliers.store') }}" 
              method="POST" 
              class="p-6 sm:p-8 space-y-6 text-xs">
            @csrf
            @if(isset($supplier))
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Tax ID -->
                <div>
                    <label for="tax_id" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Identificación Fiscal / RUC / RFC / NIT <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="tax_id" id="tax_id" 
                           class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('tax_id') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-slate-50/50' }} font-mono text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                           placeholder="Ej: 1790012345001" 
                           value="{{ old('tax_id', $supplier->tax_id ?? '') }}" 
                           required>
                    <p class="mt-1 text-[11px] text-slate-400">Identificador fiscal único de la empresa proveedora.</p>
                    @error('tax_id')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Supplier Name / Company -->
                <div>
                    <label for="name" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Razón Social / Nombre Comercial <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" 
                           class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('name') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-slate-50/50' }} text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800 font-medium"
                           placeholder="Ej: Distribuidora Nacional de Aceros S.A." 
                           value="{{ old('name', $supplier->name ?? '') }}" 
                           required>
                    @error('name')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Contact Person -->
                <div>
                    <label for="contact_name" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Nombre del Asesor / Contacto Comercial
                    </label>
                    <input type="text" name="contact_name" id="contact_name" 
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                           placeholder="Ej: Ing. Carlos Mendoza (Ventas Mayoristas)" 
                           value="{{ old('contact_name', $supplier->contact_name ?? '') }}">
                    @error('contact_name')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Teléfono / Línea Directa
                    </label>
                    <input type="text" name="phone" id="phone" 
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 font-mono text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                           placeholder="Ej: +593 2 299 8877 / +593 99 888 7766" 
                           value="{{ old('phone', $supplier->phone ?? '') }}">
                    @error('phone')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Correo Electrónico para Pedidos y Cotizaciones
                    </label>
                    <input type="email" name="email" id="email" 
                           class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('email') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-slate-50/50' }} text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                           placeholder="ventas@proveedor.com" 
                           value="{{ old('email', $supplier->email ?? '') }}">
                    @error('email')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Estado del Proveedor
                    </label>
                    <select name="status" id="status" 
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700">
                        <option value="active" {{ old('status', $supplier->status ?? 'active') === 'active' ? 'selected' : '' }}>Activo (Habilitado para compras)</option>
                        <option value="inactive" {{ old('status', $supplier->status ?? 'active') === 'inactive' ? 'selected' : '' }}>Desactivado (Suspendido temporalmente)</option>
                    </select>
                </div>

                <!-- Address -->
                <div class="md:col-span-2">
                    <label for="address" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Dirección de Bodega o Matriz
                    </label>
                    <input type="text" name="address" id="address" 
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                           placeholder="Parque Industrial Lote #15, Km 4.5 Vía Daule" 
                           value="{{ old('address', $supplier->address ?? '') }}">
                    @error('address')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Notes -->
                <div class="md:col-span-2">
                    <label for="notes" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Condiciones Comerciales y Observaciones
                    </label>
                    <textarea name="notes" id="notes" rows="3" 
                              class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800 placeholder-slate-400"
                              placeholder="Plazos de crédito (30/60 días), descuentos por pronto pago, días de entrega, marcas que distribuye...">{{ old('notes', $supplier->notes ?? '') }}</textarea>
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="pt-6 border-t border-slate-200/80 flex items-center justify-end gap-3">
                <a href="{{ route('suppliers.index') }}" 
                   class="pa-btn-secondary text-xs">
                    Cancelar
                </a>
                <button type="submit" 
                        class="pa-btn-primary text-xs">
                    <i class="bi bi-check2 text-base"></i>
                    <span>{{ isset($supplier) ? 'Guardar Cambios' : 'Registrar Proveedor' }}</span>
                </button>
            </div>
        </form>

    </div>
</div>
@endsection
