<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gestor básico y seguro de registro de logs del sistema en disco.
 */
class Logger
{
    private static ?string $logPath = null;

    /**
     * Definir ruta de almacenamiento de logs.
     */
    public static function setLogPath(string $path): void
    {
        self::$logPath = rtrim($path, '/\\');
    }

    /**
     * Obtener ruta del archivo de log principal.
     */
    public static function getLogFile(): string
    {
        if (self::$logPath === null) {
            $baseStorage = defined('STORAGE_PATH') ? STORAGE_PATH : dirname(__DIR__, 2) . '/storage';
            self::$logPath = $baseStorage . '/logs';
        }

        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }

        return self::$logPath . '/app.log';
    }

    /**
     * Escribir entrada en el log.
     *
     * @param array<string, mixed> $context
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);

        // Sanear contexto para no registrar contraseñas o secretos
        $sanitizedContext = self::sanitizeContext($context);
        $contextString = !empty($sanitizedContext) ? ' ' . json_encode($sanitizedContext, JSON_UNESCAPED_SLASHES) : '';

        $entry = sprintf("[%s] [%s] %s%s\n", $timestamp, $levelUpper, $message, $contextString);

        @file_put_contents(self::getLogFile(), $entry, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    /**
     * Eliminar claves sensibles como 'password', 'clave_hash', 'secret', etc.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private static function sanitizeContext(array $context): array
    {
        $sensitiveKeys = ['password', 'pass', 'clave', 'clave_hash', 'token', 'secret'];
        $clean = [];

        foreach ($context as $k => $v) {
            $keyLower = strtolower((string) $k);
            $isSensitive = false;
            foreach ($sensitiveKeys as $sensitive) {
                if (str_contains($keyLower, $sensitive)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $clean[$k] = '********';
            } elseif (is_array($v)) {
                $clean[$k] = self::sanitizeContext($v);
            } else {
                $clean[$k] = $v;
            }
        }

        return $clean;
    }
}
