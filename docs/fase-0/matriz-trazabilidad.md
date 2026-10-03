# Matriz de trazabilidad corregida — Fase 0

Fuente oficial: sección 10 del documento de alcance de la Escuela Oficial Rural
Mixta Lo de Fuentes. Los identificadores y las descripciones se conservan como
aparecen en la línea base documental. La fase asignada es información de
planificación del Prompt Maestro; no implica implementación anticipada.

Todos los RF y RNF permanecen `PENDIENTE`: la Fase 0 solo corrige la
trazabilidad y ningún requisito funcional ha sido implementado o validado.

## Requerimientos funcionales oficiales

| ID | Requerimiento y descripción | Fase asignada | Archivos relacionados | Tablas relacionadas | Estado | Prueba | Resultado |
|---|---|---:|---|---|---|---|---|
| RF-01 | **Autenticación.** Permitir iniciar sesión a usuarios administrativos y docentes mediante credenciales. | 3 | `app/Controllers/AuthController.php`, `app/Services/AuthService.php` | `usuarios`, `roles` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase3.php` (T3-01 a T3-06, T3-12, T3-19) | APROBADO (19/19) |
| RF-02 | **Roles y permisos.** Restringir las funciones de acuerdo con el rol del usuario. | 3 | `app/Middleware/RequireRole.php`, `app/Middleware/Authenticate.php` | `roles`, `usuarios` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase3.php` (T3-07 a T3-11, T3-13) | APROBADO (19/19) |
| RF-03 | **Recuperación de contraseña.** Recuperar el acceso mediante correo electrónico o enlace/código. | 13 | Por definir en Fase 13 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-04 | **Gestión de estudiantes.** Registrar, editar, consultar y desactivar estudiantes. | 4 | `app/Controllers/AdminEstudianteController.php`, `app/Services/EstudianteService.php`, `app/Repositories/EstudianteRepository.php` | `estudiantes` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase4.php` (T4-09, T4-10) | APROBADO (27/27) |
| RF-05 | **Datos del encargado.** Registrar los datos necesarios del padre, madre o encargado relacionados con la inscripción. | 6 | `app/Models/Encargado.php`, `app/Models/EstudianteEncargado.php`, `app/Repositories/EncargadoRepository.php`, `app/Services/InscripcionService.php` | `encargados`, `estudiantes_encargados`, `estudiantes` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase6.php` (T6-06, T6-14, T6-15) | APROBADO (30/30) |
| RF-06 | **Gestión de grados.** Habilitar y administrar los grados disponibles para cada período. | 4 | `app/Controllers/AdminGradoController.php`, `app/Controllers/AdminGradoPeriodoController.php`, `app/Services/GradoService.php` | `grados`, `grados_periodos` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase4.php` (T4-13 a T4-15) | APROBADO (27/27) |
| RF-07 | **Gestión de secciones.** Crear, editar, habilitar o deshabilitar secciones por grado. | 4 | `app/Controllers/AdminSeccionController.php`, `app/Services/SeccionService.php`, `app/Repositories/SeccionRepository.php` | `secciones`, `grados_periodos` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase4.php` (T4-16, T4-18, T4-20) | APROBADO (27/27) |
| RF-08 | **Capacidad por sección.** Establecer la cantidad máxima de estudiantes permitida en cada sección. | 4 | `app/Services/SeccionService.php`, `app/Repositories/SeccionRepository.php` | `secciones` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase4.php` (T4-16 a T4-18) | APROBADO (27/27) |
| RF-09 | **Control automático de cupos.** Impedir nuevas inscripciones cuando una sección alcance su capacidad. | 6 | `app/Services/InscripcionService.php`, `app/Repositories/InscripcionRepository.php` | `inscripciones`, `secciones` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase6.php` (T6-04, T6-05) | APROBADO (30/30) |
| RF-10 | **Disponibilidad.** Mostrar únicamente secciones con cupos disponibles durante la inscripción. | 6 | `app/Controllers/InscripcionPublicaController.php`, `app/Services/InscripcionService.php`, `views/inscripciones/nueva.php` | `secciones`, `grados_periodos`, `periodos_escolares` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase6.php` (T6-01 a T6-03, T6-25) | APROBADO (30/30) |
| RF-11 | **Inscripción web.** Registrar una inscripción mediante formulario web. | 6 | `app/Controllers/InscripcionPublicaController.php`, `app/Services/InscripcionService.php`, `app/Repositories/InscripcionRepository.php` | `inscripciones`, `estudiantes`, `encargados`, `estudiantes_encargados` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase6.php` (T6-06, T6-18, T6-19, T6-25) | APROBADO (30/30) |
| RF-12 | **Confirmación.** Generar un código único de confirmación para cada inscripción aceptada. | 6 | `app/Services/InscripcionService.php`, `app/Repositories/InscripcionRepository.php` | `inscripciones` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase6.php` (T6-07, T6-23) | APROBADO (30/30) |
| RF-13 | **Comprobante PDF.** Generar un comprobante de inscripción en PDF. | 11 | Por definir en Fase 11 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-14 | **Consulta por código.** Permitir al encargado consultar la información básica mediante el código sin crear una cuenta. | 6 | `app/Controllers/InscripcionPublicaController.php`, `app/Services/InscripcionService.php`, `views/inscripciones/consultar.php` | `inscripciones`, `estudiantes`, `encargados`, `secciones` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase6.php` (T6-08, T6-09, T6-24, T6-25) | APROBADO (30/30) |
| RF-15 | **Gestión de docentes.** Registrar y administrar la información de docentes. | 4 | `app/Controllers/AdminDocenteController.php`, `app/Services/DocenteService.php`, `app/Repositories/DocenteRepository.php` | `docentes`, `usuarios` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase4.php` (T4-05 a T4-08) | APROBADO (27/27) |
| RF-16 | **Asignaciones.** Asignar docentes a grados, secciones y aulas. | 5 | `AdminAsignacionController.php`, `AsignacionService.php`, `AsignacionRepository.php`, `AsignacionDocente.php` | `asignaciones_docentes`, `docentes`, `secciones`, `grados_periodos`, `aulas` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase5.php` (T5-01 a T5-26) | APROBADO (26/26) |
| RF-17 | **Listado de estudiantes.** Permitir al docente consultar estudiantes de su sección asignada. | 7 | `app/Controllers/DocenteController.php`, `app/Services/PortalDocenteService.php`, `app/Repositories/PortalDocenteRepository.php`, `views/docente/index.php` | `asignaciones_docentes`, `secciones`, `inscripciones`, `estudiantes` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase7.php` (T7-01 a T7-21, T7-28) | APROBADO (28/28) |
| RF-18 | **Configuración de ubicación.** Definir la ubicación de la institución y el radio permitido para el marcaje. | 8 | Por definir en Fase 8 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-19 | **Validación geográfica.** Validar que el dispositivo esté dentro del área autorizada antes del marcaje. | 8 | Por definir en Fase 8 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-20 | **Marcaje de entrada.** Registrar la entrada mediante un botón cuando se cumpla la validación de ubicación. | 8 | Por definir en Fase 8 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-21 | **Marcaje de salida.** Registrar la salida mediante un botón cuando corresponda. | 8 | Por definir en Fase 8 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-22 | **Registro de fecha y hora.** Almacenar usuario/docente, fecha, hora y tipo de cada marcaje. | 8 | Por definir en Fase 8 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-23 | **Historial de asistencia.** Permitir al docente consultar sus marcajes y a administración consultar registros autorizados. | 8 | Por definir en Fase 8 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-24 | **Solicitud de permiso.** Registrar solicitudes indicando fecha, motivo y descripción. | 9 | Por definir en Fase 9 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-25 | **Documento de respaldo.** Adjuntar un archivo de respaldo a una solicitud. | 9 | Por definir en Fase 9 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-26 | **Notificación de permiso.** Notificar a administración cuando exista una nueva solicitud. | 10 | Por definir en Fase 10 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-27 | **Aprobación/rechazo.** Aprobar o rechazar solicitudes y registrar la decisión. | 9 | Por definir en Fase 9 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-28 | **Estado de solicitud.** Permitir al docente consultar el estado e historial de sus solicitudes. | 9 | Por definir en Fase 9 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-29 | **Reporte de asistencia.** Generar reportes por docente y período. | 12 | Por definir en Fase 12 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-30 | **Consulta de inscripciones.** Consultar estudiantes inscritos, grado, sección y estado de cupo. | 12 | Por definir en Fase 12 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-31 | **Integración de módulos.** Actualizar el listado de la sección mediante inscripciones confirmadas. | 6/7 | `app/Repositories/PortalDocenteRepository.php`, `app/Services/InscripcionService.php` | `inscripciones`, `secciones`, `estudiantes`, `asignaciones_docentes` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase7.php` (T7-19, T7-20), `tests/run_tests_fase6.php` | APROBADO (28/28) |
| RF-32 | **Integridad de cupos.** Evitar que operaciones concurrentes superen la capacidad de una sección. | 6 | `app/Services/InscripcionService.php`, `app/Repositories/InscripcionRepository.php` | `inscripciones`, `secciones` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase6.php` (T6-20, T6-21) | APROBADO (30/30) |

**Conteo verificado:** 32 RF únicos, rango continuo RF-01 a RF-32, sin
duplicados ni requisitos añadidos.

## Requerimientos no funcionales oficiales

| ID | Categoría y descripción | Fase asignada | Archivos relacionados | Tablas relacionadas | Estado | Prueba | Resultado |
|---|---|---:|---|---|---|---|---|
| RNF-01 | **Usabilidad.** Formularios, botones claros y mensajes comprensibles para usuarios no técnicos. | 2/15 | Por definir | No aplica | PENDIENTE | No ejecutada | Pendiente |
| RNF-02 | **Compatibilidad.** Funcionamiento en navegadores modernos y dispositivos compatibles con geolocalización. | 2/15 | Por definir | No aplica | PENDIENTE | No ejecutada | Pendiente |
| RNF-03 | **Seguridad.** Contraseñas no almacenadas en texto plano y acceso controlado por roles. | 3/14 | `app/Services/AuthService.php`, `app/Middleware/RequireRole.php` | `usuarios`, `roles` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase3.php` (T3-01 a T3-16) | APROBADO (19/19) |
| RNF-04 | **Privacidad.** Datos personales accesibles solo a usuarios autorizados. | 14 | `app/Services/InscripcionService.php`, `views/inscripciones/consultar.php` (Fase 6), `app/Repositories/PortalDocenteRepository.php`, `views/docente/index.php` (Fase 7) / Alcance global en Fase 14 | `estudiantes`, `encargados`, `inscripciones` | PENDIENTE | `tests/run_tests_fase6.php` (T6-08, T6-24), `tests/run_tests_fase7.php` (T7-08, T7-10, T7-16, T7-28) [Evidencia parcial en Fases 6 y 7] | EVIDENCIA PARCIAL VERIFICADA EN FASES 6 Y 7. Validación integral pendiente para Fase 14. |
| RNF-05 | **Integridad.** Relaciones consistentes para inscripción, cupos y asistencia. | 1/14 | `database/schema.sql`, `database/tests/validacion.sql` | 20 tablas relacionales | IMPLEMENTADO Y VALIDADO | `database/tests/validacion.sql` (Fase 1) | APROBADO (18/18) |
| RNF-06 | **Trazabilidad.** Fecha y usuario asociado en asistencia y decisiones sobre permisos. | 8/9/14 | Por definir | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RNF-07 | **Rendimiento.** Respuesta en tiempo razonable bajo la carga prevista. | 15 | Por definir | No aplica | PENDIENTE | No ejecutada | Pendiente |
| RNF-08 | **Mantenibilidad.** Separación de presentación, negocio y acceso a datos. | 2 | Arquitectura MVC/CSR en capas | No aplica | IMPLEMENTADO Y VALIDADO | `tests/run_tests.php` (T2-01 a T2-16) | APROBADO (16/16) |
| RNF-09 | **Configurabilidad.** Grados, secciones, capacidades, aulas y ubicación modificables sin alterar código. | 4/8 | Módulos `AdminGradoController`, `AdminSeccionController`, `AdminAulaController` | `grados`, `secciones`, `aulas` | IMPLEMENTADO Y VALIDADO | `tests/run_tests_fase4.php` (T4-13 a T4-20) | APROBADO (27/27) |
| RNF-10 | **Disponibilidad.** Dependencia de la infraestructura de alojamiento del prototipo o instalación futura. | 15/16 | Por definir | No aplica | PENDIENTE | No ejecutada | Pendiente |

