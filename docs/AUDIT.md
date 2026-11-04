# Auditoría Técnica Integral y Bitácora de Reorganización - PuntoÁgil SaaS

**Fecha de Actualización:** 23 de Octubre de 2026  
**Auditor / Arquitecto:** Desarrollador Senior Laravel  
**Versión de Laravel:** 12.0  
**Versión de PHP:** 8.2.12  
**Base de Datos:** PostgreSQL 17.11 en Supabase (Pooler PgBouncer en puerto 6543)  
**Estado General:** Refactorizado, seguro y estabilizado. Se resolvieron los riesgos críticos de Broken Access Control, la pérdida de datos en productos, las fallas en autenticación por estado, la auto-eliminación de usuarios, la paginación dinámica y se purgó el código muerto.

---

## 1. Arquitectura y Estructura del Repositorio

### 1.1 Estructura Refactorizada y Separación de Responsabilidades
* **Middleware de Autorización (`EnsureUserIsAdmin`):** Creado en `app/Http/Middleware/EnsureUserIsAdmin.php` y registrado bajo el alias `admin` en `bootstrap/app.php`. Protege de forma estricta las rutas de administración (`admin.users.*`) y reportes financieros (`reports.index`, `admin.reports.export`).
* **Capa de Validación (Form Requests):**
  * `app/Http/Requests/ProductRequest.php`: Centraliza las reglas de creación y actualización de productos, valida unicidad ignorando el ID actual e incluye el campo `description`.
  * `app/Http/Requests/UserRequest.php`: Unifica la validación de usuarios (clave requerida en creación, opcional en edición).
  * `app/Http/Requests/StoreSaleRequest.php`: Valida las transacciones atómicas del POS.
* **Capa de Servicios de Dominio (`SkuGeneratorService`):**
  * Ubicado en `app/Services/SkuGeneratorService.php`. Encapsula la normalización, prefijos por palabras clave y correlativos únicos, reduciendo drásticamente la complejidad y líneas de código de `ProductController`.
* **Capa de Modelos y Relaciones Eloquent:**
  * Enriquecido `User.php` con la relación `sales()` (`HasMany(Sale::class)`) y el método auxiliar `isAdmin(): bool`.
* **Frontend y Paginación:**
  * Configurado `Paginator::useBootstrapFive()` en `app/Providers/AppServiceProvider.php`.
  * Conectada la paginación real de Laravel (`{{ $products->links() }}`) en `resources/views/inventory/index.blade.php`, eliminando la maqueta estática previa.
  * Agregados banners de alertas para mensajes de sesión (`session('success')` y `session('error')`) en las vistas de inventario y usuarios.
* **Depuración de Código Muerto y Duplicado:**
  * Eliminados de forma segura los controladores y modelos huérfanos sin tablas en la base de datos: `ProductoController.php`, `VentaController.php`, `Producto.php`, `Venta.php`, `VentaDetalle.php`.
  * Eliminadas las vistas duplicadas con rutas rotas: `resources/views/inventario/` e `index.blade.php`, `form.blade.php`, `resources/views/admin/reportes.blade.php`, `resources/views/home.blade.php` y la carpeta vacía `resources/views/ventas/`.

---

## 2. Estado Actual de Módulos

| Módulo | Estado | Archivos Clave | Diagnóstico y Mejoras Aplicadas |
| :--- | :---: | :--- | :--- |
| **Autenticación (Auth)** | 🟢 Seguro y Estable | `app/Http/Controllers/Auth/LoginController.php`<br>`resources/views/auth/login.blade.php`<br>`routes/web.php` | Bloqueo activo de usuarios con `status !== 'active'`, rate limiting en `POST /login` (`throttle:6,1`) y credenciales demo sincronizadas con los datos de seed (`admin@puntoagil.com` / `cajero@puntoagil.com` con clave `12345678`). |
| **Control de Acceso / Roles** | 🟢 Seguro y Auditado | `app/Http/Middleware/EnsureUserIsAdmin.php`<br>`routes/web.php`<br>`bootstrap/app.php` | Rutas administrativas y de reportes aisladas bajo middleware `admin`. Un cajero autenticado recibe HTTP 403 Forbidden. Verificado por test automatizado. |
| **Inventario de Productos** | 🟢 Optimizado y Sin Pérdida de Datos | `app/Http/Controllers/ProductController.php`<br>`app/Http/Requests/ProductRequest.php`<br>`app/Services/SkuGeneratorService.php`<br>`resources/views/inventory/` | Paginación real de 15 ítems con preservación de filtros en query string. Corrección de guardado de `description`. Extracción de lógica SKU a servicio. Verificado por test automatizado. |
| **Punto de Venta (POS)** | 🟢 Operativo y Validado | `app/Http/Controllers/SaleController.php`<br>`app/Http/Requests/StoreSaleRequest.php`<br>`resources/views/sales/index.blade.php` | Validación estricta con Form Request, deducción de stock transaccional, generación de boleta PDF y protección contra usuarios huérfanos. |
| **Gestión de Usuarios (Admin)** | 🟢 Blindado contra Fallos | `app/Http/Controllers/Admin/UserController.php`<br>`app/Http/Requests/UserRequest.php`<br>`resources/views/admin/users.blade.php` | Prevención de auto-eliminación del administrador en sesión y protección contra violación de integridad referencial si un usuario posee transacciones de venta. |
| **Reportes Financieros** | 🟡 Operativo (Requiere filtros) | `app/Http/Controllers/SaleController.php`<br>`resources/views/admin/reports.blade.php` | Protegido para administradores. Exportación CSV y métricas funcionales. Queda como deuda técnica permitir filtros por rango de fechas arbitrario. |

---

## 3. Matriz de Cambios Ejecutados

1. **Broken Access Control Solucionado:**
   - Middleware `EnsureUserIsAdmin` activo en grupo de rutas `admin.*` y `reports.*`.
