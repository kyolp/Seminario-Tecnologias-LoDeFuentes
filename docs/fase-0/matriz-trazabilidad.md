# Matriz de trazabilidad inicial

Esta matriz contiene únicamente requerimientos expresos del Prompt Maestro. La
columna “Archivos relacionados” indica artefactos previstos por fase, no
archivos implementados. Todos permanecen `PENDIENTE` porque Fase 0 no implementa
funcionalidad y no existe todavía una prueba satisfactoria de cada requisito.

## Requerimientos funcionales

| ID | Descripción | Fase asignada | Archivos relacionados | Tablas relacionadas | Estado | Prueba | Resultado |
|---|---|---:|---|---|---|---|---|
| RF-001 | Iniciar sesión. | 3 | Por definir en Fase 3 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-002 | Cerrar sesión. | 3 | Por definir en Fase 3 | Sesiones por definir | PENDIENTE | No ejecutada | Pendiente |
| RF-003 | Aplicar roles y proteger rutas. | 3 | Por definir en Fase 3 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-004 | Gestionar docentes. | 4 | Por definir en Fase 4 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-005 | Gestionar estudiantes. | 4 | Por definir en Fase 4 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-006 | Gestionar grados. | 4 | Por definir en Fase 4 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-007 | Gestionar secciones y sus capacidades. | 4 | Por definir en Fase 4 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-008 | Gestionar aulas. | 4 | Por definir en Fase 4 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-009 | Gestionar períodos. | 4 | Por definir en Fase 4 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-010 | Asignar docente, grado, sección y aula. | 5 | Por definir en Fase 5 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-011 | Registrar datos del estudiante en una inscripción. | 6 | Por definir en Fase 6 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-012 | Registrar datos del encargado en una inscripción. | 6 | Por definir en Fase 6 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-013 | Seleccionar grado y mostrar secciones disponibles. | 6 | Por definir en Fase 6 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-014 | Validar el cupo antes de confirmar la inscripción. | 6 | Por definir en Fase 6 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-015 | Crear la inscripción y generar un código de confirmación. | 6 | Por definir en Fase 6 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-016 | Consultar una inscripción mediante su código. | 6 | Por definir en Fase 6 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-017 | Mostrar al docente su perfil y asignación. | 7 | Por definir en Fase 7 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-018 | Mostrar al docente grado, sección, aula y estudiantes asignados. | 7 | Por definir en Fase 7 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-019 | Configurar latitud, longitud y radio institucional. | 8 | Por definir en Fase 8 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-020 | Validar la ubicación del docente antes del marcaje. | 8 | Por definir en Fase 8 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-021 | Registrar entrada con fecha y hora. | 8 | Por definir en Fase 8 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-022 | Registrar salida con fecha y hora. | 8 | Por definir en Fase 8 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-023 | Consultar historial de asistencia. | 8 | Por definir en Fase 8 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-024 | Crear y enviar una solicitud de permiso docente. | 9 | Por definir en Fase 9 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-025 | Adjuntar documento a un permiso cuando corresponda. | 9 | Por definir en Fase 9 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-026 | Consultar estado e historial de permisos propios. | 9 | Por definir en Fase 9 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-027 | Consultar, aprobar o rechazar permisos y registrar la decisión. | 9 | Por definir en Fase 9 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-028 | Emitir las notificaciones requeridas por el alcance. | 10 | Por definir en Fase 10 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Catálogo no definido |
| RF-029 | Generar comprobante PDF de inscripción. | 11 | Por definir en Fase 11 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Formato no definido |
| RF-030 | Generar los reportes establecidos por el alcance. | 12 | Por definir en Fase 12 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Catálogo no definido |
| RF-031 | Recuperar la contraseña mediante un mecanismo seguro. | 13 | Por definir en Fase 13 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |
| RF-032 | Consultar el comprobante producido al concluir la inscripción. | 11 | Por definir en Fase 11 | Por definir en Fase 1 | PENDIENTE | No ejecutada | Pendiente |

## Requerimientos no funcionales

