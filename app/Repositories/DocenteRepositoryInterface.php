<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Docente;

interface DocenteRepositoryInterface
{
    /**
     * @return array<int, Docente>
     */
    public function findAll(?string $search = null): array;

    public function findById(int $id): ?Docente;

    public function findByCodigo(string $codigo): ?Docente;

    public function findByUsuarioId(int $usuarioId): ?Docente;

    /**
     * @param array<string, mixed> $data
     */
    public function createDocente(int $idUsuario, ?string $codigoDocente, ?string $fechaIngreso, string $estado = 'ACTIVO'): int;

    /**
     * @param array<string, mixed> $data
     */
    public function updateDocente(int $idDocente, ?string $codigoDocente, ?string $fechaIngreso, string $estado): bool;

    public function updateEstado(int $idDocente, string $estado): bool;

    public function hasAsignaciones(int $idDocente): bool;

    public function delete(int $idDocente): bool;
}
