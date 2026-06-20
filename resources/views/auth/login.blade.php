<!DOCTYPE html>
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

            <!-- CORRECCIÓN AQUÍ: url('/login') en lugar de route('login.post') -->
            <form action="{{ url('/login') }}" method="POST">
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
</html>