| ID | Descripción | Fase asignada | Archivos relacionados | Tablas relacionadas | Estado | Prueba | Resultado |
|---|---|---:|---|---|---|---|---|
| RNF-001 | Usar HTML, CSS y JavaScript en frontend, PHP en backend y MySQL como base de datos. | 2 | Por definir en Fase 2 | No aplica | PENDIENTE | No ejecutada | PHP/MySQL no disponibles |
| RNF-002 | Separar presentación, negocio y acceso a datos. | 2 | Por definir en Fase 2 | No aplica | PENDIENTE | No ejecutada | Pendiente |
| RNF-003 | Usar PDO y consultas preparadas. | 2/14 | Por definir | Todas las persistentes | PENDIENTE | No ejecutada | Pendiente |
| RNF-004 | Validar datos en el servidor. | 2/14 | Por definir | Según módulo | PENDIENTE | No ejecutada | Pendiente |
| RNF-005 | Escapar las salidas para prevenir XSS. | 2/14 | Por definir | No aplica | PENDIENTE | No ejecutada | Pendiente |
| RNF-006 | Usar `password_hash()` y `password_verify()` sin texto plano. | 3/14 | Por definir | Credenciales por definir | PENDIENTE | No ejecutada | Pendiente |
| RNF-007 | Proteger sesiones y regenerar su identificador. | 3/14 | Por definir | Sesiones por definir | PENDIENTE | No ejecutada | Pendiente |
| RNF-008 | Implementar control de acceso por roles. | 3/14 | Por definir | Por definir | PENDIENTE | No ejecutada | Pendiente |
| RNF-009 | Implementar protección CSRF. | 3/14 | Por definir | No aplica | PENDIENTE | No ejecutada | Pendiente |
| RNF-010 | Validar archivos cargados y controlar su acceso. | 9/14 | Por definir | Metadatos por definir | PENDIENTE | No ejecutada | Pendiente |
| RNF-011 | Gestionar errores sin exponer información sensible. | 2/14 | Por definir | No aplica | PENDIENTE | No ejecutada | Pendiente |
| RNF-012 | Usar `.env` y excluir credenciales reales de Git. | 2/14 | `.gitignore`; configuración por definir | No aplica | PENDIENTE | Inspección de Git | Protección inicial preparada |
| RNF-013 | Aplicar claves foráneas y restricciones de integridad. | 1/14 | `database/schema.sql` previsto | Todas las relacionadas | PENDIENTE | No ejecutada | Pendiente |
| RNF-014 | Impedir sobrecupo bajo concurrencia mediante transacción, bloqueo, validación final y rollback. | 6/14 | Por definir | Inscripción/sección por definir | PENDIENTE | No ejecutada | Pendiente |
| RNF-015 | No depender solo de JavaScript para validar geolocalización. | 8/14 | Por definir | Configuración/asistencia por definir | PENDIENTE | No ejecutada | Pendiente |
| RNF-016 | Mantener trazabilidad de requerimientos, archivos, datos, estados y pruebas. | 0-16 | `docs/fase-0/matriz-trazabilidad.md` | Todas | EN DESARROLLO | Revisión documental | Matriz inicial creada |
| RNF-017 | Controlar los cambios con Git y commits limitados a cada fase. | 0-16 | Repositorio Git | No aplica | EN DESARROLLO | `git status`, rama e historial | Repositorio inicializado |
| RNF-018 | Usar datos ficticios identificados cuando falte información oficial. | 0-16 | Según fase | Según fase | PENDIENTE | Revisión documental | No se necesitaron datos de prueba |

## Observaciones de trazabilidad

- No se asignaron tablas concretas: hacerlo correspondería a la Fase 1.
- No se asignaron rutas o clases concretas: hacerlo correspondería a la Fase 2
  o a la fase funcional respectiva.
- RF-028 y RF-030 no pueden desglosarse sin el catálogo oficial de
  notificaciones y reportes.
- RNF-016 y RNF-017 están en desarrollo porque se prepararon sus mecanismos,
  pero su cumplimiento abarca todo el proyecto.
