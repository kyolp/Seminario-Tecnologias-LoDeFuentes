# PLAN DE PRUEBAS INTEGRALES Y COMPATIBILIDAD — FASE 15
## SISTEMA WEB ESCUELA OFICIAL RURAL MIXTA LO DE FUENTES

**Módulo:** Validación Final Funcional, Compatibilidad y Pruebas Integrales  
**Rol:** Antigravity — Desarrollador Principal  
**Fecha:** 20 de octubre de 2026  
**Estado:** EJECUTADO Y VALIDADO AL 100%  

---

## 1. Introducción y Propósito

El presente documento define la estrategia, alcance, entorno de ejecución, diseño de casos de prueba y criterios de aceptación para la **Fase 15: Pruebas Integrales, Compatibilidad y Validación Final Funcional** del Sistema Web Integral para la Escuela Oficial Rural Mixta Lo de Fuentes.

El objetivo primario de esta fase es someter el sistema completo a una validación holística de extremo a extremo (E2E), garantizando que todos los componentes, módulos, servicios, repositorios, controladores y vistas interactúan de forma armónica, segura y eficiente bajo condiciones reales de operación, cumpliendo rigurosamente con los 32 Requerimientos Funcionales (**RF-01 a RF-32**) y los 10 Requerimientos No Funcionales (**RNF-01 a RNF-10**) aprobados en el documento de alcance.

---

## 2. Alcance y Delimitaciones Estrictas

### 2.1 Alcance Comprendido:
1. **Flujos End-to-End Multimodulares:**
   - Flujo Administrativo: Autenticación, gestión de catálogos base (aulas, grados, períodos, secciones), creación atómica de docentes y usuarios, y asignación docente-sección-aula.
   - Flujo Docente: Autenticación de rol docente, acceso a dashboard, consulta de asignación académica y nómina de estudiantes.
   - Flujo Público: Inscripción en línea con control de cupos, generación de código único, consulta de estado por código y generación al vuelo de comprobante PDF oficial (RF-13).
   - Integración Inter-módulos (RF-31): Reflejo automático e instantáneo de la inscripción pública en la nómina del docente asignado a la sección.
   - Flujo de Control de Asistencia: Validación de geocerca escolar institucional (Haversine), marcajes cronológicos de entrada y salida, e inmutabilidad de registros (RF-18 a RF-23).
   - Flujo de Gestión de Permisos y Notificaciones: Creación de solicitud con adjunto validado (magic bytes), notificación automática al administrador (RF-26), resolución administrativa (aprobación/rechazo con bloqueo pesimista) y notificación de retorno al docente solicitante (RF-24 a RF-28).
   - Flujo de Reportes Institucionales: Filtros dinámicos y métricas de asistencia docente (RF-29) y consolidado de inscripciones y ocupación de cupos por sección (RF-30).
   - Flujo de Recuperación de Contraseña (RF-03): Solicitud anti-enumeración, token CSPRNG hasheado en SHA-256, vigencia de 1 hora, uso único e inicio de sesión con nueva credencial.
2. **Seguridad y Privacidad Transversal:**
   - Control de acceso RBAC estricto: Redirección 302 a login para usuarios anónimos y 403 Forbidden para cruce de roles.
   - Aislamiento horizontal anti-IDOR: Bloqueo de acceso entre docentes a solicitudes, documentos, nóminas y notificaciones.
   - Mitigación de SQL Injection (100% prepared statements PDO) y XSS contextual (`e()`).
   - Protección CSRF obligatoria en el 100% de las mutaciones POST.
   - Inyección centralizada de cabeceras HTTP de hardening (`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Cache-Control: no-store`).
   - Privacidad (RNF-04): Mínima exposición en consultas públicas (sin DPI, teléfono, dirección ni IDs internos).
