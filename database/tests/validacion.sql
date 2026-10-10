-- Fase 1: validación reproducible sobre una base desechable.
-- Prerrequisito: ejecutar schema.sql y seed.sql en una base vacía.
-- El procedimiento y los datos generados son exclusivamente de prueba.

DELIMITER $$

CREATE PROCEDURE validar_fase_1()
BEGIN
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_i INT DEFAULT 0;
    DECLARE v_seccion BIGINT UNSIGNED;
    DECLARE v_encargado BIGINT UNSIGNED;
    DECLARE v_estudiante BIGINT UNSIGNED;

    CREATE TEMPORARY TABLE resultados_fase_1 (
        prueba VARCHAR(100) NOT NULL,
        esperado VARCHAR(100) NOT NULL,
        observado VARCHAR(255) NOT NULL,
        resultado VARCHAR(10) NOT NULL
    );

    SELECT COUNT(*) INTO v_total
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_type = 'BASE TABLE';
    INSERT INTO resultados_fase_1 VALUES (
        'Cantidad de tablas', '20', CAST(v_total AS CHAR),
        IF(v_total = 20, 'OK', 'FALLO')
    );

    SELECT COUNT(DISTINCT table_name) INTO v_total
    FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE()
      AND constraint_type = 'PRIMARY KEY';
    INSERT INTO resultados_fase_1 VALUES (
        'Cobertura de claves primarias', '20 tablas', CONCAT(v_total, ' tablas'),
        IF(v_total = 20, 'OK', 'FALLO')
    );

    SELECT COUNT(*) INTO v_total
    FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE()
      AND constraint_type = 'FOREIGN KEY';
    INSERT INTO resultados_fase_1 VALUES (
        'Cantidad de claves foráneas', '26', CAST(v_total AS CHAR),
        IF(v_total = 26, 'OK', 'FALLO')
    );

    BEGIN
        DECLARE v_error TINYINT DEFAULT 0;
        DECLARE v_errno INT DEFAULT 0;
        DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
        BEGIN
            GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO;
            SET v_error = 1;
        END;
        INSERT INTO roles (id_rol, nombre) VALUES (1, 'PK-DUPLICADA');
        INSERT INTO resultados_fase_1 VALUES (
            'PK rechaza duplicado', 'Error 1062', CONCAT('Error ', v_errno),
            IF(v_error = 1 AND v_errno = 1062, 'OK', 'FALLO')
        );
    END;

    BEGIN
        DECLARE v_error TINYINT DEFAULT 0;
        DECLARE v_errno INT DEFAULT 0;
        DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
        BEGIN
            GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO;
            SET v_error = 1;
        END;
        INSERT INTO docentes (id_usuario, codigo_docente)
        VALUES (999999999, 'FK-INVALIDA');
        INSERT INTO resultados_fase_1 VALUES (
            'FK rechaza referencia inexistente', 'Error 1452', CONCAT('Error ', v_errno),
            IF(v_error = 1 AND v_errno = 1452, 'OK', 'FALLO')
        );
    END;

    BEGIN
        DECLARE v_error TINYINT DEFAULT 0;
        DECLARE v_errno INT DEFAULT 0;
        DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
        BEGIN
            GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO;
            SET v_error = 1;
        END;
        INSERT INTO roles (nombre) VALUES ('ADMINISTRACION');
        INSERT INTO resultados_fase_1 VALUES (
            'UNIQUE rechaza valor repetido', 'Error 1062', CONCAT('Error ', v_errno),
            IF(v_error = 1 AND v_errno = 1062, 'OK', 'FALLO')
        );
    END;

    BEGIN
        DECLARE v_error TINYINT DEFAULT 0;
        DECLARE v_errno INT DEFAULT 0;
        DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
        BEGIN
            GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO;
            SET v_error = 1;
        END;
        INSERT INTO roles (nombre) VALUES (NULL);
        INSERT INTO resultados_fase_1 VALUES (
            'NOT NULL rechaza ausencia', 'Error 1048', CONCAT('Error ', v_errno),
            IF(v_error = 1 AND v_errno = 1048, 'OK', 'FALLO')
        );
    END;

    BEGIN
        DECLARE v_error TINYINT DEFAULT 0;
        DECLARE v_errno INT DEFAULT 0;
        DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
        BEGIN
            GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO;
            SET v_error = 1;
        END;
        INSERT INTO secciones (id_grado_periodo, codigo, capacidad)
        VALUES ((SELECT MIN(id_grado_periodo) FROM grados_periodos), 'CHECK-0', 0);
        INSERT INTO resultados_fase_1 VALUES (
            'CHECK rechaza capacidad cero', 'Error 3819', CONCAT('Error ', v_errno),
            IF(v_error = 1 AND v_errno = 3819, 'OK', 'FALLO')
        );
    END;

    BEGIN
        DECLARE v_error TINYINT DEFAULT 0;
        DECLARE v_errno INT DEFAULT 0;
        DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
        BEGIN
            GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO;
            SET v_error = 1;
        END;
        INSERT INTO periodos_escolares (
            nombre, fecha_inicio, fecha_fin, estado
        ) VALUES ('Segundo período activo', '2027-01-01', '2027-12-31', 'ACTIVO');
        INSERT INTO resultados_fase_1 VALUES (
            'Solo un período activo', 'Error 1062', CONCAT('Error ', v_errno),
            IF(v_error = 1 AND v_errno = 1062, 'OK', 'FALLO')
        );
    END;

    BEGIN
        DECLARE v_error TINYINT DEFAULT 0;
        DECLARE v_errno INT DEFAULT 0;
        DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
        BEGIN
            GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO;
            SET v_error = 1;
        END;
        INSERT INTO asignaciones_docentes (
            id_docente, id_seccion, id_aula, id_usuario_creador,
            fecha_inicio, estado
        ) SELECT
            id_docente,
            id_seccion,
            id_aula,
            id_usuario_creador,
            '2026-02-01',
            'ACTIVA'
        FROM asignaciones_docentes
        LIMIT 1;
        INSERT INTO resultados_fase_1 VALUES (
            'Solo una asignación activa por sección', 'Error 1062',
            CONCAT('Error ', v_errno),
            IF(v_error = 1 AND v_errno = 1062, 'OK', 'FALLO')
        );
    END;

    SELECT COUNT(*) INTO v_total
    FROM inscripciones i
    JOIN estudiantes e ON e.id_estudiante = i.id_estudiante
    JOIN encargados en ON en.id_encargado = i.id_encargado
    JOIN secciones s ON s.id_seccion = i.id_seccion
    JOIN grados_periodos gp ON gp.id_grado_periodo = s.id_grado_periodo
    JOIN grados g ON g.id_grado = gp.id_grado
    JOIN periodos_escolares p
        ON p.id_periodo_escolar = gp.id_periodo_escolar
    WHERE i.estado = 'CONFIRMADA';
    INSERT INTO resultados_fase_1 VALUES (
        'Consulta integral de inscripción', '1 fila', CONCAT(v_total, ' fila(s)'),
        IF(v_total = 1, 'OK', 'FALLO')
    );

    SELECT COUNT(*) INTO v_total
    FROM asignaciones_docentes ad
    JOIN docentes d ON d.id_docente = ad.id_docente
    JOIN usuarios u ON u.id_usuario = d.id_usuario
    JOIN secciones s ON s.id_seccion = ad.id_seccion
    JOIN aulas a ON a.id_aula = ad.id_aula
    WHERE ad.estado = 'ACTIVA';
    INSERT INTO resultados_fase_1 VALUES (
        'Consulta de asignación docente', '1 fila', CONCAT(v_total, ' fila(s)'),
        IF(v_total = 1, 'OK', 'FALLO')
    );

    SELECT s.id_seccion, ee.id_encargado
    INTO v_seccion, v_encargado
    FROM secciones s
    CROSS JOIN estudiantes_encargados ee
    WHERE s.codigo = 'A'
    LIMIT 1;

    SELECT s.capacidad - COUNT(i.id_inscripcion) INTO v_total
    FROM secciones s
    LEFT JOIN inscripciones i
        ON i.id_seccion = s.id_seccion
       AND i.estado = 'CONFIRMADA'
    WHERE s.id_seccion = v_seccion
    GROUP BY s.id_seccion, s.capacidad;
    INSERT INTO resultados_fase_1 VALUES (
        'Cupos iniciales calculados', '29 disponibles',
        CONCAT(v_total, ' disponibles'),
        IF(v_total = 29, 'OK', 'FALLO')
    );

    SET v_i = 2;
    WHILE v_i <= 30 DO
        INSERT INTO estudiantes (
            codigo_estudiante, nombres, apellidos, estado
        ) VALUES (
            CONCAT('EST-CAP-', LPAD(v_i, 3, '0')),
            CONCAT('Estudiante ', v_i),
            'Capacidad de prueba',
            'ACTIVO'
        );
        SET v_estudiante = LAST_INSERT_ID();
        INSERT INTO estudiantes_encargados (
            id_estudiante, id_encargado, parentesco, es_principal
        ) VALUES (v_estudiante, v_encargado, 'DATO DE PRUEBA', 1);
        INSERT INTO inscripciones (
            id_estudiante, id_encargado, id_seccion,
            codigo_confirmacion, estado, fecha_confirmacion
        ) VALUES (
            v_estudiante,
            v_encargado,
            v_seccion,
            CONCAT('CAP-', LPAD(v_i, 3, '0')),
            'CONFIRMADA',
            CURRENT_TIMESTAMP(6)
        );
        SET v_i = v_i + 1;
    END WHILE;

    SELECT COUNT(*) INTO v_total
    FROM inscripciones
    WHERE id_seccion = v_seccion
      AND estado = 'CONFIRMADA';
    INSERT INTO resultados_fase_1 VALUES (
        'Ocupación de sección 30/30', '30 confirmadas',
        CONCAT(v_total, ' confirmadas'),
        IF(v_total = 30, 'OK', 'FALLO')
    );

    SELECT CASE
        WHEN COUNT(i.id_inscripcion) < s.capacidad THEN 1
        ELSE 0
    END INTO v_total
    FROM secciones s
    LEFT JOIN inscripciones i
        ON i.id_seccion = s.id_seccion
       AND i.estado = 'CONFIRMADA'
    WHERE s.id_seccion = v_seccion
    GROUP BY s.id_seccion, s.capacidad;
    INSERT INTO resultados_fase_1 VALUES (
        'Decisión de cupo al llegar a 30/30', 'RECHAZAR',
        IF(v_total = 0, 'RECHAZAR', 'ADMITIR'),
        IF(v_total = 0, 'OK', 'FALLO')
    );

    INSERT INTO configuraciones_ubicacion (
        id_usuario_creador, descripcion, latitud, longitud, radio_metros
    ) VALUES (
        (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'admin.demo'),
        'Coordenada ficticia de validación', 0.0000000, 0.0000000, 100.00
    );
    INSERT INTO marcajes_asistencia (
        id_docente, id_usuario, id_configuracion_ubicacion, tipo,
        latitud_dispositivo, longitud_dispositivo, precision_metros,
        distancia_metros, dentro_radio
    ) VALUES (
        (SELECT id_docente FROM docentes WHERE codigo_docente = 'DOC-DEMO-001'),
        (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'carlos.mendez'),
        LAST_INSERT_ID(), 'ENTRADA', 0.0000000, 0.0000000, 5.00, 0.00, 1
    );
    SELECT COUNT(*) INTO v_total FROM marcajes_asistencia;
    INSERT INTO resultados_fase_1 VALUES (
        'Persistencia de marcaje válido', '1', CAST(v_total AS CHAR),
        IF(v_total = 1, 'OK', 'FALLO')
    );

    INSERT INTO solicitudes_permiso (
        id_docente, fecha_solicitada, tipo_motivo, descripcion
    ) VALUES (
        (SELECT id_docente FROM docentes WHERE codigo_docente = 'DOC-DEMO-001'),
        '2026-09-30', 'DATO DE PRUEBA', 'Solicitud ficticia de validación'
    );
    SET @id_permiso_fase_1 = LAST_INSERT_ID();
    INSERT INTO historial_solicitudes_permiso (
        id_solicitud_permiso, id_usuario_actor,
        estado_anterior, estado_nuevo, comentario
    ) VALUES (
        @id_permiso_fase_1,
        (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'carlos.mendez'),
        NULL, 'PENDIENTE', 'Creación de prueba'
    );
    INSERT INTO documentos_permiso (
        id_solicitud_permiso, nombre_original, ruta_almacenamiento,
        tipo_mime, tamano_bytes
    ) VALUES (
        @id_permiso_fase_1, 'constancia-demo.pdf',
        'pruebas/fase1/constancia-demo.pdf', 'application/pdf', 1024
    );
    INSERT INTO notificaciones (
        id_solicitud_permiso, id_usuario_destinatario,
        tipo, titulo, mensaje
    ) VALUES (
        @id_permiso_fase_1,
        (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'admin.demo'),
        'NUEVA_SOLICITUD', 'Solicitud ficticia', 'Notificación de validación'
    );
    SELECT COUNT(*) INTO v_total
    FROM solicitudes_permiso sp
    JOIN historial_solicitudes_permiso hp
        ON hp.id_solicitud_permiso = sp.id_solicitud_permiso
    JOIN documentos_permiso dp
        ON dp.id_solicitud_permiso = sp.id_solicitud_permiso
    JOIN notificaciones n
        ON n.id_solicitud_permiso = sp.id_solicitud_permiso
    WHERE sp.id_solicitud_permiso = @id_permiso_fase_1;
    INSERT INTO resultados_fase_1 VALUES (
        'Flujo permiso-historial-documento-notificación', '1 relación',
        CONCAT(v_total, ' relación(es)'),
        IF(v_total = 1, 'OK', 'FALLO')
    );

    INSERT INTO tokens_recuperacion (
        id_usuario, token_hash, fecha_expiracion
    ) VALUES (
        (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'carlos.mendez'),
        REPEAT('a', 64), DATE_ADD(CURRENT_TIMESTAMP(6), INTERVAL 30 MINUTE)
    );
    SELECT COUNT(*) INTO v_total
    FROM tokens_recuperacion
    WHERE estado = 'ACTIVO'
      AND fecha_expiracion > CURRENT_TIMESTAMP(6);
    INSERT INTO resultados_fase_1 VALUES (
        'Token de recuperación no vencido', '1', CAST(v_total AS CHAR),
        IF(v_total = 1, 'OK', 'FALLO')
    );

    SELECT prueba, esperado, observado, resultado
    FROM resultados_fase_1
    ORDER BY prueba;

    SELECT
        COUNT(*) AS pruebas_ejecutadas,
        SUM(resultado = 'OK') AS pruebas_exitosas,
        SUM(resultado = 'FALLO') AS pruebas_fallidas
    FROM resultados_fase_1;
END$$

DELIMITER ;

CALL validar_fase_1();
