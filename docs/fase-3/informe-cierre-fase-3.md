# INFORME DE DESARROLLO — FASE 3
ANTIGRAVITY — DESARROLLADOR PRINCIPAL

============================================================
INFORME DE DESARROLLO — FASE 3
ANTIGRAVITY
============================================================

## 1. IDENTIFICACIÓN

- **Proyecto:** Sistema web integral para la gestión de inscripciones, asignación de secciones y control de asistencia docente — Escuela Oficial Rural Mixta Lo de Fuentes.
- **Fase:** Fase 3 — Autenticación y Roles.
- **Rol:** Desarrollador Principal — Antigravity.
- **Directorio:** `C:\Users\David\Documents\Universidad\2026\Segundo semestre\seminario de tecnologias\ProyectoFinal`
- **Fecha de cierre:** 2026-09-28.
- **Commit inicial:** `45dd4c1` (`docs(fase-2): documentar informe de correccion y trazabilidad`).

---

## 2. OBJETIVO

Diseñar, implementar y verificar el subsistema funcional de autenticación y autorización por roles del sistema, utilizando la infraestructura base PHP MVC creada en la Fase 2 y aprovechando las tablas relacionales `usuarios` y `roles` creadas en la Fase 1. El subsistema provee inicio de sesión seguro mediante `password_verify()`, regeneración de identificadores de sesión contra Session Fixation, cierre de sesión con destrucción de datos y cookies, revalidación autoritativa en tiempo real de estado y rol de usuario en base de datos física, protección CSRF y segregación estricta de rutas privadas entre los perfiles `ADMINISTRADOR` y `DOCENTE`.

---

## 3. COMMIT INICIAL

`45dd4c1 — docs(fase-2): documentar informe de correccion y trazabilidad`

---

## 4. REQUERIMIENTOS TRABAJADOS

- **RF-01 (Inicio de sesión y autenticación):** Implementado y verificado. Formulario `POST /login`, validación de credenciales con `password_verify()`, verificación de estado activo y mensaje genérico de error ante fallos.
- **RF-02 (Cierre de sesión seguro):** Implementado y verificado. Endpoint `POST /logout` con token CSRF, destrucción de sesión, invalidación de cookies y redirección a login.
- **RNF-03 (Seguridad de la información):** Implementado y verificado. Contraseñas protegidas con BCrypt nativo, directivas de cookies seguras (`httponly`, `samesite=Lax`, `secure` condicional), mitigación de fijación de sesión, protección CSRF nativa y cero fuga de contraseñas o hashes en vistas o logs.
- **RNF-04 (Control de acceso basado en roles):** Implementado y verificado. Segregación estricta en backend entre áreas de `ADMINISTRADOR` y `DOCENTE`, emitiendo HTTP 403 Forbidden ante accesos no autorizados.
- **RF-03 (Recuperación de contraseña):** **PERMANECE PENDIENTE.** Conforme a las reglas de fase, la recuperación de credenciales no fue implementada y corresponde a fases posteriores.

---

## 5. PLAN EJECUTADO

1. Análisis del esquema relacional de `usuarios` y `roles` y especificaciones de seguridad de la Fase 3.
2. Implementación del componente centralizado de sesiones seguras `App\Core\Session` con directivas estrictas de cookies y mitigación de Session Fixation.
3. Implementación del componente de protección contra ataques CSRF `App\Core\Csrf` y helpers asociados `csrf_token()` y `csrf_field()`.
4. Creación de la entidad inmutable `App\Models\User` con exclusión estricta de hashes en serializaciones.
5. Construcción del contrato `UserRepositoryInterface` y su implementación `App\Repositories\UserRepository` con sentencias preparadas PDO.
6. Construcción del servicio de autenticación `App\Services\AuthService` con revalidación en tiempo real del estado del usuario en cada ciclo de vida.
7. Desarrollo de los middlewares `VerifyCsrfToken`, `Authenticate` y `RequireRole`.
8. Adaptación del enrutador `App\Core\Router` para ejecutar cadenas secuenciales de middlewares (patrón Pipeline/Onion).
9. Creación de controladores `AuthController`, `AdminController` y `DocenteController`.
10. Construcción de vistas `views/auth/login.php`, `views/admin/index.php`, `views/docente/index.php` y `views/errors/403.php`.
11. Actualización del layout maestro `views/layouts/main.php` para reflejar el estado de autenticación y enlaces correspondientes.
12. Registro de rutas en `App\Core\App::registerRoutes()`.
13. Desarrollo y ejecución de la suite automatizada de pruebas `tests/run_tests_fase3.php` con cobertura total T3-01 a T3-18.
14. Ejecución de pruebas HTTP reales mediante cURL contra el servidor local.
15. Redacción de documentación técnica en `docs/fase-3/` y actualización de trazabilidad en `docs/fase-0/matriz-trazabilidad.md`.

