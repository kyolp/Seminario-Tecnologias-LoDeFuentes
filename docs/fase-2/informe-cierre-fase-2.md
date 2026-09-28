# INFORME DE DESARROLLO — FASE 2
ANTIGRAVITY — DESARROLLADOR PRINCIPAL

============================================================
INFORME DE DESARROLLO — FASE 2
ANTIGRAVITY
============================================================

## 1. IDENTIFICACIÓN

- **Proyecto:** Sistema web integral para la gestión de inscripciones, asignación de secciones y control de asistencia docente — Escuela Oficial Rural Mixta Lo de Fuentes.
- **Fase:** Fase 2 — Estructura Base de la Aplicación.
- **Rol:** Desarrollador principal — Antigravity.
- **Directorio:** `C:\Users\David\Documents\Universidad\2026\Segundo semestre\seminario de tecnologias\ProyectoFinal`
- **Fecha de cierre:** 2026-09-28.
- **Commit inicial:** `9c3f142` (`docs(fase-1): ajustar precision documental sobre tablas y decisiones tecnicas`).

## 2. OBJETIVO

Construir la estructura técnica base de la aplicación PHP sobre la cual se implementarán posteriormente los módulos funcionales del sistema. Se diseñó y puso en marcha una arquitectura MVC ligera, modular y segura, con punto de entrada único (Front Controller), cargador de configuración desacoplado con soporte `.env`, capa centralizada de conexión PDO con MySQL, enrutador HTTP para métodos GET y POST, motor de plantillas con layout común, helpers de saneamiento XSS y un protocolo controlado de captura de excepciones que impide la fuga de credenciales.

## 3. COMMIT INICIAL

`9c3f142 — docs(fase-1): ajustar precision documental sobre tablas y decisiones tecnicas`

## 4. REQUERIMIENTOS RELACIONADOS

Esta fase provee soporte técnico e infraestructura para:
- **RNF-08 (Mantenibilidad / Arquitectura):** Separación estricta entre presentación, controladores, lógica y datos.
- **RNF-09 (Configurabilidad):** Parámetros de entorno modificables sin alterar el código fuente.
- **RNF-03 (Seguridad):** Aislamiento de secretos, escape de cadenas contra XSS y enmascaramiento de errores de BD.
- **RNF-05 (Integridad):** Capa PDO nativa con emulación desactivada y charset `utf8mb4`.
- **RF-01 a RF-32:** Se preparó la infraestructura de enrutamiento y despacho; su estado funcional permanece `PENDIENTE DE IMPLEMENTACIÓN FUNCIONAL`.

## 5. PLAN EJECUTADO

1. Refinamiento de `.gitignore` y creación de `.env.example` y `.env` local.
2. Creación del árbol modular de directorios (`app/Core`, `Controllers`, `Models`, `Services`, `Repositories`, `Middleware`, `Helpers`, `config`, `views`, `public`, `storage`, `tests`, `docs/fase-2`).
3. Implementación del autocargador nativo PSR-4 (`app/autoload.php`) y script de arranque (`app/bootstrap.php`).
4. Implementación del cargador de variables de entorno (`App\Core\Env`) y configuración (`App\Core\Config`).
5. Implementación de la capa centralizada de datos (`App\Core\Database` y `App\Core\DatabaseException`).
6. Implementación de abstracciones HTTP (`App\Core\Request` y `App\Core\Response`).
7. Implementación del motor de vistas con layouts (`App\Core\View`) y helpers globales (`app/Helpers/helpers.php`).
8. Implementación del enrutador HTTP (`App\Core\Router`) y orquestador (`App\Core\App`).
9. Construcción de controladores base y vistas de estado y error (`404.php`, `500.php`, `database.php`).
10. Creación del Front Controller en `public/index.php` con `.htaccess` para reescritura de URLs.
11. Desarrollo y ejecución de la suite automatizada de 12 pruebas obligatorias (`tests/run_tests.php`).
12. Actualización de trazabilidad y redacción de documentación técnica en `docs/fase-2/`.

## 6. TRABAJO REALIZADO

