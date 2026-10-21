-- =============================================================================
-- SISTEMA WEB — ESCUELA OFICIAL RURAL MIXTA LO DE FUENTES
-- SCRIPT DE DATOS INICIALES Y DEMOSTRACIÓN INSTITUCIONAL (SEED.SQL)
-- FASE 16 — LIMPIEZA FINAL Y DATOS DE DEMOSTRACIÓN NATURALES
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- -----------------------------------------------------------------------------
-- 1. ROLES INSTITUCIONALES
-- -----------------------------------------------------------------------------
INSERT INTO roles (nombre, descripcion, activo)
VALUES
    ('ADMINISTRACION', 'Personal con funciones directivas y administrativas', 1),
    ('DOCENTE', 'Personal docente con funciones de impartición de clases y control de aula', 1);

-- -----------------------------------------------------------------------------
-- 2. USUARIOS ADMINISTRADORES Y DOCENTES
-- Hashes generados con PASSWORD_BCRYPT (costo 10)
-- Contraseñas institucionales de demostración:
--   Administración: ClaveAdmin2026!
--   Docentes:       ClaveDocente2026!
-- -----------------------------------------------------------------------------
INSERT INTO usuarios (
    id_rol, nombre_usuario, correo, clave_hash, nombres, apellidos, estado
)
VALUES
    -- Administradores
    (
        (SELECT id_rol FROM roles WHERE nombre = 'ADMINISTRACION'),
        'estuardo.gonzalez',
        'estuardo.gonzalez@escuela.com',
        '$2y$10$0pkL7lYSJBM5mlqXxf9QVeG33jakFtIpM23UuKHs4UhtybX3MymJW',
        'Estuardo Leonel',
        'González Morales',
        'ACTIVO'
    ),
    (
        (SELECT id_rol FROM roles WHERE nombre = 'ADMINISTRACION'),
        'patricia.alvarez',
        'patricia.alvarez@escuela.com',
        '$2y$10$0pkL7lYSJBM5mlqXxf9QVeG33jakFtIpM23UuKHs4UhtybX3MymJW',
        'Patricia Sofía',
        'Álvarez Castillo',
        'ACTIVO'
    ),

    -- Docentes (12 profesores para los 6 grados y 2 secciones)
    (
        (SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'),
        'carlos.mendez',
        'carlos.mendez@escuela.com',
        '$2y$10$9zCQrOhppuS5iHV8FMda8ui6xqxmRV1DbN/ZdmpJSP7Es4BoiCul6',
        'Carlos Alberto',
        'Méndez Pérez',
        'ACTIVO'
    ),
    (
        (SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'),
        'maria.lopez',
        'maria.lopez@escuela.com',
        '$2y$10$9zCQrOhppuS5iHV8FMda8ui6xqxmRV1DbN/ZdmpJSP7Es4BoiCul6',
        'María Mercedes',
        'López García',
        'ACTIVO'
    ),
    (
        (SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'),
        'diego.castillo',
        'diego.castillo@escuela.com',
        '$2y$10$9zCQrOhppuS5iHV8FMda8ui6xqxmRV1DbN/ZdmpJSP7Es4BoiCul6',
        'Diego Alejandro',
        'Castillo Hernández',
        'ACTIVO'
    ),
    (
        (SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'),
        'sofia.ramirez',
        'sofia.ramirez@escuela.com',
        '$2y$10$9zCQrOhppuS5iHV8FMda8ui6xqxmRV1DbN/ZdmpJSP7Es4BoiCul6',
        'Sofía Alejandra',
        'Ramírez Morales',
        'ACTIVO'
    ),
    (
        (SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'),
        'juan.morales',
        'juan.morales@escuela.com',
        '$2y$10$9zCQrOhppuS5iHV8FMda8ui6xqxmRV1DbN/ZdmpJSP7Es4BoiCul6',
        'Juan Carlos',
        'Morales Estrada',
        'ACTIVO'
    ),
    (
        (SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'),
        'ana.herrera',
        'ana.herrera@escuela.com',
        '$2y$10$9zCQrOhppuS5iHV8FMda8ui6xqxmRV1DbN/ZdmpJSP7Es4BoiCul6',
        'Ana Lucía',
        'Herrera Gómez',
        'ACTIVO'
    ),
    (
        (SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'),
        'fernando.reyes',
        'fernando.reyes@escuela.com',
        '$2y$10$9zCQrOhppuS5iHV8FMda8ui6xqxmRV1DbN/ZdmpJSP7Es4BoiCul6',
        'Fernando José',
        'Reyes Salazar',
        'ACTIVO'
    ),
    (
        (SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'),
        'claudia.navarro',
        'claudia.navarro@escuela.com',
        '$2y$10$9zCQrOhppuS5iHV8FMda8ui6xqxmRV1DbN/ZdmpJSP7Es4BoiCul6',
        'Claudia Marina',
        'Navarro Paz',
        'ACTIVO'
    ),
    (
        (SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'),
        'ricardo.delgado',
        'ricardo.delgado@escuela.com',
        '$2y$10$9zCQrOhppuS5iHV8FMda8ui6xqxmRV1DbN/ZdmpJSP7Es4BoiCul6',
        'Ricardo Antonio',
        'Delgado Orozco',
        'ACTIVO'
    ),
    (
        (SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'),
        'elena.figueroa',
        'elena.figueroa@escuela.com',
        '$2y$10$9zCQrOhppuS5iHV8FMda8ui6xqxmRV1DbN/ZdmpJSP7Es4BoiCul6',
        'Elena Beatriz',
        'Figueroa Barrios',
        'ACTIVO'
    ),
    (
        (SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'),
        'jorge.aguilar',
        'jorge.aguilar@escuela.com',
        '$2y$10$9zCQrOhppuS5iHV8FMda8ui6xqxmRV1DbN/ZdmpJSP7Es4BoiCul6',
        'Jorge Enrique',
        'Aguilar Ramos',
        'ACTIVO'
    ),
    (
        (SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'),
        'silvia.mendoza',
        'silvia.mendoza@escuela.com',
        '$2y$10$9zCQrOhppuS5iHV8FMda8ui6xqxmRV1DbN/ZdmpJSP7Es4BoiCul6',
        'Silvia Cristina',
        'Mendoza Ruiz',
        'ACTIVO'
    );

-- -----------------------------------------------------------------------------
-- 3. EXPEDIENTES DOCENTES
-- -----------------------------------------------------------------------------
INSERT INTO docentes (id_usuario, codigo_docente, fecha_ingreso, estado)
VALUES
    ((SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'carlos.mendez'), 'DOC-2026-001', '2024-01-15', 'ACTIVO'),
    ((SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'maria.lopez'), 'DOC-2026-002', '2024-01-15', 'ACTIVO'),
    ((SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'diego.castillo'), 'DOC-2026-003', '2024-02-01', 'ACTIVO'),
    ((SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'sofia.ramirez'), 'DOC-2026-004', '2024-02-01', 'ACTIVO'),
    ((SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'juan.morales'), 'DOC-2026-005', '2024-03-01', 'ACTIVO'),
    ((SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'ana.herrera'), 'DOC-2026-006', '2024-03-01', 'ACTIVO'),
    ((SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'fernando.reyes'), 'DOC-2026-007', '2025-01-10', 'ACTIVO'),
    ((SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'claudia.navarro'), 'DOC-2026-008', '2025-01-10', 'ACTIVO'),
    ((SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'ricardo.delgado'), 'DOC-2026-009', '2025-02-15', 'ACTIVO'),
    ((SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'elena.figueroa'), 'DOC-2026-010', '2025-02-15', 'ACTIVO'),
    ((SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'jorge.aguilar'), 'DOC-2026-011', '2025-06-01', 'ACTIVO'),
    ((SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'silvia.mendoza'), 'DOC-2026-012', '2025-06-01', 'ACTIVO');

-- -----------------------------------------------------------------------------
-- 4. PERÍODOS ESCOLARES
-- -----------------------------------------------------------------------------
INSERT INTO periodos_escolares (nombre, fecha_inicio, fecha_fin, estado)
VALUES
    ('Ciclo Escolar 2026', '2026-01-15', '2026-11-30', 'ACTIVO'),
    ('Ciclo Escolar 2025', '2025-01-15', '2025-11-30', 'CERRADO');

-- -----------------------------------------------------------------------------
-- 5. GRADOS DE EDUCACIÓN PRIMARIA
-- -----------------------------------------------------------------------------
INSERT INTO grados (codigo, nombre, orden, activo)
VALUES
    ('PRIM-1', 'Primero Primaria', 1, 1),
    ('PRIM-2', 'Segundo Primaria', 2, 1),
    ('PRIM-3', 'Tercero Primaria', 3, 1),
    ('PRIM-4', 'Cuarto Primaria', 4, 1),
    ('PRIM-5', 'Quinto Primaria', 5, 1),
    ('PRIM-6', 'Sexto Primaria', 6, 1);

-- -----------------------------------------------------------------------------
-- 6. ASOCIACIÓN DE GRADOS AL PERÍODO ACTIVO 2026
-- -----------------------------------------------------------------------------
INSERT INTO grados_periodos (id_periodo_escolar, id_grado, habilitado)
SELECT p.id_periodo_escolar, g.id_grado, 1
FROM periodos_escolares p
CROSS JOIN grados g
WHERE p.nombre = 'Ciclo Escolar 2026';

-- -----------------------------------------------------------------------------
-- 7. AULAS FÍSICAS INSTITUCIONALES
-- -----------------------------------------------------------------------------
INSERT INTO aulas (codigo, nombre, ubicacion_referencia, activa)
VALUES
    ('AUL-101', 'Aula 101 — Pabellón A', 'Edificio Central, Primer Nivel', 1),
    ('AUL-102', 'Aula 102 — Pabellón A', 'Edificio Central, Primer Nivel', 1),
    ('AUL-103', 'Aula 103 — Pabellón A', 'Edificio Central, Primer Nivel', 1),
    ('AUL-201', 'Aula 201 — Pabellón B', 'Edificio Central, Segundo Nivel', 1),
    ('AUL-202', 'Aula 202 — Pabellón B', 'Edificio Central, Segundo Nivel', 1),
    ('AUL-203', 'Aula 203 — Pabellón B', 'Edificio Central, Segundo Nivel', 1),
    ('AUL-301', 'Aula 301 — Salón de Usos Múltiples', 'Módulo Norte', 1),
    ('AUL-302', 'Aula 302 — Sala de Cómputo', 'Módulo Norte', 1);

-- -----------------------------------------------------------------------------
-- 8. SECCIONES ESCOLARES (SECCIÓN A Y B POR CADA GRADO)
-- -----------------------------------------------------------------------------
INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada)
SELECT
    gp.id_grado_periodo,
    'A',
    CONCAT(g.nombre, ' — Sección A'),
    30,
    1
FROM grados_periodos gp
JOIN grados g ON g.id_grado = gp.id_grado
JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar
WHERE p.nombre = 'Ciclo Escolar 2026';

INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada)
SELECT
    gp.id_grado_periodo,
    'B',
    CONCAT(g.nombre, ' — Sección B'),
    25,
    1
FROM grados_periodos gp
JOIN grados g ON g.id_grado = gp.id_grado
JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar
WHERE p.nombre = 'Ciclo Escolar 2026';

-- -----------------------------------------------------------------------------
-- 9. ASIGNACIONES ACADÉMICAS DOCENTES
-- -----------------------------------------------------------------------------
INSERT INTO asignaciones_docentes (
    id_docente, id_seccion, id_aula, id_usuario_creador, fecha_inicio, estado
)
SELECT
    d.id_docente,
    s.id_seccion,
    a.id_aula,
    (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'estuardo.gonzalez'),
    '2026-01-15',
    'ACTIVA'
FROM (
    SELECT 'carlos.mendez' as u, 'PRIM-1' as g, 'A' as sec, 'AUL-101' as aul UNION ALL
    SELECT 'maria.lopez', 'PRIM-1', 'B', 'AUL-102' UNION ALL
    SELECT 'diego.castillo', 'PRIM-2', 'A', 'AUL-103' UNION ALL
    SELECT 'sofia.ramirez', 'PRIM-2', 'B', 'AUL-201' UNION ALL
    SELECT 'juan.morales', 'PRIM-3', 'A', 'AUL-202' UNION ALL
    SELECT 'ana.herrera', 'PRIM-3', 'B', 'AUL-203' UNION ALL
    SELECT 'fernando.reyes', 'PRIM-4', 'A', 'AUL-101' UNION ALL
    SELECT 'claudia.navarro', 'PRIM-4', 'B', 'AUL-102' UNION ALL
    SELECT 'ricardo.delgado', 'PRIM-5', 'A', 'AUL-103' UNION ALL
    SELECT 'elena.figueroa', 'PRIM-5', 'B', 'AUL-201' UNION ALL
    SELECT 'jorge.aguilar', 'PRIM-6', 'A', 'AUL-202' UNION ALL
    SELECT 'silvia.mendoza', 'PRIM-6', 'B', 'AUL-203'
) map
JOIN usuarios u ON u.nombre_usuario = map.u
JOIN docentes d ON d.id_usuario = u.id_usuario
JOIN grados g ON g.codigo = map.g
JOIN grados_periodos gp ON gp.id_grado = g.id_grado
JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar AND p.nombre = 'Ciclo Escolar 2026'
JOIN secciones s ON s.id_grado_periodo = gp.id_grado_periodo AND s.codigo = map.sec
JOIN aulas a ON a.codigo = map.aul;

-- -----------------------------------------------------------------------------
-- 10. PADRES, MADRES Y ENCARGADOS
-- -----------------------------------------------------------------------------
INSERT INTO encargados (nombres, apellidos, telefono, correo)
VALUES
    ('Carmen Yolanda', 'Estrada Morales', '5521-4389', 'carmen.estrada@gmail.com'),
    ('Héctor Manuel', 'Gómez Vásquez', '4210-9874', 'hector.gomez@outlook.com'),
    ('Brenda Leticia', 'Juárez Méndez', '5832-1145', 'brenda.juarez@gmail.com'),
    ('Víctor Hugo', 'Reyes Pineda', '4765-8821', 'victor.reyes@yahoo.com'),
    ('Rosa María', 'Santos Flores', '5109-3324', 'rosa.santos@gmail.com'),
    ('Gustavo Adolfo', 'Paredes Ramos', '4398-7712', 'gustavo.paredes@outlook.com'),
    ('Irma Elizabeth', 'Castillo Díaz', '5941-6650', 'irma.castillo@gmail.com'),
    ('Julio César', 'Monroy Guzmán', '4180-2299', 'julio.monroy@gmail.com'),
    ('Vilma Esperanza', 'Ortiz Solís', '5634-9011', 'vilma.ortiz@outlook.com'),
    ('Mario Roberto', 'Cifuentes Lima', '4811-5543', 'mario.cifuentes@gmail.com'),
    ('Gloria Marina', 'Vega Cordero', '5277-8833', 'gloria.vega@gmail.com'),
    ('Edgar Rolando', 'Palacios Cano', '4623-1190', 'edgar.palacios@outlook.com'),
    ('Sonia Noemí', 'Cabrera Rivas', '5412-6678', 'sonia.cabrera@gmail.com'),
    ('Francisco Javier', 'Lemus Arriola', '4955-4321', 'francisco.lemus@gmail.com'),
    ('Marta Lidia', 'Sandoval Girón', '5301-8844', 'marta.sandoval@outlook.com'),
    ('Byron Leonel', 'Chacón Rosales', '4789-3310', 'byron.chacon@gmail.com'),
    ('Ingrid Paola', 'Samayoa Fuentes', '5890-2211', 'ingrid.samayoa@gmail.com'),
    ('Walter Geovany', 'Barillas Pinto', '4144-7765', 'walter.barillas@gmail.com');

-- -----------------------------------------------------------------------------
-- 11. ESTUDIANTES
-- -----------------------------------------------------------------------------
INSERT INTO estudiantes (codigo_estudiante, nombres, apellidos, fecha_nacimiento, estado)
VALUES
    ('EST-2026-001', 'Kevin Josué', 'Estrada Morales', '2019-03-12', 'ACTIVO'),
    ('EST-2026-002', 'Andrea Marisol', 'Gómez Juárez', '2019-06-25', 'ACTIVO'),
    ('EST-2026-003', 'Brayan Alexander', 'Juárez Méndez', '2019-01-18', 'ACTIVO'),
    ('EST-2026-004', 'Kimberly Paola', 'Reyes Santos', '2019-08-04', 'ACTIVO'),
    ('EST-2026-005', 'Josué David', 'Santos Flores', '2018-02-14', 'ACTIVO'),
    ('EST-2026-006', 'Evelyn Rocío', 'Paredes Castillo', '2018-05-30', 'ACTIVO'),
    ('EST-2026-007', 'Christian Daniel', 'Castillo Díaz', '2018-09-19', 'ACTIVO'),
    ('EST-2026-008', 'Fátima Gabriela', 'Monroy Ortiz', '2018-11-05', 'ACTIVO'),
    ('EST-2026-009', 'Brandon Steven', 'Ortiz Solís', '2017-04-22', 'ACTIVO'),
    ('EST-2026-010', 'Jennifer Alejandra', 'Cifuentes Vega', '2017-07-11', 'ACTIVO'),
    ('EST-2026-011', 'Erick Fernando', 'Vega Palacios', '2017-10-03', 'ACTIVO'),
    ('EST-2026-012', 'Melanie Susana', 'Palacios Cano', '2017-12-16', 'ACTIVO'),
    ('EST-2026-013', 'Anthony Gabriel', 'Cabrera Lemus', '2016-03-08', 'ACTIVO'),
    ('EST-2026-014', 'Dayana Michelle', 'Lemus Arriola', '2016-06-19', 'ACTIVO'),
    ('EST-2026-015', 'Jonathan Eduardo', 'Sandoval Girón', '2016-09-27', 'ACTIVO'),
    ('EST-2026-016', 'Génesis Abigail', 'Sandoval Girón', '2016-09-27', 'ACTIVO'),
    ('EST-2026-017', 'Jefferson Rodrigo', 'Chacón Samayoa', '2015-02-10', 'ACTIVO'),
    ('EST-2026-018', 'Ashley Nicole', 'Samayoa Fuentes', '2015-05-14', 'ACTIVO'),
    ('EST-2026-019', 'Cristopher Joel', 'Barillas Pinto', '2015-08-23', 'ACTIVO'),
    ('EST-2026-020', 'Karla Daniela', 'Barillas Pinto', '2015-08-23', 'ACTIVO'),
    ('EST-2026-021', 'Ángel Gabriel', 'Estrada Morales', '2014-01-30', 'ACTIVO'),
    ('EST-2026-022', 'Wendy Noemí', 'Gómez Juárez', '2014-04-17', 'ACTIVO'),
    ('EST-2026-023', 'Walter Alexis', 'Reyes Santos', '2014-07-09', 'ACTIVO'),
    ('EST-2026-024', 'Silvia Carolina', 'Paredes Castillo', '2014-11-21', 'ACTIVO');

-- -----------------------------------------------------------------------------
-- 12. RELACIÓN ESTUDIANTES — ENCARGADOS
-- -----------------------------------------------------------------------------
INSERT INTO estudiantes_encargados (id_estudiante, id_encargado, parentesco, es_principal)
VALUES
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-001'), (SELECT id_encargado FROM encargados WHERE correo = 'carmen.estrada@gmail.com'), 'Madre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-002'), (SELECT id_encargado FROM encargados WHERE correo = 'hector.gomez@outlook.com'), 'Padre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-003'), (SELECT id_encargado FROM encargados WHERE correo = 'brenda.juarez@gmail.com'), 'Madre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-004'), (SELECT id_encargado FROM encargados WHERE correo = 'victor.reyes@yahoo.com'), 'Padre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-005'), (SELECT id_encargado FROM encargados WHERE correo = 'rosa.santos@gmail.com'), 'Madre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-006'), (SELECT id_encargado FROM encargados WHERE correo = 'gustavo.paredes@outlook.com'), 'Padre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-007'), (SELECT id_encargado FROM encargados WHERE correo = 'irma.castillo@gmail.com'), 'Madre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-008'), (SELECT id_encargado FROM encargados WHERE correo = 'julio.monroy@gmail.com'), 'Padre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-009'), (SELECT id_encargado FROM encargados WHERE correo = 'vilma.ortiz@outlook.com'), 'Madre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-010'), (SELECT id_encargado FROM encargados WHERE correo = 'mario.cifuentes@gmail.com'), 'Padre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-011'), (SELECT id_encargado FROM encargados WHERE correo = 'gloria.vega@gmail.com'), 'Madre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-012'), (SELECT id_encargado FROM encargados WHERE correo = 'edgar.palacios@outlook.com'), 'Padre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-013'), (SELECT id_encargado FROM encargados WHERE correo = 'sonia.cabrera@gmail.com'), 'Madre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-014'), (SELECT id_encargado FROM encargados WHERE correo = 'francisco.lemus@gmail.com'), 'Padre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-015'), (SELECT id_encargado FROM encargados WHERE correo = 'marta.sandoval@outlook.com'), 'Madre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-016'), (SELECT id_encargado FROM encargados WHERE correo = 'marta.sandoval@outlook.com'), 'Madre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-017'), (SELECT id_encargado FROM encargados WHERE correo = 'byron.chacon@gmail.com'), 'Padre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-018'), (SELECT id_encargado FROM encargados WHERE correo = 'ingrid.samayoa@gmail.com'), 'Madre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-019'), (SELECT id_encargado FROM encargados WHERE correo = 'walter.barillas@gmail.com'), 'Padre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-020'), (SELECT id_encargado FROM encargados WHERE correo = 'walter.barillas@gmail.com'), 'Padre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-021'), (SELECT id_encargado FROM encargados WHERE correo = 'carmen.estrada@gmail.com'), 'Madre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-022'), (SELECT id_encargado FROM encargados WHERE correo = 'hector.gomez@outlook.com'), 'Padre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-023'), (SELECT id_encargado FROM encargados WHERE correo = 'victor.reyes@yahoo.com'), 'Padre', 1),
    ((SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-2026-024'), (SELECT id_encargado FROM encargados WHERE correo = 'gustavo.paredes@outlook.com'), 'Padre', 1);

-- -----------------------------------------------------------------------------
-- 13. INSCRIPCIONES CONFIRMADAS EN CICLO ESCOLAR 2026
-- -----------------------------------------------------------------------------
INSERT INTO inscripciones (
    id_estudiante, id_encargado, id_seccion, codigo_confirmacion, estado, fecha_confirmacion
)
SELECT
    ee.id_estudiante,
    ee.id_encargado,
    s.id_seccion,
    map.cod_ins,
    'CONFIRMADA',
    '2026-01-20 08:30:00'
FROM (
    SELECT 'EST-2026-001' as est, 'PRIM-1' as g, 'A' as sec, 'INS-2026-00101' as cod_ins UNION ALL
    SELECT 'EST-2026-002', 'PRIM-1', 'A', 'INS-2026-00102' UNION ALL
    SELECT 'EST-2026-003', 'PRIM-1', 'B', 'INS-2026-00103' UNION ALL
    SELECT 'EST-2026-004', 'PRIM-1', 'B', 'INS-2026-00104' UNION ALL
    SELECT 'EST-2026-005', 'PRIM-2', 'A', 'INS-2026-00105' UNION ALL
    SELECT 'EST-2026-006', 'PRIM-2', 'A', 'INS-2026-00106' UNION ALL
    SELECT 'EST-2026-007', 'PRIM-2', 'B', 'INS-2026-00107' UNION ALL
    SELECT 'EST-2026-008', 'PRIM-2', 'B', 'INS-2026-00108' UNION ALL
    SELECT 'EST-2026-009', 'PRIM-3', 'A', 'INS-2026-00109' UNION ALL
    SELECT 'EST-2026-010', 'PRIM-3', 'A', 'INS-2026-00110' UNION ALL
    SELECT 'EST-2026-011', 'PRIM-3', 'B', 'INS-2026-00111' UNION ALL
    SELECT 'EST-2026-012', 'PRIM-3', 'B', 'INS-2026-00112' UNION ALL
    SELECT 'EST-2026-013', 'PRIM-4', 'A', 'INS-2026-00113' UNION ALL
    SELECT 'EST-2026-014', 'PRIM-4', 'A', 'INS-2026-00114' UNION ALL
    SELECT 'EST-2026-015', 'PRIM-4', 'B', 'INS-2026-00115' UNION ALL
    SELECT 'EST-2026-016', 'PRIM-4', 'B', 'INS-2026-00116' UNION ALL
    SELECT 'EST-2026-017', 'PRIM-5', 'A', 'INS-2026-00117' UNION ALL
    SELECT 'EST-2026-018', 'PRIM-5', 'A', 'INS-2026-00118' UNION ALL
    SELECT 'EST-2026-019', 'PRIM-5', 'B', 'INS-2026-00119' UNION ALL
    SELECT 'EST-2026-020', 'PRIM-5', 'B', 'INS-2026-00120' UNION ALL
    SELECT 'EST-2026-021', 'PRIM-6', 'A', 'INS-2026-00121' UNION ALL
    SELECT 'EST-2026-022', 'PRIM-6', 'A', 'INS-2026-00122' UNION ALL
    SELECT 'EST-2026-023', 'PRIM-6', 'B', 'INS-2026-00123' UNION ALL
    SELECT 'EST-2026-024', 'PRIM-6', 'B', 'INS-2026-00124'
) map
JOIN estudiantes e ON e.codigo_estudiante = map.est
JOIN estudiantes_encargados ee ON ee.id_estudiante = e.id_estudiante AND ee.es_principal = 1
JOIN grados g ON g.codigo = map.g
JOIN grados_periodos gp ON gp.id_grado = g.id_grado
JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar AND p.nombre = 'Ciclo Escolar 2026'
JOIN secciones s ON s.id_grado_periodo = gp.id_grado_periodo AND s.codigo = map.sec;

-- -----------------------------------------------------------------------------
-- 14. CONFIGURACIÓN DE UBICACIÓN INSTITUCIONAL (GEOCERCA ESCOLAR)
-- Escuela Oficial Rural Mixta Lo de Fuentes - Sede Central
-- -----------------------------------------------------------------------------
INSERT INTO configuraciones_ubicacion (
    id_usuario_creador, descripcion, latitud, longitud, radio_metros, fecha_inicio, activa
)
VALUES (
    (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'estuardo.gonzalez'),
    'Escuela Oficial Rural Mixta Lo de Fuentes - Sede Central',
    14.6349150,
    -90.5068820,
    100.00,
    '2026-01-15 00:00:00',
    1
);

-- -----------------------------------------------------------------------------
-- 15. MARCAJES DE ASISTENCIA DOCENTE DE DEMOSTRACIÓN
-- Coordenadas reales dentro de la geocerca de la institución (distancia < 5m)
-- -----------------------------------------------------------------------------
INSERT INTO marcajes_asistencia (
    id_docente, id_usuario, id_configuracion_ubicacion, fecha_hora, tipo,
    latitud_dispositivo, longitud_dispositivo, precision_metros, distancia_metros, dentro_radio
)
SELECT
    d.id_docente,
    u.id_usuario,
    (SELECT id_configuracion_ubicacion FROM configuraciones_ubicacion WHERE activa = 1 LIMIT 1),
    m.f_hora,
    m.tipo,
    14.6349180,
    -90.5068800,
    4.50,
    2.50,
    1
FROM (
    SELECT 'carlos.mendez' as u, '2026-10-15 07:12:30' as f_hora, 'ENTRADA' as tipo UNION ALL
    SELECT 'carlos.mendez', '2026-10-15 12:35:10', 'SALIDA' UNION ALL
    SELECT 'carlos.mendez', '2026-10-16 07:14:00', 'ENTRADA' UNION ALL
    SELECT 'carlos.mendez', '2026-10-16 12:32:45', 'SALIDA' UNION ALL
    SELECT 'carlos.mendez', '2026-10-19 07:10:15', 'ENTRADA' UNION ALL
    SELECT 'carlos.mendez', '2026-10-19 12:38:20', 'SALIDA' UNION ALL
    SELECT 'maria.lopez', '2026-10-19 07:15:20', 'ENTRADA' UNION ALL
    SELECT 'maria.lopez', '2026-10-19 12:31:00', 'SALIDA' UNION ALL
    SELECT 'diego.castillo', '2026-10-19 07:18:40', 'ENTRADA' UNION ALL
    SELECT 'diego.castillo', '2026-10-19 12:34:15', 'SALIDA'
) m
JOIN usuarios u ON u.nombre_usuario = m.u
JOIN docentes d ON d.id_usuario = u.id_usuario;

-- -----------------------------------------------------------------------------
-- 16. SOLICITUDES DE PERMISO E HISTORIAL DE DECISIÓN
-- -----------------------------------------------------------------------------
-- Solicitud 1: APROBADA (Carlos Méndez)
INSERT INTO solicitudes_permiso (id_docente, fecha_solicitada, tipo_motivo, descripcion, estado, fecha_solicitud)
VALUES (
    (SELECT id_docente FROM docentes WHERE codigo_docente = 'DOC-2026-001'),
    '2026-10-28',
    'Cita médica IGSS',
    'Consulta médica programada en clínica periférica del Instituto Guatemalteco de Seguridad Social.',
    'APROBADO',
    '2026-10-18 09:15:00'
);
SET @sol_1 = LAST_INSERT_ID();

INSERT INTO historial_solicitudes_permiso (id_solicitud_permiso, id_usuario_actor, estado_anterior, estado_nuevo, comentario, fecha_cambio)
VALUES
    (@sol_1, (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'carlos.mendez'), NULL, 'PENDIENTE', 'Registro inicial de solicitud de permiso por cita médica.', '2026-10-18 09:15:00'),
    (@sol_1, (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'estuardo.gonzalez'), 'PENDIENTE', 'APROBADO', 'Permiso autorizado conforme a la programación institucional.', '2026-10-18 11:30:00');

-- Solicitud 2: PENDIENTE (María López)
INSERT INTO solicitudes_permiso (id_docente, fecha_solicitada, tipo_motivo, descripcion, estado, fecha_solicitud)
VALUES (
    (SELECT id_docente FROM docentes WHERE codigo_docente = 'DOC-2026-002'),
    '2026-10-30',
    'Trámite institucional',
    'Gestión de documentación de actualización docente en sede departamental.',
    'PENDIENTE',
    '2026-10-20 08:00:00'
);
SET @sol_2 = LAST_INSERT_ID();

INSERT INTO historial_solicitudes_permiso (id_solicitud_permiso, id_usuario_actor, estado_anterior, estado_nuevo, comentario, fecha_cambio)
VALUES
    (@sol_2, (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'maria.lopez'), NULL, 'PENDIENTE', 'Solicitud registrada pendiente de revisión directiva.', '2026-10-20 08:00:00');

-- Solicitud 3: RECHAZADA (Diego Castillo)
INSERT INTO solicitudes_permiso (id_docente, fecha_solicitada, tipo_motivo, descripcion, estado, fecha_solicitud)
VALUES (
    (SELECT id_docente FROM docentes WHERE codigo_docente = 'DOC-2026-003'),
    '2026-10-25',
    'Asunto particular',
    'Actividad particular no justificada en día de evaluación bimestral.',
    'RECHAZADO',
    '2026-10-17 14:20:00'
);
SET @sol_3 = LAST_INSERT_ID();

INSERT INTO historial_solicitudes_permiso (id_solicitud_permiso, id_usuario_actor, estado_anterior, estado_nuevo, comentario, fecha_cambio)
VALUES
    (@sol_3, (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'diego.castillo'), NULL, 'PENDIENTE', 'Solicitud ingresada por el docente.', '2026-10-17 14:20:00'),
    (@sol_3, (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'estuardo.gonzalez'), 'PENDIENTE', 'RECHAZADO', 'No es factible autorizar el permiso en fecha de evaluaciones bimestrales.', '2026-10-17 16:45:00');

-- -----------------------------------------------------------------------------
-- 17. NOTIFICACIONES INTERNAS
-- -----------------------------------------------------------------------------
-- Notificación al administrador por nueva solicitud de Carlos Méndez
INSERT INTO notificaciones (id_solicitud_permiso, id_usuario_destinatario, tipo, titulo, mensaje, fecha_creacion, fecha_lectura)
VALUES (
    @sol_1,
    (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'estuardo.gonzalez'),
    'NUEVA_SOLICITUD',
    'Nueva solicitud de permiso docente',
    'El docente Carlos Alberto Méndez Pérez ha registrado una solicitud de permiso para el 28/10/2026.',
    '2026-10-18 09:15:00',
    '2026-10-18 11:28:00'
);

-- Notificación de resolución al docente Carlos Méndez
INSERT INTO notificaciones (id_solicitud_permiso, id_usuario_destinatario, tipo, titulo, mensaje, fecha_creacion, fecha_lectura)
VALUES (
    @sol_1,
    (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'carlos.mendez'),
    'CAMBIO_ESTADO',
    'Resolución de solicitud de permiso',
    'Su solicitud de permiso para el 28/10/2026 ha sido APROBADA por la dirección.',
    '2026-10-18 11:30:00',
    '2026-10-18 14:05:00'
);

-- Notificación al administrador por nueva solicitud de María López (no leída)
INSERT INTO notificaciones (id_solicitud_permiso, id_usuario_destinatario, tipo, titulo, mensaje, fecha_creacion, fecha_lectura)
VALUES (
    @sol_2,
    (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'estuardo.gonzalez'),
    'NUEVA_SOLICITUD',
    'Nueva solicitud de permiso docente',
    'La docente María Mercedes López García ha registrado una solicitud de permiso para el 30/10/2026.',
    '2026-10-20 08:00:00',
    NULL
);

-- Notificación de resolución a Diego Castillo (rechazada)
INSERT INTO notificaciones (id_solicitud_permiso, id_usuario_destinatario, tipo, titulo, mensaje, fecha_creacion, fecha_lectura)
VALUES (
    @sol_3,
    (SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'diego.castillo'),
    'CAMBIO_ESTADO',
    'Resolución de solicitud de permiso',
    'Su solicitud de permiso para el 25/10/2026 ha sido RECHAZADA. Motivo: Coincidencia con evaluaciones.',
    '2026-10-17 16:45:00',
    '2026-10-17 18:00:00'
);

-- -----------------------------------------------------------------------------
-- FIN DE SEED.SQL (CERO TOKENS, CERO FIXTURES TÉCNICOS)
-- -----------------------------------------------------------------------------
