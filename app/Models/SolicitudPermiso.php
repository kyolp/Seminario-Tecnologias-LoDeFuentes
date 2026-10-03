<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Modelo de entidad para Solicitud de Permiso Docente (RF-24, RF-27, RF-28).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 */
class SolicitudPermiso
{
    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_APROBADO = 'APROBADO';
    public const ESTADO_RECHAZADO = 'RECHAZADO';

    public int $id_solicitud_permiso;
    public int $id_docente;
    public string $fecha_solicitada;
    public string $tipo_motivo;
    public string $descripcion;
    public string $estado;
    public string $fecha_solicitud;
    public string $fecha_actualizacion;

    // Relaciones opcionales cargadas
    public ?Docente $docente = null;
    public ?DocumentoPermiso $documento = null;
    /** @var array<int, HistorialSolicitudPermiso> */
    public array $historial = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->id_solicitud_permiso = (int) ($data['id_solicitud_permiso'] ?? 0);
        $this->id_docente = (int) ($data['id_docente'] ?? 0);
        $this->fecha_solicitada = (string) ($data['fecha_solicitada'] ?? '');
        $this->tipo_motivo = (string) ($data['tipo_motivo'] ?? '');
        $this->descripcion = (string) ($data['descripcion'] ?? '');
        $this->estado = (string) ($data['estado'] ?? self::ESTADO_PENDIENTE);
        $this->fecha_solicitud = (string) ($data['fecha_solicitud'] ?? '');
        $this->fecha_actualizacion = (string) ($data['fecha_actualizacion'] ?? '');
    }

    public function isPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    public function isAprobado(): bool
    {
        return $this->estado === self::ESTADO_APROBADO;
    }

    public function isRechazado(): bool
    {
        return $this->estado === self::ESTADO_RECHAZADO;
    }

    public function puedeDecidirse(): bool
    {
        return $this->isPendiente();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id_solicitud_permiso' => $this->id_solicitud_permiso,
            'id_docente' => $this->id_docente,
            'fecha_solicitada' => $this->fecha_solicitada,
            'tipo_motivo' => $this->tipo_motivo,
            'descripcion' => $this->descripcion,
            'estado' => $this->estado,
            'fecha_solicitud' => $this->fecha_solicitud,
            'fecha_actualizacion' => $this->fecha_actualizacion,
            'tiene_documento' => $this->documento !== null,
        ];
    }
}
