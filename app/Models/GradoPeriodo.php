<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entidad Grado-Período.
 * Mapea las columnas de la tabla `grados_periodos` y relaciones asociadas.
 */
class GradoPeriodo
{
    public function __construct(
        public readonly int $id_grado_periodo,
        public readonly int $id_periodo_escolar,
        public readonly int $id_grado,
        public readonly int $habilitado,
        public readonly string $periodo_nombre = '',
        public readonly string $grado_codigo = '',
        public readonly string $grado_nombre = '',
        public readonly int $grado_orden = 0
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id_grado_periodo: (int) ($data['id_grado_periodo'] ?? 0),
            id_periodo_escolar: (int) ($data['id_periodo_escolar'] ?? 0),
            id_grado: (int) ($data['id_grado'] ?? 0),
            habilitado: (int) ($data['habilitado'] ?? 1),
            periodo_nombre: (string) ($data['periodo_nombre'] ?? ''),
            grado_codigo: (string) ($data['grado_codigo'] ?? ''),
            grado_nombre: (string) ($data['grado_nombre'] ?? ''),
            grado_orden: (int) ($data['grado_orden'] ?? 0)
        );
    }

    public function isHabilitado(): bool
    {
        return $this->habilitado === 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id_grado_periodo' => $this->id_grado_periodo,
            'id_periodo_escolar' => $this->id_periodo_escolar,
            'id_grado' => $this->id_grado,
            'habilitado' => $this->habilitado,
            'periodo_nombre' => $this->periodo_nombre,
            'grado_codigo' => $this->grado_codigo,
            'grado_nombre' => $this->grado_nombre,
            'grado_orden' => $this->grado_orden,
        ];
    }
}
