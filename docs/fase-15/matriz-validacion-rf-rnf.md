# MATRIZ DE VALIDACIÓN DE REQUERIMIENTOS — FASE 15
## SISTEMA WEB ESCUELA OFICIAL RURAL MIXTA LO DE FUENTES

**Módulo:** Validación Final Funcional, Compatibilidad y Pruebas Integrales  
**Rol:** Antigravity — Desarrollador Principal  
**Fecha:** 20 de octubre de 2026  
**Estado:** 100% VALIDADO (32/32 RF — 10/10 RNF)  

---

## 1. Matriz de Requerimientos Funcionales Oficiales (RF-01 a RF-32)

Todos los 32 Requerimientos Funcionales oficiales del Documento de Alcance han sido implementados, probados y formalmente certificados como **VALIDADO**. Se conserva estrictamente la numeración y nombres oficiales de la línea base documental:

| ID | Nombre Oficial | Descripción Oficial y Criterios | Componentes Responsables | Pruebas de Validación | Estado Final |
|:---:|---|---|---|---|:---:|
| **RF-01** | Autenticación | Permitir iniciar sesión a usuarios administrativos y docentes mediante credenciales. | `AuthController`, `AuthService` | `tests/run_tests_fase3.php` (T3-01 a T3-06, T3-12, T3-19), `tests/run_tests_fase15.php` (T15-03, T15-07, T15-22) | **VALIDADO** |
| **RF-02** | Roles y permisos | Restringir las funciones de acuerdo con el rol del usuario (ADMINISTRADOR y DOCENTE). | `RequireRole`, `Authenticate`, `App` | `tests/run_tests_fase3.php` (T3-07 a T3-11, T3-13), `tests/run_tests_fase14.php` (T14-04), `tests/run_tests_fase15.php` (T15-07, T15-23) | **VALIDADO** |
| **RF-03** | Recuperación de contraseña | Recuperar el acceso mediante correo electrónico o enlace/código. | `PasswordRecoveryController`, `PasswordRecoveryService`, `TokenRecuperacionRepository` | `tests/run_tests_fase13.php` (T13-01 a T13-30), `tests/run_tests_fase15.php` (T15-21, T15-22) | **VALIDADO** |
| **RF-04** | Gestión de estudiantes | Registrar, editar, consultar y desactivar estudiantes. | `AdminEstudianteController`, `EstudianteService`, `EstudianteRepository` | `tests/run_tests_fase4.php` (T4-09, T4-10), `tests/run_tests_fase15.php` (T15-09) | **VALIDADO** |
| **RF-05** | Datos de encargados | Registrar los datos necesarios del padre, madre o encargado relacionados con la inscripción. | `InscripcionPublicaController`, `InscripcionService`, `EncargadoRepository` | `tests/run_tests_fase6.php` (T6-06, T6-14, T6-15), `tests/run_tests_fase15.php` (T15-09) | **VALIDADO** |
| **RF-06** | Gestión de grados | Habilitar y administrar los grados disponibles para cada período. | `AdminGradoController`, `AdminGradoPeriodoController`, `GradoService` | `tests/run_tests_fase4.php` (T4-13 a T4-15), `tests/run_tests_fase15.php` (T15-04) | **VALIDADO** |
| **RF-07** | Gestión de secciones | Crear, editar, habilitar o deshabilitar secciones por grado. | `AdminSeccionController`, `SeccionService`, `SeccionRepository` | `tests/run_tests_fase4.php` (T4-16, T4-18, T4-20), `tests/run_tests_fase15.php` (T15-04) | **VALIDADO** |
| **RF-08** | Capacidad por sección | Establecer la cantidad máxima de estudiantes permitida en cada sección. | `AdminSeccionController`, `SeccionService`, `SeccionRepository` | `tests/run_tests_fase4.php` (T4-16 a T4-18), `tests/run_tests_fase15.php` (T15-04) | **VALIDADO** |
| **RF-09** | Control automático de cupos | Impedir nuevas inscripciones cuando una sección alcance su capacidad. | `InscripcionService`, `InscripcionRepository` | `tests/run_tests_fase6.php` (T6-04, T6-05), `tests/run_tests_fase15.php` (T15-04, T15-09) | **VALIDADO** |
| **RF-10** | Disponibilidad de secciones con cupo | Mostrar únicamente secciones con cupos disponibles durante la inscripción. | `InscripcionPublicaController`, `InscripcionService` | `tests/run_tests_fase6.php` (T6-01 a T6-03, T6-25), `tests/run_tests_fase15.php` (T15-04, T15-09) | **VALIDADO** |
| **RF-11** | Inscripción web | Registrar una inscripción mediante formulario web. | `InscripcionPublicaController`, `InscripcionService`, `InscripcionRepository` | `tests/run_tests_fase6.php` (T6-06, T6-18, T6-19, T6-25), `tests/run_tests_fase15.php` (T15-09) | **VALIDADO** |
| **RF-12** | Código único | Generar un código único de confirmación para cada inscripción aceptada. | `InscripcionService`, `InscripcionRepository` | `tests/run_tests_fase6.php` (T6-07, T6-23), `tests/run_tests_fase15.php` (T15-09) | **VALIDADO** |
| **RF-13** | Comprobante PDF | Generar un comprobante de inscripción en PDF. | `ComprobanteInscripcionController`, `ComprobanteInscripcionService`, `PdfService` | `tests/run_tests_fase11.php` (T11-01 a T11-29), `tests/run_tests_fase15.php` (T15-12) | **VALIDADO** |
| **RF-14** | Consulta por código | Permitir al encargado consultar la información básica mediante el código sin crear una cuenta. | `InscripcionPublicaController`, `InscripcionService` | `tests/run_tests_fase6.php` (T6-08, T6-09, T6-24, T6-25), `tests/run_tests_fase15.php` (T15-11) | **VALIDADO** |
| **RF-15** | Gestión docente | Registrar y administrar la información de docentes. | `AdminDocenteController`, `DocenteService`, `DocenteRepository` | `tests/run_tests_fase4.php` (T4-05 a T4-08), `tests/run_tests_fase15.php` (T15-05) | **VALIDADO** |
| **RF-16** | Asignaciones docentes | Asignar docentes a grados, secciones y aulas. | `AdminAsignacionController`, `AsignacionService`, `AsignacionRepository` | `tests/run_tests_fase5.php` (T5-01 a T5-26), `tests/run_tests_fase15.php` (T15-06) | **VALIDADO** |
| **RF-17** | Lista de estudiantes del docente | Permitir al docente consultar estudiantes de su sección asignada. | `DocenteController`, `PortalDocenteService`, `PortalDocenteRepository` | `tests/run_tests_fase7.php` (T7-01 a T7-21, T7-28), `tests/run_tests_fase15.php` (T15-08) | **VALIDADO** |
| **RF-18** | Configuración de ubicación | Definir la ubicación de la institución y el radio permitido para el marcaje. | `AdminUbicacionController`, `UbicacionService`, `UbicacionRepository` | `tests/run_tests_fase8.php` (T8-01 a T8-08), `tests/run_tests_fase15.php` (T15-13) | **VALIDADO** |
| **RF-19** | Validación geográfica | Validar que el dispositivo esté dentro del área autorizada antes del marcaje. | `AsistenciaService` (Haversine), `DocenteAsistenciaController` | `tests/run_tests_fase8.php` (T8-09 a T8-13, T8-30), `tests/run_tests_fase15.php` (T15-13, T15-15) | **VALIDADO** |
| **RF-20** | Marcaje de entrada | Registrar la entrada mediante un botón cuando se cumpla la validación de ubicación. | `DocenteAsistenciaController`, `AsistenciaService` | `tests/run_tests_fase8.php` (T8-14, T8-15, T8-29), `tests/run_tests_fase15.php` (T15-13) | **VALIDADO** |
| **RF-21** | Marcaje de salida | Registrar la salida mediante un botón cuando corresponda. | `DocenteAsistenciaController`, `AsistenciaService` | `tests/run_tests_fase8.php` (T8-16 a T8-19, T8-29), `tests/run_tests_fase15.php` (T15-14) | **VALIDADO** |
| **RF-22** | Registro de fecha y hora | Almacenar usuario/docente, fecha, hora y tipo de cada marcaje. | `AsistenciaRepository`, `AsistenciaService` | `tests/run_tests_fase8.php` (T8-20), `tests/run_tests_fase15.php` (T15-13, T15-14) | **VALIDADO** |
| **RF-23** | Historial de asistencia | Permitir al docente consultar sus marcajes y a administración consultar registros autorizados. | `DocenteAsistenciaController`, `AsistenciaRepository` | `tests/run_tests_fase8.php` (T8-24, T8-25), `tests/run_tests_fase15.php` (T15-15) | **VALIDADO** |
| **RF-24** | Solicitud de permiso | Registrar solicitudes indicando fecha, motivo y descripción. | `DocentePermisoController`, `PermisoService`, `PermisoRepository` | `tests/run_tests_fase9.php` (T9-01 a T9-08, T9-33), `tests/run_tests_fase15.php` (T15-16) | **VALIDADO** |
| **RF-25** | Documento de respaldo | Adjuntar un archivo de respaldo a una solicitud. | `PermisoService`, `PermisoRepository`, `DocentePermisoController` | `tests/run_tests_fase9.php` (T9-09 a T9-17, T9-33, T9-35, T9-45), `tests/run_tests_fase15.php` (T15-16) | **VALIDADO** |
| **RF-26** | Notificación de permiso | Notificar a administración cuando exista una nueva solicitud. | `AdminNotificacionController`, `NotificacionService`, `NotificacionRepository` | `tests/run_tests_fase10.php` (T10-01 a T10-27), `tests/run_tests_fase15.php` (T15-17) | **VALIDADO** |
| **RF-27** | Aprobación/rechazo | Aprobar o rechazar solicitudes y registrar la decisión. | `AdminPermisoController`, `PermisoService`, `PermisoRepository` | `tests/run_tests_fase9.php` (T9-20 a T9-25, T9-31, T9-32, T9-34, T9-46), `tests/run_tests_fase15.php` (T15-18) | **VALIDADO** |
| **RF-28** | Estado de solicitud | Permitir al docente consultar el estado e historial de sus solicitudes. | `DocentePermisoController`, `PermisoRepository` | `tests/run_tests_fase9.php` (T9-18, T9-19, T9-33), `tests/run_tests_fase15.php` (T15-18) | **VALIDADO** |
| **RF-29** | Reporte de asistencia | Generar reportes por docente y período. | `AdminReporteController`, `ReporteService`, `ReporteRepository` | `tests/run_tests_fase12.php` (T12-02 a T12-10, T12-26), `tests/run_tests_fase15.php` (T15-19) | **VALIDADO** |
| **RF-30** | Reporte/consulta de inscripciones y cupos | Consultar estudiantes inscritos, grado, sección y estado de cupo. | `AdminReporteController`, `ReporteService`, `ReporteRepository` | `tests/run_tests_fase12.php` (T12-11 a T12-20, T12-27), `tests/run_tests_fase15.php` (T15-20) | **VALIDADO** |
| **RF-31** | Integración inscripción → nómina docente | Actualizar el listado de la sección mediante inscripciones confirmadas. | `PortalDocenteRepository`, `InscripcionService`, `DocenteController` | `tests/run_tests_fase7.php` (T7-19, T7-20), `tests/run_tests_fase15.php` (T15-10) | **VALIDADO** |
| **RF-32** | Integridad/concurrencia de cupos | Evitar que operaciones concurrentes superen la capacidad de una sección. | `InscripcionService`, `InscripcionRepository` | `tests/run_tests_fase6.php` (T6-20, T6-21), `tests/run_tests_fase15.php` (T15-09, T15-31) | **VALIDADO** |

