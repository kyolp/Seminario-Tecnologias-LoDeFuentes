<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Aula;

interface AulaRepositoryInterface
{
    /**
     * @return array<int, Aula>
     */
    public function findAll(): array;

    public function findById(int $id): ?Aula;

    public function findByCodigo(string $codigo): ?Aula;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int;

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool;

    public function updateActiva(int $id, int $activa): bool;

    public function hasAsignaciones(int $id): bool;

    public function delete(int $id): bool;
}
