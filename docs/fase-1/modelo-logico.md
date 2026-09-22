# Modelo lógico de datos — Fase 1

## Convenciones

- Tablas y columnas en español y `snake_case`.
- Claves primarias `BIGINT UNSIGNED AUTO_INCREMENT` con nombre `id_<entidad>`.
- Fechas operativas en `DATE`; instantes trazables en `DATETIME(6)`.
- Estados como `VARCHAR` más restricciones `CHECK`.
- Bajas lógicas mediante estado o `activo` solo en catálogos y entidades que
  requieren desactivación histórica.
- Relaciones críticas con `ON DELETE RESTRICT` y `ON UPDATE RESTRICT`.

## Relaciones lógicas

| Tabla | Clave primaria | Claves foráneas | Unicidad y reglas relevantes |
|---|---|---|---|
| `roles` | `id_rol` | — | `nombre` único; estado activo controlado. |
| `usuarios` | `id_usuario` | `id_rol → roles` | `nombre_usuario` y `correo` únicos; hash obligatorio; estado controlado. |
| `docentes` | `id_docente` | `id_usuario → usuarios` | Relación 1:1 mediante `id_usuario` único; estado controlado. |
| `estudiantes` | `id_estudiante` | — | Código institucional opcional y único; estado controlado. |
| `encargados` | `id_encargado` | — | Datos mínimos sin credenciales. |
| `estudiantes_encargados` | `id_estudiante_encargado` | Estudiante y encargado | Par estudiante–encargado único. |
| `periodos_escolares` | `id_periodo_escolar` | — | Nombre y fechas coherentes; un solo período activo mediante clave generada. |
| `grados` | `id_grado` | — | `codigo` y `nombre` únicos; orden positivo. |
| `grados_periodos` | `id_grado_periodo` | Período y grado | Combinación período–grado única. |
| `secciones` | `id_seccion` | `id_grado_periodo → grados_periodos` | Código único dentro del grado/período; capacidad mayor que cero. |
| `aulas` | `id_aula` | — | Código único; desactivación lógica. |
| `asignaciones_docentes` | `id_asignacion_docente` | Docente, sección, aula y usuario creador | Una asignación activa por sección mediante clave generada única. |
| `inscripciones` | `id_inscripcion` | Estudiante, encargado, vínculo estudiante–encargado, sección | Código de confirmación único; estudiante no se duplica en la misma sección. |
| `configuraciones_ubicacion` | `id_configuracion_ubicacion` | Usuario creador | Latitud, longitud y radio válidos; una configuración activa. |
| `marcajes_asistencia` | `id_marcaje_asistencia` | Docente, usuario y configuración | Tipo entrada/salida; evidencia geográfica consistente. |
| `solicitudes_permiso` | `id_solicitud_permiso` | Docente | Estado pendiente/aprobado/rechazado; fecha solicitada y descripción. |
| `historial_solicitudes_permiso` | `id_historial_permiso` | Solicitud y usuario actor | Estados controlados; secuencia cronológica indexada. |
| `documentos_permiso` | `id_documento_permiso` | Solicitud | Ruta almacenada única; tamaño no negativo; solo metadatos. |
| `notificaciones` | `id_notificacion` | Solicitud y usuario destinatario | Lectura posterior a creación; tipo limitado a permisos. |
| `tokens_recuperacion` | `id_token_recuperacion` | Usuario | Hash único; expiración posterior a creación; uso único. |

## Integridad de inscripciones y cupos

La capacidad se almacena una sola vez en `secciones.capacidad`. Los cupos
ocupados se calculan contando inscripciones con estado `CONFIRMADA`; los cupos
disponibles son `capacidad - ocupados`. No se persisten contadores derivados.

La futura operación de confirmación deberá ejecutarse en una transacción:

1. bloquear la fila de la sección con `SELECT ... FOR UPDATE`;
2. contar inscripciones confirmadas;
3. comparar el conteo con `capacidad`;
4. insertar o confirmar únicamente si existe disponibilidad;
5. confirmar la transacción.

El índice por sección y estado permite efectuar el conteo bajo bloqueo sin
mantener datos redundantes. La regla concurrente RF-32 no se implementa con un
`CHECK`, porque requiere consultar varias filas y pertenece a la transacción de
servicio.

## Derivaciones controladas

- El período y el grado de una inscripción se derivan de
  `inscripciones → secciones → grados_periodos`.
- Los cupos ocupados y disponibles se derivan de las inscripciones confirmadas.
- El aula de una sección para un docente se obtiene desde la asignación activa.
- El estado vigente de un permiso está en `solicitudes_permiso`; su trazabilidad
  se obtiene de `historial_solicitudes_permiso`.
- La vigencia real de un token combina `estado`, `fecha_expiracion` y
  `fecha_uso`.

## Índices previstos

- Autenticación: únicos sobre usuario y correo.
- Listado docente: asignaciones por docente y sección.
- Inscripción pública: único sobre código de confirmación.
- Cupos: índice `sección + estado` en inscripciones.
- Estudiantes: índice por apellidos y nombres.
- Asistencia: `docente + fecha_hora` y `fecha_hora`.
- Permisos: `docente + estado + fecha_solicitud`.
- Notificaciones: `destinatario + fecha_lectura + fecha_creacion`.
- Recuperación: único sobre hash e índice por usuario, estado y expiración.

## Normalización

El modelo alcanza una tercera forma normal razonable: catálogos separados,
relaciones muchos a muchos resueltas mediante entidades asociativas y ausencia
de contadores de cupo duplicados. Se conservan únicamente datos resumidos cuyo
uso operativo lo justifica, como el estado vigente de una solicitud junto a su
historial inmutable.
