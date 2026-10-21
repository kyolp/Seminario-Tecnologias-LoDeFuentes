-- Fase 1: DATOS DE PRUEBA ficticios y mínimos para ejecución de validacion.sql.
-- Usado exclusivamente en suite de regresión de Fase 1 en base de datos temporal.

INSERT INTO roles (nombre, descripcion)
VALUES
    ('ADMINISTRACION', 'Rol administrativo de demostración'),
    ('DOCENTE', 'Rol docente de demostración');

INSERT INTO usuarios (
    id_rol,
    nombre_usuario,
    correo,
    clave_hash,
    nombres,
    apellidos,
    estado
)
VALUES
    (
        (SELECT id_rol FROM roles WHERE nombre = 'ADMINISTRACION'),
        'admin.demo',
        'admin.demo@example.invalid',
        '$2y$10$9adBP4.DDi/AJfmRHZUBROvyaVRKXPa67CaOqIiPy3yVEwgk5U0R6',
        'Administración',
        'Demostración',
        'ACTIVO'
    ),
    (
        (SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'),
        'carlos.mendez',
        'carlos.mendez@escuela.com',
        '$2y$10$/LZ/ivMueOQ/BlQxyrULOeKijxwC6226UrGyfb7TQvUp0zN6iYqb.',
        'Carlos',
        'Méndez',
        'ACTIVO'
    );

INSERT INTO docentes (
    id_usuario,
    codigo_docente,
    fecha_ingreso,
    estado
)
VALUES (
    (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'carlos.mendez'),
    'DOC-DEMO-001',
    '2026-01-01',
    'ACTIVO'
);

INSERT INTO periodos_escolares (
    nombre,
    fecha_inicio,
    fecha_fin,
    estado
)
VALUES (
    'Período de demostración 2026',
    '2026-01-01',
    '2026-12-31',
    'ACTIVO'
);

INSERT INTO grados (codigo, nombre, orden, activo)
VALUES
    ('GRADO-DEMO-1', 'Grado de prueba 1', 1, 1),
    ('GRADO-DEMO-2', 'Grado de prueba 2', 2, 1);

INSERT INTO grados_periodos (
    id_periodo_escolar,
    id_grado,
    habilitado
)
SELECT
    p.id_periodo_escolar,
    g.id_grado,
    1
FROM periodos_escolares p
CROSS JOIN grados g
WHERE p.nombre = 'Período de demostración 2026';

INSERT INTO secciones (
    id_grado_periodo,
    codigo,
    nombre,
    capacidad,
    habilitada
)
VALUES
    (
        (
            SELECT gp.id_grado_periodo
            FROM grados_periodos gp
            JOIN periodos_escolares p
                ON p.id_periodo_escolar = gp.id_periodo_escolar
            JOIN grados g ON g.id_grado = gp.id_grado
            WHERE p.nombre = 'Período de demostración 2026'
              AND g.codigo = 'GRADO-DEMO-1'
        ),
        'A',
        'Sección de prueba A',
        30,
        1
    ),
    (
        (
            SELECT gp.id_grado_periodo
            FROM grados_periodos gp
            JOIN periodos_escolares p
                ON p.id_periodo_escolar = gp.id_periodo_escolar
            JOIN grados g ON g.id_grado = gp.id_grado
            WHERE p.nombre = 'Período de demostración 2026'
              AND g.codigo = 'GRADO-DEMO-1'
        ),
        'B',
        'Sección de prueba B',
        25,
        1
    );

INSERT INTO aulas (codigo, nombre, ubicacion_referencia, activa)
VALUES (
    'AULA-DEMO-01',
    'Aula de demostración',
    'Ubicación ficticia de prueba',
    1
);

INSERT INTO asignaciones_docentes (
    id_docente,
    id_seccion,
    id_aula,
    id_usuario_creador,
    fecha_inicio,
    estado
)
VALUES (
    (SELECT id_docente FROM docentes WHERE codigo_docente = 'DOC-DEMO-001'),
    (SELECT id_seccion FROM secciones WHERE codigo = 'A' LIMIT 1),
    (SELECT id_aula FROM aulas WHERE codigo = 'AULA-DEMO-01'),
    (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'admin.demo'),
    '2026-01-01',
    'ACTIVA'
);

INSERT INTO estudiantes (
    codigo_estudiante,
    nombres,
    apellidos,
    fecha_nacimiento,
    estado
)
VALUES (
    'EST-DEMO-001',
    'Estudiante',
    'Demostración',
    '2018-01-15',
    'ACTIVO'
);

INSERT INTO encargados (
    nombres,
    apellidos,
    telefono,
    correo
)
VALUES (
    'Encargado',
    'Demostración',
    '0000-0000',
    'encargado.demo@example.invalid'
);

INSERT INTO estudiantes_encargados (
    id_estudiante,
    id_encargado,
    parentesco,
    es_principal
)
VALUES (
    (SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-DEMO-001'),
    (
        SELECT id_encargado
        FROM encargados
        WHERE correo = 'encargado.demo@example.invalid'
    ),
    'DATO DE PRUEBA',
    1
);

INSERT INTO inscripciones (
    id_estudiante,
    id_encargado,
    id_seccion,
    codigo_confirmacion,
    estado,
    fecha_confirmacion
)
VALUES (
    (SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-DEMO-001'),
    (
        SELECT id_encargado
        FROM encargados
        WHERE correo = 'encargado.demo@example.invalid'
    ),
    (SELECT id_seccion FROM secciones WHERE codigo = 'A' LIMIT 1),
    'DEMO-F1-0001',
    'CONFIRMADA',
    CURRENT_TIMESTAMP(6)
);