> **Nota sobre RF-14:** La consulta pública se ejecuta mediante el código único alfanumérico generado en la inscripción (RF-12). El CUI no forma parte de la descripción del requisito oficial.

---

## 2. Matriz de Requerimientos No Funcionales Oficiales (RNF-01 a RNF-10)

Todos los 10 Requerimientos No Funcionales del Documento de Alcance han sido auditados técnicamente y certificados como **VALIDADO**:

| ID | Nombre Oficial | Descripción Oficial y Criterios | Implementación Técnica | Pruebas de Certificación | Estado Final |
|:---:|---|---|---|---|:---:|
| **RNF-01** | Usabilidad | Formularios, botones claros y mensajes comprensibles para usuarios no técnicos. | Labels semánticos `<label for>`, indicadores de obligatoriedad, componentes alert de feedback y diseño escolar unificado. | `tests/run_tests_fase15.php` (T15-32) | **VALIDADO** |
| **RNF-02** | Compatibilidad | Funcionamiento en navegadores modernos y dispositivos compatibles con geolocalización. | CSS responsivo (Flexbox/Grid), compatibilidad certificada en navegadores Chromium (Google Chrome y Microsoft Edge), y Geolocation API nativa. | `tests/run_tests_fase15.php` (T15-35) | **VALIDADO** |
| **RNF-03** | Seguridad | Contraseñas no almacenadas en texto plano y acceso controlado por roles. | Hashing BCrypt nativo (costo 10+), RBAC estricto, protección CSRF en mutaciones POST, sanitización contextual XSS con `e()`, y cabeceras de hardening (`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Cache-Control: no-store`). *(Nota: CSP no está implementada y no se afirma su existencia).* | `tests/run_tests_fase3.php`, `tests/run_tests_fase13.php`, `tests/run_tests_fase14.php`, `tests/run_tests_fase15.php` (T15-23, T15-26, T15-27, T15-28) | **VALIDADO** |
| **RNF-04** | Privacidad | Datos personales accesibles solo a usuarios autorizados. | Erradicación total de IDOR en nóminas, permisos y notificaciones; mínima exposición pública sin datos de contacto ni llaves de BD. | `tests/run_tests_fase14.php`, `tests/run_tests_fase15.php` (T15-11, T15-24, T15-29) | **VALIDADO** |
| **RNF-05** | Integridad | Relaciones consistentes para inscripción, cupos y asistencia. | 20 tablas relacionales en MySQL 8.0, 26 foreign keys, checks, transacciones ACID y 100% prepared statements con PDO. | `database/tests/validacion.sql`, `tests/run_tests_fase14.php`, `tests/run_tests_fase15.php` (T15-25, T15-31, T15-36) | **VALIDADO** |
| **RNF-06** | Trazabilidad | Fecha y usuario asociado en asistencia y decisiones sobre permisos. | Marcas de tiempo en microsegundos `CURRENT_TIMESTAMP(6)`, auditoría inmutable de transiciones en `historial_solicitudes_permiso` y logging sanitizado. | `tests/run_tests_fase8.php`, `tests/run_tests_fase9.php`, `tests/run_tests_fase10.php`, `tests/run_tests_fase15.php` (T15-18) | **VALIDADO** |
| **RNF-07** | Rendimiento | Respuesta en tiempo razonable bajo la carga prevista. | Consultas consolidadas en `ReporteRepository` con índices relacionales en MySQL. Medición observada en entorno de prueba: **7.23 ms**. *(Nota: El documento oficial no define un umbral de 100 ms ni SLA formal; la cifra observada es evidencia puntual del entorno de prueba sin constituir benchmark general).* | `tests/run_tests_fase15.php` (T15-33) | **VALIDADO** |
| **RNF-08** | Mantenibilidad | Separación de presentación, negocio y acceso a datos. | Arquitectura en capas limpia (MVC / CSR: Controlador-Servicio-Repositorio) con inyección de dependencias y vistas 404/500 amigables sin stack traces. | `tests/run_tests.php`, `tests/run_tests_fase14.php`, `tests/run_tests_fase15.php` (T15-30) | **VALIDADO** |
| **RNF-09** | Configurabilidad | Grados, secciones, capacidades, aulas y ubicación modificables sin alterar código. | Parámetros del sistema dinámicamente gestionables desde la interfaz administrativa (períodos, grados, secciones, capacidades, geocerca escolar). | `tests/run_tests_fase4.php`, `tests/run_tests_fase15.php` (T15-04, T15-13) | **VALIDADO** |
| **RNF-10** | Disponibilidad | Dependencia de la infraestructura de alojamiento del prototipo o instalación futura. | Stack 100% auto-contenido en Apache/PHP/MySQL (XAMPP/Linux) sin requerir servicios SaaS de terceros ni APIs en la nube. | `tests/run_tests_fase15.php` (T15-34) | **VALIDADO** |

---

## 3. Certificación de Cobertura

La totalidad de los requerimientos especificados en el documento de alcance de la Escuela Oficial Rural Mixta Lo de Fuentes ha sido **verificada, probada y aprobada**.
