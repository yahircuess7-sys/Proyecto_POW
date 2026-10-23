<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PuntoÁgil - Sistema de Gestión Comercial y Facturación POS</title>
    <!-- Google Fonts: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Tailwind CSS CDN -->
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
                            500: '#10B981',
                            600: '#059669',
                            700: '#047857',
                            900: '#064e3b',
                        },
                        slate: {
                            900: '#0F172A',
                            950: '#020617',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'ui-monospace', 'monospace']
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-full flex flex-col font-sans text-slate-800 antialiased selection:bg-emerald-500 selection:text-white bg-slate-50">

    <!-- Navbar -->
    <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-md shadow-emerald-600/20">
                    <i class="bi bi-lightning-charge-fill text-xl"></i>
                </div>
                <div>
                    <span class="text-lg font-bold text-slate-900 tracking-tight leading-none block">PuntoÁgil</span>
                    <span class="text-[10px] uppercase font-semibold text-emerald-600 tracking-wider">Cloud POS & Inventario</span>
                </div>
            </div>

            <nav class="flex items-center gap-3">
                @auth
                    <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-sm hover:shadow transition-all">
                        <i class="bi bi-speedometer2 text-sm"></i>
                        <span>Ir a mi Panel</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 shadow-sm transition-all">
                        <i class="bi bi-box-arrow-in-right text-sm text-slate-500"></i>
                        <span>Ingresar al Sistema</span>
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950 text-white pt-20 pb-28">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_var(--tw-gradient-stops))] from-emerald-500/15 via-transparent to-transparent pointer-events-none"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 mb-6">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Plataforma SaaS Cloud · Versión 2.0</span>
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight max-w-4xl mx-auto leading-tight">
                Control de inventario y facturación ágil para <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">comercios modernos</span>.
            </h1>

            <p class="mt-6 text-base sm:text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed">
                PuntoÁgil centraliza el control de existencias, cálculo automatizado de impuestos y cobro ultra rápido en caja para tu negocio.
            </p>

            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                @auth
                    <a href="{{ url('/dashboard') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 text-xs font-bold uppercase tracking-wider text-slate-900 bg-emerald-400 hover:bg-emerald-300 rounded-xl shadow-lg shadow-emerald-500/20 transition-all">
                        <span>Acceder a la Consola</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 text-xs font-bold uppercase tracking-wider text-slate-900 bg-emerald-400 hover:bg-emerald-300 rounded-xl shadow-lg shadow-emerald-500/20 transition-all">
                        <span>Iniciar Sesión en el Punto de Venta</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                @endauth

                <a href="#modulos" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 text-xs font-semibold text-slate-300 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl transition-all">
                    <span>Explorar Funcionalidades</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Key Modules Section -->
    <section id="modulos" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-xs uppercase font-bold text-emerald-600 tracking-wider">Arquitectura Modular</span>
                <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-2">Módulos Especializados por Perfil</h2>
                <p class="text-sm text-slate-500 mt-2">Diseñados para garantizar rapidez en la atención al cliente y rigurosidad contable en almacén.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- POS Module -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-7 shadow-sm hover:shadow-md transition-shadow relative">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-5">
                        <i class="bi bi-calculator text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Terminal POS Express</h3>
                    <p class="text-xs text-slate-500 leading-relaxed mb-4">
                        Cobro rápido con soporte para pantallas táctiles, búsqueda instantánea por SKU o categoría, ajuste numérico de cantidades y liquidación automática de IVA 12%.
                    </p>
                    <div class="text-xs font-semibold text-emerald-600 flex items-center gap-1">
                        <span>Optimizado para cajeros</span>
                        <i class="bi bi-check2"></i>
                    </div>
                </div>

                <!-- Inventory Module -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-7 shadow-sm hover:shadow-md transition-shadow relative">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-5">
                        <i class="bi bi-boxes text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Gestión de Catálogo & Stock</h3>
                    <p class="text-xs text-slate-500 leading-relaxed mb-4">
                        Auditoría en tiempo real de unidades en bodega, alertas de stock mínimo, generación automatizada de códigos SKU únicos y categorización de productos.
                    </p>
                    <div class="text-xs font-semibold text-blue-600 flex items-center gap-1">
                        <span>Control de almacén</span>
                        <i class="bi bi-check2"></i>
                    </div>
                </div>

                <!-- Reports & Auditing -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-7 shadow-sm hover:shadow-md transition-shadow relative">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center mb-5">
                        <i class="bi bi-file-earmark-spreadsheet text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Auditoría & Reportes</h3>
                    <p class="text-xs text-slate-500 leading-relaxed mb-4">
                        Métricas de recaudación diaria, tickets promedio, comprobantes fiscales en PDF y exportación completa a Excel/CSV con trazabilidad por operador.
                    </p>
                    <div class="text-xs font-semibold text-purple-600 flex items-center gap-1">
                        <span>Supervisión administrativa</span>
                        <i class="bi bi-check2"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="mt-auto bg-slate-900 text-slate-400 py-12 border-t border-slate-800 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <i class="bi bi-lightning-charge-fill text-emerald-500 text-base"></i>
                <span class="font-bold text-white">PuntoÁgil</span>
                <span class="text-slate-500">· Plataforma Cloud de Gestión Comercial</span>
            </div>
            <div>
                <span>&copy; {{ date('Y') }} PuntoÁgil SaaS. Todos los derechos reservados.</span>
            </div>
        </div>
    </footer>

</body>
</html>
