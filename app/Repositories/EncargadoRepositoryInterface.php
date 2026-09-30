<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Encargado;
use App\Models\EstudianteEncargado;

interface EncargadoRepositoryInterface
{
    public function findById(int $id): ?Encargado;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int;

    /**
     * Buscar encargado existente por nombre y correo o teléfono para evitar duplicados innecesarios.
     */
    public function findMatching(string $nombres, string $apellidos, ?string $telefono, ?string $correo): ?Encargado;

    /**
     * Vincular estudiante con encargado en `estudiantes_encargados`.
     */
    public function linkEstudiante(int $idEstudiante, int $idEncargado, string $parentesco, bool $esPrincipal = true): int;

    /**
     * Verificar si existe vínculo formal entre estudiante y encargado.
     */
    public function hasVinculo(int $idEstudiante, int $idEncargado): bool;

    /**
     * Obtener el vínculo específico.
     */
    public function findVinculo(int $idEstudiante, int $idEncargado): ?EstudianteEncargado;
}
