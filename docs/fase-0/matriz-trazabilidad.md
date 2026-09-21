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

- No se diseñaron tablas, claves ni relaciones físicas; corresponden a la Fase 1.
- No se definieron rutas, clases o archivos de aplicación; corresponden a fases posteriores.
- La fuente oficial distingue expresamente RF, RNF, reglas de negocio,
  criterios de aceptación y limitaciones; esta matriz conserva esa separación.
