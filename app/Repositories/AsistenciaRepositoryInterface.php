<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\MarcajeAsistencia;

/**
 * Contrato de persistencia para el registro y consulta de marcajes de asistencia.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 8 — RF-20 a RF-23).
 */
interface AsistenciaRepositoryInterface
{
    /**
     * Registrar un nuevo marcaje de asistencia inmutable.
     * Siempre se inserta dentro_radio = 1 según la restricción física de base de datos.
     */
    public function crearMarcaje(
        int $idDocente,
        int $idUsuario,
        int $idConfiguracion,
        string $tipo,
        float $latitud,
        float $longitud,
        ?float $precision,
        float $distancia
    ): MarcajeAsistencia;

    /**
     * Obtener el último marcaje registrado por el docente.
     */
    public function findUltimoMarcajeDocente(int $idDocente): ?MarcajeAsistencia;

    /**
     * Consultar el historial de marcajes de un docente específico (Aislamiento Horizontal).
     *
     * @return array<int, MarcajeAsistencia>
     */
    public function findMarcajesByDocente(int $idDocente, int $limit = 50): array;

    /**
     * Buscar un marcaje por su ID verificando pertenencia estricta al docente.
     */
    public function findMarcajeByIdAndDocente(int $idMarcaje, int $idDocente): ?MarcajeAsistencia;

    /**
     * Contar el total de marcajes de un docente.
     */
    public function countMarcajesByDocente(int $idDocente): int;

    public function obtenerEstadoDelDia(int $idDocente): array;
}
