@extends('layouts.app')

@section('title', 'Mi Suscripción y Licencia - PuntoÁgil')

@section('content')
<div x-data="{
    changeModalOpen: false,
    selectedPlanId: null,
    selectedPlanName: '',
    selectedPlanPrice: 0,
    billingCycle: 'monthly',
    openChangeModal(id, name, price) {
        this.selectedPlanId = id;
        this.selectedPlanName = name;
        this.selectedPlanPrice = price;
        this.changeModalOpen = true;
    }
}" class="max-w-6xl mx-auto space-y-8">

    <!-- Top Navigation & Title -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-1">
                <span>Administración</span>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-emerald-600 font-semibold">Planes y Licencias</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Suscripción de {{ $business->name }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">Gestión de plan activo, cuotas de consumo de recursos y actualización de membresía.</p>
        </div>

        <div class="flex items-center gap-2">
            @if($subscription)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold {{ $subscription->statusBadgeClasses() }}">
                    <span class="w-2 h-2 rounded-full {{ $subscription->isActive() ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                    {{ $subscription->statusLabel() }}
                </span>
            @endif
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

    <!-- Current Subscription Overview Card -->
    <div class="bg-gradient-to-br from-slate-900 via-slate-900 to-slate-950 text-white rounded-3xl p-6 sm:p-8 shadow-card border border-slate-800 relative overflow-hidden">
        <!-- Background subtle glow -->
        <div class="absolute -top-24 -right-24 w-72 h-72 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-7 space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-mono">
                    <i class="bi bi-patch-check-fill"></i>
                    <span>Plan Actual: {{ $subscription?->plan?->name ?? 'Sin Plan Asignado' }}</span>
                </div>

                <div class="flex items-baseline gap-3">
                    <span class="text-4xl sm:text-5xl font-black font-mono tracking-tight text-white">
                        ${{ number_format($subscription?->price ?? 0, 2) }}
                    </span>
                    <span class="text-sm font-semibold text-slate-400">
                        / {{ $subscription?->billing_cycle === 'yearly' ? 'año' : 'mes' }}
                    </span>
                </div>

                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed max-w-xl">
                    {{ $subscription?->plan?->description ?? 'Disfruta de las capacidades y módulos habilitados en la plataforma PuntoÁgil.' }}
                </p>

                <div class="pt-2 flex flex-wrap gap-4 text-xs font-mono text-slate-400">
                    <div>
                        <span class="text-slate-500 block text-[10px] uppercase font-sans font-bold">Fecha de Inicio</span>
                        <span class="text-slate-200">{{ $subscription?->starts_at ? $subscription->starts_at->format('d/m/Y') : 'Inmediato' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[10px] uppercase font-sans font-bold">Próxima Renovación</span>
                        <span class="text-slate-200">{{ $subscription?->ends_at ? $subscription->ends_at->format('d/m/Y') : 'Indefinida' }}</span>
                    </div>
                    @if($subscription?->isTrialing() && $subscription?->trial_ends_at)
                    <div>
                        <span class="text-amber-400 block text-[10px] uppercase font-sans font-bold">Prueba vence</span>
                        <span class="text-amber-300">{{ $subscription->trial_ends_at->format('d/m/Y') }}</span>
                    </div>
                    @endif
                    <div>
                        <span class="text-slate-500 block text-[10px] uppercase font-sans font-bold">Método / Pasarela</span>
                        <span class="text-slate-200 capitalize">{{ $subscription?->payment_provider ?? 'Manual' }}</span>
                    </div>
                </div>
            </div>

            <!-- Actions Side -->
            <div class="lg:col-span-5 flex flex-col justify-center items-start lg:items-end gap-3 border-t lg:border-t-0 lg:border-l border-slate-800 pt-6 lg:pt-0 lg:pl-8">
                @if($subscription && $subscription->status === \App\Models\Subscription::STATUS_CANCELLED)
                    <div class="w-full bg-amber-500/10 border border-amber-500/20 rounded-2xl p-4 text-left">
                        <p class="text-xs font-semibold text-amber-300">Suscripción en proceso de cancelación</p>
                        <p class="text-[11px] text-slate-400 mt-1">El acceso terminará al vencer el ciclo actual el {{ $subscription->ends_at?->format('d/m/Y') }}.</p>
                        <form action="{{ route('admin.subscription.resume') }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="w-full py-2 px-4 rounded-xl text-xs font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition-all">
                                <i class="bi bi-arrow-repeat mr-1"></i> Reactivar Suscripción
                            </button>
                        </form>
                    </div>
                @elseif($subscription && $subscription->isActive())
                    <form action="{{ route('admin.subscription.cancel') }}" method="POST"
                          onsubmit="return confirm('¿Está seguro de solicitar la cancelación de su suscripción al término del ciclo vigente?')">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-medium text-rose-300 hover:text-white hover:bg-rose-500/20 border border-rose-500/30 transition-all">
                            <i class="bi bi-x-circle mr-1"></i> Cancelar renovación automática
                        </button>
                    </form>
                @endif

                @if($plans->count() > 1)
                <a href="#catalogo-planes" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-md shadow-emerald-500/20 transition-all">
                    <i class="bi bi-arrow-up-circle mr-1"></i> Mejorar o Cambiar Plan
                </a>
                @else
                <a href="#catalogo-planes" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-md shadow-emerald-500/20 transition-all">
                    <i class="bi bi-patch-check mr-1"></i> Ver Cobertura del Plan ($30/mes)
                </a>
                @endif

                <!-- Online Checkout Buttons (Stripe / MercadoPago) -->
                <div class="w-full pt-2 flex flex-wrap lg:justify-end gap-2">
                    <form action="{{ route('admin.subscription.checkout') }}" method="POST">
                        @csrf
                        <input type="hidden" name="provider" value="stripe">
                        <input type="hidden" name="billing_cycle" value="{{ $subscription?->billing_cycle ?? 'monthly' }}">
                        <button type="submit"
                                class="px-3.5 py-2 rounded-xl text-[11px] font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm transition-all inline-flex items-center gap-1.5">
                            <i class="bi bi-credit-card-2-front"></i>
                            <span>Pagar con Stripe</span>
                        </button>
                    </form>
                    <form action="{{ route('admin.subscription.checkout') }}" method="POST">
                        @csrf
                        <input type="hidden" name="provider" value="mercadopago">
                        <input type="hidden" name="billing_cycle" value="{{ $subscription?->billing_cycle ?? 'monthly' }}">
                        <button type="submit"
                                class="px-3.5 py-2 rounded-xl text-[11px] font-bold bg-sky-600 hover:bg-sky-500 text-white shadow-sm transition-all inline-flex items-center gap-1.5">
                            <i class="bi bi-wallet2"></i>
                            <span>Pagar con MercadoPago</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage & Limits Tracking (Server-side enforced) -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <i class="bi bi-speedometer text-emerald-600"></i>
                Consumo de Cuotas vs Límites del Plan
            </h2>
            <span class="text-xs text-slate-500">Validados en tiempo real por el servidor</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Users Quota Card -->
            @php
                $userMax = $usage['users']['max'];
                $userCur = $usage['users']['current'];
                $userUnlimited = $usage['users']['unlimited'];
                $userPercent = $userUnlimited ? 15 : ($userMax > 0 ? min(100, round(($userCur / $userMax) * 100)) : 100);
            @endphp
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700 flex items-center gap-2">
                        <i class="bi bi-people-fill text-slate-400"></i>
                        Usuarios del Negocio
                    </span>
                    <span class="text-xs font-mono font-bold {{ $userPercent >= 90 && !$userUnlimited ? 'text-rose-600' : 'text-slate-800' }}">
                        {{ $userCur }} / {{ $userUnlimited ? 'Ilimitado' : $userMax }}
                    </span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full transition-all {{ $userPercent >= 90 && !$userUnlimited ? 'bg-rose-500' : 'bg-emerald-500' }}"
                         style="width: {{ $userPercent }}%"></div>
                </div>
                <p class="text-[11px] text-slate-500">
                    @if($userUnlimited)
                        Sin restricción de usuarios para este plan.
                    @else
                        Disponibles: <strong class="text-slate-800 font-mono">{{ max(0, $userMax - $userCur) }}</strong> vacantes.
                    @endif
                </p>
            </div>

            <!-- Products Quota Card -->
            @php
                $prodMax = $usage['products']['max'];
                $prodCur = $usage['products']['current'];
                $prodUnlimited = $usage['products']['unlimited'];
                $prodPercent = $prodUnlimited ? 15 : ($prodMax > 0 ? min(100, round(($prodCur / $prodMax) * 100)) : 100);
            @endphp
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700 flex items-center gap-2">
                        <i class="bi bi-box-seam-fill text-slate-400"></i>
                        Catálogo de Productos
                    </span>
                    <span class="text-xs font-mono font-bold {{ $prodPercent >= 90 && !$prodUnlimited ? 'text-rose-600' : 'text-slate-800' }}">
                        {{ $prodCur }} / {{ $prodUnlimited ? 'Ilimitado' : $prodMax }}
                    </span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full transition-all {{ $prodPercent >= 90 && !$prodUnlimited ? 'bg-rose-500' : 'bg-emerald-500' }}"
                         style="width: {{ $prodPercent }}%"></div>
                </div>
                <p class="text-[11px] text-slate-500">
                    @if($prodUnlimited)
                        Sin límite de productos registrados.
                    @else
                        Disponibles: <strong class="text-slate-800 font-mono">{{ max(0, $prodMax - $prodCur) }}</strong> productos.
                    @endif
                </p>
            </div>

            <!-- Cash Registers Quota Card -->
            @php
                $cashMax = $usage['cash_registers']['max'];
                $cashCur = $usage['cash_registers']['current'];
                $cashUnlimited = $usage['cash_registers']['unlimited'];
                $cashPercent = $cashUnlimited ? 15 : ($cashMax > 0 ? min(100, round(($cashCur / $cashMax) * 100)) : 100);
            @endphp
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700 flex items-center gap-2">
                        <i class="bi bi-wallet2 text-slate-400"></i>
                        Cajas Registradoras
                    </span>
                    <span class="text-xs font-mono font-bold {{ $cashPercent >= 90 && !$cashUnlimited ? 'text-rose-600' : 'text-slate-800' }}">
                        {{ $cashCur }} / {{ $cashUnlimited ? 'Ilimitado' : $cashMax }}
                    </span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full transition-all {{ $cashPercent >= 90 && !$cashUnlimited ? 'bg-rose-500' : 'bg-emerald-500' }}"
                         style="width: {{ $cashPercent }}%"></div>
                </div>
                <p class="text-[11px] text-slate-500">
                    @if($cashUnlimited)
                        Puntos de cobro simultáneos ilimitados.
                    @else
                        Disponibles: <strong class="text-slate-800 font-mono">{{ max(0, $cashMax - $cashCur) }}</strong> cajas.
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Plan Catalog Grid -->
    <div id="catalogo-planes" class="space-y-6 pt-4">
        <div class="text-center sm:text-left">
            <h2 class="text-lg font-bold text-slate-900 tracking-tight">
                {{ $plans->count() === 1 ? 'Membresía Oficial PuntoÁgil' : 'Planes de Suscripción Disponibles' }}
            </h2>
            <p class="text-xs text-slate-500">
                {{ $plans->count() === 1 ? 'Tarifa única con acceso integral a todos los módulos y funciones del sistema por solo $30.00/mes.' : 'Selecciona el plan que mejor se adapte al crecimiento y volumen de operaciones de tu negocio.' }}
            </p>
        </div>

        <div class="grid grid-cols-1 {{ $plans->count() === 1 ? 'max-w-xl mx-auto' : ($plans->count() === 2 ? 'md:grid-cols-2 max-w-4xl mx-auto' : 'md:grid-cols-3') }} gap-6 items-stretch">
            @foreach($plans as $plan)
                @php
                    $isCurrentPlan = ($subscription?->plan_id === $plan->id);
                @endphp
                <div class="flex flex-col justify-between rounded-3xl p-6 transition-all duration-200 {{ $isCurrentPlan ? 'bg-white border-2 border-emerald-500 shadow-floating relative' : 'bg-white border border-slate-200/80 hover:border-slate-300 shadow-sm' }}">
                    
                    @if($isCurrentPlan)
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full bg-emerald-500 text-slate-950 font-bold text-[10px] tracking-wider uppercase font-mono shadow-sm">
                            Tu Plan Actual
                        </div>
                    @endif

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-900">{{ $plan->name }}</h3>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                {{ $plan->trial_days > 0 ? "{$plan->trial_days} días prueba" : 'Directo' }}
                            </span>
                        </div>

                        <p class="text-xs text-slate-500 min-h-[36px]">{{ $plan->description }}</p>

                        <div class="py-2 border-y border-slate-100">
                            <span class="text-3xl font-black font-mono text-slate-900">${{ number_format($plan->price, 2) }}</span>
                            <span class="text-xs text-slate-400 font-medium">/ {{ $plan->billing_cycle === 'yearly' ? 'año' : 'mes' }}</span>
                        </div>

                        <!-- Features Checklist -->
                        <ul class="space-y-2.5 text-xs text-slate-600">
                            <li class="flex items-center gap-2">
                                <i class="bi bi-check2 text-emerald-500 font-bold"></i>
                                <span><strong>{{ $plan->isUnlimitedUsers() ? 'Usuarios ilimitados' : "Hasta {$plan->max_users} usuarios" }}</strong></span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="bi bi-check2 text-emerald-500 font-bold"></i>
                                <span><strong>{{ $plan->isUnlimitedProducts() ? 'Productos ilimitados' : "Hasta {$plan->max_products} productos" }}</strong></span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="bi bi-check2 text-emerald-500 font-bold"></i>
                                <span><strong>{{ $plan->isUnlimitedCashRegisters() ? 'Cajas ilimitadas' : "Hasta {$plan->max_cash_registers} cajas registradoras" }}</strong></span>
                            </li>

                            <li class="flex items-center gap-2 {{ $plan->hasFeature('purchases') ? 'text-slate-800' : 'text-slate-400 line-through' }}">
                                <i class="bi {{ $plan->hasFeature('purchases') ? 'bi-check2 text-emerald-500 font-bold' : 'bi-x text-slate-300' }}"></i>
                                <span>Gestión de Compras y Proveedores</span>
                            </li>
                            <li class="flex items-center gap-2 {{ $plan->hasFeature('returns') ? 'text-slate-800' : 'text-slate-400 line-through' }}">
                                <i class="bi {{ $plan->hasFeature('returns') ? 'bi-check2 text-emerald-500 font-bold' : 'bi-x text-slate-300' }}"></i>
                                <span>Devoluciones y Notas de Crédito</span>
                            </li>
                            <li class="flex items-center gap-2 {{ $plan->hasFeature('reports_export') ? 'text-slate-800' : 'text-slate-400 line-through' }}">
                                <i class="bi {{ $plan->hasFeature('reports_export') ? 'bi-check2 text-emerald-500 font-bold' : 'bi-x text-slate-300' }}"></i>
                                <span>Reportes Contables y Exportación Excel/PDF</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Bottom Action Button -->
                    <div class="pt-6 mt-6 border-t border-slate-100">
                        @if($isCurrentPlan)
                            <button disabled class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-slate-400 bg-slate-100 cursor-not-allowed">
                                Plan Activo
                            </button>
                        @else
                            <button @click="openChangeModal({{ $plan->id }}, '{{ addslashes($plan->name) }}', {{ $plan->price }})"
                                    type="button"
                                    class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-emerald-600 transition-colors shadow-sm">
                                Seleccionar este Plan
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Security & PCI-DSS Information Card -->
    <div class="rounded-2xl p-5 bg-slate-100/80 border border-slate-200 text-slate-600 text-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-white border border-slate-200 text-emerald-600 text-lg">
                <i class="bi bi-shield-check"></i>
            </div>
            <div>
                <p class="font-bold text-slate-800">Seguridad Criptográfica y Cumplimiento PCI-DSS</p>
                <p class="text-[11px] text-slate-500">PuntoÁgil nunca almacena números de tarjeta ni claves de seguridad bancarias. Todas las confirmaciones son procesadas mediante eventos Webhook firmados con HMAC-SHA256.</p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-[11px] font-mono text-slate-500">
            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
            Webhook Idempotente Activo
        </div>
    </div>

    <!-- Change Plan Modal (Alpine.js) -->
    <div x-show="changeModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="changeModalOpen = false"
             class="bg-white rounded-3xl max-w-md w-full p-6 shadow-floating border border-slate-100 space-y-5 animate-scaleIn">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900">Confirmar Cambio de Plan</h3>
                <button @click="changeModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                Estás por cambiar la suscripción de <strong class="text-slate-900">{{ $business->name }}</strong> al plan:
            </p>

            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold text-emerald-800 uppercase tracking-wider block">Nuevo Plan</span>
                    <strong class="text-lg font-bold text-emerald-950" x-text="selectedPlanName"></strong>
                </div>
                <div class="text-right">
                    <span class="text-2xl font-black font-mono text-emerald-900" x-text="'$' + Number(selectedPlanPrice).toFixed(2)"></span>
                </div>
            </div>

            <form action="{{ route('admin.subscription.change-plan') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="plan_id" :value="selectedPlanId">

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Periodicidad de Facturación</label>
                    <select name="billing_cycle" x-model="billingCycle"
                            class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none">
                        <option value="monthly">Facturación Mensual</option>
                        <option value="yearly">Facturación Anual</option>
                    </select>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-[11px] text-slate-500 leading-normal">
                    <i class="bi bi-info-circle text-emerald-600 mr-1"></i>
                    El servidor validará que la cantidad actual de usuarios, productos y cajas activas no exceda el límite del nuevo plan antes de efectuar el cambio.
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="changeModalOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-sm">
                        Confirmar y Aplicar Plan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
