# Diccionario de datos — Fase 1

## Convenciones

- Motor: MySQL 8.0.42, InnoDB, `utf8mb4` y `utf8mb4_0900_ai_ci`.
- Todas las PK son numéricas, sin signo y autoincrementales.
- `Nulo` refleja la definición física; las columnas generadas pueden resultar nulas por diseño.
- `Origen` distingue datos directos, referencias técnicas y valores generados.

## `roles`

Catálogo de roles de acceso.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_rol` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de rol. | Dato directo |
| `nombre` | `VARCHAR(50)` | Sí | UNIQUE | Nombre funcional del registro. | Dato directo |
| `descripcion` | `VARCHAR(255)` | Sí | — | Descripción funcional del registro. | Dato directo |
| `activo` | `TINYINT UNSIGNED` | Sí | — | Indicador de activación lógica. | Dato directo |
| `fecha_creacion` | `DATETIME(6)` | Sí | — | Instante de creación. | Dato directo |
| `fecha_actualizacion` | `DATETIME(6)` | Sí | — | Instante de última actualización automática. | Dato directo |

## `usuarios`

Identidades autenticables de administración y docentes.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_usuario` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de usuario. | Dato directo |
| `id_rol` | `BIGINT UNSIGNED` | Sí | FK | Referencia a rol. | Referencia técnica |
| `nombre_usuario` | `VARCHAR(50)` | Sí | UNIQUE | Nombre único usado para iniciar sesión. | Dato directo |
| `correo` | `VARCHAR(150)` | Sí | UNIQUE | Correo de contacto o autenticación, según la entidad. | Dato directo |
| `clave_hash` | `VARCHAR(255)` | Sí | — | Hash de contraseña; nunca almacena texto plano. | Dato directo |
| `nombres` | `VARCHAR(100)` | Sí | — | Nombres de la persona. | Dato directo |
| `apellidos` | `VARCHAR(100)` | Sí | — | Apellidos de la persona. | Dato directo |
| `estado` | `VARCHAR(20)` | Sí | — | Estado controlado del registro. | Dato directo |
| `ultimo_acceso` | `DATETIME(6)` | Sí | — | Instante del último acceso exitoso. | Dato directo |
| `fecha_creacion` | `DATETIME(6)` | Sí | — | Instante de creación. | Dato directo |
| `fecha_actualizacion` | `DATETIME(6)` | Sí | — | Instante de última actualización automática. | Dato directo |

## `docentes`

Perfil institucional del usuario docente.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_docente` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de docente. | Dato directo |
| `id_usuario` | `BIGINT UNSIGNED` | Sí | FK, UNIQUE | Referencia a usuario. | Referencia técnica |
| `codigo_docente` | `VARCHAR(30)` | Sí | UNIQUE | Código institucional opcional del docente. | Dato directo |
| `fecha_ingreso` | `DATE` | Sí | — | Fecha opcional de ingreso del docente. | Dato directo |
| `estado` | `VARCHAR(20)` | Sí | — | Estado controlado del registro. | Dato directo |
| `fecha_creacion` | `DATETIME(6)` | Sí | — | Instante de creación. | Dato directo |
| `fecha_actualizacion` | `DATETIME(6)` | Sí | — | Instante de última actualización automática. | Dato directo |

## `estudiantes`

Datos básicos de estudiantes.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_estudiante` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de estudiante. | Dato directo |
| `codigo_estudiante` | `VARCHAR(30)` | Sí | UNIQUE | Código institucional opcional del estudiante. | Dato directo |
| `nombres` | `VARCHAR(100)` | Sí | — | Nombres de la persona. | Dato directo |
| `apellidos` | `VARCHAR(100)` | Sí | — | Apellidos de la persona. | Dato directo |
| `fecha_nacimiento` | `DATE` | Sí | — | Fecha de nacimiento opcional. | Dato directo |
| `estado` | `VARCHAR(20)` | Sí | — | Estado controlado del registro. | Dato directo |
| `fecha_creacion` | `DATETIME(6)` | Sí | — | Instante de creación. | Dato directo |
| `fecha_actualizacion` | `DATETIME(6)` | Sí | — | Instante de última actualización automática. | Dato directo |

## `encargados`

