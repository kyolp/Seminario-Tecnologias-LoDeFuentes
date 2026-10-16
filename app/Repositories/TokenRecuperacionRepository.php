<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\TokenRecuperacion;
use PDO;

/**
 * Repositorio de tokens de recuperación mediante PDO / MySQL con consultas preparadas nativas (RF-03).
 */
class TokenRecuperacionRepository implements TokenRecuperacionRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function crear(int $idUsuario, string $tokenHash, string $fechaExpiracion): TokenRecuperacion
    {
        $sql = 'INSERT INTO tokens_recuperacion (id_usuario, token_hash, estado, fecha_creacion, fecha_expiracion)
                VALUES (:id_usuario, :token_hash, "ACTIVO", CURRENT_TIMESTAMP(6), :fecha_expiracion)';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':token_hash', trim($tokenHash), PDO::PARAM_STR);
        $stmt->bindValue(':fecha_expiracion', $fechaExpiracion, PDO::PARAM_STR);
        $stmt->execute();

        $id = (int) $this->db->lastInsertId();

        $creado = $this->findById($id);
        if ($creado === null) {
            throw new \RuntimeException('Error al recuperar el token de recuperación recién creado.');
        }

        return $creado;
    }

    public function findByTokenHash(string $tokenHash): ?TokenRecuperacion
    {
        $sql = 'SELECT id_token_recuperacion, id_usuario, token_hash, estado,
                       fecha_creacion, fecha_expiracion, fecha_uso
                FROM tokens_recuperacion
                WHERE token_hash = :hash
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':hash', trim($tokenHash), PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }

        return TokenRecuperacion::fromArray($data);
    }

    public function findForUpdate(string $tokenHash): ?TokenRecuperacion
    {
        $sql = 'SELECT id_token_recuperacion, id_usuario, token_hash, estado,
                       fecha_creacion, fecha_expiracion, fecha_uso
                FROM tokens_recuperacion
                WHERE token_hash = :hash
                LIMIT 1
                FOR UPDATE';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':hash', trim($tokenHash), PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }

        return TokenRecuperacion::fromArray($data);
    }

    public function findById(int $idToken): ?TokenRecuperacion
    {
        $sql = 'SELECT id_token_recuperacion, id_usuario, token_hash, estado,
                       fecha_creacion, fecha_expiracion, fecha_uso
                FROM tokens_recuperacion
                WHERE id_token_recuperacion = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $idToken, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }

        return TokenRecuperacion::fromArray($data);
    }

    public function marcarComoUsado(int $idToken): void
    {
        $sql = 'UPDATE tokens_recuperacion
                SET estado = "USADO",
                    fecha_uso = CURRENT_TIMESTAMP(6)
                WHERE id_token_recuperacion = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $idToken, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function revocarTokensActivosDeUsuario(int $idUsuario): void
    {
        $sql = 'UPDATE tokens_recuperacion
                SET estado = "REVOCADO"
                WHERE id_usuario = :id_usuario AND estado = "ACTIVO"';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function limpiarTokensUsuario(int $idUsuario): void
    {
        $sql = 'DELETE FROM tokens_recuperacion WHERE id_usuario = :id_usuario';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
    }
}
