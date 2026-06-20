import { BladeFile } from '../types';

export const laravelTemplates: BladeFile[] = [
  {
    path: 'resources/views/admin/users.blade.php',
    title: 'Gestor de Usuarios',
    description: 'CRUD de usuarios. Vista exclusiva para el administrador para gestionar identidades del sistema y niveles de acceso (rol).',
    icon: 'bi-people',
    code: `@extends('layouts.app')

@section('title', 'Gestionar Usuarios Activos')

@section('content')
<div class="row align-items-center mb-4 fade-in">
    <div class="col-md-6">
        <h4 class="fw-bold mb-0 text-dark">
            <i class="bi bi-people-fill text-warning me-2"></i>
            Gestión de Usuarios Activos
        </h4>
        <p class="text-secondary small mb-0 mt-1">Control de credenciales y roles para administradores y cajeros.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <a href="{{ route('admin.users.create') }}" class="btn btn-warning fw-bold text-dark px-4 shadow-sm" style="border-radius: 6px;">
            <i class="bi bi-person-plus-fill me-1"></i>
            Crear Nuevo Usuario
        </a>
    </div>
</div>

<!-- Usuarios Table Card -->
<div class="card border-0 shadow-sm fade-in mb-4" style="border-radius: 12px; overflow: hidden;">
    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <h6 class="mb-0 fw-bold text-secondary">Identidades del Sistema</h6>
        <form action="{{ route('admin.users.index') }}" method="GET" class="input-group" style="width: 250px;">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Buscar empleado..." style="font-size: 0.85rem;" value="{{ request('search') }}">
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 text-nowrap">
            <thead class="bg-light text-muted" style="font-size: 0.8rem;">
                <tr>
                    <th class="ps-4 fw-semibold text-uppercase">Nombre y Apellido</th>
                    <th class="fw-semibold text-uppercase">Email de Acceso</th>
                    <th class="fw-semibold text-uppercase">Rol</th>
                    <th class="fw-semibold text-uppercase">Estado</th>
                    <th class="fw-semibold text-uppercase">Creación</th>
                    <th class="text-end pe-4 fw-semibold text-uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody style="font-size: 0.9rem;">
                @forelse($users as $user)
                <tr>
                    <td class="ps-4 fw-medium text-dark">{{ $user->nombre }}</td>
                    <td class="text-muted">{{ $user->email }}</td>
                    <td>
                        @if($user->role === 'admin')
                            <span class="badge bg-danger text-white">Administrador</span>
                        @else
                            <span class="badge bg-primary text-white">Cajero POS</span>
                        @endif
                    </td>
                    <td>
                        @if($user->status === 'active')
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Activo</span>
                        @else
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Inactivo</span>
                        @endif
                    </td>
                    <td class="text-muted font-monospace">{{ $user->created_at->format('d/m/Y') }}</td>
                    <td class="text-end pe-4">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary py-1 px-2 border-0" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline-block" onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2 border-0" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">No se encontraron usuarios registrados en la base de datos.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($users, 'links'))
<div class="d-flex justify-content-center mt-4">
    {{ $users->links() }}
</div>
@endif
@endsection`
  },
  {
    path: 'resources/views/admin/reportes.blade.php',
    title: 'Reportes y Ventas',
    description: 'Panel y tabla consolidada de comprobantes generados, estadísticas financieras del día. Exclusivo de administrador.',
    icon: 'bi-graph-up-arrows',
    code: `@extends('layouts.app')

@section('title', 'Reportes Contables y Ventas')

@section('content')
<div class="row align-items-center mb-4 fade-in">
    <div class="col-md-6">
        <h4 class="fw-bold mb-0 text-dark">
            <i class="bi bi-graph-up-arrows text-warning me-2"></i>
            Reportes Contables y Ventas
        </h4>
        <p class="text-secondary small mb-0 mt-1">Historial consolidado de comprobantes emitidos y resumen financiero.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end">
        <button class="btn btn-outline-secondary fw-bold bg-white shadow-sm" style="border-radius: 6px;">
            <i class="bi bi-calendar3 me-2"></i> Hoy
        </button>
        <a href="{{ route('admin.reports.export') }}" class="btn btn-dark fw-bold text-white shadow-sm" style="border-radius: 6px;">
            <i class="bi bi-file-earmark-excel-fill me-2 text-success"></i> Exportar a Excel
        </a>
    </div>
</div>

<!-- Resumen Estadistico de Tarjetas -->
<div class="row g-3 mb-4 fade-in" style="animation-delay: 0.1s;">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 12px;">
            <div class="d-flex align-items-center mb-2 text-muted fw-semibold">
                <i class="bi bi-cash-stack me-2 fs-5 text-success"></i> Ingresos del Día
            </div>
            <h3 class="fw-bold text-dark mb-0">$<span class="font-monospace">{{ number_format($dailyTotal ?? 0, 2) }}</span></h3>
            <p class="small text-success mb-0 mt-1"><i class="bi bi-arrow-up-right me-1"></i> +12% sobre ayer</p>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 12px;">
            <div class="d-flex align-items-center mb-2 text-muted fw-semibold">
                <i class="bi bi-receipt me-2 fs-5 text-primary"></i> Boletas Emitidas
            </div>
            <h3 class="fw-bold text-dark mb-0"><span class="font-monospace">{{ $invoicesCount ?? 0 }}</span></h3>
            <p class="small text-muted mb-0 mt-1">Transacciones procesadas hoy</p>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100 p-4" style="border-radius: 12px;">
            <div class="d-flex align-items-center mb-2 text-muted fw-semibold">
                <i class="bi bi-box-seam me-2 fs-5 text-warning"></i> Ticket Promedio
            </div>
            <h3 class="fw-bold text-dark mb-0">$<span class="font-monospace">{{ number_format($averageTicket ?? 0, 2) }}</span></h3>
            <p class="small text-muted mb-0 mt-1">Por cada cobro en POS</p>
        </div>
    </div>
</div>

<!-- Listado de Transacciones Restantes -->
<div class="card border-0 shadow-sm fade-in mb-4" style="border-radius: 12px; overflow: hidden; animation-delay: 0.2s;">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-secondary">Últimas Transacciones Registradas</h6>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 text-nowrap">
            <thead class="bg-light text-muted" style="font-size: 0.8rem;">
                <tr>
                    <th class="ps-4 fw-semibold text-uppercase">Nº Recibo</th>
                    <th class="fw-semibold text-uppercase">Fecha y Hora</th>
                    <th class="fw-semibold text-uppercase">Atendido Por</th>
                    <th class="fw-semibold text-uppercase text-center">Ítems</th>
                    <th class="fw-semibold text-uppercase">Subtotal</th>
                    <th class="fw-semibold text-uppercase text-end pe-4">Total</th>
                </tr>
            </thead>
            <tbody style="font-size: 0.9rem;">
                @forelse($transactions as $tx)
                <tr>
                    <td class="ps-4 fw-bold text-dark font-monospace">{{ $tx->numero_factura }}</td>
                    <td class="text-secondary font-monospace">{{ $tx->created_at->format('d/m/Y - H:i') }}</td>
                    <td>
                        <span class="badge bg-light text-dark border"><i class="bi bi-person-fill text-muted me-1"></i> {{ $tx->user->nombre ?? 'Cajero' }}</span>
                    </td>
                    <td class="text-center font-monospace bg-light text-secondary rounded">{{ $tx->detalles_count ?? 0 }}</td>
                    <td class="font-monospace text-muted">\${{ number_format($tx->subtotal, 2) }}</td>
                    <td class="text-end pe-4 font-monospace fw-bold text-success">\${{ number_format($tx->total, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">No existen transacciones recientes.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($transactions, 'links'))
<div class="d-flex justify-content-center mt-4">
    {{ $transactions->links() }}
</div>
@endif
@endsection`
  },
  {
    path: 'resources/views/layouts/app.blade.php',
    title: 'Layout Principal',
    description: 'Estructura base del panel administrativo con Sidebar fijo colapsable, Navbar superior con perfiles y roles (admin vs cajero), pie de página y scripts de Bootstrap 5.',
    icon: 'bi-window-sidebar',
    code: `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
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
    <aside class="sidebar id="sidebarMenu"">
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
            <a href="{{ route('inventario.index') }}" class="sidebar-link {{ request()->routeIs('inventario.*') ? 'active' : '' }}">
                <i class="bi bi-boxes fs-5"></i>
                <span>Gestión Inventario</span>
            </a>

            <!-- Módulo de Ventas / POS (Punto de Venta) -->
            <a href="{{ route('ventas.index') }}" class="sidebar-link {{ request()->routeIs('ventas.*') ? 'active' : '' }}">
                <i class="bi bi-cart4 fs-5"></i>
                <span>Punto de Venta</span>
                <span class="ms-auto badge-pos font-monospace">POS</span>
            </a>

            @if(auth()->user() && auth()->user()->role === 'admin')
            <div class="px-3 mt-4 mb-2">
                <small class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.65rem; letter-spacing: 0.05em;">ADMINISTRACIÓN</small>
            </div>
            
            <a href="#" class="sidebar-link">
                <i class="bi bi-people fs-5"></i>
                <span>Gestionar Usuarios</span>
            </a>
            
            <a href="#" class="sidebar-link">
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
                    <button type="submit" class="btn btn-outline-danger w-full d-flex align-items-center justify-content-center gap-2 py-2 w-100">
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
                <form class="d-none d-sm-flex flex-grow-1 max-w-lg" style="max-width: 350px;">
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
                            {{ auth()->user()->nombre ?? 'Administrador Tornillo' }}
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
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-success border-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 border-start border-danger border-4" role="alert">
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
                <span class="d-none d-md-inline text-muted fs-7">Hecho para Proyecto de Maquetación Final <i class="bi bi-heart-fill text-danger text-xs"></i></span>
            </div>
        </footer>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>`,
  },
  {
    path: 'resources/views/home.blade.php',
    title: 'Landing Page (Pública)',
    description: 'Página de inicio comercial orientada al público con banner moderno, catálogo ilustrado, accesos directos de servicio y botón llamativo para acceder al sistema.',
    icon: 'bi-house-heart',
    code: `@extends('layouts.frontend') <!-- Nota: o se puede usar una vista limpia usando bootstrap directamente -->

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferretería El Tornillo - Los mejores precios y calidad en construcción</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #fafbfc;
        }
        .hero-banner {
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
            color: #fff;
            padding: 8rem 0 6rem 0;
            position: relative;
            overflow: hidden;
            border-bottom: 5px solid #f97316;
        }
        .hero-banner::after {
            content: '';
            position: absolute;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.15) 0%, transparent 60%);
            width: 600px;
            height: 600px;
            top: -200px;
            right: -100px;
            border-radius: 50%;
        }
        .feature-card {
            border: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            background: white;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important;
        }
        .btn-orange {
            background-color: #f97316;
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-orange:hover {
            background-color: #ea580c;
            color: white;
            transform: translateY(-1px);
        }
        .navbar-brand-custom {
            font-weight: 800;
            color: #1e3a8a !important;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>

    <!-- Header Navbar Público -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand navbar-brand-custom" href="#">
                <i class="bi bi-nut-fill text-warning fs-3 animate-spin"></i>
                <span>FERRETERÍA EL TORNILLO</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="publicNav">
                <ul class="navbar-nav ms-auto align-items-center gap-3">
                    <li class="nav-item">
                        <a class="nav-link active fw-medium" href="#">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="#productos">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="#sucursales">¿Dónde Estamos?</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('login') }}" class="btn btn-outline-primary fw-bold px-4 py-2" style="border-radius: 8px;">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar al Sistema
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Banner Sección Principal -->
    <header class="hero-banner">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 text-center text-lg-start">
                    <span class="badge bg-warning text-dark px-3 py-2 fs-7 fw-bold mb-3 text-uppercase">¡Precios directos de fábrica!</span>
                    <h1 class="display-3 fw-extrabold text-white mb-3" style="letter-spacing: -0.02em; font-weight: 800;">
                        Herramientas que <span style="color: #f97316;">construyen</span> tu futuro
                    </h1>
                    <p class="lead text-gray-300 fs-5 mb-4" style="color: #cbd5e1; max-width: 600px;">
                        Desde el clavo más pequeño hasta maquinaria industrial pesada. Proveemos calidad garantizada para albañiles, ingenieros y amantes del bricolaje.
                    </p>
                    <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                        <a href="{{ route('login') }}" class="btn btn-orange text-uppercase shadow-lg d-flex align-items-center gap-2 fs-6">
                            <span>Ingresar al Sistema Administrativo</span>
                            <i class="bi bi-arrow-right-short fs-4"></i>
                        </a>
                        <a href="#productos" class="btn btn-outline-light text-uppercase px-4 py-2" style="border-radius: 8px; font-weight: 600;">
                            Ver Catálogo
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block text-center position-relative">
                    <div class="bg-warning position-absolute rounded-3" style="width: 100px; height: 100px; opacity: 0.1; top: -10px; left: 10%;"></div>
                    <i class="bi bi-tools text-warning" style="font-size: 14rem; opacity: 0.95; filter: drop-shadow(0 15px 30px rgba(0,0,0,0.5));"></i>
                </div>
            </div>
        </div>
    </header>

    <!-- Características Rápidas -->
    <section class="py-5" id="productos">
        <div class="container">
            <div class="text-center mb-5">
                <h6 class="text-orange text-uppercase fw-bold" style="color:#f97316;">Nuestros Servicios</h6>
                <h2 class="fw-bold text-dark mb-2">¿Por qué preferir Ferretería El Tornillo?</h2>
                <p class="text-muted" style="max-width: 600px; margin: 0 auto;">Líderes locales con más de 15 años de experiencia entregando materiales para obras emblemáticas.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4 shadow-sm">
                        <div class="rounded-3 bg-light text-primary d-inline-flex align-items-center justify-content-center mb-4" style="width: 55px; height: 55px;">
                            <i class="bi bi-activity fs-3 text-primary"></i>
                        </div>
                        <h4 class="fw-bold text-dark">Variedad de Stock</h4>
                        <p class="text-muted">Más de 5,000 artículos en pernos, tornillos, cementos, herramientas manuales y eléctricas de marcas premium.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4 shadow-sm">
                        <div class="rounded-3 bg-light text-warning d-inline-flex align-items-center justify-content-center mb-4" style="width: 55px; height: 55px;">
                            <i class="bi bi-truck fs-3 text-warning"></i>
                        </div>
                        <h4 class="fw-bold text-dark">Entregas de Emergencia</h4>
                        <p class="text-muted">¿Tu obra no puede parar? Despachamos tus pedidos de materiales pesados directo a tu terreno de construcción.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4 shadow-sm">
                        <div class="rounded-3 bg-light text-success d-inline-flex align-items-center justify-content-center mb-4" style="width: 55px; height: 55px;">
                            <i class="bi bi-shield-check fs-3 text-success"></i>
                        </div>
                        <h4 class="fw-bold text-dark">Garantía Directa</h4>
                        <p class="text-muted">Ofrecemos cambio inmediato y soporte oficial para herramientas eléctricas de marcas de renombre.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Banner Acceso Directo Personal / Docente -->
    <section class="py-5 bg-dark text-white text-center position-relative">
        <div class="container py-3">
            <h3 class="fw-bold mb-3">Acceso Exclusivo para Personal Autorizado</h3>
            <p class="text-muted mb-4 mx-auto" style="max-width: 650px; color: #94a3b8 !important;">
                Este es un sistema integrado para personal administrativo de Inventario e Inteligencia de Caja (POS). Requiere autenticación con credenciales registradas.
            </p>
            <div class="d-flex justify-content-center gap-2">
                <a href="{{ route('login') }}" class="btn btn-warning px-5 py-3 fw-bold shadow-sm d-flex align-items-center gap-2 text-dark" style="border-radius: 8px;">
                    <i class="bi bi-lock-fill"></i>
                    <span>INGRESAR COMO OPERADOR / ADMIN</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Footer de la Landing -->
    <footer class="bg-white border-top py-4 text-center">
        <div class="container text-muted">
            <p class="mb-1">&copy; {{ date('Y') }} <strong>Ferretería El Tornillo S.A.</strong> Todos los derechos reservados.</p>
            <small>Proyecto de Maquetación Frontend Premium - Listo para Implementar</small>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>`,
  },
  {
    path: 'resources/views/auth/login.blade.php',
    title: 'Iniciar Sesión',
    description: 'Pantalla de autenticación moderna y centrada con tarjetas de sombreado, fondo temático abstracto de herramientas, campo de correo, contraseña, selección visual de roles predefinidos y validaciones.',
    icon: 'bi-shield-lock',
    code: `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Ferretería El Tornillo</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body {
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            padding: 1.5rem;
        }
        .login-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            background-color: #ffffff;
            overflow: hidden;
            max-width: 440px;
            width: 100%;
        }
        .login-header {
            background-color: #0f172a;
            color: #fff;
            padding: 2rem 2rem;
            text-align: center;
            border-bottom: 4px solid #f97316;
        }
        .form-control {
            box-shadow: none !important;
            border-color: #dee2e6;
        }
        .form-control:focus {
            border-color: #f97316;
        }
        .btn-orange {
            background: #f97316;
            color: #1e293b;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            padding: 0.8rem;
            width: 100%;
            transition: all 0.2s;
        }
        .btn-orange:hover {
            background: #ea580c;
            color: white;
            opacity: 0.95;
        }
        .badge-role {
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #cbd5e1;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
        }
        .badge-role:hover {
            border-color: #0f172a;
            background-color: #0f172a;
            color: white !important;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <!-- Encabezado de la Tarjeta con logo de Ferretería -->
        <div class="login-header">
            <div class="d-inline-flex bg-warning rounded-circle p-2 shadow-sm mb-2 text-dark" style="width: 60px; height: 60px; align-items: center; justify-content: center;">
                <i class="bi bi-nut-fill fs-2"></i>
            </div>
            <h4 class="fw-bold mb-0 text-white tracking-tight">Ferretería El Tornillo</h4>
            <small class="text-uppercase fw-semibold text-warning" style="font-size: 0.75rem; letter-spacing: 1px;">Ingreso Seguro</small>
        </div>

        <div class="card-body p-4 p-md-5">
            @if($errors->any())
                <div class="alert border border-danger bg-danger bg-opacity-10 text-danger shadow-sm py-2 px-3" style="font-size: 0.85rem;">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                
                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label text-secondary fw-semibold mb-1" style="font-size: 0.75rem;">CORREO ELECTRÓNICO</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" id="email" class="form-control bg-light border-start-0 ps-0" placeholder="ejemplo@ferreteria.com" value="{{ old('email') }}" required autofocus>
                    </div>
                </div>

                <!-- Contraseña -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label text-secondary fw-semibold mb-0" style="font-size: 0.75rem;">CONTRASEÑA</label>
                        <a href="#" class="text-decoration-none text-muted" style="font-size: 0.75rem;">¿Olvidaste tu clave?</a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control bg-light border-start-0 ps-0" placeholder="••••••••" required>
                    </div>
                </div>

                <!-- Recordarme y Sesión activa -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" name="remember" id="remember" class="form-check-input">
                        <label for="remember" class="form-check-label text-muted" style="font-size: 0.85rem;">Recordar sesión</label>
                    </div>
                </div>

                <!-- Botón de Ingreso Principal -->
                <button type="submit" class="btn btn-orange d-flex align-items-center justify-content-center gap-2 mb-3 shadow-sm">
                    <i class="bi bi-box-arrow-in-right fs-5"></i>
                    <span>INGRESAR AL SISTEMA</span>
                </button>
            </form>

            <!-- Acceso Rápido para Roles -->
            <div class="border-top pt-4 text-center mt-3">
                <span class="text-muted d-block mb-2" style="font-size: 0.75rem;">Acceso rápido de credenciales (Click):</span>
                <div class="d-flex justify-content-center gap-2">
                    <span class="badge-role d-flex align-items-center gap-1 text-dark" onclick="document.getElementById('email').value='admin@ferreteria.com'; document.getElementById('password').value='123456';">
                        <i class="bi bi-shield-lock-fill text-secondary"></i> Admin
                    </span>
                    <span class="badge-role d-flex align-items-center gap-1 text-dark" onclick="document.getElementById('email').value='cajero@ferreteria.com'; document.getElementById('password').value='123456';">
                        <i class="bi bi-cash-coin text-secondary"></i> Cajero
                    </span>
                </div>
            </div>
        </div>
    </div>

</body>
</html>`,
  },
  {
    path: 'resources/views/dashboard.blade.php',
    title: 'Panel Control',
    description: 'Dashboard administrativo principal que hereda el layout base. Incorpora bento-cards con estadísticas de ventas, stocks bajos, botones de acceso rápido a inventarios y simulación de transacciones del día.',
    icon: 'bi-grid-3x3-gap-fill',
    code: `@extends('layouts.app')

@section('title', 'Panel de Control - Ferretería El Tornillo')

@section('content')
<div class="container-fluid">
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h1 class="h3 fw-bold text-dark mb-0">¡Bienvenido de vuelta, {{ auth()->user()->nombre ?? 'Administrador' }}!</h1>
            <p class="text-muted">Aquí tienes un resumen general del movimiento técnico de hoy en la ferretería.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <span class="badge bg-white text-dark shadow-sm p-3 border rounded-3 font-monospace">
                <i class="bi bi-calendar3 text-warning me-2"></i>
                {{ date('d/M/Y - H:i') }}
            </span>
        </div>
    </div>

    <!-- Panel de Tarjetas de Indicadores Rápidos -->
    <div class="row g-4 mb-4">
        <!-- Venta del Día -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm border-start border-primary border-4 rounded-3">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted text-uppercase fw-bold text-xs" style="font-size: 0.75rem; letter-spacing: 0.05em;">Ventas del Día</span>
                        <div class="rounded-3 bg-primary bg-opacity-10 p-2 text-primary" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-currency-dollar fs-4"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-1 font-mono">$1,845.50</h3>
                    <small class="text-success fw-bold d-flex align-items-center gap-1">
                        <i class="bi bi-graph-up"></i>
                        <span>+12.5% respecto a ayer</span>
                    </small>
                </div>
            </div>
        </div>

        <!-- Productos Registrados -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm border-start border-warning border-4 rounded-3">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted text-uppercase fw-bold text-xs" style="font-size: 0.75rem; letter-spacing: 0.05em;">Total Productos</span>
                        <div class="rounded-3 bg-warning bg-opacity-10 p-2 text-warning" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-boxes fs-4"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-1 font-mono">1,240</h3>
                    <small class="text-muted d-block">Dividido en 8 categorías</small>
                </div>
            </div>
        </div>

        <!-- Alerta de Stocks Bajos -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm border-start border-danger border-4 rounded-3">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted text-uppercase fw-bold text-xs" style="font-size: 0.75rem; letter-spacing: 0.05em;">Stock Crítico</span>
                        <div class="rounded-3 bg-danger bg-opacity-10 p-2 text-danger" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-exclamation-triangle fs-4"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-danger mb-1 font-mono">4 Artículos</h3>
                    <small class="text-danger fw-bold d-block">Requiere reposición inmediata</small>
                </div>
            </div>
        </div>

        <!-- Transacciones del Día -->
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm border-start border-success border-4 rounded-3">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted text-uppercase fw-bold text-xs" style="font-size: 0.75rem; letter-spacing: 0.05em;">Órdenes del Día</span>
                        <div class="rounded-3 bg-success bg-opacity-10 p-2 text-success" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-receipt-cutoff fs-4"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-1 font-mono">34 Transacciones</h3>
                    <small class="text-muted d-block">Promedio $54.20 por Boleta</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Menú de Accesos Rápidos (POS e Inventario) -->
    <div class="row g-4 mb-4">
        <!-- Tarjeta Izquierda: Acceso Rápido a Venta POS -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 bg-primary text-white position-relative overflow-hidden rounded-3">
                <div class="position-absolute" style="right: -20px; bottom: -20px; opacity: 0.15;">
                    <i class="bi bi-cart4" style="font-size: 11rem;"></i>
                </div>
                <div class="card-body p-0">
                    <span class="badge bg-warning text-dark text-uppercase mb-2 fw-bold">Módulo Transaccional</span>
                    <h4 class="fw-bold mb-2">Punto de Venta Integrado</h4>
                    <p class="text-light mb-4" style="max-width: 440px; opacity: 0.9;">
                        Apertura directa al carrito interactivo de facturación. Ideal para cajeros y personal de mostrador. Carga productos con un clic, calcula impuestos y emite tickets.
                    </p>
                    <a href="{{ route('ventas.index') }}" class="btn btn-warning fw-bold px-4 py-2">
                        <i class="bi bi-calculator me-2"></i>Ir a Vender (POS)
                    </a>
                </div>
            </div>
        </div>

        <!-- Tarjeta Derecha: Acceso Rápido a Inventario -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white position-relative overflow-hidden rounded-3">
                <div class="position-absolute" style="right: -20px; bottom: -20px; opacity: 0.15;">
                    <i class="bi bi-boxes" style="font-size: 11rem;"></i>
                </div>
                <div class="card-body p-0">
                    <span class="badge bg-orange text-white text-uppercase mb-2 fw-bold" style="background-color: #f97316;">Bodega y Auditoría</span>
                    <h4 class="fw-bold mb-2">Administrar Artículos y Precios</h4>
                    <p class="text-muted mb-4" style="max-width: 440px; color: #cbd5e1 !important; opacity: 0.9;">
                        Inspecciona stock de clavería, cementos, herramientas. Actualiza precios, genera nuevos registros con códigos SKU, y saca estimaciones de almacenamiento real.
                    </p>
                    <a href="{{ route('inventario.index') }}" class="btn btn-outline-light fw-bold px-4 py-2">
                        <i class="bi bi-eye-fill me-2"></i>Ir al Inventario
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Últimas Ventas en Tiempo Real -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Últimas Ventas Realizadas</h5>
                        <small class="text-muted">Actividad registrada del turno actual de trabajo</small>
                    </div>
                    <span class="badge bg-light text-dark text-uppercase p-2">Turno Abierto</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-uppercase text-muted" style="font-size: 0.75rem;">
                                    <th class="px-4 py-3">Código Factura</th>
                                    <th class="py-3">Cliente / RUC</th>
                                    <th class="py-3">Fecha y Hora</th>
                                    <th class="py-3">Cajero responsable</th>
                                    <th class="py-3 text-center">Método Pago</th>
                                    <th class="py-3 text-end px-4">Monto Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="px-4 py-3 font-mono fw-bold text-primary">FAC-2026-001</td>
                                    <td>
                                        <div class="fw-semibold">Juan Pérez Meléndez</div>
                                        <small class="text-muted">DNI: 0918237123</small>
                                    </td>
                                    <td>11/06/2026 - 13:45</td>
                                    <td>
                                        <span class="badge bg-light text-secondary">Caj. Carlos</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success border border-success border-opacity-10 py-1 px-2.5">Efectivo</span>
                                    </td>
                                    <td class="text-end px-4 font-mono fw-bold text-dark">$142.50</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-mono fw-bold text-primary">FAC-2026-002</td>
                                    <td>
                                        <div class="fw-semibold">Constructora Alpha S.A.</div>
                                        <small class="text-muted">RUC: 1792384710001</small>
                                    </td>
                                    <td>11/06/2026 - 12:20</td>
                                    <td>
                                        <span class="badge bg-light text-secondary">Admin Yahir</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-10 py-1 px-2.5">Transferencia</span>
                                    </td>
                                    <td class="text-end px-4 font-mono fw-bold text-dark">$850.00</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-mono fw-bold text-primary">FAC-2026-003</td>
                                    <td>
                                        <div class="fw-semibold">Marta Sánchez G.</div>
                                        <small class="text-muted">DNI: 1204859102</small>
                                    </td>
                                    <td>11/06/2026 - 10:15</td>
                                    <td>
                                        <span class="badge bg-light text-secondary">Caj. Carlos</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info border border-info border-opacity-10 py-1 px-2.5">Tarjeta Débito</span>
                                    </td>
                                    <td class="text-end px-4 font-mono fw-bold text-dark">$45.80</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection`,
  },
  {
    path: 'resources/views/inventario/index.blade.php',
    title: 'Lista de Productos (Inventario)',
    description: 'Vista tabular del catálogo de ferretería. Soporta barra de filtros, indicadores visuales de quiebre de stock, y botones elegantes de exportación rápida a formatos Excel y PDF.',
    icon: 'bi-list-ul',
    code: `@extends('layouts.app')

@section('title', 'Control de Inventario - Ferretería El Tornillo')

@section('content')
<div class="container-fluid">
    <!-- Fila del Encabezado con Botones de Acción -->
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-6">
            <h1 class="h3 fw-bold text-dark mb-0">Inventario General de Bodega</h1>
            <p class="text-muted">Monitorea niveles de stock, marcas de cemento, tornillos de acero y herramientas del local.</p>
        </div>
        <div class="col-md-6 text-md-end d-flex flex-wrap gap-2 justify-content-md-end">
            <!-- Botones de Exportación UI (Sin lógica compleja) -->
            <button class="btn btn-outline-danger fw-semibold d-flex align-items-center gap-2" onclick="alert('Exportando listado de inventario a PDF...')">
                <i class="bi bi-filetype-pdf fs-5 text-danger"></i>
                <span>Exportar PDF</span>
            </button>
            <button class="btn btn-outline-success fw-semibold d-flex align-items-center gap-2" onclick="alert('Exportando catálogo de stock a plantilla Excel...')">
                <i class="bi bi-filetype-xlsx fs-5 text-success"></i>
                <span>Exportar Excel</span>
            </button>

            <!-- Registrar Producto Nuevo -->
            <a href="{{ route('inventario.create') }}" class="btn btn-primary fw-bold d-flex align-items-center gap-2">
                <i class="bi bi-plus-circle"></i>
                <span>Nuevo Producto</span>
            </a>
        </div>
    </div>

    <!-- Filtros de Búsqueda y Selección -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form action="{{ route('inventario.index') }}" method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label text-muted fw-bold small">BUSCAR PRODUCTO</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Escribe el nombre, código SKU o material..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fw-bold small">CATEGORÍA</label>
                    <select name="categoria" class="form-select">
                        <option value="">Todas las Familias</option>
                        <option value="Herramientas Manuales">Herramientas Manuales</option>
                        <option value="Herramientas Eléctricas">Herramientas Eléctricas</option>
                        <option value="Fijaciones y Tornillos">Fijaciones y Tornillos</option>
                        <option value="Construcción y Áridos">Construcción y Áridos</option>
                        <option value="Plomería">Plomería</option>
                        <option value="Pinturas">Pinturas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted fw-bold small">ESTADO FILTRO</label>
                    <select name="status" class="form-select">
                        <option value="">Todos los Estados</option>
                        <option value="disponible">Suficiente/Disponible</option>
                        <option value="critico">Bajo Stock</option>
                        <option value="sin_stock">Agotado (Sin Stock)</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-dark w-100 py-2 fw-semibold">
                        <i class="bi bi-funnel me-1"></i> Filtrar Stock
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Base de Datos -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-dark">
                    <thead class="table-light">
                        <tr class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                            <th class="px-4 py-3">Código SKU</th>
                            <th class="py-3">Nombre del Material</th>
                            <th class="py-3 text-center">Categoría</th>
                            <th class="py-3 text-end">Precio Compra/Venta</th>
                            <th class="py-3 text-center">Stock Físico</th>
                            <th class="py-3 text-center">Estado Alerta</th>
                            <th class="py-3 text-end px-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Fila 1: Stock Normal -->
                        <tr>
                            <td class="px-4 py-3 font-mono fw-bold">SKU-TORN-009</td>
                            <td>
                                <div class="fw-bold text-dark">Tornillo Drywall 1x6 (100un)</div>
                                <small class="text-muted">Fijación de paneles de yeso y madera suave</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border py-1.5 px-2.5">Fijaciones y Tornillos</span>
                            </td>
                            <td class="text-end">
                                <div class="fw-bold">$4.50</div>
                                <small class="text-muted" style="font-size: 0.75rem;">Sugerido: $6.00</small>
                            </td>
                            <td class="text-center fw-bold font-mono">140 Unidades</td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success border border-success border-opacity-10 py-1.5 px-3">
                                    <i class="bi bi-check-circle me-1"></i> Disponible
                                </span>
                            </td>
                            <td class="text-end px-4">
                                <div class="btn-group">
                                    <a href="{{ route('inventario.edit', 1) }}" class="btn btn-sm btn-outline-secondary" title="Editar ficha de producto">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirm('¿Estás seguro de eliminar el SKU-TORN-009?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Fila 2: Cemento Stock Critico -->
                        <tr>
                            <td class="px-4 py-3 font-mono fw-bold">SKU-CEME-512</td>
                            <td>
                                <div class="fw-bold text-dark">Cemento Gris Portlat de 50Kg</div>
                                <small class="text-muted">Material básico de alta resistencia estructural - Holcim</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border py-1.5 px-2.5">Construcción y Áridos</span>
                            </td>
                            <td class="text-end">
                                <div class="fw-bold">$9.20</div>
                                <small class="text-muted" style="font-size: 0.75rem;">Sugerido: $11.50</small>
                            </td>
                            <td class="text-center fw-bold font-mono text-warning">8 Bolsas</td>
                            <td class="text-center">
                                <span class="badge bg-warning-subtle text-warning border border-warning border-opacity-15 py-1.5 px-3">
                                    <i class="bi bi-exclamation-triangle me-1"></i> Stock Critico
                                </span>
                            </td>
                            <td class="text-end px-4">
                                <div class="btn-group">
                                    <a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil-square"></i></a>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>

                        <!-- Fila 3: Cero Stock - Badge Rojo Requerido -->
                        <tr>
                            <td class="px-4 py-3 font-mono fw-bold text-danger">SKU-ELEC-401</td>
                            <td>
                                <div class="fw-bold text-dark text-decoration-line-through">Martillo Demoledor Bosch 1100W</div>
                                <small class="text-muted text-danger">Falta entrega del distribuidor alemán autorizado</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border py-1.5 px-2.5">Herramientas Eléctricas</span>
                            </td>
                            <td class="text-end">
                                <div class="fw-bold">$345.00</div>
                                <small class="text-muted" style="font-size: 0.75rem;">Sugerido: $420.00</small>
                            </td>
                            <td class="text-center fw-bold font-mono text-danger">0 Unidades</td>
                            <td class="text-center">
                                <!-- REGLA VISUAL DE STOCK 0 - ETIQUETA ROJA OBLIGATORIA -->
                                <span class="badge bg-danger text-white py-1.5 px-3 animate-pulse">
                                    <i class="bi bi-shield-slash-fill me-1"></i> Sin Stock
                                </span>
                            </td>
                            <td class="text-end px-4">
                                <div class="btn-group">
                                    <a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil-square"></i></a>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>

                        <!-- Fila 4: Alambre Acero disponible -->
                        <tr>
                            <td class="px-4 py-3 font-mono fw-bold">SKU-ALAM-103</td>
                            <td>
                                <div class="fw-bold text-dark">Rollo de Alambre Recocido N.18</div>
                                <small class="text-muted">Amarras estribos metálicos gruesos</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border py-1.5 px-2.5">Fijaciones y Tornillos</span>
                            </td>
                            <td class="text-end">
                                <div class="fw-bold">$1.80</div>
                                <small class="text-muted" style="font-size: 0.75rem;">Sugerido: $2.40</small>
                            </td>
                            <td class="text-center fw-bold font-mono">75 Rollos</td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success border border-success border-opacity-10 py-1.5 px-3">
                                    <i class="bi bi-check-circle me-1"></i> Disponible
                                </span>
                            </td>
                            <td class="text-end px-4">
                                <div class="btn-group">
                                    <a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil-square"></i></a>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Navegación de Páginas Maqueta -->
            <div class="card-footer bg-white border-top py-3 d-flex align-items-center justify-content-between">
                <span class="text-muted small">Mostrando <strong>1</strong> a <strong>4</strong> de <strong>4</strong> registros de materiales</span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item disabled">
                            <span class="page-link">Anterior</span>
                        </li>
                        <li class="page-item active" aria-current="page">
                            <span class="page-link">1</span>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="#">2</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="#">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>
@endsection`,
  },
  {
    path: 'resources/views/inventario/form.blade.php',
    title: 'Formulario de Productos',
    description: 'Formulario LaravelBlade robusto para creación y edición de existencias. Incluye alertas de validación, campos numéricos y una interfaz limpia de guardado.',
    icon: 'bi-file-earmark-plus-fill',
    code: `@extends('layouts.app')

@section('title', isset($producto) ? 'Editar Producto - Ferretería El Tornillo' : 'Nuevo Producto - Ferretería El Tornillo')

@section('content')
<div class="container-fluid" style="max-width: 900px; margin: 0 auto;">
    
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary px-3" style="border-radius: 8px;">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="h3 fw-bold text-dark mb-0">
            {{ isset($producto) ? 'Editar Producto Existente' : 'Ingresar Nuevo Producto a Bodega' }}
        </h1>
    </div>

    <!-- Bloque de Formulario Integrado -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="fw-bold mb-0 text-primary">
                <i class="bi bi-clipboard-data-fill me-2 text-warning"></i>
                Ficha Técnica de Material e Inventario
            </h5>
            <small class="text-muted">Asegúrese de rellenar los datos de SKU para evitar duplicados en el Punto de Venta.</small>
        </div>
        <div class="card-body p-4 p-md-5">
            
            <form action="{{ isset($producto) ? route('inventario.update', $producto->id) : route('inventario.store') }}" method="POST">
                @csrf
                @if(isset($producto))
                    @method('PUT')
                @endif

                <div class="row g-4">
                    <!-- Código SKU -->
                    <div class="col-md-6">
                        <label for="sku" class="form-label text-dark fw-semibold">Código SKU / Referencia <span class="text-danger">*</span></label>
                        <input type="text" name="sku" id="sku" class="form-control font-mono @error('sku') is-invalid @enderror" 
                               placeholder="Ej: SKU-CEME-123" value="{{ old('sku', $producto->sku ?? '') }}" required>
                        <small class="text-muted">Código único para lectores de barras o búsqueda rápida.</small>
                        @error('sku')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Categoría del Material -->
                    <div class="col-md-6">
                        <label for="category" class="form-label text-dark fw-semibold">Categoría del Material <span class="text-danger">*</span></label>
                        <select name="categoria" id="category" class="form-select @error('categoria') is-invalid @enderror" required>
                            <option value="">Seleccione una Categoría</option>
                            <option value="Fijaciones y Tornillos" {{ old('categoria', $producto->categoria ?? '') === 'Fijaciones y Tornillos' ? 'selected' : '' }}>Fijaciones y Tornillos</option>
                            <option value="Herramientas Manuales" {{ old('categoria', $producto->categoria ?? '') === 'Herramientas Manuales' ? 'selected' : '' }}>Herramientas Manuales</option>
                            <option value="Herramientas Eléctricas" {{ old('categoria', $producto->categoria ?? '') === 'Herramientas Eléctricas' ? 'selected' : '' }}>Herramientas Eléctricas</option>
                            <option value="Construcción y Áridos" {{ old('categoria', $producto->categoria ?? '') === 'Construcción y Áridos' ? 'selected' : '' }}>Construcción y Áridos</option>
                            <option value="Plomería e Hidráulica" {{ old('categoria', $producto->categoria ?? '') === 'Plomería e Hidráulica' ? 'selected' : '' }}>Plomería e Hidráulica</option>
                            <option value="Pinturas" {{ old('categoria', $producto->categoria ?? '') === 'Pinturas' ? 'selected' : '' }}>Pinturas</option>
                        </select>
                        @error('categoria')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Nombre del Producto -->
                    <div class="col-12">
                        <label for="name" class="form-label text-dark fw-semibold">Nombre del Material o Herramienta <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="name" class="form-control @error('nombre') is-invalid @enderror" 
                               placeholder="Ej: Clavos de Acero 3 Pulgadas (Bolsa de 1Kg)" value="{{ old('nombre', $producto->nombre ?? '') }}" required>
                        @error('nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Descripción técnica -->
                    <div class="col-12">
                        <label for="description" class="form-label text-dark fw-semibold">Descripción del Producto</label>
                        <textarea name="descripcion" id="description" rows="3" class="form-control" 
                                  placeholder="Indique dimensiones, marca, peso u otros factores técnicos de almacenamiento (Ej: Acero galvanizado antioxidante, marca Stanley)...">{{ old('descripcion', $producto->descripcion ?? '') }}</textarea>
                    </div>

                    <!-- Precios -->
                    <div class="col-md-4">
                        <label for="price" class="form-label text-dark fw-semibold">Precio de Compra sugerido <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-currency-dollar"></i></span>
                            <input type="number" step="0.01" name="precio" id="price" class="form-control font-mono @error('precio') is-invalid @enderror" 
                                   placeholder="0.00" value="{{ old('precio', $producto->precio ?? '') }}" required>
                        </div>
                        @error('precio')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Stock Físico -->
                    <div class="col-md-4">
                        <label for="stock" class="form-label text-dark fw-semibold">Stock Inicial en Bodega <span class="text-danger">*</span></label>
                        <input type="number" name="stock" id="stock" class="form-control font-mono @error('stock') is-invalid @enderror" 
                               placeholder="Ej: 50" value="{{ old('stock', $producto->stock ?? '') }}" required min="0">
                        @error('stock')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Unidad de Medida -->
                    <div class="col-md-4">
                        <label for="unit" class="form-label text-dark fw-semibold">Unidad de Medida <span class="text-danger">*</span></label>
                        <select name="unidad" id="unit" class="form-select" required>
                            <option value="Unidades" {{ old('unidad', $producto->unidad ?? '') === 'Unidades' ? 'selected' : '' }}>Unidades (Un)</option>
                            <option value="Bolsas" {{ old('unidad', $producto->unidad ?? '') === 'Bolsas' ? 'selected' : '' }}>Bolsas (Saco 50Kg)</option>
                            <option value="Rollos" {{ old('unidad', $producto->unidad ?? '') === 'Rollos' ? 'selected' : '' }}>Rollos (Mts)</option>
                            <option value="Cajas" {{ old('unidad', $producto->unidad ?? '') === 'Cajas' ? 'selected' : '' }}>Cajas (Cj)</option>
                            <option value="Kilos" {{ old('unidad', $producto->unidad ?? '') === 'Kilos' ? 'selected' : '' }}>Kilos (Kg)</option>
                        </select>
                    </div>
                </div>

                <!-- Botones del Formulario -->
                <div class="border-top pt-4 mt-5 d-flex justify-content-end gap-2">
                    <a href="{{ route('inventario.index') }}" class="btn btn-light px-4 py-2 text-dark font-medium border" style="border-radius: 8px;">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-warning px-4 py-2 fw-bold text-dark shadow-sm d-flex align-items-center gap-2" style="border-radius: 8px;">
                        <i class="bi bi-save-fill"></i>
                        <span>{{ isset($producto) ? 'Guardar Cambios de Ficha' : 'Guardar Nuevo Producto' }}</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection`,
  },
  {
    path: 'resources/views/ventas/index.blade.php',
    title: 'Punto de Venta (POS)',
    description: 'Sistema interactivo de facturación express para el cajero. Ofrece un buscador de productos a la izquierda para cargar al carrito y un resumen del total con impuestos (IVA 12%) a la derecha con boleta imprimible.',
    icon: 'bi-cart-check-fill',
    code: `@extends('layouts.app')

@section('title', 'Punto de Venta POS - Ferretería El Tornillo')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h4 fw-bold text-dark mb-1"><i class="bi bi-printer-fill me-2 text-primary"></i>Caja Registradora POS</h1>
            <p class="text-muted mb-0">Agrega productos haciendo clic en el catálogo para generar un ticket de entrega rápida.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Panel Izquierdo: Catálogo de Productos Disponibles -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-tags-fill me-2 text-warning"></i>Catálogo de Materiales</h5>
                    <span class="badge bg-primary">Venta Express</span>
                </div>
                
                <div class="card-body p-4">
                    <!-- Buscador Rápido del POS -->
                    <div class="input-group mb-4">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="posSearch" class="form-control border-start-0 bg-light" placeholder="Filtra por nombre, categoría o medidas de clavo...">
                    </div>

                    <!-- Grid de Materiales -->
                    <div class="row g-3" style="max-height: 480px; overflow-y: auto;">
                        <!-- Item 1 -->
                        <div class="col-md-6 pos-item-card">
                            <div class="card h-100 border border-opacity-10 shadow-none hover-shadow transition p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-primary bg-opacity-10 text-primary">Tornillos</span>
                                    <span class="font-mono text-muted text-xs" style="font-size:0.75rem;">SKU-009</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Tornillo Drywall 1x6 (100un)</h6>
                                <p class="text-muted small mb-3">Stock: 140 Unidades</p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="fw-bold fs-5 text-dark font-mono">$4.50</span>
                                    <button class="btn btn-sm btn-warning fw-bold px-3 py-1 text-dark" onclick="addProductToCart(1, 'Tornillo Drywall 1x6 (100un)', 4.50, 140)">
                                        <i class="bi bi-plus-lg"></i> Agregar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Item 2 -->
                        <div class="col-md-6 pos-item-card">
                            <div class="card h-100 border border-opacity-10 shadow-none hover-shadow transition p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-primary bg-opacity-10 text-primary">Cemento</span>
                                    <span class="font-mono text-muted text-xs" style="font-size:0.75rem;">SKU-512</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Cemento Gris Portland 50Kg</h6>
                                <p class="text-muted small mb-2">Stock: 8 Sacks</p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="fw-bold fs-5 text-dark font-mono">$9.20</span>
                                    <button class="btn btn-sm btn-warning fw-bold px-3 py-1 text-dark" onclick="addProductToCart(2, 'Cemento Gris Portland 50Kg', 9.20, 8)">
                                        <i class="bi bi-plus-lg"></i> Agregar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Item 3 (Sin stock, botón deshabilitado) -->
                        <div class="col-md-6 pos-item-card opacity-75">
                            <div class="card h-100 bg-light border border-opacity-10 shadow-none p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-danger text-white">Agotado</span>
                                    <span class="font-mono text-muted text-xs" style="font-size:0.75rem;">SKU-401</span>
                                </div>
                                <h6 class="fw-bold text-dark text-decoration-line-through mb-1">Martillo Bosch 1100W</h6>
                                <p class="text-danger small mb-2 fw-medium">Stock: 0 Unidades</p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="fw-bold fs-5 text-muted font-mono">$345.00</span>
                                    <button class="btn btn-sm btn-secondary disabled px-3 py-1 text-white">
                                        <i class="bi bi-slash-circle"></i> Agotado
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Item 4 (Alambre Recocido) -->
                        <div class="col-md-6 pos-item-card">
                            <div class="card h-100 border border-opacity-10 shadow-none hover-shadow transition p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-primary bg-opacity-10 text-primary">Acopladores</span>
                                    <span class="font-mono text-muted text-xs" style="font-size:0.75rem;">SKU-103</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Rollo Alambre Recocido N.18</h6>
                                <p class="text-muted small mb-2">Stock: 75 Rollos</p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="fw-bold fs-5 text-dark font-mono">$1.80</span>
                                    <button class="btn btn-sm btn-warning fw-bold px-3 py-1 text-dark" onclick="addProductToCart(4, 'Rollo Alambre Recocido N.18', 1.80, 75)">
                                        <i class="bi bi-plus-lg"></i> Agregar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Derecho: Carrito de Compras / Factura del Cliente -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-dark text-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                        <i class="bi bi-receipt text-warning"></i> Resumen de Boleta
                    </h5>
                    <span class="badge bg-warning text-dark font-mono">FAC-TURACIÓN</span>
                </div>

                <div class="card-body p-4">
                    <!-- Datos de Facturación del Cliente -->
                    <div class="row g-2 mb-4 bg-light p-3 rounded-3" style="font-size: 0.85rem;">
                        <h6 class="fw-bold text-dark mb-2 small text-uppercase" style="letter-spacing:0.05em;">Datos de Pago e Identificación</h6>
                        <div class="col-5">
                            <input type="text" class="form-control form-control-sm" id="customer_dni" placeholder="Cédula/RUC" value="0932184912">
                        </div>
                        <div class="col-7">
                            <input type="text" class="form-control form-control-sm" id="customer_name" placeholder="Cliente Consumidor Final" value="Consumidor Final">
                        </div>
                    </div>

                    <!-- Lista de Ítems en Carrito -->
                    <div class="mb-4">
                        <label class="form-label text-muted fw-bold small text-uppercase">Productos Cargados</label>
                        <div id="cartItemsContainer" style="max-height: 220px; overflow-y: auto;" class="border rounded-3 p-3 bg-light">
                            <!-- Ítems se cargarían dinámicamente con JS, aquí mostramos un ejemplo maqueta -->
                            <div class="d-flex justify-content-between align-items-center mb-3 text-dark pb-2 border-bottom">
                                <div>
                                    <span class="fw-bold d-block small">Tornillo Drywall 1x6 (100un)</span>
                                    <small class="text-muted">$4.50 x 2 un</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold font-mono">$9.00</span>
                                    <button class="btn btn-sm btn-outline-danger py-0 px-1"><i class="bi bi-x-circle"></i></button>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-1 text-dark">
                                <div>
                                    <span class="fw-bold d-block small">Cemento Portland 50Kg</span>
                                    <small class="text-muted">$9.20 x 1 un</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold font-mono">$9.20</span>
                                    <button class="btn btn-sm btn-outline-danger py-0 px-1"><i class="bi bi-x-circle"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Calculadora de Totales -->
                    <div class="border-top pt-3 text-dark bg-opacity-10">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted smaill">Subtotal Neto:</span>
                            <span class="fw-semibold font-mono" id="val_subtotal">$18.20</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">IVA (12%):</span>
                            <span class="fw-semibold font-mono" id="val_tax">$2.18</span>
                        </div>
                        <hr class="my-2 text-muted">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="h6 fw-bold text-dark mb-0">TOTAL A COBRAR:</span>
                            <span class="h4 fw-bold text-primary font-mono mb-0" id="val_total">$20.38</span>
                        </div>
                    </div>

                    <!-- Botón para finalizar transacción -->
                    <button class="btn btn-warning w-100 py-3 fw-bold fs-6 text-dark shadow-sm d-flex align-items-center justify-content-center gap-2" onclick="alert('¡Transacción Finalizada con éxito!\nBoleta FAC-2026-34 generada e impresa en la ticketera térmica.')" style="border-radius: 8px;">
                        <i class="bi bi-cash-coin fs-5"></i>
                        <span>EMITIR BOLETA / GUARDAR VENTA</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection`,
  },
  {
    path: 'database/migrations/2014_10_12_000000_create_users_table.php',
    title: 'Migración: Usuarios',
    description: 'Estructura de la tabla de usuarios para login, con distinción de roles (admin/cajero).',
    icon: 'bi-database-fill-add',
    code: `<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'cajero'])->default('cajero');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};`
  },
  {
    path: 'database/migrations/2014_10_12_100000_create_password_reset_tokens_table.php',
    title: 'Migración: Password Resets',
    description: 'Tabla estándar de Laravel para la recuperación de contraseñas de usuarios.',
    icon: 'bi-database-fill-add',
    code: `<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};`
  },
  {
    path: 'database/migrations/2019_08_19_000000_create_failed_jobs_table.php',
    title: 'Migración: Failed Jobs',
    description: 'Tabla estándar de Laravel para el registro de colas y tareas en segundo plano que fallan.',
    icon: 'bi-database-fill-add',
    code: `<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
};`
  },
  {
    path: 'database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php',
    title: 'Migración: Access Tokens',
    description: 'Tabla estándar de Laravel Sanctum para la autenticación por tokens de API.',
    icon: 'bi-database-fill-add',
    code: `<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('nombre');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};`
  },
  {
    path: 'database/migrations/2026_06_13_000001_create_productos_table.php',
    title: 'Migración: Productos',
    description: 'Tabla principal del inventario, gestiona los materiales y su stock actual.',
    icon: 'bi-database-fill-add',
    code: `<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 10, 2);
            $table->integer('stock')->default(0);
            $table->string('categoria');
            $table->string('unidad')->default('Unidades');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};`
  },
  {
    path: 'database/migrations/2026_06_13_000002_create_ventas_table.php',
    title: 'Migración: Ventas (Cabecera)',
    description: 'Comprobantes de las facturas generadas por el POS de caja rápida.',
    icon: 'bi-database-fill-add',
    code: `<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_factura')->unique();
            $table->foreignId('user_id')->constrained('users'); // Identifica al cajero
            $table->decimal('subtotal', 10, 2);
            $table->decimal('impuesto', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};`
  },
  {
    path: 'database/migrations/2026_06_13_000003_create_venta_detalles_table.php',
    title: 'Migración: Detalles de Venta',
    description: 'Relación 1 a N para cada producto facturado dentro del mismo número de recibo.',
    icon: 'bi-database-fill-add',
    code: `<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->onDelete('cascade')->constrained('ventas')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade')->constrained('products');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_detalles');
    }
};`
  },
  {
    path: 'routes/web.php',
    title: 'Rutas Web',
    description: 'Definición de las rutas principales de la aplicación.',
    icon: 'bi-signpost-split',
    code: `<?php

use Illuminate\\Support\\Facades\\Route;
use App\\Http\\Controllers\\DashboardController;
use App\\Http\\Controllers\\ProductoController;
use App\\Http\\Controllers\\VentaController;

Route::get('/', function () {
    return view('auth.login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->nombre('dashboard');
    
    // Inventario
    Route::resource('inventario', ProductoController::class)->parameters(['inventario' => 'producto']);
    
    // POS / Ventas
    Route::get('/pos', [VentaController::class, 'create'])->nombre('pos.create');
    Route::post('/sales', [VentaController::class, 'store'])->nombre('ventas.store');
    Route::get('/sales', [VentaController::class, 'index'])->nombre('ventas.index');
    Route::get('/sales/{sale}', [VentaController::class, 'show'])->nombre('ventas.show');
    
    // Reportes
    Route::get('/reports', [VentaController::class, 'reports'])->nombre('reports.index');
});
`
  },
  {
    path: 'app/Http/Controllers/ProductoController.php',
    title: 'Controlador: Productos',
    description: 'Gestión lógica del CRUD de inventario.',
    icon: 'bi-controller',
    code: `<?php

namespace App\\Http\\Controllers;

use App\\Models\\Producto;
use Illuminate\\Http\\Request;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::all();
        return view('inventario.index', compact('productos'));
    }

    public function create()
    {
        return view('inventario.form');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'sku' => 'required|unique:productos|max:255',
            'nombre' => 'required|max:255',
            'categoria' => 'required',
            'precio' => 'required|numeric',
            'stock' => 'required|integer',
            'unidad' => 'required'
        ]);

        Producto::create($validatedData);

        return redirect()->route('inventario.index')->with('success', 'Producto registrado correctamente.');
    }

    public function edit(Product $product)
    {
        return view('inventario.form', compact('producto'));
    }

    public function update(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'sku' => 'required|max:255|unique:productos,sku,' . $producto->id,
            'nombre' => 'required|max:255',
            'categoria' => 'required',
            'precio' => 'required|numeric',
            'stock' => 'required|integer',
            'unidad' => 'required'
        ]);

        $producto->update($validatedData);

        return redirect()->route('inventario.index')->with('success', 'Producto actualizado.');
    }

    public function destroy(Product $product)
    {
        $producto->delete();
        return redirect()->route('inventario.index')->with('success', 'Producto eliminado.');
    }
}
`
  },
  {
    path: 'app/Http/Controllers/VentaController.php',
    title: 'Controlador: Ventas',
    description: 'Procesamiento del POS y registro de transacciones.',
    icon: 'bi-controller',
    code: `<?php

namespace App\\Http\\Controllers;

use App\\Models\\Venta;
use App\\Models\\VentaDetalle;
use App\\Models\\Producto;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;

class VentaController extends Controller
{
    public function index()
    {
        $ventas = Venta::with('user')->orderBy('created_at', 'desc')->get();
        return view('ventas.index', compact('ventas'));
    }

    public function create()
    {
        $productos = Producto::where('stock', '>', 0)->get();
        return view('pos.index', compact('productos')); // Vista del POS
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'detalles' => 'required|array',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $subtotal = 0;

            foreach ($request->detalles as $item) {
                $subtotal += $detalle['cantidad'] * $detalle['precio_unitario'];
            }

            $tax = $subtotal * 0.12; // IVA 12%
            $total = $subtotal + $tax;

            $venta = Venta::create([
                'numero_factura' => 'FACT-' . time(),
                'user_id' => auth()->id() ?? 1, // Usuario por defecto en demo
                'subtotal' => $subtotal,
                'impuesto' => $tax,
                'total' => $total,
            ]);

            foreach ($request->detalles as $item) {
                $producto = Producto::find($detalle['producto_id']);
                
                if ($producto->stock < $detalle['cantidad']) {
                    throw new \\Exception("Stock insuficiente para: " . $producto->nombre);
                }

                $producto->stock -= $detalle['cantidad'];
                $producto->save();

                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'subtotal' => $detalle['cantidad'] * $detalle['precio_unitario'],
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'venta_id' => $venta->id]);
        } catch (\\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function show(Sale $sale)
    {
        $venta->load(['detalles.product', 'user']);
        return view('ventas.show', compact('venta'));
    }

    public function reports()
    {
        return view('admin.reportes');
    }
}
`
  },
  {
    path: 'app/Models/Producto.php',
    title: 'Modelo: Product',
    description: 'Modelo Eloquent para los productos del inventario.',
    icon: 'bi-box-seam',
    code: `<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'nombre',
        'descripcion',
        'precio',
        'stock',
        'categoria',
        'unidad'
    ];
}
`
  },
  {
    path: 'app/Models/Venta.php',
    title: 'Modelo: Sale',
    description: 'Modelo Eloquent para la cabecera de la factura.',
    icon: 'bi-receipt',
    code: `<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;

class Venta extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_factura',
        'user_id',
        'subtotal',
        'impuesto',
        'total'
    ];

    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
`
  },
  {
    path: 'app/Models/VentaDetalle.php',
    title: 'Modelo: SaleItem',
    description: 'Modelo Eloquent para el detalle de la venta (productos en carrito).',
    icon: 'bi-list-ul',
    code: `<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;

class VentaDetalle extends Model
{
    use HasFactory;

    protected $fillable = [
        'venta_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal'
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
`
  }
];
