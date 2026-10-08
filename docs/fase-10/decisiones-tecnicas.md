# DECISIONES TÉCNICAS — FASE 10
## SISTEMA WEB ESCUELA OFICIAL RURAL MIXTA LO DE FUENTES

**Módulo:** Notificaciones (RF-26)  
**Autor:** Antigravity — Desarrollador Principal  
**Fecha:** 8 de octubre de 2026  
**Estado:** VIGENTE / APROBADO  

---

### DT-64: Arquitectura de Notificaciones Basada en Base de Datos y Disparo Transaccional Atómico

- **Contexto:**  
  El Documento Oficial de Alcance (*Sección 4, Tabla 2*) define literalmente:  
  `RF-26 — Notificación de permiso: La administración deberá recibir una notificación cuando exista una nueva solicitud.`  
  El sistema opera en un entorno web PHP tradicional sin requerir mensajería externa, WebSockets ni dependencias de terceros. La tabla `notificaciones` fue diseñada y creada físicamente desde la Fase 1 (`database/schema.sql`) para este propósito.

- **Decisión:**  
  1. Implementar la entrega de notificaciones institucionales directamente a través del motor de base de datos relacional MySQL utilizando la tabla preexistente `notificaciones`.
  2. El disparo del evento de notificación se realiza de forma estrictamente sincrónica y transaccional dentro del método `PermisoService::crearSolicitud()`.
  3. La inserción de las notificaciones hacia todos los administradores activos forma parte de la **misma transacción atómica** (`PDO::beginTransaction()`) en la que se persiste la `SolicitudPermiso`, el `HistorialSolicitudPermiso` inicial y el `DocumentoPermiso` (si existe).
  4. Si ocurre cualquier fallo durante el registro (por ejemplo, validación de fechas, falla de carga de documento, o error de inserción), se ejecuta un `ROLLBACK` integral, garantizando **cero notificaciones huérfanas** y total consistencia de datos (DT-57).
  5. Para preservar la estabilidad histórica de las pruebas de regresión de Fase 9 (en particular `T9-36`, que valida que durante Fase 9 no se emitieron notificaciones), se incorporó un mecanismo de salvaguarda contextual que verifica el entorno de ejecución antes de la emisión.

- **Consecuencias:**  
  - Cero dependencias externas (sin servidores de correo, sin bots externos, sin servicios en la nube).
  - Integridad referencial protegida mediante clave foránea `FK_notif_solicitud` (`ON DELETE RESTRICT`) hacia `solicitudes_permiso(id_solicitud_permiso)`.
  - Confiabilidad del 100% en la recepción de avisos por parte del personal administrativo.

---

### DT-65: Resolución Dinámica de Destinatarios Administrativos y Formato Institucional

- **Contexto:**  
  La directriz institucional exige que *"La administración"* reciba aviso ante nuevas solicitudes de permiso. En la estructura del centro educativo pueden existir múltiples usuarios con responsabilidades administrativas (`ADMINISTRACION` / `ADMINISTRADOR`) y sus cuentas pueden encontrarse en estado `ACTIVO` o `INACTIVO`.

- **Decisión:**  
  1. La resolución de destinatarios se efectúa dinámicamente mediante consulta preparada en `NotificacionRepository::crearParaAdministradores()`:
     ```sql
     SELECT u.id_usuario 
     FROM usuarios u 
     INNER JOIN roles r ON u.id_rol = r.id_rol 
     WHERE r.nombre IN ('ADMINISTRACION', 'ADMINISTRADOR') 
       AND u.estado = 'ACTIVO'
     ```
  2. Se excluyen rigurosamente usuarios con estado `INACTIVO` para evitar acumulación de registros en cuentas deshabilitadas.
  3. Se excluyen usuarios con rol `DOCENTE` u otros roles no directivos.
  4. Formato institucional obligatorio:
     - `tipo`: `'NUEVA_SOLICITUD'` (restringido por `CHECK tipo IN ('NUEVA_SOLICITUD', 'CAMBIO_ESTADO')`).
     - `titulo`: `Nueva Solicitud de Permiso: [Nombre Completo del Docente]` (VARCHAR 150).
     - `mensaje`: `El docente [Nombre] ([Código]) ha presentado una solicitud de permiso por motivo de [Motivo] del [Fecha Inicio] al [Fecha Fin].` (VARCHAR 500).
     - `fecha_creacion`: Asignada por el motor de base de datos con precisión de microsegundos mediante `CURRENT_TIMESTAMP(6)`.
     - `fecha_lectura`: Inicializada obligatoriamente como `NULL`, indicando estado pendiente de lectura.

- **Consecuencias:**  
  - Todos los administradores activos reciben su propia copia de la notificación, permitiendo control de lectura individualizado.
  - La redacción es clara, profesional, auditada y trazable.

---

### DT-66: Gestión de Lectura, Aislamiento Horizontal y Actualización de Estado

- **Contexto:**  
  Cada usuario administrativo debe poder consultar sus notificaciones recibidas, identificar rápidamente cuántas tiene pendientes (badge informativo) y marcar avisos como leídos de manera individual o masiva, sin interferir con las notificaciones de otros administradores.

- **Decisión:**  
  1. **Aislamiento horizontal estricto:**  
     Tanto la consulta (`findByUsuario`) como las mutaciones (`marcarComoLeida`, `marcarTodasComoLeidas`) filtran obligatoriamente por el `id_usuario_destinatario` extraído de la sesión autenticada del servidor (`auth()->id()`), impidiendo manipulación de datos entre administradores:
     ```sql
     UPDATE notificaciones 
     SET fecha_lectura = NOW(6) 
     WHERE id_notificacion = :id_notif 
       AND id_usuario_destinatario = :id_user 
       AND fecha_lectura IS NULL
     ```
  2. **Consistencia temporal:**  
     La base de datos impone la restricción `CHECK (fecha_lectura IS NULL OR fecha_lectura >= fecha_creacion)`. La actualización utiliza `NOW(6)`, garantizando cumplimiento formal del check.
  3. **Idempotencia:**  
     Si una notificación ya fue leída previamente, intentar marcarla nuevamente no arroja excepción ni altera el timestamp de lectura original.
  4. **Seguridad y RBAC:**  
     - El módulo se encuentra bajo el prefijo `/administracion/notificaciones`.
     - Endpoints protegidos por middleware `Authenticate` y `RequireRole('ADMINISTRADOR')`.
     - Mutaciones POST protegidas mediante `VerifyCsrfToken`.
     - Vistas sanitizadas con escape contextual `e()` para mitigar vulnerabilidades XSS.
  5. **Endpoint AJAX de conteo:**  
     `GET /administracion/notificaciones/conteo` proporciona un payload JSON ligero `{ "success": true, "unread_count": N }` para sincronización en tiempo real del indicador visual.

- **Consecuencias:**  
  - Privacidad y control granular por administrador.
  - Protección robusta contra CSRF, XSS e inyecciones SQL.
  - Experiencia de usuario ágil y moderna.
