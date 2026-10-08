<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entidad de dominio Notificacion.
 * Mapea las columnas de la tabla relacional `notificaciones` (MySQL 8.0).
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 10 — RF-26).
 */
class Notificacion
{
    public const TIPO_NUEVA_SOLICITUD = 'NUEVA_SOLICITUD';
    public const TIPO_CAMBIO_ESTADO = 'CAMBIO_ESTADO';

    public function __construct(
        public readonly int $id_notificacion,
        public readonly int $id_solicitud_permiso,
        public readonly int $id_usuario_destinatario,
        public readonly string $tipo,
        public readonly string $titulo,
        public readonly string $mensaje,
        public readonly string $fecha_creacion,
        public readonly ?string $fecha_lectura = null,
        // Propiedades de navegación relacional opcionales
        public readonly ?string $docente_nombre = null,
        public readonly ?string $fecha_solicitada = null,
        public readonly ?string $tipo_motivo = null,
        public readonly ?string $estado_solicitud = null
    ) {
    }

    /**
     * Construir instancia a partir de arreglo relacional obtenido por PDO.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id_notificacion: (int) ($data['id_notificacion'] ?? 0),
            id_solicitud_permiso: (int) ($data['id_solicitud_permiso'] ?? 0),
            id_usuario_destinatario: (int) ($data['id_usuario_destinatario'] ?? 0),
            tipo: (string) ($data['tipo'] ?? self::TIPO_NUEVA_SOLICITUD),
            titulo: (string) ($data['titulo'] ?? ''),
            mensaje: (string) ($data['mensaje'] ?? ''),
            fecha_creacion: (string) ($data['fecha_creacion'] ?? ''),
            fecha_lectura: isset($data['fecha_lectura']) && $data['fecha_lectura'] !== null ? (string) $data['fecha_lectura'] : null,
            docente_nombre: isset($data['docente_nombre']) ? (string) $data['docente_nombre'] : null,
            fecha_solicitada: isset($data['fecha_solicitada']) ? (string) $data['fecha_solicitada'] : null,
            tipo_motivo: isset($data['tipo_motivo']) ? (string) $data['tipo_motivo'] : null,
            estado_solicitud: isset($data['estado_solicitud']) ? (string) $data['estado_solicitud'] : null
        );
    }

    /**
     * Comprobar si la notificación ya fue leída.
     */
    public function isLeida(): bool
    {
        return $this->fecha_lectura !== null && trim($this->fecha_lectura) !== '';
    }

    /**
     * Comprobar si la notificación está pendiente de lectura.
     */
    public function isPendiente(): bool
    {
        return !$this->isLeida();
    }

    /**
     * Convertir entidad a arreglo serializable.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id_notificacion' => $this->id_notificacion,
            'id_solicitud_permiso' => $this->id_solicitud_permiso,
            'id_usuario_destinatario' => $this->id_usuario_destinatario,
            'tipo' => $this->tipo,
            'titulo' => $this->titulo,
            'mensaje' => $this->mensaje,
            'fecha_creacion' => $this->fecha_creacion,
            'fecha_lectura' => $this->fecha_lectura,
            'leida' => $this->isLeida(),
            'docente_nombre' => $this->docente_nombre,
            'fecha_solicitada' => $this->fecha_solicitada,
            'tipo_motivo' => $this->tipo_motivo,
            'estado_solicitud' => $this->estado_solicitud,
        ];
    }
}
