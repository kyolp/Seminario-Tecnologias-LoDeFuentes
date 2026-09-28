<?php

declare(strict_types=1);

/**
 * Configuración general de la aplicación.
 * Prototipo: Escuela Oficial Rural Mixta Lo de Fuentes.
 */

return [
    'name' => env('APP_NAME', 'Sistema Web Escuela Lo de Fuentes'),
    'env' => env('APP_ENV', 'development'),
    'debug' => (bool) env('APP_DEBUG', true),
    'url' => rtrim(env('APP_URL', 'http://localhost/ProyectoFinal/public'), '/'),
    'timezone' => env('APP_TIMEZONE', 'America/Guatemala'),
];
