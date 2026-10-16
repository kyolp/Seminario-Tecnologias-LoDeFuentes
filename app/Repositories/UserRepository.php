<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;
use PDO;

/**
 * Repositorio de usuarios mediante PDO / MySQL con consultas preparadas nativas.
 */
class UserRepository implements UserRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Buscar un usuario por su nombre de usuario único (incluyendo su rol relacional).
     */
    public function findByUsername(string $username): ?User
    {
        $sql = 'SELECT u.id_usuario, u.id_rol, u.nombre_usuario, u.correo, u.clave_hash,
                       u.nombres, u.apellidos, u.estado, u.ultimo_acceso,
                       r.nombre AS rol_nombre
                FROM usuarios u
                JOIN roles r ON r.id_rol = u.id_rol
                WHERE u.nombre_usuario = :username
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':username', trim($username), PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }

        return User::fromArray($data);
    }

    /**
     * Buscar un usuario por su clave primaria ID (incluyendo su rol relacional).
     */
    public function findById(int $id): ?User
    {
        $sql = 'SELECT u.id_usuario, u.id_rol, u.nombre_usuario, u.correo, u.clave_hash,
                       u.nombres, u.apellidos, u.estado, u.ultimo_acceso,
                       r.nombre AS rol_nombre
                FROM usuarios u
                JOIN roles r ON r.id_rol = u.id_rol
                WHERE u.id_usuario = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }

        return User::fromArray($data);
    }

    /**
     * Actualizar la fecha y hora de último acceso del usuario.
     */
    public function updateLastAccess(int $userId): void
    {
        $sql = 'UPDATE usuarios
                SET ultimo_acceso = CURRENT_TIMESTAMP(6)
                WHERE id_usuario = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Actualizar el estado de un usuario (ej. para pruebas de desactivación o administración).
     */
    public function updateStatus(int $userId, string $status): void
    {
        $sql = 'UPDATE usuarios
                SET estado = :status
                WHERE id_usuario = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':status', strtoupper(trim($status)), PDO::PARAM_STR);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Buscar un usuario por su correo electrónico único (RF-03).
     */
    public function findByEmail(string $email): ?User
    {
        $sql = 'SELECT u.id_usuario, u.id_rol, u.nombre_usuario, u.correo, u.clave_hash,
                       u.nombres, u.apellidos, u.estado, u.ultimo_acceso,
                       r.nombre AS rol_nombre
                FROM usuarios u
                JOIN roles r ON r.id_rol = u.id_rol
                WHERE LOWER(TRIM(u.correo)) = LOWER(TRIM(:email))
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':email', trim($email), PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }

        return User::fromArray($data);
    }

    /**
     * Actualizar la clave hasheada de un usuario (RF-03).
     */
    public function updatePassword(int $userId, string $passwordHash): void
    {
        $sql = 'UPDATE usuarios
                SET clave_hash = :hash,
                    fecha_actualizacion = CURRENT_TIMESTAMP(6)
                WHERE id_usuario = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':hash', $passwordHash, PDO::PARAM_STR);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }
}

