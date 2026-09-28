# Middlewares y Autorización por Roles — Fase 3

## 1. Arquitectura del Pipeline de Middlewares

El enrutador `App\Core\Router` fue adaptado para encadenar middlewares de forma secuencial previo a la invocación de la acción del controlador:

```text
Request ────► [ VerifyCsrfToken ] ────► [ Authenticate ] ────► [ RequireRole ] ────► Controller Action ────► Response
```

Cada middleware implementa el contrato `App\Middleware\MiddlewareInterface`:

```php
interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
```

---

## 2. Catálogo de Middlewares Implementados

### 2.1 `VerifyCsrfToken`
- **Ubicación:** `app/Middleware/VerifyCsrfToken.php`
- **Propósito:** Validar la presencia del token anti-CSRF en solicitudes `POST`.
- **Comportamiento:**
  - Extrae `_token` del cuerpo de la petición o la cabecera `X-CSRF-TOKEN`.
  - Verifica mediante `Csrf::validate($token)` usando `hash_equals()`.
  - Si es inválido o ausente: Interrumpe la ejecución y retorna **HTTP 403 Forbidden** con la vista `views/errors/403.php` ("Petición Expirada").
  - Si es válido: Delega el control a `$next($request)`.

### 2.2 `Authenticate`
- **Ubicación:** `app/Middleware/Authenticate.php`
- **Propósito:** Asegurar que el visitante posea una sesión válida y activa.
- **Comportamiento:**
  - Consulta `auth()->check()`.
  - Si no está autenticado: Almacena un mensaje flash y retorna una redirección **HTTP 302** hacia `/login`.
  - Si está autenticado: Continúa con `$next($request)`.

### 2.3 `RequireRole`
- **Ubicación:** `app/Middleware/RequireRole.php`
- **Propósito:** Autorizar la ejecución únicamente a usuarios con un rol institucional específico.
- **Comportamiento:**
  - Recibe el rol requerido en su constructor (`new RequireRole('ADMINISTRADOR')` o `new RequireRole('DOCENTE')`).
  - Obtiene el usuario autenticado vía `auth()->user()`.
  - Comprueba pertenencia al rol mediante `$user->hasRole($requiredRole)`.
  - Si no cumple con el rol: Emite **HTTP 403 Forbidden** con la vista `views/errors/403.php` ("Acceso Denegado").
  - Si cumple con el rol: Invoca `$next($request)`.

---

## 3. Política Institucional de Roles y Acceso (T3-11)

Se implementó el principio de **Mínimo Privilegio**:

1. **Rutas Administrativas (`/administracion`):**
   - Requiere rol: `ADMINISTRADOR` (o su sinónimo relacional `ADMINISTRACION`).
   - Un usuario con rol `DOCENTE` que intente ingresar recibe **HTTP 403 Forbidden**.
2. **Portal Docente (`/docente`):**
   - Requiere rol: `DOCENTE`.
   - Política definida: El portal docente contiene información y funciones exclusivas de la docencia activa (asignaciones de grado, marcaje de asistencia, permisos). Por política de mínimo privilegio, un usuario con rol exclusivamente `ADMINISTRADOR` no debe operar el portal docente sin credencial docente; ante un intento de acceso, el sistema emite **HTTP 403 Forbidden** (verificado en la prueba `T3-11`).
