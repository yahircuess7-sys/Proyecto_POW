@extends('layouts.app')

@section('title', 'Auditoría de Seguridad - PuntoÁgil')

@section('content')
<div x-data="{
    metaModalOpen: false,
    currentMeta: null,
    currentAction: '',
    openMetaModal(action, meta) {
        this.currentAction = action;
        this.currentMeta = JSON.stringify(meta, null, 2);
        this.metaModalOpen = true;
    }
}" class="space-y-6">

    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <span>Administración</span>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-emerald-600 font-semibold">Auditoría Protegida</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Bitácora de Auditoría y Seguridad</h1>
            <p class="text-xs text-slate-500 mt-0.5">Registro inmutable de trazabilidad sobre operaciones sensibles, cambios de permisos, ajustes y cierres.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-900 text-emerald-400">
                <i class="bi bi-shield-lock-fill"></i>
                Registro Inmutable
            </span>
            <a href="{{ route('admin.audit.export', request()->all()) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-sm transition-all">
                <i class="bi bi-file-earmark-spreadsheet text-emerald-600"></i>
                <span>Exportar CSV</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-sm">
        <form action="{{ route('admin.audit.index') }}" method="GET" class="space-y-3 text-xs">
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-4">
                    <label class="block font-semibold text-slate-600 mb-1">Búsqueda libre</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Buscar en descripción o IP..."
                           class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
                </div>

                <div class="sm:col-span-3">
                    <label class="block font-semibold text-slate-600 mb-1">Acción / Evento</label>
                    <input type="text" name="action" value="{{ request('action') }}"
                           placeholder="ej. user., business., stock."
                           class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 font-mono focus:bg-white focus:outline-none">
                </div>

                <div class="sm:col-span-3">
                    <label class="block font-semibold text-slate-600 mb-1">Usuario Responsable</label>
                    <select name="user_id" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
                        <option value="">Todos los usuarios</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block font-semibold text-slate-600 mb-1">Desde</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center pt-1">
                <div class="sm:col-span-3">
                    <label class="block font-semibold text-slate-600 mb-1">Hasta</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
                </div>

                @if(auth()->user()->isSuperAdmin())
                <div class="sm:col-span-5 flex items-center pt-5">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-700 font-medium">
                        <input type="checkbox" name="global" value="1" {{ request('global') ? 'checked' : '' }}
                               class="rounded text-emerald-600 focus:ring-0">
                        <span>Consultar toda la plataforma (Vista Global Super Admin)</span>
                    </label>
                </div>
                <div class="sm:col-span-4 flex justify-end gap-2 pt-5">
                @else
                <div class="sm:col-span-9 flex justify-end gap-2 pt-5">
                @endif
                    <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl font-bold text-white bg-slate-900 hover:bg-slate-800 transition-all">
                        <i class="bi bi-funnel text-emerald-400"></i>
                        <span>Aplicar Filtros</span>
                    </button>
                    @if(request()->anyFilled(['search', 'action', 'user_id', 'date_from', 'date_to', 'global']))
                        <a href="{{ route('admin.audit.index') }}" class="px-3.5 py-2 rounded-xl font-semibold text-slate-500 hover:bg-slate-100 flex items-center">
                            <i class="bi bi-x-circle mr-1"></i> Limpiar
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Audit Table -->
    <div class="bg-white border border-slate-200/80 rounded-3xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto text-xs">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">Fecha y Hora</th>
                        <th class="py-3.5 px-4">Acción / Módulo</th>
                        <th class="py-3.5 px-4">Ejecutado Por</th>
                        <th class="py-3.5 px-6">Descripción del Evento</th>
                        <th class="py-3.5 px-4">IP Origen</th>
                        <th class="py-3.5 px-4 text-right">Detalles</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/60">
                            <td class="py-3.5 px-6 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                {{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '-' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 font-mono text-[11px] font-semibold text-slate-700">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-800">{{ $log->user->name ?? 'Sistema' }}</div>
                                <div class="font-mono text-[10px] text-slate-400">{{ $log->user->email ?? '' }}</div>
                            </td>
                            <td class="py-3.5 px-6 text-slate-700 max-w-md">
                                <div class="leading-relaxed">{{ $log->description }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                @if(!empty($log->metadata))
                                    <button type="button"
                                            @click="openMetaModal('{{ $log->action }}', {{ json_encode($log->metadata) }})"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-colors">
                                        <i class="bi bi-braces"></i>
                                        <span>JSON</span>
                                    </button>
                                @else
                                    <span class="text-slate-300 font-mono text-[11px]">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                No existen registros de auditoría que coincidan con la consulta.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($logs, 'links') && $logs->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- JSON Metadata Inspector Modal (Alpine.js) -->
    <div x-show="metaModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="metaModalOpen = false"
             class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-floating border border-slate-100 space-y-4 animate-scaleIn">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-slate-900 text-emerald-400 font-mono text-xs">
                        <i class="bi bi-code-slash"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Metadatos de Auditoría</h3>
                        <p class="text-[11px] font-mono text-slate-400" x-text="currentAction"></p>
                    </div>
                </div>
                <button @click="metaModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="bg-slate-950 rounded-2xl p-4 overflow-x-auto text-emerald-400 font-mono text-xs max-h-96">
                <pre x-text="currentMeta"></pre>
            </div>

            <div class="flex justify-end pt-2">
                <button type="button" @click="metaModalOpen = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