2. **Corrección de Pérdida de Descripciones de Productos:**
   - `ProductRequest` incluye regla y persistencia para `description`.
3. **Bloqueo de Cuentas Inactivas:**
   - `LoginController` rechaza intentos de login con cuenta inactiva y emite feedback apropiado.
4. **Protección contra Fuerza Bruta:**
   - Middleware `throttle:6,1` asignado al endpoint `/login`.
5. **Corrección de `start-server.bat`:**
   - Removido `php artisan key:generate --force`. Las sesiones activas de usuarios ya no se destruyen en cada inicio.
6. **Protección de Integridad en Eliminación de Usuarios:**
   - `UserController@destroy` valida la existencia de ventas previas y bloquea la auto-eliminación.
7. **Paginación Dinámica y Homogeneización Bootstrap 5:**
   - `Paginator::useBootstrapFive()` configurado en `AppServiceProvider`.
   - Vistas actualizadas con navegación real de Laravel.
8. **Depuración de Residuos:**
   - Eliminados 5 archivos PHP de modelos/controladores en desuso y 4 vistas/carpetas duplicadas.
   - Autoload optimizado mediante `composer dump-autoload`.

---

## 4. Deuda Técnica Registrada (Para Fases Posteriores)

1. **Aislamiento del Prototipo React / AI Studio:**
   - La carpeta `src/`, `server.js`, `index.html`, `vite.config.ts`, `metadata.json`, `.env.local` y las dependencias de React en `package.json` siguen residiendo en la raíz.
   - **Acción recomendada:** Moverlos a una subcarpeta independiente (ej. `tools/ai-agent/` o `prototype/`) y limpiar `package.json` para que sirva exclusivamente al frontend de Laravel cuando se requiera empaquetar activos locales.
2. **Normalización del Bootstrapping de Entorno:**
   - En `bootstrap/app.php` (L7-19) y `config/database.php` (L87-100) persiste la lectura manual de `$_ENV` para la conexión de PostgreSQL/Supabase.
   - **Acción recomendada:** Verificar por qué el servidor local requirió este workaround en Windows y normalizarlo para permitir la ejecución limpia de `php artisan config:cache` en entornos de producción.
3. **Filtros por Rango de Fechas en Reportes:**
   - El método `SaleController@reports` actualmente calcula métricas fijas con `whereDate('created_at', now())`.
   - **Acción recomendada:** Permitir parámetros `from` y `to` desde el selector de fechas de la interfaz para auditorías de ventas históricas.
4. **Habilitación de Extensión PHP `intl`:**
   - El entorno local requiere habilitar `extension=intl` en el `php.ini` del servidor para habilitar comandos de formateo nativos como `php artisan db:show`.

---

## 5. Resultados de Verificación Automatizada

Se implementó y ejecutó la suite de pruebas en `tests/Feature/ArchitectureAndSecurityTest.php` contra la base de datos de Supabase:
- `test_guest_cannot_access_protected_routes`: **PASS** (Redirección a `/login`)
- `test_cashier_is_forbidden_from_admin_modules`: **PASS** (HTTP 403 en `/admin/users` y `/reports`)
- `test_admin_can_access_admin_modules`: **PASS** (HTTP 200)
- `test_login_rejects_inactive_user`: **PASS** (Error de sesión y estado desautenticado)
- `test_product_description_is_saved_and_persisted`: **PASS** (Persistencia exitosa de descripción técnica)
- `test_all_redesigned_views_render_successfully`: **PASS** (HTTP 200 en todas las vistas rediseñadas)
- Suite completa del proyecto: **8 tests pasados, 27 aserciones exitosas (100% OK)**.

---

## 6. Rediseño de Interfaz SaaS (Blade, Tailwind CSS & Alpine.js)

En esta fase se rediseñó integralmente la experiencia visual y operativa del sistema, transformándolo de una apariencia heterogénea a una plataforma SaaS profesional, elegante y coherente para PuntoÁgil.

### 6.1 Identidad Visual y Tokens de Diseño
- **Paleta Corporativa:**
  - Verde Esmeralda Principal: `#10B981` (`emerald-500`)
  - Verde Esmeralda Oscuro / Hover: `#059669` (`emerald-600`)
  - Azul Pizarra Profundo (Sidebar y acentos oscuros): `#0F172A` (`slate-900`)
  - Fondo Suave de Aplicación: `#F8FAFC` (`slate-50`)
  - Blanco de Superficie y Tarjetas: `#FFFFFF`
- **Tipografía:**
  - Texto principal y UI: `Inter` (sans-serif)
  - Precios, SKUs, facturas y métricas numéricas: `JetBrains Mono` (font-mono)
- **Componentes Base:**
  - Tarjetas redondeadas (`rounded-2xl`) con bordes sutiles `border-slate-200/80` y sombras suaves.
  - Insignias de estado (Active, Inactivo, Stock Óptimo, Bajo Stock, Agotado) con contrastes accesibles.
  - Modales accesibles interactivos impulsados por Alpine.js sin alertas nativas intrusivas del navegador.

### 6.2 Vistas y Módulos Rediseñados
1. **Layout Principal (`resources/views/layouts/app.blade.php`):**
   - Sidebar retráctil moderno en pizarra oscuro con badges dinámicos para roles (Admin / Cajero).
   - Menú móvil con backdrop blur impulsado por Alpine.js.
   - Encabezado superior con indicador de estado en vivo, perfil del usuario activo, atajo rápido a nueva venta POS y notificaciones flash integradas.
2. **Dashboard Ejecutivo (`resources/views/dashboard.blade.php`):**
   - 4 tarjetas métricas KPI (Ventas Totales, Artículos en Stock, Catálogo Activo y Nivel Crítico de Inventario).
   - Accesos directos prioritarios hacia "Terminal POS Express" y "Catálogo de Inventario".
   - Alertas contextuales y tabla de artículos con stock crítico.
