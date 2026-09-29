<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Estudiante;
use PDO;

class EstudianteRepository implements EstudianteRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findAll(?string $search = null): array
    {
        $sql = 'SELECT id_estudiante, codigo_estudiante, nombres, apellidos, fecha_nacimiento, estado
                FROM estudiantes';

        $params = [];
        if ($search !== null && trim($search) !== '') {
            $sql .= ' WHERE codigo_estudiante LIKE :search
                         OR nombres LIKE :search
                         OR apellidos LIKE :search';
            $params[':search'] = '%' . trim($search) . '%';
        }

        $sql .= ' ORDER BY apellidos ASC, nombres ASC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = Estudiante::fromArray($row);
        }

        return $results;
    }

    public function findById(int $id): ?Estudiante
    {
        $sql = 'SELECT id_estudiante, codigo_estudiante, nombres, apellidos, fecha_nacimiento, estado
                FROM estudiantes
                WHERE id_estudiante = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Estudiante::fromArray($data) : null;
    }

    public function findByCodigo(string $codigo): ?Estudiante
    {
        $sql = 'SELECT id_estudiante, codigo_estudiante, nombres, apellidos, fecha_nacimiento, estado
                FROM estudiantes
                WHERE codigo_estudiante = :codigo
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':codigo', trim($codigo), PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Estudiante::fromArray($data) : null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO estudiantes (codigo_estudiante, nombres, apellidos, fecha_nacimiento, estado)
                VALUES (:codigo_estudiante, :nombres, :apellidos, :fecha_nacimiento, :estado)';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':codigo_estudiante', !empty($data['codigo_estudiante']) ? trim((string)$data['codigo_estudiante']) : null, PDO::PARAM_STR);
        $stmt->bindValue(':nombres', trim((string)($data['nombres'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':apellidos', trim((string)($data['apellidos'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':fecha_nacimiento', !empty($data['fecha_nacimiento']) ? (string)$data['fecha_nacimiento'] : null, PDO::PARAM_STR);
        $stmt->bindValue(':estado', strtoupper((string)($data['estado'] ?? 'ACTIVO')), PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE estudiantes
                SET codigo_estudiante = :codigo_estudiante,
                    nombres = :nombres,
                    apellidos = :apellidos,
                    fecha_nacimiento = :fecha_nacimiento,
                    estado = :estado
                WHERE id_estudiante = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':codigo_estudiante', !empty($data['codigo_estudiante']) ? trim((string)$data['codigo_estudiante']) : null, PDO::PARAM_STR);
        $stmt->bindValue(':nombres', trim((string)($data['nombres'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':apellidos', trim((string)($data['apellidos'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':fecha_nacimiento', !empty($data['fecha_nacimiento']) ? (string)$data['fecha_nacimiento'] : null, PDO::PARAM_STR);
        $stmt->bindValue(':estado', strtoupper((string)($data['estado'] ?? 'ACTIVO')), PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function updateEstado(int $id, string $estado): bool
    {
        $sql = 'UPDATE estudiantes SET estado = :estado WHERE id_estudiante = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':estado', strtoupper($estado), PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function hasInscripciones(int $id): bool
    {
        $sql = 'SELECT 1 FROM inscripciones WHERE id_estudiante = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    public function delete(int $id): bool
    {
        $sql = 'DELETE FROM estudiantes WHERE id_estudiante = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
