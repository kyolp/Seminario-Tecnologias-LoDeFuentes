<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Config;
use App\Core\Env;
use App\Core\Response;
use App\Core\View;

/**
 * Obtener valor de variable de entorno con soporte de valor por defecto.
 */
function env(string $key, mixed $default = null): mixed
{
    return Env::get($key, $default);
}

/**
 * Obtener valor de configuración mediante notación de puntos (ej: 'app.name').
 */
function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

/**
 * Escapar cadena para salida segura en HTML previniendo XSS.
 */
function e(?string $value): string
{
    if ($value === null) {
        return '';
    }

    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Generar URL absoluta o relativa a la raíz pública de la aplicación.
 */
function url(string $path = ''): string
{
    $baseUrl = (string) Config::get('app.url', '');
    $trimmedPath = ltrim($path, '/');

    if ($trimmedPath === '') {
        return $baseUrl;
    }

    return $baseUrl . '/' . $trimmedPath;
}

/**
 * Redireccionar a una ruta específica.
 */
function redirect(string $path, int $status = 302): Response
{
    $targetUrl = filter_var($path, FILTER_VALIDATE_URL) ? $path : url($path);
    $response = new Response('', $status);
    $response->setHeader('Location', $targetUrl);
    return $response;
}

/**
 * Renderizar una vista dentro de un layout.
 *
 * @param array<string, mixed> $data
 */
function view(string $viewName, array $data = [], ?string $layout = 'main'): string
{
    return View::render($viewName, $data, $layout);
}

/**
 * Acceder a la instancia singleton de la aplicación.
 */
function app(): App
{
    return App::getInstance();
}

/**
 * Obtener el token CSRF activo para la sesión.
 */
function csrf_token(): string
{
    return \App\Core\Csrf::getToken();
}

/**
 * Generar campo de formulario HTML oculto para el token CSRF.
 */
function csrf_field(): string
{
    return \App\Core\Csrf::field();
}

/**
 * Acceder al servicio de autenticación centralizado.
 */
function auth(): \App\Services\AuthService
{
    static $auth = null;
    if ($auth === null) {
        $auth = new \App\Services\AuthService();
    }
    return $auth;
}