3. **Compatibilidad y Usabilidad:**
   - Compatibilidad de cliente en navegadores modernos (Chromium / Google Chrome y Microsoft Edge).
   - Usabilidad conforme a RNF-01: Formularios con labels semánticos, feedback visual de errores y mensajes comprensibles para personal no técnico.
4. **Rendimiento y Disponibilidad:**
   - Rendimiento conforme a RNF-07: Consultas agregadas ejecutadas en tiempo de respuesta razonable sin cuellos de botella N+1.
   - Disponibilidad conforme a RNF-10: Dependencia de la infraestructura de alojamiento local (Apache/PHP 8.2/MySQL 8) sin servicios SaaS de terceros ni APIs en la nube.

### 2.2 Delimitaciones Estrictas:
1. **Cero nuevas funcionalidades:** No se introdujeron módulos, tablas ni rutas fuera del alcance formalmente aprobado.
2. **Esquema de Base de Datos Intacto:** El archivo `database/schema.sql` se mantiene al **100% inalterado (0 modificaciones)**.
3. **Reserva Formal de Fase 16 para Limpieza de BD:** No se realizó depuración masiva ni homogenización de seeders históricos en esta fase; la depuración de datos residuales está reservada exclusivamente para la **Fase 16**.
4. **Cero `git push`:** La sincronización con repositorios remotos continúa bajo potestad y responsabilidad exclusiva del usuario.
5. **Aislamiento Atómico de Fixtures:** Todos los datos temporales creados para las pruebas integrales utilizan prefijos aislados (`_f15`) y son purgados automáticamente en el bloque `finally` de la suite, garantizando cero residuos.

---

## 3. Entorno de Pruebas

| Parámetro | Configuración Utilizada |
|---|---|
| **Sistema Operativo** | Windows 10/11 Enterprise x64 |
| **Servidor Web Local** | Servidor PHP embebido (`php -S 127.0.0.1:8105`) y Apache 2.4 (XAMPP) |
| **Intérprete PHP** | PHP 8.2.12 (CLI / CGI) con extensiones `pdo_mysql`, `curl`, `fileinfo`, `mbstring`, `openssl` |
| **Motor de Base de Datos** | MySQL Server 8.0.x (InnoDB, utf8mb4, transacciones ACID, SELECT ... FOR UPDATE) |
| **Navegadores Auditados** | Microsoft Edge 130+ y Google Chrome 130+ (Motores Chromium) |
| **Directorio Raíz** | `c:\Users\David\Documents\Universidad\2026\Segundo semestre\seminario de tecnologias\ProyectoFinal` |

---

## 4. Diseño de Casos de Prueba Automatizados (T15-01 a T15-36)

