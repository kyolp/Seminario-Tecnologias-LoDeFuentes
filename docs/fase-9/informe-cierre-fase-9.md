# INFORME DE DESARROLLO Y CIERRE — FASE 9

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 9 — Gestión de Permisos Docentes  
**Rol:** ANTIGRAVITY — DESARROLLADOR PRINCIPAL  
**Fecha de Cierre:** Octubre 2026  
**Requisitos Satisfechos:** RF-24 (Solicitud de permiso), RF-25 (Documento de respaldo), RF-27 (Aprobación/rechazo), RF-28 (Estado de solicitud), RNF-06 (Evidencia de auditoría e inmutabilidad de transiciones), RN-DOC-05 a RN-DOC-07, CA-DOC-07 a CA-DOC-09  
**Requisito Excluido por Alcance:** RF-26 (Notificación de permiso) — PENDIENTE PARA FASE 10  
**Estado:** IMPLEMENTACIÓN COMPLETADA Y VERIFICADA (46/46 OK — 100% GREEN)

---

## 1. RESUMEN EJECUTIVO

La Fase 9 implementa de forma íntegra, segura y auditada el subsistema de **Gestión de Permisos Docentes**, permitiendo la solicitud, justificación documental, revisión, resolución y auditoría de permisos de ausencia laboral para el personal docente titular y la administración del plantel.

El módulo se diseñó e implementó bajo estándares estrictos de seguridad defensiva, transaccionalidad atómica y gobierno de datos:

1. **Gestión Integral de Solicitudes Docentes (RF-24):** Formulario docente protegido con validación de fechas (no vacías, formato ISO `YYYY-MM-DD`), motivos predefinidos institucionales, descripción obligatoria detallada y asociación autoritativa del docente derivada exclusivamente de la sesión activa (`auth_user_id`), descartando cualquier parámetro manipulable por el cliente.
2. **Carga y Seguridad Rigurosa de Archivos de Soporte (RF-25, DT-63):** Capacidad opcional de adjuntar comprobantes médicos o constancias institucionales (formatos permitidos: PDF, PNG, JPG, JPEG, WEBP; límite máximo: 5 MB). Implementación de validación dual (extensión permitida y detección de tipo MIME real por magic bytes mediante `finfo(FILEINFO_MIME_TYPE)`), escaneo preventivo contra scripts ejecutables camuflados (`<?php`, `<script>`), almacenamiento fuera del document root (`storage/permisos/`) con nombres generados con CSPRNG de 32 bytes aleatorios (`permiso_<64-hex>.<ext>`) para mitigar Path Traversal, Parameter Tampering y colisiones en disco, y blindaje perimetral mediante `.htaccess` (`Require all denied`).
3. **Descarga Segura y Aislamiento Horizontal (DT-59, DT-61):** Descarga controlada de archivos mediante endpoints autenticados con validación de pertenencia: un docente únicamente puede descargar soportes de sus propias solicitudes; los administradores pueden descargar cualquier soporte justificado. Se transmiten con encabezados estrictos (`X-Content-Type-Options: nosniff`, `Content-Disposition: attachment`, `Cache-Control: private, no-cache, no-store`).
4. **Bandeja y Resolución Administrativa Robusta (RF-27):** Interfaz para personal con rol `ADMINISTRADOR` que permite filtrar solicitudes por estado (`PENDIENTE`, `APROBADO`, `RECHAZADO`) y por docente titular. Acciones atómicas de aprobación o rechazo que registran la decisión de forma concurrente y segura, admitiendo observación institucional opcional (permitiendo null o vacía sin restricciones artificiales).
5. **Máquina de Estados e Inmutabilidad de Estados Terminales (DT-56):** Ciclo de vida estricto: `PENDIENTE` $\to$ `APROBADO` o `RECHAZADO`. Una vez resuelta una solicitud en estado terminal, no se permite su reedición, reaprobación ni modificación posterior.
6. **Auditoría Append-Only Inmutable (RNF-06, DT-56):** Toda creación y cambio de estado genera un registro atómico en `historial_solicitudes_permiso` que almacena el estado anterior, el nuevo estado, el usuario responsable de la acción, la fecha/hora exacta del servidor y los comentarios asociados. La interfaz del repositorio carece por diseño de métodos de actualización (`UPDATE`) o eliminación (`DELETE`).
7. **Concurrencia Pesimista (DT-62):** La resolución administrativa emplea `SELECT ... FOR UPDATE` dentro de transacciones aisladas, garantizando que resoluciones simultáneas compitan en exclusión mutua sin condiciones de carrera (demostrado en 5 iteraciones consecutivas con 100% de consistencia).

---

## 2. DELIMITACIÓN ESTRICTA CON FASE 10 (CONTROL DE ALCANCE)

