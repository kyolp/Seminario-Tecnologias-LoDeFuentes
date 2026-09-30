<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entidad Inscripción Escolar.
 * Mapea la tabla `inscripciones` y provee información de contexto académico.
 */
class Inscripcion
{
    public function __construct(
        public readonly int $id_inscripcion,
        public readonly int $id_estudiante,
        public readonly int $id_encargado,
        public readonly int $id_seccion,
        public readonly ?string $codigo_confirmacion,
        public readonly string $estado,
        public readonly string $fecha_inscripcion,
        public readonly ?string $fecha_confirmacion = null,
        public readonly ?string $fecha_actualizacion = null,
        // Atributos de contexto cargados mediante JOIN
        public readonly ?string $estudiante_nombres = null,
        public readonly ?string $estudiante_apellidos = null,
        public readonly ?string $codigo_estudiante = null,
        public readonly ?string $estudiante_fecha_nacimiento = null,
        public readonly ?string $encargado_nombres = null,
        public readonly ?string $encargado_apellidos = null,
        public readonly ?string $encargado_telefono = null,
        public readonly ?string $encargado_correo = null,
        public readonly ?string $parentesco = null,
        public readonly ?string $seccion_codigo = null,
        public readonly ?string $seccion_nombre = null,
        public readonly ?int $seccion_capacidad = null,
        public readonly ?string $grado_nombre = null,
        public readonly ?string $periodo_nombre = null,
        public readonly ?int $id_periodo_escolar = null
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id_inscripcion: (int)($data['id_inscripcion'] ?? 0),
            id_estudiante: (int)($data['id_estudiante'] ?? 0),
            id_encargado: (int)($data['id_encargado'] ?? 0),
            id_seccion: (int)($data['id_seccion'] ?? 0),
            codigo_confirmacion: !empty($data['codigo_confirmacion']) ? (string)$data['codigo_confirmacion'] : null,
            estado: (string)($data['estado'] ?? 'PENDIENTE'),
            fecha_inscripcion: (string)($data['fecha_inscripcion'] ?? ''),
            fecha_confirmacion: !empty($data['fecha_confirmacion']) ? (string)$data['fecha_confirmacion'] : null,
            fecha_actualizacion: isset($data['fecha_actualizacion']) ? (string)$data['fecha_actualizacion'] : null,
            estudiante_nombres: isset($data['estudiante_nombres']) ? (string)$data['estudiante_nombres'] : null,
            estudiante_apellidos: isset($data['estudiante_apellidos']) ? (string)$data['estudiante_apellidos'] : null,
            codigo_estudiante: isset($data['codigo_estudiante']) ? (string)$data['codigo_estudiante'] : null,
            estudiante_fecha_nacimiento: isset($data['estudiante_fecha_nacimiento']) ? (string)$data['estudiante_fecha_nacimiento'] : null,
            encargado_nombres: isset($data['encargado_nombres']) ? (string)$data['encargado_nombres'] : null,
            encargado_apellidos: isset($data['encargado_apellidos']) ? (string)$data['encargado_apellidos'] : null,
            encargado_telefono: isset($data['encargado_telefono']) ? (string)$data['encargado_telefono'] : null,
            encargado_correo: isset($data['encargado_correo']) ? (string)$data['encargado_correo'] : null,
            parentesco: isset($data['parentesco']) ? (string)$data['parentesco'] : null,
            seccion_codigo: isset($data['seccion_codigo']) ? (string)$data['seccion_codigo'] : null,
            seccion_nombre: isset($data['seccion_nombre']) ? (string)$data['seccion_nombre'] : null,
            seccion_capacidad: isset($data['seccion_capacidad']) ? (int)$data['seccion_capacidad'] : null,
            grado_nombre: isset($data['grado_nombre']) ? (string)$data['grado_nombre'] : null,
            periodo_nombre: isset($data['periodo_nombre']) ? (string)$data['periodo_nombre'] : null,
            id_periodo_escolar: isset($data['id_periodo_escolar']) ? (int)$data['id_periodo_escolar'] : null
        );
    }

    public function isConfirmada(): bool
    {
        return strtoupper($this->estado) === 'CONFIRMADA';
    }

    public function isPendiente(): bool
    {
        return strtoupper($this->estado) === 'PENDIENTE';
    }

    public function isRechazada(): bool
    {
        return strtoupper($this->estado) === 'RECHAZADA';
    }

    public function isAnulada(): bool
    {
        return strtoupper($this->estado) === 'ANULADA';
    }

    public function getEstudianteNombreCompleto(): string
    {
        return trim(($this->estudiante_nombres ?? '') . ' ' . ($this->estudiante_apellidos ?? ''));
    }

    public function getEncargadoNombreCompleto(): string
    {
        return trim(($this->encargado_nombres ?? '') . ' ' . ($this->encargado_apellidos ?? ''));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id_inscripcion' => $this->id_inscripcion,
            'id_estudiante' => $this->id_estudiante,
            'id_encargado' => $this->id_encargado,
            'id_seccion' => $this->id_seccion,
            'codigo_confirmacion' => $this->codigo_confirmacion,
            'estado' => $this->estado,
            'fecha_inscripcion' => $this->fecha_inscripcion,
            'fecha_confirmacion' => $this->fecha_confirmacion,
            'estudiante_nombre_completo' => $this->getEstudianteNombreCompleto(),
            'codigo_estudiante' => $this->codigo_estudiante,
            'encargado_nombre_completo' => $this->getEncargadoNombreCompleto(),
            'encargado_telefono' => $this->encargado_telefono,
            'encargado_correo' => $this->encargado_correo,
            'parentesco' => $this->parentesco,
            'seccion_codigo' => $this->seccion_codigo,
            'seccion_nombre' => $this->seccion_nombre,
            'grado_nombre' => $this->grado_nombre,
            'periodo_nombre' => $this->periodo_nombre,
        ];
    }
}
