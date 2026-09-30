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
     * Reinicializar rutas según el entorno actual (útil para pruebas).
     */
    public function reboot(): self
    {
        $this->router->clearRoutes();
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
     * Registrar las rutas base de infraestructura y módulos autorizados.
     */
    public function registerRoutes(): void
    {
        // 1. Rutas públicas de bienvenida y estado del sistema
        $this->router->get('/', [\App\Controllers\HomeController::class, 'index']);
        $this->router->get('/status', [\App\Controllers\HomeController::class, 'status']);

        // 2. Rutas de autenticación (Fase 3)
        $this->router->get('/login', [\App\Controllers\AuthController::class, 'showLoginForm']);
        $this->router->post('/login', [\App\Controllers\AuthController::class, 'login'], [
            \App\Middleware\VerifyCsrfToken::class,
        ]);
        $this->router->post('/logout', [\App\Controllers\AuthController::class, 'logout'], [
            \App\Middleware\VerifyCsrfToken::class,
        ]);

        // 3. Rutas privadas autorizadas por roles (Fase 3)
        $this->router->get('/administracion', [\App\Controllers\AdminController::class, 'index'], [
            \App\Middleware\Authenticate::class,
            new \App\Middleware\RequireRole('ADMINISTRADOR'),
        ]);

        $this->router->get('/docente', [\App\Controllers\DocenteController::class, 'index'], [
            \App\Middleware\Authenticate::class,
            new \App\Middleware\RequireRole('DOCENTE'),
        ]);

        // 4. Módulos de Administración Básica (Fase 4)
        $adminGet = [
            \App\Middleware\Authenticate::class,
            new \App\Middleware\RequireRole('ADMINISTRADOR'),
        ];
        $adminPost = [
            \App\Middleware\Authenticate::class,
            new \App\Middleware\RequireRole('ADMINISTRADOR'),
            \App\Middleware\VerifyCsrfToken::class,
        ];

        // Docentes
        $this->router->get('/administracion/docentes', [\App\Controllers\AdminDocenteController::class, 'index'], $adminGet);
        $this->router->get('/administracion/docentes/ver', [\App\Controllers\AdminDocenteController::class, 'show'], $adminGet);
        $this->router->get('/administracion/docentes/crear', [\App\Controllers\AdminDocenteController::class, 'createForm'], $adminGet);
        $this->router->post('/administracion/docentes/crear', [\App\Controllers\AdminDocenteController::class, 'store'], $adminPost);
        $this->router->get('/administracion/docentes/editar', [\App\Controllers\AdminDocenteController::class, 'editForm'], $adminGet);
        $this->router->post('/administracion/docentes/editar', [\App\Controllers\AdminDocenteController::class, 'update'], $adminPost);
        $this->router->post('/administracion/docentes/estado', [\App\Controllers\AdminDocenteController::class, 'toggleEstado'], $adminPost);
        $this->router->post('/administracion/docentes/eliminar', [\App\Controllers\AdminDocenteController::class, 'delete'], $adminPost);

        // Estudiantes
        $this->router->get('/administracion/estudiantes', [\App\Controllers\AdminEstudianteController::class, 'index'], $adminGet);
        $this->router->get('/administracion/estudiantes/ver', [\App\Controllers\AdminEstudianteController::class, 'show'], $adminGet);
        $this->router->get('/administracion/estudiantes/crear', [\App\Controllers\AdminEstudianteController::class, 'createForm'], $adminGet);
        $this->router->post('/administracion/estudiantes/crear', [\App\Controllers\AdminEstudianteController::class, 'store'], $adminPost);
        $this->router->get('/administracion/estudiantes/editar', [\App\Controllers\AdminEstudianteController::class, 'editForm'], $adminGet);
        $this->router->post('/administracion/estudiantes/editar', [\App\Controllers\AdminEstudianteController::class, 'update'], $adminPost);
        $this->router->post('/administracion/estudiantes/estado', [\App\Controllers\AdminEstudianteController::class, 'toggleEstado'], $adminPost);
        $this->router->post('/administracion/estudiantes/eliminar', [\App\Controllers\AdminEstudianteController::class, 'delete'], $adminPost);

        // Períodos Escolares
        $this->router->get('/administracion/periodos', [\App\Controllers\AdminPeriodoController::class, 'index'], $adminGet);
        $this->router->get('/administracion/periodos/ver', [\App\Controllers\AdminPeriodoController::class, 'show'], $adminGet);
        $this->router->get('/administracion/periodos/crear', [\App\Controllers\AdminPeriodoController::class, 'createForm'], $adminGet);
        $this->router->post('/administracion/periodos/crear', [\App\Controllers\AdminPeriodoController::class, 'store'], $adminPost);
        $this->router->get('/administracion/periodos/editar', [\App\Controllers\AdminPeriodoController::class, 'editForm'], $adminGet);
        $this->router->post('/administracion/periodos/editar', [\App\Controllers\AdminPeriodoController::class, 'update'], $adminPost);
        $this->router->post('/administracion/periodos/estado', [\App\Controllers\AdminPeriodoController::class, 'toggleEstado'], $adminPost);
        $this->router->post('/administracion/periodos/eliminar', [\App\Controllers\AdminPeriodoController::class, 'delete'], $adminPost);

        // Grados
        $this->router->get('/administracion/grados', [\App\Controllers\AdminGradoController::class, 'index'], $adminGet);
        $this->router->get('/administracion/grados/crear', [\App\Controllers\AdminGradoController::class, 'createForm'], $adminGet);
        $this->router->post('/administracion/grados/crear', [\App\Controllers\AdminGradoController::class, 'store'], $adminPost);
        $this->router->get('/administracion/grados/editar', [\App\Controllers\AdminGradoController::class, 'editForm'], $adminGet);
        $this->router->post('/administracion/grados/editar', [\App\Controllers\AdminGradoController::class, 'update'], $adminPost);
        $this->router->post('/administracion/grados/estado', [\App\Controllers\AdminGradoController::class, 'toggleActivo'], $adminPost);
        $this->router->post('/administracion/grados/eliminar', [\App\Controllers\AdminGradoController::class, 'delete'], $adminPost);

        // Grados por Período
        $this->router->get('/administracion/grados-periodos', [\App\Controllers\AdminGradoPeriodoController::class, 'index'], $adminGet);
        $this->router->get('/administracion/grados-periodos/crear', [\App\Controllers\AdminGradoPeriodoController::class, 'createForm'], $adminGet);
        $this->router->post('/administracion/grados-periodos/crear', [\App\Controllers\AdminGradoPeriodoController::class, 'store'], $adminPost);
        $this->router->post('/administracion/grados-periodos/estado', [\App\Controllers\AdminGradoPeriodoController::class, 'toggleHabilitado'], $adminPost);
        $this->router->post('/administracion/grados-periodos/eliminar', [\App\Controllers\AdminGradoPeriodoController::class, 'delete'], $adminPost);

        // Secciones
        $this->router->get('/administracion/secciones', [\App\Controllers\AdminSeccionController::class, 'index'], $adminGet);
        $this->router->get('/administracion/secciones/ver', [\App\Controllers\AdminSeccionController::class, 'show'], $adminGet);
        $this->router->get('/administracion/secciones/crear', [\App\Controllers\AdminSeccionController::class, 'createForm'], $adminGet);
        $this->router->post('/administracion/secciones/crear', [\App\Controllers\AdminSeccionController::class, 'store'], $adminPost);
        $this->router->get('/administracion/secciones/editar', [\App\Controllers\AdminSeccionController::class, 'editForm'], $adminGet);
        $this->router->post('/administracion/secciones/editar', [\App\Controllers\AdminSeccionController::class, 'update'], $adminPost);
        $this->router->post('/administracion/secciones/estado', [\App\Controllers\AdminSeccionController::class, 'toggleHabilitada'], $adminPost);
        $this->router->post('/administracion/secciones/eliminar', [\App\Controllers\AdminSeccionController::class, 'delete'], $adminPost);

        // Aulas
        $this->router->get('/administracion/aulas', [\App\Controllers\AdminAulaController::class, 'index'], $adminGet);
        $this->router->get('/administracion/aulas/crear', [\App\Controllers\AdminAulaController::class, 'createForm'], $adminGet);
        $this->router->post('/administracion/aulas/crear', [\App\Controllers\AdminAulaController::class, 'store'], $adminPost);
        $this->router->get('/administracion/aulas/editar', [\App\Controllers\AdminAulaController::class, 'editForm'], $adminGet);
        $this->router->post('/administracion/aulas/editar', [\App\Controllers\AdminAulaController::class, 'update'], $adminPost);
        $this->router->post('/administracion/aulas/estado', [\App\Controllers\AdminAulaController::class, 'toggleActiva'], $adminPost);
        $this->router->post('/administracion/aulas/toggle', [\App\Controllers\AdminAulaController::class, 'toggleActiva'], $adminPost);
        $this->router->post('/administracion/aulas/eliminar', [\App\Controllers\AdminAulaController::class, 'delete'], $adminPost);

        // 5. Rutas técnicas de prueba: aisladas exclusivamente para entornos de desarrollo y testing
        $env = (string) Config::get('app.env', 'development');
        if (in_array(strtolower($env), ['development', 'testing', 'local'], true)) {
            $this->router->get('/test/get', [\App\Controllers\TestController::class, 'testGet']);
            $this->router->post('/test/post', [\App\Controllers\TestController::class, 'testPost']);
        }
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