De conformidad con las instrucciones de control del proyecto y la matriz de trazabilidad:
- **RF-26 ("Notificación de permiso"):** Permanece formal y técnicamente **PENDIENTE PARA LA FASE 10**.
- **Tabla `notificaciones`:** Aunque la tabla relacional existe en el esquema MySQL desde la Fase 1, durante la Fase 9 no fue poblada, modificada ni consultada funcionalmente.
- **Evidencia Técnica (T9-36):** La prueba automatizada T9-36 certifica que ninguna operación de Fase 9 insertó registros en la tabla `notificaciones` ($\Delta \text{notificaciones} = 0$), garantizando la no invasión del alcance de Fase 10.

---

## 3. COMPONENTES ARQUITECTÓNICOS IMPLEMENTADOS Y MODIFICADOS

### Capa de Núcleo (Core)
- `app/Core/Request.php`: Soporte robusto a subida de archivos multipart (`file()`, `getFile()`, `hasFile()`, `getFiles()`) y cabeceras normalizadas seguras.
- `app/Core/App.php`: Registro de rutas protegidas para docente (`/docente/permisos/*`), administración (`/administracion/permisos/*`) y descarga segura, asociadas a sus respectivos middlewares RBAC (`RequireRole:DOCENTE`, `RequireRole:ADMINISTRADOR`).

### Entidades de Dominio (Models)
- `app/Models/SolicitudPermiso.php`: Entidad representativa de la tabla `solicitudes_permiso` con constantes de estado canónicas (`ESTADO_PENDIENTE`, `ESTADO_APROBADO`, `ESTADO_RECHAZADO`) y métodos auxiliares de estado terminal.
- `app/Models/HistorialSolicitudPermiso.php`: Entidad de auditoría inmutable para `historial_solicitudes_permiso`.
- `app/Models/DocumentoPermiso.php`: Entidad representativa del archivo de soporte adjunto en `documentos_permiso`.

### Repositorios y Persistencia (Data Layer)
- `app/Repositories/PermisoRepositoryInterface.php`: Contrato formal de persistencia que incluye búsqueda con bloqueo pesimista (`findForUpdate()`), listados con filtros, registro atómico de solicitudes, documentos e historial append-only.
- `app/Repositories/PermisoRepository.php`: Implementación PDO con transaccionalidad explícita, sentencias preparadas, locking pesimista y exclusión total de sentencias de alteración/borrado sobre tablas de auditoría.

### Servicios de Negocio (Service Layer)
- `app/Services/PermisoService.php`: Capa de dominio que implementa las reglas de negocio institucionales, validación de formularios, validación criptográfica y de magic bytes de archivos, generación aleatoria unívoca de nombres físicos por carga (`bin2hex(random_bytes(32))`), control de máquina de estados y transaccionalidad atómica.

### Controladores (Controllers)
- `app/Controllers/DocentePermisoController.php`: Gestión de solicitudes del docente: listado con aislamiento horizontal, formulario de creación con soporte multipart/CSRF, detalle de solicitud y descarga autorizada.
- `app/Controllers/AdminPermisoController.php`: Gestión administrativa: bandeja general con filtros por estado y docente, detalle de auditoría completa, resolución (aprobación/rechazo) con control de concurrencia y descarga de soportes.

### Vistas (Presentation Layer)
- `views/docente/permisos/index.php`: Listado de solicitudes personales del docente titular.
- `views/docente/permisos/crear.php`: Formulario amigable de registro con soporte de archivo adjunto y protección CSRF.
- `views/docente/permisos/ver.php`: Detalle de solicitud personal, visualización del documento de soporte e historial de resolución.
- `views/admin/permisos/index.php`: Bandeja administrativa con filtros por estado y docente, contadores y acciones directas.
- `views/admin/permisos/ver.php`: Ficha técnica de la solicitud con historial de auditoría, enlace de descarga de soporte y formulario de resolución administrativa.

### Almacenamiento y Protección Perimetral
- `storage/permisos/`: Directorio privado de almacenamiento de archivos fuera de `public/`.
- `storage/permisos/.htaccess`: Configuración de servidor Apache que deniega categóricamente el acceso HTTP directo (`Require all denied`).

---

## 4. DECISIONES TÉCNICAS ADOPTADAS (DT-56 A DT-63)