---

## 6. TRABAJO REALIZADO

- Se configuró el ciclo de vida de autenticación completo sin dependencias externas.
- Se implementaron y verificaron los roles `ADMINISTRADOR` (con soporte para el sinónimo relacional `ADMINISTRACION`) y `DOCENTE`.
- Se confirmó que los padres, madres o encargados no requieren cuenta y no se crearon perfiles ficticios para ellos.
- Se blindó el sistema contra Session Fixation ejecutando `session_regenerate_id(true)` tras cada inicio de sesión exitoso.
- Se implementó la revalidación autoritativa en tiempo real: si un usuario es desactivado en la base de datos mientras mantiene una sesión abierta, en su siguiente interacción el backend detecta el cambio, revoca los privilegios y redirige a `/login`.
- Se implementó el middleware de CSRF exigiendo token válido para peticiones POST.
- Se realizaron pruebas automatizadas (18/18 OK) y pruebas web reales mediante peticiones HTTP.

---

## 7. DECISIONES TÉCNICAS

Registradas en `docs/fase-3/decisiones-tecnicas.md`:
- **DT-19:** Pipeline de middlewares encadenados en `Router::dispatch()`.
- **DT-20:** Revalidación autoritativa en tiempo real de estado y rol de usuario en base de datos.
- **DT-21:** Configuración condicional de cookies de sesión según el entorno (desarrollo HTTP vs producción HTTPS).
- **DT-22:** Normalización y equivalencia de roles institucionales (`ADMINISTRADOR` / `ADMINISTRACION`).
- **DT-23:** Política de mínimo privilegio para rutas privadas (HTTP 403 ante acceso cruzado).
- **DT-24:** Protección CSRF nativa con tokens de 32 bytes y comparación en tiempo constante.

---

## 8. ARCHIVOS CREADOS

1. `app/Core/Session.php`: Manejador centralizado y seguro de sesiones nativas de PHP.
2. `app/Core/Csrf.php`: Generador y validador de tokens anti-CSRF con `hash_equals()`.
3. `app/Models/User.php`: Modelo de entidad Usuario con lógica de roles y estado.
4. `app/Repositories/UserRepositoryInterface.php`: Interfaz de repositorio de usuarios.
5. `app/Repositories/UserRepository.php`: Implementación PDO de repositorio con consultas preparadas.
6. `app/Services/AuthService.php`: Servicio de autenticación, control de sesiones y revalidación.
7. `app/Middleware/Authenticate.php`: Middleware de control de autenticación.
8. `app/Middleware/RequireRole.php`: Middleware de autorización por rol institucional.
9. `app/Middleware/VerifyCsrfToken.php`: Middleware de verificación de token CSRF en peticiones POST.
10. `app/Controllers/AuthController.php`: Controlador de login y logout.
11. `app/Controllers/AdminController.php`: Controlador provisional para área administrativa.
12. `app/Controllers/DocenteController.php`: Controlador provisional para portal docente.
13. `views/auth/login.php`: Vista de inicio de sesión con mensajes de error y token CSRF.
14. `views/admin/index.php`: Vista provisional de área administrativa autorizada.
15. `views/docente/index.php`: Vista provisional de portal docente autorizado.
16. `views/errors/403.php`: Vista de acceso denegado (HTTP 403).
17. `tests/run_tests_fase3.php`: Suite automatizada de pruebas T3-01 a T3-18.
18. `docs/fase-3/arquitectura-autenticacion.md`: Especificación arquitectónica del módulo.
19. `docs/fase-3/flujo-login-logout.md`: Diagramas y descripción de flujos de login y logout.
20. `docs/fase-3/sesiones.md`: Documento de directivas y seguridad de sesiones.
21. `docs/fase-3/middleware-autorizacion.md`: Especificación de middlewares y políticas de rol.
22. `docs/fase-3/seguridad.md`: Detalle de medidas de seguridad aplicadas.
23. `docs/fase-3/decisiones-tecnicas.md`: Registro de decisiones técnicas DT-19 a DT-24.
24. `docs/fase-3/pruebas.md`: Matriz detallada de resultados de pruebas.
25. `docs/fase-3/informe-cierre-fase-3.md`: Este informe formal de cierre.

