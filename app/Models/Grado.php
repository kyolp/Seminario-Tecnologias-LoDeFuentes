<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entidad Grado.
 * Mapea las columnas de la tabla `grados`.
 */
class Grado
{
    public function __construct(
        public readonly int $id_grado,
        public readonly string $codigo,
        public readonly string $nombre,
        public readonly int $orden,
        public readonly int $activo
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id_grado: (int) ($data['id_grado'] ?? 0),
            codigo: (string) ($data['codigo'] ?? ''),
            nombre: (string) ($data['nombre'] ?? ''),
            orden: (int) ($data['orden'] ?? 0),
            activo: (int) ($data['activo'] ?? 1)
        );
    }

    public function isActivo(): bool
    {
        return $this->activo === 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id_grado' => $this->id_grado,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'orden' => $this->orden,
            'activo' => $this->activo,
        ];
    }
}
