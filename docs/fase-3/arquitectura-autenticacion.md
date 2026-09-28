# Arquitectura de Autenticación y Autorización — Fase 3

## 1. Visión General

La Fase 3 implementa el subsistema de autenticación, control de sesiones y autorización por roles para el prototipo institucional de la **Escuela Oficial Rural Mixta Lo de Fuentes**, integrándose de manera natural con la arquitectura MVC desacoplada desarrollada en la Fase 2 y aprovechando las tablas relacionales `usuarios` y `roles` creadas en la Fase 1.

El flujo arquitectónico sigue estrictamente la separación de responsabilidades:

```text
Petición HTTP (Navegador)
       │
       ▼
Front Controller (public/index.php)
       │
       ▼
Router + Middleware Pipeline
  ├── VerifyCsrfToken
  ├── Authenticate
  └── RequireRole (ADMINISTRADOR / DOCENTE)
       │
       ▼
Controlador (AuthController / AdminController / DocenteController)
       │
       ▼
Servicio (AuthService)
       │
       ▼
Repositorio (UserRepository)
       │
       ▼
Persistencia PDO (Base de Datos MySQL)
```

---

## 2. Componentes Principales

### 2.1 Entidad `User` (`app/Models/User.php`)
Modela de forma inmutable y tipada las columnas de `usuarios` y el nombre relacional de `roles`:
- Identidad: `id_usuario`, `id_rol`, `nombre_usuario`, `correo`.
- Seguridad: `clave_hash` (gestionada mediante `password_hash()` / `password_verify()`).
- Datos Personales: `nombres`, `apellidos`, `nombre_completo`.
- Estado: `estado` (`ACTIVO`, `INACTIVO`, `BLOQUEADO`).
- Métodos de autorización: `isActive()`, `hasRole(string $role)`, `isAdmin()`, `isDocente()`.
- Protección: El método `toArray()` excluye explícitamente `clave_hash` para evitar fugas inadvertidas a vistas o respuestas JSON.

### 2.2 Repositorio de Usuarios (`app/Repositories/UserRepository.php`)
Encapsula el acceso a la base de datos MySQL mediante sentencias preparadas nativas (`PDO::prepare()`):
- `findByUsername(string $username)`: Consulta relacional con `JOIN roles` para obtener el usuario y su rol.
- `findById(int $id)`: Consulta por clave primaria utilizada para la revalidación en tiempo real del estado de cuenta en cada solicitud.
- `updateLastAccess(int $userId)`: Actualiza la marca temporal `ultimo_acceso`.
- `updateStatus(int $userId, string $status)`: Soporte para actualización de estado del usuario.
- Cero consultas SQL dispersas en controladores o vistas.

### 2.3 Servicio de Autenticación (`app/Services/AuthService.php`)
Concentra la lógica de negocio de autenticación y control de acceso:
- `attempt(string $username, string $password)`: Valida credenciales, comprueba estado activo, verifica el hash de contraseña, regenera el ID de sesión contra fijación y actualiza la marca de acceso.
- `user()`: Revalida en cada llamada la vigencia del usuario en la base de datos física. Si el usuario fue desactivado o eliminado mientras tenía una sesión previa, revoca los privilegios y destruye la sesión inmediatamente (Reglas 14 y 15).
- `check()`: Retorna verdadero si hay un usuario activo con sesión válida.
- `logout()`: Destruye la sesión e invalida cookies de forma segura.

### 2.4 Gestión de Sesiones (`app/Core/Session.php`)
Capa centralizada para abstraer `$_SESSION`:
- Configuración de cookies: `httponly = true`, `samesite = Lax`, `secure` condicionado a HTTPS en producción para preservar la operatividad en desarrollo HTTP local.
- Regeneración criptográfica (`session_regenerate_id(true)` / fallback en CLI).
- Manejo de mensajes flash para retroalimentación al usuario.
- Destrucción segura invalidando la cookie del navegador.

### 2.5 Protección CSRF (`app/Core/Csrf.php`)
- Generación de tokens criptográficos mediante `random_bytes(32)`.
- Comparación en tiempo constante con `hash_equals()`.
- Helper global `csrf_field()` y `csrf_token()`.

### 2.6 Pipeline de Middlewares (`app/Middleware/`)
- `VerifyCsrfToken`: Intercepta peticiones POST verificando la presencia y validez del token.
- `Authenticate`: Impide el acceso a recursos protegidos sin sesión válida, redirigiendo a `/login`.
- `RequireRole`: Restringe el acceso evaluando el rol del usuario (`ADMINISTRADOR`, `DOCENTE`), respondiendo con HTTP 403 Forbidden y vista personalizada si los privilegios son insuficientes.

---

## 3. Delimitación de Roles Institucionales

Conforme al alcance oficial del proyecto:
1. **`ADMINISTRADOR` (o `ADMINISTRACION`):** Personal administrativo y directivo del establecimiento escolar. Accede al área `/administracion`.
2. **`DOCENTE`:** Personal docente de la escuela. Accede al portal `/docente`.
3. **Padre, madre o encargado:** **NO REQUIERE CUENTA.** La consulta y registro de inscripciones escolares no requiere autenticación en este sistema según las especificaciones institucionales. No se crearon roles ni cuentas para encargados.