3. **Catálogo de Inventario (`resources/views/inventory/index.blade.php`):**
   - Barra de herramientas con filtros por texto, categoría y estado de stock en tiempo real.
   - Tabla con badges de disponibilidad por colorimetría (esmeralda, ámbar, rosa).
   - Modal interactivo de confirmación de eliminación con Alpine.js.
   - Paginación conectada a Eloquent conservando parámetros de búsqueda.
4. **Formulario de Productos (`resources/views/inventory/form.blade.php`, `create.blade.php`, `edit.blade.php`):**
   - Diseño en cuadrícula limpia de dos columnas, badges de ayuda y cálculo automático de SKU sugerido.
   - Preservación completa del campo `description`.
5. **Detalle de Producto (`resources/views/inventory/show.blade.php`):**
   - Ficha técnica con desglose de márgenes, estado de bodega y accesos rápidos a edición.
6. **Terminal POS de Ventas (`resources/views/sales/index.blade.php`):**
   - Interfaz táctil ergonómica dividida en catálogo de productos y carrito lateral interactivo.
   - Filtro por pastillas de categoría y barra de búsqueda reactiva.
   - Ajustadores rápidos de cantidad (+ / - / eliminar) de alta respuesta táctil.
   - Desglose contable automático en tiempo real: Subtotal, IVA 12% y Total.
   - Envío asíncrono con feedback visual, botón de bloqueo ante doble click y descarga automática de factura PDF.
7. **Detalle / Comprobante de Venta (`resources/views/sales/show.blade.php`):**
   - Comprobante digital fiscal con diseño tipo recibo corporativo.
   - Botones para impresión directa (`window.print()`), navegación a reportes y apertura de nueva venta.
   - Desglose de ítems con cantidades, valores unitarios y subtotales.
8. **Gestión de Usuarios (`resources/views/admin/users.blade.php`, `create.blade.php`, `edit.blade.php`, `form.blade.php`):**
   - Listado administrativo con búsqueda por nombre y correo, badges de rol y estado.
   - Modal de confirmación para eliminar operadores.
   - Formularios estilizados con ayudas visuales y selección intuitiva de roles.
9. **Reportes Contables y Auditoría (`resources/views/admin/reports.blade.php`):**
   - Tarjetas métricas ejecutivas (Ingresos del Día, Comprobantes Emitidos y Ticket Promedio).
   - Tabla histórica de transacciones con enlace directo al detalle fiscal de cada comprobante.
   - Botón directo de exportación CSV/Excel.
10. **Autenticación e Inicio de Sesión (`resources/views/auth/login.blade.php`):**
    - Pantalla centrada con branding PuntoÁgil, tipografía Inter y alertas de validación estilizadas.
    - Botones de acceso rápido para demostración (Admin y Cajero) con autocompletado en un solo click.
11. **Página de Entrada / Bienvenida (`resources/views/welcome.blade.php`):**
    - Landing corporativa SaaS con hero banner degradado, resumen de módulos por rol y acceso directo al sistema.

---

## 7. Arquitectura Multiempresa (Multi-Tenancy) y Seguridad de Base de Datos

### 7.1 Modelo de Datos y Relaciones Multiempresa
Se diseñó e implementó un esquema de aislamiento multiempresa (shared-database, isolated-schema) robusto y extensible:
* **Entidad `businesses`:**
  * Define cada empresa/inquilino (tenant) en la plataforma SaaS.
  * Campos: `id`, `name`, `slug` (único), `tax_id` (RUC/NIT/RFC), `email`, `phone`, `address`, `status` ('active', 'suspended'), `settings` (JSONB para monedas, tasas de impuestos y parámetros fiscales) y `timestamps`.
* **Entidad `business_user` (Membresías Multiempresa):**
  * Modela la pertenencia de un usuario a una o varias empresas con roles contextuales.
  * Campos: `id`, `business_id` (FK cascade), `user_id` (FK cascade), `role` ('owner', 'admin', 'cajero'), `is_default` (booleano) y `timestamps`.
  * Restricción de integridad: Clave única compuesta en `(business_id, user_id)`.
* **Contexto Activo en `users`:**
  * Columna `current_business_id` (FK referenciando `businesses.id`, `nullOnDelete`), permitiendo navegación transparente entre empresas sin cerrar sesión.
* **Aislamiento en Registros Comerciales:**
  * **`products`:** Añadida columna `business_id` (FK `NOT NULL`, cascade). Eliminado el antiguo índice único global `products_sku_unique` y reemplazado por la clave única compuesta `(business_id, sku)`. Creados índices compuestos `(business_id, category)` y `(business_id, stock)` para consultas de inventario de alto rendimiento.
  * **`sales`:** Añadida columna `business_id` (FK `NOT NULL`, cascade). Creada clave única compuesta `(business_id, invoice_number)` e índice compuesto `(business_id, created_at)`.
  * **`sale_items`:** Añadida columna `business_id` (FK `NOT NULL`, cascade) e índice `(business_id)` para acelerar agregaciones contables y auditorías.

### 7.2 Migración Incremental, Reversible y Sin Pérdida de Datos
* **Archivo de Migración:** `database/migrations/2026_10_23_000001_create_businesses_and_multi_tenant_architecture.php`.
* **Ejecución no destructiva:** Creó e insertó automáticamente la empresa matriz inicial **`PuntoÁgil Central`** (`id: 1`, `slug: 'puntoagil-central'`), asociando de forma transparente los usuarios existentes (`admin@puntoagil.com` como `owner`, `cajero@puntoagil.com` como `cajero`) y todos los productos existentes, garantizando continuidad de servicio sin registros huérfanos.
* **Reversibilidad (`down`):** Implementada de forma segura para eliminar restricciones compuestas, restaurar índices originales y suprimir tablas adicionales en caso de requerirse rollback.

