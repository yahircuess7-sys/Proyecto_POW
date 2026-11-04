@extends('layouts.app')

@section('title', 'Directorio de Proveedores - PuntoÁgil')

@section('content')
<div class="space-y-6" x-data="{ 
    deleteModalOpen: false, 
    deleteUrl: '', 
    supplierName: '',
    confirmDelete(url, name) {
        this.deleteUrl = url;
        this.supplierName = name;
        this.deleteModalOpen = true;
    }
}">

    <!-- Top Header & Actions -->
    <div class="animate-fade-up flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <span>Operaciones</span>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-emerald-600 font-semibold">Directorio de Proveedores</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Proveedores y Abastecimiento</h1>
            <p class="text-xs text-slate-500 mt-0.5">Gestión de distribuidores mayoristas, marcas, contactos comerciales e ingreso de mercadería.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('purchases.index') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 transition-all shadow-sm">
                <i class="bi bi-bag-check text-emerald-600"></i>
                <span>Ver Compras</span>
            </a>

            <a href="{{ route('purchases.create') }}" 
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-all shadow-sm">
                <i class="bi bi-cart-plus-fill text-emerald-600"></i>
                <span>Registrar Compra</span>
            </a>

            @can('create', \App\Models\Supplier::class)
            <a href="{{ route('suppliers.create') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm shadow-emerald-500/20 transition-all active:scale-95">
                <i class="bi bi-building-add text-sm"></i>
                <span>Nuevo Proveedor</span>
            </a>
            @endcan
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Proveedores</span>
            <div class="flex items-baseline justify-between mt-1">
                <p class="text-2xl font-bold font-mono text-slate-900">{{ $kpis['total'] }}</p>
                <span class="text-xs text-slate-400 font-mono">empresas</span>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-600 flex items-center gap-1">
                <i class="bi bi-check-circle"></i> Activos
            </span>
            <p class="text-2xl font-bold font-mono text-emerald-600 mt-1">{{ $kpis['active'] }}</p>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Desactivados</span>
            <p class="text-2xl font-bold font-mono text-slate-600 mt-1">{{ $kpis['inactive'] }}</p>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-indigo-600 flex items-center gap-1">
                <i class="bi bi-receipt"></i> Con Órdenes de Compra
            </span>
            <p class="text-2xl font-bold font-mono text-indigo-600 mt-1">{{ $kpis['with_purchases'] }}</p>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
        <form action="{{ route('suppliers.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5">
            <!-- Search Input -->
            <div class="lg:col-span-7">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Buscar Proveedor</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" 
                           class="w-full pl-10 pr-4 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-800"
                           placeholder="Nombre comercial, RUC, contacto, correo o teléfono..." 
                           value="{{ request('search') }}">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">Estado</label>
                <select name="status" 
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-slate-700">
                    <option value="">Todos los Estados</option>
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

    <!-- Suppliers Table Card -->
    <div class="rounded-2xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                        <th class="py-3.5 px-5">RUC / Identificación</th>
                        <th class="py-3.5 px-4">Razón Social / Proveedor</th>
                        <th class="py-3.5 px-4">Contacto / Asesor</th>
                        <th class="py-3.5 px-4">Canales de Comunicación</th>
                        <th class="py-3.5 px-3 text-center">Compras</th>
                        <th class="py-3.5 px-3 text-center">Estado</th>
                        <th class="py-3.5 px-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($suppliers as $supplier)
                    <tr class="hover:bg-slate-50/80 transition-colors {{ ! $supplier->isActive() ? 'bg-slate-50/50 opacity-75' : '' }}">
                        
                        <!-- Tax ID -->
                        <td class="py-3.5 px-5 font-mono">
                            <span class="inline-block px-2 py-0.5 rounded-lg bg-slate-100 border border-slate-200 font-bold text-slate-800">
                                {{ $supplier->tax_id }}
                            </span>
                        </td>

                        <!-- Name & Address -->
                        <td class="py-3.5 px-4 max-w-xs">
                            <a href="{{ route('suppliers.show', $supplier->id) }}" class="font-bold text-slate-900 hover:text-emerald-600 transition-colors block text-xs">
                                {{ $supplier->name }}
                            </a>
                            <p class="text-[11px] text-slate-500 truncate mt-0.5">
                                {{ $supplier->address ?: 'Sin dirección fiscal registrada' }}
                            </p>
                        </td>

                        <!-- Contact -->
                        <td class="py-3.5 px-4">
                            @if($supplier->contact_name)
                                <span class="font-medium text-slate-800 flex items-center gap-1">
                                    <i class="bi bi-person text-slate-400"></i> {{ $supplier->contact_name }}
                                </span>
                            @else
                                <span class="text-slate-400">No especificado</span>
                            @endif
                        </td>

                        <!-- Email & Phone -->
                        <td class="py-3.5 px-4 font-mono text-[11px]">
                            @if($supplier->email)
                                <p class="text-slate-600 truncate flex items-center gap-1">
                                    <i class="bi bi-envelope text-slate-400"></i> {{ $supplier->email }}
                                </p>
                            @endif
                            @if($supplier->phone)
                                <p class="text-slate-700 mt-0.5 flex items-center gap-1 font-semibold">
                                    <i class="bi bi-telephone text-emerald-600"></i> {{ $supplier->phone }}
                                </p>
                            @endif
                            @if(!$supplier->email && !$supplier->phone)
                                <span class="text-slate-400">---</span>
                            @endif
                        </td>

                        <!-- Purchases Count -->
                        <td class="py-3.5 px-3 text-center">
                            <a href="{{ route('suppliers.show', $supplier->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 font-mono font-bold text-slate-700 transition-colors" title="Ver compras a este proveedor">
                                <i class="bi bi-bag-check text-[11px] text-slate-400"></i>
                                <span>{{ $supplier->purchases_count }}</span>
                            </a>
                        </td>

                        <!-- Status Badge -->
                        <td class="py-3.5 px-3 text-center">
                            @if($supplier->isActive())
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
                                <a href="{{ route('suppliers.show', $supplier->id) }}" 
                                   class="p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors" 
                                   title="Ver Ficha y Compras">
                                    <i class="bi bi-eye text-sm"></i>
                                </a>

                                @can('update', $supplier)
                                <a href="{{ route('suppliers.edit', $supplier->id) }}" 
                                   class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors" 
                                   title="Editar Información">
                                    <i class="bi bi-pencil text-sm"></i>
                                </a>
                                @endcan

                                @can('delete', $supplier)
                                <form action="{{ route('suppliers.toggle-status', $supplier->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="p-1.5 rounded-lg {{ $supplier->isActive() ? 'text-slate-400 hover:text-amber-600 hover:bg-amber-50' : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50' }} transition-colors"
                                            title="{{ $supplier->isActive() ? 'Desactivar proveedor' : 'Reactivar proveedor' }}">
                                        <i class="bi {{ $supplier->isActive() ? 'bi-slash-circle' : 'bi-check-circle' }} text-sm"></i>
                                    </button>
                                </form>

                                <button type="button" 
                                        @click="confirmDelete('{{ route('suppliers.destroy', $supplier->id) }}', '{{ addslashes($supplier->name) }}')"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" 
                                        title="Eliminar Proveedor">
                                    <i class="bi bi-trash text-sm"></i>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            <i class="bi bi-building text-3xl mb-2 opacity-40 block"></i>
                            <p class="font-semibold text-slate-600">No se encontraron proveedores registrados con los filtros actuales.</p>
                            <p class="text-[11px] text-slate-400 mt-1">Registra distribuidores para controlar tus compras y costos de adquisición.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($suppliers->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $suppliers->links() }}
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
                <h3 class="text-base font-bold text-slate-900">¿Eliminar proveedor?</h3>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Estás a punto de eliminar a <strong class="text-slate-800" x-text="supplierName"></strong>.
                    Si el proveedor tiene compras registradas, el sistema bloqueará la eliminación para no alterar la trazabilidad contable (en su lugar podrás desactivarlo).
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