**Conteo verificado:** 10 RNF únicos, rango continuo RNF-01 a RNF-10, sin
convertir reglas, criterios, limitaciones o decisiones técnicas en RNF.

## Reglas de negocio

Los identificadores `RN-DOC` son referencias internas de trazabilidad y no
sustituyen identificadores oficiales.

| ID interno | Regla respaldada por la sección 11 |
|---|---|
| RN-DOC-01 | No superar la capacidad configurada de una sección. |
| RN-DOC-02 | No mostrar secciones llenas para nuevas inscripciones. |
| RN-DOC-03 | Informar ausencia de cupos cuando todas las secciones habilitadas estén llenas. |
| RN-DOC-04 | No implementar lista de espera en la primera versión. |
| RN-DOC-05 | Configurar cantidad de secciones y capacidad por período, sin fijarlas en código. |
| RN-DOC-06 | Generar un código por cada inscripción confirmada. |
| RN-DOC-07 | No crear cuenta para padre, madre o encargado en la primera versión. |
| RN-DOC-08 | Exigir autenticación docente antes del marcaje. |
| RN-DOC-09 | Solicitar y validar ubicación antes del marcaje. |
| RN-DOC-10 | Aceptar el marcaje solo dentro del radio autorizado. |
| RN-DOC-11 | Registrar entrada y salida con fecha y hora. |
| RN-DOC-12 | Impedir acceso docente a funciones administrativas. |
| RN-DOC-13 | Mantener estado e historial de permisos. |
| RN-DOC-14 | Relacionar grados, secciones, aulas y asignaciones para alimentar el listado docente. |