### 7.3 Evaluación y Hallazgos Críticos de Supabase RLS vs. Rol de Conexión
* **Diagnóstico de Conexión:** Laravel se conecta a Supabase mediante PgBouncer (`aws-0-us-east-2.pooler.supabase.com:6543`) utilizando el rol de base de datos `postgres`.
* **Hallazgo Crítico:** Mediante inspección en tiempo de ejecución de las propiedades de rol en PostgreSQL (`rolbypassrls = 1`), se evidenció que el usuario `postgres` posee la capacidad de omitir RLS incondicionalmente (`BYPASSRLS`). Según las especificaciones de PostgreSQL, incluso con `FORCE ROW LEVEL SECURITY`, los roles con `BYPASSRLS` evaden las políticas de fila.
* **Decisión de Seguridad en Profundidad (Defense-in-Depth):**
  1. **Aislamiento Estricto en Capa de Aplicación (Primario):**
     * **`TenantManager` (`app/Services/TenantManager.php`):** Singleton que administra el negocio activo en memoria y sincroniza la variable de sesión `SET app.current_business_id`.
     * **`BusinessScope` (`app/Models/Scopes/BusinessScope.php`):** Global Scope de Eloquent que intercepta todas las consultas sobre `Product`, `Sale` y `SaleItem` agregando automáticamente `WHERE business_id = ?`.
     * **`BelongsToBusiness` (`app/Traits/BelongsToBusiness.php`):** Trait que auto-vincula el alcance global y asigna de forma automática el `business_id` en el evento `creating` del ciclo de vida del modelo.
  2. **Control en Middleware (`app/Http/Middleware/IdentifyTenant.php`):**
     * Valida de manera obligatoria que el usuario autenticado pertenezca activamente a la empresa seleccionada antes de permitir el acceso.
     * Comparte las variables reactivas `$currentBusiness` y `$currentBusinessUserRole` con las vistas Blade.
  3. **Políticas RLS en Base de Datos (Secundario / API Supabase):**
     * Se aplicó `ALTER TABLE ... ENABLE ROW LEVEL SECURITY` y se definieron políticas `tenant_isolation` en `products`, `sales` y `sale_items`. Si en el futuro se exponen clientes de Supabase con roles restringidos (`anon`, `authenticated`), la base de datos aplicará las restricciones a nivel de motor.

### 7.4 Controladores y Lógica Comercial Adaptada
* **`ProductController` & `ProductRequest`:** La regla de unicidad del SKU ahora valida exclusivamente dentro de la empresa en sesión (`Rule::unique('products', 'sku')->where('business_id', $businessId)`). Empresas distintas pueden tener el mismo SKU sin conflictos.
* **`SkuGeneratorService`:** Al consultar productos existentes para generar correlativos con prefijo (`CEM-001`), el servicio respeta el `BusinessScope`, garantizando correlativos independientes por empresa.
* **`SaleController`:** En el POS, el controlador valida que cada ítem solicitado exista y pertenezca al catálogo del negocio activo, bloqueando intentos de cobro cruzado de inventario. Las boletas y reportes quedan filtrados automáticamente.
* **`Admin\UserController`:** La administración de usuarios opera en el contexto de la empresa activa. Un administrador solo puede ver, crear, editar y remover operadores asociados a su empresa.

### 7.5 Verificación Automatizada (Resultados 100% Satisfactorios)
Se diseñó e implementó la suite especializada `tests/Feature/MultiTenantIsolationTest.php` junto con la preservación de la suite base `ArchitectureAndSecurityTest.php`:
* `test_catalog_is_strictly_isolated_between_businesses`: **PASS** (Catálogo de una empresa B es invisible en empresa A; acceso directo por ID retorna 404).
* `test_sku_uniqueness_is_scoped_per_business`: **PASS** (Permite coexistencia del mismo SKU en diferentes empresas pero previene duplicidad dentro de la misma).
* `test_pos_checkout_rejects_cross_tenant_products`: **PASS** (Rechaza cobros de artículos pertenecientes a otra empresa con HTTP 400).
* `test_user_management_is_isolated_per_business`: **PASS** (Aislamiento completo de operadores entre empresas en módulo administrativo).
* **Resultado Global de la Suite:** **13 tests aprobados, 51 aserciones exitosas (100% OK)**.

---

## 8. Autenticación Avanzada, OAuth 2.0 con Google y Control de Acceso Basado en Roles (RBAC)

### 8.1 Fortalecimiento de Sesiones, Login, Recuperación y Verificación
* **Rate Limiting Granular (`LoginController`):** Además del middleware `throttle:6,1`, se implementó limitación por combinación `email|ip` mediante `RateLimiter` (máximo 5 intentos fallidos por minuto), limpiando el contador tras un acceso válido.
* **Revocación Inmediata de Sesiones Inactivas (`IdentifyTenant`):** Si un usuario autenticado es desactivado (`status = 'inactive'`) mientras mantiene una sesión abierta, el middleware cierra su sesión en la siguiente petición, limpia `TenantManager`, invalida la sesión y regenera el token CSRF.
* **Recuperación de Contraseña (`PasswordResetController`):**
  * Se corrigió en `config/auth.php` el nombre de la tabla de tokens a `password_reset_token` (alineado con la migración real existente en PostgreSQL).
  * Rutas implementadas: `GET /forgot-password`, `POST /forgot-password`, `GET /reset-password/{token}` y `POST /reset-password` con vistas dedicadas (`auth/forgot-password.blade.php` y `auth/reset-password.blade.php`).
