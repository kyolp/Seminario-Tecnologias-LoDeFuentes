# Auditoría Integral de Seguridad, Privacidad y Hardening Global — Fase 14
## Sistema Web — Escuela Oficial Rural Mixta Lo de Fuentes

**Fecha:** 20 de octubre de 2026  
**Responsable:** Antigravity — Desarrollador Principal  
**Alcance:** Auditoría transversal estática y dinámica de seguridad sobre el código base (Fases 0 a 13).  

---

## 1. Resumen Ejecutivo

Durante la Fase 14 se llevó a cabo una auditoría integral sobre las capas de presentación, negocio, persistencia, enrutamiento, autenticación y manejo de archivos del sistema. El objetivo consistió en identificar vectores de riesgo, robustecer la arquitectura frente a ataques comunes (OWASP Top 10) y validar el cumplimiento definitivo del requerimiento no funcional **RNF-04 (Privacidad)**.

Se identificaron y remediaron 5 hallazgos de seguridad (SEC-01 a SEC-05) y se documentaron 2 evaluaciones conformes (SEC-06 y SEC-07). Todas las mitigaciones fueron integradas sin alterar el esquema relacional (`database/schema.sql` permanece intacto) y sin degradar la funcionalidad preexistente, confirmándose mediante 32 pruebas específicas de seguridad y 380 pruebas de regresión acumuladas con 100% de éxito.

---

## 2. Matriz Consolidada de Hallazgos y Remediación

| Identificador | Severidad | Categoría | Componente Afectado | Estado | Mitigación Técnica |
|:---:|:---:|---|---|:---:|---|
| **SEC-01** | **ALTO** | Defensa Perimetral / Cabeceras HTTP | `app/Core/App.php` | **REMEDIADO** | Inyección global de `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN` y `Referrer-Policy: strict-origin-when-cross-origin` (DT-77). |
| **SEC-02** | **MEDIO** | Privacidad / Almacenamiento en Caché | `app/Core/App.php` | **REMEDIADO** | Control de `Cache-Control: no-store, no-cache, must-revalidate` y `Pragma: no-cache` en rutas privadas y de autenticación (DT-78). |
| **SEC-03** | **MEDIO** | Divulgación de Información / Path Leak | `AdminPermisoController`, `DocentePermisoController` | **REMEDIADO** | Reemplazo de excepciones con rutas absolutas locales por mensaje institucional genérico y registro interno en bitácora (DT-79). |
| **SEC-04** | **BAJO** | Trazabilidad / Saneamiento de Logs | `app/Core/Logger.php` | **REMEDIADO** | Ampliación de palabras clave filtradas (`cui`, `credencial`, `authorization`) con máscara `********`. |
| **SEC-05** | **BAJO** | Gestión de Sesiones / Inactividad | `app/Services/AuthService.php` | **REMEDIADO** | Implementación de política de expiración de sesión por inactividad a los 7200 segundos (2 horas) (DT-80). |
| **SEC-06** | **INFORMATIVO** | Inyección SQL / XSS / CSRF | Controladores, Vistas y Repositorios | **CONFORME** | 100% de consultas utilizan PDO prepared statements; 100% de vistas escapan con `e()`; validación estricta de tokens CSRF. |
| **SEC-07** | **INFORMATIVO** | Privacidad y Aislamiento (RNF-04) | Portales, Reportes y Consulta Pública | **CONFORME** | Erradicación total de IDOR; mínima exposición de datos de menores; protección de coordenadas geográficas docentes (DT-81). |

---

## 3. Detalle de Hallazgos y Remediaciones

### SEC-01 (ALTO): Ausencia de Cabeceras HTTP Globales de Seguridad
- **Vulnerabilidad:** Las respuestas HTTP no enviaban directivas perimetrales estándar del navegador, permitiendo potencialmente ataques de Clickjacking (`<iframe>` desde orígenes externos) o explotación de MIME-sniffing si un atacante subiera un archivo con contenido manipulado.
- **Remediación:** Se implementó `App::applySecurityHeaders()` en el ciclo de vida de cada respuesta, asegurando que toda petición contenga:
  ```http
  X-Content-Type-Options: nosniff
  X-Frame-Options: SAMEORIGIN
  Referrer-Policy: strict-origin-when-cross-origin
  ```
- **Prueba de Verificación:** `T14-27` y `T14-31`.