Datos básicos de padres, madres o encargados.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_encargado` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de encargado. | Dato directo |
| `nombres` | `VARCHAR(100)` | Sí | — | Nombres de la persona. | Dato directo |
| `apellidos` | `VARCHAR(100)` | Sí | — | Apellidos de la persona. | Dato directo |
| `telefono` | `VARCHAR(30)` | Sí | — | Teléfono opcional del encargado. | Dato directo |
| `correo` | `VARCHAR(150)` | Sí | — | Correo de contacto o autenticación, según la entidad. | Dato directo |
| `fecha_creacion` | `DATETIME(6)` | Sí | — | Instante de creación. | Dato directo |
| `fecha_actualizacion` | `DATETIME(6)` | Sí | — | Instante de última actualización automática. | Dato directo |

## `estudiantes_encargados`

Vínculo muchos a muchos entre estudiantes y encargados.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_estudiante_encargado` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de vínculo estudiante–encargado. | Dato directo |
| `id_estudiante` | `BIGINT UNSIGNED` | Sí | FK, UNIQUE compuesta | Referencia a estudiante. | Referencia técnica |
| `id_encargado` | `BIGINT UNSIGNED` | Sí | FK, UNIQUE compuesta | Referencia a encargado. | Referencia técnica |
| `parentesco` | `VARCHAR(50)` | Sí | — | Relación declarada entre estudiante y encargado. | Dato directo |
| `es_principal` | `TINYINT UNSIGNED` | Sí | — | Indica si es el encargado principal. | Dato directo |
| `fecha_creacion` | `DATETIME(6)` | Sí | — | Instante de creación. | Dato directo |

## `periodos_escolares`

Ciclos escolares configurables.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_periodo_escolar` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de período escolar. | Dato directo |
| `nombre` | `VARCHAR(100)` | Sí | UNIQUE | Nombre funcional del registro. | Dato directo |
| `fecha_inicio` | `DATE` | Sí | — | Inicio de vigencia. | Dato directo |
| `fecha_fin` | `DATE` | Sí | — | Fin de vigencia; nulo cuando continúa vigente. | Dato directo |
| `estado` | `VARCHAR(20)` | Sí | — | Estado controlado del registro. | Dato directo |
| `clave_periodo_activo` | `TINYINT` | Sí | UNIQUE | Columna generada que garantiza un único período activo. | Dato directo |
| `fecha_creacion` | `DATETIME(6)` | Sí | — | Instante de creación. | Dato directo |
| `fecha_actualizacion` | `DATETIME(6)` | Sí | — | Instante de última actualización automática. | Dato directo |

## `grados`

Catálogo reutilizable de grados.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_grado` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de grado. | Dato directo |
| `codigo` | `VARCHAR(30)` | Sí | UNIQUE | Código funcional del registro. | Dato directo |
| `nombre` | `VARCHAR(100)` | Sí | UNIQUE | Nombre funcional del registro. | Dato directo |
| `orden` | `SMALLINT UNSIGNED` | Sí | UNIQUE | Orden configurable de presentación del grado. | Dato directo |
| `activo` | `TINYINT UNSIGNED` | Sí | — | Indicador de activación lógica. | Dato directo |
| `fecha_creacion` | `DATETIME(6)` | Sí | — | Instante de creación. | Dato directo |
| `fecha_actualizacion` | `DATETIME(6)` | Sí | — | Instante de última actualización automática. | Dato directo |

## `grados_periodos`

Grados habilitados en cada período.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_grado_periodo` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de grado habilitado en período. | Dato directo |
| `id_periodo_escolar` | `BIGINT UNSIGNED` | Sí | FK, UNIQUE compuesta | Referencia a periodo escolar. | Referencia técnica |
| `id_grado` | `BIGINT UNSIGNED` | Sí | FK, UNIQUE compuesta | Referencia a grado. | Referencia técnica |
| `habilitado` | `TINYINT UNSIGNED` | Sí | — | Indica si el grado está habilitado en el período. | Dato directo |
| `fecha_creacion` | `DATETIME(6)` | Sí | — | Instante de creación. | Dato directo |

## `secciones`

Secciones, estado y capacidad por grado y período.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_seccion` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de sección. | Dato directo |
| `id_grado_periodo` | `BIGINT UNSIGNED` | Sí | FK, UNIQUE compuesta | Referencia a grado periodo. | Referencia técnica |
| `codigo` | `VARCHAR(20)` | Sí | UNIQUE compuesta | Código funcional del registro. | Dato directo |
| `nombre` | `VARCHAR(100)` | Sí | — | Nombre funcional del registro. | Dato directo |
| `capacidad` | `SMALLINT UNSIGNED` | Sí | — | Máximo configurado de estudiantes de la sección. | Dato directo |
| `habilitada` | `TINYINT UNSIGNED` | Sí | — | Indica si la sección está habilitada. | Dato directo |
| `fecha_creacion` | `DATETIME(6)` | Sí | — | Instante de creación. | Dato directo |
| `fecha_actualizacion` | `DATETIME(6)` | Sí | — | Instante de última actualización automática. | Dato directo |

