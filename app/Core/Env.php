<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gestor y cargador de variables de entorno desde archivo .env.
 */
class Env
{
    /**
     * @var array<string, mixed>
     */
    private static array $variables = [];

    /**
     * Cargar y parsear archivo .env si existe.
     */
    public static function load(string $filePath): void
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignorar líneas vacías o comentarios
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Separar clave=valor
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $val = trim($parts[1]);

            // Eliminar comillas envolventes si existen
            if (
                (str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'"))
            ) {
                $val = substr($val, 1, -1);
            }

            // Normalizar valores booleanos y nulos
            $parsedVal = match (strtolower($val)) {
                'true', '(true)' => true,
                'false', '(false)' => false,
                'null', '(null)', 'empty' => null,
                default => $val,
            };

            self::$variables[$key] = $parsedVal;
            $_ENV[$key] = $parsedVal;
            $_SERVER[$key] = $parsedVal;
            putenv("{$key}={$val}");
        }
    }

    /**
     * Obtener valor de variable de entorno con fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$variables)) {
            return self::$variables[$key];
        }

        $envVal = getenv($key);
        if ($envVal !== false) {
            return match (strtolower($envVal)) {
                'true', '(true)' => true,
                'false', '(false)' => false,
                'null', '(null)', 'empty' => null,
                default => $envVal,
            };
        }

        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        return $default;
    }

    /**
     * Limpiar variables cargadas (útil para pruebas).
     */
    public static function reset(): void
    {
        self::$variables = [];
    }
}
