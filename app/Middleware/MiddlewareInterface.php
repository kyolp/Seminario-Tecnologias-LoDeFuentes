<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Interfaz base para componentes de middleware (preparada para control de autenticación y roles en Fase 3+).
 */
interface MiddlewareInterface
{
    /**
     * Procesar una solicitud HTTP y pasar el control al siguiente elemento de la cadena.
     *
     * @param callable(Request): Response $next
     */
    public function handle(Request $request, callable $next): Response;
}
