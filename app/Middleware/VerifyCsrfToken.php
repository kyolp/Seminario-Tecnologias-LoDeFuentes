<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

/**
 * Middleware de verificación de token CSRF para solicitudes mutantes (POST).
 */
class VerifyCsrfToken implements MiddlewareInterface
{
    /**
     * Interceptar y validar el token CSRF en peticiones POST.
     */
    public function handle(Request $request, callable $next): Response
    {
        if (strtoupper($request->getMethod()) === 'POST') {
            $token = $request->input('_token');

            if ($token === null || !is_string($token)) {
                $token = $request->getHeader('X-CSRF-TOKEN');
            }

            if (!Csrf::validate($token)) {
                try {
                    $html = View::render('errors.403', [
                        'title' => 'Petición Expirada - 403',
                        'message' => 'La sesión o el token de seguridad CSRF ha expirado o no es válido. Por favor, recargue el formulario e intente nuevamente.',
                    ], 'main');

                    return new Response($html, 403, ['Content-Type' => 'text/html; charset=utf-8']);
                } catch (\Throwable) {
                    return new Response('<h1>403 - Petición Expirada</h1><p>Token CSRF inválido.</p>', 403, [
                        'Content-Type' => 'text/html; charset=utf-8',
                    ]);
                }
            }
        }

        return $next($request);
    }
}
