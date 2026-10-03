<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Modelo de entidad para Documento de Soporte de Permiso Docente (RF-25, DT-58, DT-59, DT-60).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 */
class DocumentoPermiso
{
    public int $id_documento_permiso;
    public int $id_solicitud_permiso;
    public string $nombre_original;
    public string $ruta_almacenamiento;
    public string $tipo_mime;
    public int $tamano_bytes;
    public string $fecha_carga;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->id_documento_permiso = (int) ($data['id_documento_permiso'] ?? 0);
        $this->id_solicitud_permiso = (int) ($data['id_solicitud_permiso'] ?? 0);
        $this->nombre_original = (string) ($data['nombre_original'] ?? '');
        $this->ruta_almacenamiento = (string) ($data['ruta_almacenamiento'] ?? '');
        $this->tipo_mime = (string) ($data['tipo_mime'] ?? '');
        $this->tamano_bytes = (int) ($data['tamano_bytes'] ?? 0);
        $this->fecha_carga = (string) ($data['fecha_carga'] ?? '');
    }

    public function getTamanoFormateado(): string
    {
        if ($this->tamano_bytes >= 1048576) {
            return number_format($this->tamano_bytes / 1048576, 2) . ' MB';
        }
        if ($this->tamano_bytes >= 1024) {
            return number_format($this->tamano_bytes / 1024, 2) . ' KB';
        }
        return $this->tamano_bytes . ' B';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id_documento_permiso' => $this->id_documento_permiso,
            'id_solicitud_permiso' => $this->id_solicitud_permiso,
            'nombre_original' => $this->nombre_original,
            'ruta_almacenamiento' => $this->ruta_almacenamiento,
            'tipo_mime' => $this->tipo_mime,
            'tamano_bytes' => $this->tamano_bytes,
            'tamano_formateado' => $this->getTamanoFormateado(),
            'fecha_carga' => $this->fecha_carga,
        ];
    }
}
