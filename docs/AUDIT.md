# Auditoría Técnica Integral - Sistema PuntoÁgil (Ferretería El Tornillo)

**Fecha:** 23 de Octubre de 2026  
**Auditor:** Desarrollador Senior Laravel  
**Versión de Laravel:** 12.0  
**Versión de PHP:** 8.2.12  
**Base de Datos:** PostgreSQL 17.11 en Supabase (Pooler PgBouncer en puerto 6543)  
**Estado General:** Funcional en módulos clave, pero con colisión arquitectural (React/Vite vs Blade), duplicidad de código (inglés/español), vulnerabilidades de autorización crítica y bugs funcionales en formularios y autenticación.

---

## 1. Arquitectura y Estructura del Repositorio

### 1.1 Colisión de Prototipo React/Node con Laravel Monolítico
* **Hallazgo:** El repositorio mezcla dos aplicaciones distintas en la misma raíz:
  1. **Monolito Laravel 12:** Rutas en `routes/web.php`, controladores en `app/Http/Controllers/`, vistas Blade en `resources/views/` maquetadas con **Bootstrap 5.3 CDN** y estilos personalizados.
  2. **Prototipo React 19 / AI Studio:** Directorio `src/` (`App.tsx`, `main.tsx`, `components/`, etc.), `server.js` (Express + `@google/genai`), `index.html`, `vite.config.ts`, `metadata.json` y `package.json` bautizado como `"react-example"`.
* **Impacto:** 
  - `package.json` define `"dev": "vite --port=3000 --host=0.0.0.0"` y dependencias de React/Tailwind v4 que no se usan en las vistas Blade.
  - `composer.json` intenta ejecutar `npm run dev` en su script `dev`, lo cual levanta el servidor de React en lugar del pipeline de assets de Laravel.
  - `vite.config.js` (Laravel) y `vite.config.ts` (React) coexisten y generan ambigüedad en herramientas de construcción.
  - Existencia de un archivo huérfano `public/hot` apuntando a `http://0.0.0.0:3000`.

### 1.2 Configuración del Entorno y Conexión Supabase
* **Base de datos:** Conexión activa a `aws-0-us-east-2.pooler.supabase.com:6543` con driver `pgsql` y `DB_SSLMODE=require`.
* **Migraciones:** 10 migraciones ejecutadas exitosamente (`users`, `products`, `sales`, `sale_items`, `sessions`, `jobs`, `cache`, etc.).
* **Hacks en arranque (`bootstrap/app.php` y `config/database.php`):**
  - En `bootstrap/app.php` (L7-19) se agregó un parser manual de `.env` que puebla `$_ENV` y ejecuta `putenv()`.
  - En `config/database.php` (L89-99) se modificó el bloque `pgsql` para leer `$_ENV['DB_HOST'] ?? env('DB_HOST')`.
  - **Riesgo:** Rompe el aislamiento nativo de Laravel y falla si se ejecuta `php artisan config:cache` en producción.
* **Script destructivo `start-server.bat`:**
  - La línea 8 ejecuta `php artisan key:generate --force` en cada inicio del servidor local. Esto invalida inmediatamente todas las sesiones existentes en base de datos y cookies encriptadas de usuarios.
* **Extensión PHP `ext-intl` ausente:**
  - La CLI local no tiene habilitada la extensión `intl` en `php.ini`. Ejecutar comandos que invoquen `Illuminate\Support\Number` (como `php artisan db:show`) dispara una excepción fatal `RuntimeException`.

---

## 2. Estado de Módulos y Funcionalidades

