# Suite de Pruebas — Fase 4

**Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes**  
**Fase 4: Administración Básica**  
**Responsable Técnico:** Antigravity — Desarrollador Principal  
**Fecha:** Septiembre 2026 (Actualizado tras corrección controlada)

---

## 1. Cobertura de Pruebas Automatizadas (27/27)

El archivo ejecutable `tests/run_tests_fase4.php` cubre de forma integral los 27 casos de prueba obligatorios correspondientes a la Fase 4:

| Identificador | Caso de Prueba | Tipo de Validación | Resultado |
|---|---|---|:---:|
| **T4-01** | Administrador accede a rutas administrativas | RBAC / HTTP 200 en las 8 rutas de administración | **OK** |
| **T4-02** | Docente no puede acceder a administración | RBAC / HTTP 403 Forbidden para rol DOCENTE | **OK** |
| **T4-03** | Usuario no autenticado es redirigido a login | Middleware Authenticate / HTTP 302 hacia `/login` | **OK** |
| **T4-04** | Intentos de mutación (POST) sin CSRF son rechazados | Middleware VerifyCsrfToken / HTTP 403 | **OK** |
| **T4-05** | Creación atómica de docente | Transaccional / Inserción en `usuarios` y `docentes` | **OK** |
| **T4-06** | Rollback atómico ante fallo en creación de docente | Atomicidad / Transacción revertida sin huérfanos | **OK** |
| **T4-07** | Edición de datos de docente | Persistencia / Modificación exitosa en BD | **OK** |
| **T4-08** | Desactivación de docente | Lógica / Cambio de estado a INACTIVO sin pérdida | **OK** |
| **T4-09** | Creación de estudiante con datos válidos | Persistencia / Registro en `estudiantes` | **OK** |
| **T4-10** | Validación de campos obligatorios en estudiante | Dominio / Rechazo con mensajes específicos | **OK** |
| **T4-11** | Creación de período escolar válido | Dominio / Registro en `periodos_escolares` | **OK** |
| **T4-12** | Rechazo de segundo período escolar activo simultáneo | Integridad / Restricción `uk_periodos_escolares_un_activo` | **OK** |
| **T4-13** | Creación de grado con orden de progresión válido | Dominio / Restricción de orden jerárquico positivo | **OK** |
| **T4-14** | Asociación de grado a período escolar existente | Relacional / Inserción en `grados_periodos` | **OK** |
| **T4-15** | Rechazo de grado duplicado en el mismo período | Integridad / Restricción `uk_grados_periodos` | **OK** |
| **T4-16** | Creación de sección con capacidad válida | Dominio / Capacidad entera positiva persistida | **OK** |
| **T4-17** | Rechazo de capacidad inválida en sección | Validación / Rechazo de 0, negativo, string y decimal | **OK** |
| **T4-18** | Capacidad mayor a 0 persistida y editable | Persistencia / Actualización de cupos en BD | **OK** |
| **T4-19** | Creación de aula y validación de código único | Integridad / Restricción `uk_aulas_codigo` | **OK** |
| **T4-20** | Integridad referencial: rechazo de FKs inexistentes | Relacional / Rechazo preventivo ante IDs foráneos | **OK** |
| **T4-21** | Prevención XSS en renderizado administrativo | Seguridad / Neutralización de payloads `<script>` | **OK** |
| **T4-22** | Protección contra asignación masiva (mass assignment) | Seguridad / Whitelist estricto en controladores/servicios | **OK** |
| **T4-23** | Regresión completa de Fase 3 (Autenticación y Roles) | Regresión / 19 de 19 pruebas de Fase 3 exitosas | **OK** |
| **T4-24** | Regresión completa de Fase 2 (Arquitectura Base) | Regresión / 16 de 16 pruebas de Fase 2 exitosas | **OK** |
| **T4-25** | Regresión completa de Fase 1 (Base de Datos) | Regresión / 18 de 18 pruebas relacionales en BD temporal | **OK** |
| **T4-26** | Flujo administrativo real mediante HTTP | HTTP real / Servidor efímero CLI y cliente cURL | **OK** |
| **T4-27** | Seguridad de credencial inicial docente | Seguridad / Hashing BCrypt, no exposición, no pwd universal | **OK** |

---

## 2. Detalle de Pruebas Agregadas (F4-02 y F4-03)