## Criterios de aceptación

Los identificadores `CA-DOC` son referencias internas de trazabilidad.

| ID interno | Criterio respaldado por la sección 14 |
|---|---|
| CA-DOC-01 | Crear grados, secciones y capacidades sin modificar código. |
| CA-DOC-02 | Impedir superar el cupo máximo. |
| CA-DOC-03 | Asociar inscripción confirmada con grado y sección. |
| CA-DOC-04 | Mostrar al docente los estudiantes de su sección. |
| CA-DOC-05 | Permitir el marcaje solo después de validar ubicación. |
| CA-DOC-06 | Conservar fecha y hora de asistencia. |
| CA-DOC-07 | Enviar, notificar, decidir y consultar permisos. |
| CA-DOC-08 | Generar comprobante PDF de inscripción. |
| CA-DOC-09 | Generar reporte de asistencia docente. |
| CA-DOC-10 | Mostrar solo funciones correspondientes al rol. |
| CA-DOC-11 | Documentar casos exitosos y de validación/error. |

## Limitaciones y consideraciones

Los identificadores `LIM-DOC` son referencias internas de trazabilidad.

| ID interno | Limitación respaldada por la sección 15 |
|---|---|
| LIM-DOC-01 | La precisión geográfica depende del dispositivo, navegador, permisos y servicios de geolocalización. |
| LIM-DOC-02 | Las notificaciones dependen del correo o mecanismo que se defina. |
| LIM-DOC-03 | Los datos institucionales pendientes deben validarse antes de la entrega final. |
| LIM-DOC-04 | La infraestructura, conectividad y alojamiento deben verificarse con la institución. |
| LIM-DOC-05 | Los datos de estudiantes y encargados exigen confidencialidad y acceso restringido. |
| LIM-DOC-06 | Los cambios de alcance deben aprobarse para evitar ampliaciones no controladas. |

