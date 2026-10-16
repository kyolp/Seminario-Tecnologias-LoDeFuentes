<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Entidad de Token de Recuperación de Contraseña — Fase 13 (RF-03).
 * Mapea la tabla relacional `tokens_recuperacion` diseñada en Fase 1.
 */
class TokenRecuperacion
{
    public const ESTADO_ACTIVO = 'ACTIVO';
    public const ESTADO_USADO = 'USADO';
    public const ESTADO_REVOCADO = 'REVOCADO';

    public const VIGENCIA_MINUTOS = 60;

    public function __construct(
        public readonly int $id_token_recuperacion,
        public readonly int $id_usuario,
        public readonly string $token_hash,
        public readonly string $estado,
        public readonly string $fecha_creacion,
        public readonly string $fecha_expiracion,
        public readonly ?string $fecha_uso = null
    ) {
    }

    /**
     * Construir entidad a partir de arreglo relacional obtenido por PDO.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id_token_recuperacion: (int) ($data['id_token_recuperacion'] ?? 0),
            id_usuario: (int) ($data['id_usuario'] ?? 0),
            token_hash: (string) ($data['token_hash'] ?? ''),
            estado: strtoupper((string) ($data['estado'] ?? self::ESTADO_ACTIVO)),
            fecha_creacion: (string) ($data['fecha_creacion'] ?? ''),
            fecha_expiracion: (string) ($data['fecha_expiracion'] ?? ''),
            fecha_uso: isset($data['fecha_uso']) && $data['fecha_uso'] !== null ? (string) $data['fecha_uso'] : null
        );
    }

    public function isActivo(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO;
    }

    public function isUsado(): bool
    {
        return $this->estado === self::ESTADO_USADO;
    }

    public function isRevocado(): bool
    {
        return $this->estado === self::ESTADO_REVOCADO;
    }

    public function isExpirado(?DateTimeInterface $now = null): bool
    {
        $fechaActual = $now ?? new DateTimeImmutable();
        $expiracion = DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $this->fecha_expiracion);

        if ($expiracion === false) {
            $expiracion = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $this->fecha_expiracion);
        }

        if ($expiracion === false) {
            // Ante fallo de parseo, se considera expirado por seguridad
            return true;
        }

        return $fechaActual >= $expiracion;
    }

    public function isValido(?DateTimeInterface $now = null): bool
    {
        return $this->isActivo() && !$this->isExpirado($now);
    }

    /**
     * Convertir a arreglo seguro omitiendo datos sensibles.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id_token_recuperacion' => $this->id_token_recuperacion,
            'id_usuario' => $this->id_usuario,
            'estado' => $this->estado,
            'fecha_creacion' => $this->fecha_creacion,
            'fecha_expiracion' => $this->fecha_expiracion,
            'fecha_uso' => $this->fecha_uso,
        ];
    }
}
