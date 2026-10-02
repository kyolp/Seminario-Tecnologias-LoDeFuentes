<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuthService;
use App\Services\UbicacionService;
use InvalidArgumentException;
use Throwable;

/**
 * Controlador administrativo para la configuración de ubicación institucional y radio (RF-18).
 * Acceso restringido exclusivamente a usuarios con rol ADMINISTRADOR.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 8).
 */
class AdminUbicacionController extends BaseController
{
    private AuthService $auth;
    private UbicacionService $ubicacionService;

    public function __construct(
        ?AuthService $auth = null,
        ?UbicacionService $ubicacionService = null
    ) {
        $this->auth = $auth ?? new AuthService();
        $this->ubicacionService = $ubicacionService ?? new UbicacionService();
    }

    /**
     * Listado y estado de la configuración de ubicación actual e historial.
     */
    public function index(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        if (!$user->hasRole('ADMINISTRADOR') && !$user->hasRole('ADMINISTRACION')) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'No cuenta con los privilegios administrativos requeridos para acceder a la configuración de ubicación.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'ADMINISTRADOR',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $configuracionActiva = $this->ubicacionService->obtenerConfiguracionActiva();
        $historial = $this->ubicacionService->obtenerHistorialConfiguraciones();

        $html = View::render('admin.ubicacion.index', [
            'title' => 'Ubicación Institucional y Geocerca — Administración',
            'user' => $user,
            'configuracionActiva' => $configuracionActiva,
            'historial' => $historial,
            'mensajeExito' => $request->query('msg'),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Formulario para definir o actualizar la ubicación geográfica institucional.
     */
    public function createForm(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        if (!$user->hasRole('ADMINISTRADOR') && !$user->hasRole('ADMINISTRACION')) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Acceso restringido a personal de administración.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'ADMINISTRADOR',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $configuracionActiva = $this->ubicacionService->obtenerConfiguracionActiva();

        $html = View::render('admin.ubicacion.configurar', [
            'title' => 'Configurar Ubicación Institucional — Administración',
            'user' => $user,
            'configuracionActiva' => $configuracionActiva,
            'errores' => [],
            'valores' => [
                'descripcion' => $configuracionActiva?->descripcion ?? 'Escuela Oficial Rural Mixta Lo de Fuentes',
                'latitud' => $configuracionActiva?->latitud ?? 14.6349150,
                'longitud' => $configuracionActiva?->longitud ?? -90.5068820,
                'radio_metros' => $configuracionActiva?->radio_metros ?? 100.0,
            ],
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Procesar y activar una nueva configuración geográfica institucional.
     */
    public function store(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        if (!$user->hasRole('ADMINISTRADOR') && !$user->hasRole('ADMINISTRACION')) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Acceso denegado.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'ADMINISTRADOR',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $descripcion = trim((string) $request->post('descripcion'));
        $latitud = $request->post('latitud');
        $longitud = $request->post('longitud');
        $radioMetros = $request->post('radio_metros');

        try {
            $this->ubicacionService->configurarNuevaUbicacion($user->id_usuario, [
                'descripcion' => $descripcion,
                'latitud' => $latitud,
                'longitud' => $longitud,
                'radio_metros' => $radioMetros,
            ]);

            return Response::redirect('/administracion/ubicacion?msg=' . urlencode('Ubicación institucional configurada y activada exitosamente.'));
        } catch (InvalidArgumentException $e) {
            $configuracionActiva = $this->ubicacionService->obtenerConfiguracionActiva();

            $html = View::render('admin.ubicacion.configurar', [
                'title' => 'Configurar Ubicación Institucional — Administración',
                'user' => $user,
                'configuracionActiva' => $configuracionActiva,
                'error' => $e->getMessage(),
                'valores' => [
                    'descripcion' => $descripcion,
                    'latitud' => $latitud,
                    'longitud' => $longitud,
                    'radio_metros' => $radioMetros,
                ],
            ], 'main');

            return new Response($html, 422, ['Content-Type' => 'text/html; charset=utf-8']);
        } catch (Throwable $e) {
            $configuracionActiva = $this->ubicacionService->obtenerConfiguracionActiva();

            $html = View::render('admin.ubicacion.configurar', [
                'title' => 'Configurar Ubicación Institucional — Administración',
                'user' => $user,
                'configuracionActiva' => $configuracionActiva,
                'error' => 'Error al guardar la configuración: ' . $e->getMessage(),
                'valores' => [
                    'descripcion' => $descripcion,
                    'latitud' => $latitud,
                    'longitud' => $longitud,
                    'radio_metros' => $radioMetros,
                ],
            ], 'main');

            return new Response($html, 500, ['Content-Type' => 'text/html; charset=utf-8']);
        }
    }
}
