@extends('layouts.app')

@section('title', isset($customer) ? 'Editar Cliente - PuntoÁgil' : 'Nuevo Cliente - PuntoÁgil')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Back Navigation -->
    <div class="flex items-center justify-between">
        <a href="{{ route('customers.index') }}" 
           class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="bi bi-arrow-left text-sm"></i>
            <span>Volver a Clientes</span>
        </a>

        <span class="text-xs text-slate-400 font-mono">
            {{ isset($customer) ? 'ID #' . $customer->id . ' · ' . $customer->document_number : 'Nuevo Registro Comercial' }}
        </span>
    </div>

    <!-- Form Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
        
        <!-- Header -->
        <div class="px-6 py-5 border-b border-slate-200/80 bg-slate-50/50 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-slate-900">
                    {{ isset($customer) ? 'Editar Ficha del Cliente' : 'Registrar Nuevo Cliente' }}
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Datos comerciales, identificación fiscal y canales de contacto directo.
                </p>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                <i class="bi bi-person-badge"></i>
            </div>
        </div>

        <!-- Form Body -->
        <form action="{{ isset($customer) ? route('customers.update', $customer->id) : route('customers.store') }}" 
              method="POST" 
              class="p-6 sm:p-8 space-y-6 text-xs">
            @csrf
            @if(isset($customer))
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Document Type -->
                <div>
                    <label for="document_type" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Tipo de Documento <span class="text-rose-500">*</span>
                    </label>
                    <select name="document_type" id="document_type" 
                            class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('document_type') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-slate-50/50' }} text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700" 
                            required>
                        <option value="Cédula" {{ old('document_type', $customer->document_type ?? 'Cédula') === 'Cédula' ? 'selected' : '' }}>Cédula de Identidad (Personal)</option>
                        <option value="RUC" {{ old('document_type', $customer->document_type ?? '') === 'RUC' ? 'selected' : '' }}>RUC (Empresa)</option>
                    </select>
                    @error('document_type')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Document Number -->
                <div>
                    <label for="document_number" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Número de Cédula o RUC <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="document_number" id="document_number" 
                           class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('document_number') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-slate-50/50' }} font-mono text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                           placeholder="Ej: 0928374651 (Cédula) o 1792345678001 (RUC)" 
                           value="{{ old('document_number', $customer->document_number ?? '') }}" 
                           required>
                    <p class="mt-1 text-[11px] text-slate-400">Debe ser único dentro de tu empresa.</p>
                    @error('document_number')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Customer Name -->
                <div class="md:col-span-2">
                    <label for="name" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Nombre Completo o Razón Social <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" 
                           class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('name') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-slate-50/50' }} text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800 font-medium"
                           placeholder="Ej: Constructora San Pedro S.A. o Juan Pérez" 
                           value="{{ old('name', $customer->name ?? '') }}" 
                           required>
                    @error('name')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Correo Electrónico para Facturación
                    </label>
                    <input type="email" name="email" id="email" 
                           class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('email') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-slate-50/50' }} text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                           placeholder="cliente@ejemplo.com" 
                           value="{{ old('email', $customer->email ?? '') }}">
                    @error('email')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Teléfono / Celular de Contacto
                    </label>
                    <input type="text" name="phone" id="phone" 
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 font-mono text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                           placeholder="Ej: +593 99 123 4567" 
                           value="{{ old('phone', $customer->phone ?? '') }}">
                    @error('phone')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Address -->
                <div class="md:col-span-2">
                    <label for="address" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Dirección Domiciliaria o Fiscal
                    </label>
                    <input type="text" name="address" id="address" 
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                           placeholder="Av. Principal #100 y Calle Secundaria" 
                           value="{{ old('address', $customer->address ?? '') }}">
                    @error('address')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Estado de la Cuenta
                    </label>
                    <select name="status" id="status" 
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700">
                        <option value="active" {{ old('status', $customer->status ?? 'active') === 'active' ? 'selected' : '' }}>Activo (Habilitado para Facturación)</option>
                        <option value="inactive" {{ old('status', $customer->status ?? 'active') === 'inactive' ? 'selected' : '' }}>Desactivado (Suspendido temporalmente)</option>
                    </select>
                </div>

                <!-- Notes -->
                <div class="md:col-span-2">
                    <label for="notes" class="block font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Notas y Observaciones Comerciales
                    </label>
                    <textarea name="notes" id="notes" rows="3" 
                              class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800 placeholder-slate-400"
                              placeholder="Condiciones de crédito, persona autorizada para compras, preferencias...">{{ old('notes', $customer->notes ?? '') }}</textarea>
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="pt-6 border-t border-slate-200/80 flex items-center justify-end gap-3">
                <a href="{{ route('customers.index') }}" 
                   class="pa-btn-secondary text-xs">
                    Cancelar
                </a>
                <button type="submit" 
                        class="pa-btn-primary text-xs">
                    <i class="bi bi-check2 text-base"></i>
                    <span>{{ isset($customer) ? 'Guardar Cambios' : 'Registrar Cliente' }}</span>
                </button>
            </div>
        </form>

    </div>
</div>
@endsection
