<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AsignacionService;
use App\Services\AuthService;

class AdminAsignacionController extends BaseController
{
    private AsignacionService $asignacionService;
    private AuthService $auth;

    public function __construct(
        ?AsignacionService $asignacionService = null,
        ?AuthService $auth = null
    ) {
        $this->asignacionService = $asignacionService ?? new AsignacionService();
        $this->auth = $auth ?? new AuthService();
    }

    public function index(Request $request): Response
    {
        $filters = [
            'periodo_id' => (int)$request->query('periodo_id', 0),
            'docente_id' => (int)$request->query('docente_id', 0),
            'seccion_id' => (int)$request->query('seccion_id', 0),
            'estado' => (string)$request->query('estado', ''),
            'q' => (string)$request->query('q', ''),
        ];

        $asignaciones = $this->asignacionService->listarAsignaciones($filters);
        $periodos = $this->asignacionService->obtenerPeriodos();
        $docentes = $this->asignacionService->obtenerDocentesActivos();

        $html = View::render('admin.asignaciones.index', [
            'title' => 'Gestión de Asignaciones Docentes',
            'asignaciones' => $asignaciones,
            'periodos' => $periodos,
            'docentes' => $docentes,
            'filtros' => $filters,
            'flash_success' => Session::getFlash('success'),
            'flash_error' => Session::getFlash('error'),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function show(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $asignacion = $this->asignacionService->obtenerAsignacion($id);

        if (!$asignacion) {
            Session::setFlash('error', 'Asignación docente no encontrada.');
            return Response::redirect('/administracion/asignaciones');
        }

        $html = View::render('admin.asignaciones.ver', [
            'title' => 'Detalle de Asignación Docente',
            'asignacion' => $asignacion,
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function createForm(Request $request): Response
    {
        $docentes = $this->asignacionService->obtenerDocentesActivos();
        $aulas = $this->asignacionService->obtenerAulasActivas();
        $secciones = $this->asignacionService->obtenerSeccionesDisponibles();
        $periodos = $this->asignacionService->obtenerPeriodos();

        $idSeccionParam = (int)$request->query('id_seccion', 0);

        $html = View::render('admin.asignaciones.crear', [
            'title' => 'Registrar Asignación Docente',
            'docentes' => $docentes,
            'aulas' => $aulas,
            'secciones' => $secciones,
            'periodos' => $periodos,
            'errors' => [],
            'old' => [
                'id_seccion' => $idSeccionParam > 0 ? (string)$idSeccionParam : '',
                'fecha_inicio' => date('Y-m-d'),
                'estado' => 'ACTIVA',
            ],
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function store(Request $request): Response
    {
        // Whitelist estricto contra mass assignment
        $input = [
            'id_docente' => (int)$request->input('id_docente', 0),
            'id_seccion' => (int)$request->input('id_seccion', 0),
            'id_aula' => (int)$request->input('id_aula', 0),
            'fecha_inicio' => (string)$request->input('fecha_inicio', ''),
            'fecha_fin' => (string)$request->input('fecha_fin', ''),
            'estado' => (string)$request->input('estado', 'ACTIVA'),
            'id_periodo_escolar' => (int)$request->input('id_periodo_escolar', 0),
            'id_grado' => (int)$request->input('id_grado', 0),
        ];

        $user = $this->auth->user();
        $idUsuario = $user ? $user->id_usuario : (int)Session::get('auth_user_id', 1);

        $result = $this->asignacionService->crearAsignacion($input, $idUsuario);

        if (!$result['success']) {
            $docentes = $this->asignacionService->obtenerDocentesActivos();
            $aulas = $this->asignacionService->obtenerAulasActivas();
            $secciones = $this->asignacionService->obtenerSeccionesDisponibles();
            $periodos = $this->asignacionService->obtenerPeriodos();

            $html = View::render('admin.asignaciones.crear', [
                'title' => 'Registrar Asignación Docente',
                'docentes' => $docentes,
                'aulas' => $aulas,
                'secciones' => $secciones,
                'periodos' => $periodos,
                'errors' => $result['errors'] ?? ['general' => $result['message']],
                'old' => $input,
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        Session::setFlash('success', 'Asignación docente registrada exitosamente.');
        return Response::redirect('/administracion/asignaciones');
    }

    public function editForm(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $asignacion = $this->asignacionService->obtenerAsignacion($id);

        if (!$asignacion) {
            Session::setFlash('error', 'Asignación docente no encontrada.');
            return Response::redirect('/administracion/asignaciones');
        }

        $aulas = $this->asignacionService->obtenerAulasActivas();

        $html = View::render('admin.asignaciones.editar', [
            'title' => 'Editar Asignación Docente',
            'asignacion' => $asignacion,
            'aulas' => $aulas,
            'errors' => [],
            'old' => $asignacion->toArray(),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function update(Request $request): Response
    {
        $id = (int)$request->query('id', 0);

        // Whitelist estricto de campos mutables
        $input = [
            'id_aula' => (int)$request->input('id_aula', 0),
            'fecha_inicio' => (string)$request->input('fecha_inicio', ''),
            'fecha_fin' => (string)$request->input('fecha_fin', ''),
            'estado' => (string)$request->input('estado', 'ACTIVA'),
        ];

        $result = $this->asignacionService->actualizarAsignacion($id, $input);

        if (!$result['success']) {
            $asignacion = $this->asignacionService->obtenerAsignacion($id);
            $aulas = $this->asignacionService->obtenerAulasActivas();

            $html = View::render('admin.asignaciones.editar', [
                'title' => 'Editar Asignación Docente',
                'asignacion' => $asignacion,
                'aulas' => $aulas,
                'errors' => $result['errors'] ?? ['general' => $result['message']],
                'old' => array_merge(['id_asignacion_docente' => $id], $input),
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        Session::setFlash('success', 'Asignación docente actualizada exitosamente.');
        return Response::redirect('/administracion/asignaciones');
    }

    public function toggleEstado(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $nuevoEstado = (string)$request->input('nuevo_estado', 'INACTIVA');
        $fechaFin = (string)$request->input('fecha_fin', '');

        $result = $this->asignacionService->cambiarEstado($id, $nuevoEstado, $fechaFin !== '' ? $fechaFin : null);

        if ($result['success']) {
            Session::setFlash('success', $result['message']);
        } else {
            Session::setFlash('error', $result['message']);
        }

        return Response::redirect('/administracion/asignaciones');
    }

    public function reemplazarForm(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $asignacion = $this->asignacionService->obtenerAsignacion($id);

        if (!$asignacion || !$asignacion->isActiva()) {
            Session::setFlash('error', 'Solo se puede reemplazar el docente de una asignación activa.');
            return Response::redirect('/administracion/asignaciones');
        }

        $docentes = $this->asignacionService->obtenerDocentesActivos();
        $aulas = $this->asignacionService->obtenerAulasActivas();

        $html = View::render('admin.asignaciones.reemplazar', [
            'title' => 'Reemplazar Docente en Sección',
            'asignacion' => $asignacion,
            'docentes' => $docentes,
            'aulas' => $aulas,
            'errors' => [],
            'old' => [
                'id_aula' => (string)$asignacion->id_aula,
                'fecha_inicio' => date('Y-m-d'),
            ],
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function reemplazar(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $nuevoIdDocente = (int)$request->input('nuevo_id_docente', 0);
        $idAula = (int)$request->input('id_aula', 0);
        $fechaInicio = (string)$request->input('fecha_inicio', date('Y-m-d'));

        $user = $this->auth->user();
        $idUsuario = $user ? $user->id_usuario : (int)Session::get('auth_user_id', 1);

        $result = $this->asignacionService->reemplazarDocenteAtomico(
            $id,
            $nuevoIdDocente,
            $idAula,
            $fechaInicio,
            $idUsuario
        );

        if (!$result['success']) {
            $asignacion = $this->asignacionService->obtenerAsignacion($id);
            $docentes = $this->asignacionService->obtenerDocentesActivos();
            $aulas = $this->asignacionService->obtenerAulasActivas();

            $html = View::render('admin.asignaciones.reemplazar', [
                'title' => 'Reemplazar Docente en Sección',
                'asignacion' => $asignacion,
                'docentes' => $docentes,
                'aulas' => $aulas,
                'errors' => ['general' => $result['message']],
                'old' => [
                    'nuevo_id_docente' => (string)$nuevoIdDocente,
                    'id_aula' => (string)$idAula,
                    'fecha_inicio' => $fechaInicio,
                ],
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        Session::setFlash('success', 'Docente reemplazado exitosamente de forma atómica.');
        return Response::redirect('/administracion/asignaciones');
    }
}