- **DT-56 — Máquina de estados canónica e inmutabilidad terminal de solicitudes:** Estados válidos: `PENDIENTE`, `APROBADO`, `RECHAZADO`. Ninguna solicitud resuelta puede transicionar nuevamente ni alterarse.
- **DT-57 — Atomicidad transaccional y registro obligatorio de historial:** Todo cambio de estado (`UPDATE`) y creación de historial (`INSERT`) se ejecuta dentro de la misma transacción relacional; ante cualquier eventualidad se ejecuta un `ROLLBACK` total.
- **DT-58 — Almacenamiento privado fuera del document root con nombres ofuscados:** Los archivos se alojan en `storage/permisos/`, completamente fuera de `public/`, impidiendo accesos web directos.
- **DT-59 — Descarga segura mediada por aplicación y cabeceras defensivas:** No existen enlaces directos a la carpeta de almacenamiento. La descarga se procesa exclusivamente mediante PHP, transmitiendo cabeceras que previenen ejecución de scripts en el navegador.
- **DT-60 — Validación dual de archivos (MIME sniffing + magic bytes + cuota de 5MB):** Se verifica tanto la extensión declarada como los magic bytes reales del archivo mediante `finfo`, impidiendo que archivos maliciosos renombrados sean admitidos.
- **DT-61 — Aislamiento horizontal de consultas y descargas:** Un docente autenticado solo puede consultar solicitudes y descargar comprobantes pertenecientes a su registro docente; cualquier intento de acceso ajeno es rechazado de inmediato con `HTTP 403 Forbidden`.
- **DT-62 — Concurrencia pesimista en resolución administrativa:** Bloqueo `SELECT ... FOR UPDATE` durante el proceso de resolución administrativa para evitar que dos administradores resuelvan concurrentemente la misma solicitud.
- **DT-63 — Nombre físico criptográficamente aleatorio e impredecible por cada carga:** Nombres físicos generados con `bin2hex(random_bytes(32))` (`permiso_<64-hex>.<ext>`), garantizando nombres independientes incluso para cargas de archivos idénticos y neutralizando vectores de predicción y directory traversal.

---

## 5. RESULTADOS DE LA SUITE DE PRUEBAS DE FASE 9 (46/46)

| Bloque / Código | Descripción de la Prueba | Estado |
|---|---|:---:|
| **T9-01** | Estructura relacional de permisos en MySQL y restricciones canónicas (DT-56) | **OK** |
| **T9-02** | Registro de solicitud de permiso válida sin documento (RF-24, DT-56) | **OK** |
| **T9-03** | Creación atómica del historial de auditoría al registrar solicitud (RNF-06, DT-56) | **OK** |
| **T9-04** | Validación de fecha solicitada obligatoria y formato válido (RF-24) | **OK** |
| **T9-05** | Validación de tipo_motivo obligatorio y longitud máxima (RF-24) | **OK** |
| **T9-06** | Validación de descripcion obligatoria y longitud máxima (RF-24) | **OK** |
| **T9-07** | Protección contra manipulación de id_docente desde el cliente (DT-61) | **OK** |
| **T9-08** | Protección de estado inicial contra mass assignment (DT-56) | **OK** |
| **T9-09** | Carga y validación exitosa de documento PDF con magic bytes reales (RF-25, DT-60) | **OK** |
| **T9-10** | Carga y validación exitosa de documento de imagen PNG (RF-25, DT-60) | **OK** |
| **T9-11** | Rechazo de archivo ejecutable/PHP camuflado con extensión permitida (DT-60) | **OK** |
| **T9-12** | Rechazo de documento que excede el límite de 5MB (DT-60) | **OK** |
| **T9-13** | Almacenamiento privado con nombre único impredecible y sin path traversal (DT-58, DT-63) | **OK** |
| **T9-14** | Inaccesibilidad directa vía HTTP de archivos subidos en storage/ (DT-58) | **OK** |
| **T9-15** | Descarga autorizada de soporte por el docente propietario con headers seguros (DT-59) | **OK** |
| **T9-16** | Aislamiento horizontal: Docente ajeno no puede descargar documento ajeno (DT-61) | **OK** |
| **T9-17** | Descarga autorizada de documento de soporte para Administrador (RF-27, DT-59) | **OK** |
| **T9-18** | Listado personal de permisos con aislamiento horizontal estricto (RF-28, DT-61) | **OK** |
| **T9-19** | Detalle de permiso: Denegación de acceso a solicitud ajena (RF-28, DT-61) | **OK** |
| **T9-20** | Bandeja administrativa de permisos con soporte de filtros por estado y docente (RF-27) | **OK** |
| **T9-21** | Aprobación administrativa de solicitud de permiso (RF-27, DT-56) | **OK** |
| **T9-22** | Rechazo administrativo de solicitud de permiso (RF-27, DT-56) | **OK** |
| **T9-23** | Registro inmutable de transición en historial_solicitudes_permiso (RNF-06, DT-56, DT-57) | **OK** |
| **T9-24** | Inmutabilidad de estado terminal (APROBADO/RECHAZADO no admite modificaciones - DT-56) | **OK** |
| **T9-25** | Restricción de rol: Docente no puede resolver permisos en administración (RF-27) | **OK** |
| **T9-26** | Protección CSRF estricta en endpoints POST de permisos (RF-24, RF-27) | **OK** |
| **T9-27** | Sanitización estricta contra inyección XSS en vistas docentes y administrativas | **OK** |
| **T9-28** | Inmutabilidad arquitectónica: Cero endpoints de modificación o borrado de historial (RNF-06, DT-56) | **OK** |
| **T9-29** | Rechazo de registro para docentes inactivos o usuarios no autenticados (RF-24) | **OK** |
| **T9-30** | Integridad referencial estricta en MySQL para solicitudes_permiso | **OK** |
| **T9-31** | Concurrencia real multiproceso en resolución con bloqueo pesimista: 5 iteraciones reales (DT-62) | **OK** |
| **T9-32** | Atomicidad transaccional y rollback ante fallo en historial (DT-57) | **OK** |
| **T9-33** | Flujo HTTP real Docente con aislamiento horizontal y anti-tampering (RF-24, RF-25, RF-28, DT-61) | **OK** |
| **T9-34** | Flujo HTTP real Administrador con re-resolución denegada y cero fuga SQL (RF-27, RF-28, DT-56, DT-62) | **OK** |
| **T9-35** | Verificación de seguridad: Acceso directo HTTP a storage/permisos/ devuelve HTTP 404 (DT-58) | **OK** |
| **T9-36** | Control estricto de alcance de Fase 10: Cero notificaciones creadas (RF-26 PENDIENTE FASE 10) | **OK** |
| **T9-37** | Regresión completa de Fase 8 (Asistencia y Geolocalización: 40/40) | **OK** |
| **T9-38** | Regresión completa de Fase 7 (Portal Docente: 28/28) | **OK** |
| **T9-39** | Regresión completa de Fase 6 (Inscripciones y Cupos: 30/30) | **OK** |
| **T9-40** | Regresión completa de Fase 5 (Asignaciones Docentes: 26/26) | **OK** |
| **T9-41** | Regresión completa de Fase 4 (Administración Básica: 27/27) | **OK** |
| **T9-42** | Regresión completa de Fase 3 (Autenticación y Roles: 19/19) | **OK** |
| **T9-43** | Regresión completa de Fase 2 (Arquitectura Base y Enrutamiento: 16/16) | **OK** |
| **T9-44** | Regresión completa de Fase 1 (Base de Datos Relacional: 18/18) | **OK** |
| **T9-45** | Nombres físicos aleatorios independientes por carga (F9-05, DT-63) | **OK** |
| **T9-46** | Rechazo administrativo con observación opcional sin restricción artificial (F9-04, RF-27) | **OK** |

