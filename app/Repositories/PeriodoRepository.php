<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\PeriodoEscolar;
use PDO;

class PeriodoRepository implements PeriodoRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findAll(): array
    {
        $sql = 'SELECT id_periodo_escolar, nombre, fecha_inicio, fecha_fin, estado
                FROM periodos_escolares
                ORDER BY fecha_inicio DESC, id_periodo_escolar DESC';

        $stmt = $this->db->query($sql);
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = PeriodoEscolar::fromArray($row);
        }

        return $results;
    }

    public function findById(int $id): ?PeriodoEscolar
    {
        $sql = 'SELECT id_periodo_escolar, nombre, fecha_inicio, fecha_fin, estado
                FROM periodos_escolares
                WHERE id_periodo_escolar = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? PeriodoEscolar::fromArray($data) : null;
    }

    public function findByNombre(string $nombre): ?PeriodoEscolar
    {
        $sql = 'SELECT id_periodo_escolar, nombre, fecha_inicio, fecha_fin, estado
                FROM periodos_escolares
                WHERE nombre = :nombre
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nombre', trim($nombre), PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? PeriodoEscolar::fromArray($data) : null;
    }

    public function findActivo(): ?PeriodoEscolar
    {
        $sql = "SELECT id_periodo_escolar, nombre, fecha_inicio, fecha_fin, estado
                FROM periodos_escolares
                WHERE estado = 'ACTIVO'
                LIMIT 1";

        $stmt = $this->db->query($sql);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? PeriodoEscolar::fromArray($data) : null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO periodos_escolares (nombre, fecha_inicio, fecha_fin, estado)
                VALUES (:nombre, :fecha_inicio, :fecha_fin, :estado)';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nombre', trim((string)($data['nombre'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':fecha_inicio', (string)($data['fecha_inicio'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin', (string)($data['fecha_fin'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':estado', strtoupper((string)($data['estado'] ?? 'PLANIFICADO')), PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE periodos_escolares
                SET nombre = :nombre,
                    fecha_inicio = :fecha_inicio,
                    fecha_fin = :fecha_fin,
                    estado = :estado
                WHERE id_periodo_escolar = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nombre', trim((string)($data['nombre'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':fecha_inicio', (string)($data['fecha_inicio'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':fecha_fin', (string)($data['fecha_fin'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':estado', strtoupper((string)($data['estado'] ?? 'PLANIFICADO')), PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function updateEstado(int $id, string $estado): bool
    {
        $sql = 'UPDATE periodos_escolares SET estado = :estado WHERE id_periodo_escolar = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':estado', strtoupper($estado), PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function hasGradosPeriodos(int $id): bool
    {
        $sql = 'SELECT 1 FROM grados_periodos WHERE id_periodo_escolar = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $sql = 'DELETE FROM periodos_escolares WHERE id_periodo_escolar = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
