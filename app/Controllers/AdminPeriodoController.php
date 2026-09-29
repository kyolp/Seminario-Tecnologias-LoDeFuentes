<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\PeriodoService;

class AdminPeriodoController extends BaseController
{
    private PeriodoService $service;

    public function __construct(?PeriodoService $service = null)
    {
        $this->service = $service ?? new PeriodoService();
    }

    public function index(Request $request): Response
    {
        $periodos = $this->service->listarPeriodos();

        $html = View::render('admin.periodos.index', [
            'title' => 'Administración de Períodos Escolares',
            'periodos' => $periodos,
            'flash_success' => Session::getFlash('success'),
            'flash_error' => Session::getFlash('error'),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function show(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $periodo = $this->service->obtenerPeriodo($id);

        if (!$periodo) {
            Session::setFlash('error', 'Período escolar no encontrado.');
            return Response::redirect('/administracion/periodos');
        }

        $html = View::render('admin.periodos.ver', [
            'title' => 'Detalle del Período Escolar',
            'periodo' => $periodo,
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function createForm(Request $request): Response
    {
        $html = View::render('admin.periodos.crear', [
            'title' => 'Crear Período Escolar',
            'errors' => [],
            'old' => [],
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function store(Request $request): Response
    {
        $input = [
            'nombre' => (string)$request->input('nombre', ''),
            'fecha_inicio' => (string)$request->input('fecha_inicio', ''),
            'fecha_fin' => (string)$request->input('fecha_fin', ''),
            'estado' => (string)$request->input('estado', 'PLANIFICADO'),
        ];

        $result = $this->service->crearPeriodo($input);

        if (!$result['success']) {
            $html = View::render('admin.periodos.crear', [
                'title' => 'Crear Período Escolar',
                'errors' => $result['errors'] ?? ['general' => $result['message'] ?? 'Error al guardar.'],
                'old' => $input,
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        Session::setFlash('success', 'Período escolar creado exitosamente.');
        return Response::redirect('/administracion/periodos');
    }

    public function editForm(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $periodo = $this->service->obtenerPeriodo($id);

        if (!$periodo) {
            Session::setFlash('error', 'Período escolar no encontrado.');
            return Response::redirect('/administracion/periodos');
        }

        $html = View::render('admin.periodos.editar', [
            'title' => 'Editar Período Escolar',
            'periodo' => $periodo,
            'errors' => [],
            'old' => $periodo->toArray(),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function update(Request $request): Response
    {
        $id = (int)$request->query('id', 0);

        $input = [
            'nombre' => (string)$request->input('nombre', ''),
            'fecha_inicio' => (string)$request->input('fecha_inicio', ''),
            'fecha_fin' => (string)$request->input('fecha_fin', ''),
            'estado' => (string)$request->input('estado', 'PLANIFICADO'),
        ];

        $result = $this->service->actualizarPeriodo($id, $input);

        if (!$result['success']) {
            $periodo = $this->service->obtenerPeriodo($id);
            $html = View::render('admin.periodos.editar', [
                'title' => 'Editar Período Escolar',
                'periodo' => $periodo,
                'errors' => $result['errors'] ?? ['general' => $result['message'] ?? 'Error al actualizar.'],
                'old' => array_merge(['id_periodo_escolar' => $id], $input),
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        Session::setFlash('success', 'Período escolar actualizado exitosamente.');
        return Response::redirect('/administracion/periodos');
    }

    public function toggleEstado(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $nuevoEstado = (string)$request->input('nuevo_estado', 'ACTIVO');

        $result = $this->service->cambiarEstado($id, $nuevoEstado);

        if ($result['success']) {
            Session::setFlash('success', $result['message'] ?? 'Estado actualizado.');
        } else {
            Session::setFlash('error', $result['message'] ?? 'No se pudo modificar el estado.');
        }

        return Response::redirect('/administracion/periodos');
    }

    public function delete(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $result = $this->service->eliminarPeriodo($id);

        if ($result['success']) {
            Session::setFlash('success', $result['message'] ?? 'Período eliminado.');
        } else {
            Session::setFlash('error', $result['message'] ?? 'No se pudo eliminar el período.');
        }

        return Response::redirect('/administracion/periodos');
    }
}
