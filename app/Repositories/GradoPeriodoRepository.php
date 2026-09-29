<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\GradoPeriodo;
use PDO;

class GradoPeriodoRepository implements GradoPeriodoRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findAll(?int $periodoId = null): array
    {
        $sql = 'SELECT gp.id_grado_periodo, gp.id_periodo_escolar, gp.id_grado, gp.habilitado,
                       p.nombre AS periodo_nombre,
                       g.codigo AS grado_codigo, g.nombre AS grado_nombre, g.orden AS grado_orden
                FROM grados_periodos gp
                JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar
                JOIN grados g ON g.id_grado = gp.id_grado';

        $params = [];
        if ($periodoId !== null && $periodoId > 0) {
            $sql .= ' WHERE gp.id_periodo_escolar = :periodoId';
            $params[':periodoId'] = $periodoId;
        }

        $sql .= ' ORDER BY p.fecha_inicio DESC, g.orden ASC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_INT);
        }
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = GradoPeriodo::fromArray($row);
        }

        return $results;
    }

    public function findById(int $id): ?GradoPeriodo
    {
        $sql = 'SELECT gp.id_grado_periodo, gp.id_periodo_escolar, gp.id_grado, gp.habilitado,
                       p.nombre AS periodo_nombre,
                       g.codigo AS grado_codigo, g.nombre AS grado_nombre, g.orden AS grado_orden
                FROM grados_periodos gp
                JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar
                JOIN grados g ON g.id_grado = gp.id_grado
                WHERE gp.id_grado_periodo = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? GradoPeriodo::fromArray($data) : null;
    }

    public function findByPeriodoAndGrado(int $periodoId, int $gradoId): ?GradoPeriodo
    {
        $sql = 'SELECT gp.id_grado_periodo, gp.id_periodo_escolar, gp.id_grado, gp.habilitado,
                       p.nombre AS periodo_nombre,
                       g.codigo AS grado_codigo, g.nombre AS grado_nombre, g.orden AS grado_orden
                FROM grados_periodos gp
                JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar
                JOIN grados g ON g.id_grado = gp.id_grado
                WHERE gp.id_periodo_escolar = :periodoId
                  AND gp.id_grado = :gradoId
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':periodoId', $periodoId, PDO::PARAM_INT);
        $stmt->bindValue(':gradoId', $gradoId, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? GradoPeriodo::fromArray($data) : null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO grados_periodos (id_periodo_escolar, id_grado, habilitado)
                VALUES (:id_periodo_escolar, :id_grado, :habilitado)';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_periodo_escolar', (int)($data['id_periodo_escolar'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':id_grado', (int)($data['id_grado'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':habilitado', isset($data['habilitado']) ? (int)$data['habilitado'] : 1, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function updateHabilitado(int $id, int $habilitado): bool
    {
        $sql = 'UPDATE grados_periodos SET habilitado = :habilitado WHERE id_grado_periodo = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':habilitado', $habilitado === 1 ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function hasSecciones(int $id): bool
    {
        $sql = 'SELECT 1 FROM secciones WHERE id_grado_periodo = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $sql = 'DELETE FROM grados_periodos WHERE id_grado_periodo = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
