<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Repositorio de configuración centralizada de la aplicación con soporte para notación de puntos.
 */
class Config
{
    /**
     * @var array<string, mixed>
     */
    private static array $items = [];

    /**
     * Cargar todos los archivos de configuración desde el directorio especificado.
     */
    public static function load(string $configDir): void
    {
        if (!is_dir($configDir)) {
            return;
        }

        $files = glob($configDir . '/*.php');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $key = basename($file, '.php');
            $data = require $file;
            if (is_array($data)) {
                self::$items[$key] = $data;
            }
        }
    }

    /**
     * Obtener un valor de configuración usando notación de puntos (ej: 'database.connections.mysql.host').
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $current = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * Establecer o sobreescribir un valor de configuración en tiempo de ejecución.
     */
    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $current = &self::$items;

        foreach ($segments as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current = $value;
    }

    /**
     * Obtener todos los elementos de configuración.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return self::$items;
    }

    /**
     * Reiniciar la configuración (útil para pruebas).
     */
    public static function reset(): void
    {
        self::$items = [];
    }
}
