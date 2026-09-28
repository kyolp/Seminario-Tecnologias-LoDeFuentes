<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

/**
 * Enrutador de solicitudes HTTP para métodos GET y POST con manejo de 404 y soporte de controladores.
 */
class Router
{
    /**
     * @var array<string, array<string, array{handler: mixed, middleware: array<mixed>}>>
     */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    /**
     * @var callable|null
     */
    private $notFoundHandler = null;

    /**
     * Registrar una ruta para método GET.
     *
     * @param array{0: class-string, 1: string}|Closure|callable $handler
     * @param array<mixed> $middleware
     */
    public function get(string $path, mixed $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Registrar una ruta para método POST.
     *
     * @param array{0: class-string, 1: string}|Closure|callable $handler
     * @param array<mixed> $middleware
     */
    public function post(string $path, mixed $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Registrar ruta genérica.
     *
     * @param array<mixed> $middleware
     */
    public function addRoute(string $method, string $path, mixed $handler, array $middleware = []): self
    {
        $normalizedPath = $this->normalizePath($path);
        $this->routes[strtoupper($method)][$normalizedPath] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];

        return $this;
    }

    /**
     * Definir manejador personalizado para ruta 404 No Encontrada.
     */
    public function setNotFoundHandler(callable $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    /**
     * Despachar la solicitud entrante hacia el controlador o función registrada.
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $this->normalizePath($request->getPath());

        $route = $this->routes[$method][$path] ?? null;

        if ($route === null) {
            return $this->handleNotFound($request);
        }

        $handler = $route['handler'];

        // Si el handler es un callable o Closure
        if ($handler instanceof Closure || is_callable($handler) && is_string($handler)) {
            $result = $handler($request);
            return $this->normalizeResponse($result);
        }

        // Si el handler es [Controller::class, 'method']
        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $action] = $handler;

            if (!class_exists($controllerClass)) {
                throw new RuntimeException("La clase del controlador no existe: [{$controllerClass}]");
            }

            $controller = new $controllerClass();

            if (!method_exists($controller, $action)) {
                throw new RuntimeException("El método [{$action}] no existe en el controlador [{$controllerClass}]");
            }

            $result = $controller->$action($request);
            return $this->normalizeResponse($result);
        }

        throw new RuntimeException("El manejador de la ruta [{$method} {$path}] no tiene un formato ejecutable válido.");
    }

    /**
     * Manejar respuesta 404 controlada.
     */
    private function handleNotFound(Request $request): Response
    {
        if ($this->notFoundHandler !== null) {
            $result = ($this->notFoundHandler)($request);
            return $this->normalizeResponse($result, 404);
        }

        // Intentar renderizar vista de error 404
        try {
            $html = View::render('errors.404', [
                'title' => 'Página no encontrada - 404',
                'path' => $request->getPath(),
            ], 'main');

            return new Response($html, 404, ['Content-Type' => 'text/html; charset=utf-8']);
        } catch (\Throwable) {
            return new Response('<h1>404 - Página no encontrada</h1><p>El recurso solicitado no existe.</p>', 404, [
                'Content-Type' => 'text/html; charset=utf-8',
            ]);
        }
    }

    /**
     * Normalizar cualquier resultado retornado por un controlador a una instancia de Response.
     */
    private function normalizeResponse(mixed $result, int $defaultStatus = 200): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_string($result)) {
            return new Response($result, $defaultStatus, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        if (is_array($result)) {
            return Response::json($result, $defaultStatus);
        }

        return new Response('', $defaultStatus);
    }

    /**
     * Normalizar formato de la ruta.
     */
    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '' ? '/' : $path;
    }

    /**
     * Obtener todas las rutas registradas (útil para auditoría o pruebas).
     *
     * @return array<string, array<string, array{handler: mixed, middleware: array<mixed>}>>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