### SEC-02 (MEDIO): Almacenamiento en Caché de Navegador en Vistas Privadas
- **Vulnerabilidad:** Navegadores en terminales compartidas podían almacenar en memoria o disco local las vistas administrativas, nóminas de estudiantes y reportes de asistencia tras el cierre de sesión.
- **Remediación:** Se configuró la inyección condicional de directivas anti-caché en `App::applySecurityHeaders()` para las rutas `/administracion*`, `/docente*`, `/login`, `/logout`, `/recuperar-contrasena` y `/restablecer-contrasena`:
  ```http
  Cache-Control: no-store, no-cache, must-revalidate, max-age=0
  Pragma: no-cache
  ```
- **Prueba de Verificación:** `T14-28`.

### SEC-03 (MEDIO): Fuga de Rutas Físicas del Servidor en Error de Descarga de Permisos
- **Vulnerabilidad:** Al fallar la descarga de un soporte físico inexistente en disco, el controlador retornaba un mensaje de error que exponía la ruta absoluta del sistema de archivos local (`C:\Users\...\storage\permisos\...`).
- **Remediación:** Se sanitizó el flujo de error en `AdminPermisoController::descargarDocumento()` y `DocentePermisoController::descargarDocumento()`, retornando un mensaje neutral *"El documento solicitado no se encuentra disponible."* y registrando la anomalía de bajo nivel exclusivamente en el log seguro.
- **Prueba de Verificación:** `T14-07` y `T14-29`.

### SEC-04 (BAJO): Saneamiento Incompleto de Datos Sensibles en Logger
- **Vulnerabilidad:** El saneamiento automático de `Logger::interpolate()` enmascaraba contraseñas y tokens, pero no contemplaba identificadores personales sensibles como CUI (`cui`) o credenciales genéricas (`credencial`, `authorization`).
- **Remediación:** Se amplió la lista de patrones sensibles en `Logger::sanitizeContext()` para reemplazar de forma recursiva cualquier valor asociado a estas claves por `********`.
- **Prueba de Verificación:** `T14-30`.

### SEC-05 (BAJO): Ausencia de Control de Inactividad de Sesión en Capa de Aplicación
- **Vulnerabilidad:** Dependencia exclusiva de la configuración del runtime de PHP para la expiración de sesiones, pudiendo mantener sesiones abiertas indefinidamente en entornos donde `session.gc_maxlifetime` esté sobredimensionado.
- **Remediación:** Se agregó en `AuthService` el seguimiento de la marca de tiempo `auth_last_activity`. Si transcurren más de 7200 segundos (2 horas) sin actividad activa del usuario autenticado, la sesión se revoca forzosamente y se registra el evento en bitácora.
- **Prueba de Verificación:** `T14-18`.

### SEC-06 (INFORMATIVO): Mitigación Integral de Inyección SQL, XSS y CSRF
- **Evaluación:**
  1. **SQLi:** Todos los repositorios (`UserRepository`, `DocenteRepository`, `InscripcionRepository`, `AsistenciaRepository`, `PermisoRepository`, `NotificacionRepository`, `ReporteRepository`, `TokenRecuperacionRepository`) utilizan 100% de sentencias preparadas PDO con vinculación estricta de parámetros (`bindValue`/`execute`). Cero interpolaciones inseguras.
  2. **XSS:** Vistas públicas, administrativas y docentes aplican rigurosamente el helper `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
  3. **CSRF:** Toda mutación HTTP (POST/PUT/DELETE) pasa obligatoriamente por el middleware `VerifyCsrfToken`, rechazando peticiones sin token o con token alterado mediante HTTP 403.
- **Prueba de Verificación:** `T14-11` a `T14-15`.

### SEC-07 (INFORMATIVO): Validación Integral del Requerimiento RNF-04 (Privacidad)
- **Evaluación:**
  1. **IDOR:** Se auditó y comprobó que un docente no puede acceder a permisos ajenos (`T14-06`), no puede descargar archivos de otros usuarios (`T14-07`), no puede marcar como leídas notificaciones ajenas (`T14-08`), no puede consultar nóminas de secciones que no le están asignadas (`T14-09`), y sus consultas de asistencia ignoran parámetros client-side (`T14-10`).
  2. **Mínima Exposición:** El comprobante de inscripción y consulta pública no exponen números telefónicos, correos electrónicos ni fechas de nacimiento de encargados o menores (`T14-25`). El reporte administrativo de asistencia excluye coordenadas geográficas personales (`T14-26`).
- **Prueba de Verificación:** `T14-06` a `T14-10`, `T14-25`, `T14-26`.

---

## 4. Conclusión de la Auditoría

El sistema cumple plenamente con los estándares de seguridad defensiva, confidencialidad e integridad requeridos para la plataforma escolar. Todas las vulnerabilidades detectadas han sido mitigadas y el requerimiento **RNF-04** queda formalmente validado.
