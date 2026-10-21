# Decisiones Técnicas — Fase 14
## Sistema Web — Escuela Oficial Rural Mixta Lo de Fuentes

**Módulo:** Seguridad, Privacidad y Hardening Global  
**Fecha:** 20 de octubre de 2026  
**Línea Base Previa:** DT-76 (Fase 13)  

---

### DT-77: Cabeceras HTTP de Hardening Global y Defensa Perimetral
- **Contexto:** Se requiere proteger a los usuarios de la plataforma institucional frente a ataques comunes basados en el navegador, como ataques de Clickjacking (UI redressing), explotación de confusión de tipos MIME (MIME-sniffing), y fugas accidentales de información de referencia hacia orígenes externos.
- **Decisión:**
  1. Implementar la inyección centralizada de cabeceras de seguridad en el pipeline de respuestas de la aplicación dentro de `App::handle()` mediante el método privado `applySecurityHeaders()`.
  2. Inyectar de manera transversal e incondicional en todas las respuestas HTTP:
     - `X-Content-Type-Options: nosniff`: Instruye al navegador a respetar estrictamente el tipo MIME declarado, neutralizando ataques donde archivos con contenido ejecutable son interpretados como scripts.
     - `X-Frame-Options: SAMEORIGIN`: Impide que la aplicación sea embebida en marcos (`<frame>`, `<iframe>`, `<object>`) de dominios externos, mitigando ataques de Clickjacking.
     - `Referrer-Policy: strict-origin-when-cross-origin`: Restringe el envío de URLs completas en la cabecera `Referer` hacia sitios externos, enviando únicamente el origen en peticiones HTTPS hacia otros dominios.
- **Justificación:** Cumple con las mejores prácticas de defensa en profundidad (RNF-03 y RNF-08) sin impacto en el rendimiento ni alteraciones al flujo de los controladores.

---

### DT-78: Control Estricto de No-Almacenamiento en Caché del Navegador en Rutas Privadas y de Autenticación
- **Contexto:** En equipos compartidos de uso escolar o administrativo (por ejemplo, computadoras del laboratorio o secretaría), las páginas autenticadas o con datos personales sensibles podrían quedar almacenadas en el historial/caché local del navegador, permitiendo a usuarios posteriores presionar el botón "Atrás" y visualizar información confidencial tras un cierre de sesión.
- **Decisión:**
  1. Evaluar en el despachador central `App::handle()` el prefijo de la URI solicitada.
  2. Para todas las rutas administrativas (`/administracion*`), rutas del portal docente (`/docente*`), pantallas de autenticación (`/login`, `/logout`) y flujos de restablecimiento de contraseña (`/recuperar-contrasena`, `/restablecer-contrasena`), aplicar cabeceras HTTP anti-caché:
     - `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`
     - `Pragma: no-cache`
- **Justificación:** Asegura la confidencialidad de la información de estudiantes, nóminas, asistencias y permisos (RNF-04), garantizando que los navegadores no almacenen páginas privadas en discos compartidos o memoria caché volátil.

---

### DT-79: Mitigación de Fuga de Rutas Físicas del Servidor en Módulos de Descarga de Archivos
- **Contexto:** Durante la auditoría de seguridad de los controladores de permisos (`AdminPermisoController` y `DocentePermisoController`), se identificó que en escenarios donde un archivo físico de soporte no existía en el almacenamiento local (`storage/permisos/`), el mensaje de error o la traza devuelta al cliente contenía la ruta absoluta del sistema de archivos local (`C:\Users\...`).
- **Decisión:**
  1. Reemplazar cualquier mención a rutas físicas o mensajes técnicos de bajo nivel en las respuestas HTTP de error por mensajes genéricos e institucionales:
     `"El documento solicitado no se encuentra disponible."`
  2. Registrar la discrepancia física internamente a través de `Logger::error()`, preservando la trazabilidad operativa en bitácoras protegidas sin exponer jamás la topología interna del servidor al usuario.
- **Justificación:** Previene la divulgación involuntaria de rutas locales (Information Disclosure / Path Leakage), fortaleciendo la seguridad perimetral de la aplicación.

---

### DT-80: Política Auxiliar de Expiración de Sesión por Inactividad Prolongada
- **Contexto:** Se requiere garantizar que sesiones autenticadas abandonadas en terminales del centro educativo no permanezcan abiertas indefinidamente, complementando la configuración del runtime de PHP con un control en la capa de aplicación.
- **Decisión:**
  1. En `AuthService::attempt()`, al iniciar sesión exitosamente, registrar la marca de tiempo de actividad inicial:
     `Session::set('auth_last_activity', time())`.
  2. En `AuthService::user()`, durante cada verificación de identidad en tiempo de ejecución:
     - Comprobar la diferencia entre `time()` y `auth_last_activity`.
     - Si la diferencia supera el umbral de 7200 segundos (2 horas continuas sin peticiones), revocar la sesión automáticamente mediante `$this->logout()`, registrar el evento en el log y retornar `null`.
     - Si la sesión se encuentra activa y vigente, renovar `Session::set('auth_last_activity', time())`.
- **Justificación:** Mitiga el riesgo de secuestro de sesión física por abandono de terminales en instalaciones educativas, asegurando el principio de menor privilegio en el tiempo (RNF-03).

---

### DT-81: Validación Integral, Aislamiento Horizontal y Cierre Definitivo de RNF-04 (Privacidad)
- **Contexto:** El requerimiento no funcional RNF-04 (*Privacidad: Datos personales accesibles solo a usuarios autorizados*) requería una auditoría transversal y validación integral antes de transicionar de su estado provisional con evidencia parcial hacia su aprobación definitiva.
- **Decisión:**
  1. Auditar transversalmente los módulos del sistema implementados en Fases 1 a 13 para verificar la erradicación total de vulnerabilidades de Referencia Directa Insegura a Objetos (IDOR):
     - **Portal Docente:** Nómina de estudiantes restringida exclusivamente a secciones formalmente asignadas; historial de asistencia derivado unívocamente del docente autenticado sin aceptar `id_docente` de cliente; solicitudes de permiso accesibles únicamente por el docente emisor; marcas de lectura de notificaciones aisladas por usuario destinatario.
     - **Consulta Pública Escolar:** Mínima exposición verificada en el comprobante de inscripción y consulta pública (exclusión de teléfonos, correos y fechas de nacimiento de encargados y menores).
     - **Reportes Administrativos:** Exclusión estricta de coordenadas geográficas personales de docentes en el reporte de asistencia (RNF-04 / RF-29).
  2. Tras verificar el cumplimiento mediante 32 pruebas específicas en `tests/run_tests_fase14.php`, transicionar formalmente el estado de RNF-04 a **VALIDADO** en la Matriz de Trazabilidad.
- **Justificación:** Asegura el cumplimiento exhaustivo del marco legal y de privacidad institucional sobre datos de menores y personal docente.
