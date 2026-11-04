# Preparación para Producción y Despliegue en Vercel — PuntoÁgil SaaS (Módulo 20)

Este documento constituye la guía técnica verificable de preparación para producción (**Módulo 20**) de **PuntoÁgil Cloud POS**, basada en el código real del repositorio, la auditoría de seguridad (**Módulo 17**), las pruebas integrales (**Módulo 18**) y la validación fiscal de Nicaragua (**Módulo 19**). Incluye además la arquitectura y pasos exactos para desplegar el proyecto en **Vercel**.

---

## 1. Lista de Comprobación de Producción (Checklist Verificable)

| Categoría | Requisito Técnico | Estado Actual | Evidencia / Ubicación en el Proyecto |
| :--- | :--- | :--- | :--- |
| **Seguridad y Secretos** | Archivo `.env` y claves privadas excluidos de Git | **VERIFICADO** | [`.gitignore`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/.gitignore) y [`.vercelignore`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/.vercelignore) excluyen `.env*`, `/storage/*.key` y `/vendor`. `git ls-files` confirma que `.env` no está rastreado. |
| **Dependencias** | Auditoría de vulnerabilidades en paquetes PHP (`composer audit`) | **VERIFICADO (0 CVEs)** | Actualizado `laravel/framework` (`v12.71.0`), `dompdf/dompdf` (`v3.1.6`) y componentes `symfony/*` (`v7.4.23`). `composer audit` reporta **0 vulnerabilidades**. |
| **Cabeceras HTTP y HTTPS** | `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `HSTS` y `TrustProxies` | **VERIFICADO** | [`SecurityHeaders.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/app/Http/Middleware/SecurityHeaders.php), [`bootstrap/app.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/bootstrap/app.php) (`trustProxies(at: '*')`) y [`AppServiceProvider.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/app/Providers/AppServiceProvider.php) (`URL::forceScheme('https')`). |
| **Sesiones y Cookies** | Cookies `HttpOnly`, `SameSite=lax`, `Secure` en producción y protección CSRF | **VERIFICADO** | [`config/session.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/config/session.php) (`http_only=true`, `same_site=lax`, `secure=true` en `production`, driver `database`). |
| **Autenticación y Rate Limiting** | Bloqueo por fuerza bruta en login, recuperación de clave e invitaciones | **VERIFICADO** | [`LoginController.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/app/Http/Controllers/Auth/LoginController.php) (`RateLimiter` 5 intentos/min por email+IP) y middleware `throttle` en [`routes/web.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/routes/web.php). |
| **Aislamiento Multiempresa y RLS** | Aislamiento en aplicación (`BusinessScope` + Policies) y `ROW LEVEL SECURITY` en Supabase | **VERIFICADO (31/31 tablas)** | [`IdentifyTenant.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/app/Http/Middleware/IdentifyTenant.php), [`BusinessScope.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/app/Models/Scopes/BusinessScope.php) y migración [`2026_11_04_000007_harden_supabase_rls_and_postgrest_security.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/database/migrations/2026_11_04_000007_harden_supabase_rls_and_postgrest_security.php). |
| **Licencias y Suscripciones** | Plan único de \$30.00 USD/mes, control de cuotas y bloqueo de empresas/licencias suspendidas en servidor | **VERIFICADO** | [`SubscriptionController.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/app/Http/Controllers/Admin/SubscriptionController.php), [`IdentifyTenant.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/app/Http/Middleware/IdentifyTenant.php) y [`SubscriptionWebhookController.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/app/Http/Controllers/SubscriptionWebhookController.php). |
| **Validación Fiscal Nicaragua** | Soporte Córdoba (`NIO` `C$`), Régimen General (`IVA 15%` Ley 822), Cuota Fija (`0%`), Cédula/RUC, avisos sin validez fiscal DGI e inmutabilidad | **VERIFICADO** | [`NicaraguaFiscalHelper.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/app/Support/NicaraguaFiscalHelper.php), [`SaleController.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/app/Http/Controllers/SaleController.php), [`Sale.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/app/Models/Sale.php) y [`SaleReturn.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/app/Models/SaleReturn.php). |
| **Infraestructura Serverless Vercel** | Entrypoint `/api/index.php`, `vercel.json`, almacenamiento efímero `/tmp/storage` y activos compilados en `public/build` | **VERIFICADO** | [`api/index.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/api/index.php), [`vercel.json`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/vercel.json), [`.vercelignore`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/.vercelignore) y [`bootstrap/app.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/bootstrap/app.php). |
| **Credenciales Externas de Producción** | Servidor SMTP transaccional, Google OAuth de producción y Webhook Secret de pasarela de pagos | **PENDIENTE DE INFRAESTRUCTURA EXTERNA** | Requiere cuentas y llaves propias del operador antes de habilitar cobros automáticos o correos reales en producción. |
| **Facturación Fiscal Oficial DGI** | Pie de Imprenta Fiscal / Homologación SACFI DGI Nicaragua (si el contribuyente emite factura fiscal desde el sistema) | **PENDIENTE DE TRÁMITE LEGAL EXTERNO** | El sistema emite **Comprobantes Internos de Caja** con leyenda expresa. La emisión con validez de Crédito Fiscal requiere autorización de la DGI Nicaragua. |

---

## 2. Variables de Entorno para Producción

Ninguna credencial de producción debe almacenarse en Git. En el servidor de producción o en el panel de **Vercel (Project Settings -> Environment Variables)**, configure las siguientes variables:

### 2.1 Variables Obligatorias (Core & Base de Datos Supabase)
```env
APP_NAME="PuntoÁgil"
APP_ENV=production
APP_KEY="base64:GENERAR_CON_PHP_ARTISAN_KEY_GENERATE"
APP_DEBUG=false
APP_URL="https://su-dominio-en-vercel.vercel.app"
FORCE_HTTPS=true

# Conexión a Supabase PostgreSQL (Pooler Transaccional PgBouncer en puerto 6543 para Serverless/Vercel)
DB_CONNECTION=pgsql
DB_HOST="aws-0-us-east-2.pooler.supabase.com"
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME="postgres.SU_PROJECT_REF"
DB_PASSWORD="SU_PASSWORD_SEGURO_DE_SUPABASE"
DB_SSLMODE=require

# Sesiones, Caché y Logs compatibles con Serverless (Vercel)
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
LOG_LEVEL=warning
```

### 2.2 Variables Opcionales (Servicios Externos)
```env
# Correo Transaccional (Recuperación de contraseñas e invitaciones a negocios)
MAIL_MAILER=smtp
MAIL_HOST="smtp.proveedor.com"
MAIL_PORT=587
MAIL_USERNAME="usuario-smtp"
MAIL_PASSWORD="password-smtp"
MAIL_FROM_ADDRESS="no-reply@puntoagil.com"
MAIL_FROM_NAME="PuntoÁgil SaaS"

# Inicio de Sesión con Google OAuth 2.0 (Si no se define, el sistema desactiva el botón de forma segura)
GOOGLE_CLIENT_ID=""
GOOGLE_CLIENT_SECRET=""
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

# Pasarela de Pagos para Suscripciones (Opcional - Verificación HMAC de Webhooks)
STRIPE_KEY=""
STRIPE_SECRET=""
STRIPE_WEBHOOK_SECRET=""
MERCADOPAGO_PUBLIC_KEY=""
MERCADOPAGO_ACCESS_TOKEN=""
MERCADOPAGO_WEBHOOK_SECRET=""
```

---

## 3. Arquitectura de Base de Datos (Supabase PostgreSQL), Copias de Seguridad y Restauración

### 3.1 Modo de Conexión en Producción y Vercel
- **Operación Web / Serverless (Puerto `6543` — Transaction Pooler PgBouncer):**
  - Ideal para **Vercel Functions**, ya que reutiliza conexiones efímeras sin agotar el límite de conexiones de PostgreSQL.
  - Conecta con el rol `postgres.<project_ref>` bajo TLS (`DB_SSLMODE=require`).
- **Ejecución de Migraciones y Mantenimiento (Puerto `5432` — Session Pooler / Direct):**
  - Se recomienda usar el puerto `5432` (o ejecutar `php artisan migrate --force` desde consola administrativa) cuando se apliquen cambios de esquema DDL.
- **Seguridad en Capa de Datos (RLS y PostgREST):**
  - Las **31 tablas** del esquema `public` tienen `ROW LEVEL SECURITY` (`rowsecurity = true`) activo y los roles públicos de PostgREST (`anon` y `authenticated`) tienen permisos revocados sobre las tablas internas, impidiendo accesos directos por API pública de Supabase.

### 3.2 Copias de Seguridad (Backups) y Restauración
1. **Backups Automáticos en Supabase:**
   - Habilitar backups diarios automáticos y *Point-in-Time Recovery (PITR)* en el panel de Supabase (*Database -> Backups*).
2. **Respaldo Manual Previo a Despliegues (`pg_dump`):**
   ```bash
   pg_dump "postgresql://postgres.<project_ref>:<password>@aws-0-us-east-2.pooler.supabase.com:5432/postgres?sslmode=require" \
     --format=custom --no-owner --no-acl --file="backup_puntoagil_$(date +%Y%m%d_%H%M).dump"
   ```
3. **Procedimiento de Restauración (`pg_restore`):**
   ```bash
   pg_restore --clean --if-exists --no-owner --no-acl \
     --dbname="postgresql://postgres.<project_ref>:<password>@aws-0-us-east-2.pooler.supabase.com:5432/postgres?sslmode=require" \
     "backup_puntoagil_YYYYMMDD_HHMM.dump"
   ```
4. **Conservación Legal Tributaria (Nicaragua):**
   - Conforme a los **Arts. 43 y 103 numeral 3 del Código Tributario de Nicaragua (Ley No. 562)**, los respaldos de base de datos que contienen ventas (`sales`), ítems (`sale_items`), devoluciones (`sale_returns`), movimientos de inventario (`inventory_movements`), cierres de caja (`cash_shifts`) y auditoría (`audit_logs`) deben conservarse por un mínimo de **4 años** (recomendado **6 años**).

---

## 4. Guía Paso a Paso para Subir y Usar PuntoÁgil en Vercel

El proyecto ya cuenta con la configuración lista para **Vercel Serverless PHP**:
- [`api/index.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/api/index.php): Punto de entrada serverless que inicializa `/tmp/storage` (vistas compiladas, caché temporal y logs) y delega al front controller [`public/index.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/public/index.php).
- [`vercel.json`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/vercel.json): Configura el runtime `vercel-php@0.7.3`, sirve los activos estáticos desde `/public/build/**` y redirige las rutas dinámicas a `/api/index.php`, preconfigurando las rutas de caché en `/tmp` y `LOG_CHANNEL=stderr`.
- [`.vercelignore`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/.vercelignore): Excluye `/vendor`, `/node_modules`, `/tests` y archivos `.env` locales para que Vercel instale únicamente dependencias de producción (`--no-dev`) manteniendo la función rápida y ligera.
- [`bootstrap/app.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/bootstrap/app.php): Detecta automáticamente la variable `VERCEL=1` y redirige `storage_path()` a `/tmp/storage` para evitar errores de sistema de archivos de solo lectura (`Read-only file system`).

### Pasos para Desplegar en Vercel:

1. **Compilar Activos Frontend (si se modificaron estilos/JS) y Subir a GitHub/GitLab:**
   ```bash
   npm run build
   git add .
   git commit -m "chore: ready for vercel deployment"
   git push origin fase_5_Final
   ```
2. **Importar el Proyecto en Vercel:**
   - Ingrese a [https://vercel.com/new](https://vercel.com/new) e importe el repositorio Git de **PuntoÁgil**.
   - En **Framework Preset**, seleccione **Other** (Vercel detectará automáticamente `vercel.json`).
3. **Configurar las Variables de Entorno en Vercel:**
   - En la sección **Environment Variables** del proyecto en Vercel, agregue:
     - `APP_KEY`: Copie el valor de su `APP_KEY` (ej. `base64:...`) o genere uno nuevo con `php artisan key:generate --show`.
     - `APP_URL`: La URL asignada por Vercel (ej. `https://punto-agil.vercel.app`).
     - `DB_CONNECTION`: `pgsql`
     - `DB_HOST`: `aws-0-us-east-2.pooler.supabase.com`
     - `DB_PORT`: `6543`
     - `DB_DATABASE`: `postgres`
     - `DB_USERNAME`: Su usuario del pooler de Supabase (`postgres.<project_ref>`)
     - `DB_PASSWORD`: La contraseña de su base de datos en Supabase
     - `DB_SSLMODE`: `require`
4. **Desplegar (`Deploy`):**
   - Haga clic en **Deploy**. Vercel descargará los paquetes de Composer con `vercel-php@0.7.3` y publicará la aplicación en menos de 2 minutos.
   - Verifique el estado de salud accediendo a `https://<su-dominio>.vercel.app/up` (debe responder HTTP 200).

---

## 5. Procedimiento de Despliegue Controlado, Monitoreo y Reversión (Rollback)

### 5.1 Despliegue de Nuevas Versiones
1. Ejecutar la batería de pruebas automatizadas antes de publicar:
   ```bash
   php artisan test
   ```
2. Generar respaldo preventivo de la base de datos en Supabase.
3. Aplicar migraciones pendientes de forma no destructiva:
   ```bash
   php artisan migrate --force
   ```
4. Hacer *push* a la rama conectada con Vercel (o promover el *Preview Deployment* a *Production* en el panel de Vercel).

### 5.2 Reversión ante Fallos (Rollback)
1. **Reversión Inmediata de Aplicación en Vercel:**
   - En el panel de Vercel -> **Deployments**, seleccione el despliegue estable anterior y haga clic en **Instant Rollback**. El tráfico vuelve a la versión previa en segundos sin tiempo de inactividad.
2. **Reversión de Migración de Base de Datos (solo si la migración no fue destructiva):**
   ```bash
   php artisan migrate:rollback --step=1 --force
   ```

### 5.3 Monitoreo y Registros
- **Healthcheck HTTP:** Ruta nativa `/up` configurada en [`bootstrap/app.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/bootstrap/app.php).
- **Logs de Errores en Tiempo Real:** En Vercel, todos los eventos enviados a `LOG_CHANNEL=stderr` se visualizan en vivo en la pestaña **Logs / Runtime Logs** del proyecto, sin almacenar archivos `.log` locales.
- **Bitácora de Auditoría de Negocio y Seguridad:** Consultable en `/admin/audit-logs` por Propietarios y Super Admins, con exportación a CSV e inmutabilidad garantizada a nivel de modelo ([`AuditLog.php`](file:///d:/Proyecto_PuntoAgil/Punto_Agil/app/Models/AuditLog.php)).
