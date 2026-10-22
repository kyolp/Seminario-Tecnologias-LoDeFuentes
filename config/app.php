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
    'attendance_allowed_networks' => env('ATTENDANCE_ALLOWED_NETWORKS', env('APP_ENV') === 'development' || env('APP_ENV') === 'testing' ? '127.0.0.1/32,::1/128' : ''),
];
