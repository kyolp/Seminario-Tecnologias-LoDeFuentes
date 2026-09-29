<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\EstudianteService;

class AdminEstudianteController extends BaseController
{
    private EstudianteService $service;

    public function __construct(?EstudianteService $service = null)
    {
        $this->service = $service ?? new EstudianteService();
    }

    public function index(Request $request): Response
    {
        $search = $request->query('buscar');
        $searchStr = is_string($search) && trim($search) !== '' ? trim($search) : null;
        $estudiantes = $this->service->listarEstudiantes($searchStr);

        $html = View::render('admin.estudiantes.index', [
            'title' => 'Administración de Estudiantes',
            'estudiantes' => $estudiantes,
            'search' => $searchStr ?? '',
            'flash_success' => Session::getFlash('success'),
            'flash_error' => Session::getFlash('error'),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function show(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $estudiante = $this->service->obtenerEstudiante($id);

        if (!$estudiante) {
            Session::setFlash('error', 'Estudiante no encontrado.');
            return Response::redirect('/administracion/estudiantes');
        }

        $html = View::render('admin.estudiantes.ver', [
            'title' => 'Detalle del Estudiante',
            'estudiante' => $estudiante,
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function createForm(Request $request): Response
    {
        $html = View::render('admin.estudiantes.crear', [
            'title' => 'Registrar Estudiante',
            'errors' => [],
            'old' => [],
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function store(Request $request): Response
    {
        $input = [
            'codigo_estudiante' => (string)$request->input('codigo_estudiante', ''),
            'nombres' => (string)$request->input('nombres', ''),
            'apellidos' => (string)$request->input('apellidos', ''),
            'fecha_nacimiento' => (string)$request->input('fecha_nacimiento', ''),
            'estado' => (string)$request->input('estado', 'ACTIVO'),
        ];

        $result = $this->service->crearEstudiante($input);

        if (!$result['success']) {
            $html = View::render('admin.estudiantes.crear', [
                'title' => 'Registrar Estudiante',
                'errors' => $result['errors'] ?? ['general' => $result['message'] ?? 'Error al guardar.'],
                'old' => $input,
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        Session::setFlash('success', 'Estudiante registrado exitosamente.');
        return Response::redirect('/administracion/estudiantes');
    }

    public function editForm(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $estudiante = $this->service->obtenerEstudiante($id);

        if (!$estudiante) {
            Session::setFlash('error', 'Estudiante no encontrado.');
            return Response::redirect('/administracion/estudiantes');
        }

        $html = View::render('admin.estudiantes.editar', [
            'title' => 'Editar Estudiante',
            'estudiante' => $estudiante,
            'errors' => [],
            'old' => $estudiante->toArray(),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function update(Request $request): Response
    {
        $id = (int)$request->query('id', 0);

        $input = [
            'codigo_estudiante' => (string)$request->input('codigo_estudiante', ''),
            'nombres' => (string)$request->input('nombres', ''),
            'apellidos' => (string)$request->input('apellidos', ''),
            'fecha_nacimiento' => (string)$request->input('fecha_nacimiento', ''),
            'estado' => (string)$request->input('estado', 'ACTIVO'),
        ];

        $result = $this->service->actualizarEstudiante($id, $input);

        if (!$result['success']) {
            $estudiante = $this->service->obtenerEstudiante($id);
            $html = View::render('admin.estudiantes.editar', [
                'title' => 'Editar Estudiante',
                'estudiante' => $estudiante,
                'errors' => $result['errors'] ?? ['general' => $result['message'] ?? 'Error al actualizar.'],
                'old' => array_merge(['id_estudiante' => $id], $input),
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        Session::setFlash('success', 'Estudiante actualizado exitosamente.');
        return Response::redirect('/administracion/estudiantes');
    }

    public function toggleEstado(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $nuevoEstado = (string)$request->input('nuevo_estado', 'INACTIVO');

        $result = $this->service->cambiarEstado($id, $nuevoEstado);

        if ($result['success']) {
            Session::setFlash('success', $result['message'] ?? 'Estado actualizado.');
        } else {
            Session::setFlash('error', $result['message'] ?? 'Error al modificar estado.');
        }

        return Response::redirect('/administracion/estudiantes');
    }

    public function delete(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $result = $this->service->eliminarEstudiante($id);

        if ($result['success']) {
            Session::setFlash('success', $result['message'] ?? 'Estudiante eliminado.');
        } else {
            Session::setFlash('error', $result['message'] ?? 'No se pudo eliminar el estudiante.');
        }

        return Response::redirect('/administracion/estudiantes');
    }
}
