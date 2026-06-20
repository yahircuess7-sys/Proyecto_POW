@extends('layouts.frontend') <!-- Nota: o se puede usar una vista limpia usando bootstrap directamente -->

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
</html>