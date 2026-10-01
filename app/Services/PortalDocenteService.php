<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AsignacionDocente;
use App\Models\Docente;
use App\Repositories\PortalDocenteRepository;
use App\Repositories\PortalDocenteRepositoryInterface;

/**
 * Servicio de lógica de negocio para el Portal Docente.
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 */
class PortalDocenteService
{
    private PortalDocenteRepositoryInterface $repo;

    public function __construct(?PortalDocenteRepositoryInterface $repo = null)
    {
        $this->repo = $repo ?? new PortalDocenteRepository();
    }

    /**
     * Obtener el perfil docente activo asociado al usuario en sesión.
     */
    public function obtenerDocentePorUsuarioId(int $usuarioId): ?Docente
    {
        return $this->repo->findDocenteByUsuarioId($usuarioId);
    }

    /**
     * Consultar todas las asignaciones docentes activas para el docente autenticado.
     *
     * @return array<int, AsignacionDocente>
     */
    public function obtenerAsignacionesActivas(int $idDocente): array
    {
        return $this->repo->findActiveAsignacionesByDocente($idDocente);
    }

    /**
     * Comprobar si una sección está formalmente asignada al docente y en estado activo.
     */
    public function isSeccionAsignadaADocente(int $idDocente, int $idSeccion): bool
    {
        return $this->repo->isSeccionAsignadaADocente($idDocente, $idSeccion);
    }

    /**
     * Obtener el listado de estudiantes inscritos en una sección asignada al docente.
     * Aplica validación estricta de aislamiento horizontal antes de consultar.
     *
     * @return array{
     *     autorizado: bool,
     *     estudiantes: array<int, array<string, mixed>>,
     *     total: int,
     *     mensaje?: string
     * }
     */
    public function obtenerEstudiantesSeccion(int $idDocente, int $idSeccion): array
    {
        // 1. Barrera de aislamiento horizontal
        if (!$this->isSeccionAsignadaADocente($idDocente, $idSeccion)) {
            return [
                'autorizado' => false,
                'estudiantes' => [],
                'total' => 0,
                'mensaje' => 'No tiene autorización para consultar los estudiantes de la sección solicitada.',
            ];
        }

        // 2. Consulta autoritativa en repositorio
        $estudiantes = $this->repo->findEstudiantesBySeccionDocente($idDocente, $idSeccion);

        return [
            'autorizado' => true,
            'estudiantes' => $estudiantes,
            'total' => count($estudiantes),
        ];
    }

    /**
     * Orquestar el resumen completo de datos para el renderizado del Portal Docente.
     *
     * @return array{
     *     sin_asignacion: bool,
     *     asignaciones: array<int, AsignacionDocente>,
     *     asignacion_seleccionada: ?AsignacionDocente,
     *     estudiantes: array<int, array<string, mixed>>,
     *     total_estudiantes: int,
     *     autorizado: bool,
     *     mensaje_aviso?: string
     * }
     */
    public function obtenerResumenPortal(int $idDocente, ?int $idSeccionSolicitada = null): array
    {
        $asignaciones = $this->obtenerAsignacionesActivas($idDocente);

        // Caso: Docente sin asignaciones activas
        if (empty($asignaciones)) {
            return [
                'sin_asignacion' => true,
                'asignaciones' => [],
                'asignacion_seleccionada' => null,
                'estudiantes' => [],
                'total_estudiantes' => 0,
                'autorizado' => true,
                'mensaje_aviso' => 'Actualmente no tiene una sección asignada.',
            ];
        }

        // Determinar qué asignación mostrar
        $seleccionada = null;
        if ($idSeccionSolicitada !== null) {
            foreach ($asignaciones as $asig) {
                if ($asig->id_seccion === $idSeccionSolicitada) {
                    $seleccionada = $asig;
                    break;
                }
            }

            // Si la sección solicitada no pertenece a sus asignaciones activas: rechazo
            if ($seleccionada === null) {
                return [
                    'sin_asignacion' => false,
                    'asignaciones' => $asignaciones,
                    'asignacion_seleccionada' => null,
                    'estudiantes' => [],
                    'total_estudiantes' => 0,
                    'autorizado' => false,
                    'mensaje_aviso' => 'No tiene autorización para acceder a la sección solicitada.',
                ];
            }
        } else {
            // Por defecto, mostrar la primera asignación activa
            $seleccionada = $asignaciones[0];
        }

        $estudiantes = $this->repo->findEstudiantesBySeccionDocente($idDocente, $seleccionada->id_seccion);

        return [
            'sin_asignacion' => false,
            'asignaciones' => $asignaciones,
            'asignacion_seleccionada' => $seleccionada,
            'estudiantes' => $estudiantes,
            'total_estudiantes' => count($estudiantes),
            'autorizado' => true,
        ];
    }
}
