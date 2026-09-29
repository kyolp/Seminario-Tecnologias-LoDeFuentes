<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PeriodoEscolar;

interface PeriodoRepositoryInterface
{
    /**
     * @return array<int, PeriodoEscolar>
     */
    public function findAll(): array;

    public function findById(int $id): ?PeriodoEscolar;

    public function findByNombre(string $nombre): ?PeriodoEscolar;

    public function findActivo(): ?PeriodoEscolar;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int;

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool;

    public function updateEstado(int $id, string $estado): bool;

    public function hasGradosPeriodos(int $id): bool;

    public function delete(int $id): bool;
}
