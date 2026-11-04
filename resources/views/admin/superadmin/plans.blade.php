@extends('layouts.app')

@section('title', 'Super Admin - Catálogo de Planes - PuntoÁgil')

@section('content')
<div x-data="{ createModalOpen: false }" class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <span class="text-amber-500 font-semibold font-mono flex items-center gap-1">
                    <i class="bi bi-shield-shaded"></i> Super Admin
                </span>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-slate-800 font-semibold">Planes de Suscripción</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Catálogo de Planes y Precios SaaS</h1>
            <p class="text-xs text-slate-500 mt-0.5">Definición de precios, cuotas de recursos (usuarios, inventario, cajas) y funcionalidades habilitadas.</p>
        </div>

        <button @click="createModalOpen = true"
                class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-sm transition-all flex items-center gap-1.5">
            <i class="bi bi-plus-lg"></i>
            <span>Nuevo Plan</span>
        </button>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="bi bi-check-circle-fill text-emerald-600 text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Plans Table & Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
        @forelse($plans as $plan)
            <div class="bg-white rounded-3xl p-6 border {{ $plan->is_active ? 'border-slate-200/80' : 'border-dashed border-slate-300 opacity-75' }} shadow-sm flex flex-col justify-between space-y-5">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider {{ $plan->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                            {{ $plan->is_active ? 'Disponible' : 'Desactivado' }}
                        </span>

                        <span class="text-xs font-mono font-semibold text-slate-500 flex items-center gap-1">
                            <i class="bi bi-people"></i>
                            {{ $plan->subscriptions_count }} activos
                        </span>
                    </div>

                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ $plan->name }}</h2>
                        <span class="text-[10px] font-mono text-slate-400">slug: {{ $plan->slug }}</span>
                        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">{{ $plan->description ?: 'Sin descripción.' }}</p>
                    </div>

                    <div class="py-3 border-y border-slate-100 flex items-baseline gap-2">
                        <span class="text-3xl font-black font-mono text-slate-900">${{ number_format($plan->price, 2) }}</span>
                        <span class="text-xs text-slate-400 font-medium">/ {{ $plan->billing_cycle === 'yearly' ? 'año' : 'mes' }}</span>
                        @if($plan->trial_days > 0)
                            <span class="ml-auto text-[10px] font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                {{ $plan->trial_days }}d prueba
                            </span>
                        @endif
                    </div>

                    <!-- Quota Limits -->
                    <div class="space-y-2 text-xs">
                        <div class="flex items-center justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Límite Usuarios:</span>
                            <span class="font-mono font-bold text-slate-800">{{ $plan->isUnlimitedUsers() ? 'Ilimitado' : $plan->max_users }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Límite Productos:</span>
                            <span class="font-mono font-bold text-slate-800">{{ $plan->isUnlimitedProducts() ? 'Ilimitado' : $plan->max_products }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1 border-b border-slate-50">
                            <span class="text-slate-500">Cajas Registradoras:</span>
                            <span class="font-mono font-bold text-slate-800">{{ $plan->isUnlimitedCashRegisters() ? 'Ilimitado' : $plan->max_cash_registers }}</span>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="pt-2 space-y-1.5 text-xs text-slate-600">
                        <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block mb-1">Módulos Habilitados:</span>
                        <div class="flex flex-wrap gap-1.5">
                            @if($plan->hasFeature('purchases'))
                                <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-[11px] text-slate-700 font-medium">Compras</span>
                            @endif
                            @if($plan->hasFeature('returns'))
                                <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-[11px] text-slate-700 font-medium">Devoluciones</span>
                            @endif
                            @if($plan->hasFeature('reports_export'))
                                <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-[11px] text-slate-700 font-medium">Reportes Excel/PDF</span>
                            @endif
                            @if($plan->hasFeature('advanced_analytics'))
                                <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-[11px] text-slate-700 font-medium">Analítica Avanzada</span>
                            @endif
                            @if($plan->hasFeature('priority_support'))
                                <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-[11px] text-slate-700 font-medium">Soporte 24/7</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Toggle Status Action -->
                <div class="pt-4 border-t border-slate-100">
                    <form action="{{ route('admin.superadmin.plans.toggle', $plan) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="w-full py-2 px-3 rounded-xl text-xs font-semibold {{ $plan->is_active ? 'text-rose-600 hover:bg-rose-50 border border-rose-200' : 'text-emerald-600 hover:bg-emerald-50 border border-emerald-200' }} transition-colors">
                            {{ $plan->is_active ? 'Desactivar para Nuevos Clientes' : 'Reactivar en Catálogo' }}
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-3 py-12 text-center text-slate-400 bg-white rounded-3xl border border-slate-200">
                No existen planes configurados en el catálogo.
            </div>
        @endforelse
    </div>

    <!-- Create Plan Modal -->
    <div x-show="createModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="createModalOpen = false"
             class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-floating border border-slate-100 space-y-5 animate-scaleIn">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900">Crear Nuevo Plan SaaS</h3>
                <button @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form action="{{ route('admin.superadmin.plans.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Nombre del Plan <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="ej. Corporativo Plus"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Descripción Comercial</label>
                        <input type="text" name="description" placeholder="Ideal para cadenas de retail con múltiples puntos de venta..."
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Precio ($) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="price" required placeholder="49.00"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono focus:bg-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ciclo de Facturación <span class="text-rose-500">*</span></label>
                        <select name="billing_cycle" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
                            <option value="monthly">Mensual</option>
                            <option value="yearly">Anual</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Días de Prueba Gratuita <span class="text-rose-500">*</span></label>
                        <input type="number" min="0" name="trial_days" value="0" required
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono focus:bg-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Máximo de Usuarios (Vacío = Ilimitado)</label>
                        <input type="number" min="1" name="max_users" placeholder="Ilimitado"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono focus:bg-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Máximo de Productos (Vacío = Ilimitado)</label>
                        <input type="number" min="1" name="max_products" placeholder="Ilimitado"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono focus:bg-white focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Máximo de Cajas (Vacío = Ilimitado)</label>
                        <input type="number" min="1" name="max_cash_registers" placeholder="Ilimitado"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 font-mono focus:bg-white focus:outline-none">
                    </div>
                </div>

                <!-- Features checklist -->
                <div class="pt-2 border-t border-slate-100">
                    <label class="block font-bold text-slate-700 mb-2">Módulos y Funcionalidades Incluidas</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer hover:bg-slate-100">
                            <input type="checkbox" name="features[purchases]" value="1" checked class="rounded text-emerald-600 focus:ring-0">
                            <span class="font-medium text-slate-700">Módulo de Compras</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer hover:bg-slate-100">
                            <input type="checkbox" name="features[returns]" value="1" checked class="rounded text-emerald-600 focus:ring-0">
                            <span class="font-medium text-slate-700">Devoluciones y Anulaciones</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer hover:bg-slate-100">
                            <input type="checkbox" name="features[reports_export]" value="1" checked class="rounded text-emerald-600 focus:ring-0">
                            <span class="font-medium text-slate-700">Exportación de Reportes</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer hover:bg-slate-100">
                            <input type="checkbox" name="features[advanced_analytics]" value="1" class="rounded text-emerald-600 focus:ring-0">
                            <span class="font-medium text-slate-700">Analítica Avanzada</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="createModalOpen = false"
                            class="px-4 py-2 rounded-xl font-semibold text-slate-600 hover:bg-slate-100">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-sm">
                        Crear Plan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
