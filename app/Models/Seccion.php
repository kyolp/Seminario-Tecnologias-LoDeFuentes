<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entidad Sección.
 * Mapea las columnas de la tabla `secciones` y contexto de grado y período.
 */
class Seccion
{
    public function __construct(
        public readonly int $id_seccion,
        public readonly int $id_grado_periodo,
        public readonly string $codigo,
        public readonly ?string $nombre,
        public readonly int $capacidad,
        public readonly int $habilitada,
        public readonly string $grado_nombre = '',
        public readonly string $periodo_nombre = ''
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id_seccion: (int) ($data['id_seccion'] ?? 0),
            id_grado_periodo: (int) ($data['id_grado_periodo'] ?? 0),
            codigo: (string) ($data['codigo'] ?? ''),
            nombre: isset($data['nombre']) ? (string) $data['nombre'] : null,
            capacidad: (int) ($data['capacidad'] ?? 0),
            habilitada: (int) ($data['habilitada'] ?? 1),
            grado_nombre: (string) ($data['grado_nombre'] ?? ''),
            periodo_nombre: (string) ($data['periodo_nombre'] ?? '')
        );
    }

    public function isHabilitada(): bool
    {
        return $this->habilitada === 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id_seccion' => $this->id_seccion,
            'id_grado_periodo' => $this->id_grado_periodo,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'capacidad' => $this->capacidad,
            'habilitada' => $this->habilitada,
            'grado_nombre' => $this->grado_nombre,
            'periodo_nombre' => $this->periodo_nombre,
        ];
    }
}
