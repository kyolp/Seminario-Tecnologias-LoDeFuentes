<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Docente;
use PDO;

class DocenteRepository implements DocenteRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findAll(?string $search = null): array
    {
        $sql = 'SELECT d.id_docente, d.id_usuario, d.codigo_docente, d.fecha_ingreso, d.estado,
                       u.nombre_usuario, u.correo, u.nombres, u.apellidos, u.estado AS usuario_estado
                FROM docentes d
                JOIN usuarios u ON u.id_usuario = d.id_usuario';

        $params = [];
        if ($search !== null && trim($search) !== '') {
            $sql .= ' WHERE d.codigo_docente LIKE :search
                         OR u.nombres LIKE :search
                         OR u.apellidos LIKE :search
                         OR u.correo LIKE :search
                         OR u.nombre_usuario LIKE :search';
            $params[':search'] = '%' . trim($search) . '%';
        }

        $sql .= ' ORDER BY u.apellidos ASC, u.nombres ASC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = Docente::fromArray($row);
        }

        return $results;
    }

    public function findById(int $id): ?Docente
    {
        $sql = 'SELECT d.id_docente, d.id_usuario, d.codigo_docente, d.fecha_ingreso, d.estado,
                       u.nombre_usuario, u.correo, u.nombres, u.apellidos, u.estado AS usuario_estado
                FROM docentes d
                JOIN usuarios u ON u.id_usuario = d.id_usuario
                WHERE d.id_docente = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Docente::fromArray($data) : null;
    }

    public function findByCodigo(string $codigo): ?Docente
    {
        $sql = 'SELECT d.id_docente, d.id_usuario, d.codigo_docente, d.fecha_ingreso, d.estado,
                       u.nombre_usuario, u.correo, u.nombres, u.apellidos, u.estado AS usuario_estado
                FROM docentes d
                JOIN usuarios u ON u.id_usuario = d.id_usuario
                WHERE d.codigo_docente = :codigo
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':codigo', trim($codigo), PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Docente::fromArray($data) : null;
    }

    public function findByUsuarioId(int $usuarioId): ?Docente
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

    public function createDocente(int $idUsuario, ?string $codigoDocente, ?string $fechaIngreso, string $estado = 'ACTIVO'): int
    {
        $sql = 'INSERT INTO docentes (id_usuario, codigo_docente, fecha_ingreso, estado)
                VALUES (:id_usuario, :codigo_docente, :fecha_ingreso, :estado)';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':codigo_docente', $codigoDocente !== null && trim($codigoDocente) !== '' ? trim($codigoDocente) : null, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_ingreso', $fechaIngreso !== null && trim($fechaIngreso) !== '' ? trim($fechaIngreso) : null, PDO::PARAM_STR);
        $stmt->bindValue(':estado', strtoupper($estado), PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    public function updateDocente(int $idDocente, ?string $codigoDocente, ?string $fechaIngreso, string $estado): bool
    {
        $sql = 'UPDATE docentes
                SET codigo_docente = :codigo_docente,
                    fecha_ingreso = :fecha_ingreso,
                    estado = :estado
                WHERE id_docente = :id_docente';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':codigo_docente', $codigoDocente !== null && trim($codigoDocente) !== '' ? trim($codigoDocente) : null, PDO::PARAM_STR);
        $stmt->bindValue(':fecha_ingreso', $fechaIngreso !== null && trim($fechaIngreso) !== '' ? trim($fechaIngreso) : null, PDO::PARAM_STR);
        $stmt->bindValue(':estado', strtoupper($estado), PDO::PARAM_STR);
        $stmt->bindValue(':id_docente', $idDocente, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function updateEstado(int $idDocente, string $estado): bool
    {
        $sql = 'UPDATE docentes SET estado = :estado WHERE id_docente = :id_docente';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':estado', strtoupper($estado), PDO::PARAM_STR);
        $stmt->bindValue(':id_docente', $idDocente, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function hasAsignaciones(int $idDocente): bool
    {
        $sql = 'SELECT 1 FROM asignaciones_docentes WHERE id_docente = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $idDocente, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    public function delete(int $idDocente): bool
    {
        $sql = 'DELETE FROM docentes WHERE id_docente = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $idDocente, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
