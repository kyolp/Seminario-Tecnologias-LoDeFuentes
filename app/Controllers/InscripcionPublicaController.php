<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\InscripcionService;

/**
 * Controlador para el proceso público de inscripciones y consulta por código (Fase 6).
 * No requiere autenticación previa (Actor público: Padre, Madre o Encargado).
 */
class InscripcionPublicaController
{
    private InscripcionService $inscripcionService;

    public function __construct(?InscripcionService $inscripcionService = null)
    {
        $this->inscripcionService = $inscripcionService ?? new InscripcionService();
    }

    /**
     * Mostrar formulario público de inscripción web (RF-11).
     */
    public function createForm(Request $request): Response
    {
        $periodo = $this->inscripcionService->obtenerPeriodoInscripcion();
        $grados = [];

        if ($periodo) {
            $grados = $this->inscripcionService->obtenerGradosHabilitados($periodo->id_periodo_escolar);
        }

        $html = View::render('inscripciones.nueva', [
            'title' => 'Inscripción Escolar en Línea — Escuela Oficial Rural Mixta Lo de Fuentes',
            'periodo' => $periodo,
            'grados' => $grados,
            'errors' => Session::get('_flash_errors', []),
            'old' => Session::get('_flash_old', []),
            'successMessage' => Session::get('_flash_success'),
            'errorMessage' => Session::get('_flash_error'),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Endpoint API / AJAX para obtener secciones disponibles con cálculo de cupos (RF-10, RN-DOC-02, RN-DOC-03).
     */
    public function getSeccionesDisponibles(Request $request): Response
    {
        $idGradoPeriodo = (int)$request->query('id_grado_periodo', 0);
        if ($idGradoPeriodo <= 0) {
            return new Response(json_encode(['success' => false, 'secciones' => []]), 400, ['Content-Type' => 'application/json']);
        }

        // Secciones que aún tienen cupo (soloConCupos = true)
        $secciones = $this->inscripcionService->obtenerSeccionesDisponibles($idGradoPeriodo, true);

        return new Response(json_encode([
            'success' => true,
            'secciones' => $secciones,
            'total_disponibles' => count($secciones),
        ]), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Procesar la solicitud de inscripción web (POST público protegido por CSRF).
     */
    public function store(Request $request): Response
    {
        // Whitelisting estricto contra Mass Assignment (DT-42)
        $allowedFields = [
            'estudiante_nombres',
            'estudiante_apellidos',
            'estudiante_fecha_nacimiento',
            'codigo_estudiante',
            'encargado_nombres',
            'encargado_apellidos',
            'encargado_telefono',
            'encargado_correo',
            'parentesco',
            'id_seccion',
        ];

        $payload = [];
        foreach ($allowedFields as $field) {
            $val = $request->input($field);
            if ($val !== null) {
                $payload[$field] = $val;
            }
        }

        $result = $this->inscripcionService->procesarInscripcionPublica($payload);

        if ($result['success']) {
            $codigo = (string)($result['codigo_confirmacion'] ?? '');
            Session::set('_inscripcion_exitosa_codigo', $codigo);
            Session::setFlash('success', $result['message']);

            return Response::redirect(url('inscripcion/exito?codigo=' . urlencode($codigo)));
        }

        // Manejo de error: re-renderizar formulario con estado HTTP 422
        $periodo = $this->inscripcionService->obtenerPeriodoInscripcion();
        $grados = $periodo ? $this->inscripcionService->obtenerGradosHabilitados($periodo->id_periodo_escolar) : [];

        $html = View::render('inscripciones.nueva', [
            'title' => 'Inscripción Escolar en Línea — Escuela Oficial Rural Mixta Lo de Fuentes',
            'periodo' => $periodo,
            'grados' => $grados,
            'errors' => $result['errors'] ?? ['general' => $result['message']],
            'old' => $payload,
            'errorMessage' => $result['message'],
        ], 'main');

        return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Pantalla de éxito con código único de confirmación (RF-12).
     */
    public function success(Request $request): Response
    {
        $codigo = (string)($request->query('codigo') ?? Session::get('_inscripcion_exitosa_codigo', ''));

        $datosInscripcion = null;
        if ($codigo !== '') {
            $res = $this->inscripcionService->consultarInscripcionPorCodigo($codigo);
            if ($res['success']) {
                $datosInscripcion = $res['data'];
            }
        }

        $html = View::render('inscripciones.exito', [
            'title' => 'Inscripción Confirmada — Escuela Oficial Rural Mixta Lo de Fuentes',
            'codigo' => $codigo,
            'inscripcion' => $datosInscripcion,
            'successMessage' => Session::get('_flash_success'),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Formulario de consulta pública mediante código (RF-14).
     */
    public function consultarForm(Request $request): Response
    {
        $codigo = trim((string)$request->query('codigo', ''));
        $resultado = null;
        $error = null;

        if ($codigo !== '') {
            $res = $this->inscripcionService->consultarInscripcionPorCodigo($codigo);
            if ($res['success']) {
                $resultado = $res['data'];
            } else {
                $error = $res['message'];
            }
        }

        $html = View::render('inscripciones.consultar', [
            'title' => 'Consultar Estado de Inscripción — Escuela Oficial Rural Mixta Lo de Fuentes',
            'codigo' => $codigo,
            'resultado' => $resultado,
            'error' => $error,
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Procesar consulta pública mediante POST (RF-14).
     */
    public function consultar(Request $request): Response
    {
        $codigo = trim((string)$request->input('codigo', ''));
        $res = $this->inscripcionService->consultarInscripcionPorCodigo($codigo);

        $html = View::render('inscripciones.consultar', [
            'title' => 'Consultar Estado de Inscripción — Escuela Oficial Rural Mixta Lo de Fuentes',
            'codigo' => $codigo,
            'resultado' => $res['success'] ? $res['data'] : null,
            'error' => !$res['success'] ? $res['message'] : null,
        ], 'main');

        $statusCode = $res['success'] ? 200 : 404;
        return new Response($html, $statusCode, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
