<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AsignacionDocente;
use App\Models\Docente;

/**
 * Contrato para el repositorio de persistencia del Portal Docente.
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 */
interface PortalDocenteRepositoryInterface
{
    /**
     * Obtener el perfil docente vinculado al identificador de usuario autenticado.
     */
    public function findDocenteByUsuarioId(int $usuarioId): ?Docente;

    /**
     * Obtener todas las asignaciones activas de un docente con su contexto académico completo.
     *
     * @return array<int, AsignacionDocente>
     */
    public function findActiveAsignacionesByDocente(int $idDocente): array;

    /**
     * Obtener una asignación específica verificando que pertenezca al docente.
     */
    public function findAsignacionByIdAndDocente(int $idAsignacion, int $idDocente): ?AsignacionDocente;

    /**
     * Verificar si una sección específica se encuentra asignada activamente al docente.
     */
    public function isSeccionAsignadaADocente(int $idDocente, int $idSeccion): bool;

    /**
     * Consultar los estudiantes con inscripción confirmada y vigente en una sección asignada al docente.
     * Aplica filtro autoritativo por id_docente a nivel de SQL para aislamiento horizontal.
     *
     * @return array<int, array{
     *     id_estudiante: int,
     *     codigo_estudiante: ?string,
     *     nombres: string,
     *     apellidos: string,
     *     fecha_nacimiento: ?string,
     *     codigo_confirmacion: ?string,
     *     fecha_confirmacion: ?string,
     *     inscripcion_estado: string
     * }>
     */
    public function findEstudiantesBySeccionDocente(int $idDocente, int $idSeccion): array;

    /**
     * Contar la cantidad de estudiantes válidamente inscritos en la sección del docente.
     */
    public function countEstudiantesBySeccionDocente(int $idDocente, int $idSeccion): int;
}