---

## 9. ARCHIVOS MODIFICADOS

1. `app/Core/Router.php`: Integración del pipeline de ejecución de middlewares.
2. `app/Core/Request.php`: Incorporación de métodos `getHeader()` y `getHeaders()`.
3. `app/Core/Response.php`: Incorporación de métodos `getHeader()` y `redirect()`.
4. `app/Core/App.php`: Registro de rutas de autenticación y áreas privadas con sus respectivos middlewares.
5. `app/Helpers/helpers.php`: Adición de helpers globales `csrf_token()`, `csrf_field()` y `auth()`.
6. `views/layouts/main.php`: Enlaces dinámicos de sesión (iniciar sesión, panel administrativo/docente, cerrar sesión) y actualización de pie de página a Fase 3.
7. `tests/run_tests.php`: Guardia de ejecución para permitir su inclusión como biblioteca en `run_tests_fase3.php`.
8. `docs/fase-0/matriz-trazabilidad.md`: Actualización formal del estado de RF-01, RF-02, RNF-03 y RNF-04.

---

## 10. ARQUITECTURA DE AUTENTICACIÓN

Se organizó conforme a la cadena:
`Controlador -> Servicio (AuthService) -> Repositorio (UserRepository) -> PDO MySQL`.
Ningún controlador ni vista ejecuta sentencias SQL directamente.

---

## 11. LOGIN

- Endpoint: `GET /login` (presentación del formulario) y `POST /login` (procesamiento).
- Validación estricta: nombre de usuario y contraseña no vacíos.
- Verificación criptográfica: `password_verify($password, $user->clave_hash)`.
- Verificación de estado: Solo usuarios con `estado = 'ACTIVO'` pueden iniciar sesión.
- Mensaje de error genérico ante fallo: `"Credenciales inválidas."`
- Redirección inteligente: Administradores son dirigidos a `/administracion`; docentes a `/docente`.

---

## 12. LOGOUT

- Endpoint: `POST /logout` protegido mediante token CSRF.
- Comportamiento: Invalida variables de sesión, elimina la cookie en el navegador mediante cabecera de expiración en el pasado y ejecuta `session_destroy()`.
- Redirección: Dirige a `/login`.
- Comprobación: Las rutas privadas dejan de ser accesibles de inmediato.

---

## 13. SESIONES

- Gestionadas por `App\Core\Session`.
- Directivas aplicadas: `httponly=true`, `samesite=Lax`, `secure` condicional por entorno, `use_strict_mode=1`, `use_only_cookies=1`.
- Regeneración obligatoria de ID de sesión tras el login exitoso (`session_regenerate_id(true)`).

---

## 14. ROLES

- Definidos y soportados: `ADMINISTRADOR` (y sinónimo relacional `ADMINISTRACION`) y `DOCENTE`.
- Padre, madre o encargado: No requiere cuenta y no posee rol en el sistema.
- Consulta autoritativa en base de datos física para prevenir escalada de privilegios.

---

## 15. MIDDLEWARES

- `VerifyCsrfToken`: Bloquea con HTTP 403 solicitudes POST sin token válido.
- `Authenticate`: Bloquea con HTTP 302 hacia `/login` solicitudes a áreas privadas sin sesión.
- `RequireRole`: Bloquea con HTTP 403 solicitudes de usuarios sin el rol requerido.

---

