<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\DocenteRepository;
use App\Repositories\DocenteRepositoryInterface;
use App\Repositories\GradoRepository;
use App\Repositories\GradoRepositoryInterface;
use App\Repositories\PeriodoRepository;
use App\Repositories\PeriodoRepositoryInterface;
use App\Repositories\SeccionRepository;
use App\Repositories\SeccionRepositoryInterface;
use App\Services\AuthService;
use App\Services\ReporteService;
use InvalidArgumentException;
use Throwable;

/**
 * Controlador para la visualización y consulta de reportes administrativos (RF-29 y RF-30).
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 12).
 */
class AdminReporteController extends BaseController
{
    private AuthService $auth;
    private ReporteService $reporteService;
    private PeriodoRepositoryInterface $periodoRepo;
    private DocenteRepositoryInterface $docenteRepo;
    private GradoRepositoryInterface $gradoRepo;
    private SeccionRepositoryInterface $seccionRepo;

    public function __construct(
        ?AuthService $auth = null,
        ?ReporteService $reporteService = null,
        ?PeriodoRepositoryInterface $periodoRepo = null,
        ?DocenteRepositoryInterface $docenteRepo = null,
        ?GradoRepositoryInterface $gradoRepo = null,
        ?SeccionRepositoryInterface $seccionRepo = null
    ) {
        $this->auth = $auth ?? new AuthService();
        $this->reporteService = $reporteService ?? new ReporteService();
        $this->periodoRepo = $periodoRepo ?? new PeriodoRepository();
        $this->docenteRepo = $docenteRepo ?? new DocenteRepository();
        $this->gradoRepo = $gradoRepo ?? new GradoRepository();
        $this->seccionRepo = $seccionRepo ?? new SeccionRepository();
    }

    /**
     * Centro de control de reportes administrativos.
     */
    public function index(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        if (!$user->hasRole('ADMINISTRADOR') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'No posee los privilegios suficientes para acceder al módulo de reportes.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'ADMINISTRADOR',
            ], 'main'), 403);
        }

        return $this->render('admin.reportes.index', [
            'title' => 'Reportes Institucionales — Administración',
            'user' => $user,
        ]);
    }

    /**
     * Reporte de Asistencia Docente por docente y período (RF-29).
     */
    public function asistencia(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        if (!$user->hasRole('ADMINISTRADOR') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'No posee los privilegios suficientes para consultar el reporte de asistencia.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'ADMINISTRADOR',
            ], 'main'), 403);
        }

        $params = $request->query();
        $mensajeError = null;
        $resultado = [
            'filtros' => [],
            'marcajes' => [],
            'resumen' => [
                'total_marcajes' => 0,
                'total_entradas' => 0,
                'total_salidas' => 0,
                'docentes_registrados' => 0,
            ],
        ];

        try {
            $resultado = $this->reporteService->generarReporteAsistencia($params);
        } catch (InvalidArgumentException $e) {
            $mensajeError = $e->getMessage();
        } catch (Throwable $e) {
            $mensajeError = 'Ocurrió un error inesperado al procesar la consulta del reporte de asistencia.';
        }

        $periodos = $this->periodoRepo->findAll();
        $docentes = $this->docenteRepo->findAll();

        return $this->render('admin.reportes.asistencia', [
            'title' => 'Reporte de Asistencia Docente (RF-29)',
            'user' => $user,
            'periodos' => $periodos,
            'docentes' => $docentes,
            'filtros' => $params,
            'marcajes' => $resultado['marcajes'],
            'resumen' => $resultado['resumen'],
            'mensajeError' => $mensajeError,
        ]);
    }

    /**
     * Reporte y Consulta de Inscripciones y Cupos por Grado y Sección (RF-30).
     */
    public function inscripciones(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        if (!$user->hasRole('ADMINISTRADOR') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'No posee los privilegios suficientes para consultar el reporte de inscripciones.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'ADMINISTRADOR',
            ], 'main'), 403);
        }

        $params = $request->query();
        $mensajeError = null;
        $resultado = [
            'filtros' => [],
            'secciones_cupos' => [],
            'estudiantes' => [],
            'resumen' => [
                'total_secciones' => 0,
                'capacidad_total' => 0,
                'cupos_ocupados_total' => 0,
                'cupos_disponibles_total' => 0,
                'secciones_llenas' => 0,
                'secciones_disponibles' => 0,
                'total_estudiantes_inscritos' => 0,
                'total_confirmadas' => 0,
                'total_pendientes' => 0,
                'total_anuladas' => 0,
            ],
        ];

        try {
            $resultado = $this->reporteService->generarReporteInscripciones($params);
        } catch (InvalidArgumentException $e) {
            $mensajeError = $e->getMessage();
        } catch (Throwable $e) {
            $mensajeError = 'Ocurrió un error inesperado al procesar la consulta del reporte de inscripciones.';
        }

        $periodos = $this->periodoRepo->findAll();
        $grados = $this->gradoRepo->findAll();
        $secciones = $this->seccionRepo->findAll();

        return $this->render('admin.reportes.inscripciones', [
            'title' => 'Consulta de Inscripciones y Cupos (RF-30)',
            'user' => $user,
            'periodos' => $periodos,
            'grados' => $grados,
            'secciones' => $secciones,
            'filtros' => $params,
            'seccionesCupos' => $resultado['secciones_cupos'],
            'estudiantes' => $resultado['estudiantes'],
            'resumen' => $resultado['resumen'],
            'mensajeError' => $mensajeError,
        ]);
    }
}
