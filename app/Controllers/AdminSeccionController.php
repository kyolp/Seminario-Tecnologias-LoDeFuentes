<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\GradoService;
use App\Services\SeccionService;

class AdminSeccionController extends BaseController
{
    private SeccionService $seccionService;
    private GradoService $gradoService;

    public function __construct(
        ?SeccionService $seccionService = null,
        ?GradoService $gradoService = null
    ) {
        $this->seccionService = $seccionService ?? new SeccionService();
        $this->gradoService = $gradoService ?? new GradoService();
    }

    public function index(Request $request): Response
    {
        $gpId = (int)$request->query('grado_periodo_id', 0);
        $gpIdFilter = $gpId > 0 ? $gpId : null;

        $secciones = $this->seccionService->listarSecciones($gpIdFilter);
        $gradosPeriodos = $this->gradoService->listarGradosPeriodos();

        $html = View::render('admin.secciones.index', [
            'title' => 'Administración de Secciones',
            'secciones' => $secciones,
            'gradosPeriodos' => $gradosPeriodos,
            'gpSeleccionado' => $gpId,
            'flash_success' => Session::getFlash('success'),
            'flash_error' => Session::getFlash('error'),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function show(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $seccion = $this->seccionService->obtenerSeccion($id);

        if (!$seccion) {
            Session::setFlash('error', 'Sección no encontrada.');
            return Response::redirect('/administracion/secciones');
        }

        $html = View::render('admin.secciones.ver', [
            'title' => 'Detalle de la Sección',
            'seccion' => $seccion,
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function createForm(Request $request): Response
    {
        $gradosPeriodos = $this->gradoService->listarGradosPeriodos();

        $html = View::render('admin.secciones.crear', [
            'title' => 'Crear Sección',
            'gradosPeriodos' => $gradosPeriodos,
            'errors' => [],
            'old' => [
                'id_grado_periodo' => $request->query('grado_periodo_id', ''),
                'capacidad' => '30',
            ],
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function store(Request $request): Response
    {
        $input = [
            'id_grado_periodo' => $request->input('id_grado_periodo', 0),
            'codigo' => (string)$request->input('codigo', ''),
            'nombre' => (string)$request->input('nombre', ''),
            'capacidad' => $request->input('capacidad', ''),
            'habilitada' => (int)$request->input('habilitada', 1),
        ];

        $result = $this->seccionService->crearSeccion($input);

        if (!$result['success']) {
            $gradosPeriodos = $this->gradoService->listarGradosPeriodos();

            $html = View::render('admin.secciones.crear', [
                'title' => 'Crear Sección',
                'gradosPeriodos' => $gradosPeriodos,
                'errors' => $result['errors'] ?? ['general' => $result['message'] ?? 'Error al guardar.'],
                'old' => $input,
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        Session::setFlash('success', 'Sección creada exitosamente con su capacidad correspondiente.');
        return Response::redirect('/administracion/secciones');
    }

    public function editForm(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $seccion = $this->seccionService->obtenerSeccion($id);

        if (!$seccion) {
            Session::setFlash('error', 'Sección no encontrada.');
            return Response::redirect('/administracion/secciones');
        }

        $html = View::render('admin.secciones.editar', [
            'title' => 'Editar Sección',
            'seccion' => $seccion,
            'errors' => [],
            'old' => $seccion->toArray(),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function update(Request $request): Response
    {
        $id = (int)$request->query('id', 0);

        $input = [
            'codigo' => (string)$request->input('codigo', ''),
            'nombre' => (string)$request->input('nombre', ''),
            'capacidad' => $request->input('capacidad', ''),
            'habilitada' => (int)$request->input('habilitada', 1),
        ];

        $result = $this->seccionService->actualizarSeccion($id, $input);

        if (!$result['success']) {
            $seccion = $this->seccionService->obtenerSeccion($id);

            $html = View::render('admin.secciones.editar', [
                'title' => 'Editar Sección',
                'seccion' => $seccion,
                'errors' => $result['errors'] ?? ['general' => $result['message'] ?? 'Error al actualizar.'],
                'old' => array_merge(['id_seccion' => $id], $input),
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        Session::setFlash('success', 'Sección y capacidad actualizadas exitosamente.');
        return Response::redirect('/administracion/secciones');
    }

    public function toggleHabilitada(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $habilitada = (int)$request->input('habilitada', 0);

        $result = $this->seccionService->cambiarHabilitada($id, $habilitada);

        if ($result['success']) {
            Session::setFlash('success', $result['message'] ?? 'Estado actualizado.');
        } else {
            Session::setFlash('error', $result['message'] ?? 'Error al modificar estado.');
        }

        return Response::redirect('/administracion/secciones');
    }

    public function delete(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $result = $this->seccionService->eliminarSeccion($id);

        if ($result['success']) {
            Session::setFlash('success', $result['message'] ?? 'Sección eliminada.');
        } else {
            Session::setFlash('error', $result['message'] ?? 'No se pudo eliminar la sección.');
        }

        return Response::redirect('/administracion/secciones');
    }
}
