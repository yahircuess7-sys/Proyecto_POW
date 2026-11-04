@extends('layouts.app')

@section('title', 'Super Admin - Usuarios Globales - PuntoÁgil')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <span class="text-amber-500 font-semibold font-mono flex items-center gap-1">
                    <i class="bi bi-shield-shaded"></i> Super Admin
                </span>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-slate-800 font-semibold">Usuarios Globales</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Directorio Global de Usuarios</h1>
            <p class="text-xs text-slate-500 mt-0.5">Auditoría y control de cuentas en toda la plataforma SaaS y asignación de privilegios de Super Admin.</p>
        </div>

        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-900 text-amber-400">
            <i class="bi bi-shield-lock-fill"></i> Privilegio Restringido
        </span>
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

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="bi bi-exclamation-octagon-fill text-rose-600 text-base"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Search & Filters -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-sm">
        <form action="{{ route('admin.superadmin.users') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center text-xs">
            <div class="sm:col-span-6">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Buscar por nombre o correo electrónico..."
                       class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
            </div>

            <div class="sm:col-span-2">
                <select name="role" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
                    <option value="">Cualquier Rol</option>
                    <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin Global</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrador</option>
                    <option value="cashier" {{ request('role') === 'cashier' ? 'selected' : '' }}>Cajero</option>
                    <option value="inventory" {{ request('role') === 'inventory' ? 'selected' : '' }}>Inventario</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <select name="status" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
                    <option value="">Cualquier Estado</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activo</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>

            <div class="sm:col-span-2 flex justify-end gap-2">
                <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl font-bold text-white bg-slate-900 hover:bg-slate-800 transition-all">
                    <i class="bi bi-funnel"></i>
                    <span>Filtrar</span>
                </button>
                @if(request()->anyFilled(['search', 'role', 'status']))
                    <a href="{{ route('admin.superadmin.users') }}" class="px-3 py-2 rounded-xl font-semibold text-slate-500 hover:bg-slate-100 flex items-center">
                        <i class="bi bi-x-circle"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white border border-slate-200/80 rounded-3xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto text-xs">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-6">Usuario</th>
                        <th class="py-3.5 px-4">Privilegios SaaS</th>
                        <th class="py-3.5 px-4">Empresas Asignadas</th>
                        <th class="py-3.5 px-4">Estado</th>
                        <th class="py-3.5 px-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50/60">
                            <!-- User info -->
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-xs">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $user->name }}</div>
                                        <div class="font-mono text-[11px] text-slate-400">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Platform privilege -->
                            <td class="py-4 px-4">
                                @if($user->is_super_admin)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="bi bi-shield-check"></i> Super Admin
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600 capitalize">
                                        {{ $user->role ?? 'Usuario' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Businesses -->
                            <td class="py-4 px-4">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @forelse($user->businesses as $b)
                                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-medium text-[10px]">
                                            {{ $b->name }}
                                        </span>
                                    @empty
                                        <span class="text-slate-400 text-[11px] italic">Sin empresas</span>
                                    @endforelse
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold {{ $user->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $user->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $user->status === 'active' ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>

                            <!-- Action -->
                            <td class="py-4 px-6 text-right">
                                <form action="{{ route('admin.superadmin.users.toggle-super', $user) }}" method="POST"
                                      onsubmit="return confirm('¿Confirma alternar el privilegio de Super Admin para {{ addslashes($user->name) }}?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold {{ $user->is_super_admin ? 'text-amber-700 hover:bg-amber-100/70 border border-amber-300 bg-amber-50' : 'text-slate-600 hover:bg-slate-100 border border-slate-200' }} transition-colors">
                                        <i class="bi bi-shield-shaded"></i>
                                        <span>{{ $user->is_super_admin ? 'Revocar Super Admin' : 'Hacer Super Admin' }}</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400">
                                No se encontraron usuarios con los filtros indicados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