## Observaciones de trazabilidad

- La Fase 1 diseñó y validó las tablas, claves, relaciones e índices descritos
  en la actualización siguiente. Esto constituye soporte de datos, no
  implementación funcional de los RF.
- No se definieron rutas, clases o archivos de aplicación; corresponden a fases posteriores.
- La fuente oficial distingue expresamente RF, RNF, reglas de negocio,
  criterios de aceptación y limitaciones; esta matriz conserva esa separación.

## Actualización controlada de soporte de datos — Fase 1

Los estados funcionales de RF y RNF permanecen `PENDIENTE`. Solo se registra el
soporte físico ya disponible y validado en `database/schema.sql`,
`database/seed.sql` y `database/tests/validacion.sql`.

| Requisitos respaldados | Tablas de soporte creadas | Estado de Fase 1 | Evidencia de Fase 1 |
|---|---|---|---|
| RF-01 a RF-03; RNF-03 | `roles`, `usuarios`, `docentes`, `tokens_recuperacion` | SOPORTE DE DATOS IMPLEMENTADO | PK, FK, UNIQUE, NOT NULL y token vigente validados. |
| RF-04, RF-05; RNF-04 | `estudiantes`, `encargados`, `estudiantes_encargados` | SOPORTE DE DATOS IMPLEMENTADO | Relación estudiante–encargado validada con datos ficticios. |
| RF-06 a RF-10, RF-32; RNF-05, RNF-09 | `periodos_escolares`, `grados`, `grados_periodos`, `secciones`, `inscripciones` | SOPORTE DE DATOS IMPLEMENTADO | Capacidad 30/30, disponibilidad cero y decisión `RECHAZAR` validadas. |
| RF-11, RF-12, RF-14, RF-30, RF-31 | `inscripciones` y relaciones escolares | SOPORTE DE DATOS IMPLEMENTADO | Consulta integral estudiante–encargado–sección–grado–período validada. |
| RF-15 a RF-17 | `docentes`, `aulas`, `asignaciones_docentes` | SOPORTE DE DATOS IMPLEMENTADO | Consulta docente–sección–aula y unicidad de asignación activa validadas. |
| RF-18 a RF-23, RF-29; RNF-06 | `configuraciones_ubicacion`, `marcajes_asistencia` | SOPORTE DE DATOS IMPLEMENTADO | Marcaje ficticio dentro del radio persistido y relacionado. |
| RF-24 a RF-28; RNF-06 | `solicitudes_permiso`, `historial_solicitudes_permiso`, `documentos_permiso`, `notificaciones` | SOPORTE DE DATOS IMPLEMENTADO | Flujo relacional completo validado. |
| RF-13 | No requiere tabla propia; se genera desde `inscripciones` y sus relaciones. | SOPORTE DE DATOS IMPLEMENTADO | Decisión de datos documentada; generación PDF sigue pendiente. |

La prueba ejecutada y sus 18 resultados se documentan en
`docs/fase-1/pruebas-base-datos.md`.

## Actualización controlada de infraestructura base — Fase 2

Los estados funcionales de los requerimientos funcionales (RF-01 a RF-32) permanecen `PENDIENTE`.
La Fase 2 implementa exclusivamente la infraestructura técnica base (arquitectura en capas, front controller, configuración desacoplada con validación fail-fast, conexión centralizada PDO, enrutador GET/POST con aislamiento estricto de rutas técnicas en producción, defensa perimetral multicapa, layouts, helpers de escape XSS y suite de pruebas automatizadas T2-01 a T2-16).

