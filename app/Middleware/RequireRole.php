<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuthService;

/**
 * Middleware que autoriza el acceso según el rol institucional del usuario autenticado.
 * Retorna HTTP 403 con vista controlada ante accesos no autorizados.
 */
class RequireRole implements MiddlewareInterface
{
    private AuthService $auth;
    private string $requiredRole;

    public function __construct(string $requiredRole = 'ADMINISTRADOR', ?AuthService $auth = null)
    {
        $this->requiredRole = $requiredRole;
        $this->auth = $auth ?? new AuthService();
    }

    /**
     * Interceptar petición para verificar pertenencia al rol exigido.
     */
    public function handle(Request $request, callable $next): Response
    {
        $user = $this->auth->user();

        // Si no está autenticado, redirigir a login
        if ($user === null) {
            return Response::redirect('/login');
        }

        // Si no cuenta con el rol requerido, emitir HTTP 403 controlado
        if (!$user->hasRole($this->requiredRole)) {
            try {
                $html = View::render('errors.403', [
                    'title' => 'Acceso Denegado - 403',
                    'message' => 'No posee los privilegios suficientes para acceder a esta área institucional.',
                    'userRole' => $user->rol_nombre,
                    'requiredRole' => $this->requiredRole,
                ], 'main');

                return new Response($html, 403, ['Content-Type' => 'text/html; charset=utf-8']);
            } catch (\Throwable) {
                return new Response('<h1>403 - Acceso Denegado</h1><p>Privilegios insuficientes.</p>', 403, [
                    'Content-Type' => 'text/html; charset=utf-8',
                ]);
            }
        }

        return $next($request);
    }
}
