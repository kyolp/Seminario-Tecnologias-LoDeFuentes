<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

/**
 * Contrato de acceso a datos para la entidad Usuario.
 */
interface UserRepositoryInterface
{
    /**
     * Buscar un usuario por su nombre de usuario único.
     */
    public function findByUsername(string $username): ?User;

    /**
     * Buscar un usuario por su identificador primario.
     */
    public function findById(int $id): ?User;

    /**
     * Registrar la marca temporal del último acceso exitoso.
     */
    public function updateLastAccess(int $userId): void;

    /**
     * Actualizar el estado de un usuario (ACTIVO, INACTIVO, BLOQUEADO).
     */
    public function updateStatus(int $userId, string $status): void;

    /**
     * Buscar un usuario por su correo electrónico único (RF-03).
     */
    public function findByEmail(string $email): ?User;

    /**
     * Actualizar el hash de contraseña de un usuario (RF-03).
     */
    public function updatePassword(int $userId, string $passwordHash): void;
}