| Requerimientos / Normas respaldadas | Componentes técnicos de infraestructura creados | Estado de Fase 2 | Evidencia de Fase 2 |
|---|---|---|---|
| RNF-08 (Mantenibilidad / Arquitectura) | `app/Core/App.php`, `Controller.php`, `View.php`, `public/index.php` | INFRAESTRUCTURA PREPARADA | Separación estricta entre presentación, controladores, lógica y datos verificada en T2-01, T2-05, T2-09, T2-13. |
| RNF-09 (Configurabilidad) | `App\Core\Env.php`, `App\Core\Config.php`, `.env.example`, `config/*.php` | INFRAESTRUCTURA PREPARADA | Configuración separada del código fuente sin exponer secretos y validación fail-fast estricta, verificada en T2-02, T2-11, T2-15. |
| RNF-03 (Seguridad base / Protección de datos) | `App\Core\Database.php`, `DatabaseException.php`, `Helpers/helpers.php` (`e()`), `.htaccess`, `storage/.htaccess` | INFRAESTRUCTURA PREPARADA | Enmascaramiento de errores de BD, escape HTML contra inyecciones XSS y blindaje perimetral contra acceso a archivos internos verificados en T2-04, T2-10, T2-14. |
| RNF-05 (Integridad de datos) | Capa PDO centralizada con emulación desactivada y charset `utf8mb4` | INFRAESTRUCTURA PREPARADA | Conexión PDO validada en T2-03, integridad estática validada en T2-12 y regresión funcional completa en BD temporal validada en T2-16. |
| RF-01 a RF-32 (Requerimientos funcionales) | `App\Core\Router.php`, `Request.php`, `Response.php`, `MiddlewareInterface.php` | PENDIENTE DE IMPLEMENTACIÓN FUNCIONAL | Enrutamiento GET/POST y aislamiento de rutas técnicas probados en T2-05 a T2-08 y T2-13; módulos funcionales se implementarán a partir de Fase 3. |

La suite automatizada de pruebas y sus 16 resultados se documentan en `docs/fase-2/pruebas.md`.

## Actualización controlada de Autenticación y Roles — Fase 3

La Fase 3 implementa de forma completa y funcional el subsistema de autenticación, control de sesiones, protección CSRF y autorización por roles para los perfiles `ADMINISTRADOR` y `DOCENTE`.

| Requisitos respaldados | Componentes funcionales creados | Estado de Fase 3 | Evidencia de Fase 3 |
|---|---|---|---|
| **RF-01** (Autenticación: permitir iniciar sesión mediante credenciales a administrativos y docentes; con mecanismo técnico complementario de cierre de sesión seguro) | `AuthController::login()`, `AuthController::logout()`, `AuthService::attempt()`, `AuthService::logout()`, `UserRepository::findByUsername()`, `Session::destroy()`, `views/auth/login.php` | IMPLEMENTADO Y VERIFICADO | Formulario POST `/login`, verificación de credenciales con `password_verify()`, verificación de estado `ACTIVO`, regeneración de sesión CLI y HTTP real validadas en T3-01 a T3-06 y T3-19. Cierre de sesión seguro en POST `/logout` con token CSRF, destrucción de sesión e invalidación de cookies validado en T3-12. |
| **RF-02** (Roles y permisos: restringir las funciones de acuerdo con el rol del usuario) | `RequireRole.php`, `Authenticate.php`, `AdminController.php`, `DocenteController.php`, `views/errors/403.php` | IMPLEMENTADO Y VERIFICADO | Segregación estricta en backend entre áreas `/administracion` (`ADMINISTRADOR`) y `/docente` (`DOCENTE`), bloqueo con HTTP 403 Forbidden ante accesos no autorizados y revalidación de rol en tiempo real contra base de datos validadas en T3-07 a T3-11, T3-13 y T3-15. |
| **RNF-03** (Seguridad: contraseñas no almacenadas en texto plano y acceso controlado por roles) | `Session.php`, `Csrf.php`, `VerifyCsrfToken.php`, `User.php`, `App\Core\Helpers` | IMPLEMENTADO Y VERIFICADO | Contraseñas protegidas exclusivamente con BCrypt nativo (`PASSWORD_BCRYPT`), cookies de sesión configuradas con flags seguros (`HttpOnly`, `SameSite=Lax`, `Secure` condicional), mitigación rigurosa de Session Fixation (T3-06 y T3-19), validación CSRF obligatoria (T3-14) y confidencialidad absoluta sin fuga de secretos o hashes (T3-16). |
| **RNF-04** (Privacidad: datos personales accesibles solo a usuarios autorizados) | Arquitectura base de roles y autenticación (`User`, `UserRepository`, middlewares) | ASIGNADO A FASE 14 | La autenticación y autorización por roles implementadas en Fase 3 constituyen el fundamento técnico para el cumplimiento integral de la privacidad asignado a la Fase 14. |
| **RF-03** (Recuperación de contraseña: correo electrónico o enlace/código) | - | PENDIENTE DE IMPLEMENTACIÓN | Fuera del alcance de la Fase 3; asignado oficialmente a Fase 13. |
| **RF-04 a RF-32** (Módulos funcionales de negocio) | - | PENDIENTE DE IMPLEMENTACIÓN FUNCIONAL | Corresponden a las Fases 4 a 12 conforme a la planificación oficial del proyecto. |

