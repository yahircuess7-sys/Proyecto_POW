@extends('layouts.app')

@section('title', 'Super Admin - Negocios y Suscripciones - PuntoÁgil')

@section('content')
<div x-data="{
    subModalOpen: false,
    selectedBusinessId: null,
    selectedBusinessName: '',
    selectedPlanId: '',
    selectedStatus: 'active',
    endsAt: '',
    notes: '',
    openSubscriptionModal(id, name, planId, status, ends, notes) {
        this.selectedBusinessId = id;
        this.selectedBusinessName = name;
        this.selectedPlanId = planId || '';
        this.selectedStatus = status || 'active';
        this.endsAt = ends || '';
        this.notes = notes || '';
        this.subModalOpen = true;
    }
}" class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <span class="text-amber-500 font-semibold font-mono flex items-center gap-1">
                    <i class="bi bi-shield-shaded"></i> Super Admin
                </span>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-slate-800 font-semibold">Directorio de Negocios</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Gestión Global de Empresas (Tenants)</h1>
            <p class="text-xs text-slate-500 mt-0.5">Control de cuentas cliente, asignación manual de licencias, soporte técnico e intervención.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.businesses.create') }}" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-sm transition-all">
                <i class="bi bi-plus-lg mr-1"></i> Nueva Empresa
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

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="bi bi-exclamation-octagon-fill text-rose-600 text-base"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Filters & Search Bar -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-sm">
        <form action="{{ route('admin.superadmin.businesses') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center text-xs">
            <div class="sm:col-span-5">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Buscar por razón social, slug, RUC o email..."
                       class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
            </div>

            <div class="sm:col-span-3">
                <select name="status" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
                    <option value="">Todos los Estados</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Solo Activas</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Solo Suspendidas</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <select name="plan_id" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
                    <option value="">Cualquier Plan</option>
                    @foreach($plans as $p)
                        <option value="{{ $p->id }}" {{ request('plan_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2 flex justify-end gap-2">
                <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl font-bold text-white bg-slate-900 hover:bg-slate-800 transition-all">
                    <i class="bi bi-funnel"></i>
                    <span>Filtrar</span>
                </button>
                @if(request()->anyFilled(['search', 'status', 'plan_id']))
                    <a href="{{ route('admin.superadmin.businesses') }}" class="px-3 py-2 rounded-xl font-semibold text-slate-500 hover:bg-slate-100 flex items-center">
                        <i class="bi bi-x-circle"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Businesses Table -->
    <div class="bg-white border border-slate-200/80 rounded-3xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto text-xs">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-6">Negocio / Identificación</th>
                        <th class="py-3.5 px-4">Suscripción y Plan</th>
                        <th class="py-3.5 px-4">Métricas de Consumo</th>
                        <th class="py-3.5 px-4">Estado</th>
                        <th class="py-3.5 px-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($businesses as $business)
                        @php
                            $sub = $business->currentSubscription();
                            $plan = $sub?->plan;
                        @endphp
                        <tr class="hover:bg-slate-50/60">
                            <!-- Business Info -->
                            <td class="py-4 px-6">
                                <div class="font-bold text-slate-900 text-sm">{{ $business->name }}</div>
                                <div class="flex items-center gap-2 mt-0.5 text-slate-400 font-mono text-[11px]">
                                    <span>slug: <strong class="text-slate-600">{{ $business->slug }}</strong></span>
                                    @if($business->tax_id)
                                        <span>•</span>
                                        <span>RUC: {{ $business->tax_id }}</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-400">{{ $business->email ?? 'Sin correo' }}</div>
                            </td>

                            <!-- Subscription Info -->
                            <td class="py-4 px-4">
                                @if($sub && $plan)
                                    <div class="font-bold text-slate-800">{{ $plan->name }}</div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $sub->statusBadgeClasses() }}">
                                            {{ $sub->statusLabel() }}
                                        </span>
                                        <span class="font-mono text-[10px] text-slate-400">
                                            ${{ number_format($sub->price, 2) }}/{{ $sub->billing_cycle === 'yearly' ? 'año' : 'mes' }}
                                        </span>
                                    </div>
                                    @if($sub->ends_at)
                                        <div class="text-[10px] text-slate-400 mt-1">Vence: {{ $sub->ends_at->format('d/m/Y') }}</div>
                                    @endif
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600">
                                        Sin suscripción activa
                                    </span>
                                @endif
                            </td>

                            <!-- Usage stats -->
                            <td class="py-4 px-4 font-mono text-[11px]">
                                <div class="text-slate-700">
                                    <i class="bi bi-people text-slate-400 mr-1"></i> {{ $business->users_count }} usuarios
                                </div>
                                <div class="text-slate-700 mt-0.5">
                                    <i class="bi bi-box-seam text-slate-400 mr-1"></i> {{ $business->products_count }} productos
                                </div>
                                <div class="text-slate-500 mt-0.5">
                                    <i class="bi bi-receipt text-slate-400 mr-1"></i> {{ $business->sales_count }} ventas
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $business->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $business->status === 'active' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    {{ $business->status === 'active' ? 'Activo' : 'Suspendido' }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Subscription Override Button -->
                                    <button type="button"
                                            @click="openSubscriptionModal(
                                                {{ $business->id }},
                                                '{{ addslashes($business->name) }}',
                                                '{{ $sub?->plan_id }}',
                                                '{{ $sub?->status ?? 'active' }}',
                                                '{{ $sub?->ends_at ? $sub->ends_at->format('Y-m-d') : '' }}',
                                                '{{ addslashes($sub?->notes ?? '') }}'
                                            )"
                                            title="Modificar Plan o Estado de Suscripción"
                                            class="p-2 rounded-xl text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 border border-slate-200 transition-colors">
                                        <i class="bi bi-credit-card"></i>
                                    </button>

                                    <!-- Technical Support Impersonate Button -->
                                    <form action="{{ route('admin.superadmin.businesses.impersonate', $business) }}" method="POST"
                                          onsubmit="return confirm('¿Ingresar en contexto técnico a {{ addslashes($business->name) }}?')">
                                        @csrf
                                        <button type="submit"
                                                title="Soporte Técnico (Conmutar Contexto)"
                                                class="p-2 rounded-xl text-slate-600 hover:text-blue-700 hover:bg-blue-50 border border-slate-200 transition-colors">
                                            <i class="bi bi-box-arrow-in-right"></i>
                                        </button>
                                    </form>

                                    <!-- Suspend / Activate Tenant -->
                                    <form action="{{ route('admin.businesses.toggle-status', $business) }}" method="POST"
                                          onsubmit="return confirm('¿Confirma cambiar el estado de acceso de la empresa {{ addslashes($business->name) }}?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                title="{{ $business->status === 'active' ? 'Suspender Empresa' : 'Activar Empresa' }}"
                                                class="p-2 rounded-xl {{ $business->status === 'active' ? 'text-amber-600 hover:bg-amber-50 border border-amber-200' : 'text-emerald-600 hover:bg-emerald-50 border border-emerald-200' }} transition-colors">
                                            <i class="bi {{ $business->status === 'active' ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400">
                                No se encontraron empresas con los filtros especificados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($businesses->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $businesses->links() }}
            </div>
        @endif
    </div>

    <!-- Edit Subscription Modal (Super Admin Override) -->
    <div x-show="subModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="subModalOpen = false"
             class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-floating border border-slate-100 space-y-5">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-amber-500/10 text-amber-600 text-sm">
                        <i class="bi bi-shield-lock"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Suscripción Administrativa</h3>
                        <p class="text-[11px] text-slate-400 font-mono" x-text="selectedBusinessName"></p>
                    </div>
                </div>
                <button @click="subModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form :action="'{{ url('superadmin/businesses') }}/' + selectedBusinessId + '/subscription'" method="POST" class="space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Plan a Asignar <span class="text-rose-500">*</span></label>
                    <select name="plan_id" x-model="selectedPlanId" required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
                        <option value="">Selecciona un plan...</option>
                        @foreach($plans as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} (${{ number_format($p->price, 2) }}/{{ $p->billing_cycle }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Estado de la Suscripción <span class="text-rose-500">*</span></label>
                    <select name="status" x-model="selectedStatus" required
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
                        <option value="active">Activa (Vigente)</option>
                        <option value="trialing">En Período de Prueba</option>
                        <option value="pending">Pendiente de Pago</option>
                        <option value="past_due">Vencida / En Mora</option>
                        <option value="suspended">Suspendida</option>
                        <option value="cancelled">Cancelada</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Fecha de Vencimiento / Fin de Ciclo</label>
                    <input type="date" name="ends_at" x-model="endsAt"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Notas Administrativas / Justificación</label>
                    <textarea name="notes" x-model="notes" rows="2" placeholder="Motivo de la asignación manual o prórroga..."
                              class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="subModalOpen = false"
                            class="px-4 py-2 rounded-xl font-semibold text-slate-600 hover:bg-slate-100">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl font-bold text-white bg-slate-900 hover:bg-emerald-600 transition-colors shadow-sm">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
