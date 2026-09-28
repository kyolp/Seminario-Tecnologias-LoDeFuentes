<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Orquestador principal de la aplicación web.
 * Maneja el ciclo de vida de la solicitud, router, captura de errores y envío de respuesta.
 */
class App
{
    private static ?App $instance = null;
    private Router $router;
    private bool $booted = false;

    public function __construct()
    {
        $this->router = new Router();
    }

    /**
     * Obtener instancia singleton de la aplicación.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Inicializar dependencias y configuraciones base.
     */
    public function boot(): self
    {
        if ($this->booted) {
            return $this;
        }

        // Cargar rutas del sistema
        $this->registerRoutes();

        $this->booted = true;
        return $this;
    }

    /**
     * Obtener el enrutador.
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Ejecutar la aplicación procesando la solicitud HTTP.
     */
    public function handle(?Request $request = null): Response
    {
        $this->boot();

        if ($request === null) {
            $request = Request::createFromGlobals();
        }

        try {
            return $this->router->dispatch($request);
        } catch (DatabaseException $e) {
            // Error controlado de base de datos que nunca expone contraseñas
            Logger::error('Error de base de datos capturado en App: ' . $e->getSafeMessage());

            return $this->renderDatabaseError($e);
        } catch (Throwable $e) {
            // Error interno no controlado
            Logger::error('Excepción interna en la aplicación: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->renderInternalError($e);
        }
    }

    /**
     * Registrar las rutas base de infraestructura para la Fase 2.
     */
    private function registerRoutes(): void
    {
        // Ruta principal de bienvenida y estado del sistema
        $this->router->get('/', [\App\Controllers\HomeController::class, 'index']);

        // Rutas de infraestructura técnica y pruebas obligatorias
        $this->router->get('/status', [\App\Controllers\HomeController::class, 'status']);
        $this->router->get('/test/get', [\App\Controllers\TestController::class, 'testGet']);
        $this->router->post('/test/post', [\App\Controllers\TestController::class, 'testPost']);
    }

    /**
     * Renderizar página segura de error de base de datos.
     */
    private function renderDatabaseError(DatabaseException $e): Response
    {
        try {
            $html = View::render('errors.database', [
                'title' => 'Servicio temporalmente no disponible',
                'message' => $e->getSafeMessage(),
            ], 'main');

            return new Response($html, 503, ['Content-Type' => 'text/html; charset=utf-8']);
        } catch (Throwable) {
            return new Response('<h1>Error del Sistema</h1><p>No fue posible conectar con la base de datos.</p>', 503, [
                'Content-Type' => 'text/html; charset=utf-8',
            ]);
        }
    }

    /**
     * Renderizar página segura de error 500 interno.
     */
    private function renderInternalError(Throwable $e): Response
    {
        $isDebug = (bool) Config::get('app.debug', false);

        try {
            $html = View::render('errors.500', [
                'title' => 'Error Interno del Servidor - 500',
                'message' => $isDebug ? e($e->getMessage()) : 'Ha ocurrido un error inesperado en el servidor.',
                'debug' => $isDebug,
                'file' => $isDebug ? $e->getFile() : null,
                'line' => $isDebug ? $e->getLine() : null,
            ], 'main');

            return new Response($html, 500, ['Content-Type' => 'text/html; charset=utf-8']);
        } catch (Throwable) {
            return new Response('<h1>500 - Error Interno</h1><p>Ocurrió un error en el servidor.</p>', 500, [
                'Content-Type' => 'text/html; charset=utf-8',
            ]);
        }
    }

    /**
     * Reiniciar instancia para pruebas.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
