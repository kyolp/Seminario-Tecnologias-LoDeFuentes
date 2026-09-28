<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

/**
 * Middleware que verifica que la solicitud provenga de un usuario autenticado y activo.
 */
class Authenticate implements MiddlewareInterface
{
    private AuthService $auth;

    public function __construct(?AuthService $auth = null)
    {
        $this->auth = $auth ?? new AuthService();
    }

    /**
     * Interceptar petición para verificar sesión válida.
     */
    public function handle(Request $request, callable $next): Response
    {
        if (!$this->auth->check()) {
            // Guardar mensaje flash para informar al usuario tras la redirección
            Session::setFlash('error', 'Debe iniciar sesión para acceder a esta área.');

            return Response::redirect('/login');
        }

        return $next($request);
    }
}
