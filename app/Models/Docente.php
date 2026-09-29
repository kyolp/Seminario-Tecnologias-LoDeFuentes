<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entidad Docente.
 * Mapea las columnas de la tabla `docentes` y los datos del usuario vinculado.
 */
class Docente
{
    public function __construct(
        public readonly int $id_docente,
        public readonly int $id_usuario,
        public readonly ?string $codigo_docente,
        public readonly ?string $fecha_ingreso,
        public readonly string $estado,
        public readonly string $nombre_usuario = '',
        public readonly string $correo = '',
        public readonly string $nombres = '',
        public readonly string $apellidos = '',
        public readonly string $usuario_estado = ''
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id_docente: (int) ($data['id_docente'] ?? 0),
            id_usuario: (int) ($data['id_usuario'] ?? 0),
            codigo_docente: isset($data['codigo_docente']) ? (string) $data['codigo_docente'] : null,
            fecha_ingreso: isset($data['fecha_ingreso']) ? (string) $data['fecha_ingreso'] : null,
            estado: (string) ($data['estado'] ?? 'ACTIVO'),
            nombre_usuario: (string) ($data['nombre_usuario'] ?? ''),
            correo: (string) ($data['correo'] ?? ''),
            nombres: (string) ($data['nombres'] ?? ''),
            apellidos: (string) ($data['apellidos'] ?? ''),
            usuario_estado: (string) ($data['usuario_estado'] ?? '')
        );
    }

    public function isActivo(): bool
    {
        return strtoupper($this->estado) === 'ACTIVO';
    }

    public function getNombreCompleto(): string
    {
        $fullName = trim($this->nombres . ' ' . $this->apellidos);
        return $fullName !== '' ? $fullName : ($this->codigo_docente ?? 'Docente #' . $this->id_docente);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id_docente' => $this->id_docente,
            'id_usuario' => $this->id_usuario,
            'codigo_docente' => $this->codigo_docente,
            'fecha_ingreso' => $this->fecha_ingreso,
            'estado' => $this->estado,
            'nombre_usuario' => $this->nombre_usuario,
            'correo' => $this->correo,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'usuario_estado' => $this->usuario_estado,
        ];
    }
}