La suite automatizada de pruebas y sus 19 resultados se documentan en `docs/fase-3/pruebas.md`.

## Actualización controlada del Portal Docente — Fase 7

La Fase 7 transforma el área docente provisional en un portal de producción robusto, enfocado en la consulta de nóminas de estudiantes por sección asignada y su integración canónica con las inscripciones en línea.

| Requisitos respaldados | Componentes funcionales creados | Estado de Fase 7 | Evidencia de Fase 7 |
|---|---|---|---|
| **RF-17** (Listado de estudiantes: permitir al docente consultar los estudiantes de su sección asignada) | `DocenteController::index()`, `PortalDocenteService::getPortalData()`, `PortalDocenteRepository::findEstudiantesBySeccionDocente()`, `views/docente/index.php` | IMPLEMENTADO Y VALIDADO | Aislamiento horizontal estricto de dos capas (controlador HTTP 403 y JOIN SQL obligatorio `ad.id_docente`), identidad derivada exclusivamente de sesión (`auth_user_id`), soporte de multiasignación y degradación elegante ante docentes sin sección asignada. Verificado en T7-01 a T7-18 y T7-21. |
| **RF-31** (Integración de módulos: actualizar el listado de la sección mediante inscripciones confirmadas) | `PortalDocenteRepository::findEstudiantesBySeccionDocente()`, `PortalDocenteRepository::countEstudiantesBySeccionDocente()`, flujo transaccional de Fase 6 | IMPLEMENTADO Y VALIDADO | Integración canónica directa sin duplicación de datos: cualquier inscripción confirmada (`CONFIRMADA`) en Fase 6 se refleja inmediatamente en el portal del docente titular, excluyendo inscripciones canceladas. Verificado en T7-19 y T7-20. |
| **RNF-04** (Privacidad: datos personales accesibles solo a usuarios autorizados) | `PortalDocenteRepository` (proyección selectiva), `views/docente/index.php` | EVIDENCIA PARCIAL VERIFICADA EN FASE 7 | Minimización de datos en nómina escolar docente: solo se exponen datos estrictamente pedagógicos (`codigo_estudiante`, `nombres`, `apellidos`, `fecha_nacimiento`, `estado_inscripcion`). Se aíslan y omiten registros de encargados, teléfonos, emails, direcciones domiciliarias, contraseñas e IDs internos. Verificado en T7-08, T7-10 y T7-16. Validación integral de privacidad permanece en estado `PENDIENTE` para Fase 14. |
| **RN-DOC-04, RN-DOC-12, RN-DOC-14, CA-DOC-04, CA-DOC-10** | Módulo Portal Docente | IMPLEMENTADO Y VALIDADO | Cumplimiento estricto de reglas de negocio institucionales y criterios de aceptación del documento de alcance. |

La suite automatizada de pruebas y sus 28 resultados se documentan en `docs/fase-7/pruebas.md`.

## Actualización controlada de Asistencia Docente y Geolocalización — Fase 8

La Fase 8 implementa en su totalidad el subsistema de geocerca institucional, validación geográfica geodésica mediante Haversine, registro inmutable de entrada y salida con fecha/hora autoritativas de servidor, y consulta personal de historial con aislamiento horizontal estricto.

