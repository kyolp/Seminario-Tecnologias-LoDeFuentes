# Decisiones Técnicas — Fase 2

Este documento registra las decisiones de diseño arquitectónico e infraestructura adoptadas durante la implementación de la Fase 2 del prototipo institucional para la **Escuela Oficial Rural Mixta Lo de Fuentes**.

---

## DT-09 — Autocargador PSR-4 nativo sin dependencias de terceros

- **Tema:** Carga automática de clases del espacio de nombres `App\`.
- **Problema:** El entorno validado en Fase 0 cuenta con PHP 8.2 CLI nativo, pero no exige la instalación de Composer como requisito previo para la ejecución del prototipo.
- **Información disponible:** PHP provee la función `spl_autoload_register`, capaz de implementar la especificación PSR-4.
- **Decisión:** Implementar un cargador nativo en `app/autoload.php` que asocie el prefijo `App\` con la carpeta física `app/`.
- **Justificación:** Garantiza reproducibilidad inmediata en el entorno local sin instalar paquetes externos no autorizados ni alterar la ligereza del proyecto.
- **Impacto:** Todas las clases dentro de `app/` se cargan bajo demanda de manera ordenada y tipada.
- **¿Configurable posteriormente?:** Sí, si en fases futuras se autoriza la incorporación de herramientas como Composer.

---

## DT-10 — Punto único de entrada (Front Controller) y reescritura

- **Tema:** Control del ciclo de vida de las solicitudes HTTP.
- **Problema:** Múltiples archivos accesibles en la raíz pública pueden originar accesos no autorizados y duplicación de inicializaciones.
- **Información disponible:** El patrón Front Controller centraliza el arranque, middleware y enrutamiento en un solo archivo.
- **Decisión:** Establecer `public/index.php` como el único punto de entrada público, respaldado por reglas de reescritura en `public/.htaccess`.
- **Justificación:** Cumple con el requerimiento de centralizar el flujo de solicitudes y facilita la posterior intercepción de seguridad (Fases 3 y 14).
- **Impacto:** Los archivos del núcleo (`app/`, `config/`, `views/`, `storage/`) quedan fuera de la raíz pública directa.
- **¿Configurable posteriormente?:** No; es el estándar de seguridad adoptado para la arquitectura.

---

## DT-11 — Parser de entorno nativo (`.env`) y repositorio de configuración

- **Tema:** Separación de secretos y configuración del código fuente.
- **Problema:** No se deben versionar credenciales reales en Git, pero el sistema debe ser configurable por entorno (desarrollo, pruebas, producción).
- **Información disponible:** El estándar de la industria utiliza variables de entorno y archivos `.env` excluidos en `.gitignore`.
- **Decisión:** Crear `App\Core\Env` para parsear `.env` y `App\Core\Config` para leer archivos modulares en `config/` (`app.php`, `database.php`) mediante notación de puntos (ej: `Config::get('database.connections.mysql.port')`).
- **Justificación:** Permite cambiar hosts, puertos y contraseñas sin modificar una sola línea de código fuente (RNF-09).
- **Impacto:** Se provee `.env.example` en el repositorio sin secretos reales; `.env` permanece estrictamente local.
- **¿Configurable posteriormente?:** Sí, mediante edición del archivo `.env`.

---

## DT-12 — Aislamiento y enmascaramiento estricto de errores de base de datos

- **Tema:** Seguridad en el manejo de excepciones de conexión PDO.
- **Problema:** Los errores nativos de PDO (`PDOException`) pueden exponer en el mensaje el host, puerto, nombre de usuario e incluso fragmentos de contraseñas.
- **Información disponible:** Requerimiento obligatorio T2-04: un fallo de conexión no debe exponer credenciales.
- **Decisión:** Encapsular la conexión en `App\Core\Database`, registrar el detalle técnico de forma segura en `storage/logs/app.log` previa sanitización, y lanzar `App\Core\DatabaseException` con un mensaje genérico protegido hacia la vista.
- **Justificación:** Previene fugas de información sensible tanto en pantalla como en respuestas API o JSON.
- **Impacto:** Si la base de datos se detiene o rechaza la conexión, el usuario observa una vista 503 controlada sin datos técnicos sensibles.
- **¿Configurable posteriormente?:** Sí, el nivel de detalle se asocia al indicador `app.debug`.

---

## DT-13 — Enrutador ligero con diferenciación de métodos HTTP y vistas de error

- **Tema:** Despacho de rutas y manejo de 404.
- **Problema:** Se requiere despachar peticiones `GET` y `POST` de forma limpia hacia controladores sin incluir frameworks pesados.
- **Información disponible:** La arquitectura MVC ligera solicitada requiere mapear URIs hacia métodos de controladores (`[Controller::class, 'method']`).
- **Decisión:** Desarrollar `App\Core\Router` con registro explícito de métodos, resolución de controladores, normalización de rutas y renderizado automático de la vista `views/errors/404.php` ante rutas inexistentes.
- **Justificación:** Satisface las pruebas T2-05 a T2-08 y deja preparada la estructura para encadenar middlewares en la Fase 3.
- **Impacto:** Respuestas HTTP bien formadas con códigos de estado correctos (200, 201, 404, 500, 503).
- **¿Configurable posteriormente?:** Sí, ampliando métodos o soporte de expresiones regulares si fuera necesario.

---

## DT-14 — Motor de plantillas nativo con layouts y escape XSS obligatorio

- **Tema:** Renderizado de interfaz y protección contra inyección de HTML/JavaScript.
- **Problema:** Evitar duplicar cabeceras y pies de página en las vistas, y asegurar que entradas no confiables se escapen correctamente.
- **Información disponible:** PHP nativo ofrece buffers de salida (`ob_start`, `ob_get_clean`) y funciones de escape (`htmlspecialchars`).
- **Decisión:** Implementar `App\Core\View` con soporte para layout maestro `views/layouts/main.php` e incorporar el helper global `e(?string $value)` que ejecuta `htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- **Justificación:** Mantiene alta velocidad de renderizado, cero dependencias y protección proactiva contra XSS (RNF-03).
- **Impacto:** Las vistas futuras sólo definen su contenido específico; el encabezado institucional y pie son gestionados por el layout.
- **¿Configurable posteriormente?:** Sí, se pueden crear múltiples layouts (ej. `auth`, `admin`, `docente`).

