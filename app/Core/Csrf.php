<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gestor de protección contra falsificación de peticiones en sitios cruzados (CSRF).
 * Genera y valida tokens criptográficos usando funciones nativas seguras.
 */
class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    /**
     * Generar un nuevo token CSRF o devolver el existente en la sesión.
     */
    public static function getToken(): string
    {
        $token = Session::get(self::SESSION_KEY);

        if (!is_string($token) || $token === '') {
            $token = self::generateToken();
        }

        return $token;
    }

    /**
     * Forzar la regeneración de un nuevo token CSRF.
     */
    public static function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set(self::SESSION_KEY, $token);
        return $token;
    }

    /**
     * Validar si un token proporcionado coincide con el de la sesión.
     * Utiliza hash_equals para evitar ataques basados en tiempo (timing attacks).
     */
    public static function validate(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $sessionToken = Session::get(self::SESSION_KEY);
        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Generar un campo HTML oculto con el token CSRF para formularios.
     */
    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
