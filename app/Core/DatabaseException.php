<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Throwable;

/**
 * Excepción controlada para errores de base de datos que garantiza la no exposición de credenciales.
 */
class DatabaseException extends RuntimeException
{
    private string $safeMessage;

    public function __construct(string $message = 'Error de conexión a la base de datos.', int $code = 0, ?Throwable $previous = null)
    {
        // Sanear el mensaje para remover cualquier contraseña o credencial antes de almacenar
        $this->safeMessage = self::sanitize($message);
        parent::__construct($this->safeMessage, $code, $previous);
    }

    public function getSafeMessage(): string
    {
        return $this->safeMessage;
    }

    /**
     * Remover patrones de contraseñas, usuarios o DSN completos del mensaje de error.
     */
    public static function sanitize(string $rawMessage): string
    {
        // Eliminar patrones como password=..., pwd=..., using password: YES/NO
        $sanitized = preg_replace('/(password|pwd|pass)\s*=\s*[^\s;]+/i', '$1=********', $rawMessage);
        $sanitized = preg_replace('/using password:\s*(yes|no)/i', 'using password: [PROTEGIDO]', (string) $sanitized);
        $sanitized = preg_replace('/user\s*=\s*[^\s;]+/i', 'user=[PROTEGIDO]', (string) $sanitized);

        return (string) $sanitized;
    }
}
