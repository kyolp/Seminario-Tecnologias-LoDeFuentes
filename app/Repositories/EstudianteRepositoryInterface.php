<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Estudiante;

interface EstudianteRepositoryInterface
{
    /**
     * @return array<int, Estudiante>
     */
    public function findAll(?string $search = null): array;

    public function findById(int $id): ?Estudiante;

    public function findByCodigo(string $codigo): ?Estudiante;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int;

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool;

    public function updateEstado(int $id, string $estado): bool;

    public function hasInscripciones(int $id): bool;

    public function delete(int $id): bool;
}