---

## DT-15 — Aislamiento estricto de rutas técnicas en entornos productivos (F2-01)

- **Tema:** Exposición de endpoints técnicos de prueba (`/test/get`, `/test/post`).
- **Problema:** Rutas diseñadas para verificar el enrutamiento no deben quedar expuestas indiscriminadamente en entornos de producción.
- **Información disponible:** La variable `APP_ENV` identifica el entorno de ejecución (`production`, `development`, `testing`, `local`).
- **Decisión:** Registrar condicionalmente en `App\Core\App::registerRoutes()` las rutas técnicas `/test/*` únicamente si `APP_ENV` pertenece a `['development', 'testing', 'local']`. En cualquier otro entorno (o por omisión productiva), dichas rutas no se registran y retornan código HTTP 404 (Not Found).
- **Justificación:** Previene vectores de información o abuso en producción manteniendo la capacidad de prueba durante el ciclo de desarrollo y pruebas automatizadas.
- **Impacto:** En producción las rutas técnicas no existen para el router.
- **¿Configurable posteriormente?:** Sí, a través de la variable `APP_ENV`.

---

## DT-16 — Protección perimetral multicapa para archivos sensibles y logs (F2-02)

- **Tema:** Prevención de acceso directo a archivos internos (`.env`, `config/`, `app/`, `storage/logs/`).
- **Problema:** En despliegues donde el DocumentRoot no apunte estrictamente a `public/`, o ante solicitudes con rutas relativas/ocultas, los archivos de configuración y logs podrían ser consultados por HTTP.
- **Información disponible:** Servidores web basados en Apache interpretan archivos `.htaccess` en cascada.
- **Decisión:** Implementar defensa en profundidad con 3 niveles:
  1. `.htaccess` en la raíz del proyecto bloqueando acceso directo a carpetas sensibles (`app`, `config`, `database`, `docs`, `storage`, `tests`, `views`) y archivos ocultos (`.env`, `.git`) retornando HTTP 403 Forbidden, y redirigiendo el tráfico web a `public/`.
  2. `storage/.htaccess` con política estricta de denegación total (`Require all denied` / `Deny from all`).
  3. `public/.htaccess` con bloqueo de archivos ocultos y enrutamiento seguro hacia `index.php`.
- **Justificación:** Cumple con la defensa perimetral requerida, mitigando riesgos de desconfiguración en el servidor web.
- **Impacto:** Imposibilidad de consultar archivos de credenciales, logs o código fuente vía HTTP.
- **¿Configurable posteriormente?:** Sí, mediante directivas del servidor web.

---

## DT-17 — Validación fail-fast estricta de configuración de base de datos (F2-03)

- **Tema:** Obligatoriedad de parámetros de conexión y prevención de fallos silenciosos.
- **Problema:** Fallbacks silenciosos como `localhost` o `3306` pueden ocultar omisiones en el archivo `.env`, provocando intentos de conexión erróneos o confusos.
- **Información disponible:** `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` son parámetros requeridos para el acceso a MySQL.
- **Decisión:** Eliminar valores por defecto silenciosos en `config/database.php` y crear `App\Core\Database::validateConfig()`. Si cualquiera de los parámetros falta, está vacío o el puerto está fuera del rango válido (1-65535), se interrumpe la ejecución inmediatamente con una `DatabaseException` controlada que indica la variable ausente sin exponer credenciales.
- **Justificación:** Principio fail-fast: errores de configuración deben detectarse en el arranque antes de intentar operaciones en la red o base de datos.
- **Impacto:** El sistema falla inmediatamente si falta alguna credencial requerida en el `.env`.
- **¿Configurable posteriormente?:** No, la obligatoriedad es una regla de integridad arquitectónica.

---

## DT-18 — Verificación continua y automatizada de regresión de Fase 1 (F2-04)

- **Tema:** Garantía de integridad funcional del modelo relacional de Fase 1.
- **Problema:** La evolución de la arquitectura en Fase 2 y posteriores no debe comprometer la capacidad de recrear y validar la base de datos de Fase 1.
- **Información disponible:** Los artefactos `database/schema.sql`, `database/seed.sql` y `database/tests/validacion.sql` contienen el modelo de 20 tablas y 18 pruebas automatizadas.
- **Decisión:** Integrar en la suite de pruebas automatizadas la prueba `T2-16`, la cual crea de forma aislada una base de datos temporal (`escuela_fase2_regresion_temp`), ejecuta secuencialmente el schema, las semillas y el script de validación, verifica que se ejecuten y aprueben 18/18 pruebas (0 fallos), y destruye la base temporal al finalizar.
- **Justificación:** Asegura de forma continua y automatizada que el modelo de datos de Fase 1 permanece 100% funcional.
- **Impacto:** Cobertura de regresión completa y automatizada en la suite PHP CLI.
- **¿Configurable posteriormente?:** No; forma parte del pipeline de pruebas permanentes del prototipo.

