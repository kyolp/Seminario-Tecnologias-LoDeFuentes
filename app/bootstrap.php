<?php

declare(strict_types=1);

/**
 * Archivo de arranque e inicialización de la aplicación.
 * Prototipo: Escuela Oficial Rural Mixta Lo de Fuentes.
 */

// 1. Definición de rutas base del proyecto
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__DIR__));
defined('APP_PATH') or define('APP_PATH', ROOT_PATH . '/app');
defined('CONFIG_PATH') or define('CONFIG_PATH', ROOT_PATH . '/config');
defined('VIEW_PATH') or define('VIEW_PATH', ROOT_PATH . '/views');
defined('STORAGE_PATH') or define('STORAGE_PATH', ROOT_PATH . '/storage');
defined('PUBLIC_PATH') or define('PUBLIC_PATH', ROOT_PATH . '/public');

// 2. Registro del autocargador PSR-4
require_once APP_PATH . '/autoload.php';

// 3. Carga de funciones auxiliares globales
require_once APP_PATH . '/Helpers/helpers.php';

// 4. Carga de variables de entorno desde .env
App\Core\Env::load(ROOT_PATH . '/.env');

// 5. Carga de archivos de configuración
App\Core\Config::load(CONFIG_PATH);

// 6. Configuración de zona horaria
$timezone = (string) App\Core\Config::get('app.timezone', 'America/Guatemala');
date_default_timezone_set($timezone);

// 7. Configuración de directorio de logs
App\Core\Logger::setLogPath(STORAGE_PATH . '/logs');

// 8. Manejo de errores de PHP según el entorno
$isDebug = (bool) App\Core\Config::get('app.debug', false);
if ($isDebug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}
