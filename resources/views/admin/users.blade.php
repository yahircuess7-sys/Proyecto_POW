@extends('layouts.app')

@section('title', 'Gestión de Usuarios - PuntoÁgil')

@section('content')
<div class="space-y-6" x-data="{
    deleteModalOpen: false,
    deleteActionUrl: '',
    deleteUserName: '',
    confirmDelete(url, name) {
        this.deleteActionUrl = url;
        this.deleteUserName = name;
        this.deleteModalOpen = true;
    }
}">
    <!-- Top Header -->
    <div class="animate-fade-up flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <span>Administración</span>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-emerald-600 font-semibold">Usuarios del Sistema</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Gestión de Usuarios</h1>
            <p class="text-xs text-slate-500 mt-0.5">Control de cuentas, perfiles de cajero y accesos administrativos.</p>
        </div>
        <div>
            <a href="{{ route('admin.users.create') }}" class="btn-shine inline-flex items-center gap-2 px-4 py-2.5 text-xs font-semibold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-sm hover:shadow transition-all active:scale-95 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                <i class="bi bi-person-plus text-sm"></i>
                <span>Nuevo Usuario</span>
            </a>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="animate-fade-up stagger-1 bg-white border border-slate-200/80 rounded-2xl p-4 shadow-sm">
        <form action="{{ route('admin.users.index') }}" method="GET" class="flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="relative w-full sm:max-w-md">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Buscar por nombre o correo electrónico..."
                       class="w-full pl-9 pr-8 py-2 text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                @if(request('search'))
                    <a href="{{ route('admin.users.index') }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                        <i class="bi bi-x-circle-fill"></i>
                    </a>
                @endif
            </div>

            <div class="flex items-center gap-2 text-xs text-slate-500 w-full sm:w-auto justify-end">
                <span>Total registrados: <strong class="text-slate-800 font-mono">{{ method_exists($users, 'total') ? $users->total() : $users->count() }}</strong></span>
            </div>
        </form>
    </div>

    <!-- Users Table Card -->
    <div class="animate-fade-up stagger-2 bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Usuario</th>
                        <th class="py-3.5 px-6">Rol de Acceso</th>
                        <th class="py-3.5 px-6">Estado</th>
                        <th class="py-3.5 px-6">Fecha Registro</th>
                        <th class="py-3.5 px-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs uppercase shrink-0 border border-slate-200/60">
                                        {{ substr($user->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900">{{ $user->name }}</div>
                                        <div class="text-[11px] text-slate-400 font-mono">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-6">
                                @if($user->role === 'admin')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                        <i class="bi bi-shield-lock-fill text-[10px]"></i> Administrador
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        <i class="bi bi-cash-coin text-[10px]"></i> Cajero POS
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-6">
                                @if($user->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-6 font-mono text-slate-500 text-[11px]">
                                {{ $user->created_at->format('d/m/Y') }}
                            </td>
                            <td class="py-3.5 px-6 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                       title="Editar Usuario"
                                       class="p-2 text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                        <i class="bi bi-pencil-square text-sm"></i>
                                    </a>

                                    @if(auth()->id() !== $user->id)
                                        <button type="button"
                                                @click="confirmDelete('{{ route('admin.users.destroy', $user) }}', '{{ addslashes($user->name) }}')"
                                                title="Eliminar Usuario"
                                                class="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                            <i class="bi bi-trash3 text-sm"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center">
                                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <i class="bi bi-people text-xl"></i>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-700">No se encontraron usuarios</h3>
                                <p class="text-xs text-slate-400 mt-1">Prueba con otro término de búsqueda o crea un nuevo usuario.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($users, 'links') && $users->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Alpine.js Confirmation Modal for Delete -->
    <div x-show="deleteModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="deleteModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                 @click="deleteModalOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="deleteModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <div class="p-6">
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4">
                        <i class="bi bi-exclamation-triangle text-xl"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 text-center" id="modal-title">¿Eliminar este usuario?</h3>
                    <p class="text-xs text-slate-500 text-center mt-2">
                        Estás por revocar y eliminar permanentemente el acceso de <strong class="text-slate-800" x-text="deleteUserName"></strong>. Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-2 border-t border-slate-100">
                    <form :action="deleteActionUrl" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex justify-center px-4 py-2 text-xs font-semibold text-white bg-rose-600 rounded-xl hover:bg-rose-700 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-rose-500/30">
                            Sí, Eliminar Usuario
                        </button>
                    </form>
                    <button type="button"
                            @click="deleteModalOpen = false"
                            class="inline-flex justify-center px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
