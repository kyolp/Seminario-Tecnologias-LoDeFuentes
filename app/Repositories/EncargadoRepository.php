<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Encargado;
use App\Models\EstudianteEncargado;
use PDO;

class EncargadoRepository implements EncargadoRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findById(int $id): ?Encargado
    {
        $sql = 'SELECT id_encargado, nombres, apellidos, telefono, correo, fecha_creacion, fecha_actualizacion
                FROM encargados
                WHERE id_encargado = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Encargado::fromArray($data) : null;
    }

    public function findMatching(string $nombres, string $apellidos, ?string $telefono, ?string $correo): ?Encargado
    {
        $nombres = trim($nombres);
        $apellidos = trim($apellidos);

        // Si tiene correo, buscar por nombres, apellidos y correo
        if (!empty($correo)) {
            $stmt = $this->db->prepare('SELECT id_encargado, nombres, apellidos, telefono, correo, fecha_creacion, fecha_actualizacion
                                        FROM encargados
                                        WHERE LOWER(nombres) = LOWER(:nombres)
                                          AND LOWER(apellidos) = LOWER(:apellidos)
                                          AND LOWER(correo) = LOWER(:correo)
                                        LIMIT 1');
            $stmt->execute([
                ':nombres' => $nombres,
                ':apellidos' => $apellidos,
                ':correo' => trim($correo),
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return Encargado::fromArray($row);
            }
        }

        // Si tiene teléfono, buscar por nombres, apellidos y teléfono
        if (!empty($telefono)) {
            $stmt = $this->db->prepare('SELECT id_encargado, nombres, apellidos, telefono, correo, fecha_creacion, fecha_actualizacion
                                        FROM encargados
                                        WHERE LOWER(nombres) = LOWER(:nombres)
                                          AND LOWER(apellidos) = LOWER(:apellidos)
                                          AND telefono = :telefono
                                        LIMIT 1');
            $stmt->execute([
                ':nombres' => $nombres,
                ':apellidos' => $apellidos,
                ':telefono' => trim($telefono),
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return Encargado::fromArray($row);
            }
        }

        return null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO encargados (nombres, apellidos, telefono, correo)
                VALUES (:nombres, :apellidos, :telefono, :correo)';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nombres', trim((string)($data['nombres'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':apellidos', trim((string)($data['apellidos'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':telefono', !empty($data['telefono']) ? trim((string)$data['telefono']) : null, PDO::PARAM_STR);
        $stmt->bindValue(':correo', !empty($data['correo']) ? trim((string)$data['correo']) : null, PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    public function linkEstudiante(int $idEstudiante, int $idEncargado, string $parentesco, bool $esPrincipal = true): int
    {
        // Verificar si ya existe el vínculo
        $stmtCheck = $this->db->prepare('SELECT id_estudiante_encargado FROM estudiantes_encargados WHERE id_estudiante = :e AND id_encargado = :enc LIMIT 1');
        $stmtCheck->execute([':e' => $idEstudiante, ':enc' => $idEncargado]);
        $existingId = $stmtCheck->fetchColumn();
        if ($existingId) {
            return (int)$existingId;
        }

        $sql = 'INSERT INTO estudiantes_encargados (id_estudiante, id_encargado, parentesco, es_principal)
                VALUES (:id_estudiante, :id_encargado, :parentesco, :es_principal)';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_estudiante', $idEstudiante, PDO::PARAM_INT);
        $stmt->bindValue(':id_encargado', $idEncargado, PDO::PARAM_INT);
        $stmt->bindValue(':parentesco', trim($parentesco), PDO::PARAM_STR);
        $stmt->bindValue(':es_principal', $esPrincipal ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    public function hasVinculo(int $idEstudiante, int $idEncargado): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM estudiantes_encargados WHERE id_estudiante = :e AND id_encargado = :enc LIMIT 1');
        $stmt->execute([':e' => $idEstudiante, ':enc' => $idEncargado]);
        return (bool)$stmt->fetchColumn();
    }

    public function findVinculo(int $idEstudiante, int $idEncargado): ?EstudianteEncargado
    {
        $stmt = $this->db->prepare('SELECT id_estudiante_encargado, id_estudiante, id_encargado, parentesco, es_principal, fecha_creacion
                                    FROM estudiantes_encargados
                                    WHERE id_estudiante = :e AND id_encargado = :enc
                                    LIMIT 1');
        $stmt->execute([':e' => $idEstudiante, ':enc' => $idEncargado]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? EstudianteEncargado::fromArray($row) : null;
    }
}
