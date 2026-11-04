@extends('layouts.app')

@section('title', 'Directorio de Clientes - PuntoÁgil')

@section('content')
<div class="space-y-6" x-data="{ 
    deleteModalOpen: false, 
    deleteUrl: '', 
    customerName: '',
    confirmDelete(url, name) {
        this.deleteUrl = url;
        this.customerName = name;
        this.deleteModalOpen = true;
    }
}">

    <!-- Top Header & Actions -->
    <div class="animate-fade-up flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <span>Operaciones</span>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-emerald-600 font-semibold">Directorio de Clientes</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Gestión y Cartera de Clientes</h1>
            <p class="text-xs text-slate-500 mt-0.5">Control de compradores, cuentas corporativas, historial de consumo y facturación fiscal.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('customers.create') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-500/20 transition-all active:scale-95">
                <i class="bi bi-person-plus-fill text-sm"></i>
                <span>Nuevo Cliente</span>
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total en Cartera</span>
            <div class="flex items-baseline justify-between mt-1">
                <p class="text-2xl font-bold font-mono text-slate-900">{{ $kpis['total'] }}</p>
                <span class="text-xs text-slate-400 font-mono">clientes</span>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-600 flex items-center gap-1">
                <i class="bi bi-check-circle"></i> Clientes Activos
            </span>
            <p class="text-2xl font-bold font-mono text-emerald-600 mt-1">{{ $kpis['active'] }}</p>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Desactivados</span>
            <p class="text-2xl font-bold font-mono text-slate-600 mt-1">{{ $kpis['inactive'] }}</p>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-indigo-600 flex items-center gap-1">
                <i class="bi bi-bag-check"></i> Con Compras Registradas
            </span>
            <p class="text-2xl font-bold font-mono text-indigo-600 mt-1">{{ $kpis['with_sales'] }}</p>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
        <form action="{{ route('customers.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5">
            <!-- Search Input -->
            <div class="lg:col-span-5">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Buscar Cliente</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" 
                           class="w-full pl-10 pr-4 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                           placeholder="Nombre, cédula, RUC, teléfono o correo..." 
                           value="{{ request('search') }}">
                </div>
            </div>

            <!-- Document Type Filter -->
            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Tipo de Documento</label>
                <select name="document_type" 
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700">
                    <option value="">Todos los Documentos</option>
                    <option value="DNI" {{ request('document_type') === 'DNI' ? 'selected' : '' }}>DNI / Cédula</option>
                    <option value="RUC" {{ request('document_type') === 'RUC' ? 'selected' : '' }}>RUC</option>
                    <option value="RFC" {{ request('document_type') === 'RFC' ? 'selected' : '' }}>RFC</option>
                    <option value="Pasaporte" {{ request('document_type') === 'Pasaporte' ? 'selected' : '' }}>Pasaporte</option>
                    <option value="Otro" {{ request('document_type') === 'Otro' ? 'selected' : '' }}>Otro</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Estado</label>
                <select name="status" 
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700">
                    <option value="">Todos</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activos</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Desactivados</option>
                </select>
            </div>

            <!-- Submit Button -->
            <div class="lg:col-span-2 flex items-end">
                <button type="submit" 
                        class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 transition-all shadow-sm">
                    <i class="bi bi-funnel-fill text-emerald-400"></i>
                    <span>Filtrar</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Customers Table Card -->
    <div class="rounded-2xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                        <th class="py-3.5 px-5">Identificación / Doc</th>
                        <th class="py-3.5 px-4">Cliente / Razón Social</th>
                        <th class="py-3.5 px-4">Contacto Directo</th>
                        <th class="py-3.5 px-4">Ubicación / Dirección</th>
                        <th class="py-3.5 px-3 text-center">Ventas</th>
                        <th class="py-3.5 px-3 text-center">Estado</th>
                        <th class="py-3.5 px-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $customer)
                    <tr class="hover:bg-slate-50/80 transition-colors {{ ! $customer->isActive() ? 'bg-slate-50/50 opacity-75' : '' }}">
                        
                        <!-- Document -->
                        <td class="py-3.5 px-5 font-mono">
                            <span class="inline-block px-2 py-0.5 rounded-lg bg-slate-100 border border-slate-200 font-bold text-slate-800">
                                {{ $customer->document_number }}
                            </span>
                            <span class="block text-[10px] text-slate-400 font-sans mt-0.5">{{ $customer->document_type }}</span>
                        </td>

                        <!-- Name & Email -->
                        <td class="py-3.5 px-4 max-w-xs">
                            <a href="{{ route('customers.show', $customer->id) }}" class="font-bold text-slate-900 hover:text-emerald-600 transition-colors block text-xs">
                                {{ $customer->name }}
                            </a>
                            @if($customer->email)
                                <p class="text-[11px] text-slate-500 font-mono mt-0.5 flex items-center gap-1">
                                    <i class="bi bi-envelope text-slate-400"></i> {{ $customer->email }}
                                </p>
                            @else
                                <span class="text-[11px] text-slate-400">Sin correo registrado</span>
                            @endif
                        </td>

                        <!-- Phone -->
                        <td class="py-3.5 px-4 font-mono">
                            @if($customer->phone)
                                <span class="inline-flex items-center gap-1 text-slate-700">
                                    <i class="bi bi-telephone text-emerald-600 text-[11px]"></i> {{ $customer->phone }}
                                </span>
                            @else
                                <span class="text-slate-400">---</span>
                            @endif
                        </td>

                        <!-- Address -->
                        <td class="py-3.5 px-4 max-w-xs">
                            <p class="text-slate-600 text-[11px] truncate">
                                {{ $customer->address ?: 'Sin dirección física especificada' }}
                            </p>
                        </td>

                        <!-- Sales Count -->
                        <td class="py-3.5 px-3 text-center">
                            <a href="{{ route('customers.show', $customer->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 font-mono font-bold text-slate-700 transition-colors" title="Ver compras del cliente">
                                <i class="bi bi-receipt text-[11px] text-slate-400"></i>
                                <span>{{ $customer->sales_count }}</span>
                            </a>
                        </td>

                        <!-- Status Badge -->
                        <td class="py-3.5 px-3 text-center">
                            @if($customer->isActive())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span> Activo
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1"></span> Desactivado
                                </span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="py-3.5 px-5 text-right">
                            <div class="inline-flex items-center justify-end gap-1">
                                <a href="{{ route('customers.show', $customer->id) }}" 
                                   class="p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors" 
                                   title="Ver Ficha y Transacciones">
                                    <i class="bi bi-eye text-sm"></i>
                                </a>

                                <a href="{{ route('customers.edit', $customer->id) }}" 
                                   class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors" 
                                   title="Editar Información">
                                    <i class="bi bi-pencil text-sm"></i>
                                </a>

                                @can('delete', $customer)
                                <form action="{{ route('customers.toggle-status', $customer->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="p-1.5 rounded-lg {{ $customer->isActive() ? 'text-slate-400 hover:text-amber-600 hover:bg-amber-50' : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50' }} transition-colors"
                                            title="{{ $customer->isActive() ? 'Desactivar cliente' : 'Reactivar cliente' }}">
                                        <i class="bi {{ $customer->isActive() ? 'bi-slash-circle' : 'bi-check-circle' }} text-sm"></i>
                                    </button>
                                </form>

                                <button type="button" 
                                        @click="confirmDelete('{{ route('customers.destroy', $customer->id) }}', '{{ addslashes($customer->name) }}')"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" 
                                        title="Eliminar Cliente">
                                    <i class="bi bi-trash text-sm"></i>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            <i class="bi bi-people text-3xl mb-2 opacity-40 block"></i>
                            <p class="font-semibold text-slate-600">No se encontraron clientes registrados con los filtros actuales.</p>
                            <p class="text-[11px] text-slate-400 mt-1">Registra nuevos compradores para habilitar facturación y seguimiento comercial.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $customers->links() }}
        </div>
        @endif
    </div>

    <!-- Modal: Confirmar Eliminación -->
    <div x-show="deleteModalOpen" x-cloak class="pa-modal-backdrop" @keydown.escape.window="deleteModalOpen = false">
        <div class="pa-modal max-w-sm" @click.away="deleteModalOpen = false">
            <div class="p-6 text-center">
                <div class="w-12 h-12 rounded-2xl mx-auto flex items-center justify-center mb-4 bg-rose-50 text-rose-600 border border-rose-200">
                    <i class="bi bi-trash text-xl"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900">¿Eliminar cliente?</h3>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Estás a punto de eliminar a <strong class="text-slate-800" x-text="customerName"></strong>.
                    Si el cliente posee boletas o ventas asociadas, el sistema protegerá la integridad contable y no permitirá borrarlo (en su lugar podrás desactivarlo).
                </p>

                <form :action="deleteUrl" method="POST" class="mt-6 flex items-center justify-center gap-3">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="deleteModalOpen = false" class="pa-btn-secondary text-xs flex-1">
                        Cancelar
                    </button>
                    <button type="submit" class="pa-btn-danger text-xs flex-1">
                        Sí, Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
