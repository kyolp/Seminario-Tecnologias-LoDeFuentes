# Configuración del Sistema — Fase 2

## Modelo de configuración

El sistema utiliza un esquema desacoplado de dos niveles:
1. **Archivo de entorno (`.env`):** Contiene los parámetros específicos del servidor o estación local (hosts, puertos, credenciales, entorno). Este archivo está estrictamente excluido del repositorio Git mediante `.gitignore`.
2. **Archivos de configuración (`config/*.php`):** Archivos versionados que definen la estructura formal de opciones, consumiendo las variables del entorno a través de la función auxiliar `env($clave, $defecto)`.

## Variables de entorno soportadas

| Variable | Tipo | Valor por defecto en `.env.example` | Descripción |
|---|---|---|---|
| `APP_NAME` | Cadena | "Sistema Web Escuela Lo de Fuentes" | Nombre descriptivo de la institución y sistema. |
| `APP_ENV` | Cadena | `development` | Entorno de ejecución (`development`, `testing`, `production`). |
| `APP_DEBUG` | Booleano | `true` | Habilita detalles de depuración interna. En producción debe ser `false`. |
| `APP_URL` | Cadena | `http://localhost/ProyectoFinal/public` | URL base para generar enlaces absolutos seguros. |
| `APP_TIMEZONE` | Cadena | `America/Guatemala` | Zona horaria oficial para registros de fecha y hora. |
| `DB_CONNECTION` | Cadena | `mysql` | Controlador de base de datos predeterminado. |
| `DB_HOST` | Cadena | `127.0.0.1` | Dirección IP o host del servidor MySQL. |
| `DB_PORT` | Entero | `3307` | Puerto de escucha del servicio MySQL. |
| `DB_DATABASE` | Cadena | `escuela_lo_de_fuentes` | Nombre de la base de datos relacional. |
| `DB_USERNAME` | Cadena | `root` | Nombre del usuario de conexión a la base de datos. |
| `DB_PASSWORD` | Cadena | *(vacío)* | Contraseña del usuario (nunca se almacena en el repositorio). |
| `DB_CHARSET` | Cadena | `utf8mb4` | Juego de caracteres obligatorio para compatibilidad Unicode. |
| `DB_COLLATION` | Cadena | `utf8mb4_0900_ai_ci` | Intercalación moderna de ordenamiento para MySQL 8. |

## Uso en el código

El acceso a la configuración se realiza mediante la clase `App\Core\Config` o la función helper `config()`:

```php
// Acceso a parámetros generales
$nombre = config('app.name');
$esDepuracion = config('app.debug', false);

// Acceso a parámetros de base de datos
$host = config('database.connections.mysql.host');
$puerto = config('database.connections.mysql.port');
```

## Aislamiento de secretos

1. **Exclusión en `.gitignore`:** Se asegura que ninguna regla permita subir `.env` o `.env.*` al repositorio.
2. **Plantilla segura (`.env.example`):** Proporciona la estructura exacta de configuración con valores ficticios o locales genéricos.
3. **Saneamiento en Logger:** La clase `App\Core\Logger` filtra automáticamente cualquier clave que contenga `password`, `token`, `secret` o `clave` antes de persistir información en `storage/logs/app.log`.
