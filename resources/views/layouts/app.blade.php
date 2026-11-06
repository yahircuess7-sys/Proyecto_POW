<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PuntoÁgil') - Sistema de Gestión y POS</title>

    <!-- Google Fonts: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons for universal lightweight iconography -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Tailwind CSS v3 via Official CDN with PuntoÁgil Emerald & Slate Theme -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            500: '#10b981', /* Verde Esmeralda */
                            600: '#059669', /* Verde Oscuro */
                            700: '#047857',
                            slate: '#0f172a', /* Azul Pizarra */
                            card: '#1e293b',
                            bg: '#f8fafc',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    boxShadow: {
                        'subtle': '0 1px 3px 0 rgba(15, 23, 42, 0.05), 0 1px 2px 0 rgba(15, 23, 42, 0.02)',
                        'card': '0 4px 6px -1px rgba(15, 23, 42, 0.04), 0 2px 4px -1px rgba(15, 23, 42, 0.02)',
                        'floating': '0 20px 25px -5px rgba(15, 23, 42, 0.08), 0 10px 10px -5px rgba(15, 23, 42, 0.04)',
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js for lightweight, reactive UI components (drawers, modals, dropdowns) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }

        /* Cross-Document View Transitions & Compositor Motion (Modern Web Guidance) */
        @media (prefers-reduced-motion: no-preference) {
            @view-transition {
                navigation: auto;
            }

            .vt-sidebar { view-transition-name: app-sidebar; }
            .vt-header { view-transition-name: app-header; }
            .vt-main { view-transition-name: main-content; }

            ::view-transition-old(main-content) {
                animation: 0.18s cubic-bezier(0.4, 0, 1, 1) both vt-fade-out;
            }
            ::view-transition-new(main-content) {
                animation: 0.28s cubic-bezier(0.22, 1, 0.36, 1) 0.04s both vt-fade-in;
            }

            @keyframes vt-fade-out {
                to { opacity: 0; translate: 0 -6px; }
            }
            @keyframes vt-fade-in {
                from { opacity: 0; translate: 0 10px; }
                to { opacity: 1; translate: 0 0; }
            }

            @keyframes fadeUp {
                from { opacity: 0; translate: 0 16px; }
                to { opacity: 1; translate: 0 0; }
            }

            @keyframes scaleIn {
                from { opacity: 0; scale: 0.96; }
                to { opacity: 1; scale: 1; }
            }

            @keyframes slideInRight {
                from { opacity: 0; translate: -12px 0; }
                to { opacity: 1; translate: 0 0; }
            }

            @keyframes gentleFloat {
                0%, 100% { translate: 0 0; }
                50% { translate: 0 -5px; }
            }

            .animate-fade-up {
                animation: fadeUp 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
            }
            .animate-scale-in {
                animation: scaleIn 0.35s cubic-bezier(0.22, 1, 0.36, 1) both;
            }
            .animate-slide-right {
                animation: slideInRight 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
            }
            .animate-float {
                animation: gentleFloat 4s ease-in-out infinite;
            }

            .stagger-1 { animation-delay: 50ms; }
            .stagger-2 { animation-delay: 100ms; }
            .stagger-3 { animation-delay: 150ms; }
            .stagger-4 { animation-delay: 200ms; }
            .stagger-5 { animation-delay: 250ms; }

            .saas-card-hover {
                transition: translate 0.25s cubic-bezier(0.22, 1, 0.36, 1),
                            box-shadow 0.25s cubic-bezier(0.22, 1, 0.36, 1),
                            border-color 0.25s ease;
            }
            .saas-card-hover:hover {
                translate: 0 -3px;
                box-shadow: 0 12px 24px -6px rgba(15, 23, 42, 0.08), 0 4px 8px -4px rgba(16, 185, 129, 0.06);
            }

            .nav-link-anim {
                transition: translate 0.2s cubic-bezier(0.22, 1, 0.36, 1),
                            background-color 0.2s ease,
                            color 0.2s ease;
            }
            .nav-link-anim:hover {
                translate: 3px 0;
            }
        }

        /* Button subtle shine effect */
        .btn-shine {
            position: relative;
            overflow: hidden;
        }
        @media (prefers-reduced-motion: no-preference) {
            .btn-shine::after {
                content: '';
                position: absolute;
                inset: 0;
                translate: -120% 0;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.22), transparent);
                transition: translate 0.55s ease;
            }
            .btn-shine:hover::after {
                translate: 120% 0;
            }
        }

        /* Modern subtle dark scrollbar for the sidebar */
        .sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(71, 85, 105, 0.45) transparent;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }
        .sidebar-scroll::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(71, 85, 105, 0.45);
            border-radius: 9999px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(16, 185, 129, 0.65);
        }

        /* Native <dialog> top-layer scroll containment (Modern Web Guidance) */
        dialog:modal {
            overscroll-behavior: contain;
        }
        dialog::backdrop {
            overflow: hidden;
            overscroll-behavior: contain;
            background-color: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
        }

        /* Reusable Modal & Button Utilities */
        .pa-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 60;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background-color: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
        }
        .pa-modal {
            width: 100%;
            max-width: 28rem;
            background: #ffffff;
            border-radius: 1.25rem;
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
            padding: 1.5rem;
        }
        .pa-btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.125rem;
            border-radius: 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            background-color: #10b981;
            color: #ffffff;
            transition: all 0.15s ease;
        }
        .pa-btn-primary:hover { background-color: #059669; }
        .pa-btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.125rem;
            border-radius: 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
            transition: all 0.15s ease;
        }
        .pa-btn-secondary:hover { background-color: #e2e8f0; }
        .pa-btn-danger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.125rem;
            border-radius: 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            background-color: #e11d48;
            color: #ffffff;
            transition: all 0.15s ease;
        }
        .pa-btn-danger:hover { background-color: #be123c; }

        @media print {
            .vt-sidebar, .vt-header, footer, .no-print { display: none !important; }
            .lg\:pl-64 { padding-left: 0 !important; }
            .vt-main { padding: 0 !important; max-width: 100% !important; }
            body { background: #ffffff !important; }
        }
    </style>
    @yield('styles')
</head>
<body class="h-full antialiased text-slate-800 bg-[#f8fafc]" x-data="{ sidebarOpen: false, userDropdown: false, tenantDropdown: false }">

    <!-- Mobile Drawer Overlay -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
         @click="sidebarOpen = false" x-cloak>
    </div>

    <!-- Sidebar Layout -->
    <aside class="vt-sidebar fixed inset-y-0 left-0 z-50 flex flex-col w-64 h-screen max-h-screen bg-[#0f172a] text-slate-300 transition-transform duration-300 ease-in-out lg:translate-x-0 shadow-xl border-r border-slate-800"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        
        <!-- Brand Header -->
        <div class="flex-shrink-0 flex items-center justify-between h-16 px-6 bg-slate-950/40 border-b border-slate-800/80">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group text-white">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 text-white shadow-lg shadow-emerald-500/20 group-hover:scale-105 group-hover:rotate-3 transition-transform duration-300">
                    <i class="bi bi-lightning-charge-fill text-xl"></i>
                </div>
                <div>
                    <span class="text-lg font-bold tracking-tight text-white flex items-center gap-1.5">
                        Punto<span class="text-emerald-400 font-extrabold">Ágil</span>
                    </span>
                    <span class="block text-[10px] uppercase tracking-wider text-emerald-400/90 font-medium">SaaS Cloud POS</span>
                </div>
            </a>
            <button class="p-1 rounded-lg text-slate-400 hover:text-white lg:hidden" @click="sidebarOpen = false">
                <i class="bi bi-x-lg text-lg"></i>
            </button>
        </div>

        <!-- Navigation Links (Scrollable area) -->
        <div class="flex-1 min-h-0 px-4 py-5 overflow-y-auto space-y-6 sidebar-scroll">
            
            <!-- Principal -->
            <div>
                <p class="px-3 mb-2 text-[11px] font-semibold tracking-wider uppercase text-slate-300 font-mono">Operaciones</p>
                <nav class="space-y-1">
                    <a href="{{ route('dashboard') }}" 
                       class="nav-link-anim flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-emerald-500/15 text-emerald-400 font-semibold border border-emerald-500/30 shadow-sm shadow-emerald-500/10' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i class="bi bi-speedometer2 text-base {{ request()->routeIs('dashboard') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Panel Inicial</span>
                    </a>

                    <a href="{{ route('inventory.index') }}" 
                       class="nav-link-anim flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('inventory.*') ? 'bg-emerald-500/15 text-emerald-400 font-semibold border border-emerald-500/30 shadow-sm shadow-emerald-500/10' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i class="bi bi-boxes text-base {{ request()->routeIs('inventory.*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Inventario y Stock</span>
                    </a>

                    <a href="{{ route('pos.create') }}" 
                       class="nav-link-anim flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('pos.*') ? 'bg-emerald-500/15 text-emerald-400 font-semibold border border-emerald-500/30 shadow-sm shadow-emerald-500/10' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <i class="bi bi-cart4 text-base {{ request()->routeIs('pos.*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                            <span>Punto de Venta</span>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-500 text-slate-950 font-mono tracking-wide">POS</span>
                    </a>

                    <a href="{{ route('cash.current') }}" 
                       class="nav-link-anim flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('cash.*') ? 'bg-emerald-500/15 text-emerald-400 font-semibold border border-emerald-500/30 shadow-sm shadow-emerald-500/10' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <i class="bi bi-wallet2 text-base {{ request()->routeIs('cash.*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                            <span>Caja y Turnos</span>
                        </div>
                    </a>

                    <a href="{{ route('sales.history') }}" 
                       class="nav-link-anim flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('sales.history') || (request()->routeIs('sales.show') && !request()->routeIs('pos.*')) ? 'bg-emerald-500/15 text-emerald-400 font-semibold border border-emerald-500/30 shadow-sm shadow-emerald-500/10' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i class="bi bi-receipt text-base {{ request()->routeIs('sales.history') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Historial Ventas</span>
                    </a>

                    <a href="{{ route('sales.returns.index') }}" 
                       class="nav-link-anim flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('sales.returns.*') ? 'bg-emerald-500/15 text-emerald-400 font-semibold border border-emerald-500/30 shadow-sm shadow-emerald-500/10' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i class="bi bi-arrow-counterclockwise text-base {{ request()->routeIs('sales.returns.*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Devoluciones</span>
                    </a>

                    <a href="{{ route('customers.index') }}" 
                       class="nav-link-anim flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('customers.*') ? 'bg-emerald-500/15 text-emerald-400 font-semibold border border-emerald-500/30 shadow-sm shadow-emerald-500/10' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i class="bi bi-people-fill text-base {{ request()->routeIs('customers.*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Clientes</span>
                    </a>

                    @if(auth()->user() && auth()->user()->isAdmin())
                    <a href="{{ route('suppliers.index') }}" 
                       class="nav-link-anim flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('suppliers.*') ? 'bg-emerald-500/15 text-emerald-400 font-semibold border border-emerald-500/30 shadow-sm shadow-emerald-500/10' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i class="bi bi-truck text-base {{ request()->routeIs('suppliers.*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Proveedores</span>
                    </a>

                    <a href="{{ route('purchases.index') }}" 
                       class="nav-link-anim flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('purchases.*') ? 'bg-emerald-500/15 text-emerald-400 font-semibold border border-emerald-500/30 shadow-sm shadow-emerald-500/10' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i class="bi bi-bag-check text-base {{ request()->routeIs('purchases.*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Compras</span>
                    </a>
                    @endif
                </nav>
            </div>

            <!-- Administración (Solo con rol admin, propietario o super admin) -->
            @if(auth()->user() && auth()->user()->isAdmin())
            <div>
                <p class="px-3 mb-2 text-[11px] font-semibold tracking-wider uppercase text-slate-300 font-mono">Administración</p>
                <nav class="space-y-1">
                    <a href="{{ route('admin.users.index') }}" 
                       class="nav-link-anim flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('admin.users.*') ? 'bg-emerald-500/15 text-emerald-400 font-semibold border border-emerald-500/30 shadow-sm shadow-emerald-500/10' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i class="bi bi-people text-base {{ request()->routeIs('admin.users.*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Gestión de Usuarios</span>
                    </a>

                    <a href="{{ route('reports.index') }}" 
                       class="nav-link-anim flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('reports.*') ? 'bg-emerald-500/15 text-emerald-400 font-semibold border border-emerald-500/30 shadow-sm shadow-emerald-500/10' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i class="bi bi-graph-up-arrows text-base {{ request()->routeIs('reports.*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Reportes Contables</span>
                    </a>

                    <a href="{{ route('admin.subscription.index') }}" 
                       class="nav-link-anim flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('admin.subscription.*') ? 'bg-emerald-500/15 text-emerald-400 font-semibold border border-emerald-500/30 shadow-sm shadow-emerald-500/10' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <i class="bi bi-patch-check text-base {{ request()->routeIs('admin.subscription.*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                            <span>Mi Suscripción</span>
                        </div>
                        <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-semibold rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Plan</span>
                    </a>

                    @can('manage-business-settings')
                    <a href="{{ route('admin.business.settings') }}" 
                       class="nav-link-anim flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('admin.business.*') ? 'bg-emerald-500/15 text-emerald-400 font-semibold border border-emerald-500/30 shadow-sm shadow-emerald-500/10' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i class="bi bi-building-gear text-base {{ request()->routeIs('admin.business.*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Configurar Empresa</span>
                    </a>
                    @endcan

                    @can('view-audit-logs')
                    <a href="{{ route('admin.audit.index') }}" 
                       class="nav-link-anim flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('admin.audit.*') ? 'bg-emerald-500/15 text-emerald-400 font-semibold border border-emerald-500/30 shadow-sm shadow-emerald-500/10' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i class="bi bi-shield-check text-base {{ request()->routeIs('admin.audit.*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Auditoría Protegida</span>
                    </a>
                    @endcan
                </nav>
            </div>
            @endif

            <!-- Administración Global Separada (Exclusivo Super Admin) -->
            @can('manage-platform')
            <div>
                <p class="px-3 mb-2 text-[11px] font-semibold tracking-wider uppercase text-amber-400 font-mono">Super Admin Global</p>
                <nav class="space-y-1">
                    <a href="{{ route('admin.superadmin.dashboard') }}" 
                       class="nav-link-anim flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('admin.superadmin.dashboard') ? 'bg-amber-500/15 text-amber-400 font-semibold border border-amber-500/30 shadow-sm' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i class="bi bi-speedometer2 text-base {{ request()->routeIs('admin.superadmin.dashboard') ? 'text-amber-400' : 'text-slate-400' }}"></i>
                        <span>Panel Central SaaS</span>
                    </a>

                    <a href="{{ route('admin.superadmin.businesses') }}" 
                       class="nav-link-anim flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('admin.superadmin.businesses*') || request()->routeIs('admin.businesses.*') ? 'bg-amber-500/15 text-amber-400 font-semibold border border-amber-500/30 shadow-sm' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i class="bi bi-buildings text-base {{ request()->routeIs('admin.superadmin.businesses*') ? 'text-amber-400' : 'text-slate-400' }}"></i>
                        <span>Negocios (Tenants)</span>
                    </a>

                    <a href="{{ route('admin.superadmin.plans') }}" 
                       class="nav-link-anim flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('admin.superadmin.plans*') ? 'bg-amber-500/15 text-amber-400 font-semibold border border-amber-500/30 shadow-sm' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i class="bi bi-tags text-base {{ request()->routeIs('admin.superadmin.plans*') ? 'text-amber-400' : 'text-slate-400' }}"></i>
                        <span>Planes y Precios</span>
                    </a>

                    <a href="{{ route('admin.superadmin.users') }}" 
                       class="nav-link-anim flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ request()->routeIs('admin.superadmin.users*') ? 'bg-amber-500/15 text-amber-400 font-semibold border border-amber-500/30 shadow-sm' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i class="bi bi-person-gear text-base {{ request()->routeIs('admin.superadmin.users*') ? 'text-amber-400' : 'text-slate-400' }}"></i>
                        <span>Usuarios Globales</span>
                    </a>
                </nav>
            </div>
            @endcan

            <!-- Sistema -->
            <div>
                <p class="px-3 mb-2 text-[11px] font-semibold tracking-wider uppercase text-slate-300 font-mono">Accesos</p>
                <nav class="space-y-1">
                    <a href="{{ route('home') }}" 
                       class="nav-link-anim flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:bg-slate-800/70 hover:text-white">
                        <i class="bi bi-globe text-base text-slate-400"></i>
                        <span>Página Pública</span>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Sidebar Footer / Logout -->
        <div class="flex-shrink-0 p-4 border-t border-slate-800/80 bg-slate-950/40">
            <div class="flex items-center gap-3 p-2 rounded-xl bg-slate-900/80 border border-slate-800 mb-3">
                <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-emerald-500/10 text-emerald-400 font-bold text-sm border border-emerald-500/20">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name ?? 'Usuario' }}</p>
                    <p class="text-[11px] text-slate-400 capitalize flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full {{ auth()->user() && auth()->user()->status === 'active' ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                        {{ $currentBusinessUserRole ?? (auth()->user()->role ?? 'Cajero') }}
                    </p>
                </div>
            </div>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" 
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold text-rose-400 hover:text-white hover:bg-rose-500/20 border border-rose-500/30 transition-all">
                    <i class="bi bi-box-arrow-left text-sm"></i>
                    <span>Cerrar Sesión</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="lg:pl-64 flex flex-col min-h-screen">
        
        <!-- Top App Header -->
        @php
            $userActiveBusinesses = auth()->check()
                ? auth()->user()->businesses()->where('businesses.status', 'active')->get()
                : collect();
            $headerSub = isset($currentBusiness) && $currentBusiness ? $currentBusiness->currentSubscription() : null;
            $activeTenantName = $currentBusiness->name ?? 'PuntoÁgil Central';
            $activeTenantInitial = strtoupper(mb_substr($activeTenantName, 0, 1));
            $activeRoleLabel = match ($currentBusinessUserRole ?? (auth()->user()->role ?? 'cajero')) {
                'owner' => 'Propietario',
                'admin' => 'Administrador',
                'super_admin' => 'Super Admin',
                default => 'Cajero',
            };
        @endphp
        <header class="vt-header sticky top-0 z-30 flex items-center justify-between h-16 px-4 md:px-8 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-sm">
            <div class="flex items-center gap-3 min-w-0">
                <button @click="sidebarOpen = true" class="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 lg:hidden focus:outline-none">
                    <i class="bi bi-list text-2xl leading-none"></i>
                </button>

                <!-- Modern Tenant Switcher (Notion / Linear / Shopify style) -->
                <div class="relative" @click.away="tenantDropdown = false">
                    <button type="button"
                            @click="tenantDropdown = !tenantDropdown"
                            class="group flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-xl bg-slate-50/90 hover:bg-slate-100 border border-slate-200/80 transition-all focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                        <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 text-white text-xs font-bold shadow-sm shrink-0">
                            {{ $activeTenantInitial }}
                        </div>
                        <div class="text-left min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-bold text-slate-800 truncate max-w-[140px] sm:max-w-[210px]">{{ $activeTenantName }}</span>
                                <span class="hidden md:inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-white text-slate-600 border border-slate-200/80">
                                    {{ $activeRoleLabel }}
                                </span>
                            </div>
                        </div>
                        <i class="bi bi-chevron-expand text-xs text-slate-400 group-hover:text-slate-600 transition-colors"></i>
                    </button>

                    <div x-show="tenantDropdown"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute left-0 mt-2 w-72 bg-white rounded-2xl shadow-floating border border-slate-200/80 py-2 z-50 divide-y divide-slate-100"
                         x-cloak>
                        <div class="px-3.5 py-2">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Negocio Activo</p>
                            <div class="flex items-center justify-between mt-1">
                                <p class="text-xs font-bold text-slate-900 truncate">{{ $activeTenantName }}</p>
                                <span class="text-[10px] font-mono font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded">C$ NIO</span>
                            </div>
                        </div>

                        @if($userActiveBusinesses->count() > 1)
                            <div class="py-1.5 max-h-56 overflow-y-auto">
                                <p class="px-3.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Cambiar de Negocio</p>
                                @foreach($userActiveBusinesses as $ub)
                                    @php
                                        $isCurrentTenant = (int) ($currentBusiness->id ?? auth()->user()->currentBusinessId()) === (int) $ub->id;
                                    @endphp
                                    <form action="{{ route('business.switch') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="business_id" value="{{ $ub->id }}">
                                        <button type="submit"
                                                class="w-full flex items-center justify-between px-3.5 py-2 text-xs transition-colors {{ $isCurrentTenant ? 'bg-emerald-50/70 text-emerald-800 font-bold' : 'text-slate-700 hover:bg-slate-50 font-medium' }}">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span class="w-6 h-6 rounded-md flex items-center justify-center text-[11px] font-bold shrink-0 {{ $isCurrentTenant ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600' }}">
                                                    {{ strtoupper(mb_substr($ub->name, 0, 1)) }}
                                                </span>
                                                <div class="text-left min-w-0">
                                                    <span class="block truncate max-w-[165px]">{{ $ub->name }}</span>
                                                    <span class="block text-[10px] text-slate-400 capitalize">{{ $ub->pivot->role }}</span>
                                                </div>
                                            </div>
                                            @if($isCurrentTenant)
                                                <i class="bi bi-check2 text-emerald-600 text-sm shrink-0"></i>
                                            @endif
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @endif

                        <div class="py-1">
                            @can('manage-business-settings')
                                <a href="{{ route('admin.business.settings') }}" class="flex items-center gap-2.5 px-3.5 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-emerald-600 transition-colors">
                                    <i class="bi bi-sliders text-slate-400"></i>
                                    <span>Configurar Datos y Ticket</span>
                                </a>
                            @endcan
                            @if(auth()->check() && auth()->user()->isAdmin())
                                <a href="{{ route('admin.subscription.index') }}" class="flex items-center gap-2.5 px-3.5 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-emerald-600 transition-colors">
                                    <i class="bi bi-patch-check text-slate-400"></i>
                                    <span>Estado de Suscripción</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Controls -->
            <div class="flex items-center gap-2.5">
                <!-- Discreet Subscription Status Pill -->
                @if(auth()->check() && auth()->user()->isAdmin() && $headerSub)
                    @if($headerSub->isTrialing())
                        @php $trialLeft = $headerSub->trialDaysRemaining(); @endphp
                        <a href="{{ route('admin.subscription.index') }}"
                           class="hidden lg:inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-[11px] font-semibold transition-colors {{ $trialLeft <= 3 ? 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100' : 'bg-sky-50 text-sky-800 border border-sky-200/80 hover:bg-sky-100/80' }}"
                           title="Administrar plan y suscripción">
                            <i class="bi bi-hourglass-split {{ $trialLeft <= 3 ? 'text-amber-600' : 'text-sky-600' }}"></i>
                            <span>Prueba: {{ $trialLeft }} {{ $trialLeft === 1 ? 'día restante' : 'días restantes' }}</span>
                            <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-white/90 border border-current/15">Actualizar</span>
                        </a>
                    @elseif(!$headerSub->isActive())
                        <a href="{{ route('admin.subscription.index') }}"
                           class="hidden lg:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 transition-colors">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <span>Suscripción {{ $headerSub->statusLabel() }} · Renovar</span>
                        </a>
                    @else
                        <a href="{{ route('admin.subscription.index') }}"
                           class="hidden xl:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[11px] font-semibold bg-emerald-50/80 text-emerald-700 border border-emerald-200/70 hover:bg-emerald-100/70 transition-colors"
                           title="Suscripción activa">
                            <i class="bi bi-patch-check-fill text-emerald-500"></i>
                            <span>Plan Activo</span>
                        </a>
                    @endif
                @endif

                @if(auth()->check() && auth()->user()->isSuperAdmin())
                    <a href="{{ route('admin.superadmin.dashboard') }}"
                       class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 transition-colors"
                       title="Ir al Panel Global de Super Admin">
                        <i class="bi bi-shield-shaded"></i>
                        <span>Super Admin</span>
                    </a>
                @endif

                <a href="{{ route('pos.create') }}" 
                   class="btn-shine inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold bg-emerald-500 hover:bg-emerald-600 text-white shadow-sm shadow-emerald-500/30 transition-all active:scale-95">
                    <i class="bi bi-cart-plus-fill"></i>
                    <span class="hidden sm:inline">Ir al POS</span>
                </a>

                <!-- User Popover Menu -->
                <div class="relative" @click.away="userDropdown = false">
                    <button @click="userDropdown = !userDropdown" 
                            class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-100 transition-colors focus:outline-none">
                        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-900 text-white text-xs font-bold shadow-sm">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="hidden md:block text-left">
                            <p class="text-xs font-semibold text-slate-800 leading-none truncate max-w-[110px]">{{ explode(' ', trim(auth()->user()->name ?? 'Usuario'))[0] }}</p>
                            <p class="text-[10px] text-slate-400 leading-none mt-1">{{ $activeRoleLabel }}</p>
                        </div>
                        <i class="bi bi-chevron-down text-xs text-slate-400"></i>
                    </button>

                    <div x-show="userDropdown" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-floating border border-slate-200/80 py-2 z-50 divide-y divide-slate-100"
                         x-cloak>
                        <div class="px-4 py-2.5">
                            <p class="text-xs font-semibold text-slate-900">{{ auth()->user()->name ?? 'Usuario' }}</p>
                            <p class="text-[11px] text-slate-500 truncate">{{ auth()->user()->email ?? '' }}</p>
                        </div>
                        <div class="py-1">
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-emerald-600 transition-colors">
                                <i class="bi bi-person-gear text-sm text-slate-400"></i>
                                <span>Mi Perfil y Contraseña</span>
                            </a>
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-emerald-600 transition-colors">
                                <i class="bi bi-speedometer2 text-sm text-slate-400"></i>
                                <span>Panel de Control</span>
                            </a>
                            @if(auth()->check() && (auth()->user()->isOwner() || auth()->user()->isSuperAdmin()))
                                <a href="{{ route('admin.business.settings') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-emerald-600 transition-colors">
                                    <i class="bi bi-gear text-sm text-slate-400"></i>
                                    <span>Configuración de Empresa</span>
                                </a>
                            @endif
                        </div>
                        <div class="py-1">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left flex items-center gap-2.5 px-4 py-2 text-xs text-rose-600 hover:bg-rose-50 transition-colors">
                                    <i class="bi bi-box-arrow-left text-sm"></i>
                                    <span>Cerrar Sesión</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <main class="vt-main flex-1 p-4 md:p-8 max-w-7xl w-full mx-auto">
            
            <!-- Global Flash Messages -->
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition class="no-print animate-scale-in flex items-center justify-between p-4 mb-6 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-800 shadow-subtle">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-500 text-white shrink-0">
                            <i class="bi bi-check-lg text-lg"></i>
                        </div>
                        <p class="text-sm font-medium">{{ session('success') }}</p>
                    </div>
                    <button @click="show = false" class="text-emerald-600 hover:text-emerald-900 p-1">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-transition class="no-print flex items-center justify-between p-4 mb-6 rounded-2xl bg-rose-50 border border-rose-200/80 text-rose-800 shadow-subtle">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-8 h-8 rounded-xl bg-rose-500 text-white shrink-0">
                            <i class="bi bi-exclamation-triangle-fill text-sm"></i>
                        </div>
                        <p class="text-sm font-medium">{{ session('error') }}</p>
                    </div>
                    <button @click="show = false" class="text-rose-600 hover:text-rose-900 p-1">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
            @endif

            @if($errors->any())
                <div x-data="{ show: true }" x-show="show" x-transition class="no-print flex items-start justify-between p-4 mb-6 rounded-2xl bg-rose-50 border border-rose-200/80 text-rose-800 shadow-subtle">
                    <div class="flex items-start gap-3">
                        <div class="flex items-center justify-center w-8 h-8 rounded-xl bg-rose-500 text-white shrink-0 mt-0.5">
                            <i class="bi bi-exclamation-octagon-fill text-sm"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-rose-700 mb-1">Revisa los datos ingresados</p>
                            <ul class="text-xs font-medium space-y-0.5 list-disc list-inside">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <button @click="show = false" class="text-rose-600 hover:text-rose-900 p-1">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="mt-auto py-5 px-6 md:px-8 border-t border-slate-200 bg-white text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p>&copy; {{ date('Y') }} <strong class="text-slate-700">PuntoÁgil</strong> &bull; Plataforma SaaS de Gestión Comercial</p>
            <div class="flex items-center gap-4 text-slate-400">
                <span class="inline-flex items-center gap-1.5"><i class="bi bi-shield-check text-emerald-500"></i> Datos Protegidos</span>
                <span>&bull;</span>
                <span>PostgreSQL Cloud</span>
            </div>
        </footer>
    </div>

    @yield('scripts')
</body>
</html>
