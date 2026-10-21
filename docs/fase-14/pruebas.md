# Registro de Pruebas Automatizadas — Fase 14
## Sistema Web — Escuela Oficial Rural Mixta Lo de Fuentes

**Módulo:** Seguridad, Privacidad y Hardening Global  
**Script de Ejecución:** `tests/run_tests_fase14.php`  
**Total de Pruebas:** 32  
**Exitosas (OK):** 32  
**Fallidas (FALLO):** 0  
**Estado:** 100% GREEN (Aprobada)  

---

### Tabla Detallada de Pruebas

| Código | Requisito | Descripción de la Prueba | Resultado Obtenido | Estado |
|---|---|---|---|:---:|
| **T14-01** | Trazabilidad | Trazabilidad documental: RNF-03 a RNF-08 auditados para Fase 14; Fases 15 y 16 pendientes | Todos los RNF oficiales auditados y documentados en matriz de trazabilidad | **OK** |
| **T14-02** | RBAC / Auth | Acceso anónimo a rutas administrativas denegado con redirección 302 a /login | Redirección 302 hacia `/login` en todas las rutas administrativas protegidas | **OK** |
| **T14-03** | RBAC / Auth | Acceso anónimo a rutas del portal docente denegado con redirección 302 a /login | Redirección 302 hacia `/login` en todas las rutas del portal docente | **OK** |
| **T14-04** | RBAC | Acceso de rol DOCENTE a rutas administrativas bloqueado con HTTP 403 Forbidden | Rechazo estricto HTTP 403 Forbidden con vista controlada de acceso denegado | **OK** |
| **T14-05** | RBAC | Acceso de rol ADMINISTRADOR a portal docente denegado con HTTP 403 (mínimo privilegio) | Rechazo estricto HTTP 403 Forbidden conforme al principio de mínimo privilegio | **OK** |
| **T14-06** | IDOR / Privacidad | IDOR: Docente no puede consultar detalle de permiso ajeno (HTTP 403 Forbidden) | Rechazo HTTP 403 al intentar visualizar solicitud de permiso de otro docente | **OK** |
| **T14-07** | IDOR / Privacidad | IDOR: Descarga de documento ajeno/no autorizado bloqueada sin fuga de rutas físicas | Solicitud rechazada (403/404) con mensaje seguro y cero exposición de rutas locales | **OK** |
| **T14-08** | IDOR / Privacidad | IDOR: Docente no puede marcar como leída notificación perteneciente a otro usuario | Notificación ajena permanece intacta (`fecha_lectura IS NULL`), mutación rechazada | **OK** |
| **T14-09** | IDOR / Privacidad | IDOR: Docente no puede consultar nómina de estudiantes de sección no asignada | Rechazo estricto HTTP 403 al solicitar sección que no pertenece a su asignación | **OK** |
| **T14-10** | IDOR / Privacidad | IDOR: Historial de asistencia ignora parámetros manipulados y aísla identidad de sesión | El endpoint ignora `?id_docente` de cliente y consulta únicamente el de la sesión | **OK** |
| **T14-11** | Inyección SQL | SQL Injection: Payload de inyección en consulta pública neutralizado de forma segura | Búsqueda rechazada o sin resultados controlados, sin error de sintaxis SQL | **OK** |
| **T14-12** | Inyección SQL | SQL Injection: 100% de consultas en repositorios utilizan sentencias preparadas PDO | 0 concatenaciones directas detectadas; 100% sentencias preparadas PDO | **OK** |
| **T14-13** | XSS | XSS: Sanitización contextual obligatoria mediante helper e() contra Cross-Site Scripting | Helpers `e()` y `htmlspecialchars` neutralizan etiquetas HTML/JS en vistas | **OK** |
| **T14-14** | CSRF | CSRF: Rechazo estricto (HTTP 403 Forbidden) ante peticiones POST que omiten token CSRF | Peticiones POST sin cabecera/parámetro CSRF rechazadas con HTTP 403 | **OK** |
| **T14-15** | CSRF | CSRF: Rechazo estricto (HTTP 403 Forbidden) ante tokens CSRF alterados o no coincidentes | Token alterado o no coincidente con sesión es rechazado con HTTP 403 | **OK** |
| **T14-16** | Sesiones | Sesiones: Regeneración estricta de ID de sesión al autenticar contra Session Fixation | `session_regenerate_id(true)` ejecutado; identificador cambia de forma obligatoria | **OK** |
| **T14-17** | Sesiones | Sesiones: Destrucción total de datos y revocación de identidad en logout | Sesión destruida; acceso posterior a recursos protegidos es bloqueado | **OK** |
| **T14-18** | Sesiones | Sesiones: Política auxiliar de expiración por inactividad prolongada (7200s / 2 horas) (DT-80) | Sesión inactiva por más de 7200s revocada automáticamente y usuario redirigido | **OK** |
| **T14-19** | Autenticación | Autenticación: Cero contraseñas en texto plano y almacenamiento con BCrypt nativo (RNF-03) | Hashes válidos con `$2y$`; cero contraseñas en texto claro en base de datos | **OK** |
| **T14-20** | Autenticación | Autenticación: Mensajes de error neutrales e idénticos mitigan enumeración de usuarios | Mensaje genérico *"Credenciales inválidas."* ante usuario o clave incorrectos | **OK** |
| **T14-21** | Criptografía | Tokens: Generación con CSPRNG (256 bits) y almacenamiento exclusivo en forma de hash SHA-256 | Tokens generados con `random_bytes(32)`; solo hash SHA-256 persistido en BD | **OK** |
| **T14-22** | Carga Archivos | Archivos: Detección estricta de Magic Bytes mediante finfo rechaza PHP camuflado como PNG | Magic bytes reales analizados con `finfo`; payloads de scripts rechazados | **OK** |
| **T14-23** | Perímetro | Archivos: Protección perimetral de storage/.htaccess bloquea acceso web directo a adjuntos | Reglas `.htaccess` en `storage/` devuelven 403/404 ante solicitudes HTTP directas | **OK** |
| **T14-24** | Path Traversal | Archivos: Sanitización de nombres mitiga ataques de Directory / Path Traversal (DT-58) | Nombres con `../` saneados o asignados mediante hashes aleatorios independientes | **OK** |
| **T14-25** | Privacidad (RNF-04) | Privacidad (RNF-04): Mínima exposición verificada en consulta pública escolar | Exclusión estricta de teléfonos, correos y fechas de nacimiento de encargados y menores | **OK** |
| **T14-26** | Privacidad (RNF-04) | Privacidad (RNF-04): Reporte de asistencia protege coordenadas geográficas privadas de docentes | Reporte administrativo excluye latitud y longitud personales del docente | **OK** |
| **T14-27** | Cabeceras HTTP | Cabeceras HTTP: Inyección global de X-Content-Type-Options y X-Frame-Options (DT-77) | Cabeceras `nosniff`, `SAMEORIGIN` y `Referrer-Policy` presentes en respuestas | **OK** |
| **T14-28** | Cabeceras HTTP | Cabeceras HTTP: Cache-Control (no-store, no-cache) activo en rutas privadas y de auth (DT-78) | Cabeceras `no-store, no-cache, must-revalidate` inyectadas en rutas sensibles | **OK** |
| **T14-29** | Manejo de Errores | Manejo de errores: Vistas de error 404/500 no exponen rutas físicas locales ni trazas SQL | Vistas 404/500 controladas sin stack traces ni nombres de archivos internos | **OK** |
| **T14-30** | Logging Seguro | Logging seguro: Logger sanitiza y enmascara automáticamente contraseñas, tokens y CUI | Valores sensibles enmascarados como `********` en registros de bitácora | **OK** |
| **T14-31** | HTTP Real | Flujo HTTP real: Verificación de cabeceras de seguridad (nosniff, SAMEORIGIN) en servidor web real | Status 200 y cabeceras de seguridad comprobadas vía cURL en servidor embebido | **OK** |
| **T14-32** | Integridad y Fixtures | Gestión de fixtures: Cero registros temporales permanentes y schema.sql intacto | Limpieza atómica de fixtures garantizada y archivo `schema.sql` 100% inalterado | **OK** |

---

### Resumen de Ejecución

- **Total ejecutadas:** 32
- **Exitosas (OK):** 32
- **Fallidas (FALLO):** 0
- **Tasa de Aprobación:** 100.0%
- **Regresión Histórica Acumulada (Fases 2 a 14):** 380/380 pruebas superadas satisfactoriamente (100% GREEN).