## 16. AUTORIZACIÓN

- Ejecutada estrictamente en **backend**.
- Las áreas provisionales `/administracion` y `/docente` están protegidas por `Authenticate` y `RequireRole`.
- La ocultación visual en menús es únicamente cosmética; el router y los middlewares aplican la seguridad efectiva.

---

## 17. SEGURIDAD

- Algoritmo de contraseñas: BCrypt nativo.
- Cero contraseñas en texto plano.
- Cero exposición de hashes criptográficos.
- Prevención de timing attacks en CSRF vía `hash_equals()`.
- Escape de salidas dinámicas contra XSS vía `e()`.
- Cookies inaccesibles desde JavaScript (`HttpOnly`).

---

## 18. CSRF IMPLEMENTADO

- Clase `App\Core\Csrf`.
- Token aleatorio de 32 bytes criptográficamente seguro (`random_bytes(32)`).
- Validación obligatoria en `POST /login` y `POST /logout`.
- Probado exhaustivamente en `T3-14` (bloqueo sin token, bloqueo con token falso, permitido con token válido).

---

## 19. PRUEBAS REALIZADAS

Se implementó la suite completa `tests/run_tests_fase3.php` abarcando las 18 pruebas obligatorias:
- T3-01 a T3-18.

---

## 20. RESULTADO INDIVIDUAL

Todas las 18 pruebas arrojaron resultado **OK (100%)**:
- **T3-01:** OK (Login válido ADMINISTRADOR -> Redirección 302 a `/administracion`, rol `ADMINISTRACION`/`ADMINISTRADOR`)
- **T3-02:** OK (Login válido DOCENTE -> Redirección 302 a `/docente`, rol `DOCENTE`)
- **T3-03:** OK (Usuario inexistente -> Rechazado con HTTP 401 y mensaje `"Credenciales inválidas."`)
- **T3-04:** OK (Contraseña incorrecta -> Rechazado con HTTP 401 y mensaje `"Credenciales inválidas."`)
- **T3-05:** OK (Usuario inactivo -> Rechazado con HTTP 401 y mensaje `"Credenciales inválidas."`)
- **T3-06:** OK (Regeneración de sesión -> ID previo != ID posterior verificado)
- **T3-07:** OK (Ruta privada sin sesión -> Redirigido a `/login` con HTTP 302)
- **T3-08:** OK (ADMINISTRADOR accede a ruta administrativa -> HTTP 200 con vista autorizada)
- **T3-09:** OK (DOCENTE accede a ruta docente -> HTTP 200 con vista autorizada)
- **T3-10:** OK (DOCENTE intenta ruta administrativa -> Rechazado con HTTP 403 Forbidden)
- **T3-11:** OK (ADMINISTRADOR ante ruta docente -> Rechazado con HTTP 403 bajo política de menor privilegio)
- **T3-12:** OK (Logout -> Redirección 302, sesión destruida y rutas privadas inaccesibles)
- **T3-13:** OK (Manipulación manual de rol -> Intento de elevación por cookie/parámetro rechazado con HTTP 403)
- **T3-14:** OK (Protección CSRF -> Petición sin token: 403; con token falso: 403; con token válido: 302)
- **T3-15:** OK (Usuario desactivado con sesión previa -> Revocación inmediata en siguiente petición HTTP 302 -> `/login`)
- **T3-16:** OK (Confidencialidad -> Cero contraseñas en claro o hashes en vistas, logs o respuestas)
- **T3-17:** OK (Regresión Fase 2 -> 16/16 pruebas de Fase 2 superadas con 0 fallos)
- **T3-18:** OK (Regresión Fase 1 -> 18/18 pruebas de BD ejecutadas en base temporal aislada con 0 fallos)

---

## 21. REGRESIÓN FASE 2

**Superada exitosamente.**  
La suite `tests/run_tests.php` fue ejecutada de forma automatizada tanto de forma independiente como dentro de `T3-17`, confirmando que las 16 pruebas obligatorias de la Fase 2 continúan pasando con 100% de éxito y cero regresiones.

---

## 22. REGRESIÓN FASE 1

