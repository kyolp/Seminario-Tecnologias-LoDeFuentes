# MATRIZ DE VALIDACIÓN DE REQUERIMIENTOS — FASE 15
## SISTEMA WEB ESCUELA OFICIAL RURAL MIXTA LO DE FUENTES

**Módulo:** Validación Final Funcional, Compatibilidad y Pruebas Integrales  
**Rol:** Antigravity — Desarrollador Principal  
**Fecha:** 20 de octubre de 2026  
**Estado:** 100% VALIDADO (32/32 RF — 10/10 RNF)  

---

## 1. Matriz de Requerimientos Funcionales (RF-01 a RF-32)

Todos los 32 Requerimientos Funcionales han sido implementados, probados y formalmente certificados como **VALIDADO**.

| ID | Nombre Oficial | Descripción y Criterios | Módulo / Controlador Responsable | Prueba de Validación | Estado Final |
|:---:|---|---|---|:---:|:---:|
| **RF-01** | Autenticación y control de acceso | Inicio de sesión con credenciales válidas, control de roles (ADMINISTRADOR y DOCENTE), denegación a inactivos y mensajes neutrales anti-enumeración. | `AuthController`, `AuthService` | `T3-01`, `T3-02`, `T14-19`, `T15-03`, `T15-07` | **VALIDADO** |
| **RF-02** | Cierre seguro de sesión | Destrucción completa de la sesión del servidor, eliminación de cookies de sesión e invalidación de acceso a rutas privadas. | `AuthController`, `Session` | `T3-12`, `T14-17` | **VALIDADO** |
| **RF-03** | Recuperación de contraseña | Solicitud segura sin enumeración, token CSPRNG (64 hex, hash SHA-256 en BD), vigencia de 1 hora, uso único y actualización de clave con BCrypt. | `PasswordRecoveryController`, `PasswordRecoveryService` | `T13-01` a `T13-30`, `T15-21`, `T15-22` | **VALIDADO** |
| **RF-04** | Gestión de roles y permisos | Control de acceso basado en roles (RBAC) estricto. Separación de funciones administrativas y docentes sin escalación de privilegios. | `App`, `Authenticate`, `RequireRole` | `T3-08`, `T3-10`, `T14-04`, `T15-23` | **VALIDADO** |
| **RF-05** | Datos del encargado | Captura, validación y persistencia de información del tutor legal (nombres, apellidos, teléfono, correo) y vínculo N:M con el estudiante. | `InscripcionPublicaController`, `EncargadoRepository` | `T6-06`, `T6-14`, `T15-09` | **VALIDADO** |
| **RF-06** | Datos del estudiante | Registro y validación de nombres, apellidos, fecha de nacimiento y código de estudiante con unicidad controlada. | `InscripcionPublicaController`, `EstudianteRepository` | `T4-09`, `T6-06`, `T15-09` | **VALIDADO** |
| **RF-07** | Gestión de grados y secciones | Catálogo dinámico de grados escolares con orden de progresión pedagógica y asociación a períodos lectivos específicos. | `AdminGradoController`, `AdminGradoPeriodoController` | `T4-13`, `T4-14`, `T15-04` | **VALIDADO** |
| **RF-08** | Asignación de secciones | Configuración de secciones con código, nombre de jornada y capacidad máxima mayor a cero vinculadas a un grado-período. | `AdminSeccionController`, `SeccionRepository` | `T4-16`, `T4-18`, `T15-04` | **VALIDADO** |
| **RF-09** | Control automático de cupos | Cálculo en tiempo real de cupos disponibles (`capacidad - confirmadas`); exclusión automática de secciones colmadas (RN-DOC-02). | `InscripcionService` | `T6-04`, `T6-05`, `T6-20`, `T15-04`, `T15-09` | **VALIDADO** |
| **RF-10** | Disponibilidad de secciones | Exposición filtrada de secciones con cupos vigentes y habilitadas durante el proceso de inscripción web. | `InscripcionPublicaController`, `InscripcionService` | `T6-03`, `T6-05`, `T15-04` | **VALIDADO** |
| **RF-11** | Inscripción web pública | Proceso de autoservicio público para matrícula de alumnos sin requerir inicio de sesión previo. | `InscripcionPublicaController`, `InscripcionService` | `T6-01`, `T6-06`, `T6-25`, `T15-09` | **VALIDADO** |
| **RF-12** | Código único de confirmación | Generación criptográficamente segura de código de confirmación alfanumérico irrepetible (`INS-YYYY-XXXXXXXX`) por matrícula aceptada. | `InscripcionService` | `T6-07`, `T6-23`, `T15-09` | **VALIDADO** |
| **RF-13** | Comprobante de inscripción PDF | Generación al vuelo en memoria de documento PDF oficial descargable con código de barras/confirmación, sin almacenamiento en disco. | `ComprobanteInscripcionController`, `ComprobanteInscripcionService` | `T11-01` a `T11-29`, `T15-12` | **VALIDADO** |
| **RF-14** | Consulta pública de estado | Consulta en línea del estado de la matrícula mediante el código de confirmación, con estricta protección de datos personales. | `InscripcionPublicaController`, `InscripcionService` | `T6-08`, `T6-24`, `T15-11` | **VALIDADO** |
| **RF-15** | Gestión de docentes | Registro, edición, activación/desactivación de docentes con asignación atómica de credenciales de usuario y código institucional. | `AdminDocenteController`, `DocenteService` | `T4-05`, `T4-07`, `T4-08`, `T15-05` | **VALIDADO** |
| **RF-16** | Asignación académica docente | Vinculación académica de docentes a sección y aula física; restricción de una única asignación activa por sección simultáneamente. | `AdminAsignacionController`, `AsignacionService` | `T5-05`, `T5-11`, `T5-20`, `T15-06` | **VALIDADO** |
| **RF-17** | Portal docente | Interfaz exclusiva para el personal docente autenticado, mostrando contexto de sus asignaciones activas y resumen de actividades. | `DocenteController`, `PortalDocenteService` | `T7-01`, `T7-04`, `T7-05`, `T15-07` | **VALIDADO** |
| **RF-18** | Configuración de geocerca escolar | Administración de coordenadas centrales (latitud, longitud) y radio de cobertura en metros de la institución escolar. | `AdminUbicacionController`, `UbicacionService` | `T8-01`, `T8-02`, `T8-08`, `T15-13` | **VALIDADO** |
| **RF-19** | Validación geográfica de asistencia | Cálculo autoritativo en backend mediante fórmula geodésica Haversine; rechazo estricto de coordenadas fuera del radio permitido. | `AsistenciaService`, `DocenteAsistenciaController` | `T8-09`, `T8-11`, `T8-12`, `T15-15` | **VALIDADO** |
| **RF-20** | Registro de marcaje de entrada | Marcaje docente de inicio de jornada escolar validando identidad de sesión, geocerca activa y fecha/hora del servidor. | `DocenteAsistenciaController`, `AsistenciaService` | `T8-10`, `T8-14`, `T15-13` | **VALIDADO** |
| **RF-21** | Registro de marcaje de salida | Marcaje docente de finalización de jornada validando geocerca y existencia previa de entrada registrada. | `DocenteAsistenciaController`, `AsistenciaService` | `T8-16`, `T8-19`, `T15-14` | **VALIDADO** |
| **RF-22** | Validación de secuencia de marcajes | Máquina de estados que impide entradas consecutivas sin salida previa y salidas sin entrada previa. | `AsistenciaService` | `T8-15`, `T8-17`, `T8-18`, `T15-14` | **VALIDADO** |
| **RF-23** | Consulta de historial de asistencia | Visualización inmutable y cronológica descendente de las marcas de asistencia registradas por el docente autenticado. | `DocenteAsistenciaController`, `AsistenciaRepository` | `T8-24`, `T8-25`, `T15-15` | **VALIDADO** |
| **RF-24** | Solicitud de permisos docentes | Formulario de registro de solicitud con fecha futura, tipo de motivo institucional y descripción detallada del requerimiento. | `DocentePermisoController`, `PermisoService` | `T9-02`, `T9-04`, `T9-05`, `T15-16` | **VALIDADO** |
| **RF-25** | Adjunto de soporte en permisos | Carga opcional de documento probatorio (PDF/imagen) con validación estricta de Magic Bytes en servidor (finfo) y almacenamiento fuera de webroot. | `DocentePermisoController`, `PermisoService` | `T9-09`, `T9-10`, `T9-11`, `T15-16` | **VALIDADO** |
| **RF-26** | Notificaciones automáticas | Emisión automática de notificaciones en tiempo real al administrador ante nuevas solicitudes y al docente ante resoluciones. | `AdminNotificacionController`, `NotificacionService` | `T10-03`, `T10-04`, `T15-17`, `T15-18` | **VALIDADO** |
| **RF-27** | Resolución administrativa de permisos | Panel administrativo para revisión, aprobación o rechazo de solicitudes docentes con bloqueo pesimista (`FOR UPDATE`). | `AdminPermisoController`, `PermisoService` | `T9-20`, `T9-21`, `T9-22`, `T15-18` | **VALIDADO** |
| **RF-28** | Historial y trazabilidad de permisos | Bitácora inmutable en base de datos (`historial_solicitudes_permiso`) que registra todas las transiciones de estado con autor y timestamp. | `PermisoService`, `PermisoRepository` | `T9-03`, `T9-23`, `T9-24`, `T15-18` | **VALIDADO** |
| **RF-29** | Reporte de asistencia docente | Generación de informe con filtros multidimensionales (período, docente, rango de fechas, tipo) y métricas de puntualidad y asistencia. | `AdminReporteController`, `ReporteService` | `T12-02` a `T12-10`, `T15-19` | **VALIDADO** |
| **RF-30** | Reporte de inscripciones y cupos | Reporte consolidado de ocupación de cupos por grado y sección, con listado detallado de estudiantes y estados de inscripción. | `AdminReporteController`, `ReporteService` | `T12-11` a `T12-20`, `T15-20` | **VALIDADO** |
| **RF-31** | Integración de nómina de sección | Reflejo inmediato y automático de los estudiantes formalmente matriculados en la nómina visible por el docente asignado. | `PortalDocenteService`, `InscripcionService` | `T7-19`, `T7-20`, `T15-10` | **VALIDADO** |
| **RF-32** | Integridad concurrente de inscripciones | Control estricto de concurrencia bajo transacciones pesimistas (`SELECT ... FOR UPDATE`), garantizando cero sobrecupos ante solicitudes simultáneas. | `InscripcionService` | `T6-20`, `T6-21`, `T6-22` | **VALIDADO** |

