<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Controlador para la página principal y estado técnico del prototipo.
 */
class HomeController extends BaseController
{
    /**
     * Página de inicio que muestra el estado de la arquitectura base.
     */
    public function index(Request $request): Response
    {
        $dbConnected = Database::testConnection();

        return $this->render('home.index', [
            'title' => 'Inicio - Plataforma Base',
            'phpVersion' => PHP_VERSION,
            'dbConnected' => $dbConnected,
            'environment' => (string) Config::get('app.env', 'development'),
            'debug' => (bool) Config::get('app.debug', false),
            'appName' => (string) Config::get('app.name', 'Sistema Web Escuela Lo de Fuentes'),
        ]);
    }

    /**
     * Endpoint técnico para verificación de estado y conectividad.
     */
    public function status(Request $request): Response
    {
        $dbConnected = Database::testConnection();

        return $this->json([
            'status' => 'operational',
            'phase' => 'Fase 2 - Estructura Base de la Aplicación',
            'php_version' => PHP_VERSION,
            'database_connected' => $dbConnected,
            'environment' => Config::get('app.env'),
            'timestamp' => date('c'),
        ]);
    }
}
