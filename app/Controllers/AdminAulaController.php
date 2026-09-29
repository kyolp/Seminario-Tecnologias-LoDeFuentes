<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AulaService;

class AdminAulaController extends BaseController
{
    private AulaService $service;

    public function __construct(?AulaService $service = null)
    {
        $this->service = $service ?? new AulaService();
    }

    public function index(Request $request): Response
    {
        $aulas = $this->service->listarAulas();

        $html = View::render('admin.aulas.index', [
            'title' => 'Administración de Aulas',
            'aulas' => $aulas,
            'flash_success' => Session::getFlash('success'),
            'flash_error' => Session::getFlash('error'),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function createForm(Request $request): Response
    {
        $html = View::render('admin.aulas.crear', [
            'title' => 'Registrar Nueva Aula',
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
            'ubicacion_referencia' => (string)$request->input('ubicacion_referencia', ''),
            'activa' => (int)$request->input('activa', 1),
        ];

        $result = $this->service->crearAula($input);

        if (!$result['success']) {
            $html = View::render('admin.aulas.crear', [
                'title' => 'Registrar Nueva Aula',
                'errors' => $result['errors'] ?? ['general' => $result['message'] ?? 'Error al guardar.'],
                'old' => $input,
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        Session::setFlash('success', 'Aula registrada exitosamente.');
        return Response::redirect('/administracion/aulas');
    }

    public function editForm(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $aula = $this->service->obtenerAula($id);

        if (!$aula) {
            Session::setFlash('error', 'Aula no encontrada.');
            return Response::redirect('/administracion/aulas');
        }

        $html = View::render('admin.aulas.editar', [
            'title' => 'Editar Aula',
            'aula' => $aula,
            'errors' => [],
            'old' => $aula->toArray(),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function update(Request $request): Response
    {
        $id = (int)$request->query('id', 0);

        $input = [
            'codigo' => (string)$request->input('codigo', ''),
            'nombre' => (string)$request->input('nombre', ''),
            'ubicacion_referencia' => (string)$request->input('ubicacion_referencia', ''),
            'activa' => (int)$request->input('activa', 1),
        ];

        $result = $this->service->actualizarAula($id, $input);

        if (!$result['success']) {
            $aula = $this->service->obtenerAula($id);
            $html = View::render('admin.aulas.editar', [
                'title' => 'Editar Aula',
                'aula' => $aula,
                'errors' => $result['errors'] ?? ['general' => $result['message'] ?? 'Error al actualizar.'],
                'old' => array_merge(['id_aula' => $id], $input),
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        Session::setFlash('success', 'Aula actualizada exitosamente.');
        return Response::redirect('/administracion/aulas');
    }

    public function toggleActiva(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $activa = (int)$request->input('activa', 0);

        $result = $this->service->cambiarActiva($id, $activa);

        if ($result['success']) {
            Session::setFlash('success', $result['message'] ?? 'Estado actualizado.');
        } else {
            Session::setFlash('error', $result['message'] ?? 'No se pudo modificar estado.');
        }

        return Response::redirect('/administracion/aulas');
    }

    public function delete(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $result = $this->service->eliminarAula($id);

        if ($result['success']) {
            Session::setFlash('success', $result['message'] ?? 'Aula eliminada.');
        } else {
            Session::setFlash('error', $result['message'] ?? 'No se pudo eliminar el aula.');
        }

        return Response::redirect('/administracion/aulas');
    }
}
