# Matriz de Pruebas Automatizadas — Fase 3

## Entorno de Verificación

- **Sistema Operativo:** Windows NT 10.0 x64
- **Runtime:** PHP 8.2.12 CLI (ZTS Visual C++ 2019 x64)
- **Base de Datos:** MySQL Community Server 8.0.42 (Puerto local 3307)
- **Driver PDO:** `pdo_mysql`
- **Script Ejecutor:** `tests/run_tests_fase3.php`
- **Fecha de Verificación:** 2026-09-28

---

## Resultados Detallados de las Pruebas (18/18)

| ID | Prueba | Procedimiento ejecutado | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|---|
| **T3-01** | Login válido ADMINISTRADOR | Envío de `POST /login` con credenciales `admin.demo` / `Admin.2026!` y token CSRF válido. | HTTP 302 hacia `/administracion`, sesión creada con rol `ADMINISTRACION` / `ADMINISTRADOR`. | Status 302 -> `/administracion`, usuario autenticado `admin.demo` (Rol: `ADMINISTRACION`). | **OK** |
| **T3-02** | Login válido DOCENTE | Envío de `POST /login` con credenciales `docente.demo` / `Docente.2026!` y token CSRF válido. | HTTP 302 hacia `/docente`, sesión creada con rol `DOCENTE`. | Status 302 -> `/docente`, usuario autenticado `docente.demo` (Rol: `DOCENTE`). | **OK** |
| **T3-03** | Usuario inexistente | Envío de `POST /login` con usuario inexistente `usuario_fantasma_xyz_999`. | Acceso rechazado con HTTP 401 y mensaje genérico "Credenciales inválidas." | Status 401 con mensaje genérico "Credenciales inválidas." sin sesión creada. | **OK** |
| **T3-04** | Contraseña incorrecta | Envío de `POST /login` para `admin.demo` con contraseña incorrecta. | Acceso rechazado con HTTP 401 y mensaje genérico "Credenciales inválidas." | Status 401 con mensaje genérico "Credenciales inválidas." sin revelar existencia de usuario. | **OK** |
| **T3-05** | Usuario inactivo | Envío de `POST /login` para usuario `inactivo.demo` con `estado = 'INACTIVO'`. | Acceso rechazado con HTTP 401 y mensaje genérico sin iniciar sesión. | Status 401: Usuario inactivo rechazado sin iniciar sesión. | **OK** |
| **T3-06** | Regeneración de sesión | Comparación del ID de sesión antes y después del inicio de sesión exitoso. | Identificador de sesión regenerado (`idBefore != idAfter`). | Antes: `"f68f662236d5af8ae9fd0b4510aab917"` \| Después: `"86abbc847065554bf801f1e55f306ae6"` (Cambió: SÍ). | **OK** |
| **T3-07** | Ruta privada sin autenticación | Solicitud `GET /administracion` y `GET /docente` sin sesión previa iniciada. | Redirección controlada HTTP 302 hacia `/login`. | Rutas `/administracion` y `/docente` redirigen a `/login` con HTTP 302 sin sesión previa. | **OK** |
| **T3-08** | ADMINISTRADOR accede a admin | Solicitud `GET /administracion` con sesión activa de `admin.demo`. | Acceso concedido con HTTP 200 y vista de área administrativa. | Status 200: Vista "Área administrativa — sesión válida" renderizada correctamente. | **OK** |
| **T3-09** | DOCENTE accede a docente | Solicitud `GET /docente` con sesión activa de `docente.demo`. | Acceso concedido con HTTP 200 y vista de portal docente. | Status 200: Vista "Portal docente — sesión válida" renderizada correctamente. | **OK** |
| **T3-10** | DOCENTE intenta ruta admin | Solicitud `GET /administracion` con sesión activa de `docente.demo`. | Acceso denegado con HTTP 403 Forbidden y vista de error controlada. | HTTP 403 Forbidden emitido con vista controlada "Acceso Denegado". | **OK** |
| **T3-11** | ADMINISTRADOR ante ruta docente (política) | Solicitud `GET /docente` con sesión activa de `admin.demo`. | Acceso denegado con HTTP 403 Forbidden bajo política de mínimo privilegio. | HTTP 403 emitido conforme a la política de mínimo privilegio (área docente reservada a docentes). | **OK** |
| **T3-12** | Cierre de sesión (Logout) | Envío de `POST /logout` con token CSRF y posterior intento de acceso a `/administracion`. | Redirección 302, sesión destruida e imposibilidad de reingreso a rutas privadas. | Logout ejecutó redirección 302, destruyó sesión y bloqueó acceso posterior a `/administracion`. | **OK** |
| **T3-13** | Manipulación manual de rol | Intento de inyectar variables de rol por cookie o parámetro en petición de usuario docente. | Bloqueo con HTTP 403 mediante revalidación en base de datos. | HTTP 403: El backend valida el rol contra base de datos en tiempo real; manipulación rechazada. | **OK** |
| **T3-14** | Protección CSRF en login/logout | Pruebas de `POST /login` sin token, con token corrupto y con token válido. | Bloqueo HTTP 403 sin token o con token falso; procesado con token válido. | POST sin token: 403 \| POST con token inválido: 403 \| POST con token válido: 302. | **OK** |
| **T3-15** | Usuario desactivado con sesión previa | Desactivación en BD de un usuario con sesión abierta y posterior solicitud privada. | Revocación inmediata de sesión y redirección a `/login`. | Usuario desactivado en BD fue bloqueado en la siguiente petición y su sesión fue revocada (HTTP 302 -> `/login`). | **OK** |
| **T3-16** | Confidencialidad de contraseñas | Inspección de vistas generadas, logs en disco y respuestas HTTP. | Cero contraseñas en claro o hashes BCrypt expuestos. | Ningún secreto ni hash BCrypt presente en vistas, logs o respuestas. | **OK** |
| **T3-17** | Regresión completa de Fase 2 | Ejecución automatizada de la suite de Fase 2 (`Phase2TestSuite`). | 16 pruebas ejecutadas y 16 exitosas (0 fallos). | 16/16 pruebas de Fase 2 ejecutadas con 16 OK y 0 fallos. | **OK** |
| **T3-18** | Regresión completa de Fase 1 | Recreación y validación funcional del modelo relacional de Fase 1 en BD temporal aislada. | 18 pruebas automatizadas ejecutadas con 18 exitosas (0 fallos). | 18/18 pruebas de Fase 1 ejecutadas con 18 exitosas y 0 fallidas en BD temporal aislada. | **OK** |

---

## Resumen Cuantitativo

- **Total de pruebas ejecutadas:** 18
- **Pruebas exitosas (OK):** 18 (100%)
- **Pruebas fallidas (FALLO):** 0 (0%)
- **Resultado general:** **TODAS LAS PRUEBAS DE FASE 3 SUPERADAS SATISFACTORIAMENTE (18/18)**
