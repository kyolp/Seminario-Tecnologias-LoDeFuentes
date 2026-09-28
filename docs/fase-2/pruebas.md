# Matriz de Pruebas Obligatorias — Fase 2

## Entorno de verificación

- **Sistema Operativo:** Windows NT 10.0 x64
- **Runtime:** PHP 8.2.12 CLI (ZTS Visual C++ 2019 x64)
- **Base de Datos:** MySQL Community Server 8.0.42
- **Driver PDO:** `pdo_mysql`
- **Script ejecutor:** `tests/run_tests.php`
- **Fecha de ejecución:** 2026-09-28

---

## Resultados detallados de las pruebas obligatorias

| ID | Prueba | Procedimiento ejecutado | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|---|
| **T2-01** | Carga de aplicación | Instanciación y llamada a `App::getInstance()->boot()`. | Inicialización limpia sin errores fatales. | Aplicación inicializada correctamente sin excepciones. | **OK** |
| **T2-02** | Configuración | Lectura de valores vía `Config::get()` y volcado de arreglo. | Carga de configuración modular sin exponer secretos. | Valores `app.name` y `database.connections` leídos correctamente. | **OK** |
| **T2-03** | Conexión PDO | Ejecución de `Database::getConnection()->query('SELECT 1')`. | Conexión exitosa a MySQL y consulta básica. | Conexión PDO establecida con éxito (`SELECT 1 = 1`). | **OK** |
| **T2-04** | Error de conexión | Intento de conexión con credenciales erróneas forzadas y análisis de cadena del error. | Lanzamiento de `DatabaseException` sin incluir contraseñas en el mensaje. | Excepción controlada capturada; la contraseña ficticia no aparece en el mensaje. | **OK** |
| **T2-05** | Ruta válida | Envío de solicitud simulada `GET /` mediante `App::handle()`. | Respuesta HTTP 200 con contenido de la vista principal. | Status 200 retornado con encabezado de la Escuela Lo de Fuentes. | **OK** |
| **T2-06** | Ruta inexistente | Envío de solicitud simulada `GET /ruta-inexistente-404`. | Respuesta HTTP 404 con plantilla de error controlada. | Status 404 emitido y vista `errors/404.php` renderizada. | **OK** |
| **T2-07** | GET | Envío de solicitud `GET /test/get?echo=prueba`. | Respuesta HTTP 200 con parámetro de consulta procesado en JSON. | Status 200 con JSON `method: "GET"`, `echo: "prueba"`. | **OK** |
| **T2-08** | POST | Envío de solicitud `POST /test/post` con carga útil simulada. | Respuesta HTTP 201 con payload procesado por el controlador. | Status 201 con JSON `method: "POST"` y datos recibidos. | **OK** |
| **T2-09** | Layout | Renderizado de `home.index` embebido en layout maestro `main.php`. | Ensamble coherente de encabezado, contenido y pie de página. | HTML generado incluye `app-header`, `app-main` y `app-footer`. | **OK** |
| **T2-10** | Escape HTML | Invocación de `e('<script>alert(1)</script>')`. | Cadena saneada sin etiquetas ejecutables de script. | Salida convertida a `&lt;script&gt;alert(1)&lt;/script&gt;`. | **OK** |
| **T2-11** | Secretos | Inspección de Git (`git ls-files .env`) y verificación de `.env.example`. | Archivo `.env` fuera del control de versiones y plantilla disponible. | `.env` no rastreado por Git y `.env.example` presente. | **OK** |
| **T2-12** | Regresión Fase 1 (Artefactos) | Verificación de existencia e integridad de archivos de base de datos de Fase 1. | `schema.sql`, `seed.sql`, `validacion.sql` e informes intactos. | Todos los artefactos de la Fase 1 preservados y utilizables. | **OK** |
| **T2-13** | Aislamiento rutas técnicas | Cambio dinámico a `APP_ENV=production` y despacho de solicitudes a `/test/get` y `/test/post`. | Rutas técnicas devuelven HTTP 404 en producción y operan en testing/development. | HTTP 404 en production; HTTP 200 en testing. Aislamiento exitoso. | **OK** |
| **T2-14** | Protección perimetral | Inspección de reglas `.htaccess` (raíz, storage, public) y despacho sintético a `/.env`, `/config/database.php` y `/storage/logs/app.log`. | Denegación perimetral y respuestas 404/403 sin exposición de código ni secretos. | Reglas perimetrales activas; sin fugas de secretos en respuestas HTTP (404/403). | **OK** |
| **T2-15** | Validación estricta BD | Invocación de `Database::validateConfig()` con configuraciones incompletas (omisión de host, port, db, user, pass). | Interrupción fail-fast vía `DatabaseException` indicando el parámetro faltante sin exponer contraseñas. | 5 validaciones fail-fast superadas; secretos resguardados; configuración válida aceptada. | **OK** |
| **T2-16** | Regresión funcional BD Fase 1 | Creación automatizada de base de datos temporal aislada, ejecución secuencial de `schema.sql`, `seed.sql` y `validacion.sql`, verificación de resultados y eliminación. | 18 pruebas automáticas de Fase 1 ejecutadas con 18 exitosas y 0 fallidas en la base temporal. | 18/18 pruebas de Fase 1 ejecutadas con 18 exitosas y 0 fallidas en BD temporal aislada. | **OK** |

---

## Resumen cuantitativo

- **Pruebas planificadas:** 16
- **Pruebas ejecutadas:** 16
- **Pruebas exitosas:** 16 (100%)
- **Pruebas fallidas:** 0 (0%)
- **Resultado general:** **TODAS LAS PRUEBAS SUPERADAS SATISFACTORIAMENTE (16/16)**

