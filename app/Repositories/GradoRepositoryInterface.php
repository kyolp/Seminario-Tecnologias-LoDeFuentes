<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Grado;

interface GradoRepositoryInterface
{
    /**
     * @return array<int, Grado>
     */
    public function findAll(): array;

    public function findById(int $id): ?Grado;

    public function findByCodigo(string $codigo): ?Grado;

    public function findByNombre(string $nombre): ?Grado;

    public function findByOrden(int $orden): ?Grado;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int;

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool;

    public function updateActivo(int $id, int $activo): bool;

    public function hasGradosPeriodos(int $id): bool;

    public function delete(int $id): bool;
}
