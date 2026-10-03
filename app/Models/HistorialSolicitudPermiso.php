<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Modelo de entidad para Historial de Solicitud de Permiso Docente (RF-27, RF-28, RNF-06, DT-56).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 */
class HistorialSolicitudPermiso
{
    public int $id_historial_permiso;
    public int $id_solicitud_permiso;
    public int $id_usuario_actor;
    public ?string $estado_anterior;
    public string $estado_nuevo;
    public ?string $comentario;
    public string $fecha_cambio;

    public ?User $usuarioActor = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->id_historial_permiso = (int) ($data['id_historial_permiso'] ?? 0);
        $this->id_solicitud_permiso = (int) ($data['id_solicitud_permiso'] ?? 0);
        $this->id_usuario_actor = (int) ($data['id_usuario_actor'] ?? 0);
        $this->estado_anterior = isset($data['estado_anterior']) && $data['estado_anterior'] !== null
            ? (string) $data['estado_anterior']
            : null;
        $this->estado_nuevo = (string) ($data['estado_nuevo'] ?? SolicitudPermiso::ESTADO_PENDIENTE);
        $this->comentario = isset($data['comentario']) && $data['comentario'] !== null
            ? (string) $data['comentario']
            : null;
        $this->fecha_cambio = (string) ($data['fecha_cambio'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id_historial_permiso' => $this->id_historial_permiso,
            'id_solicitud_permiso' => $this->id_solicitud_permiso,
            'id_usuario_actor' => $this->id_usuario_actor,
            'estado_anterior' => $this->estado_anterior,
            'estado_nuevo' => $this->estado_nuevo,
            'comentario' => $this->comentario,
            'fecha_cambio' => $this->fecha_cambio,
            'actor_nombre' => $this->usuarioActor?->nombre_usuario,
            'actor_rol' => $this->usuarioActor?->rol_nombre,
        ];
    }
}
