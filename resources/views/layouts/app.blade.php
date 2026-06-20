<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ferretería El Tornillo') - Sistema Administrativo</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Estilos Personalizados del Sistema -->
    <style>
        :root {
            --fc-primary: #1e3a8a;       /* Azul Ferretero Oscuro */
            --fc-secondary: #f97316;     /* Naranja Tornillo */
            --fc-dark: #0f172a;          /* Dark Slate */
            --fc-light: #f8fafc;         /* Soft Gray Background */
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--fc-light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background-color: var(--fc-dark);
            color: #94a3b8;
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            transition: all 0.3s;
        }

        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.85rem 1.5rem;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }

        .sidebar-link:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: #fff;
            border-left-color: var(--fc-secondary);
        }

        .sidebar-link.active {
            background-color: rgba(249, 115, 22, 0.1);
            color: #fff;
            font-weight: 600;
            border-left-color: var(--fc-secondary);
        }

        /* Main Content Layout */
        .main-wrapper {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: all 0.3s;
        }

        .top-navbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1.5rem;
        }

        .footer {
            background: #fff;
            border-top: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
            margin-top: auto;
        }

        .badge-pos {
            background-color: var(--fc-secondary);
            color: white;
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
        }

        /* Responsive Breakpoints */
        @media (max-width: 991.98px) {
            .sidebar {
                margin-left: -260px;
            }
            .sidebar.active {
                margin-left: 0;
            }
            .main-wrapper {
                margin-left: 0;
            }
        }
    </style>
    @yield('styles')
</head>
<body>

    <!-- Sidebar de la Ferretería -->
    <aside class="sidebar" id="sidebarMenu">
        <div class="sidebar-brand">
            <i class="bi bi-nut-fill text-warning fs-3"></i>
            <div>
                <h6 class="text-white mb-0 fw-bold">El Tornillo</h6>
                <small class="text-xs text-muted" style="font-size: 0.7rem;">SISTEMA ADMINISTRATIVO</small>
            </div>
        </div>

        <div class="py-3">
            <div class="px-3 mb-2">
                <small class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.65rem; letter-spacing: 0.05em;">MENU GENERAL</small>
            </div>

            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2 fs-5"></i>
                <span>Panel Inicial</span>
            </a>

            <!-- Módulo de Inventario - Accesible por Admin y Cajeros -->
            <a href="{{ route('inventory.index') }}" class="sidebar-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                <i class="bi bi-boxes fs-5"></i>
                <span>Gestión Inventario</span>
            </a>

            <!-- Módulo de Ventas / POS (Punto de Venta) -->
            <a href="{{ route('pos.create') }}" class="sidebar-link {{ request()->routeIs('sales.*') || request()->routeIs('pos.*') ? 'active' : '' }}">
                <i class="bi bi-cart4 fs-5"></i>
                <span>Punto de Venta</span>
                <span class="ms-auto badge-pos font-monospace">POS</span>
            </a>

            @if(auth()->user() && auth()->user()->role === 'admin')
            <div class="px-3 mt-4 mb-2">
                <small class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.65rem; letter-spacing: 0.05em;">ADMINISTRACIÓN</small>
            </div>
            
            <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="bi bi-people fs-5"></i>
                <span>Gestionar Usuarios</span>
            </a>
            
            <a href="{{ route('reports.index') }}" class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrows fs-5"></i>
                <span>Reportes y Ventas</span>
            </a>
            @endif

            <div class="px-3 mt-4 mb-2">
                <small class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.65rem; letter-spacing: 0.05em;">SISTEMA</small>
            </div>
            
            <a href="{{ route('home') }}" class="sidebar-link">
                <i class="bi bi-globe fs-5"></i>
                <span>Ir al Sitio Público</span>
            </a>

            <div class="px-3 mt-5">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger w-full d-flex align-items-center justify-content-center gap-2 py-2">
                        <i class="bi bi-box-arrow-left"></i>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Envoltorio de Contenido Principal -->
    <div class="main-wrapper">
        
        <!-- Navbar Superior de Acciones -->
        <nav class="navbar top-navbar navbar-expand bg-white sticky-top shadow-sm">
            <div class="container-fluid">
                <!-- Botón de apertura de barra lateral en móvil -->
                <button class="btn btn-outline-secondary me-3 d-lg-none" id="sidebarToggle" onclick="document.getElementById('sidebarMenu').classList.toggle('active')">
                    <i class="bi bi-list"></i>
                </button>

                <!-- Buscador rápido en la cabecera -->
                <form class="d-none d-sm-flex grow max-w-lg" style="max-width: 350px;">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0" placeholder="Buscar código, clavos, categorías..." aria-label="Search">
                    </div>
                </form>

                <!-- Menú de Usuario Derecha -->
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-3 text-end d-none d-md-block">
                        <span class="d-block fw-semibold text-dark fs-7">
                            {{ auth()->user()->name ?? 'Administrador Tornillo' }}
                        </span>
                        <small class="text-muted text-uppercase d-block" style="font-size: 0.65rem; color: var(--fc-secondary) !important;">
                            Rol: <span class="badge {{ (auth()->user()->role ?? 'admin') === 'admin' ? 'bg-primary' : 'bg-success' }}">{{ auth()->user()->role ?? 'Administrador' }}</span>
                        </small>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="navbarDropdown">
                            <li><h6 class="dropdown-header">Perfil de Usuario</h6></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Mi cuenta</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Ajustes del POS</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button class="dropdown-item text-danger" type="submit">
                                        <i class="bi bi-box-arrow-left me-2"></i>Salir
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Contenedor de Vistas Dinámicas -->
        <main class="container-fluid py-4 px-md-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-success" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 border-start border-danger" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Pie de página simple comercial -->
        <footer class="footer bg-white text-muted text-center py-3">
            <div class="container-fluid d-md-flex align-items-center justify-content-between">
                <span>&copy; {{ date('Y') }} <strong>Ferretería El Tornillo S.A.</strong> - Sistema Administrativo.</span>
            </div>
        </footer>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>