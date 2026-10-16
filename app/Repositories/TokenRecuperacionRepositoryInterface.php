<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TokenRecuperacion;

/**
 * Contrato de acceso a datos para la persistencia de tokens de recuperación (RF-03).
 */
interface TokenRecuperacionRepositoryInterface
{
    /**
     * Crear y persistir un nuevo token de recuperación.
     */
    public function crear(int $idUsuario, string $tokenHash, string $fechaExpiracion): TokenRecuperacion;

    /**
     * Buscar un token por su hash SHA-256 único.
     */
    public function findByTokenHash(string $tokenHash): ?TokenRecuperacion;

    /**
     * Buscar un token por su hash con bloqueo pesimista exclusivo (SELECT ... FOR UPDATE).
     */
    public function findForUpdate(string $tokenHash): ?TokenRecuperacion;

    /**
     * Buscar un token por su clave primaria ID.
     */
    public function findById(int $idToken): ?TokenRecuperacion;

    /**
     * Marcar un token como USADO registrando la fecha y hora exacta de consumo.
     */
    public function marcarComoUsado(int $idToken): void;

    /**
     * Revocar todos los tokens activos de un usuario al generar uno nuevo o tras un cambio de clave.
     */
    public function revocarTokensActivosDeUsuario(int $idUsuario): void;

    /**
     * Eliminar físicamente los tokens de un usuario (para limpieza controlada en suites de prueba).
     */
    public function limpiarTokensUsuario(int $idUsuario): void;
}
