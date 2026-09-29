<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Aula;
use PDO;

class AulaRepository implements AulaRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findAll(): array
    {
        $sql = 'SELECT id_aula, codigo, nombre, ubicacion_referencia, activa
                FROM aulas
                ORDER BY codigo ASC';

        $stmt = $this->db->query($sql);
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = Aula::fromArray($row);
        }

        return $results;
    }

    public function findById(int $id): ?Aula
    {
        $sql = 'SELECT id_aula, codigo, nombre, ubicacion_referencia, activa
                FROM aulas
                WHERE id_aula = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Aula::fromArray($data) : null;
    }

    public function findByCodigo(string $codigo): ?Aula
    {
        $sql = 'SELECT id_aula, codigo, nombre, ubicacion_referencia, activa
                FROM aulas
                WHERE codigo = :codigo
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':codigo', trim($codigo), PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Aula::fromArray($data) : null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO aulas (codigo, nombre, ubicacion_referencia, activa)
                VALUES (:codigo, :nombre, :ubicacion_referencia, :activa)';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':codigo', trim((string)($data['codigo'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':nombre', !empty($data['nombre']) ? trim((string)$data['nombre']) : null, PDO::PARAM_STR);
        $stmt->bindValue(':ubicacion_referencia', !empty($data['ubicacion_referencia']) ? trim((string)$data['ubicacion_referencia']) : null, PDO::PARAM_STR);
        $stmt->bindValue(':activa', isset($data['activa']) ? (int)$data['activa'] : 1, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE aulas
                SET codigo = :codigo,
                    nombre = :nombre,
                    ubicacion_referencia = :ubicacion_referencia,
                    activa = :activa
                WHERE id_aula = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':codigo', trim((string)($data['codigo'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':nombre', !empty($data['nombre']) ? trim((string)$data['nombre']) : null, PDO::PARAM_STR);
        $stmt->bindValue(':ubicacion_referencia', !empty($data['ubicacion_referencia']) ? trim((string)$data['ubicacion_referencia']) : null, PDO::PARAM_STR);
        $stmt->bindValue(':activa', isset($data['activa']) ? (int)$data['activa'] : 1, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function updateActiva(int $id, int $activa): bool
    {
        $sql = 'UPDATE aulas SET activa = :activa WHERE id_aula = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':activa', $activa === 1 ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function hasAsignaciones(int $id): bool
    {
        $sql = 'SELECT 1 FROM asignaciones_docentes WHERE id_aula = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $sql = 'DELETE FROM aulas WHERE id_aula = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
