<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\AsignacionDocente;
use App\Models\Docente;
use PDO;

/**
 * Implementación del repositorio de persistencia para el Portal Docente.
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 */
class PortalDocenteRepository implements PortalDocenteRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findDocenteByUsuarioId(int $usuarioId): ?Docente
    {
        $sql = 'SELECT d.id_docente, d.id_usuario, d.codigo_docente, d.fecha_ingreso, d.estado,
                       u.nombre_usuario, u.correo, u.nombres, u.apellidos, u.estado AS usuario_estado
                FROM docentes d
                JOIN usuarios u ON u.id_usuario = d.id_usuario
                WHERE d.id_usuario = :usuarioId
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':usuarioId', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Docente::fromArray($data) : null;
    }

    public function findActiveAsignacionesByDocente(int $idDocente): array
    {
        $sql = 'SELECT a.id_asignacion_docente, a.id_docente, a.id_seccion, a.id_aula, a.id_usuario_creador,
                       a.fecha_inicio, a.fecha_fin, a.estado, a.id_seccion_activa,
                       a.fecha_creacion, a.fecha_actualizacion,
                       TRIM(CONCAT(u.nombres, " ", u.apellidos)) AS docente_nombre,
                       d.codigo_docente,
                       u.correo AS docente_correo,
                       s.codigo AS seccion_codigo,
                       s.nombre AS seccion_nombre,
                       s.capacidad AS seccion_capacidad,
                       g.id_grado,
                       g.nombre AS grado_nombre,
                       p.id_periodo_escolar,
                       p.nombre AS periodo_nombre,
                       au.codigo AS aula_codigo,
                       au.nombre AS aula_nombre,
                       TRIM(CONCAT(uc.nombres, " ", uc.apellidos)) AS creador_nombre
                FROM asignaciones_docentes a
                JOIN docentes d ON d.id_docente = a.id_docente
                JOIN usuarios u ON u.id_usuario = d.id_usuario
                JOIN secciones s ON s.id_seccion = a.id_seccion
                JOIN grados_periodos gp ON gp.id_grado_periodo = s.id_grado_periodo
                JOIN grados g ON g.id_grado = gp.id_grado
                JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar
                JOIN aulas au ON au.id_aula = a.id_aula
                LEFT JOIN usuarios uc ON uc.id_usuario = a.id_usuario_creador
                WHERE a.id_docente = :id_docente
                  AND a.estado = "ACTIVA"
                ORDER BY p.fecha_inicio DESC, g.orden ASC, s.codigo ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_docente', $idDocente, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = AsignacionDocente::fromArray($row);
        }

        return $results;
    }

    public function findAsignacionByIdAndDocente(int $idAsignacion, int $idDocente): ?AsignacionDocente
    {
        $sql = 'SELECT a.id_asignacion_docente, a.id_docente, a.id_seccion, a.id_aula, a.id_usuario_creador,
                       a.fecha_inicio, a.fecha_fin, a.estado, a.id_seccion_activa,
                       a.fecha_creacion, a.fecha_actualizacion,
                       TRIM(CONCAT(u.nombres, " ", u.apellidos)) AS docente_nombre,
                       d.codigo_docente,
                       u.correo AS docente_correo,
                       s.codigo AS seccion_codigo,
                       s.nombre AS seccion_nombre,
                       s.capacidad AS seccion_capacidad,
                       g.id_grado,
                       g.nombre AS grado_nombre,
                       p.id_periodo_escolar,
                       p.nombre AS periodo_nombre,
                       au.codigo AS aula_codigo,
                       au.nombre AS aula_nombre,
                       TRIM(CONCAT(uc.nombres, " ", uc.apellidos)) AS creador_nombre
                FROM asignaciones_docentes a
                JOIN docentes d ON d.id_docente = a.id_docente
                JOIN usuarios u ON u.id_usuario = d.id_usuario
                JOIN secciones s ON s.id_seccion = a.id_seccion
                JOIN grados_periodos gp ON gp.id_grado_periodo = s.id_grado_periodo
                JOIN grados g ON g.id_grado = gp.id_grado
                JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar
                JOIN aulas au ON au.id_aula = a.id_aula
                LEFT JOIN usuarios uc ON uc.id_usuario = a.id_usuario_creador
                WHERE a.id_asignacion_docente = :id_asignacion
                  AND a.id_docente = :id_docente
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_asignacion', $idAsignacion, PDO::PARAM_INT);
        $stmt->bindValue(':id_docente', $idDocente, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? AsignacionDocente::fromArray($row) : null;
    }

    public function isSeccionAsignadaADocente(int $idDocente, int $idSeccion): bool
    {
        $sql = 'SELECT 1
                FROM asignaciones_docentes
                WHERE id_docente = :id_docente
                  AND id_seccion = :id_seccion
                  AND estado = "ACTIVA"
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_docente', $idDocente, PDO::PARAM_INT);
        $stmt->bindValue(':id_seccion', $idSeccion, PDO::PARAM_INT);
        $stmt->execute();

        return (bool)$stmt->fetchColumn();
    }

    public function findEstudiantesBySeccionDocente(int $idDocente, int $idSeccion): array
    {
        // Doble barrera de aislamiento horizontal: se comprueba ad.id_docente = :id_docente y ad.estado = 'ACTIVA'
        $sql = 'SELECT e.id_estudiante, e.codigo_estudiante, e.nombres, e.apellidos, e.fecha_nacimiento,
                       i.codigo_confirmacion, i.fecha_confirmacion, i.estado AS inscripcion_estado
                FROM inscripciones i
                JOIN estudiantes e ON e.id_estudiante = i.id_estudiante
                JOIN asignaciones_docentes ad ON ad.id_seccion = i.id_seccion
                WHERE i.id_seccion = :id_seccion
                  AND ad.id_docente = :id_docente
                  AND ad.estado = "ACTIVA"
                  AND i.estado = "CONFIRMADA"
                  AND e.estado = "ACTIVO"
                ORDER BY e.apellidos ASC, e.nombres ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_seccion', $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':id_docente', $idDocente, PDO::PARAM_INT);
        $stmt->execute();

        $estudiantes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $estudiantes[] = [
                'id_estudiante' => (int)$row['id_estudiante'],
                'codigo_estudiante' => $row['codigo_estudiante'] !== null ? (string)$row['codigo_estudiante'] : null,
                'nombres' => (string)$row['nombres'],
                'apellidos' => (string)$row['apellidos'],
                'fecha_nacimiento' => $row['fecha_nacimiento'] !== null ? (string)$row['fecha_nacimiento'] : null,
                'codigo_confirmacion' => $row['codigo_confirmacion'] !== null ? (string)$row['codigo_confirmacion'] : null,
                'fecha_confirmacion' => $row['fecha_confirmacion'] !== null ? (string)$row['fecha_confirmacion'] : null,
                'inscripcion_estado' => (string)$row['inscripcion_estado'],
            ];
        }

        return $estudiantes;
    }

    public function countEstudiantesBySeccionDocente(int $idDocente, int $idSeccion): int
    {
        $sql = 'SELECT COUNT(*)
                FROM inscripciones i
                JOIN estudiantes e ON e.id_estudiante = i.id_estudiante
                JOIN asignaciones_docentes ad ON ad.id_seccion = i.id_seccion
                WHERE i.id_seccion = :id_seccion
                  AND ad.id_docente = :id_docente
                  AND ad.estado = "ACTIVA"
                  AND i.estado = "CONFIRMADA"
                  AND e.estado = "ACTIVO"';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_seccion', $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':id_docente', $idDocente, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }
}
