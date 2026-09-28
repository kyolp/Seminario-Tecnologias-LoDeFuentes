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

        $driver = $config['driver'] ?? 'mysql';
        $host = $config['host'] ?? '127.0.0.1';
        $port = (int) ($config['port'] ?? 3306);
        $dbname = $config['database'] ?? '';
        $username = (string) ($config['username'] ?? 'root');
        $password = (string) ($config['password'] ?? '');
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
     * Reiniciar instancia de conexión (útil para pruebas o cambio de configuración).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
