<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Negocio - PuntoÁgil SaaS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-full flex items-center justify-center p-4 sm:p-6 text-slate-800 font-sans antialiased selection:bg-emerald-500 selection:text-white">

    <div class="relative w-full max-w-lg z-10 py-6">
        <!-- Logo & Header -->
        <div class="text-center mb-6">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3 group">
                <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shadow-lg shadow-emerald-600/25">
                    <i class="bi bi-lightning-charge-fill text-2xl"></i>
                </div>
                <div class="text-left">
                    <div class="text-xl font-bold text-slate-900 tracking-tight leading-none">Punto<span class="text-emerald-600">Ágil</span></div>
                    <div class="text-xs font-semibold text-emerald-600 uppercase tracking-widest mt-1">SaaS Cloud POS</div>
                </div>
            </a>
            <h2 class="mt-4 text-xl font-bold text-slate-900 tracking-tight">Registra tu Negocio</h2>
            <p class="text-xs text-slate-500 mt-1">Comienza con 14 días de prueba en el Plan PuntoÁgil ($30.00/mes).</p>
        </div>

        <div class="bg-white/95 backdrop-blur-md border border-slate-200/80 rounded-2xl shadow-xl shadow-slate-200/50 p-6 sm:p-8">
            @if(session('error'))
                <div class="mb-5 p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-start gap-2.5">
                    <i class="bi bi-info-circle-fill text-amber-600 text-sm shrink-0 mt-0.5"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

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

            <form action="{{ route('register.store') }}" method="POST" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label for="business_name" class="block text-xs font-semibold text-slate-700 mb-1">
                            Nombre Comercial del Negocio *
                        </label>
                        <input type="text" name="business_name" id="business_name" value="{{ old('business_name') }}" required
                               placeholder="Ej. Ferretería San Juan"
                               class="w-full px-3.5 py-2.5 text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    </div>

                    <div>
                        <label for="tax_id" class="block text-xs font-semibold text-slate-700 mb-1">
                            RUC / Identificación Fiscal
                        </label>
                        <input type="text" name="tax_id" id="tax_id" value="{{ old('tax_id') }}"
                               placeholder="Ej. J0310000001234"
                               class="w-full px-3.5 py-2.5 text-xs font-mono text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    </div>

                    <div>
                        <label for="currency" class="block text-xs font-semibold text-slate-700 mb-1">
                            Moneda Operativa *
                        </label>
                        <select name="currency" id="currency"
                                class="w-full px-3.5 py-2.5 text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            <option value="NIO" @selected(old('currency', 'NIO') === 'NIO')>Córdobas (C$ - NIO)</option>
                            <option value="USD" @selected(old('currency') === 'USD')>Dólares ($ - USD)</option>
                        </select>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-3">
                    <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-3">Datos del Propietario</span>

                    <div class="space-y-3.5">
                        <div>
                            <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Nombre Completo *</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                   placeholder="Ej. Carlos Mendoza"
                                   class="w-full px-3.5 py-2.5 text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Correo Electrónico *</label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                       placeholder="propietario@empresa.com"
                                       class="w-full px-3.5 py-2.5 text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            </div>

                            <div>
                                <label for="phone" class="block text-xs font-semibold text-slate-700 mb-1">Teléfono</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                                       placeholder="Ej. 8888-8888"
                                       class="w-full px-3.5 py-2.5 text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">Contraseña *</label>
                                <input type="password" name="password" id="password" required minlength="8"
                                       placeholder="Mínimo 8 caracteres"
                                       class="w-full px-3.5 py-2.5 text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">Confirmar Contraseña *</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8"
                                       placeholder="Repite la contraseña"
                                       class="w-full px-3.5 py-2.5 text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit"
                        class="w-full mt-2 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-md shadow-emerald-600/20 transition-all">
                    <i class="bi bi-building-add text-base"></i>
                    <span>Crear Cuenta y Negocio</span>
                </button>
            </form>

            <div class="mt-4">
                <a href="{{ route('auth.google') }}"
                   class="w-full inline-flex items-center justify-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
                    <i class="bi bi-google text-sm text-rose-500"></i>
                    <span>Registrarse con Google</span>
                </a>
            </div>

            <div class="mt-5 pt-4 border-t border-slate-100 text-center">
                <p class="text-xs text-slate-500">
                    ¿Ya tienes una cuenta registrada?
                    <a href="{{ route('login') }}" class="font-bold text-emerald-600 hover:text-emerald-700 underline underline-offset-2 ml-1">
                        Inicia sesión aquí
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