* **Verificación de Correo (`EmailVerificationController`):**
  * El modelo `User` implementa `MustVerifyEmail`. Las cuentas existentes fueron marcadas como verificadas en la migración `2026_10_24_000001_enhance_auth_and_rbac_columns_on_users_table.php` para evitar bloqueos operativos.
  * Rutas implementadas: `/email/verify`, `/email/verify/{id}/{hash}` (con middleware `signed`) y `/email/verification-notification`.

### 8.2 Autenticación con Google (OAuth 2.0 / OpenID Connect)
* **Controlador:** `app/Http/Controllers/Auth/GoogleAuthController.php`.
* **Comportamiento seguro sin simulación:**
  * Si `GOOGLE_CLIENT_ID` y `GOOGLE_CLIENT_SECRET` no están definidos en el entorno, `GET /auth/google` **nunca simula un inicio de sesión**; redirige de forma segura a `/login` informando que el proveedor no está configurado.
  * Cuando está configurado, soporta tanto `Laravel\Socialite` (si se instala el paquete) como el flujo estándar OAuth 2.0 / OpenID Connect de Google mediante `Illuminate\Support\Facades\Http`, validando estado criptográfico CSRF (`google_oauth_state`) en sesión, verificando `email_verified` y vinculando `google_id` y `avatar` únicamente a cuentas autorizadas y activas en PuntoÁgil.

### 8.3 Jerarquía de 4 Niveles de Rol y Políticas de Autorización en Servidor
Se implementaron Políticas de Eloquent (`ProductPolicy`, `SalePolicy`, `UserPolicy`, `BusinessPolicy`) y Gates en `AppServiceProvider`, aplicadas en el servidor mediante `Gate::authorize()` y `FormRequest::authorize()`:

| Rol | Identificador | Alcance y Permisos en el Servidor |
| :--- | :--- | :--- |
| **Super Admin** | `super_admin` (`is_super_admin = true`) | Control global de plataforma (`Gate: manage-platform`), gestión multiempresa, acceso irrestricto a todos los módulos. |
| **Propietario del Negocio** | `owner` | Control total de su empresa (`Gate: manage-business-settings`), creación/edición/eliminación de administradores (`admin`) y cajeros (`cajero`), gestión completa de inventario y reportes. No puede ser eliminado ni degradado por administradores regulares. |
| **Administrador** | `admin` | Gestión operativa de la empresa: crear, editar y eliminar productos (`ProductPolicy`), ver y exportar reportes contables (`SalePolicy`), y gestionar únicamente usuarios con rol `cajero` (`UserPolicy`). Bloqueado con HTTP 403 si intenta crear/editar/eliminar a un `owner` o `super_admin`. |
| **Cajero POS** | `cajero` | Operación de caja rápida (`pos.create`, `sales.store`, `sales.index`, `sales.show`, `sales.pdf`) y **solo lectura** en inventario (`inventory.index`, `inventory.show`). Bloqueado en el servidor con **HTTP 403** ante cualquier intento de crear, editar o eliminar productos, o acceder a usuarios y reportes. |

### 8.4 Configuraciones Externas Pendientes (Variables de Entorno)
Para habilitar en producción el inicio de sesión con Google y el envío real de correos electrónicos (recuperación de clave y verificación), el administrador de infraestructura debe definir en `.env`:
```env
GOOGLE_CLIENT_ID="su-client-id.apps.googleusercontent.com"
GOOGLE_CLIENT_SECRET="su-client-secret"
GOOGLE_REDIRECT_URI="https://su-dominio.com/auth/google/callback"

MAIL_MAILER="smtp"
MAIL_HOST="smtp.proveedor.com"
MAIL_PORT=587
MAIL_USERNAME="usuario-smtp"
MAIL_PASSWORD="password-smtp"
MAIL_FROM_ADDRESS="no-reply@puntoagil.com"
MAIL_FROM_NAME="PuntoÁgil SaaS"
```
*(Documentadas como plantilla sin secretos en `.env.example` y registradas en `config/services.php`)*.

---

## 9. Gestión de Negocios, Membresías de Usuarios, Invitaciones y Auditoría Protegida

### 9.1 Esquema de Datos Incremental (`2026_10_24_000002_add_last_login_invitations_and_audit_logs.php`)
* **Trazabilidad de Último Acceso en `users`:**
  * Se añadieron las columnas `last_login_at` (`TIMESTAMP NULL`) y `last_login_ip` (`VARCHAR(45) NULL`), actualizadas automáticamente en `LoginController` y `GoogleAuthController`.
* **Invitaciones por Negocio (`business_invitations`):**
  * Tabla para invitar miembros a un negocio con rol asignado (`owner`, `admin`, `cajero`), token único criptográfico (`token`), estado (`pending`, `accepted`, `revoked`, `expired`) y fecha de expiración (`expires_at`).
* **Bitácora de Auditoría Protegida (`audit_logs`):**
  * Tabla inmutable (solo inserción y lectura mediante `AuditLogger::record`) que almacena `business_id`, `user_id`, `action`, `target_type`, `target_id`, `description`, `metadata` (JSON con sanitización automática de secretos), `ip_address`, `user_agent` y `created_at`.

### 9.2 Gestión de Negocios y Separación Super Admin vs. Owner
* **Configuración del Negocio Activo (`BusinessController@settings` / `updateSettings`):**
  * Protegido por `Gate::authorize('manage-business-settings')`. Exclusivo para el **Owner** del negocio activo (y **Super Admin**). Permite configurar nombre comercial, RFC/NIT (`tax_id`), correo, teléfono y dirección fiscal.
* **Cambio de Contexto Multiempresa (`BusinessController@switchBusiness`):**
  * Permite a usuarios autorizados en múltiples negocios alternar su empresa activa (`POST /business/switch`), validando membresía activa en el servidor antes de cambiar `current_business_id`.