| Requisitos respaldados | Componentes funcionales creados | Estado de Fase 8 | Evidencia de Fase 8 |
|---|---|---|---|
| **RF-18** (Configuración de ubicación: permitir a la administración registrar las coordenadas del centro educativo y el radio permitido para el marcaje de asistencia) | `AdminUbicacionController`, `UbicacionService`, `UbicacionRepository`, `views/admin/ubicacion/*` | IMPLEMENTADO Y VALIDADO | Panel administrativo `/administracion/ubicacion` protegido con RBAC (`ADMINISTRADOR`). Validación de rango geográfico ($\text{latitud} \in [-90, 90]$, $\text{longitud} \in [-180, 180]$, $\text{radio} > 0$, $\text{descripción} \le 150$). Versionado transaccional y unicidad condicional en BD (`uk_configuraciones_ubicacion_activa`). Verificado en T8-01 a T8-08. |
| **RF-19** (Validación geográfica: validar que el docente se encuentre dentro del radio autorizado antes de permitir el registro de asistencia) | `AsistenciaService::calcularDistanciaHaversine()`, `AsistenciaService::isDentroRadio()`, `AsistenciaService::procesarMarcaje()` | IMPLEMENTADO Y VALIDADO | Cálculo geodésico autoritativo en backend con radio medio terrestre $R = 6,371,000$ metros. Inclusión estricta de frontera ($\text{distancia} \le \text{radio}$). Rechazo sin persistencia (HTTP 422 / DomainException) ante intentos fuera de radio o ausencia de geocerca activa. Parámetros maliciosos de cliente descartados (whitelist). Verificado en T8-09 a T8-13, T8-29 y T8-30. |
| **RF-20** (Marcaje de entrada: permitir al docente registrar su hora de entrada) | `DocenteAsistenciaController::marcarEntrada()`, `AsistenciaService::registrarEntrada()`, `AsistenciaRepository::crearMarcaje()` | IMPLEMENTADO Y VALIDADO | Endpoint POST `/docente/asistencia/entrada` protegido con sesión DOCENTE activa y token CSRF. Captura de coordenadas con `navigator.geolocation`. Validación de máquina de estados: impide doble entrada sin salida previa. Verificado en T8-10, T8-14, T8-15 y T8-29. |
| **RF-21** (Marcaje de salida: permitir al docente registrar su hora de salida) | `DocenteAsistenciaController::marcarSalida()`, `AsistenciaService::registrarSalida()`, `AsistenciaRepository::crearMarcaje()` | IMPLEMENTADO Y VALIDADO | Endpoint POST `/docente/asistencia/salida` con token CSRF. Exige entrada previa para cerrar jornada (impide salidas huérfanas o duplicadas). Secuencia completa alternada validada en T8-16 a T8-19 y T8-29. |
| **RF-22** (Registro de fecha y hora: guardar la fecha y la hora del marcaje con base en la información del servidor) | `AsistenciaRepository::crearMarcaje()`, columna `fecha_hora` | IMPLEMENTADO Y VALIDADO | Estampado temporal generado exclusivamente por el motor relacional mediante `CURRENT_TIMESTAMP(6)` con microsegundos. Parámetros temporales del cliente son ignorados por diseño. Verificado en T8-20. |
| **RF-23** (Historial de asistencia: permitir al docente consultar su historial de marcajes) | `DocenteAsistenciaController::index()`, `DocenteAsistenciaController::historial()`, `AsistenciaRepository::findMarcajesByDocente()`, `views/docente/asistencia/index.php` | IMPLEMENTADO Y VALIDADO | Consulta ordenada cronológicamente descendente (`fecha_hora DESC`). Aislamiento horizontal absoluto: docente solo accede a sus propios registros derivados de la sesión autenticada (`auth_user_id`). Verificado en T8-24, T8-25 y T8-28. |
| **RNF-06** (Trazabilidad: registrar operaciones clave mediante bitácoras o mecanismos de auditoría) | `AsistenciaRepositoryInterface`, tabla `marcajes_asistencia` | EVIDENCIA PARCIAL VERIFICADA EN FASE 8 | Inmutabilidad técnica de marcajes: la interfaz del repositorio carece de métodos `update` y `delete`. Restricción física en base de datos mediante `CONSTRAINT chk_marcajes_dentro_radio CHECK (dentro_radio = 1)`. Verificado en T8-26 y T8-27. La auditoría global del sistema permanece asignada a la fase correspondiente. |
| **RF-29** (Reporte de asistencia: generar reportes de asistencia docente por rango de fechas) | - | PENDIENTE DE IMPLEMENTACIÓN | Requisito administrativo explícitamente delimitado para la Fase 12 (Reportes y Exportación). Cero implementación anticipada. |
| **RN-DOC-08, RN-DOC-09, RN-DOC-10, RN-DOC-11, CA-DOC-05, CA-DOC-06, LIM-DOC-01** | Módulo de Asistencia y Geolocalización | IMPLEMENTADO Y VALIDADO | Cumplimiento exhaustivo de reglas de negocio, criterios de aceptación y gestión de limitaciones documentadas de contexto seguro/navegador. |

La suite automatizada de pruebas y sus 40 resultados se documentan en `docs/fase-8/pruebas.md`.

## Actualización controlada de Gestión de Permisos Docentes — Fase 9

La Fase 9 implementa de forma íntegra el subsistema de gestión de solicitudes de permisos docentes, carga y validación estricta de documentos de soporte (PDF, PNG, JPG hasta 5MB), almacenamiento seguro fuera del document root con nombres hasheados mediante SHA-256, bandeja administrativa con filtros, resolución administrativa con bloqueo pesimista (`SELECT ... FOR UPDATE`), historial de auditoría inmutable append-only y delimitación estricta excluyendo el módulo de notificaciones (asignado a Fase 10).

