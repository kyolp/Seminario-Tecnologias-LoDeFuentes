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
| RF-01 | **Autenticación.** Permitir iniciar sesión a usuarios administrativos y docentes mediante credenciales. | 3 | Por definir en Fase 3 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-02 | **Roles y permisos.** Restringir las funciones de acuerdo con el rol del usuario. | 3 | Por definir en Fase 3 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-03 | **Recuperación de contraseña.** Recuperar el acceso mediante correo electrónico o enlace/código. | 13 | Por definir en Fase 13 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-04 | **Gestión de estudiantes.** Registrar, editar, consultar y desactivar estudiantes. | 4 | Por definir en Fase 4 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-05 | **Datos del encargado.** Registrar los datos necesarios del padre, madre o encargado relacionados con la inscripción. | 6 | Por definir en Fase 6 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-06 | **Gestión de grados.** Habilitar y administrar los grados disponibles para cada período. | 4 | Por definir en Fase 4 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-07 | **Gestión de secciones.** Crear, editar, habilitar o deshabilitar secciones por grado. | 4 | Por definir en Fase 4 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-08 | **Capacidad por sección.** Establecer la cantidad máxima de estudiantes permitida en cada sección. | 4 | Por definir en Fase 4 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-09 | **Control automático de cupos.** Impedir nuevas inscripciones cuando una sección alcance su capacidad. | 6 | Por definir en Fase 6 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-10 | **Disponibilidad.** Mostrar únicamente secciones con cupos disponibles durante la inscripción. | 6 | Por definir en Fase 6 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-11 | **Inscripción web.** Registrar una inscripción mediante formulario web. | 6 | Por definir en Fase 6 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-12 | **Confirmación.** Generar un código único de confirmación para cada inscripción aceptada. | 6 | Por definir en Fase 6 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-13 | **Comprobante PDF.** Generar un comprobante de inscripción en PDF. | 11 | Por definir en Fase 11 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-14 | **Consulta por código.** Permitir al encargado consultar la información básica mediante el código sin crear una cuenta. | 6 | Por definir en Fase 6 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-15 | **Gestión de docentes.** Registrar y administrar la información de docentes. | 4 | Por definir en Fase 4 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-16 | **Asignaciones.** Asignar docentes a grados, secciones y aulas. | 5 | Por definir en Fase 5 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-17 | **Listado de estudiantes.** Permitir al docente consultar estudiantes de su sección asignada. | 7 | Por definir en Fase 7 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
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
| RF-31 | **Integración de módulos.** Actualizar el listado de la sección mediante inscripciones confirmadas. | 6/7 | Por definir en Fases 6 y 7 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-32 | **Integridad de cupos.** Evitar que operaciones concurrentes superen la capacidad de una sección. | 6 | Por definir en Fase 6 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |

**Conteo verificado:** 32 RF únicos, rango continuo RF-01 a RF-32, sin
duplicados ni requisitos añadidos.

## Requerimientos no funcionales oficiales

| ID | Categoría y descripción | Fase asignada | Archivos relacionados | Tablas relacionadas | Estado | Prueba | Resultado |
|---|---|---:|---|---|---|---|---|
| RNF-01 | **Usabilidad.** Formularios, botones claros y mensajes comprensibles para usuarios no técnicos. | 2/15 | Por definir | No aplica | PENDIENTE | No ejecutada | Pendiente |
| RNF-02 | **Compatibilidad.** Funcionamiento en navegadores modernos y dispositivos compatibles con geolocalización. | 2/15 | Por definir | No aplica | PENDIENTE | No ejecutada | Pendiente |
| RNF-03 | **Seguridad.** Contraseñas no almacenadas en texto plano y acceso controlado por roles. | 3/14 | Por definir | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RNF-04 | **Privacidad.** Datos personales accesibles solo a usuarios autorizados. | 14 | Por definir | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RNF-05 | **Integridad.** Relaciones consistentes para inscripción, cupos y asistencia. | 1/14 | Por definir en Fase 1 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RNF-06 | **Trazabilidad.** Fecha y usuario asociado en asistencia y decisiones sobre permisos. | 8/9/14 | Por definir | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RNF-07 | **Rendimiento.** Respuesta en tiempo razonable bajo la carga prevista. | 15 | Por definir | No aplica | PENDIENTE | No ejecutada | Pendiente |
| RNF-08 | **Mantenibilidad.** Separación de presentación, negocio y acceso a datos. | 2 | Por definir en Fase 2 | No aplica | PENDIENTE | No ejecutada | Pendiente |
| RNF-09 | **Configurabilidad.** Grados, secciones, capacidades, aulas y ubicación modificables sin alterar código. | 4/8 | Por definir | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
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
| **RF-01** (Inicio de sesión) | `AuthController::login()`, `AuthService::attempt()`, `UserRepository::findByUsername()`, `views/auth/login.php` | IMPLEMENTADO Y VERIFICADO | Autenticación con `password_verify()`, verificación de estado activo y regeneración de sesión validadas en T3-01 a T3-06. |
| **RF-02** (Cierre de sesión) | `AuthController::logout()`, `AuthService::logout()`, `Session::destroy()` | IMPLEMENTADO Y VERIFICADO | Destrucción de sesión, invalidación de cookie y revocación de acceso a rutas privadas validadas en T3-12. |
| **RNF-03** (Seguridad de la información) | `Session.php`, `Csrf.php`, `VerifyCsrfToken.php`, `User.php` | IMPLEMENTADO Y VERIFICADO | BCrypt nativo, mitigación de Session Fixation, validación CSRF obligatoria y confidencialidad estricta validadas en T3-06, T3-14, T3-16. |
| **RNF-04** (Control de acceso basado en roles) | `RequireRole.php`, `Authenticate.php`, `AdminController.php`, `DocenteController.php` | IMPLEMENTADO Y VERIFICADO | Segregación estricta en backend entre ADMINISTRADOR y DOCENTE, con HTTP 403 y revalidación en tiempo real en BD validadas en T3-07 a T3-11, T3-13, T3-15. |
| **RF-03** (Recuperación de contraseña) | - | PENDIENTE DE IMPLEMENTACIÓN | Fuera del alcance de la Fase 3; corresponde a una fase posterior autorizada. |
| **RF-04 a RF-32** (Módulos funcionales de negocio) | - | PENDIENTE DE IMPLEMENTACIÓN FUNCIONAL | Corresponden a las Fases 4 a 14 conforme al plan maestro del proyecto. |

La suite automatizada de pruebas y sus 18 resultados se documentan en `docs/fase-3/pruebas.md`.