* **Administración Global de Empresas (`BusinessController@index`, `create`, `store`, `toggleStatus`):**
  * Separada bajo `/admin/businesses` y protegida por `Gate::authorize('manage-platform')` (exclusivo **Super Admin**). Permite listar todos los negocios del SaaS, registrar nuevas empresas asignando su propietario (`owner`), y suspender o reactivar la operación de cualquier tenant con confirmación modal.

### 9.3 Gestión de Usuarios, Estados de Cuenta, Invitaciones y Revocación
* **Jerarquía Estricta contra Escalación de Privilegios (`UserController` & `UserPolicy`):**
  * **Owner:** Puede crear, invitar, editar, activar/desactivar y revocar acceso a miembros con rol `admin` y `cajero` dentro de su negocio.
  * **Admin:** Solo puede crear, invitar, editar, activar/desactivar y revocar cuentas con rol `cajero` de su negocio. Cualquier petición manipulada para asignar rol `admin`, `owner` o `super_admin`, o para modificar/desactivar a un `Owner` u otro `Admin`, es rechazada en el servidor con **HTTP 403**.
  * **Autoprotección:** Ningún usuario puede desactivar su propia cuenta ni revocar su propio acceso.
* **Sistema de Invitaciones (`UserController@invite` & `InvitationController`):**
  * Si el servidor tiene configurado un transporte SMTP real (`MAIL_MAILER` distinto de `log`/`array`), envía el correo con el enlace de invitación `/invitations/{token}`.
  * En entorno local (`MAIL_MAILER=log`), registra la invitación y muestra el enlace directo en la interfaz para que pueda probarse localmente sin inventar servicios de correo externos.
* **Confirmaciones para Operaciones Delicadas:**
  * En `resources/views/admin/users.blade.php` y `resources/views/admin/businesses/index.blade.php`, activar/desactivar cuentas, revocar accesos de miembros y suspender empresas requieren confirmación explícita mediante modales interactivos en Alpine.js.

---

## 10. Módulo Completo de Productos, Inventario y Trazabilidad de Stock (Fase 3)

### 10.1 Esquema de Datos y Migración Incremental (`2026_10_24_000003_enhance_products_and_create_inventory_movements.php`)
* **Extensión de `products`:**
  * `barcode` (`VARCHAR(100) NULL`): Código de barras (EAN/UPC) con índice compuesto `(business_id, barcode)`.
  * `cost` (`DECIMAL(10,2) DEFAULT 0.00`): Costo de adquisición para cálculo contable y margen bruto.
  * `min_stock` (`INTEGER DEFAULT 5`): Umbral de existencias mínimas para alerta de stock crítico.
  * `status` (`VARCHAR(20) DEFAULT 'active'`): Estado en catálogo (`active` / `inactive`) con índice compuesto `(business_id, status)`.
* **Tabla de Trazabilidad `inventory_movements`:**
  * Columnas: `id`, `business_id` (FK), `product_id` (FK), `user_id` (FK nullable), `type` (`'initial'`, `'in'`, `'out'`, `'adjustment'`, `'sale'`), `quantity` (delta con signo), `previous_stock`, `new_stock`, `unit_cost`, `reason` (obligatorio), `reference_type` / `reference_id` (mórfico para ventas u otros documentos), `created_at`, `updated_at`.
  * Índices de alto rendimiento: `(business_id, product_id, created_at)`, `(business_id, type)`, `(business_id, user_id)`.

### 10.2 Trazabilidad de Inventario y Protección de Concurrencia (`InventoryService`)
* **Capa de Dominio (`app/Services/InventoryService.php`):**
  * `recordInitialStock`: Al registrar un producto con existencias mayores a cero, genera automáticamente el movimiento inicial auditado.
  * `recordSaleDeduction`: Ejecuta bloqueo a nivel de fila (`lockForUpdate()`) dentro de la transacción del POS (`SaleController@store`), valida que el producto esté activo (`status = 'active'`) y que las existencias sean suficientes, deduce el inventario y crea el movimiento de tipo `sale` vinculado a la factura.
  * `adjustStock`: Ajustes manuales por entrada (`in`), salida/merma (`out`) o conteo físico (`adjustment`). Exige motivo justificado obligatorio (mínimo 3 caracteres), valida contra stock negativo (`$newStock >= 0`) y registra auditoría en `audit_logs`.

### 10.3 Operaciones en Controlador y Vistas Blade
* **Activación y Desactivación de Productos (`ProductController@toggleStatus`):**
  * Permite desactivar productos sin eliminarlos para proteger la integridad referencial de comprobantes históricos. Los productos desactivados quedan automáticamente excluidos del terminal POS y de nuevas ventas.
* **Importación Masiva CSV (`ProductController@importCsv`):**
  * Soporta delimitadores `;` y `,`, valida encabezados (`Nombre, Categoria, Precio, Costo, Stock, Stock_Minimo, Unidad, SKU, Codigo_Barras, Descripcion`), resuelve SKUs por familia y crea movimientos iniciales para productos con existencias.
* **Exportación Actualizada (CSV & PDF):**
  * Incorpora código de barras, costo, precio, stock, stock mínimo y estado de catálogo.
* **Interfaz de Usuario Ergonómica (`inventory/index.blade.php`, `form.blade.php`, `show.blade.php`):**
  * Tarjetas KPI en el índice con conteo de artículos, stock crítico dinámico (`stock <= min_stock`), agotados y valor total en mostrador.
  * Modales en Alpine.js para ajuste manual de existencias con motivo y para importación masiva CSV.
  * Pestaña histórica de movimientos en la ficha de detalle de cada producto.

---

## 11. Módulo 8: Clientes, Proveedores y Compras con Trazabilidad Multi-Tenant

