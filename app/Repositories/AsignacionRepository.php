<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\AsignacionDocente;
use PDO;

class AsignacionRepository implements AsignacionRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    private function getBaseSelectQuery(): string
    {
        return 'SELECT a.id_asignacion_docente, a.id_docente, a.id_seccion, a.id_aula, a.id_usuario_creador,
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
                LEFT JOIN usuarios uc ON uc.id_usuario = a.id_usuario_creador';
    }

    public function findAll(array $filters = []): array
    {
        $sql = $this->getBaseSelectQuery();
        $conditions = [];
        $params = [];

        if (!empty($filters['periodo_id']) && (int)$filters['periodo_id'] > 0) {
            $conditions[] = 'p.id_periodo_escolar = :periodo_id';
            $params[':periodo_id'] = (int)$filters['periodo_id'];
        }

        if (!empty($filters['docente_id']) && (int)$filters['docente_id'] > 0) {
            $conditions[] = 'a.id_docente = :docente_id';
            $params[':docente_id'] = (int)$filters['docente_id'];
        }

        if (!empty($filters['seccion_id']) && (int)$filters['seccion_id'] > 0) {
            $conditions[] = 'a.id_seccion = :seccion_id';
            $params[':seccion_id'] = (int)$filters['seccion_id'];
        }

        if (!empty($filters['grado_id']) && (int)$filters['grado_id'] > 0) {
            $conditions[] = 'g.id_grado = :grado_id';
            $params[':grado_id'] = (int)$filters['grado_id'];
        }

        if (!empty($filters['estado']) && in_array($filters['estado'], ['ACTIVA', 'INACTIVA'], true)) {
            $conditions[] = 'a.estado = :estado';
            $params[':estado'] = $filters['estado'];
        }

        if (!empty($filters['q'])) {
            $q = '%' . trim((string)$filters['q']) . '%';
            $conditions[] = '(u.nombres LIKE :q1 OR u.apellidos LIKE :q2 OR d.codigo_docente LIKE :q3 OR s.codigo LIKE :q4 OR au.codigo LIKE :q5)';
            $params[':q1'] = $q;
            $params[':q2'] = $q;
            $params[':q3'] = $q;
            $params[':q4'] = $q;
            $params[':q5'] = $q;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY p.fecha_inicio DESC, g.orden ASC, s.codigo ASC, a.estado ASC, a.fecha_inicio DESC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $val, $type);
        }
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = AsignacionDocente::fromArray($row);
        }

        return $results;
    }

    public function findById(int $id): ?AsignacionDocente
    {
        $sql = $this->getBaseSelectQuery() . ' WHERE a.id_asignacion_docente = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? AsignacionDocente::fromArray($row) : null;
    }

    public function findActiveBySeccion(int $idSeccion): ?AsignacionDocente
    {
        $sql = $this->getBaseSelectQuery() . ' WHERE a.id_seccion = :id_seccion AND a.estado = "ACTIVA" LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_seccion', $idSeccion, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? AsignacionDocente::fromArray($row) : null;
    }

    public function findByDocenteAndPeriodo(int $idDocente, int $idPeriodo): array
    {
        $sql = $this->getBaseSelectQuery() . ' WHERE a.id_docente = :id_docente AND p.id_periodo_escolar = :id_periodo ORDER BY a.estado ASC, s.codigo ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_docente', $idDocente, PDO::PARAM_INT);
        $stmt->bindValue(':id_periodo', $idPeriodo, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = AsignacionDocente::fromArray($row);
        }

        return $results;
    }

    public function existsActiveForSeccion(int $idSeccion, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM asignaciones_docentes WHERE id_seccion = :id_seccion AND estado = "ACTIVA"';
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id_asignacion_docente != :exclude_id';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_seccion', $idSeccion, PDO::PARAM_INT);
        if ($excludeId !== null && $excludeId > 0) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return (bool)$stmt->fetchColumn();
    }

    public function existsDuplicateInicio(int $idDocente, int $idSeccion, int $idAula, string $fechaInicio, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM asignaciones_docentes 
                WHERE id_docente = :id_docente 
                  AND id_seccion = :id_seccion 
                  AND id_aula = :id_aula 
                  AND fecha_inicio = :fecha_inicio';
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id_asignacion_docente != :exclude_id';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_docente', $idDocente, PDO::PARAM_INT);
        $stmt->bindValue(':id_seccion', $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':id_aula', $idAula, PDO::PARAM_INT);
        $stmt->bindValue(':fecha_inicio', $fechaInicio, PDO::PARAM_STR);
        if ($excludeId !== null && $excludeId > 0) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return (bool)$stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO asignaciones_docentes (
                    id_docente, id_seccion, id_aula, id_usuario_creador,
                    fecha_inicio, fecha_fin, estado
                ) VALUES (
                    :id_docente, :id_seccion, :id_aula, :id_usuario_creador,
                    :fecha_inicio, :fecha_fin, :estado
                )';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_docente', (int)$data['id_docente'], PDO::PARAM_INT);
        $stmt->bindValue(':id_seccion', (int)$data['id_seccion'], PDO::PARAM_INT);
        $stmt->bindValue(':id_aula', (int)$data['id_aula'], PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario_creador', (int)$data['id_usuario_creador'], PDO::PARAM_INT);
        $stmt->bindValue(':fecha_inicio', (string)$data['fecha_inicio'], PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin', !empty($data['fecha_fin']) ? (string)$data['fecha_fin'] : null, !empty($data['fecha_fin']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':estado', (string)($data['estado'] ?? 'ACTIVA'), PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE asignaciones_docentes
                SET id_aula = :id_aula,
                    fecha_inicio = :fecha_inicio,
                    fecha_fin = :fecha_fin,
                    estado = :estado
                WHERE id_asignacion_docente = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_aula', (int)$data['id_aula'], PDO::PARAM_INT);
        $stmt->bindValue(':fecha_inicio', (string)$data['fecha_inicio'], PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin', !empty($data['fecha_fin']) ? (string)$data['fecha_fin'] : null, !empty($data['fecha_fin']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':estado', (string)($data['estado'] ?? 'ACTIVA'), PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function updateEstado(int $id, string $estado, ?string $fechaFin = null): bool
    {
        $sql = 'UPDATE asignaciones_docentes
                SET estado = :estado,
                    fecha_fin = :fecha_fin
                WHERE id_asignacion_docente = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':estado', $estado, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin', $fechaFin, $fechaFin !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function countByDocente(int $idDocente): int
    {
        $sql = 'SELECT COUNT(*) FROM asignaciones_docentes WHERE id_docente = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $idDocente, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function countBySeccion(int $idSeccion): int
    {
        $sql = 'SELECT COUNT(*) FROM asignaciones_docentes WHERE id_seccion = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function countByAula(int $idAula): int
    {
        $sql = 'SELECT COUNT(*) FROM asignaciones_docentes WHERE id_aula = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $idAula, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
