<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Response;

/**
 * Controlador base del prototipo institucional.
 */
abstract class BaseController extends Controller
{
    /**
     * Renderizar vista inyectando metadatos comunes institucionales.
     *
     * @param array<string, mixed> $data
     */
    protected function render(string $viewName, array $data = [], ?string $layout = 'main', int $statusCode = 200): Response
    {
        $defaultData = [
            'appName' => Config::get('app.name', 'Escuela Oficial Rural Mixta Lo de Fuentes'),
            'currentYear' => date('Y'),
        ];

        $mergedData = array_merge($defaultData, $data);
        return parent::render($viewName, $mergedData, $layout, $statusCode);
    }
}
