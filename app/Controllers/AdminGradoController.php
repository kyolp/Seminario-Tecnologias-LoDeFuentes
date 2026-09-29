<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\GradoService;

class AdminGradoController extends BaseController
{
    private GradoService $service;

    public function __construct(?GradoService $service = null)
    {
        $this->service = $service ?? new GradoService();
    }

    public function index(Request $request): Response
    {
        $grados = $this->service->listarGrados();

        $html = View::render('admin.grados.index', [
            'title' => 'Administración de Grados',
            'grados' => $grados,
            'flash_success' => Session::getFlash('success'),
            'flash_error' => Session::getFlash('error'),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function createForm(Request $request): Response
    {
        $html = View::render('admin.grados.crear', [
            'title' => 'Registrar Nuevo Grado',
            'errors' => [],
            'old' => [],
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function store(Request $request): Response
    {
        $input = [
            'codigo' => (string)$request->input('codigo', ''),
            'nombre' => (string)$request->input('nombre', ''),
            'orden' => $request->input('orden', ''),
            'activo' => (int)$request->input('activo', 1),
        ];

        $result = $this->service->crearGrado($input);

        if (!$result['success']) {
            $html = View::render('admin.grados.crear', [
                'title' => 'Registrar Nuevo Grado',
                'errors' => $result['errors'] ?? ['general' => $result['message'] ?? 'Error al guardar.'],
                'old' => $input,
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        Session::setFlash('success', 'Grado registrado exitosamente.');
        return Response::redirect('/administracion/grados');
    }

    public function editForm(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $grado = $this->service->obtenerGrado($id);

        if (!$grado) {
            Session::setFlash('error', 'Grado no encontrado.');
            return Response::redirect('/administracion/grados');
        }

        $html = View::render('admin.grados.editar', [
            'title' => 'Editar Grado',
            'grado' => $grado,
            'errors' => [],
            'old' => $grado->toArray(),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function update(Request $request): Response
    {
        $id = (int)$request->query('id', 0);

        $input = [
            'codigo' => (string)$request->input('codigo', ''),
            'nombre' => (string)$request->input('nombre', ''),
            'orden' => $request->input('orden', ''),
            'activo' => (int)$request->input('activo', 1),
        ];

        $result = $this->service->actualizarGrado($id, $input);

        if (!$result['success']) {
            $grado = $this->service->obtenerGrado($id);
            $html = View::render('admin.grados.editar', [
                'title' => 'Editar Grado',
                'grado' => $grado,
                'errors' => $result['errors'] ?? ['general' => $result['message'] ?? 'Error al actualizar.'],
                'old' => array_merge(['id_grado' => $id], $input),
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        Session::setFlash('success', 'Grado actualizado exitosamente.');
        return Response::redirect('/administracion/grados');
    }

    public function toggleActivo(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $activo = (int)$request->input('activo', 0);

        $result = $this->service->cambiarActivo($id, $activo);

        if ($result['success']) {
            Session::setFlash('success', $result['message'] ?? 'Estado actualizado.');
        } else {
            Session::setFlash('error', $result['message'] ?? 'No se pudo modificar estado.');
        }

        return Response::redirect('/administracion/grados');
    }

    public function delete(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $result = $this->service->eliminarGrado($id);

        if ($result['success']) {
            Session::setFlash('success', $result['message'] ?? 'Grado eliminado.');
        } else {
            Session::setFlash('error', $result['message'] ?? 'No se pudo eliminar el grado.');
        }

        return Response::redirect('/administracion/grados');
    }
}
