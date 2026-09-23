-- Fase 1: esquema físico para MySQL 8.0.42.
-- Ejecutar sobre una base de datos vacía previamente seleccionada.
-- No contiene credenciales, datos institucionales ni lógica funcional PHP.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE roles (
    id_rol BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255) NULL,
    activo TINYINT UNSIGNED NOT NULL DEFAULT 1,
    fecha_creacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    fecha_actualizacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_roles PRIMARY KEY (id_rol),
    CONSTRAINT uk_roles_nombre UNIQUE (nombre),
    CONSTRAINT chk_roles_activo CHECK (activo IN (0, 1)),
    CONSTRAINT chk_roles_nombre CHECK (CHAR_LENGTH(TRIM(nombre)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE usuarios (
    id_usuario BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_rol BIGINT UNSIGNED NOT NULL,
    nombre_usuario VARCHAR(50) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    clave_hash VARCHAR(255) NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
    ultimo_acceso DATETIME(6) NULL,
    fecha_creacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    fecha_actualizacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_usuarios PRIMARY KEY (id_usuario),
    CONSTRAINT uk_usuarios_nombre_usuario UNIQUE (nombre_usuario),
    CONSTRAINT uk_usuarios_correo UNIQUE (correo),
    CONSTRAINT chk_usuarios_estado CHECK (estado IN ('ACTIVO', 'INACTIVO', 'BLOQUEADO')),
    CONSTRAINT chk_usuarios_clave_hash CHECK (CHAR_LENGTH(clave_hash) >= 60),
    CONSTRAINT chk_usuarios_identidad CHECK (
        CHAR_LENGTH(TRIM(nombre_usuario)) > 0
        AND CHAR_LENGTH(TRIM(nombres)) > 0
        AND CHAR_LENGTH(TRIM(apellidos)) > 0
    ),
    CONSTRAINT fk_usuarios_roles FOREIGN KEY (id_rol)
        REFERENCES roles (id_rol)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE docentes (
    id_docente BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario BIGINT UNSIGNED NOT NULL,
    codigo_docente VARCHAR(30) NULL,
    fecha_ingreso DATE NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
    fecha_creacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    fecha_actualizacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_docentes PRIMARY KEY (id_docente),
    CONSTRAINT uk_docentes_usuario UNIQUE (id_usuario),
    CONSTRAINT uk_docentes_codigo UNIQUE (codigo_docente),
    CONSTRAINT chk_docentes_estado CHECK (estado IN ('ACTIVO', 'INACTIVO')),
    CONSTRAINT fk_docentes_usuarios FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE estudiantes (
    id_estudiante BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo_estudiante VARCHAR(30) NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
    fecha_creacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    fecha_actualizacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_estudiantes PRIMARY KEY (id_estudiante),
    CONSTRAINT uk_estudiantes_codigo UNIQUE (codigo_estudiante),
    CONSTRAINT chk_estudiantes_estado CHECK (estado IN ('ACTIVO', 'INACTIVO')),
    CONSTRAINT chk_estudiantes_identidad CHECK (
        CHAR_LENGTH(TRIM(nombres)) > 0
        AND CHAR_LENGTH(TRIM(apellidos)) > 0
    ),
    INDEX idx_estudiantes_nombre (apellidos, nombres)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE encargados (
    id_encargado BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    telefono VARCHAR(30) NULL,
    correo VARCHAR(150) NULL,
    fecha_creacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    fecha_actualizacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_encargados PRIMARY KEY (id_encargado),
    CONSTRAINT chk_encargados_identidad CHECK (
        CHAR_LENGTH(TRIM(nombres)) > 0
        AND CHAR_LENGTH(TRIM(apellidos)) > 0
    ),
    INDEX idx_encargados_nombre (apellidos, nombres),
    INDEX idx_encargados_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE estudiantes_encargados (
    id_estudiante_encargado BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_estudiante BIGINT UNSIGNED NOT NULL,
    id_encargado BIGINT UNSIGNED NOT NULL,
    parentesco VARCHAR(50) NOT NULL,
    es_principal TINYINT UNSIGNED NOT NULL DEFAULT 0,
    fecha_creacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_estudiantes_encargados PRIMARY KEY (id_estudiante_encargado),
    CONSTRAINT uk_estudiantes_encargados UNIQUE (id_estudiante, id_encargado),
    CONSTRAINT chk_estudiantes_encargados_principal CHECK (es_principal IN (0, 1)),
    CONSTRAINT chk_estudiantes_encargados_parentesco CHECK (
        CHAR_LENGTH(TRIM(parentesco)) > 0
    ),
    CONSTRAINT fk_estudiantes_encargados_estudiantes FOREIGN KEY (id_estudiante)
        REFERENCES estudiantes (id_estudiante)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_estudiantes_encargados_encargados FOREIGN KEY (id_encargado)
        REFERENCES encargados (id_encargado)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE periodos_escolares (
    id_periodo_escolar BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'PLANIFICADO',
    clave_periodo_activo TINYINT GENERATED ALWAYS AS (
        CASE WHEN estado = 'ACTIVO' THEN 1 ELSE NULL END
    ) STORED,
    fecha_creacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    fecha_actualizacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_periodos_escolares PRIMARY KEY (id_periodo_escolar),
    CONSTRAINT uk_periodos_escolares_nombre UNIQUE (nombre),
    CONSTRAINT uk_periodos_escolares_un_activo UNIQUE (clave_periodo_activo),
    CONSTRAINT chk_periodos_escolares_fechas CHECK (fecha_fin >= fecha_inicio),
    CONSTRAINT chk_periodos_escolares_estado CHECK (
        estado IN ('PLANIFICADO', 'ACTIVO', 'CERRADO')
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE grados (
    id_grado BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(30) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    orden SMALLINT UNSIGNED NOT NULL,
    activo TINYINT UNSIGNED NOT NULL DEFAULT 1,
    fecha_creacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    fecha_actualizacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_grados PRIMARY KEY (id_grado),
    CONSTRAINT uk_grados_codigo UNIQUE (codigo),
    CONSTRAINT uk_grados_nombre UNIQUE (nombre),
    CONSTRAINT uk_grados_orden UNIQUE (orden),
    CONSTRAINT chk_grados_orden CHECK (orden > 0),
    CONSTRAINT chk_grados_activo CHECK (activo IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE grados_periodos (
    id_grado_periodo BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_periodo_escolar BIGINT UNSIGNED NOT NULL,
    id_grado BIGINT UNSIGNED NOT NULL,
    habilitado TINYINT UNSIGNED NOT NULL DEFAULT 1,
    fecha_creacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_grados_periodos PRIMARY KEY (id_grado_periodo),
    CONSTRAINT uk_grados_periodos UNIQUE (id_periodo_escolar, id_grado),
    CONSTRAINT chk_grados_periodos_habilitado CHECK (habilitado IN (0, 1)),
    CONSTRAINT fk_grados_periodos_periodos FOREIGN KEY (id_periodo_escolar)
        REFERENCES periodos_escolares (id_periodo_escolar)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_grados_periodos_grados FOREIGN KEY (id_grado)
        REFERENCES grados (id_grado)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE secciones (
    id_seccion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_grado_periodo BIGINT UNSIGNED NOT NULL,
    codigo VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NULL,
    capacidad SMALLINT UNSIGNED NOT NULL,
    habilitada TINYINT UNSIGNED NOT NULL DEFAULT 1,
    fecha_creacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    fecha_actualizacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_secciones PRIMARY KEY (id_seccion),
    CONSTRAINT uk_secciones_contexto UNIQUE (id_grado_periodo, codigo),
    CONSTRAINT chk_secciones_capacidad CHECK (capacidad > 0),
    CONSTRAINT chk_secciones_habilitada CHECK (habilitada IN (0, 1)),
    CONSTRAINT fk_secciones_grados_periodos FOREIGN KEY (id_grado_periodo)
        REFERENCES grados_periodos (id_grado_periodo)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    INDEX idx_secciones_habilitadas (id_grado_periodo, habilitada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE aulas (
    id_aula BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(30) NOT NULL,
    nombre VARCHAR(100) NULL,
    ubicacion_referencia VARCHAR(255) NULL,
    activa TINYINT UNSIGNED NOT NULL DEFAULT 1,
    fecha_creacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    fecha_actualizacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_aulas PRIMARY KEY (id_aula),
    CONSTRAINT uk_aulas_codigo UNIQUE (codigo),
    CONSTRAINT chk_aulas_activa CHECK (activa IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE asignaciones_docentes (
    id_asignacion_docente BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_docente BIGINT UNSIGNED NOT NULL,
    id_seccion BIGINT UNSIGNED NOT NULL,
    id_aula BIGINT UNSIGNED NOT NULL,
    id_usuario_creador BIGINT UNSIGNED NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVA',
    id_seccion_activa BIGINT UNSIGNED GENERATED ALWAYS AS (
        CASE WHEN estado = 'ACTIVA' THEN id_seccion ELSE NULL END
    ) STORED,
    fecha_creacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    fecha_actualizacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_asignaciones_docentes PRIMARY KEY (id_asignacion_docente),
    CONSTRAINT uk_asignaciones_seccion_activa UNIQUE (id_seccion_activa),
    CONSTRAINT uk_asignaciones_inicio UNIQUE (
        id_docente, id_seccion, id_aula, fecha_inicio
    ),
    CONSTRAINT chk_asignaciones_estado CHECK (estado IN ('ACTIVA', 'INACTIVA')),
    CONSTRAINT chk_asignaciones_fechas CHECK (
        fecha_fin IS NULL OR fecha_fin >= fecha_inicio
    ),
    CONSTRAINT fk_asignaciones_docentes FOREIGN KEY (id_docente)
        REFERENCES docentes (id_docente)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_asignaciones_secciones FOREIGN KEY (id_seccion)
        REFERENCES secciones (id_seccion)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_asignaciones_aulas FOREIGN KEY (id_aula)
        REFERENCES aulas (id_aula)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_asignaciones_usuario_creador FOREIGN KEY (id_usuario_creador)
        REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    INDEX idx_asignaciones_docente_estado (id_docente, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE inscripciones (
    id_inscripcion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_estudiante BIGINT UNSIGNED NOT NULL,
    id_encargado BIGINT UNSIGNED NOT NULL,
    id_seccion BIGINT UNSIGNED NOT NULL,
    codigo_confirmacion VARCHAR(64) NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
    fecha_inscripcion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    fecha_confirmacion DATETIME(6) NULL,
    estudiante_vigente BIGINT UNSIGNED GENERATED ALWAYS AS (
        CASE WHEN estado IN ('PENDIENTE', 'CONFIRMADA') THEN id_estudiante ELSE NULL END
    ) STORED,
    seccion_vigente BIGINT UNSIGNED GENERATED ALWAYS AS (
        CASE WHEN estado IN ('PENDIENTE', 'CONFIRMADA') THEN id_seccion ELSE NULL END
    ) STORED,
    fecha_actualizacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_inscripciones PRIMARY KEY (id_inscripcion),
    CONSTRAINT uk_inscripciones_codigo UNIQUE (codigo_confirmacion),
    CONSTRAINT uk_inscripciones_estudiante_seccion_vigente UNIQUE (
        estudiante_vigente, seccion_vigente
    ),
    CONSTRAINT chk_inscripciones_estado CHECK (
        estado IN ('PENDIENTE', 'CONFIRMADA', 'RECHAZADA', 'ANULADA')
    ),
    CONSTRAINT chk_inscripciones_confirmacion CHECK (
        (estado IN ('CONFIRMADA', 'ANULADA')
            AND codigo_confirmacion IS NOT NULL
            AND fecha_confirmacion IS NOT NULL)
        OR
        (estado IN ('PENDIENTE', 'RECHAZADA')
            AND codigo_confirmacion IS NULL
            AND fecha_confirmacion IS NULL)
    ),
    CONSTRAINT fk_inscripciones_estudiantes FOREIGN KEY (id_estudiante)
        REFERENCES estudiantes (id_estudiante)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_inscripciones_encargados FOREIGN KEY (id_encargado)
        REFERENCES encargados (id_encargado)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_inscripciones_vinculo_encargado FOREIGN KEY (
        id_estudiante, id_encargado
    ) REFERENCES estudiantes_encargados (id_estudiante, id_encargado)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_inscripciones_secciones FOREIGN KEY (id_seccion)
        REFERENCES secciones (id_seccion)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    INDEX idx_inscripciones_seccion_estado (id_seccion, estado),
    INDEX idx_inscripciones_estudiante_fecha (id_estudiante, fecha_inscripcion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE configuraciones_ubicacion (
    id_configuracion_ubicacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario_creador BIGINT UNSIGNED NOT NULL,
    descripcion VARCHAR(150) NOT NULL,
    latitud DECIMAL(10, 7) NOT NULL,
    longitud DECIMAL(10, 7) NOT NULL,
    radio_metros DECIMAL(10, 2) NOT NULL,
    fecha_inicio DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    fecha_fin DATETIME(6) NULL,
    activa TINYINT UNSIGNED NOT NULL DEFAULT 1,
    clave_configuracion_activa TINYINT GENERATED ALWAYS AS (
        CASE WHEN activa = 1 THEN 1 ELSE NULL END
    ) STORED,
    fecha_creacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_configuraciones_ubicacion PRIMARY KEY (id_configuracion_ubicacion),
    CONSTRAINT uk_configuraciones_ubicacion_activa UNIQUE (
        clave_configuracion_activa
    ),
    CONSTRAINT chk_configuraciones_latitud CHECK (
        latitud BETWEEN -90.0000000 AND 90.0000000
    ),
    CONSTRAINT chk_configuraciones_longitud CHECK (
        longitud BETWEEN -180.0000000 AND 180.0000000
    ),
    CONSTRAINT chk_configuraciones_radio CHECK (radio_metros > 0),
    CONSTRAINT chk_configuraciones_activa CHECK (activa IN (0, 1)),
    CONSTRAINT chk_configuraciones_fechas CHECK (
        fecha_fin IS NULL OR fecha_fin >= fecha_inicio
    ),
    CONSTRAINT fk_configuraciones_usuario_creador FOREIGN KEY (id_usuario_creador)
        REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE marcajes_asistencia (
    id_marcaje_asistencia BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_docente BIGINT UNSIGNED NOT NULL,
    id_usuario BIGINT UNSIGNED NOT NULL,
    id_configuracion_ubicacion BIGINT UNSIGNED NOT NULL,
    fecha_hora DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    tipo VARCHAR(10) NOT NULL,
    latitud_dispositivo DECIMAL(10, 7) NOT NULL,
    longitud_dispositivo DECIMAL(10, 7) NOT NULL,
    precision_metros DECIMAL(10, 2) NULL,
    distancia_metros DECIMAL(10, 2) NOT NULL,
    dentro_radio TINYINT UNSIGNED NOT NULL,
    CONSTRAINT pk_marcajes_asistencia PRIMARY KEY (id_marcaje_asistencia),
    CONSTRAINT chk_marcajes_tipo CHECK (tipo IN ('ENTRADA', 'SALIDA')),
    CONSTRAINT chk_marcajes_latitud CHECK (
        latitud_dispositivo BETWEEN -90.0000000 AND 90.0000000
    ),
    CONSTRAINT chk_marcajes_longitud CHECK (
        longitud_dispositivo BETWEEN -180.0000000 AND 180.0000000
    ),
    CONSTRAINT chk_marcajes_precision CHECK (
        precision_metros IS NULL OR precision_metros > 0
    ),
    CONSTRAINT chk_marcajes_distancia CHECK (distancia_metros >= 0),
    CONSTRAINT chk_marcajes_dentro_radio CHECK (dentro_radio = 1),
    CONSTRAINT fk_marcajes_docentes FOREIGN KEY (id_docente)
        REFERENCES docentes (id_docente)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_marcajes_usuarios FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_marcajes_configuracion FOREIGN KEY (id_configuracion_ubicacion)
        REFERENCES configuraciones_ubicacion (id_configuracion_ubicacion)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    INDEX idx_marcajes_docente_fecha (id_docente, fecha_hora),
    INDEX idx_marcajes_fecha (fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE solicitudes_permiso (
    id_solicitud_permiso BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_docente BIGINT UNSIGNED NOT NULL,
    fecha_solicitada DATE NOT NULL,
    tipo_motivo VARCHAR(100) NOT NULL,
    descripcion VARCHAR(1000) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
    fecha_solicitud DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    fecha_actualizacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
        ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_solicitudes_permiso PRIMARY KEY (id_solicitud_permiso),
    CONSTRAINT chk_solicitudes_estado CHECK (
        estado IN ('PENDIENTE', 'APROBADO', 'RECHAZADO')
    ),
    CONSTRAINT chk_solicitudes_contenido CHECK (
        CHAR_LENGTH(TRIM(tipo_motivo)) > 0
        AND CHAR_LENGTH(TRIM(descripcion)) > 0
    ),
    CONSTRAINT fk_solicitudes_docentes FOREIGN KEY (id_docente)
        REFERENCES docentes (id_docente)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    INDEX idx_solicitudes_docente_estado_fecha (
        id_docente, estado, fecha_solicitud
    ),
    INDEX idx_solicitudes_estado_fecha (estado, fecha_solicitud)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE historial_solicitudes_permiso (
    id_historial_permiso BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_solicitud_permiso BIGINT UNSIGNED NOT NULL,
    id_usuario_actor BIGINT UNSIGNED NOT NULL,
    estado_anterior VARCHAR(20) NULL,
    estado_nuevo VARCHAR(20) NOT NULL,
    comentario VARCHAR(500) NULL,
    fecha_cambio DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_historial_solicitudes_permiso PRIMARY KEY (id_historial_permiso),
    CONSTRAINT chk_historial_estado_anterior CHECK (
        estado_anterior IS NULL
        OR estado_anterior IN ('PENDIENTE', 'APROBADO', 'RECHAZADO')
    ),
    CONSTRAINT chk_historial_estado_nuevo CHECK (
        estado_nuevo IN ('PENDIENTE', 'APROBADO', 'RECHAZADO')
    ),
    CONSTRAINT chk_historial_transicion CHECK (
        estado_anterior IS NULL OR estado_anterior <> estado_nuevo
    ),
    CONSTRAINT fk_historial_solicitudes FOREIGN KEY (id_solicitud_permiso)
        REFERENCES solicitudes_permiso (id_solicitud_permiso)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_historial_usuarios FOREIGN KEY (id_usuario_actor)
        REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    INDEX idx_historial_solicitud_fecha (
        id_solicitud_permiso, fecha_cambio
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE documentos_permiso (
    id_documento_permiso BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_solicitud_permiso BIGINT UNSIGNED NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    ruta_almacenamiento VARCHAR(500) NOT NULL,
    tipo_mime VARCHAR(100) NOT NULL,
    tamano_bytes BIGINT UNSIGNED NOT NULL,
    fecha_carga DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_documentos_permiso PRIMARY KEY (id_documento_permiso),
    CONSTRAINT uk_documentos_permiso_ruta UNIQUE (ruta_almacenamiento),
    CONSTRAINT chk_documentos_tamano CHECK (tamano_bytes > 0),
    CONSTRAINT chk_documentos_nombre CHECK (
        CHAR_LENGTH(TRIM(nombre_original)) > 0
        AND CHAR_LENGTH(TRIM(ruta_almacenamiento)) > 0
        AND CHAR_LENGTH(TRIM(tipo_mime)) > 0
    ),
    CONSTRAINT fk_documentos_solicitudes FOREIGN KEY (id_solicitud_permiso)
        REFERENCES solicitudes_permiso (id_solicitud_permiso)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    INDEX idx_documentos_solicitud (id_solicitud_permiso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE notificaciones (
    id_notificacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_solicitud_permiso BIGINT UNSIGNED NOT NULL,
    id_usuario_destinatario BIGINT UNSIGNED NOT NULL,
    tipo VARCHAR(30) NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    mensaje VARCHAR(500) NOT NULL,
    fecha_creacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    fecha_lectura DATETIME(6) NULL,
    CONSTRAINT pk_notificaciones PRIMARY KEY (id_notificacion),
    CONSTRAINT chk_notificaciones_tipo CHECK (
        tipo IN ('NUEVA_SOLICITUD', 'CAMBIO_ESTADO')
    ),
    CONSTRAINT chk_notificaciones_fechas CHECK (
        fecha_lectura IS NULL OR fecha_lectura >= fecha_creacion
    ),
    CONSTRAINT fk_notificaciones_solicitudes FOREIGN KEY (id_solicitud_permiso)
        REFERENCES solicitudes_permiso (id_solicitud_permiso)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_notificaciones_usuarios FOREIGN KEY (id_usuario_destinatario)
        REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    INDEX idx_notificaciones_destinatario (
        id_usuario_destinatario, fecha_lectura, fecha_creacion
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE tokens_recuperacion (
    id_token_recuperacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_usuario BIGINT UNSIGNED NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVO',
    fecha_creacion DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    fecha_expiracion DATETIME(6) NOT NULL,
    fecha_uso DATETIME(6) NULL,
    CONSTRAINT pk_tokens_recuperacion PRIMARY KEY (id_token_recuperacion),
    CONSTRAINT uk_tokens_recuperacion_hash UNIQUE (token_hash),
    CONSTRAINT chk_tokens_estado CHECK (
        estado IN ('ACTIVO', 'USADO', 'REVOCADO')
    ),
    CONSTRAINT chk_tokens_expiracion CHECK (
        fecha_expiracion > fecha_creacion
    ),
    CONSTRAINT chk_tokens_uso CHECK (
        (estado = 'USADO' AND fecha_uso IS NOT NULL)
        OR (estado IN ('ACTIVO', 'REVOCADO') AND fecha_uso IS NULL)
    ),
    CONSTRAINT fk_tokens_usuarios FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    INDEX idx_tokens_usuario_estado_expiracion (
        id_usuario, estado, fecha_expiracion
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
