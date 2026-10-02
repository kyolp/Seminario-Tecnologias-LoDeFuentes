<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Modelo de entidad para MarcajeAsistencia.
 * Representa un evento inmutable de asistencia docente (ENTRADA o SALIDA).
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 8 — RF-20, RF-21, RF-22, RF-23).
 */
class MarcajeAsistencia
{
    public int $id_marcaje_asistencia;
    public int $id_docente;
    public int $id_usuario;
    public int $id_configuracion_ubicacion;
    public string $fecha_hora;
    public string $tipo;
    public float $latitud_dispositivo;
    public float $longitud_dispositivo;
    public ?float $precision_metros;
    public float $distancia_metros;
    public int $dentro_radio;
    public ?string $docente_nombre = null;
    public ?string $codigo_docente = null;
    public ?string $configuracion_descripcion = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id_marcaje_asistencia = (int) ($data['id_marcaje_asistencia'] ?? 0);
            $this->id_docente = (int) ($data['id_docente'] ?? 0);
            $this->id_usuario = (int) ($data['id_usuario'] ?? 0);
            $this->id_configuracion_ubicacion = (int) ($data['id_configuracion_ubicacion'] ?? 0);
            $this->fecha_hora = (string) ($data['fecha_hora'] ?? '');
            $this->tipo = strtoupper((string) ($data['tipo'] ?? 'ENTRADA'));
            $this->latitud_dispositivo = (float) ($data['latitud_dispositivo'] ?? 0.0);
            $this->longitud_dispositivo = (float) ($data['longitud_dispositivo'] ?? 0.0);
            $this->precision_metros = isset($data['precision_metros']) && $data['precision_metros'] !== null && $data['precision_metros'] !== ''
                ? (float) $data['precision_metros']
                : null;
            $this->distancia_metros = (float) ($data['distancia_metros'] ?? 0.0);
            $this->dentro_radio = (int) ($data['dentro_radio'] ?? 1);
            $this->docente_nombre = isset($data['docente_nombre']) ? (string) $data['docente_nombre'] : null;
            $this->codigo_docente = isset($data['codigo_docente']) ? (string) $data['codigo_docente'] : null;
            $this->configuracion_descripcion = isset($data['configuracion_descripcion']) ? (string) $data['configuracion_descripcion'] : null;
        }
    }

    public function isEntrada(): bool
    {
        return $this->tipo === 'ENTRADA';
    }

    public function isSalida(): bool
    {
        return $this->tipo === 'SALIDA';
    }

    public function toArray(): array
    {
        return [
            'id_marcaje_asistencia' => $this->id_marcaje_asistencia,
            'id_docente' => $this->id_docente,
            'id_usuario' => $this->id_usuario,
            'id_configuracion_ubicacion' => $this->id_configuracion_ubicacion,
            'fecha_hora' => $this->fecha_hora,
            'tipo' => $this->tipo,
            'latitud_dispositivo' => $this->latitud_dispositivo,
            'longitud_dispositivo' => $this->longitud_dispositivo,
            'precision_metros' => $this->precision_metros,
            'distancia_metros' => $this->distancia_metros,
            'dentro_radio' => $this->dentro_radio,
            'docente_nombre' => $this->docente_nombre,
            'codigo_docente' => $this->codigo_docente,
            'configuracion_descripcion' => $this->configuracion_descripcion,
        ];
    }
}