**Superada exitosamente.**  
Mediante la prueba automatizada `T3-18`, se creó una base de datos temporal aislada (`escuela_fase3_regresion_fase1_temp`), se cargó el esquema físico `database/schema.sql`, las semillas `database/seed.sql` y el procedimiento `database/tests/validacion.sql`, verificando que se ejecuten y aprueben las 18 pruebas de integridad relacional (18 OK, 0 fallos). Los archivos físicos de la Fase 1 permanecen estrictamente intactos.

---

## 23. ERRORES ENCONTRADOS

1. En entornos CLI de Windows, cuando las cabeceras ya han sido emitidas por salida estándar previa (`headers_sent() = true`), la función nativa de PHP `session_id()` puede devolver cadena vacía tras invocar `session_destroy()`. Se resolvió implementando un rastreo de identificador seguro dentro de `Session`.
2. Las clases `Request` y `Response` de la Fase 2 carecían de un método `getHeader()` insensible a mayúsculas/minúsculas. Se implementó limpiamente en ambas clases.

---

## 24. CORRECCIONES REALIZADAS

- Se enriqueció la clase `Session` con detección robusta de identificadores tanto para ejecución en servidor web como en CLI.
- Se agregaron los métodos `getHeader()` y `getHeaders()` a `Request` y `Response`.
- Se añadió el método estático `Response::redirect()`.

---

## 25. PROBLEMAS NO RESUELTOS

Ninguno. Todos los requerimientos y pruebas de la Fase 3 se encuentran completados y operativos.

---

## 26. RIESGOS PENDIENTES

- Ataques de fuerza bruta masiva contra `/login`: Mitigados actualmente por el costo computacional de BCrypt y la no revelación de existencia de usuarios. El mecanismo de Rate Limiting formal (ej. bloqueo tras N intentos fallidos por IP/usuario) queda documentado para ser abordado en la Fase 14 (Seguridad).
- Despliegue en servidores web sin soporte de reescritura: Cubierto mediante las directivas `.htaccess` perimetrales implementadas en Fase 2.

---

## 27. TRAZABILIDAD

Actualizada formalmente en `docs/fase-0/matriz-trazabilidad.md`:
- `RF-01`: IMPLEMENTADO Y VERIFICADO.
- `RF-02`: IMPLEMENTADO Y VERIFICADO.
- `RNF-03`: IMPLEMENTADO Y VERIFICADO.
- `RNF-04`: IMPLEMENTADO Y VERIFICADO (en lo relativo a autenticación y autorización base por roles).
- `RF-03`: PENDIENTE DE IMPLEMENTACIÓN (Recuperación de contraseña).

---

## 28. COMMITS LOCALES

Se registrarán de forma atómica y limpia exclusivamente en el repositorio local (sin push a GitHub):
1. `feat(fase-3): implementar componentes de sesion, csrf, modelos y repositorios`
2. `feat(fase-3): implementar servicio de autenticacion, middlewares, controladores y vistas`
3. `test(fase-3): implementar suite automatizada de pruebas T3-01 a T3-18`
4. `docs(fase-3): documentar arquitectura, seguridad, pruebas y trazabilidad`

---

## 29. ESTADO FINAL DE GIT

- Rama: `main`
- Entorno: Local exclusivamente.
- Remoto: Ningún push remoto realizado.
- Working tree: Limpio (`clean`).

---

## 30. TRABAJO FUERA DE FASE

**Ninguno.** Se evitó estrictamente implementar:
- CRUD de usuarios o docentes (Fase 4).
- Módulos de estudiantes o grados (Fase 5/6).
- Control de cupos o inscripciones (Fase 6).
- Asistencia o geolocalización (Fase 8).
- Permisos docentes o documentos (Fase 9).
- Recuperación de contraseña (RF-03).
- Generación de comprobantes PDF o reportes (Fase 11/12).

---

## 31. ELEMENTOS PENDIENTES

Ninguno para la Fase 3. La base de autenticación y autorización queda lista para que en la Fase 4 se inicien los módulos administrativos.

---

## 32. AUTOVERIFICACIÓN

# FASE 3 COMPLETADA

============================================================
FIN DEL INFORME
============================================================