| Módulo | Estado | Archivos Clave | Descripción y Diagnóstico |
| :--- | :---: | :--- | :--- |
| **Autenticación (Auth)** | ⚠️ Con Fallas | `app/Http/Controllers/Auth/LoginController.php`<br>`resources/views/auth/login.blade.php`<br>`database/seeders/DatabaseSeeder.php` | Login funcional por sesión, pero permite el acceso a usuarios inactivos (`status='inactive'`), carece de rate limiting y las credenciales de demo en la vista están desincronizadas con la BD. |
| **Control de Acceso / Roles** | 🔴 Crítico | `routes/web.php`<br>`resources/views/layouts/app.blade.php`<br>`app/Http/Controllers/Admin/UserController.php` | No existe middleware ni Gate para el rol `admin`. Un cajero puede ingresar directamente por URL a `/admin/users` y gestionar administradores. |
| **Panel Principal (Dashboard)** | 🟢 Operativo | `app/Http/Controllers/DashboardController.php`<br>`resources/views/dashboard.blade.php` | Muestra métricas de ventas del día, inventario, categorías y stock crítico. Correctamente integrado. |
| **Inventario de Productos** | 🟡 Operativo con Bugs | `app/Http/Controllers/ProductController.php`<br>`resources/views/inventory/` | CRUD completo, generación de SKU inteligente, exportación PDF y CSV. **Bug:** El campo `description` no se guarda por omisión en `$request->validate()`. Sin paginación. |
| **Punto de Venta (POS)** | 🟢 Operativo | `app/Http/Controllers/SaleController.php`<br>`resources/views/sales/index.blade.php` | Carrito dinámico en JavaScript, validación de stock, registro transaccional con DB Transaction, generación de boleta PDF vía Dompdf. |
| **Detalle de Venta y Boletas** | 🟢 Operativo | `app/Http/Controllers/SaleController.php`<br>`resources/views/sales/show.blade.php`<br>`resources/views/sales/invoice_pdf.blade.php` | Visualización e impresión de boleta en formato ticket/A4 con desglose de ítems, subtotal e IVA. |
| **Reportes y Transacciones** | 🟡 Parcial | `app/Http/Controllers/SaleController.php`<br>`resources/views/admin/reports.blade.php` | Listado de últimas 12 transacciones y exportación CSV. No permite filtrar por rango de fechas (fijo a `now()`). |
| **Gestión de Usuarios (Admin)** | 🟡 Riesgoso | `app/Http/Controllers/Admin/UserController.php`<br>`resources/views/admin/users/` | CRUD de empleados y administradores. **Riesgo:** Permite auto-eliminación y caída por integridad referencial si el usuario tiene ventas. |
| **Módulos Duplicados en Español** | ⚪ Código Muerto | `app/Http/Controllers/ProductoController.php`<br>`app/Http/Controllers/VentaController.php`<br>`app/Models/Producto.php`, `Venta.php`, `VentaDetalle.php`<br>`resources/views/inventario/`, `resources/views/admin/reportes.blade.php` | Código duplicado e incompleto que apunta a tablas inexistentes (`productos`, `ventas`). No están enrutados y colapsan si se invocan. |

---

## 3. Matriz de Errores Evidentes y Riesgos de Seguridad

### 3.1 Riesgos Críticos de Seguridad (Prioridad 1)
1. **Falta de Autorización en Rutas Administrativas (Broken Access Control):**
   - **Ubicación:** `routes/web.php` (L38-40).
   - **Detalle:** La ruta `Route::prefix('admin')->name('admin.')->group(...)` solo tiene middleware `['auth']`. La restricción por rol solo existe a nivel visual en `resources/views/layouts/app.blade.php` (L164).
   - **Vulnerabilidad:** Un cajero autenticado puede acceder a `http://localhost/admin/users`, crear nuevos administradores, cambiar contraseñas de otros usuarios o eliminarlos.
2. **Usuarios Inactivos Pueden Iniciar Sesión:**
   - **Ubicación:** `app/Http/Controllers/Auth/LoginController.php` (L23).
   - **Detalle:** `Auth::attempt($credentials)` solo valida `email` y `password`. Si un administrador desactiva a un empleado (`status = 'inactive'`), este puede seguir iniciando sesión sin impedimento.
3. **Ausencia de Protección contra Fuerza Bruta en Login:**
   - **Ubicación:** `routes/web.php` (L15) y `LoginController.php`.
   - **Detalle:** La ruta `POST /login` no implementa middleware de limitación de tasa (`throttle:6,1` o `RateLimiter`).

### 3.2 Errores Funcionales Evidentes (Prioridad 2)
1. **Pérdida de Información del Campo `description` en Productos:**
   - **Ubicación:** `app/Http/Controllers/ProductController.php` (L52-59 y L89-96).
   - **Detalle:** El formulario `inventory/form.blade.php` incluye el textarea `description`. Sin embargo, la validación en `store()` y `update()` no incluye la regla para `description`. Al llamar `Product::create($validatedData)` o `$product->update($validatedData)`, Laravel descarta automáticamente cualquier campo no validado, por lo que las descripciones nunca se guardan en la base de datos.
2. **Credenciales de Demostración Incorrectas en Pantalla de Login:**
   - **Ubicación:** `resources/views/auth/login.blade.php` (L142-145).
   - **Detalle:** Los botones de acceso rápido autocompletan `admin@ferreteria.com` / `123456` y `cajero@ferreteria.com` / `123456`. Los datos reales inyectados por `DatabaseSeeder.php` son `admin@eltornillo.com` y `cajero@eltornillo.com` con clave `12345678`. El usuario que hace clic en los botones de prueba recibe un error de credenciales inválidas.
3. **Regeneración Forzada de `APP_KEY` en `start-server.bat`:**
   - **Ubicación:** `start-server.bat` (L8).
   - **Detalle:** Ejecutar `php artisan key:generate --force` cada vez que se levanta el proyecto invalida las sesiones de base de datos (`sessions`) y corrompe cualquier dato cifrado previo.
