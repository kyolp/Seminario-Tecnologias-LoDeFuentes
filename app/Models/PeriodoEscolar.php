<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entidad Período Escolar.
 * Mapea las columnas de la tabla `periodos_escolares`.
 */
class PeriodoEscolar
{
    public function __construct(
        public readonly int $id_periodo_escolar,
        public readonly string $nombre,
        public readonly string $fecha_inicio,
        public readonly string $fecha_fin,
        public readonly string $estado
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id_periodo_escolar: (int) ($data['id_periodo_escolar'] ?? 0),
            nombre: (string) ($data['nombre'] ?? ''),
            fecha_inicio: (string) ($data['fecha_inicio'] ?? ''),
            fecha_fin: (string) ($data['fecha_fin'] ?? ''),
            estado: (string) ($data['estado'] ?? 'PLANIFICADO')
        );
    }

    public function isActivo(): bool
    {
        return strtoupper($this->estado) === 'ACTIVO';
    }

    public function isPlanificado(): bool
    {
        return strtoupper($this->estado) === 'PLANIFICADO';
    }

    public function isCerrado(): bool
    {
        return strtoupper($this->estado) === 'CERRADO';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id_periodo_escolar' => $this->id_periodo_escolar,
            'nombre' => $this->nombre,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'estado' => $this->estado,
        ];
    }
}