## `aulas`

Catálogo configurable de aulas.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_aula` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de aula. | Dato directo |
| `codigo` | `VARCHAR(30)` | Sí | UNIQUE | Código funcional del registro. | Dato directo |
| `nombre` | `VARCHAR(100)` | Sí | — | Nombre funcional del registro. | Dato directo |
| `ubicacion_referencia` | `VARCHAR(255)` | Sí | — | Descripción orientativa de la ubicación del aula. | Dato directo |
| `activa` | `TINYINT UNSIGNED` | Sí | — | Indica vigencia activa. | Dato directo |
| `fecha_creacion` | `DATETIME(6)` | Sí | — | Instante de creación. | Dato directo |
| `fecha_actualizacion` | `DATETIME(6)` | Sí | — | Instante de última actualización automática. | Dato directo |

## `asignaciones_docentes`

Asignaciones históricas de docentes a sección y aula.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_asignacion_docente` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de asignación docente. | Dato directo |
| `id_docente` | `BIGINT UNSIGNED` | Sí | FK, UNIQUE compuesta | Referencia a docente. | Referencia técnica |
| `id_seccion` | `BIGINT UNSIGNED` | Sí | FK, UNIQUE compuesta | Referencia a seccion. | Referencia técnica |
| `id_aula` | `BIGINT UNSIGNED` | Sí | FK, UNIQUE compuesta | Referencia a aula. | Referencia técnica |
| `id_usuario_creador` | `BIGINT UNSIGNED` | Sí | FK | Usuario que creó el registro. | Referencia técnica |
| `fecha_inicio` | `DATE` | Sí | UNIQUE compuesta | Inicio de vigencia. | Dato directo |
| `fecha_fin` | `DATE` | Sí | — | Fin de vigencia; nulo cuando continúa vigente. | Dato directo |
| `estado` | `VARCHAR(20)` | Sí | — | Estado controlado del registro. | Dato directo |
| `id_seccion_activa` | `BIGINT UNSIGNED` | Sí | UNIQUE | Columna generada que garantiza una sola asignación activa por sección. | Dato directo |
| `fecha_creacion` | `DATETIME(6)` | Sí | — | Instante de creación. | Dato directo |
| `fecha_actualizacion` | `DATETIME(6)` | Sí | — | Instante de última actualización automática. | Dato directo |

## `inscripciones`