4. **Fallo de Integridad Referencial al Eliminar Usuarios:**
   - **Ubicación:** `app/Http/Controllers/Admin/UserController.php` (L76-81) y `database/migrations/2026_06_13_000002_create_sales_table.php` (L14).
   - **Detalle:** La tabla `sales` tiene `$table->foreignId('user_id')->constrained('users')` sin cascada ni `nullOnDelete`. Si un administrador intenta eliminar a un cajero que ya registró ventas, el sistema arroja una excepción de base de datos no controlada (SQLSTATE 23503). Tampoco se impide que el usuario administrador actual se auto-elimine.

### 3.3 Código Duplicado y Residuos Huérfanos (Prioridad 3)
1. **Modelos y Controladores Duplicados (Spanglish vs English):**
   - Coexisten `ProductController` (activo) con `ProductoController` (muerto).
   - Coexisten `SaleController` (activo) con `VentaController` (muerto).
   - Coexisten `Product` / `Sale` / `SaleItem` con `Producto` / `Venta` / `VentaDetalle`.
   - Las migraciones reales son `products`, `sales`, `sale_items`. Los modelos en español fallarán inmediatamente si son llamados porque sus tablas no existen.
2. **Vistas Blade Duplicadas:**
   - `resources/views/inventory/` (activa) vs `resources/views/inventario/` (duplicada con enlaces rotos a `route('inventario.index')`).
   - `resources/views/admin/reports.blade.php` (activa) vs `resources/views/admin/reportes.blade.php` (duplicada).
3. **Residuos de React / Express / AI Studio:**
   - Todo el contenido de `src/`, `server.js`, `index.html`, `vite.config.ts`, `metadata.json`, `.env.local` y los paquetes en `package.json` no guardan relación con la aplicación Laravel Blade en ejecución.

---

## 4. Estado de Dependencias y Entorno

### Backend (PHP / Composer)
- `laravel/framework`: 12.0
- `barryvdh/laravel-dompdf`: 3.1 (utilizado directamente con instancias `Dompdf\Dompdf` en controladores)
- `laravel/tinker`: 2.10.1
- **Observación:** No se utiliza ningún paquete de roles (como Spatie Laravel-Permission). Los roles están basados en la columna enum `role` (`admin`, `cajero`) de la tabla `users`.

### Frontend
- Las vistas Blade no cargan los scripts compilados de Vite; dependen de:
  - Bootstrap 5.3.3 CSS (vía CDN jsDelivr)
  - Bootstrap Icons 1.11.3 (vía CDN jsDelivr)
  - Fuentes Google Fonts (Plus Jakarta Sans, JetBrains Mono, Inter)
  - JavaScript Vanilla inline en `sales/index.blade.php` para la lógica del POS.

---

## 5. Plan de Acción y Prioridades Recomendadas

### Fase 1: Seguridad y Control de Acceso (Inmediato)
1. Crear un Middleware `EnsureUserIsAdmin` (o registrar middleware de alias `role:admin`) y aplicarlo a las rutas `admin.*` y `reports.*`.
2. Actualizar `LoginController@login` para verificar que `status === 'active'`.
3. Aplicar limitador de tasa (`throttle:5,1`) a la ruta `login`.
4. Proteger `UserController@destroy` contra la auto-eliminación y manejar la restricción de integridad cuando un usuario posee ventas registradas.

### Fase 2: Corrección de Bugs Funcionales en Flujo Principal
1. Incluir `'description' => 'nullable|string'` en la validación de `ProductController@store` y `ProductController@update`.
2. Corregir los botones de demo en `resources/views/auth/login.blade.php` para que usen `admin@eltornillo.com` y `cajero@eltornillo.com` con clave `12345678`.
3. Remover `php artisan key:generate --force` de `start-server.bat`.
4. Agregar paginación en `ProductController@index` (`$query->paginate(15)`).

### Fase 3: Limpieza y Desacoplamiento Arquitectural
1. Eliminar o archivar el código muerto en español (`ProductoController`, `VentaController`, `Producto`, `Venta`, `VentaDetalle`, `resources/views/inventario`, `resources/views/admin/reportes.blade.php`).
2. Aislar o remover el prototipo React/Node (`src/`, `server.js`, `index.html`, `vite.config.ts`) a una carpeta dedicada o submódulo, limpiando `package.json` para que sirva exclusivamente al frontend de Laravel.
3. Normalizar `bootstrap/app.php` y `config/database.php` removiendo los hacks de carga manual de `.env` para garantizar compatibilidad con `config:cache`.
4. Habilitar la extensión `extension=intl` en el `php.ini` de la máquina de desarrollo.
