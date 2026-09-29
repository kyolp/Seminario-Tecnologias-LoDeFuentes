<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entidad Estudiante.
 * Mapea las columnas de la tabla `estudiantes`.
 */
class Estudiante
{
    public function __construct(
        public readonly int $id_estudiante,
        public readonly ?string $codigo_estudiante,
        public readonly string $nombres,
        public readonly string $apellidos,
        public readonly ?string $fecha_nacimiento,
        public readonly string $estado
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id_estudiante: (int) ($data['id_estudiante'] ?? 0),
            codigo_estudiante: isset($data['codigo_estudiante']) ? (string) $data['codigo_estudiante'] : null,
            nombres: (string) ($data['nombres'] ?? ''),
            apellidos: (string) ($data['apellidos'] ?? ''),
            fecha_nacimiento: isset($data['fecha_nacimiento']) ? (string) $data['fecha_nacimiento'] : null,
            estado: (string) ($data['estado'] ?? 'ACTIVO')
        );
    }

    public function isActivo(): bool
    {
        return strtoupper($this->estado) === 'ACTIVO';
    }

    public function getNombreCompleto(): string
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id_estudiante' => $this->id_estudiante,
            'codigo_estudiante' => $this->codigo_estudiante,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'estado' => $this->estado,
        ];
    }
}
