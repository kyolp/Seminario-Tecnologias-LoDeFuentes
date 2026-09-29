<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Seccion;
use PDO;

class SeccionRepository implements SeccionRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findAll(?int $gradoPeriodoId = null): array
    {
        $sql = 'SELECT s.id_seccion, s.id_grado_periodo, s.codigo, s.nombre, s.capacidad, s.habilitada,
                       g.nombre AS grado_nombre, p.nombre AS periodo_nombre
                FROM secciones s
                JOIN grados_periodos gp ON gp.id_grado_periodo = s.id_grado_periodo
                JOIN grados g ON g.id_grado = gp.id_grado
                JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar';

        $params = [];
        if ($gradoPeriodoId !== null && $gradoPeriodoId > 0) {
            $sql .= ' WHERE s.id_grado_periodo = :gradoPeriodoId';
            $params[':gradoPeriodoId'] = $gradoPeriodoId;
        }

        $sql .= ' ORDER BY p.fecha_inicio DESC, g.orden ASC, s.codigo ASC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_INT);
        }
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = Seccion::fromArray($row);
        }

        return $results;
    }

    public function findById(int $id): ?Seccion
    {
        $sql = 'SELECT s.id_seccion, s.id_grado_periodo, s.codigo, s.nombre, s.capacidad, s.habilitada,
                       g.nombre AS grado_nombre, p.nombre AS periodo_nombre
                FROM secciones s
                JOIN grados_periodos gp ON gp.id_grado_periodo = s.id_grado_periodo
                JOIN grados g ON g.id_grado = gp.id_grado
                JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar
                WHERE s.id_seccion = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Seccion::fromArray($data) : null;
    }

    public function findByGradoPeriodoAndCodigo(int $gradoPeriodoId, string $codigo): ?Seccion
    {
        $sql = 'SELECT s.id_seccion, s.id_grado_periodo, s.codigo, s.nombre, s.capacidad, s.habilitada,
                       g.nombre AS grado_nombre, p.nombre AS periodo_nombre
                FROM secciones s
                JOIN grados_periodos gp ON gp.id_grado_periodo = s.id_grado_periodo
                JOIN grados g ON g.id_grado = gp.id_grado
                JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar
                WHERE s.id_grado_periodo = :gradoPeriodoId
                  AND s.codigo = :codigo
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':gradoPeriodoId', $gradoPeriodoId, PDO::PARAM_INT);
        $stmt->bindValue(':codigo', trim($codigo), PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Seccion::fromArray($data) : null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada)
                VALUES (:id_grado_periodo, :codigo, :nombre, :capacidad, :habilitada)';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_grado_periodo', (int)($data['id_grado_periodo'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':codigo', strtoupper(trim((string)($data['codigo'] ?? ''))), PDO::PARAM_STR);
        $stmt->bindValue(':nombre', !empty($data['nombre']) ? trim((string)$data['nombre']) : null, PDO::PARAM_STR);
        $stmt->bindValue(':capacidad', (int)($data['capacidad'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':habilitada', isset($data['habilitada']) ? (int)$data['habilitada'] : 1, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE secciones
                SET codigo = :codigo,
                    nombre = :nombre,
                    capacidad = :capacidad,
                    habilitada = :habilitada
                WHERE id_seccion = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':codigo', strtoupper(trim((string)($data['codigo'] ?? ''))), PDO::PARAM_STR);
        $stmt->bindValue(':nombre', !empty($data['nombre']) ? trim((string)$data['nombre']) : null, PDO::PARAM_STR);
        $stmt->bindValue(':capacidad', (int)($data['capacidad'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':habilitada', isset($data['habilitada']) ? (int)$data['habilitada'] : 1, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function updateHabilitada(int $id, int $habilitada): bool
    {
        $sql = 'UPDATE secciones SET habilitada = :habilitada WHERE id_seccion = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':habilitada', $habilitada === 1 ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function hasAsignaciones(int $id): bool
    {
        $sql = 'SELECT 1 FROM asignaciones_docentes WHERE id_seccion = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    public function hasInscripciones(int $id): bool
    {
        $sql = 'SELECT 1 FROM inscripciones WHERE id_seccion = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $sql = 'DELETE FROM secciones WHERE id_seccion = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
