# Medidas de Seguridad Aplicadas — Fase 3

## 1. Gestión de Contraseñas (Hashing y Verificación)

En cumplimiento de las directivas del proyecto:
- **Algoritmo:** BCrypt nativo de PHP mediante `password_hash($password, PASSWORD_BCRYPT)` con costo estándar.
- **Verificación:** `password_verify($password, $user->clave_hash)`.
- **Cero Texto Plano:** Las contraseñas nunca se almacenan ni se transmiten en texto plano en la base de datos o logs.
- **Cero Exposición de Hashes:** La entidad `User::toArray()` excluye explícitamente `clave_hash`. Las vistas y respuestas JSON nunca incluyen el hash criptográfico.

---

## 2. Mitigación contra Ataques Comunes

### 2.1 Protección contra Cross-Site Request Forgery (CSRF)
- Se implementó la clase `App\Core\Csrf`.
- Cada sesión activa genera un token criptográfico impredecible de 32 bytes (`random_bytes(32)`).
- Todo formulario con método `POST` (`/login`, `/logout`) incluye el token mediante el helper `csrf_field()`.
- El middleware `VerifyCsrfToken` intercepta y valida el token usando `hash_equals()` para evitar ataques basados en tiempo. Solicitudes sin token o con token alterado son rechazadas con HTTP 403.

### 2.2 Protección contra Cross-Site Scripting (XSS)
- Todas las salidas dinámicas en las vistas (`login.php`, `admin/index.php`, `docente/index.php`, `errors/403.php` y layout `main.php`) aplican la función global `e()`, que ejecuta `htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- Las cookies de sesión están marcadas con `httponly = true`, impidiendo que scripts maliciosos puedan leerlas mediante `document.cookie`.

### 2.3 Mitigación de Session Fixation
- Tras la validación exitosa de credenciales en `POST /login`, el sistema ejecuta `session_regenerate_id(true)`, asignando un nuevo identificador de sesión y purgando el anterior.

### 2.4 Prevención de Enumeración de Usuarios
- Los intentos de inicio de sesión fallidos (por usuario inexistente, contraseña errónea o cuenta inactiva) devuelven exactamente la misma respuesta:
  ```text
  Credenciales inválidas.
  ```
- No se informa si el nombre de usuario existe en la base de datos.

### 2.5 Revalidación Autorativa contra Manipulación de Roles
- El rol y estado del usuario no se confían ciegamente a la sesión de PHP. En cada ciclo de solicitud privada, `AuthService::user()` consulta la base de datos física para comprobar que el usuario siga `ACTIVO` y obtener su rol vigente, frustrando intentos de manipulación client-side de cookies o parámetros.

### 2.6 Prevención de Open Redirect
- Tras el inicio de sesión exitoso, el sistema no acepta parámetros de redirección arbitrarios (`return`, `redirect`, `next`). Las redirecciones son determinadas internamente en el backend según el rol verificado (`/administracion` o `/docente`).
