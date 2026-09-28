<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Controlador base que provee métodos comunes de respuesta y renderizado.
 */
abstract class Controller
{
    /**
     * Renderizar una vista y devolver una instancia de Response.
     *
     * @param array<string, mixed> $data
     */
    protected function render(string $viewName, array $data = [], ?string $layout = 'main', int $statusCode = 200): Response
    {
        $html = View::render($viewName, $data, $layout);
        return new Response($html, $statusCode, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    /**
     * Devolver una respuesta en formato JSON.
     */
    protected function json(mixed $data, int $statusCode = 200): Response
    {
        return Response::json($data, $statusCode);
    }

    /**
     * Devolver una redirección HTTP controlada.
     */
    protected function redirect(string $path, int $status = 302): Response
    {
        return redirect($path, $status);
    }
}
