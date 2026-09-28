<?php

declare(strict_types=1);

/**
 * Punto de entrada único (Front Controller) para todas las solicitudes web.
 * Sistema Web — Escuela Oficial Rural Mixta Lo de Fuentes.
 */

// 1. Cargar arranque y dependencias del sistema
require_once dirname(__DIR__) . '/app/bootstrap.php';

// 2. Obtener instancia de la aplicación y procesar la solicitud
$app = App\Core\App::getInstance();
$response = $app->handle();

// 3. Emitir cabeceras y contenido HTTP
$response->send();
