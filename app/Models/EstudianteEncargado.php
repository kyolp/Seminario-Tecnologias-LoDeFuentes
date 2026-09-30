<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entidad EstudianteEncargado.
 * Mapea la relación N:M de la tabla `estudiantes_encargados`.
 */
class EstudianteEncargado
{
    public function __construct(
        public readonly int $id_estudiante_encargado,
        public readonly int $id_estudiante,
        public readonly int $id_encargado,
        public readonly string $parentesco,
        public readonly int $es_principal,
        public readonly ?string $fecha_creacion = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id_estudiante_encargado: (int)($data['id_estudiante_encargado'] ?? 0),
            id_estudiante: (int)($data['id_estudiante'] ?? 0),
            id_encargado: (int)($data['id_encargado'] ?? 0),
            parentesco: (string)($data['parentesco'] ?? ''),
            es_principal: (int)($data['es_principal'] ?? 0),
            fecha_creacion: isset($data['fecha_creacion']) ? (string)$data['fecha_creacion'] : null
        );
    }

    public function isPrincipal(): bool
    {
        return $this->es_principal === 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id_estudiante_encargado' => $this->id_estudiante_encargado,
            'id_estudiante' => $this->id_estudiante,
            'id_encargado' => $this->id_encargado,
            'parentesco' => $this->parentesco,
            'es_principal' => $this->es_principal,
            'fecha_creacion' => $this->fecha_creacion,
        ];
    }
}