### 11.1 Esquema de Base de Datos y Migración Incremental (`2026_11_04_000001_create_customers_suppliers_and_purchases_tables.php`)
* **Tabla `customers`:**
  * Campos: `id`, `business_id` (FK a `businesses`), `document_type` (`DNI`, `RUC`, `Pasaporte`, `Otro`), `document_number` (`VARCHAR(50)`), `name` (`VARCHAR(255)`), `email` (`VARCHAR(255) NULL`), `phone` (`VARCHAR(50) NULL`), `address` (`TEXT NULL`), `status` (`'active'` / `'inactive'`), `notes` (`TEXT NULL`), timestamps y soft-deletes.
  * Restricción de Unicidad Multi-Tenant: `UNIQUE(business_id, document_number)`. Distintas empresas pueden tener el mismo número de documento, pero se evitan duplicados en la misma empresa.
  * Índices de consulta rápida: `(business_id, status)`, `(business_id, name)`.
* **Tabla `suppliers`:**
  * Campos: `id`, `business_id` (FK), `tax_id` (`VARCHAR(50)` - RUC/NIT), `name` (`VARCHAR(255)`), `contact_name` (`VARCHAR(255) NULL`), `email` (`VARCHAR(255) NULL`), `phone` (`VARCHAR(50) NULL`), `address` (`TEXT NULL`), `status` (`'active'` / `'inactive'`), `notes` (`TEXT NULL`), timestamps y soft-deletes.
  * Restricción de Unicidad Multi-Tenant: `UNIQUE(business_id, tax_id)`.
  * Índices: `(business_id, status)`, `(business_id, name)`.
* **Modificación en `sales`:**
  * Agregado `customer_id` (FK nullable a `customers` con `ON DELETE SET NULL`), `customer_name` (`VARCHAR(255) NULL`), `customer_document` (`VARCHAR(50) NULL`). Permite asociar clientes formales o conservar la instantánea fiscal sin romper recibos si el cliente se desvincula.
* **Modificación en `products`:**
  * Agregado `supplier_id` (FK nullable a `suppliers` con `ON DELETE SET NULL`) para identificar al proveedor habitual de cada artículo.
* **Tablas `purchases` y `purchase_items`:**
  * `purchases`: `id`, `business_id` (FK), `supplier_id` (FK), `user_id` (FK), `purchase_number` (`VARCHAR(50)` único por empresa), `supplier_invoice_number` (`VARCHAR(100) NULL`), `purchase_date` (`DATE`), `subtotal` (`DECIMAL(12,2)`), `tax` (`DECIMAL(12,2)`), `total` (`DECIMAL(12,2)`), `status` (`'completed'` / `'cancelled'`), `notes` (`TEXT NULL`), timestamps.
  * `purchase_items`: `id`, `purchase_id` (FK cascada), `product_id` (FK), `quantity` (`INTEGER`), `unit_cost` (`DECIMAL(12,2)`), `subtotal` (`DECIMAL(12,2)`), timestamps.

### 11.2 Capa de Modelos Eloquent y Relaciones
* **`Customer` (`app/Models/Customer.php`):**
  * Usa trait `BelongsToBusiness` para aislamiento automático.
  * Relación `sales()` (`HasMany(Sale::class)`).
  * Métodos de métricas: `totalSpent()`, `totalPurchasesCount()`, `isActive()`.
* **`Supplier` (`app/Models/Supplier.php`):**
  * Usa trait `BelongsToBusiness`.
  * Relación `purchases()` (`HasMany(Purchase::class)`) y `products()` (`HasMany(Product::class)`).
  * Métodos de métricas: `totalPurchased()`, `totalPurchasesCount()`, `isActive()`.
* **`Purchase` y `PurchaseItem` (`app/Models/Purchase.php`, `app/Models/PurchaseItem.php`):**
  * Relaciones directas entre proveedor, usuario responsable, ítems y productos.
* **Modelos Actualizados (`Sale`, `Product`, `Business`):**
  * Relaciones `Sale::customer()`, `Product::supplier()`, `Business::customers()`, `Business::suppliers()`, `Business::purchases()`.

### 11.3 Políticas de Autorización y Form Requests
* **`CustomerPolicy` (`app/Policies/CustomerPolicy.php`):**
  * Cajeros, Administradores y Propietarios pueden listar, ver y registrar clientes de su empresa.
  * La edición está permitida a usuarios de la empresa; la eliminación o desactivación está reservada para roles con permisos administrativos.
  * `delete` rechaza la eliminación física si el cliente cuenta con ventas registradas (protección de integridad fiscal).
* **`SupplierPolicy` y `PurchasePolicy` (`app/Policies/SupplierPolicy.php`, `app/Policies/PurchasePolicy.php`):**
  * Restringidas estrictamente a administradores y propietarios del negocio. Cajeros reciben `403 Forbidden`.
* **Form Requests con Validación Multi-Tenant:**
  * `CustomerRequest`: Regla `Rule::unique('customers', 'document_number')->where('business_id', $businessId)->ignore($customerId)`.
  * `SupplierRequest`: Regla `Rule::unique('suppliers', 'tax_id')->where('business_id', $businessId)->ignore($supplierId)`.
  * `PurchaseRequest`: Valida que el proveedor pertenezca a la empresa y que cada producto del array `items.*.product_id` exista y pertenezca al negocio del usuario.

### 11.4 Integración de Compras con Inventario y Kardex Atómico
* En `PurchaseController@store`:
  * Ejecución dentro de una transacción `DB::beginTransaction()`.
  * Bloqueo pesimista `lockForUpdate()` sobre cada producto para evitar condiciones de carrera concurrentes.
  * Creación de `Purchase` y sus `PurchaseItem`.
  * Actualización automática del costo unitario (`cost`) del producto con el valor negociado de compra.
  * Incremento directo del stock (`stock = previous_stock + quantity`).
  * Creación inmediata de `InventoryMovement` con `type = 'in'`, trazabilidad obligatoria a la compra (`reference_type = Purchase::class`, `reference_id = $purchase->id`) y auditoría en `audit_logs`.

