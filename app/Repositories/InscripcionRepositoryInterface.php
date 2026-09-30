<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Inscripcion;

interface InscripcionRepositoryInterface
{
    public function findById(int $id): ?Inscripcion;

    public function findByCodigoConfirmacion(string $codigo): ?Inscripcion;

    public function findVigenteByEstudianteAndSeccion(int $idEstudiante, int $idSeccion): ?Inscripcion;

    /**
     * Contar cupos ocupados (inscripciones vigentes: CONFIRMADA o PENDIENTE) en una sección.
     */
    public function countOcupacionBySeccion(int $idSeccion): int;

    /**
     * Bloquear pesimistamente la fila de la sección con SELECT ... FOR UPDATE.
     *
     * @return array{id_seccion: int, id_grado_periodo: int, codigo: string, nombre: ?string, capacidad: int, habilitada: int}|null
     */
    public function lockSeccionForUpdate(int $idSeccion): ?array;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int;

    public function existsCodigo(string $codigo): bool;

    /**
     * @param array<string, mixed> $filters
     * @return array<Inscripcion>
     */
    public function findAll(array $filters = []): array;
}
