<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Capa centralizada de conexión a base de datos MySQL mediante PDO.
 * Prototipo: Escuela Oficial Rural Mixta Lo de Fuentes.
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Obtener instancia única de conexión PDO.
     *
     * @throws DatabaseException
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    /**
     * Crear una nueva conexión PDO utilizando la configuración o parámetros provistos.
     *
     * @param array<string, mixed>|null $config
     * @throws DatabaseException
     */
    public static function createConnection(?array $config = null): PDO
    {
        if ($config === null) {
            $defaultConn = (string) Config::get('database.default', 'mysql');
            $config = (array) Config::get("database.connections.{$defaultConn}", []);
        }

        // Validación estricta fail-fast de parámetros obligatorios
        self::validateConfig($config);

        $driver = $config['driver'] ?? 'mysql';
        $host = (string) $config['host'];
        $port = (int) $config['port'];
        $dbname = (string) $config['database'];
        $username = (string) $config['username'];
        $password = (string) $config['password'];
        $charset = $config['charset'] ?? 'utf8mb4';

        $dsn = "{$driver}:host={$host};port={$port};charset={$charset}";
        if ($dbname !== '') {
            $dsn .= ";dbname={$dbname}";
        }

        $options = $config['options'] ?? [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, $username, $password, $options);
            return $pdo;
        } catch (PDOException $e) {
            // Registrar el error en logs internos de forma segura
            Logger::error('Fallo de conexión a base de datos: ' . DatabaseException::sanitize($e->getMessage()), [
                'driver' => $driver,
                'host' => $host,
                'port' => $port,
                'database' => $dbname,
                'code' => $e->getCode(),
            ]);

            // Lanzar excepción controlada que nunca expone contraseñas al usuario final
            throw new DatabaseException(
                'No fue posible establecer la conexión con la base de datos.',
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Probar si una conexión es válida y operativa.
     *
     * @param array<string, mixed>|null $config
     */
    public static function testConnection(?array $config = null): bool
    {
        try {
            $pdo = self::createConnection($config);
            $stmt = $pdo->query('SELECT 1');
            return $stmt !== false && $stmt->fetchColumn() !== false;
        } catch (DatabaseException) {
            return false;
        }
    }

    /**
     * Validar estrictamente la presencia de parámetros obligatorios de conexión.
     *
     * @param array<string, mixed> $config
     * @throws DatabaseException
     */
    public static function validateConfig(array $config): void
    {
        $mandatory = [
            'host' => 'DB_HOST',
            'port' => 'DB_PORT',
            'database' => 'DB_DATABASE',
            'username' => 'DB_USERNAME',
        ];

        foreach ($mandatory as $field => $envVar) {
            if (!isset($config[$field]) || $config[$field] === '' || $config[$field] === null) {
                throw new DatabaseException(
                    "Configuración obligatoria de base de datos ausente: [{$envVar}]. Defina la variable en su archivo .env.",
                    500
                );
            }
        }

        $port = (int) $config['port'];
        if ($port <= 0 || $port > 65535) {
            throw new DatabaseException(
                "El parámetro de configuración [DB_PORT] debe ser un puerto numérico válido (1-65535).",
                500
            );
        }

        if (!array_key_exists('password', $config)) {
            throw new DatabaseException(
                "Configuración obligatoria de base de datos ausente: [DB_PASSWORD]. Defina la variable en su archivo .env.",
                500
            );
        }
    }

    /**
     * Reiniciar instancia de conexión (útil para pruebas o cambio de configuración).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