### 11.5 Terminal POS y Facturación con Clientes
* En `SaleController` y `resources/views/sales/index.blade.php`:
  * Selector dinámico de clientes registrados con auto-llenado de DNI/RUC y nombre.
  * Opción predeterminada rápida para ventas a "Consumidor Final" sin fricción de caja.
  * Persistencia de `customer_id`, `customer_name` y `customer_document` en el payload JSON de `sales.store`.
  * Visualización del cliente en la consulta de recibo (`sales/show.blade.php`) y en la exportación PDF (`sales/invoice_pdf.blade.php`).

### 11.6 Suite de Pruebas Automatizadas
* Archivo creado: `tests/Feature/CustomerAndSupplierManagementTest.php`.
  * `test_customer_creation_editing_and_tenant_isolation`: Valida creación, edición, desactivación lógica, protección multi-tenant (Empresa B no puede ver ni modificar clientes de Empresa A) y unicidad por negocio.
  * `test_supplier_permissions_and_protection`: Comprueba que cajeros reciben 403, que administradores registran y desactivan proveedores, y que no se pueden eliminar proveedores con compras históricas.
  * `test_purchase_registers_inventory_in_movement_and_updates_product_cost`: Verifica la creación de órdenes de compra, la liquidación de impuestos, la actualización de costos de producto y el registro del movimiento de inventario de entrada.
  * `test_sale_associates_customer_and_prevents_unsafe_deletion`: Verifica el enlace cliente-venta en el POS, cálculo de métricas de cliente y bloqueo de borrado de clientes con ventas.

---

## 12. Módulo 9: Compras, Abastecimiento y Recepción Controlada de Mercancía con Kardex

### 12.1 Esquema de Base de Datos y Migración Incremental (`2026_11_04_000002_add_discounts_and_reception_to_purchases.php`)
* **Extensión de `purchases`:**
  * `discount` (`DECIMAL(10,2) DEFAULT 0.00`): Descuentos globales acumulados en la factura de compra.
  * `received_at` (`TIMESTAMP NULL`): Fecha y hora exacta de la recepción física de la mercadería.
  * `received_by` (`BIGINT NULL`, FK a `users` con `ON DELETE SET NULL`): Usuario responsable de verificar e ingresar el stock a bodega.
* **Extensión de `purchase_items`:**
  * `discount` (`DECIMAL(10,2) DEFAULT 0.00`): Descuento específico aplicado a la línea de producto.

### 12.2 Máquina de Estados de Compra y Recepción
* **`Purchase::STATUS_PENDING ('pending')` (Orden de Compra / En Espera):**
  * La orden de compra se registra como compromiso con el proveedor.
  * **Regla estricta:** NO se tocan existencias físicas ni se crean movimientos en `inventory_movements`.
* **`Purchase::STATUS_RECEIVED ('received' / 'completed')` (Mercancía Recibida):**
  * Indica que los artículos fueron entregados y verificados físicamente.
  * Ejecuta una transacción atómica con `lockForUpdate()` sobre cada producto, incrementa el stock, actualiza el costo unitario del catálogo y genera el movimiento de Kardex (`InventoryMovement::TYPE_IN`).
* **`Purchase::STATUS_CANCELLED ('cancelled')` (Orden Cancelada):**
  * Anula una orden pendiente antes de su arribo, sin desfasar el stock del catálogo.

### 12.3 Idempotencia y Protección contra Doble Recepción
* En `PurchaseController@receive`:
  * Verificación previa con `if ($purchase->isReceived())`: si la orden ya fue recibida, rechaza la operación e impide duplicar el stock.
  * Rechaza recepciones de órdenes que hayan sido canceladas previamente.
* En `PurchaseController@cancel`:
  * Verificación previa con `if ($purchase->isReceived())`: bloquea la cancelación de compras que ya ingresaron a inventario (protección de consistencia contable).

### 12.4 Liquidación Financiera con Descuentos
* Soporta descuentos por ítem y descuento global adicional.
* Fórmula implementada:
  $$\text{Subtotal Bruto} = \sum (\text{cantidad} \times \text{costo})$$
  $$\text{Descuento Total} = \sum \text{descuento}_{\text{ítem}} + \text{descuento}_{\text{global}}$$
  $$\text{Base Imponible (Neto)} = \max(0, \text{Subtotal Bruto} - \text{Descuento Total})$$
  $$\text{Impuesto IVA (12\%)} = \text{Base Imponible} \times 0.12$$
  $$\text{Total Factura} = \text{Base Imponible} + \text{Impuesto}$$

### 12.5 Rutas y Políticas
* `PATCH /purchases/{purchase}/receive` -> `PurchaseController@receive` (autorizado por `PurchasePolicy@receive`).
* `PATCH /purchases/{purchase}/cancel` -> `PurchaseController@cancel` (autorizado por `PurchasePolicy@cancel`).
* Restricción por rol: Solo administradores, propietarios o super admin pueden crear, recibir o anular compras.

### 12.6 Suite de Pruebas Automatizadas
* Archivo creado: `tests/Feature/PurchaseAndSupplyManagementTest.php` (4 tests, 35 aserciones, 100% PASS).
  * `test_pending_purchase_does_not_affect_inventory_until_explicitly_received`: Comprueba que compras pendientes no alteran stock ni generan Kardex hasta pulsar recepción.
  * `test_prevents_duplicate_reception_idempotency`: Comprueba que volver a recibir una compra genera error y no duplica existencias.
  * `test_cancelling_pending_purchase_and_protection_against_cancelling_received`: Comprueba que órdenes pendientes pueden anularse sin tocar stock, y que compras recibidas no pueden cancelarse arbitrariamente.
  * `test_discounts_and_financial_totals_calculation`: Valida el desglose de descuentos por producto, descuento global, base neta imponible y total.







