# Capa de Conexión PDO — Fase 2

## Propósito

Centralizar la interacción con el motor de base de datos MySQL 8.0.x mediante el estándar PDO de PHP, garantizando alta consistencia relacional, consultas nativas preparadas y protección absoluta contra la fuga de credenciales.

## Clase `App\Core\Database`

La clase `Database` actúa como fábrica centralizada y singleton para proveer conexiones PDO activas hacia el servidor MySQL:

```php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }
        return self::$instance;
    }
}
```

## Parámetros de configuración aplicados

La conexión se ensambla a partir de `config/database.php` con los siguientes valores estándar:

- **Controlador:** `mysql`
- **Host:** `127.0.0.1` (configurable vía `DB_HOST`)
- **Puerto:** `3307` o `3306` (configurable vía `DB_PORT`)
- **Base de datos:** `escuela_lo_de_fuentes` (configurable vía `DB_DATABASE`)
- **Juego de caracteres:** `utf8mb4` (soporte completo de caracteres y tildes en español)
- **Intercalación:** `utf8mb4_0900_ai_ci`

## Opciones obligatorias de PDO

| Opción de PDO | Valor | Justificación técnica |
|---|---|---|
| `PDO::ATTR_ERRMODE` | `PDO::ERRMODE_EXCEPTION` | Fuerza a que cualquier error de SQL lance excepciones interceptables de forma estricta. |
| `PDO::ATTR_DEFAULT_FETCH_MODE` | `PDO::FETCH_ASSOC` | Devuelve resultados indexados por nombre de columna, simplificando la manipulación de datos. |
| `PDO::ATTR_EMULATE_PREPARES` | `false` | Desactiva la emulación en PHP para que MySQL realice la preparación nativa en el servidor, blindando contra inyección SQL. |

## Protección y enmascaramiento de errores (T2-04)

Cuando la conexión falla (por credenciales inválidas, base inexistente o servicio caído):
1. El bloque `try / catch` captura la `PDOException`.
2. Se escribe un evento en `storage/logs/app.log` saneando cualquier mención a la contraseña.
3. Se lanza `App\Core\DatabaseException` con el mensaje neutro:
   `"No fue posible establecer la conexión con la base de datos."`
4. Ninguna vista, traza o salida al cliente HTTP incluye contraseñas, usuarios o la cadena de conexión DSN.
