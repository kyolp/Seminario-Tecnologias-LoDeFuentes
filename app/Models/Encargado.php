<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entidad Encargado (Padre, Madre o Tutor Legal).
 * Mapea las columnas de la tabla `encargados`.
 */
class Encargado
{
    public function __construct(
        public readonly int $id_encargado,
        public readonly string $nombres,
        public readonly string $apellidos,
        public readonly ?string $telefono,
        public readonly ?string $correo,
        public readonly ?string $fecha_creacion = null,
        public readonly ?string $fecha_actualizacion = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id_encargado: (int)($data['id_encargado'] ?? 0),
            nombres: (string)($data['nombres'] ?? ''),
            apellidos: (string)($data['apellidos'] ?? ''),
            telefono: !empty($data['telefono']) ? (string)$data['telefono'] : null,
            correo: !empty($data['correo']) ? (string)$data['correo'] : null,
            fecha_creacion: isset($data['fecha_creacion']) ? (string)$data['fecha_creacion'] : null,
            fecha_actualizacion: isset($data['fecha_actualizacion']) ? (string)$data['fecha_actualizacion'] : null
        );
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
            'id_encargado' => $this->id_encargado,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'telefono' => $this->telefono,
            'correo' => $this->correo,
            'fecha_creacion' => $this->fecha_creacion,
            'fecha_actualizacion' => $this->fecha_actualizacion,
        ];
    }
}
