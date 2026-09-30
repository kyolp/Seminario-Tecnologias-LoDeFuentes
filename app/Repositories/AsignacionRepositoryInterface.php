<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AsignacionDocente;

interface AsignacionRepositoryInterface
{
    /**
     * Listar asignaciones con filtros opcionales.
     *
     * @param array<string, mixed> $filters
     * @return array<AsignacionDocente>
     */
    public function findAll(array $filters = []): array;

    /**
     * Buscar una asignación por su clave primaria.
     */
    public function findById(int $id): ?AsignacionDocente;

    /**
     * Buscar la asignación activa actual para una sección específica.
     */
    public function findActiveBySeccion(int $idSeccion): ?AsignacionDocente;

    /**
     * Buscar asignaciones de un docente para un período escolar.
     *
     * @return array<AsignacionDocente>
     */
    public function findByDocenteAndPeriodo(int $idDocente, int $idPeriodo): array;

    /**
     * Verificar preventivamente si ya existe una asignación activa para la sección.
     */
    public function existsActiveForSeccion(int $idSeccion, ?int $excludeId = null): bool;

    /**
     * Verificar preventivamente si ya existe un registro para el mismo docente, sección, aula y fecha de inicio.
     */
    public function existsDuplicateInicio(int $idDocente, int $idSeccion, int $idAula, string $fechaInicio, ?int $excludeId = null): bool;

    /**
     * Crear un nuevo registro de asignación docente.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): int;

    /**
     * Actualizar campos permitidos de una asignación existente.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool;

    /**
     * Actualizar estado y fecha de finalización de una asignación.
     */
    public function updateEstado(int $id, string $estado, ?string $fechaFin = null): bool;

    /**
     * Conteo de asignaciones asociadas a un docente.
     */
    public function countByDocente(int $idDocente): int;

    /**
     * Conteo de asignaciones asociadas a una sección.
     */
    public function countBySeccion(int $idSeccion): int;

    /**
     * Conteo de asignaciones asociadas a un aula.
     */
    public function countByAula(int $idAula): int;
}
