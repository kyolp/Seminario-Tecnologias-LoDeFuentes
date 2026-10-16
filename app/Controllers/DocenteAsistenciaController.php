<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AsistenciaService;
use App\Services\AuthService;
use App\Services\PortalDocenteService;
use DomainException;
use InvalidArgumentException;
use Throwable;

/**
 * Controlador para el registro y consulta de asistencia docente con geolocalización.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 8 — RF-19, RF-20, RF-21, RF-22, RF-23).
 *
 * Aplica aislamiento horizontal estricto e inmutabilidad de registros.
 */
class DocenteAsistenciaController extends BaseController
{
    private AuthService $auth;
    private AsistenciaService $asistenciaService;
    private PortalDocenteService $portalService;

    public function __construct(
        ?AuthService $auth = null,
        ?AsistenciaService $asistenciaService = null,
        ?PortalDocenteService $portalService = null
    ) {
        $this->auth = $auth ?? new AuthService();
        $this->asistenciaService = $asistenciaService ?? new AsistenciaService();
        $this->portalService = $portalService ?? new PortalDocenteService();
    }

    /**
     * Pantalla principal del módulo de asistencia docente.
     */
    public function index(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        // Revalidación autoritativa en tiempo real de rol y estado activo (DT-54)
        if (!$user->hasRole('DOCENTE') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Solo los usuarios con rol docente activo pueden acceder al módulo de asistencia.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $docente = $this->portalService->obtenerDocentePorUsuarioId($user->id_usuario);
        if ($docente === null || !$docente->isActivo()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Su cuenta docente no se encuentra activa o no existe un perfil asociado.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $estadoAsistencia = $this->asistenciaService->obtenerEstadoAsistenciaDocente($docente->id_docente);
        $historial = $this->asistenciaService->obtenerHistorialDocente($docente->id_docente, 30);

        $html = View::render('docente.asistencia.index', [
            'title' => 'Control de Asistencia Docente — Escuela Oficial Rural Mixta Lo de Fuentes',
            'user' => $user,
            'docente' => $docente,
            'configuracion' => $estadoAsistencia['configuracion'],
            'ultimoMarcaje' => $estadoAsistencia['ultimo_marcaje'],
            'puedeMarcarEntrada' => $estadoAsistencia['puede_marcar_entrada'],
            'puedeMarcarSalida' => $estadoAsistencia['puede_marcar_salida'],
            'jornadaFinalizada' => $estadoAsistencia['jornada_finalizada'],
            'historial' => $historial,
            'mensajeExito' => $request->query('msg'),
            'mensajeError' => $request->query('error'),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Registrar marcaje de entrada con coordenadas del navegador.
     */
    public function marcarEntrada(Request $request): Response
    {
        return $this->procesarMarcajeHttp($request, 'ENTRADA');
    }

    /**
     * Registrar marcaje de salida con coordenadas del navegador.
     */
    public function marcarSalida(Request $request): Response
    {
        return $this->procesarMarcajeHttp($request, 'SALIDA');
    }

    /**
     * Historial personal de marcajes del docente autenticado (RF-23).
     */
    public function historial(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        if (!$user->hasRole('DOCENTE') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Acceso restringido.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $docente = $this->portalService->obtenerDocentePorUsuarioId($user->id_usuario);
        if ($docente === null || !$docente->isActivo()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Cuenta docente inactiva.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        // Aislamiento horizontal estricto: id_docente derivado unívocamente de la sesión
        $historial = $this->asistenciaService->obtenerHistorialDocente($docente->id_docente, 100);

        if ($this->isJsonRequest($request)) {
            $data = array_map(fn($m) => $m->toArray(), $historial);
            return new Response((string) json_encode(['exito' => true, 'historial' => $data], JSON_UNESCAPED_UNICODE), 200, [
                'Content-Type' => 'application/json; charset=utf-8',
            ]);
        }

        $html = View::render('docente.asistencia.historial', [
            'title' => 'Historial de Asistencia — Portal Docente',
            'user' => $user,
            'docente' => $docente,
            'historial' => $historial,
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Procesamiento común de la solicitud HTTP de marcaje.
     */
    private function procesarMarcajeHttp(Request $request, string $tipo): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            if ($this->isJsonRequest($request)) {
                return new Response((string) json_encode(['exito' => false, 'error' => 'No autenticado'], JSON_UNESCAPED_UNICODE), 401, [
                    'Content-Type' => 'application/json',
                ]);
            }
            return Response::redirect('/login');
        }

        if (!$user->hasRole('DOCENTE') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Solo los docentes activos pueden registrar asistencia.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $docente = $this->portalService->obtenerDocentePorUsuarioId($user->id_usuario);
        if ($docente === null || !$docente->isActivo()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Su cuenta docente no se encuentra activa o no existe perfil docente asociado.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'DOCENTE',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        // Whitelist estricta contra mass-assignment: únicamente aceptamos latitud, longitud y precision
        $datosCliente = [
            'latitud' => $request->post('latitud'),
            'longitud' => $request->post('longitud'),
            'precision' => $request->post('precision'),
        ];

        try {
            if ($tipo === 'ENTRADA') {
                $resultado = $this->asistenciaService->registrarEntrada(
                    $docente->id_docente,
                    $user->id_usuario,
                    $datosCliente
                );
            } else {
                $resultado = $this->asistenciaService->registrarSalida(
                    $docente->id_docente,
                    $user->id_usuario,
                    $datosCliente
                );
            }

            if ($this->isJsonRequest($request)) {
                return new Response((string) json_encode([
                    'exito' => true,
                    'mensaje' => $resultado['mensaje'],
                    'distancia_metros' => round($resultado['distancia_metros'], 2),
                    'radio_metros' => round($resultado['radio_metros'], 2),
                    'marcaje' => $resultado['marcaje']->toArray(),
                ], JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/json; charset=utf-8']);
            }

            return Response::redirect('/docente/asistencia?msg=' . urlencode($resultado['mensaje']));
        } catch (DomainException $e) {
            // Error de negocio: fuera de radio o secuencia inválida
            if ($this->isJsonRequest($request)) {
                return new Response((string) json_encode([
                    'exito' => false,
                    'error' => $e->getMessage(),
                ], JSON_UNESCAPED_UNICODE), 422, ['Content-Type' => 'application/json; charset=utf-8']);
            }

            return Response::redirect('/docente/asistencia?error=' . urlencode($e->getMessage()));
        } catch (InvalidArgumentException $e) {
            // Error de formato en coordenadas
            if ($this->isJsonRequest($request)) {
                return new Response((string) json_encode([
                    'exito' => false,
                    'error' => $e->getMessage(),
                ], JSON_UNESCAPED_UNICODE), 422, ['Content-Type' => 'application/json; charset=utf-8']);
            }

            return Response::redirect('/docente/asistencia?error=' . urlencode($e->getMessage()));
        } catch (Throwable $e) {
            if ($this->isJsonRequest($request)) {
                return new Response((string) json_encode([
                    'exito' => false,
                    'error' => 'Ocurrió un error inesperado al procesar el marcaje.',
                ], JSON_UNESCAPED_UNICODE), 500, ['Content-Type' => 'application/json; charset=utf-8']);
            }

            return Response::redirect('/docente/asistencia?error=' . urlencode('Ocurrió un error inesperado al procesar el marcaje.'));
        }
    }

    private function isJsonRequest(Request $request): bool
    {
        $accept = (string) $request->header('Accept');
        $xReq = (string) $request->header('X-Requested-With');

        return str_contains($accept, 'application/json') || strtolower($xReq) === 'xmlhttprequest';
    }
}