### T4-26 — Flujo administrativo real mediante HTTP
Validación end-to-end con servidor PHP CLI efímero (`php -S 127.0.0.1:<puerto> -t public`) y cURL:
- **(A) Login de administrador:** GET `/login` (captura de cookie de sesión y token CSRF), POST `/login` con `admin.demo` / `Admin.2026!` $\to$ `HTTP 302` con `Location: /administracion`. GET `/administracion` con cookie de sesión $\to$ `HTTP 200` y renderizado de módulos administrativos.
- **(B) Creación administrativa real vía POST:** GET `/administracion/aulas/crear` (extracción de token CSRF), POST `/administracion/aulas/crear` con `codigo = AUL-HTTP-01`, `nombre = Aula HTTP 01`, `ubicacion_referencia = Edificio Central, Modulo A`, `activa = 1` $\to$ `HTTP 302` a `/administracion/aulas`. Verificación en base de datos: registro existente con `id_aula`, `codigo = AUL-HTTP-01` y `activa = 1`.
- **(C) Mutación POST sin CSRF / con CSRF inválido:** POST `/administracion/aulas/crear` con token falsificado $\to$ `HTTP 403 Forbidden`. Verificación en base de datos: `AUL-HTTP-BAD` no existe en `aulas` (CERO mutaciones).
- **(D) Intento de acceso administrativo por Docente:** Login HTTP con `docente.demo`, seguido de GET `/administracion` $\to$ `HTTP 403 Forbidden` (bloqueo RBAC estricto).
- **(E) Intento de acceso administrativo por usuario no autenticado:** GET `/administracion` sin cookies $\to$ `HTTP 302` hacia `/login`.
- **(F) Edición administrativa real vía HTTP:** GET `/administracion/aulas/editar?id=<id>` (extracción de token CSRF), POST `/administracion/aulas/editar?id=<id>` con `nombre = Aula HTTP Modificada`, `ubicacion_referencia = Edificio Central, Modulo B` $\to$ `HTTP 302`. Verificación en base de datos: campos actualizados.
- **(G) Cambio de estado real vía HTTP toggle:** POST `/administracion/aulas/estado?id=<id>` con `activa = 0` y token CSRF $\to$ `HTTP 302`. Verificación en base de datos: `activa = 0`.
- **(H) Limpieza y finalización:** Eliminación de entidades de prueba en MySQL, eliminación de archivos temporales de cookies y terminación forzosa y limpia del proceso del servidor temporal (`taskkill`).

### T4-27 — Seguridad de credencial inicial docente
- **(1) Ausencia de contraseña universal estática:** El sistema exige que el administrador ingrese una contraseña individual para cada docente al momento del registro (mínimo 8 caracteres); no existe ninguna contraseña por defecto preconfigurada en código.
- **(2) Hashing seguro con BCrypt:** La contraseña viaja al servicio `DocenteService`, donde se aplica `password_hash($password, PASSWORD_BCRYPT)`. En base de datos se almacena exclusivamente el hash con prefijo `$2y$`. Verificación con `password_verify()` es satisfactoria con la clave asignada y rechaza claves inválidas o universales.
- **(3) No almacenamiento en texto plano:** Se verifica en la tabla `usuarios` que el campo `clave_hash` no coincide ni contiene la contraseña en texto plano, ni tampoco ninguna otra columna.
- **(4) No exposición en vistas ante error de validación:** Si un formulario de registro o edición de docente falla la validación (por ejemplo, correo inválido devolviendo `HTTP 422`), el controlador `AdminDocenteController` elimina explícitamente el campo `password` del arreglo `$old` (`unset($old['password'])`), garantizando que la vista devuelta no incluya la contraseña ni en inputs ocultos ni en el código HTML.
- **(5) No exposición en archivos de registro (logs):** Se verifica que el archivo de logs del sistema (`storage/logs/app.log`) no contiene la contraseña en texto plano ni el hash.

---

## 3. Resumen Global de Regresiones

- **Fase 1 (Base de Datos):** 18/18 pruebas aprobadas en base de datos temporal aislada.
- **Fase 2 (Infraestructura):** 16/16 pruebas aprobadas en proceso independiente.
- **Fase 3 (Autenticación y Roles):** 19/19 pruebas aprobadas en proceso independiente.
- **Fase 4 (Administración Básica):** 27/27 pruebas aprobadas satisfactoriamente.
- **Total de pruebas en suite combinada:** 80 pruebas ejecutadas, **80 exitosas, 0 fallidas**.