| Código | Módulo / Requisito | Descripción del Caso de Prueba | Criterio de Éxito |
|:---:|---|---|---|
| **T15-01** | Trazabilidad RF | Verificación de los 32 Requerimientos Funcionales (**RF-01 a RF-32**) | 32/32 RF mapeados a controladores/servicios activos y clasificados como VALIDADO. |
| **T15-02** | Trazabilidad RNF | Verificación de los 10 Requerimientos No Funcionales (**RNF-01 a RNF-10**) | 10/10 RNF definidos con criterios técnicos y clasificados como VALIDADO. |
| **T15-03** | Flujo Admin | Autenticación de Administrador, acceso a `/administracion` y carga de catálogos base | HTTP 200 en dashboard administrativo y presencia de catálogos de grados, aulas y períodos. |
| **T15-04** | Flujo Admin | Control de períodos escolares, grados, secciones y cupos iniciales | Período activo, sección con capacidad 25 y cupos disponibles iniciales calculados en 25/25. |
| **T15-05** | Flujo Admin | Creación atómica de docente con usuario y credencial BCrypt | Docente y usuario creados en transacción; verificación de código unívoco y `password_verify` OK. |
| **T15-06** | Flujo Admin | Asignación académica de docente a sección y aula | Asignación activa confirmada por `PortalDocenteService` con control de unicidad de sección activa. |
| **T15-07** | Flujo Docente | Autenticación de rol DOCENTE, acceso a `/docente` y restricción a administración | HTTP 200 en `/docente` y HTTP 403 Forbidden al intentar acceder a rutas administrativas. |
| **T15-08** | Flujo Docente | Consulta autorizada de nómina de estudiantes con aislamiento horizontal | Docente asignado visualiza nómina inicial (0 estudiantes); docente no asignado recibe denegación. |
| **T15-09** | Flujo Público | Inscripción web procesada con código único y descuento de cupos (RF-09, RF-11, RF-12) | Inscripción en estado CONFIRMADA, código generado (`INS-...`) y cupos disponibles decrementados (25 -> 24). |
| **T15-10** | Inter-Módulos | Reflejo inmediato de inscripción pública en nómina docente (**RF-31**) | Estudiante inscrito aparece de forma inmediata en la nómina de la sección del docente (Total: 1). |
| **T15-11** | Flujo Público | Consulta de estado por código de confirmación con privacidad RNF-04 (**RF-14**) | Retorna estudiante, grado y estado CONFIRMADA; sin exposición de teléfonos, DPI ni direcciones. |
| **T15-12** | Flujo Público | Generación y descarga al vuelo de comprobante de inscripción oficial en PDF (**RF-13**) | HTTP 200, firma `%PDF-`, contenido superior a 1000 bytes y cabeceras de descarga seguras. |
| **T15-13** | Flujo Asistencia | Marcaje docente de ENTRADA dentro de geocerca escolar (**RF-18, RF-20**) | Marcaje exitoso a < 50m de la institución con campo persistido `dentro_radio = 1`. |
| **T15-14** | Flujo Asistencia | Marcaje docente de SALIDA y validación estricta de secuencia (**RF-21, RF-22**) | Salida registrada exitosamente; segundo intento consecutivo de salida rechazado por regla de secuencia. |
| **T15-15** | Flujo Asistencia | Rechazo fuera de geocerca y consulta de historial (**RF-19, RF-23**) | Marcaje a > 10km rechazado con `DomainException`; consulta de historial retorna orden cronológico DESC. |
| **T15-16** | Flujo Permisos | Creación de solicitud docente con soporte adjunto validado (**RF-24, RF-25**) | Solicitud en estado PENDIENTE con metadatos de documento y almacenamiento protegido en `storage/permisos/`. |
| **T15-17** | Flujo Permisos | Notificación automática en tiempo real al administrador (**RF-26**) | Notificación de tipo `NUEVA_SOLICITUD` insertada atómicamente en estado no leída para el administrador. |
| **T15-18** | Flujo Permisos | Aprobación administrativa, historial inmutable y notificación al docente (**RF-27, RF-28**) | Solicitud pasa a APROBADO, historial de auditoría registrado y notificación de retorno al docente. |
| **T15-19** | Flujo Reportes | Generación de reporte de asistencia docente con filtros y métricas (**RF-29**) | Reporte consolidado con filtros de fecha, total de entradas, salidas y docentes computados. |
| **T15-20** | Flujo Reportes | Reporte consolidado de inscripciones y disponibilidad de cupos (**RF-30**) | Reporte estructurado con ocupación por sección, cupos restantes y listado de estudiantes inscritos. |
| **T15-21** | Flujo Recuperación | Solicitud de enlace, token CSPRNG y anti-enumeración (**RF-03**) | Token hexadecimal seguro de 64 chars, hash SHA-256 en BD y respuestas neutrales para existentes/inexistentes. |
| **T15-22** | Flujo Recuperación | Restablecimiento de contraseña, token de un solo uso y login (**RF-03**) | Contraseña actualizada con BCrypt, token marcado como USADO e inicio de sesión exitoso con nueva clave. |
| **T15-23** | Seguridad | RBAC estricto: Denegación anónima (302) y cruzada (403) | Anónimo redirigido a `/login`; acceso de rol Docente en Admin y Admin en Docente denegados con 403. |
| **T15-24** | Seguridad | Aislamiento horizontal anti-IDOR en nóminas, permisos y notificaciones | Docente ajeno no puede ver permisos, descargar archivos ni consultar nóminas de otros docentes. |
| **T15-25** | Seguridad | Mitigación de SQL Injection y uso universal de prepared statements (RNF-05) | Payloads de inyección (`' OR '1'='1`, `'; DROP TABLE...`) tratados como literales seguros. |
| **T15-26** | Seguridad | Prevención de Cross-Site Scripting (XSS) contextual con helper `e()` | Inyecciones `<script>` y atributos de evento neutralizados a entidades HTML seguras (`&lt; &gt;`). |
| **T15-27** | Seguridad | Protección CSRF obligatoria en mutaciones POST (RNF-03) | Peticiones POST sin token o con token alterado denegadas estrictamente con HTTP 403 Forbidden. |
| **T15-28** | Seguridad | Inyección transversal de cabeceras HTTP de hardening | Respuestas HTTP incluyen `X-Content-Type-Options: nosniff` y `X-Frame-Options: SAMEORIGIN`. |
| **T15-29** | Privacidad | Mínima exposición pública de datos personales de menores y tutores (RNF-04) | Respuestas públicas no exponen DPI, teléfonos, direcciones particulares ni identificadores internos de BD. |
| **T15-30** | Manejo de Errores | Vistas de error 404 y 500 sanitizadas sin fugas de stack trace (RNF-08) | HTTP 404 renderizado con vista institucional sin rutas físicas de Windows (`C:\`) ni trazas de código. |
| **T15-31** | Integridad Relacional | Verificación de 20 tablas relacionales activas y `schema.sql` intacto (RNF-05) | 20 tablas presentes con FKs y checks; `database/schema.sql` con 0 modificaciones en git. |
| **T15-32** | Usabilidad | Formularios accesibles con etiquetas semánticas y feedback claro (RNF-01) | Vistas clave cumplen con `<label for>`, indicadores obligatorios, feedback de error y componentes alert. |
| **T15-33** | Rendimiento | Consultas agregadas de reportes ejecutadas eficientemente sin N+1 (RNF-07) | Tiempo de respuesta razonable en entorno de prueba (7.23 ms observados sin umbral SLA en documento). |
| **T15-34** | Disponibilidad | Arquitectura auto-contenida dependiente de infraestructura de alojamiento (RNF-10) | Cálculos geodésicos y PDFs generados localmente sin APIs externas pagadas (Google Maps, Stripe, etc.). |
| **T15-35** | Servidor HTTP Real | Validación end-to-end de códigos de respuesta HTTP en servidor web embebido | 200 en Home/Login, 302 en ruta protegida y 404 en ruta inexistente probados vía HTTP real. |
| **T15-36** | Limpieza Atómica | Cero registros temporales permanentes y working tree limpio | Fixtures purgados en bloque `finally`; cero residuos en `usuarios`, `docentes`, `inscripciones`, etc. |

---

## 5. Metodología de Ejecución y Criterios de Aceptación

1. **Ejecución Automatizada:** La suite `tests/run_tests_fase15.php` ejecuta de forma secuencial y determinista las 36 pruebas.
2. **Criterio de Aprobación Global:**
   - 100% de las pruebas de Fase 15 deben resultar exitosas (`36/36 OK`).
   - Cero fallos tolerados (`0 FALLO`).
   - 100% de las pruebas históricas de regresión (Fases 1 a 14) deben resultar exitosas (`398/398 OK`).
   - Total acumulado del proyecto: **434 / 434 pruebas aprobadas (100% GREEN)**.
3. **Criterio de No Regresión:** La base de datos, el archivo `schema.sql`, las rutas y los controladores deben conservar estricta compatibilidad con las especificaciones técnicas DT-01 a DT-81.