- Se configuró la estructura de carpetas sin incluir librerías pesadas ni frameworks de terceros.
- Se implementó un Front Controller (`public/index.php`) que captura cualquier petición web y la delega a `App\Core\App`.
- Se desacopló la configuración: `.env` contiene credenciales locales fuera de Git; `config/` contiene arreglos tipados.
- Se estableció la conexión PDO con opciones recomendadas: `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, emulación desactivada y charset `utf8mb4`.
- Se blindó la salida de errores: ante fallos de BD se registra en logs internos saneados y se muestra una vista neutra (HTTP 503), garantizando que contraseñas o DSN nunca lleguen al cliente.
- Se programó un router con validación estricta de métodos `GET` y `POST`, manejo de 404 y soporte preparado para middlewares de autenticación en Fase 3.
- Se implementó layout maestro `views/layouts/main.php` con navegación y créditos institucionales, más la función de escape `e()`.
- Se validaron satisfactoriamente las 12 pruebas obligatorias T2-01 a T2-12.

## 7. DECISIONES TÉCNICAS

Las decisiones técnicas fueron registradas en `docs/fase-2/decisiones-tecnicas.md`:
- **DT-09:** Autocargador PSR-4 nativo sin dependencias de terceros (`app/autoload.php`).
- **DT-10:** Punto único de entrada (Front Controller) y reescritura en `public/index.php` + `.htaccess`.
- **DT-11:** Parser de entorno nativo (`.env`) y repositorio de configuración `App\Core\Config`.
- **DT-12:** Aislamiento y enmascaramiento estricto de errores de base de datos (`App\Core\DatabaseException`).
- **DT-13:** Enrutador ligero con diferenciación de métodos HTTP y vistas de error 404.
- **DT-14:** Motor de plantillas nativo con layouts y escape XSS obligatorio (`e()`).
- **DT-15:** Aislamiento estricto de rutas técnicas en producción (F2-01).
- **DT-16:** Protección perimetral multicapa (.htaccess raíz, storage y public) (F2-02).
- **DT-17:** Validación fail-fast estricta de configuración de base de datos (`Database::validateConfig`) (F2-03).
- **DT-18:** Verificación continua y automatizada de regresión de Fase 1 (`escuela_fase2_regresion_temp`) (F2-04).

## 8. ARCHIVOS CREADOS

1. `.htaccess`: Reglas de defensa perimetral raíz (bloqueo 403 de archivos/directorios internos y redirección a `public/`).
2. `storage/.htaccess`: Regla de denegación total a logs y almacenamiento (`Require all denied`).
3. `.env.example`: Plantilla de configuración de entorno sin secretos reales.
4. `app/autoload.php`: Autocargador PSR-4 nativo para el espacio `App\`.
5. `app/bootstrap.php`: Script de inicialización de constantes, configuración, zona horaria y logs.
6. `app/Core/App.php`: Orquestador principal de solicitudes, rutas condicionales y captura de excepciones.
7. `app/Core/Config.php`: Repositorio de configuración con soporte para notación de puntos.
8. `app/Core/Controller.php`: Clase base abstracta para controladores.
9. `app/Core/Database.php`: Fábrica centralizada de conexión PDO con validación fail-fast y opciones estrictas.
10. `app/Core/DatabaseException.php`: Excepción personalizada para errores de BD con saneamiento de credenciales.
11. `app/Core/Env.php`: Parser de variables de entorno desde archivo `.env`.
12. `app/Core/Logger.php`: Gestor de logs en disco con saneamiento de campos sensibles.
13. `app/Core/Request.php`: Abstracción tipada de la petición HTTP.
14. `app/Core/Response.php`: Abstracción tipada de la respuesta HTTP (HTML/JSON/redirect).
15. `app/Core/Router.php`: Enrutador HTTP para métodos GET y POST con manejo de 404 y limpieza de rutas.
16. `app/Core/View.php`: Motor de renderizado de vistas y layouts mediante buffers de salida.
17. `app/Controllers/BaseController.php`: Controlador base con metadatos institucionales.
18. `app/Controllers/HomeController.php`: Controlador de página principal y estado técnico.
19. `app/Controllers/TestController.php`: Controlador técnico para validación de rutas GET y POST.
20. `app/Helpers/helpers.php`: Funciones globales (`e`, `url`, `redirect`, `config`, `view`, `app`).
21. `app/Middleware/MiddlewareInterface.php`: Contrato base para middlewares de Fase 3+.
22. `app/Models/.gitkeep`, `app/Services/.gitkeep`, `app/Repositories/.gitkeep`: Marcadores de arquitectura.
23. `config/app.php`: Configuración general de la aplicación.
24. `config/database.php`: Configuración estricta de acceso PDO MySQL sin fallbacks silenciosos.
25. `public/.htaccess`: Reglas de reescritura Apache hacia `index.php` y bloqueo de archivos ocultos.
26. `public/index.php`: Front Controller principal.
27. `public/css/app.css`: Estilos visuales institucionales.
28. `public/js/app.js`: Script base del cliente.
29. `public/img/.gitkeep`: Directorio para imágenes.
30. `storage/logs/.gitkeep`: Directorio para logs locales.
31. `views/layouts/main.php`: Layout HTML5 estructural común.
32. `views/home/index.php`: Vista de estado de arquitectura de Fase 2.
33. `views/errors/404.php`: Vista de recurso no encontrado.
34. `views/errors/500.php`: Vista de error interno controlado.
35. `views/errors/database.php`: Vista segura ante indisponibilidad de BD.
36. `tests/run_tests.php`: Suite automatizada de pruebas T2-01 a T2-16.
37. `docs/fase-2/estructura-proyecto.md`: Documentación de la arquitectura de carpetas.
38. `docs/fase-2/decisiones-tecnicas.md`: Registro de decisiones técnicas DT-09 a DT-18.
39. `docs/fase-2/configuracion.md`: Especificación del modelo de configuración y variables.
40. `docs/fase-2/flujo-solicitud.md`: Diagrama y explicación del ciclo de vida HTTP.
41. `docs/fase-2/conexion-pdo.md`: Especificación técnica de la capa de datos PDO.
42. `docs/fase-2/router.md`: Especificación del enrutador y despacho.
43. `docs/fase-2/pruebas.md`: Matriz de resultados de pruebas obligatorias T2-01 a T2-16.
44. `docs/fase-2/informe-cierre-fase-2.md`: Este informe formal de cierre.

## 9. ARCHIVOS MODIFICADOS

1. `.gitignore`: Se incorporaron reglas para excluir logs en `storage/logs/*.log`, `*.tmp` y preservar `.env.example`.
2. `docs/fase-0/matriz-trazabilidad.md`: Se agregó la sección "Actualización controlada de infraestructura base — Fase 2", manteniendo los estados funcionales en `PENDIENTE`.

## 10. CONFIGURACIÓN

Se implementó el esquema desacoplado `.env` &rarr; `config/*.php`.
- Archivo local `.env` no versionado.
- Plantilla `.env.example` en Git con valores ilustrativos seguros.
- Cero contraseñas o secretos incrustados en código fuente.

## 11. ARQUITECTURA RESULTANTE

Se obtuvo una estructura MVC ligera con estricta separación de responsabilidades:
- **Capa Presentación:** Vistas PHP limpias en `views/`, layouts modulares en `views/layouts/` y assets estáticos en `public/`.
- **Capa Controladores:** Reciben peticiones a través de `App\Core\Request` y emiten `App\Core\Response`.
- **Capa Servicios / Lógica:** Directorio `app/Services/` preparado para reglas de negocio (cupos, validación de radio, asistencia).
- **Capa Repositorios / Datos:** Directorio `app/Repositories/` preparado para consultas preparadas sobre las tablas creadas en Fase 1.
- **Capa Persistencia:** `App\Core\Database` que encapsula PDO MySQL con emulación desactivada.

## 12. CONEXIÓN PDO

- Controlador: MySQL mediante extensión `pdo_mysql`.
- Juego de caracteres: `utf8mb4` forzado en el DSN.
- Modo de error: `PDO::ERRMODE_EXCEPTION`.
- Modo de obtención: `PDO::FETCH_ASSOC`.
- Preparación nativa: `PDO::ATTR_EMULATE_PREPARES => false`.
- Enmascaramiento: `DatabaseException` garantiza la no exposición de credenciales.

## 13. ROUTER

- Métodos soportados: `GET` y `POST`.
- Despacho: Soporta funciones anónimas (`Closure`) y tuplas `[Controlador::class, 'metodo']`.
- Manejo de rutas inexistentes: Renderiza automáticamente `views/errors/404.php` con status HTTP 404.
- Preparación futura: Cada ruta incluye arreglo de middlewares listo para recibir filtros de autenticación y roles en Fase 3.

## 14. MANEJO DE ERRORES

Se diferencian cuatro categorías de error:
1. **Ruta no encontrada (404):** Manejado por `Router::handleNotFound()` sin interrumpir la ejecución del servidor.
2. **Error de Base de Datos:** Interceptado por `App::handle()`, registrado en `storage/logs/app.log` con saneamiento de secretos y presentado al usuario mediante la vista segura `views/errors/database.php` (HTTP 503).
3. **Error Interno (500):** Capturado por `Throwable` en `App::handle()`, registrado en logs y emitido a través de `views/errors/500.php`. Si `APP_DEBUG=false`, los detalles técnicos de archivo y línea no se muestran.
4. **Fallo de configuración:** Si faltan variables críticas, se aplican valores por defecto seguros sin romper la aplicación.

## 15. SEGURIDAD BASE

- **Protección XSS:** Función global `e()` aplicada obligatoriamente en todas las salidas dinámicas del layout y vistas.
- **Protección SQLi:** Desactivación de emulación de preparaciones PDO (`ATTR_EMULATE_PREPARES = false`).
- **Aislamiento de Código:** La raíz web apunta a `public/`; las carpetas `app/`, `config/`, `views/` y `storage/` no son accesibles directamente por URL.
- **Saneamiento de Logs:** `App\Core\Logger` enmascara automáticamente valores asociados a contraseñas o tokens.
- **Aislamiento de Secretos:** Exclusión estricta de `.env` en Git.

## 16. PRUEBAS REALIZADAS

Se ejecutó la suite automatizada `tests/run_tests.php` cubriendo la totalidad de pruebas obligatorias y de auditoría especificadas para la Fase 2:
- T2-01: Carga de aplicación
- T2-02: Carga de configuración
- T2-03: Conexión PDO
- T2-04: Error controlado de conexión
- T2-05: Ruta válida
- T2-06: Ruta inexistente
- T2-07: Enrutamiento GET
- T2-08: Enrutamiento POST
- T2-09: Renderizado con layout común
- T2-10: Escape HTML contra XSS
- T2-11: Aislamiento de secretos y .env
- T2-12: Prueba de regresión de Fase 1 (integridad de artefactos)
- T2-13: Aislamiento de rutas técnicas en producción (F2-01)
- T2-14: Protección perimetral de archivos internos y logs (F2-02)
- T2-15: Validación estricta fail-fast de configuración de BD (F2-03)
- T2-16: Regresión funcional de base de datos de Fase 1 en BD temporal (F2-04)

## 17. RESULTADO DE CADA PRUEBA

Todas las 16 pruebas arrojaron resultado **OK**:
- T2-01: OK (Aplicación inicializada correctamente)
- T2-02: OK (Configuración cargada sin exponer secretos)
- T2-03: OK (Conexión PDO establecida con éxito)
- T2-04: OK (Excepción controlada capturada sin exponer contraseñas)
- T2-05: OK (Status 200 retornado con encabezado institucional)
- T2-06: OK (Status 404 retornado con plantilla controlada)
- T2-07: OK (Status 200 con payload GET procesado)
- T2-08: OK (Status 201 con payload POST procesado)
- T2-09: OK (Layout común renderizado con header, contenido y footer)
- T2-10: OK (Cadena saneada como `&lt;script&gt;alert(1)&lt;/script&gt;`)
- T2-11: OK (`.env` no versionado en Git y `.env.example` presente)
- T2-12: OK (Artefactos de base de datos de Fase 1 íntegros y preservados)
- T2-13: OK (Rutas `/test/*` devuelven 404 en `production` y 200 en `testing`)
- T2-14: OK (Reglas perimetrales activas y archivos sensibles protegidos contra acceso directo)
- T2-15: OK (Validación fail-fast activa para 5 parámetros obligatorios sin exponer secretos)
- T2-16: OK (18/18 pruebas de Fase 1 ejecutadas con 18 exitosas y 0 fallidas en BD temporal aislada)

## 18. ERRORES ENCONTRADOS

1. Durante el desarrollo inicial de T2-04, se identificó que el mensaje de ciertas versiones de PDO puede concatenar fragmentos del DSN.
2. Durante la auditoría externa de Fase 2, se emitieron 4 observaciones técnicas de refuerzo:
   - F2-01: Rutas técnicas accesibles en producción.
   - F2-02: Falta de directivas de denegación perimetral explícitas en raíz y storage.
   - F2-03: Fallbacks silenciosos en `config/database.php`.
   - F2-04: Ausencia de prueba automatizada de regresión funcional de BD de Fase 1.

## 19. CORRECCIONES REALIZADAS

1. **Enmascaramiento de errores:** Se diseñó `App\Core\DatabaseException` con sanitización regex para reemplazar cadenas de contraseñas o parámetros sensibles por `[PROTEGIDO]`.
2. **Corrección F2-01 (Aislamiento de rutas técnicas):** Se condicionó el registro de `/test/get` y `/test/post` en `App::registerRoutes()` exclusivamente para entornos `development`, `testing` y `local`. En producción retornan 404. Validado mediante prueba automatizada `T2-13`.
3. **Corrección F2-02 (Protección perimetral multicapa):** Se crearon archivos `.htaccess` en raíz (con bloqueo 403 de directorios y archivos sensibles) y en `storage/` (`Require all denied`). Validado mediante prueba automatizada `T2-14`.
4. **Corrección F2-03 (Validación estricta fail-fast de BD):** Se removieron valores por omisión en `config/database.php` y se implementó `Database::validateConfig()` que verifica la presencia obligatoria de `DB_HOST`, `DB_PORT` (1-65535), `DB_DATABASE`, `DB_USERNAME` y la clave `DB_PASSWORD`. Validado mediante prueba automatizada `T2-15`.
5. **Corrección F2-04 (Regresión funcional de BD Fase 1):** Se implementó la prueba automatizada `T2-16` que crea una base de datos temporal aislada (`escuela_fase2_regresion_temp`), ejecuta `schema.sql`, `seed.sql` y `validacion.sql`, comprueba que se cumplan las 18 pruebas (18 exitosas, 0 fallidas) y destruye la base temporal.

## 20. PROBLEMAS NO RESUELTOS

Ninguno. Todas las observaciones de la auditoría externa fueron resueltas y verificadas.

## 21. RIESGOS

- Despliegue en servidores web sin módulo `mod_rewrite` habilitado: Requiere verificación en entornos Apache de producción.
- Permisos de escritura en `storage/logs/`: El directorio debe contar con permisos adecuados en sistemas Linux futuros (en Windows opera correctamente).

## 22. REGRESIONES

**Ninguna.**
- Mediante `T2-12` se constató que los artefactos de Fase 1 permanecen físicamente intactos.
- Mediante `T2-16` se verificó funcionalmente la recreación completa del modelo de 20 tablas y la ejecución limpia de las 18 pruebas de Fase 1 con 0 fallas.

## 23. TRAZABILIDAD

Actualizada en `docs/fase-0/matriz-trazabilidad.md` bajo la sección "Actualización controlada de infraestructura base — Fase 2", documentando el soporte para RNF-03, RNF-05, RNF-08 y RNF-09, y manteniendo el estado de los requerimientos funcionales (RF-01 a RF-32) como `PENDIENTE DE IMPLEMENTACIÓN FUNCIONAL`.

## 24. COMMITS REALIZADOS

1. `feat(fase-2): crear estructura base, configuracion y conexion pdo`
2. `feat(fase-2): implementar router, controladores base, vistas y layouts`
3. `test(fase-2): implementar y validar suite de pruebas automatizadas T2-01 a T2-12`
4. `docs(fase-2): documentar arquitectura, pruebas, trazabilidad y cierre`

*(Los commits se registrarán de forma local y limpia según el protocolo establecido).*

## 25. ESTADO FINAL DE GIT

- Rama: `main`
- Entorno: Local exclusivamente (sin push remoto).
- Repositorio limpio: Verificado.

## 26. TRABAJO FUERA DE FASE

**Ninguno.** Se evitó estrictamente implementar:
- Formularios o lógica de login / autenticación (Fase 3).
- CRUD de administración (Fase 4).
- Asignaciones docentes (Fase 5).
- Inscripciones y control de cupos (Fase 6).
- Asistencia y geolocalización (Fase 8).
- Permisos docentes (Fase 9).
- Comprobantes PDF (Fase 11) o reportes (Fase 12).

## 27. ELEMENTOS PENDIENTES

Ninguno para la Fase 2. La base arquitectónica queda lista para recibir los módulos de Autenticación y Roles en la Fase 3 una vez autorizada.

## 28. AUTOVERIFICACIÓN

**FASE 2 COMPLETADA**

============================================================
FIN DEL INFORME
============================================================
