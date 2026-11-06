@extends('layouts.app')

@section('title', 'Super Admin - Panel Central SaaS - PuntoÁgil')

@section('content')
<div class="space-y-8">

    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <span class="text-amber-500 font-semibold font-mono flex items-center gap-1">
                    <i class="bi bi-shield-shaded"></i> Super Admin Global
                </span>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-slate-800 font-semibold">Panel de Control SaaS</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Métricas y Administración de Plataforma</h1>
            <p class="text-xs text-slate-500 mt-0.5">Visión global de empresas (tenants), suscripciones, facturación recurrente (MRR) y seguridad.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                PostgreSQL Multi-tenant Activo
            </span>
            <a href="{{ route('admin.superadmin.businesses') }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 transition-all shadow-sm">
                <i class="bi bi-buildings mr-1"></i> Ver Empresas
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
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

    <!-- Primary SaaS Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- MRR (Monthly Recurring Revenue) -->
        <div class="bg-linear-to-br from-slate-900 to-slate-950 text-white rounded-3xl p-5 shadow-card border border-slate-800 space-y-2 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold font-mono tracking-wider uppercase text-emerald-400">MRR Mensual</span>
                <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 text-sm">
                    <i class="bi bi-currency-dollar"></i>
                </span>
            </div>
            <p class="text-3xl font-black font-mono tracking-tight text-white">
                ${{ number_format($mrr, 2) }}
            </p>
            <p class="text-[11px] text-slate-400">Ingreso recurrente proyectado por suscripciones activas.</p>
        </div>

        <!-- Total Businesses / Tenants -->
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200/80 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold font-mono tracking-wider uppercase text-slate-400">Empresas (Tenants)</span>
                <span class="p-2 rounded-xl bg-slate-100 text-slate-600 text-sm">
                    <i class="bi bi-building"></i>
                </span>
            </div>
            <p class="text-3xl font-black font-mono tracking-tight text-slate-900">{{ $totalBusinesses }}</p>
            <div class="flex items-center gap-3 text-[11px] text-slate-500 font-mono">
                <span class="text-emerald-600 font-semibold">{{ $activeBusinesses }} activas</span>
                <span>•</span>
                <span class="text-rose-600 font-semibold">{{ $suspendedBusinesses }} susp.</span>
            </div>
        </div>

        <!-- Subscriptions -->
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200/80 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold font-mono tracking-wider uppercase text-slate-400">Suscripciones</span>
                <span class="p-2 rounded-xl bg-slate-100 text-slate-600 text-sm">
                    <i class="bi bi-credit-card-2-front"></i>
                </span>
            </div>
            <p class="text-3xl font-black font-mono tracking-tight text-slate-900">{{ $totalSubscriptions }}</p>
            <div class="flex items-center gap-3 text-[11px] text-slate-500 font-mono">
                <span class="text-emerald-600 font-semibold">{{ $activeSubscriptions }} activas</span>
                <span>•</span>
                <span class="text-amber-600 font-semibold">{{ $pastDueSubscriptions }} por renovar</span>
            </div>
        </div>

        <!-- Global Users -->
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200/80 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold font-mono tracking-wider uppercase text-slate-400">Usuarios en Plataforma</span>
                <span class="p-2 rounded-xl bg-slate-100 text-slate-600 text-sm">
                    <i class="bi bi-people"></i>
                </span>
            </div>
            <p class="text-3xl font-black font-mono tracking-tight text-slate-900">{{ $totalUsers }}</p>
            <div class="flex items-center gap-3 text-[11px] text-slate-500 font-mono">
                <span class="text-emerald-600 font-semibold">{{ $activeUsers }} activos</span>
                <span>•</span>
                <span class="text-slate-400">{{ $totalProductsInPlatform }} productos</span>
            </div>
        </div>
    </div>

    <!-- Secondary SaaS Metrics: Sales Volume & Plans Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Plans Distribution -->
        <div class="lg:col-span-7 bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Distribución de Planes Activos</h2>
                    <p class="text-xs text-slate-500">Cantidad de clientes suscritos por cada tier de precios</p>
                </div>
                <a href="{{ route('admin.superadmin.plans') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">
                    Administrar Planes <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="space-y-3 pt-2">
                @foreach($plansDistribution as $plan)
                    @php
                        $share = $activeSubscriptions > 0 ? round(($plan->subscriptions_count / $activeSubscriptions) * 100) : 0;
                    @endphp
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <strong class="text-slate-800">{{ $plan->name }}</strong>
                                <span class="font-mono text-slate-400">(${{ number_format($plan->price, 2) }}/{{ $plan->billing_cycle === 'yearly' ? 'año' : 'mes' }})</span>
                            </div>
                            <span class="font-mono font-bold text-slate-900">
                                {{ $plan->subscriptions_count }} suscriptores ({{ $share }}%)
                            </span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-200 overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $share }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Platform Diagnostics & Volume -->
        <div class="lg:col-span-5 bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 space-y-4">
            <h2 class="text-sm font-bold text-slate-900">Salud del Sistema y Actividad</h2>
            
            <div class="p-4 rounded-2xl bg-slate-900 text-white space-y-2">
                <span class="text-[10px] font-mono uppercase tracking-wider text-slate-400">Volumen Global de Ventas POS</span>
                <p class="text-2xl font-black font-mono text-emerald-400">C$ {{ number_format($totalSalesVolume, 2) }}</p>
                <p class="text-[11px] text-slate-400">Total transaccionado por los negocios en Córdobas (NIO).</p>
            </div>

            <div class="space-y-2 text-xs">
                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                    <span class="text-slate-500">Motor de Base de Datos</span>
                    <span class="font-mono font-semibold text-slate-800">PostgreSQL (Supabase)</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                    <span class="text-slate-500">Versión de Framework</span>
                    <span class="font-mono font-semibold text-slate-800">Laravel {{ app()->version() }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                    <span class="text-slate-500">Versión de PHP</span>
                    <span class="font-mono font-semibold text-slate-800">{{ phpversion() }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="text-slate-500">Aislamiento de Clientes</span>
                    <span class="inline-flex items-center gap-1 text-emerald-600 font-bold font-mono">
                        <i class="bi bi-lock-fill"></i> Multi-tenant estricto
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Registered Businesses & Global Audit Stream -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Recent Businesses Table -->
        <div class="lg:col-span-7 bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Negocios Registrados Recientemente</h2>
                    <p class="text-xs text-slate-500">Últimas empresas añadidas a la plataforma SaaS</p>
                </div>
                <a href="{{ route('admin.superadmin.businesses') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">
                    Ver todos ({{ $totalBusinesses }})
                </a>
            </div>

            <div class="overflow-x-auto text-xs">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="py-3 px-5">Empresa</th>
                            <th class="py-3 px-4">Plan Actual</th>
                            <th class="py-3 px-4">Recursos</th>
                            <th class="py-3 px-4">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentBusinesses as $biz)
                            @php
                                $sub = $biz->currentSubscription();
                            @endphp
                            <tr class="hover:bg-slate-50/60">
                                <td class="py-3 px-5">
                                    <div class="font-bold text-slate-900">{{ $biz->name }}</div>
                                    <div class="font-mono text-[10px] text-slate-400">slug: {{ $biz->slug }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-semibold text-slate-800">{{ $sub?->plan?->name ?? 'Sin plan' }}</span>
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-500">
                                    {{ $biz->users_count }} users / {{ $biz->products_count }} prod
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $biz->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                        {{ $biz->status === 'active' ? 'Activo' : 'Suspendido' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-400">No hay negocios registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Global Audit Stream -->
        <div class="lg:col-span-5 bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Actividad Administrativa Global</h2>
                        <p class="text-xs text-slate-500">Eventos de seguridad y cambios de plataforma</p>
                    </div>
                    <a href="{{ route('admin.audit.index', ['global' => 1]) }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">
                        Bitácora <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="divide-y divide-slate-100 text-xs">
                    @forelse($recentAuditLogs as $log)
                        <div class="p-4 space-y-1 hover:bg-slate-50/60">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-mono font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                    {{ $log->action }}
                                </span>
                                <span class="text-slate-400 font-mono">{{ $log->created_at?->diffForHumans() }}</span>
                            </div>
                            <p class="text-slate-700 text-xs line-clamp-2">{{ $log->description }}</p>
                            <div class="text-[10px] text-slate-400 flex items-center gap-2">
                                <span>Por: <strong class="text-slate-600">{{ $log->user->name ?? 'Sistema' }}</strong></span>
                                @if($log->business)
                                    <span>•</span>
                                    <span>Empresa: <strong class="text-slate-600">{{ $log->business->name }}</strong></span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-400">No hay actividad reciente registrada.</div>
                    @endforelse
                </div>
            </div>

            <div class="p-4 border-t border-slate-100 bg-slate-50/50 text-center">
                <span class="text-[11px] text-slate-500">Bitácora inmutable protegida por Gate 'manage-platform'.</span>
            </div>
        </div>
    </div>

</div>
@endsection
