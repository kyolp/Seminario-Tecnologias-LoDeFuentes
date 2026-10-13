<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Contrato de persistencia para reportes institucionales de asistencia docente e inscripciones y cupos.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 12 — RF-29 y RF-30).
 */
interface ReporteRepositoryInterface
{
    /**
     * Consultar marcajes de asistencia docente según filtros autorizados (RF-29).
     *
     * @param array<string, mixed> $filtros Criterios: id_docente, fecha_inicio, fecha_fin, tipo, orden
     * @return array<int, array<string, mixed>> Lista de marcajes con datos de docente y ubicación institucional
     */
    public function findMarcajesAsistencia(array $filtros = []): array;

    /**
     * Consultar resumen de estado de cupos por sección según filtros (RF-30).
     * Reutiliza estrictamente la lógica de ocupación validada (inscripciones CONFIRMADAS y PENDIENTES).
     *
     * @param array<string, mixed> $filtros Criterios: id_periodo_escolar, id_grado, id_seccion
     * @return array<int, array<string, mixed>> Secciones con capacidad, ocupados, disponibles y estado de cupo
     */
    public function findResumenCuposPorSeccion(array $filtros = []): array;

    /**
     * Consultar estudiantes inscritos según filtros (RF-30).
     *
     * @param array<string, mixed> $filtros Criterios: id_periodo_escolar, id_grado, id_seccion, estado
     * @return array<int, array<string, mixed>> Inscripciones con datos de estudiante, grado y sección
     */
    public function findInscripcionesDetalle(array $filtros = []): array;
}
