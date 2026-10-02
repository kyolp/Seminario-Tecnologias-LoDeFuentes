<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Modelo de entidad para ConfiguracionUbicacion.
 * Representa una coordenada institucional autorizada y su radio de cobertura.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 8 — RF-18).
 */
class ConfiguracionUbicacion
{
    public int $id_configuracion_ubicacion;
    public int $id_usuario_creador;
    public string $descripcion;
    public float $latitud;
    public float $longitud;
    public float $radio_metros;
    public string $fecha_inicio;
    public ?string $fecha_fin;
    public bool $activa;
    public string $fecha_creacion;
    public ?string $usuario_creador_nombre = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id_configuracion_ubicacion = (int) ($data['id_configuracion_ubicacion'] ?? 0);
            $this->id_usuario_creador = (int) ($data['id_usuario_creador'] ?? 0);
            $this->descripcion = (string) ($data['descripcion'] ?? '');
            $this->latitud = (float) ($data['latitud'] ?? 0.0);
            $this->longitud = (float) ($data['longitud'] ?? 0.0);
            $this->radio_metros = (float) ($data['radio_metros'] ?? 0.0);
            $this->fecha_inicio = (string) ($data['fecha_inicio'] ?? '');
            $this->fecha_fin = isset($data['fecha_fin']) && $data['fecha_fin'] !== null ? (string) $data['fecha_fin'] : null;
            $this->activa = (bool) ($data['activa'] ?? true);
            $this->fecha_creacion = (string) ($data['fecha_creacion'] ?? '');
            $this->usuario_creador_nombre = isset($data['usuario_creador_nombre']) ? (string) $data['usuario_creador_nombre'] : null;
        }
    }

    public function isActiva(): bool
    {
        return $this->activa;
    }

    public function toArray(): array
    {
        return [
            'id_configuracion_ubicacion' => $this->id_configuracion_ubicacion,
            'id_usuario_creador' => $this->id_usuario_creador,
            'descripcion' => $this->descripcion,
            'latitud' => $this->latitud,
            'longitud' => $this->longitud,
            'radio_metros' => $this->radio_metros,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'activa' => $this->activa,
            'fecha_creacion' => $this->fecha_creacion,
            'usuario_creador_nombre' => $this->usuario_creador_nombre,
        ];
    }
}
