-- Completar el carné interno de estudiantes existentes sin código.
-- Seleccionar primero la base del proyecto en MySQL Workbench.
-- Se recomienda respaldar la base antes de ejecutar.
-- Conserva los códigos existentes.
-- Puede ejecutarse nuevamente: solo actualiza códigos vacíos.
-- Los estudiantes nuevos reciben su carné desde InscripcionService.php.

START TRANSACTION;

UPDATE estudiantes
SET codigo_estudiante = CONCAT(
    'LDF-',
    CASE
        WHEN id_estudiante < 1000000
        THEN LPAD(CAST(id_estudiante AS CHAR), 6, '0')
        ELSE CAST(id_estudiante AS CHAR)
    END
)
WHERE codigo_estudiante IS NULL
   OR codigo_estudiante = '';

COMMIT;