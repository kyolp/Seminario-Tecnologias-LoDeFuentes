<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gestor centralizado y seguro de sesiones nativas de PHP.
 * Prototipo: Escuela Oficial Rural Mixta Lo de Fuentes.
 */
class Session
{
    private static bool $started = false;
    private static ?string $currentId = null;

    /**
     * Iniciar la sesión PHP con directivas estrictas de seguridad.
     */
    public static function start(): void
    {
        if (self::$started && session_status() === PHP_SESSION_ACTIVE) {
            if (!isset($_SESSION) || !is_array($_SESSION)) {
                $_SESSION = [];
            }
            if (self::$currentId === null) {
                $nativeId = session_id();
                self::$currentId = !empty($nativeId) ? $nativeId : bin2hex(random_bytes(16));
            }
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            // Configuración de directivas seguras de cookies antes de iniciar
            if (!headers_sent()) {
                $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

                // En desarrollo local (HTTP) $isHttps es false; en producción HTTPS será true
                $secureCookie = $isHttps && (Config::get('app.env') === 'production');

                session_set_cookie_params([
                    'lifetime' => 0, // Expira al cerrar el navegador
                    'path' => '/',
                    'domain' => '',
                    'secure' => $secureCookie,
                    'httponly' => true, // Mitigación XSS: inaccesible para JavaScript
                    'samesite' => 'Lax', // Mitigación CSRF
                ]);

                ini_set('session.use_strict_mode', '1');
                ini_set('session.use_only_cookies', '1');
                ini_set('session.cookie_httponly', '1');
            }

            @session_start();
        }

        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }

        $nativeId = session_id();
        self::$currentId = !empty($nativeId) ? $nativeId : (self::$currentId ?? bin2hex(random_bytes(16)));
        self::$started = true;
    }

    /**
     * Obtener el identificador actual de sesión.
     */
    public static function id(): string
    {
        self::start();
        $nativeId = session_id();
        if (!empty($nativeId)) {
            self::$currentId = $nativeId;
            return $nativeId;
        }

        if (self::$currentId === null) {
            self::$currentId = bin2hex(random_bytes(16));
        }

        return self::$currentId;
    }

    /**
     * Establecer manualmente el ID de sesión (útil para pruebas o cambio controlado).
     */
    public static function setId(string $id): void
    {
        self::$currentId = $id;

        if (!headers_sent()) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                @session_write_close();
                self::$started = false;
            }
            @session_id($id);
            self::start();
        }
    }

    /**
     * Regenerar el identificador de sesión para mitigar Session Fixation.
     */
    public static function regenerate(bool $deleteOldSession = true): bool
    {
        self::start();
        $previousId = self::$currentId;

        if (!headers_sent()) {
            $result = @session_regenerate_id($deleteOldSession);
            $newNative = session_id();
            if (!empty($newNative) && $newNative !== $previousId) {
                self::$currentId = $newNative;
                return $result;
            }
        }

        // En entornos CLI donde las cabeceras ya se enviaron, generar un nuevo identificador criptográfico
        self::$currentId = bin2hex(random_bytes(16));
        return true;
    }

    /**
     * Obtener un valor de la sesión.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return (isset($_SESSION) && is_array($_SESSION)) ? ($_SESSION[$key] ?? $default) : $default;
    }

    /**
     * Establecer un valor en la sesión.
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }
        $_SESSION[$key] = $value;
    }

    /**
     * Verificar si existe una clave en la sesión.
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION) && is_array($_SESSION) && array_key_exists($key, $_SESSION);
    }

    /**
     * Eliminar una clave de la sesión.
     */
    public static function remove(string $key): void
    {
        self::start();
        if (isset($_SESSION) && is_array($_SESSION)) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Establecer un mensaje flash que dura solo una solicitud.
     */
    public static function setFlash(string $key, mixed $value): void
    {
        self::start();
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Helper dual para establecer u obtener un mensaje flash.
     */
    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            self::setFlash($key, $value);
            return null;
        }

        return self::getFlash($key);
    }

    /**
     * Obtener y eliminar un mensaje flash.
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::start();
        if (isset($_SESSION['_flash'][$key])) {
            $value = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $value;
        }
        return $default;
    }

    /**
     * Destruir la sesión completamente e invalidar cookie.
     */
    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        self::$currentId = null;

        if (!headers_sent() && ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            @setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
        }

        self::$started = false;
    }

    /**
     * Limpiar estado en memoria (útil entre pruebas).
     */
    public static function reset(): void
    {
        $_SESSION = [];
        self::$currentId = null;
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
        }
        self::$started = false;
    }
}
