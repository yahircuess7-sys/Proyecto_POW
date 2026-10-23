<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - PuntoÁgil / Ferretería El Tornillo</title>
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
<body class="h-full flex items-center justify-center p-4 sm:p-6 text-slate-800 font-sans antialiased selection:bg-emerald-500 selection:text-white">

    <div class="w-full max-w-md">
        <!-- Logo & Header -->
        <div class="text-center mb-6">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3 group">
                <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shadow-lg shadow-emerald-600/25 group-hover:bg-emerald-700 transition-all">
                    <i class="bi bi-nut-fill text-2xl"></i>
                </div>
                <div class="text-left">
                    <div class="text-xl font-bold text-slate-900 tracking-tight leading-none">PuntoÁgil</div>
                    <div class="text-xs font-semibold text-emerald-600 uppercase tracking-widest mt-1">Ferretería El Tornillo</div>
                </div>
            </a>
            <h2 class="mt-5 text-xl font-bold text-slate-900 tracking-tight">Acceso a la Plataforma</h2>
            <p class="text-xs text-slate-500 mt-1">Ingresa tus credenciales para gestionar ventas e inventario.</p>
        </div>

        <!-- Card Container -->
        <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xl shadow-slate-200/50 p-6 sm:p-8">
            <!-- Errors Alert -->
            @if($errors->any())
                <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-start gap-2.5">
                    <i class="bi bi-exclamation-circle-fill text-sm shrink-0 mt-0.5"></i>
                    <ul class="space-y-0.5 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Form -->
            <form action="{{ url('/login') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Correo Electrónico
                    </label>
                    <div class="relative">
                        <i class="bi bi-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="email"
                               name="email"
                               id="email"
                               value="{{ old('email') }}"
                               placeholder="tu-correo@eltornillo.com"
                               required
                               autofocus
                               class="w-full pl-10 pr-4 py-2.5 text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-semibold text-slate-700">
                            Contraseña
                        </label>
                        <span class="text-[11px] text-slate-400">8+ caracteres</span>
                    </div>
                    <div class="relative">
                        <i class="bi bi-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="password"
                               name="password"
                               id="password"
                               placeholder="••••••••"
                               required
                               class="w-full pl-10 pr-4 py-2.5 text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               name="remember"
                               id="remember"
                               class="w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500/20 focus:ring-offset-0">
                        <span class="text-xs text-slate-600">Mantener sesión iniciada</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        class="w-full mt-2 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-md shadow-emerald-600/20 hover:shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                    <i class="bi bi-box-arrow-in-right text-base"></i>
                    <span>Ingresar al Sistema</span>
                </button>
            </form>

            <!-- Quick Credentials Helper for Demo/Audit -->
            <div class="mt-6 pt-5 border-t border-slate-100">
                <span class="block text-center text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2.5">
                    Acceso Rápido (Demostración)
                </span>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button"
                            onclick="document.getElementById('email').value='admin@eltornillo.com'; document.getElementById('password').value='12345678';"
                            class="p-2.5 rounded-xl border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/50 text-left transition-all group">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                            <span class="text-xs font-bold text-slate-700 group-hover:text-emerald-700">Administrador</span>
                        </div>
                        <div class="text-[10px] text-slate-400 font-mono mt-0.5 truncate">admin@eltornillo.com</div>
                    </button>

                    <button type="button"
                            onclick="document.getElementById('email').value='cajero@eltornillo.com'; document.getElementById('password').value='12345678';"
                            class="p-2.5 rounded-xl border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/50 text-left transition-all group">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span class="text-xs font-bold text-slate-700 group-hover:text-emerald-700">Cajero POS</span>
                        </div>
                        <div class="text-[10px] text-slate-400 font-mono mt-0.5 truncate">cajero@eltornillo.com</div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Back to Home -->
        <div class="text-center mt-6">
            <a href="{{ url('/') }}" class="text-xs text-slate-500 hover:text-slate-800 transition-colors inline-flex items-center gap-1">
                <i class="bi bi-arrow-left"></i>
                <span>Volver a la página principal</span>
            </a>
        </div>
    </div>

</body>
</html>
