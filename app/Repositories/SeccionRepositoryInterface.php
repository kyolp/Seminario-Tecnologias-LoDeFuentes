<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Seccion;

interface SeccionRepositoryInterface
{
    /**
     * @return array<int, Seccion>
     */
    public function findAll(?int $gradoPeriodoId = null): array;

    public function findById(int $id): ?Seccion;

    public function findByGradoPeriodoAndCodigo(int $gradoPeriodoId, string $codigo): ?Seccion;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int;

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool;

    public function updateHabilitada(int $id, int $habilitada): bool;

    public function hasAsignaciones(int $id): bool;

    public function hasInscripciones(int $id): bool;

    public function delete(int $id): bool;
}