---

## 2. Matriz de Requerimientos No Funcionales (RNF-01 a RNF-10)

Todos los 10 Requerimientos No Funcionales han sido implementados, auditados técnicamente y certificados como **VALIDADO**.

| ID | Categoría | Criterios y Especificaciones Técnicas | Implementación Arquitectónica | Pruebas de Certificación | Estado Final |
|:---:|---|---|---|:---:|:---:|
| **RNF-01** | Usabilidad | Interfaces intuitivas para personal no técnico; etiquetas semánticas `<label for>`, indicadores obligatorios, feedback visual de errores y componentes de alerta accesibles. | Plantillas limpias en `views/`, layout escolar unificado, helper de errores y directivas CSS semánticas. | `T15-32` | **VALIDADO** |
| **RNF-02** | Compatibilidad | Operación fluida en navegadores web modernos basados en Chromium (Google Chrome y Microsoft Edge) y diseño responsivo adaptable a dispositivos móviles. | CSS estándar con diseño flexible (Flexbox / Grid), meta viewport configurado y Geolocation API nativa. | `T15-35`, auditoría de vistas | **VALIDADO** |
| **RNF-03** | Seguridad | Contraseñas hasheadas con BCrypt nativo (costo 10+), RBAC estricto, protección CSRF en todos los POST, mitigación de XSS con `e()`, cabeceras de hardening (`nosniff`, `SAMEORIGIN`) y expiración de sesiones. | `AuthService`, middleware `VerifyCsrfToken`, `RequireRole`, `App::applySecurityHeaders`, `Logger` sanitizado. | `T14-01` a `T14-32`, `T15-23`, `T15-26`, `T15-27`, `T15-28` | **VALIDADO** |
| **RNF-04** | Privacidad | Protección de datos personales de menores y tutores. Erradicación total de IDOR en nóminas, permisos y notificaciones; mínima exposición pública sin DPI, teléfonos ni coordenadas GPS privadas. | DTOs sanitizados en `InscripcionService`, políticas de aislamiento horizontal en repositorios y reportes sin coordenadas personales. | `T6-24`, `T11-15`, `T11-16`, `T14-06` a `T14-10`, `T14-25`, `T14-26`, `T15-11`, `T15-24`, `T15-29` | **VALIDADO** |
| **RNF-05** | Integridad | Integridad referencial y de dominio en MySQL 8.0 (20 tablas, 26 FKs, CHECKs, UNIQUEs) y uso universal (100%) de sentencias preparadas PDO con parámetros fuertemente tipados. | `database/schema.sql` estricto, repositorios desacoplados con inyección de PDO y transacciones ACID. | `T1-01` a `T1-18`, `T14-11`, `T14-12`, `T15-25`, `T15-31` | **VALIDADO** |
| **RNF-06** | Trazabilidad | Registro inmutable de eventos con marcas de tiempo en microsegundos `CURRENT_TIMESTAMP(6)`, bitácoras de auditoría en cambios de estado de permisos y log de eventos sanitizado. | Tablas `historial_solicitudes_permiso` y `marcajes_asistencia`, clase `Logger` con enmascaramiento automático de secretos. | `T8-20`, `T9-23`, `T10-09`, `T14-30`, `T15-18` | **VALIDADO** |
| **RNF-07** | Rendimiento | Consultas agregadas e indexadas ejecutadas en tiempo de respuesta inferior a 100ms; consultas de reportes optimizadas con JOINs indexados sin patrones de cuello de botella N+1. | Índices compuestos en `secciones`, `inscripciones`, `marcajes_asistencia` y consultas consolidadas en `ReporteRepository`. | `T15-33` (7.23 ms medidos) | **VALIDADO** |
| **RNF-08** | Mantenibilidad | Arquitectura en capas limpia (MVC / CSR: Controlador-Servicio-Repositorio) con inyección de dependencias, interfaces de repositorio y páginas de error 404/500 amigables sin fugas técnicas. | Separación de responsabilidades en `app/`, interfaces contractuales en `app/Repositories/`, vistas de error desacopladas. | `T14-29`, `T15-30` | **VALIDADO** |
| **RNF-09** | Configurabilidad | Parámetros del sistema dinámicamente configurables desde la interfaz administrativa (radio y coordenadas de geocerca escolar, capacidades de sección, ciclos lectivos). | Módulos administrativos de Períodos, Grados, Secciones y Ubicación sin necesidad de tocar código fuente. | `T4-11`, `T4-16`, `T8-01`, `T15-04` | **VALIDADO** |
| **RNF-10** | Disponibilidad y Portabilidad | Stack 100% autónomo y auto-contenido en Apache 2.4 / PHP 8.2 / MySQL 8.0 (XAMPP/Linux) sin dependencias de servicios externos pagados, APIs en la nube o SaaS de terceros. | Biblioteca FPDF local 1.86, fórmula matemática Haversine nativa en PHP, criptografía local OpenSSL y BCrypt. | `T11-25`, `T15-34` | **VALIDADO** |

---

## 3. Certificación de Cobertura

La totalidad de los requerimientos especificados en el documento de alcance de la Escuela Oficial Rural Mixta Lo de Fuentes ha sido **verificada, probada y aprobada**.
