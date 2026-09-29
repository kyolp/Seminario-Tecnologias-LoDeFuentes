<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\GradoPeriodo;

interface GradoPeriodoRepositoryInterface
{
    /**
     * @return array<int, GradoPeriodo>
     */
    public function findAll(?int $periodoId = null): array;

    public function findById(int $id): ?GradoPeriodo;

    public function findByPeriodoAndGrado(int $periodoId, int $gradoId): ?GradoPeriodo;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int;

    public function updateHabilitado(int $id, int $habilitado): bool;

    public function hasSecciones(int $id): bool;

    public function delete(int $id): bool;
}
