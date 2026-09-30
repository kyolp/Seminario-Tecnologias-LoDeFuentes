<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\DocenteService;

class AdminDocenteController extends BaseController
{
    private DocenteService $service;

    public function __construct(?DocenteService $service = null)
    {
        $this->service = $service ?? new DocenteService();
    }

    public function index(Request $request): Response
    {
        $search = $request->query('buscar');
        $searchStr = is_string($search) && trim($search) !== '' ? trim($search) : null;
        $docentes = $this->service->listarDocentes($searchStr);

        $html = View::render('admin.docentes.index', [
            'title' => 'Administración de Docentes',
            'docentes' => $docentes,
            'search' => $searchStr ?? '',
            'flash_success' => Session::getFlash('success'),
            'flash_error' => Session::getFlash('error'),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function show(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $docente = $this->service->obtenerDocente($id);

        if (!$docente) {
            Session::setFlash('error', 'Docente no encontrado.');
            return Response::redirect('/administracion/docentes');
        }

        $html = View::render('admin.docentes.ver', [
            'title' => 'Detalle del Docente',
            'docente' => $docente,
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function createForm(Request $request): Response
    {
        $html = View::render('admin.docentes.crear', [
            'title' => 'Registrar Nuevo Docente',
            'errors' => [],
            'old' => [],
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function store(Request $request): Response
    {
        // Whitelist estricto contra mass assignment
        $input = [
            'nombres' => (string)$request->input('nombres', ''),
            'apellidos' => (string)$request->input('apellidos', ''),
            'nombre_usuario' => (string)$request->input('nombre_usuario', ''),
            'correo' => (string)$request->input('correo', ''),
            'password' => (string)$request->input('password', ''),
            'codigo_docente' => (string)$request->input('codigo_docente', ''),
            'fecha_ingreso' => (string)$request->input('fecha_ingreso', ''),
            'estado' => (string)$request->input('estado', 'ACTIVO'),
        ];

        $result = $this->service->crearDocente($input);

        if (!$result['success']) {
            $old = $input;
            unset($old['password']);
            $html = View::render('admin.docentes.crear', [
                'title' => 'Registrar Nuevo Docente',
                'errors' => $result['errors'] ?? ['general' => $result['message'] ?? 'Error al guardar.'],
                'old' => $old,
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        Session::setFlash('success', 'Docente registrado exitosamente.');
        return Response::redirect('/administracion/docentes');
    }

    public function editForm(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $docente = $this->service->obtenerDocente($id);

        if (!$docente) {
            Session::setFlash('error', 'Docente no encontrado.');
            return Response::redirect('/administracion/docentes');
        }

        $html = View::render('admin.docentes.editar', [
            'title' => 'Editar Docente',
            'docente' => $docente,
            'errors' => [],
            'old' => $docente->toArray(),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function update(Request $request): Response
    {
        $id = (int)$request->query('id', 0);

        $input = [
            'nombres' => (string)$request->input('nombres', ''),
            'apellidos' => (string)$request->input('apellidos', ''),
            'correo' => (string)$request->input('correo', ''),
            'password' => (string)$request->input('password', ''),
            'codigo_docente' => (string)$request->input('codigo_docente', ''),
            'fecha_ingreso' => (string)$request->input('fecha_ingreso', ''),
            'estado' => (string)$request->input('estado', 'ACTIVO'),
        ];

        $result = $this->service->actualizarDocente($id, $input);

        if (!$result['success']) {
            $docente = $this->service->obtenerDocente($id);
            $old = array_merge(['id_docente' => $id], $input);
            unset($old['password']);
            $html = View::render('admin.docentes.editar', [
                'title' => 'Editar Docente',
                'docente' => $docente,
                'errors' => $result['errors'] ?? ['general' => $result['message'] ?? 'Error al actualizar.'],
                'old' => $old,
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        Session::setFlash('success', 'Docente actualizado exitosamente.');
        return Response::redirect('/administracion/docentes');
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

        return Response::redirect('/administracion/docentes');
    }

    public function delete(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $result = $this->service->eliminarDocente($id);

        if ($result['success']) {
            Session::setFlash('success', $result['message'] ?? 'Docente eliminado.');
        } else {
            Session::setFlash('error', $result['message'] ?? 'No se pudo eliminar el docente.');
        }

        return Response::redirect('/administracion/docentes');
    }
}
