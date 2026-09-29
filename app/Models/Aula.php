<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entidad Aula.
 * Mapea las columnas de la tabla `aulas`.
 */
class Aula
{
    public function __construct(
        public readonly int $id_aula,
        public readonly string $codigo,
        public readonly ?string $nombre,
        public readonly ?string $ubicacion_referencia,
        public readonly int $activa
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id_aula: (int) ($data['id_aula'] ?? 0),
            codigo: (string) ($data['codigo'] ?? ''),
            nombre: isset($data['nombre']) ? (string) $data['nombre'] : null,
            ubicacion_referencia: isset($data['ubicacion_referencia']) ? (string) $data['ubicacion_referencia'] : null,
            activa: (int) ($data['activa'] ?? 1)
        );
    }

    public function isActiva(): bool
    {
        return $this->activa === 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id_aula' => $this->id_aula,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'ubicacion_referencia' => $this->ubicacion_referencia,
            'activa' => $this->activa,
        ];
    }
}
