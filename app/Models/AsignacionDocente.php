<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entidad de Asignación Docente.
 * Mapea las columnas de la tabla `asignaciones_docentes` y su contexto académico.
 */
class AsignacionDocente
{
    public function __construct(
        public readonly int $id_asignacion_docente,
        public readonly int $id_docente,
        public readonly int $id_seccion,
        public readonly int $id_aula,
        public readonly int $id_usuario_creador,
        public readonly string $fecha_inicio,
        public readonly ?string $fecha_fin,
        public readonly string $estado,
        public readonly ?int $id_seccion_activa = null,
        public readonly ?string $fecha_creacion = null,
        public readonly ?string $fecha_actualizacion = null,
        public readonly string $docente_nombre = '',
        public readonly string $docente_codigo = '',
        public readonly string $docente_correo = '',
        public readonly string $seccion_codigo = '',
        public readonly string $seccion_nombre = '',
        public readonly int $seccion_capacidad = 0,
        public readonly int $id_grado = 0,
        public readonly string $grado_nombre = '',
        public readonly int $id_periodo_escolar = 0,
        public readonly string $periodo_nombre = '',
        public readonly string $aula_codigo = '',
        public readonly string $aula_nombre = '',
        public readonly string $creador_nombre = ''
    ) {
    }

    /**
     * Construir entidad a partir de arreglo asociativo PDO.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id_asignacion_docente: (int) ($data['id_asignacion_docente'] ?? 0),
            id_docente: (int) ($data['id_docente'] ?? 0),
            id_seccion: (int) ($data['id_seccion'] ?? 0),
            id_aula: (int) ($data['id_aula'] ?? 0),
            id_usuario_creador: (int) ($data['id_usuario_creador'] ?? 0),
            fecha_inicio: (string) ($data['fecha_inicio'] ?? ''),
            fecha_fin: !empty($data['fecha_fin']) ? (string) $data['fecha_fin'] : null,
            estado: (string) ($data['estado'] ?? 'ACTIVA'),
            id_seccion_activa: isset($data['id_seccion_activa']) && $data['id_seccion_activa'] !== null ? (int) $data['id_seccion_activa'] : null,
            fecha_creacion: isset($data['fecha_creacion']) ? (string) $data['fecha_creacion'] : null,
            fecha_actualizacion: isset($data['fecha_actualizacion']) ? (string) $data['fecha_actualizacion'] : null,
            docente_nombre: (string) ($data['docente_nombre'] ?? ''),
            docente_codigo: (string) ($data['docente_codigo'] ?? ''),
            docente_correo: (string) ($data['docente_correo'] ?? ''),
            seccion_codigo: (string) ($data['seccion_codigo'] ?? ''),
            seccion_nombre: (string) ($data['seccion_nombre'] ?? ''),
            seccion_capacidad: (int) ($data['seccion_capacidad'] ?? 0),
            id_grado: (int) ($data['id_grado'] ?? 0),
            grado_nombre: (string) ($data['grado_nombre'] ?? ''),
            id_periodo_escolar: (int) ($data['id_periodo_escolar'] ?? 0),
            periodo_nombre: (string) ($data['periodo_nombre'] ?? ''),
            aula_codigo: (string) ($data['aula_codigo'] ?? ''),
            aula_nombre: (string) ($data['aula_nombre'] ?? ''),
            creador_nombre: (string) ($data['creador_nombre'] ?? '')
        );
    }

    public function isActiva(): bool
    {
        return $this->estado === 'ACTIVA';
    }

    /**
     * Representación en arreglo asociativo para transporte o vistas.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id_asignacion_docente' => $this->id_asignacion_docente,
            'id_docente' => $this->id_docente,
            'id_seccion' => $this->id_seccion,
            'id_aula' => $this->id_aula,
            'id_usuario_creador' => $this->id_usuario_creador,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'estado' => $this->estado,
            'id_seccion_activa' => $this->id_seccion_activa,
            'fecha_creacion' => $this->fecha_creacion,
            'fecha_actualizacion' => $this->fecha_actualizacion,
            'docente_nombre' => $this->docente_nombre,
            'docente_codigo' => $this->docente_codigo,
            'docente_correo' => $this->docente_correo,
            'seccion_codigo' => $this->seccion_codigo,
            'seccion_nombre' => $this->seccion_nombre,
            'seccion_capacidad' => $this->seccion_capacidad,
            'id_grado' => $this->id_grado,
            'grado_nombre' => $this->grado_nombre,
            'id_periodo_escolar' => $this->id_periodo_escolar,
            'periodo_nombre' => $this->periodo_nombre,
            'aula_codigo' => $this->aula_codigo,
            'aula_nombre' => $this->aula_nombre,
            'creador_nombre' => $this->creador_nombre,
        ];
    }
}
