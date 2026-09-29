<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\GradoService;
use App\Services\PeriodoService;

class AdminGradoPeriodoController extends BaseController
{
    private GradoService $gradoService;
    private PeriodoService $periodoService;

    public function __construct(
        ?GradoService $gradoService = null,
        ?PeriodoService $periodoService = null
    ) {
        $this->gradoService = $gradoService ?? new GradoService();
        $this->periodoService = $periodoService ?? new PeriodoService();
    }

    public function index(Request $request): Response
    {
        $periodoId = (int)$request->query('periodo_id', 0);
        $periodoIdFilter = $periodoId > 0 ? $periodoId : null;

        $gradosPeriodos = $this->gradoService->listarGradosPeriodos($periodoIdFilter);
        $periodos = $this->periodoService->listarPeriodos();

        $html = View::render('admin.grados_periodos.index', [
            'title' => 'Configuración de Grados por Período',
            'gradosPeriodos' => $gradosPeriodos,
            'periodos' => $periodos,
            'periodoSeleccionado' => $periodoId,
            'flash_success' => Session::getFlash('success'),
            'flash_error' => Session::getFlash('error'),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function createForm(Request $request): Response
    {
        $periodos = $this->periodoService->listarPeriodos();
        $grados = $this->gradoService->listarGrados();

        $html = View::render('admin.grados_periodos.crear', [
            'title' => 'Asociar Grado a Período Escolar',
            'periodos' => $periodos,
            'grados' => $grados,
            'errors' => [],
            'old' => [
                'id_periodo_escolar' => $request->query('periodo_id', ''),
            ],
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function store(Request $request): Response
    {
        $input = [
            'id_periodo_escolar' => $request->input('id_periodo_escolar', 0),
            'id_grado' => $request->input('id_grado', 0),
            'habilitado' => (int)$request->input('habilitado', 1),
        ];

        $result = $this->gradoService->asociarGradoPeriodo($input);

        if (!$result['success']) {
            $periodos = $this->periodoService->listarPeriodos();
            $grados = $this->gradoService->listarGrados();

            $html = View::render('admin.grados_periodos.crear', [
                'title' => 'Asociar Grado a Período Escolar',
                'periodos' => $periodos,
                'grados' => $grados,
                'errors' => $result['errors'] ?? ['general' => $result['message'] ?? 'Error al asociar.'],
                'old' => $input,
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        Session::setFlash('success', 'Grado asociado al período escolar exitosamente.');
        return Response::redirect('/administracion/grados-periodos');
    }

    public function toggleHabilitado(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $habilitado = (int)$request->input('habilitado', 0);

        $result = $this->gradoService->cambiarHabilitadoGradoPeriodo($id, $habilitado);

        if ($result['success']) {
            Session::setFlash('success', $result['message'] ?? 'Estado actualizado.');
        } else {
            Session::setFlash('error', $result['message'] ?? 'Error al modificar estado.');
        }

        return Response::redirect('/administracion/grados-periodos');
    }

    public function delete(Request $request): Response
    {
        $id = (int)$request->query('id', 0);
        $result = $this->gradoService->eliminarGradoPeriodo($id);

        if ($result['success']) {
            Session::setFlash('success', $result['message'] ?? 'Asociación removida.');
        } else {
            Session::setFlash('error', $result['message'] ?? 'No se pudo remover la asociación.');
        }

        return Response::redirect('/administracion/grados-periodos');
    }
}
