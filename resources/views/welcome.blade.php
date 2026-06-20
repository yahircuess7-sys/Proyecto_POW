<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenidos a Ferretería El Tornillo</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
        }
        .font-mono {
            font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, monospace;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand text-primary fw-bold d-flex align-items-center gap-2" href="{{ url('/') }}">
                <i class="bi bi-nut-fill text-warning fs-3"></i>
                <span class="fw-bolder tracking-tight" style="letter-spacing: -0.3px;">FERRETERÍA EL TORNILLO</span>
            </a>
            <div class="ms-auto">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary fw-bold px-4 py-2" style="border-radius: 8px;">
                        <i class="bi bi-person-fill me-2"></i>Ir a mi Panel
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-primary fw-bold px-4 py-2" style="border-radius: 8px;">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar al Sistema
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero Banner -->
    <header class="py-5 text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%); border-bottom: 6px solid #f97316;">
        <div class="container py-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-7 text-center text-lg-start">
                    <span class="badge bg-warning text-dark px-3 py-2 fw-bold mb-3 rounded-pill text-uppercase" style="font-size: 0.85rem;">EXCELENCIA EN CONSTRUCCIÓN</span>
                    <h1 class="display-4 text-white mb-3" style="font-weight: 800; letter-spacing: -1px;">
                        Herramientas y pernos que <span style="color: #f97316;">sostienen</span> tus obras
                    </h1>
                    <p class="lead text-light opacity-90 fs-5 mb-4" style="max-width: 620px;">
                        Líderes locales en clavería, cementos Portland de alta duración, maquinaria manual y de torque. Conéctate al sistema para revisar stock o facturar en caja rápida.
                    </p>
                    <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-warning px-4 py-3 fw-bold text-dark text-uppercase d-flex align-items-center gap-2 shadow-lg" style="border-radius: 8px; background-color: #f97316; border-color: #f97316; color: #fff;">
                                <span>Ingresar al Sistema Administrativo</span>
                                <i class="bi bi-arrow-right-short fs-4"></i>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-warning px-4 py-3 fw-bold text-uppercase d-flex align-items-center gap-2 shadow-lg" style="border-radius: 8px; background-color: #f97316; border-color: #f97316; color: #fff;">
                                <span>Ingresar al Sistema Administrativo</span>
                                <i class="bi bi-arrow-right-short fs-4"></i>
                            </a>
                        @endauth
                    </div>
                </div>
                <div class="col-lg-5 d-none d-lg-block text-center text-warning">
                    <i class="bi bi-tools" style="font-size: 13rem; opacity: 0.9;"></i>
                </div>
            </div>
        </div>
    </header>

    <!-- Tarjetas de Beneficios -->
    <section class="py-5 bg-white">
        <div class="container text-center mb-5 mt-4">
            <span class="text-uppercase fw-bold font-mono" style="color: #f97316;">Módulos del Sistema</span>
            <h2 class="fw-bold text-dark mt-1">Gestión de Roles de Ferretería</h2>
            <p class="text-muted mx-auto" style="max-width: 600px;">Esta interfaz permite operar procesos completos de Auditoría de Almacén y Control de Transacciones de Caja (POS).</p>
        </div>
        <div class="container mb-5">
            <div class="row g-4 justify-content-center">
                <div class="col-md-5">
                    <div class="card h-100 p-4 border shadow-sm rounded-3" style="border-color: rgba(0,0,0,0.1);">
                        <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 d-inline-flex mb-3" style="width: 56px; height: 56px;">
                            <i class="bi bi-boxes fs-3"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Rol: Auditor de Inventario</h5>
                        <p class="text-muted mb-0">Ingresa nuevos items, actualiza precios, controla mermas y analiza qué materiales se encuentran bajo el límite óptimo o sin stock.</p>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card h-100 p-4 border shadow-sm rounded-3" style="border-color: rgba(0,0,0,0.1);">
                        <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 d-inline-flex mb-3" style="width: 56px; height: 56px;">
                            <i class="bi bi-cart-check fs-3"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Rol: Cajero POS</h5>
                        <p class="text-muted mb-0">Facturación ágil. Descuenta productos del stock en tiempo real y calcula los totales e impuestos de las compras del cliente.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container text-center">
            <a class="navbar-brand text-light fw-bold d-inline-flex align-items-center gap-2 mb-3" href="#">
                <i class="bi bi-nut-fill text-warning fs-4"></i>
                <span class="tracking-tight">Ferretería El Tornillo S.A.</span>
            </a>
            <p class="text-white-50 small mb-0">&copy; {{ date('Y') }} Sistema Ferretería "El Tornillo". Todos los derechos reservados.</p>
        </div>
    </footer>

</body>
</html>