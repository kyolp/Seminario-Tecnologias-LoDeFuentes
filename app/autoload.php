<?php

declare(strict_types=1);

/**
 * Autocargador PSR-4 nativo para el espacio de nombres App.
 * Prototipo: Escuela Oficial Rural Mixta Lo de Fuentes.
 */

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