**Resultado consolidado:** **46/46 pruebas ejecutadas exitosamente (100% OK, 0 fallos).**

---

## 6. MATRIZ DE REQUISITOS DEL SISTEMA (FASE 9)

- **RF-24 (Solicitud de permiso):** **IMPLEMENTADO Y VERIFICADO**
- **RF-25 (Documento de respaldo):** **IMPLEMENTADO Y VERIFICADO**
- **RF-26 (Notificación de permiso):** **PENDIENTE PARA FASE 10** (Cero invasión en Fase 9).
- **RF-27 (Aprobación/rechazo):** **IMPLEMENTADO Y VERIFICADO**
- **RF-28 (Estado de solicitud):** **IMPLEMENTADO Y VERIFICADO**
- **RNF-06 (Trazabilidad y auditoría de operaciones clave):** **EVIDENCIA PARCIAL VERIFICADA EN FASE 9** (Historial inmutable append-only implementado para el ciclo de vida de permisos).

---

## 7. LÍMITES Y NO INVASIÓN DE FASES POSTERIORES

En riguroso apego a la metodología fase por fase:
1. **Fase 10 — Módulo de Notificaciones:** No iniciada. La tabla `notificaciones` no sufrió alteraciones funcionales.
2. **Fase 11 — Comprobantes PDF:** No iniciada. No se han implementado generadores de comprobantes en PDF.
3. **Fase 12 — Reportes y Exportación:** No iniciada. No se han generado exportaciones gerenciales en Excel o PDF.
4. **Fase 13 — Recuperación de Contraseña:** No iniciada.
5. **Fase 14 — Endurecimiento Integral de Seguridad y Privacidad:** La evidencia de Fase 9 se circunscribe a permisos docentes; la auditoría e integración perimetral completa permanece asignada a la Fase 14.

---

## 8. CONCLUSIÓN Y ESTADO DE CIERRE

La **Fase 9 — Gestión de Permisos Docentes** ha culminado satisfactoriamente con la totalidad de sus requisitos funcionales implementados, probados y documentados. No se ha modificado funcionalidad de fases previas ni se ha adelantado trabajo de fases posteriores.

```
FASE 9 DETENIDA A LA ESPERA DE REVISIÓN EXTERNA Y AUTORIZACIÓN DEL USUARIO.
```
