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
