<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Grado;
use PDO;

class GradoRepository implements GradoRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findAll(): array
    {
        $sql = 'SELECT id_grado, codigo, nombre, orden, activo
                FROM grados
                ORDER BY orden ASC, nombre ASC';

        $stmt = $this->db->query($sql);
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = Grado::fromArray($row);
        }

        return $results;
    }

    public function findById(int $id): ?Grado
    {
        $sql = 'SELECT id_grado, codigo, nombre, orden, activo
                FROM grados
                WHERE id_grado = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Grado::fromArray($data) : null;
    }

    public function findByCodigo(string $codigo): ?Grado
    {
        $sql = 'SELECT id_grado, codigo, nombre, orden, activo
                FROM grados
                WHERE codigo = :codigo
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':codigo', trim($codigo), PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Grado::fromArray($data) : null;
    }

    public function findByNombre(string $nombre): ?Grado
    {
        $sql = 'SELECT id_grado, codigo, nombre, orden, activo
                FROM grados
                WHERE nombre = :nombre
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nombre', trim($nombre), PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Grado::fromArray($data) : null;
    }

    public function findByOrden(int $orden): ?Grado
    {
        $sql = 'SELECT id_grado, codigo, nombre, orden, activo
                FROM grados
                WHERE orden = :orden
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':orden', $orden, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Grado::fromArray($data) : null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO grados (codigo, nombre, orden, activo)
                VALUES (:codigo, :nombre, :orden, :activo)';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':codigo', trim((string)($data['codigo'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':nombre', trim((string)($data['nombre'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':orden', (int)($data['orden'] ?? 1), PDO::PARAM_INT);
        $stmt->bindValue(':activo', isset($data['activo']) ? (int)$data['activo'] : 1, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE grados
                SET codigo = :codigo,
                    nombre = :nombre,
                    orden = :orden,
                    activo = :activo
                WHERE id_grado = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':codigo', trim((string)($data['codigo'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':nombre', trim((string)($data['nombre'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':orden', (int)($data['orden'] ?? 1), PDO::PARAM_INT);
        $stmt->bindValue(':activo', isset($data['activo']) ? (int)$data['activo'] : 1, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function updateActivo(int $id, int $activo): bool
    {
        $sql = 'UPDATE grados SET activo = :activo WHERE id_grado = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':activo', $activo === 1 ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function hasGradosPeriodos(int $id): bool
    {
        $sql = 'SELECT 1 FROM grados_periodos WHERE id_grado = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $sql = 'DELETE FROM grados WHERE id_grado = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