| Requisitos respaldados | Componentes funcionales creados | Estado de Fase 9 | Evidencia de Fase 9 |
|---|---|---|---|
| **RF-24** (Registro de solicitudes: permitir al docente ingresar una solicitud de permiso especificando fecha y motivo) | `DocentePermisoController::crear()`, `DocentePermisoController::guardar()`, `PermisoService::registrarSolicitud()`, `PermisoRepository::crearSolicitud()`, `views/docente/permisos/crear.php` | IMPLEMENTADO Y VERIFICADO | Formulario docente con validación estricta de fecha solicitada (formato ISO `YYYY-MM-DD`, obligatoria), tipo de motivo institucional (`ENFERMEDAD`, `PERSONAL`, `CAPACITACION`, `OTRO`), descripción detallada obligatoria (hasta 500 caracteres), derivación autoritativa de identidad desde sesión segura (`auth_user_id`), descarte de manipulación de cliente y estado inicial inmutable `PENDIENTE`. Verificado en T9-01 a T9-08, T9-29 y T9-33. |
| **RF-25** (Documentos de soporte: permitir adjuntar archivos como justificación del permiso) | `PermisoService::guardarDocumento()`, `PermisoService::validarArchivoSoporte()`, `PermisoRepository::crearDocumento()`, `views/docente/permisos/crear.php` | IMPLEMENTADO Y VERIFICADO | Carga multipart opcional con validación dual: extensión permitida (`pdf`, `png`, `jpg`, `jpeg`) y detección de tipo MIME real por magic bytes mediante `finfo(FILEINFO_MIME_TYPE)`. Límite de tamaño estricto de 5MB. Escaneo preventivo de contenido ejecutable malicioso (`<?php`, `<script>`). Almacenamiento privado fuera del document root (`storage/permisos/`) con nombres únicos hasheados SHA-256 (`{sha256}.{ext}`) para mitigar Path Traversal. Protección perimetral con `.htaccess` (`Require all denied`). Verificado en T9-09 a T9-14, T9-33 y T9-35. |
| **RF-26** (Notificación de permiso: notificar al docente el resultado de su solicitud) | - | PENDIENTE — FASE 10 | Requisito formalmente asignado a la Fase 10 (Notificaciones). En Fase 9 no se implementaron envíos ni se crearon registros en la tabla `notificaciones`. Verificado en T9-36 ($\Delta \text{notificaciones} = 0$). |
| **RF-27** (Aprobación de permisos: permitir al administrador aprobar o rechazar solicitudes de permiso) | `AdminPermisoController::index()`, `AdminPermisoController::ver()`, `AdminPermisoController::resolver()`, `PermisoService::resolverSolicitud()`, `PermisoRepository::findForUpdate()`, `views/admin/permisos/*` | IMPLEMENTADO Y VERIFICADO | Bandeja administrativa protegida con RBAC (`ADMINISTRADOR`), con filtros por estado (`PENDIENTE`, `APROBADO`, `RECHAZADO`) y docente. Resolución atómica de aprobación o rechazo con comentarios obligatorios en rechazo. Bloqueo pesimista `SELECT ... FOR UPDATE` para concurrencia segura. Inmutabilidad de estados terminales (impide re-resolución). Verificado en T9-20 a T9-25, T9-31, T9-32 y T9-34. |
| **RF-28** (Consulta de estado: permitir al docente consultar el estado de sus solicitudes) | `DocentePermisoController::index()`, `DocentePermisoController::ver()`, `PermisoRepository::findByDocente()`, `views/docente/permisos/index.php`, `views/docente/permisos/ver.php` | IMPLEMENTADO Y VERIFICADO | Listado y detalle personal de permisos con aislamiento horizontal estricto: el docente solo puede acceder a solicitudes propias derivadas de su sesión autenticada (`auth_user_id`). Denegación inmediata con `HTTP 403 Forbidden` ante intentos de manipulación de ID de solicitud ajena. Verificado en T9-18, T9-19 y T9-33. |
| **RNF-06** (Trazabilidad: registrar operaciones clave mediante bitácoras o mecanismos de auditoría) | `PermisoRepository::crearHistorial()`, tabla `historial_solicitudes_permiso`, `HistorialSolicitudPermiso` | EVIDENCIA PARCIAL VERIFICADA EN FASE 9 | Bitácora inmutable append-only: cada transición de estado registra atómicamente estado anterior, estado nuevo, usuario responsable, fecha/hora exacta del servidor (`CURRENT_TIMESTAMP`) y comentarios de auditoría. La interfaz del repositorio carece de métodos `update` o `delete`. Verificado en T9-03, T9-23, T9-27 y T9-28. Auditoría integral del sistema permanece asignada a la Fase 14. |
| **RN-DOC-05, RN-DOC-06, RN-DOC-07, CA-DOC-07, CA-DOC-08, CA-DOC-09** | Módulo de Gestión de Permisos Docentes | IMPLEMENTADO Y VALIDADO | Cumplimiento riguroso de reglas de negocio institucionales y criterios de aceptación del documento de alcance. |

La suite automatizada de pruebas y sus 44 resultados se documentan en `docs/fase-9/pruebas.md`.