Inscripciones y confirmaciones de estudiantes.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_inscripcion` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de inscripción. | Dato directo |
| `id_estudiante` | `BIGINT UNSIGNED` | Sí | FK | Referencia a estudiante. | Referencia técnica |
| `id_encargado` | `BIGINT UNSIGNED` | Sí | FK | Referencia a encargado. | Referencia técnica |
| `id_seccion` | `BIGINT UNSIGNED` | Sí | FK | Referencia a seccion. | Referencia técnica |
| `codigo_confirmacion` | `VARCHAR(64)` | Sí | UNIQUE | Código público único de una inscripción confirmada. | Dato directo |
| `estado` | `VARCHAR(20)` | Sí | — | Estado controlado del registro. | Dato directo |
| `fecha_inscripcion` | `DATETIME(6)` | Sí | — | Instante de registro de la inscripción. | Dato directo |
| `fecha_confirmacion` | `DATETIME(6)` | Sí | — | Instante en que se confirmó la inscripción. | Dato directo |
| `estudiante_vigente` | `BIGINT UNSIGNED` | Sí | UNIQUE compuesta | Columna generada para evitar duplicar una inscripción vigente. | Dato directo |
| `seccion_vigente` | `BIGINT UNSIGNED` | Sí | UNIQUE compuesta | Columna generada que acompaña la unicidad de inscripción vigente. | Dato directo |
| `fecha_actualizacion` | `DATETIME(6)` | Sí | — | Instante de última actualización automática. | Dato directo |

## `configuraciones_ubicacion`

Versiones de ubicación y radio autorizados.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_configuracion_ubicacion` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de configuración de ubicación. | Dato directo |
| `id_usuario_creador` | `BIGINT UNSIGNED` | Sí | FK | Usuario que creó el registro. | Referencia técnica |
| `descripcion` | `VARCHAR(150)` | Sí | — | Descripción funcional del registro. | Dato directo |
| `latitud` | `DECIMAL(10, 7)` | Sí | — | Latitud configurada de la institución. | Dato directo |
| `longitud` | `DECIMAL(10, 7)` | Sí | — | Longitud configurada de la institución. | Dato directo |
| `radio_metros` | `DECIMAL(10, 2)` | Sí | — | Radio permitido, expresado en metros. | Dato directo |
| `fecha_inicio` | `DATETIME(6)` | Sí | — | Inicio de vigencia. | Dato directo |
| `fecha_fin` | `DATETIME(6)` | Sí | — | Fin de vigencia; nulo cuando continúa vigente. | Dato directo |
| `activa` | `TINYINT UNSIGNED` | Sí | — | Indica vigencia activa. | Dato directo |
| `clave_configuracion_activa` | `TINYINT` | Sí | UNIQUE | Columna generada que garantiza una única configuración activa. | Dato directo |
| `fecha_creacion` | `DATETIME(6)` | Sí | — | Instante de creación. | Dato directo |

## `marcajes_asistencia`

Eventos de entrada y salida con evidencia geográfica.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_marcaje_asistencia` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de marcaje de asistencia. | Dato directo |
| `id_docente` | `BIGINT UNSIGNED` | Sí | FK | Referencia a docente. | Referencia técnica |
| `id_usuario` | `BIGINT UNSIGNED` | Sí | FK | Referencia a usuario. | Referencia técnica |
| `id_configuracion_ubicacion` | `BIGINT UNSIGNED` | Sí | FK | Referencia a configuracion ubicacion. | Referencia técnica |
| `fecha_hora` | `DATETIME(6)` | Sí | — | Instante exacto del marcaje. | Dato directo |
| `tipo` | `VARCHAR(10)` | Sí | — | Tipo controlado del evento o notificación. | Dato directo |
| `latitud_dispositivo` | `DECIMAL(10, 7)` | Sí | — | Latitud reportada por el dispositivo. | Dato directo |
| `longitud_dispositivo` | `DECIMAL(10, 7)` | Sí | — | Longitud reportada por el dispositivo. | Dato directo |
| `precision_metros` | `DECIMAL(10, 2)` | Sí | — | Precisión reportada por el dispositivo, en metros. | Dato directo |
| `distancia_metros` | `DECIMAL(10, 2)` | Sí | — | Distancia calculada respecto de la configuración aplicada. | Dato directo |
| `dentro_radio` | `TINYINT UNSIGNED` | Sí | — | Evidencia de que el marcaje fue aceptado dentro del radio. | Dato directo |

## `solicitudes_permiso`

Solicitudes de permiso y su estado vigente.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_solicitud_permiso` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de solicitud de permiso. | Dato directo |
| `id_docente` | `BIGINT UNSIGNED` | Sí | FK | Referencia a docente. | Referencia técnica |
| `fecha_solicitada` | `DATE` | Sí | — | Fecha para la que se solicita permiso. | Dato directo |
| `tipo_motivo` | `VARCHAR(100)` | Sí | — | Clasificación textual del motivo. | Dato directo |
| `descripcion` | `VARCHAR(1000)` | Sí | — | Descripción funcional del registro. | Dato directo |
| `estado` | `VARCHAR(20)` | Sí | — | Estado controlado del registro. | Dato directo |
| `fecha_solicitud` | `DATETIME(6)` | Sí | — | Instante de creación de la solicitud. | Dato directo |
| `fecha_actualizacion` | `DATETIME(6)` | Sí | — | Instante de última actualización automática. | Dato directo |

## `historial_solicitudes_permiso`

