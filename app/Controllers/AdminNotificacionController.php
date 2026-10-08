<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuthService;
use App\Services\NotificacionService;
use DomainException;
use Throwable;

/**
 * Controlador para la visualización y gestión de notificaciones administrativas.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 10 — RF-26).
 */
class AdminNotificacionController extends BaseController
{
    private AuthService $auth;
    private NotificacionService $notificacionService;

    public function __construct(
        ?AuthService $auth = null,
        ?NotificacionService $notificacionService = null
    ) {
        $this->auth = $auth ?? new AuthService();
        $this->notificacionService = $notificacionService ?? new NotificacionService();
    }

    /**
     * Bandeja de notificaciones del usuario administrador (RF-26, DT-65).
     */
    public function index(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            return Response::redirect('/login');
        }

        if (!$user->hasRole('ADMINISTRADOR') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Acceso restringido a personal con rol administrativo.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'ADMINISTRADOR',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        // Filtro: todas, no_leidas, leidas
        $filtro = strtolower(trim((string) ($request->query('filtro') ?? 'todas')));
        $soloNoLeidas = null;
        if ($filtro === 'no_leidas') {
            $soloNoLeidas = true;
        } elseif ($filtro === 'leidas') {
            $soloNoLeidas = false;
        }

        $limite = 50;
        $notificaciones = $this->notificacionService->obtenerNotificacionesUsuario(
            $user->id_usuario,
            $soloNoLeidas,
            $limite
        );

        $conteoNoLeidas = $this->notificacionService->obtenerConteoNoLeidas($user->id_usuario);

        if ($this->isJsonRequest($request)) {
            $data = array_map(fn($n) => $n->toArray(), $notificaciones);
            return Response::json([
                'exito' => true,
                'conteo_no_leidas' => $conteoNoLeidas,
                'notificaciones' => $data,
            ]);
        }

        $html = View::render('admin.notificaciones.index', [
            'title' => 'Notificaciones Administrativas — Sistema Escolar',
            'user' => $user,
            'notificaciones' => $notificaciones,
            'conteoNoLeidas' => $conteoNoLeidas,
            'filtro' => $filtro,
            'mensajeExito' => $request->query('msg'),
            'mensajeError' => $request->query('error'),
        ], 'main');

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Marcar una notificación individual como leída (RF-26, DT-66).
     */
    public function marcarLeida(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            if ($this->isJsonRequest($request)) {
                return Response::json(['exito' => false, 'error' => 'No autenticado'], 401);
            }
            return Response::redirect('/login');
        }

        if (!$user->hasRole('ADMINISTRADOR') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Acceso restringido a administradores.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'ADMINISTRADOR',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        $idNotificacion = (int) ($request->post('id_notificacion') ?? $request->post('id') ?? 0);
        if ($idNotificacion <= 0) {
            if ($this->isJsonRequest($request)) {
                return Response::json(['exito' => false, 'error' => 'Identificador de notificación inválido.'], 422);
            }
            return Response::redirect('/administracion/notificaciones?error=' . urlencode('Identificador inválido.'));
        }

        try {
            $this->notificacionService->marcarLeida($idNotificacion, $user->id_usuario);

            if ($this->isJsonRequest($request)) {
                return Response::json([
                    'exito' => true,
                    'mensaje' => 'Notificación marcada como leída.',
                    'id_notificacion' => $idNotificacion,
                ]);
            }

            return Response::redirect('/administracion/notificaciones?msg=' . urlencode('Notificación marcada como leída.'));
        } catch (DomainException $e) {
            if ($this->isJsonRequest($request)) {
                return Response::json(['exito' => false, 'error' => $e->getMessage()], 403);
            }
            return Response::redirect('/administracion/notificaciones?error=' . urlencode($e->getMessage()));
        } catch (Throwable $e) {
            if ($this->isJsonRequest($request)) {
                return Response::json(['exito' => false, 'error' => 'Error al actualizar notificación.'], 500);
            }
            return Response::redirect('/administracion/notificaciones?error=' . urlencode('Ocurrió un error inesperado.'));
        }
    }

    /**
     * Marcar todas las notificaciones pendientes del usuario como leídas.
     */
    public function marcarTodasLeidas(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null) {
            if ($this->isJsonRequest($request)) {
                return Response::json(['exito' => false, 'error' => 'No autenticado'], 401);
            }
            return Response::redirect('/login');
        }

        if (!$user->hasRole('ADMINISTRADOR') || !$user->isActive()) {
            return new Response(View::render('errors.403', [
                'title' => 'Acceso Denegado - 403',
                'message' => 'Acceso restringido a administradores.',
                'userRole' => $user->rol_nombre,
                'requiredRole' => 'ADMINISTRADOR',
            ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        try {
            $totalActualizadas = $this->notificacionService->marcarTodasLeidas($user->id_usuario);

            if ($this->isJsonRequest($request)) {
                return Response::json([
                    'exito' => true,
                    'mensaje' => sprintf('%d notificaciones marcadas como leídas.', $totalActualizadas),
                    'actualizadas' => $totalActualizadas,
                ]);
            }

            return Response::redirect('/administracion/notificaciones?msg=' . urlencode(sprintf('%d notificaciones marcadas como leídas.', $totalActualizadas)));
        } catch (Throwable $e) {
            if ($this->isJsonRequest($request)) {
                return Response::json(['exito' => false, 'error' => 'Error al procesar la solicitud.'], 500);
            }
            return Response::redirect('/administracion/notificaciones?error=' . urlencode('Ocurrió un error al actualizar las notificaciones.'));
        }
    }

    /**
     * Obtener conteo de no leídas en JSON para componentes dinámicos.
     */
    public function conteoNoLeidas(Request $request): Response
    {
        $user = $this->auth->user();
        if ($user === null || !$user->hasRole('ADMINISTRADOR') || !$user->isActive()) {
            return Response::json([
                'exito' => false,
                'success' => false,
                'no_leidas' => 0,
                'unread_count' => 0,
            ], 403);
        }

        $conteo = $this->notificacionService->obtenerConteoNoLeidas($user->id_usuario);

        return Response::json([
            'exito' => true,
            'success' => true,
            'no_leidas' => $conteo,
            'unread_count' => $conteo,
        ]);
    }

    private function isJsonRequest(Request $request): bool
    {
        $accept = (string) $request->header('Accept');
        $xReq = (string) $request->header('X-Requested-With');

        return str_contains($accept, 'application/json') || strtolower($xReq) === 'xmlhttprequest';
    }
}