Eventos inmutables de cambio de estado.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_historial_permiso` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de evento de historial. | Dato directo |
| `id_solicitud_permiso` | `BIGINT UNSIGNED` | Sí | FK | Referencia a solicitud permiso. | Referencia técnica |
| `id_usuario_actor` | `BIGINT UNSIGNED` | Sí | FK | Usuario responsable del cambio. | Referencia técnica |
| `estado_anterior` | `VARCHAR(20)` | Sí | — | Estado previo del permiso; nulo en el evento inicial. | Dato directo |
| `estado_nuevo` | `VARCHAR(20)` | Sí | — | Estado resultante del evento. | Dato directo |
| `comentario` | `VARCHAR(500)` | Sí | — | Observación asociada al cambio de estado. | Dato directo |
| `fecha_cambio` | `DATETIME(6)` | Sí | — | Instante del cambio de estado. | Dato directo |

## `documentos_permiso`

Metadatos de respaldos de permisos.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_documento_permiso` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de documento de permiso. | Dato directo |
| `id_solicitud_permiso` | `BIGINT UNSIGNED` | Sí | FK | Referencia a solicitud permiso. | Referencia técnica |
| `nombre_original` | `VARCHAR(255)` | Sí | — | Nombre original del archivo adjunto. | Dato directo |
| `ruta_almacenamiento` | `VARCHAR(500)` | Sí | UNIQUE | Ruta lógica única del archivo fuera de MySQL. | Dato directo |
| `tipo_mime` | `VARCHAR(100)` | Sí | — | Tipo MIME declarado del archivo. | Dato directo |
| `tamano_bytes` | `BIGINT UNSIGNED` | Sí | — | Tamaño del archivo en bytes. | Dato directo |
| `fecha_carga` | `DATETIME(6)` | Sí | — | Instante de carga del documento. | Dato directo |

## `notificaciones`

Avisos internos asociados a permisos.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_notificacion` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de notificación. | Dato directo |
| `id_solicitud_permiso` | `BIGINT UNSIGNED` | Sí | FK | Referencia a solicitud permiso. | Referencia técnica |
| `id_usuario_destinatario` | `BIGINT UNSIGNED` | Sí | FK | Usuario que recibe la notificación. | Referencia técnica |
| `tipo` | `VARCHAR(30)` | Sí | — | Tipo controlado del evento o notificación. | Dato directo |
| `titulo` | `VARCHAR(150)` | Sí | — | Título de la notificación. | Dato directo |
| `mensaje` | `VARCHAR(500)` | Sí | — | Contenido de la notificación. | Dato directo |
| `fecha_creacion` | `DATETIME(6)` | Sí | — | Instante de creación. | Dato directo |
| `fecha_lectura` | `DATETIME(6)` | Sí | — | Instante de lectura; nulo mientras esté pendiente. | Dato directo |

## `tokens_recuperacion`

Tokens hasheados de recuperación de acceso.

| Campo | Tipo | Nulo | Clave/Regla | Descripción | Origen |
|---|---|---|---|---|---|
| `id_token_recuperacion` | `BIGINT UNSIGNED` | Sí | PK | Identificador interno de token de recuperación. | Dato directo |
| `id_usuario` | `BIGINT UNSIGNED` | Sí | FK | Referencia a usuario. | Referencia técnica |
| `token_hash` | `VARCHAR(255)` | Sí | UNIQUE | Hash único del token; nunca conserva el secreto original. | Dato directo |
| `estado` | `VARCHAR(20)` | Sí | — | Estado controlado del registro. | Dato directo |
| `fecha_creacion` | `DATETIME(6)` | Sí | — | Instante de creación. | Dato directo |
| `fecha_expiracion` | `DATETIME(6)` | Sí | — | Instante de vencimiento del token. | Dato directo |
| `fecha_uso` | `DATETIME(6)` | Sí | — | Instante de consumo del token. | Dato directo |

## Datos derivados que no se almacenan

- Cupos ocupados: conteo de inscripciones `CONFIRMADA` por sección.
- Cupos disponibles: `secciones.capacidad - ocupados`.
- Grado y período de una inscripción: derivados desde su sección.
- Vigencia real de un token: combinación de estado, expiración y fecha de uso.
- Comprobantes y reportes: se generan desde los datos persistidos; no requieren tablas propias.

