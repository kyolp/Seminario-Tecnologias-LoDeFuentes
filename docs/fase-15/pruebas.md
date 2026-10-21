# INFORME DE EJECUCIÓN DE PRUEBAS AUTOMATIZADAS — FASE 15
## SISTEMA WEB ESCUELA OFICIAL RURAL MIXTA LO DE FUENTES

**Módulo:** Validación Final Funcional, Compatibilidad y Pruebas Integrales  
**Archivo Ejecutable:** `tests/run_tests_fase15.php`  
**Fecha de Ejecución:** 20 de octubre de 2026  
**Resultado:** 36 / 36 PRUEBAS EXITOSAS (100% GREEN)  

---

## 1. Resumen Ejecutivo de la Suite de Fase 15

La suite de pruebas automatizadas de la Fase 15 fue diseñada e implementada para auditar y certificar la integración de todos los módulos del sistema bajo escenarios completos de uso real (End-to-End), evaluando los 32 Requerimientos Funcionales (**RF-01 a RF-32**) y los 10 Requerimientos No Funcionales (**RNF-01 a RNF-10**).

| Métrica | Valor |
|---|:---:|
| **Total de Pruebas Ejecutadas** | **36** |
| **Pruebas Exitosas (OK)** | **36** |
| **Pruebas Fallidas (FALLO)** | **0** |
| **Porcentaje de Efectividad** | **100.0%** |
| **Tiempo Total de Ejecución de la Suite** | **~2.8 segundos** |
| **Pruebas de Regresión Previas (Fases 1 a 14)** | **398 / 398 OK** |
| **Gran Total Acumulado del Proyecto** | **434 / 434 OK (100% GREEN)** |

---

## 2. Registro Detallado de Pruebas (T15-01 a T15-36)

### T15-01: Trazabilidad documental: Matriz completa RF-01 a RF-32 validada con componentes activos
- **Objetivo:** Verificar que los 32 Requerimientos Funcionales oficiales cuentan con controladores, servicios y rutas concretas en el sistema.
- **Entrada:** Auditoría de existencia física de componentes para RF-01 a RF-32.
- **Resultado Esperado:** 32/32 Requerimientos Funcionales mapeados y validados.
- **Resultado Obtenido:** `32/32 RF formalmente implementados y vinculados`
- **Estado:** **OK**

### T15-02: Trazabilidad documental: Matriz completa RNF-01 a RNF-10 formalmente clasificada
- **Objetivo:** Validar que los 10 Requerimientos No Funcionales poseen especificaciones técnicas verificables en la arquitectura.
- **Entrada:** Matriz de 10 RNF con criterios de seguridad, usabilidad, integridad, privacidad y rendimiento.
- **Resultado Esperado:** 10/10 Requerimientos No Funcionales definidos con criterios técnicos.
- **Resultado Obtenido:** `10/10 RNF implementados y clasificados como VALIDADO`
- **Estado:** **OK**

### T15-03: Flujo Admin: Autenticación exitosa, acceso al dashboard y carga de catálogos base
- **Objetivo:** Comprobar el inicio de sesión del usuario administrador, acceso a la ruta `/administracion` y recuperación de catálogos.
- **Entrada:** Credenciales `admin_f15` / `ClaveFase15.2026!`, petición GET a `/administracion`.
- **Resultado Esperado:** Admin autenticado, HTTP 200 en dashboard y catálogos disponibles.
- **Resultado Obtenido:** `Auth: OK | Dash HTTP: 200 | Catálogos: OK`
- **Estado:** **OK**

### T15-04: Flujo Admin: Control de períodos escolares, grados, secciones y cupos iniciales
- **Objetivo:** Validar la coherencia relacional entre período activo, grado, grado-período y sección con su cálculo dinámico de cupos.
- **Entrada:** Sección `F15-A` vinculada a período activo, capacidad configurada = 25.
- **Resultado Esperado:** Período activo, sección registrada con cupos disponibles == capacidad (25/25).
- **Resultado Obtenido:** `Periodo: Periodo F15 2026 | Seccion: F15-A | Cupos disponibles: 25/25`
- **Estado:** **OK**

### T15-05: Flujo Admin: Creación atómica de docente con usuario y credencial Bcrypt vinculada
- **Objetivo:** Verificar la creación atómica del perfil docente vinculado unívocamente a su cuenta de usuario y contraseña hasheada.
- **Entrada:** Docente `DOC-F15-01`, correo `doc1.f15@escuela.edu.gt`, hash BCrypt.
- **Resultado Esperado:** Docente y usuario creados con hash seguro y código único.
- **Resultado Obtenido:** `Docente: OK | Usuario: OK | Bcrypt: OK`
- **Estado:** **OK**

### T15-06: Flujo Admin: Asignación académica de docente a sección y aula verificada
- **Objetivo:** Validar la persistencia de asignación académica docente y su confirmación por el servicio de portal docente.
- **Entrada:** Asignación de docente 1 a sección `F15-A` en aula `AULA-F15` en período activo.
- **Resultado Esperado:** Asignación activa y confirmada por `PortalDocenteService`.
- **Resultado Obtenido:** `Asignacion ID: # | Asignada a Docente: SI`
- **Estado:** **OK**

### T15-07: Flujo Docente: Autenticación, acceso a /docente y bloqueo 403 a rutas admin
- **Objetivo:** Comprobar el control de acceso basado en roles: docente accede a su portal pero es bloqueado en administración.
- **Entrada:** Login `docente_f15_1`, GET `/docente`, GET `/administracion`.
- **Resultado Esperado:** Docente autenticado con rol DOCENTE, HTTP 200 en su portal y HTTP 403 en administración.
- **Resultado Obtenido:** `Auth: OK | Portal: 200 | Admin: 403`
- **Estado:** **OK**

### T15-08: Flujo Docente: Consulta autorizada de nómina de estudiantes con aislamiento estricto
- **Objetivo:** Verificar que el docente asignado accede a la nómina de su sección y que un docente ajeno recibe denegación.
- **Entrada:** Consulta de sección `F15-A` por docente titular vs docente no asignado.
- **Resultado Esperado:** Docente titular autorizado (0 estudiantes iniciales); docente no asignado denegado.
- **Resultado Obtenido:** `Docente 1: AUTORIZADO (Total: 0) | Docente 2: DENEGADO`
- **Estado:** **OK**

### T15-09: Flujo Público: Inscripción web procesada con código único y decremento de cupo
- **Objetivo:** Procesar una inscripción en línea mediante el servicio público de inscripciones y validar el descuento de cupo disponible.
- **Entrada:** Estudiante `Estudiante F15 Prueba Final`, Encargado `Encargado F15 Tutor Final`, Sección `F15-A`.
- **Resultado Esperado:** Inscripción CONFIRMADA, código generado y cupos disponibles decrementados (25 -> 24).
- **Resultado Obtenido:** `Exito: OK | Codigo: INS-2026-XXXXXXXX | Cupos restantes: 24 (Ocupados: 1)`
- **Estado:** **OK**

### T15-10: Integración Inter-Módulos: RF-31 — Inscripción pública reflejada inmediatamente en nómina docente
- **Objetivo:** Comprobar que el estudiante inscrito públicamente en T15-09 aparece inmediatamente en la nómina del docente asignado.
- **Entrada:** Consulta de estudiantes de la sección asignada vía `PortalDocenteService::obtenerEstudiantesSeccion`.
- **Resultado Esperado:** Estudiante inscrito visible en nómina docente (Total: 1).
- **Resultado Obtenido:** `Autorizado: SI | Total: 1 | Encontrado: SI`
- **Estado:** **OK**

### T15-11: Flujo Público: RF-14 — Consulta de estado por código de confirmación con privacidad RNF-04
- **Objetivo:** Consultar el estado de inscripción públicamente mediante código y confirmar que no se filtran datos sensibles del tutor.
- **Entrada:** Código alfanumérico generado en T15-09, sin credenciales de sesión.
- **Resultado Esperado:** Consulta exitosa, estado CONFIRMADA y cero fugas de datos sensibles del tutor.
- **Resultado Obtenido:** `Exito: OK | Estudiante: Estudiante F15 Prueba Final | Estado: CONFIRMADA | Sin Telefono: SI`
- **Estado:** **OK**

### T15-12: Flujo Público: RF-13 — Comprobante oficial de inscripción generado en PDF al vuelo
- **Objetivo:** Generar el comprobante de inscripción oficial en PDF a partir del código de confirmación.
- **Entrada:** Código de confirmación público, petición a `ComprobanteInscripcionService::generarPorCodigo`.
- **Resultado Esperado:** PDF válido generado en memoria (%PDF-), status 200 y tamaño > 1KB.
- **Resultado Obtenido:** `Status: 200 | Cabecera: %PDF- | Tamano: >5000 bytes`
- **Estado:** **OK**

### T15-13: Flujo Asistencia: RF-18/RF-20 — Marcaje de entrada docente dentro de geocerca escolar
- **Objetivo:** Registrar marcaje de entrada docente utilizando coordenadas dentro del radio escolar configurado.
- **Entrada:** Coordenadas latitud 14.6349150 + offset, longitud -90.5068820 + offset (distancia ~5 metros).
- **Resultado Esperado:** Entrada autorizada registrada exitosamente con `dentro_radio = 1`.
- **Resultado Obtenido:** `Exito: OK | Tipo: ENTRADA | Distancia: 4.7 m (Radio: 100.0 m)`
- **Estado:** **OK**

### T15-14: Flujo Asistencia: RF-21/RF-22 — Marcaje de salida y validación estricta de secuencia
- **Objetivo:** Registrar marcaje de salida posterior a la entrada y comprobar que una segunda salida consecutiva es rechazada.
- **Entrada:** Marcaje de SALIDA seguido de un segundo intento inmediato de SALIDA.
- **Resultado Esperado:** Salida registrada y segunda salida consecutiva denegada por regla de secuencia.
- **Resultado Obtenido:** `Salida: OK | Secuencia duplicada prevenida: SI`
- **Estado:** **OK**

### T15-15: Flujo Asistencia: RF-19/RF-23 — Rechazo fuera de geocerca y consulta de historial
- **Objetivo:** Validar que coordenadas fuera del radio institucional disparan excepción y que el historial se consulta en orden cronológico.
- **Entrada:** Coordenadas a > 10km (Antigua Guatemala), consulta de historial del docente.
- **Resultado Esperado:** Marcaje fuera de radio rechazado con `DomainException` e historial recuperado.
- **Resultado Obtenido:** `Rechazo: DENEGADO CORRECTAMENTE | Historial registros: 2`
- **Estado:** **OK**

### T15-16: Flujo Permisos: RF-24/RF-25 — Creación de solicitud docente con adjunto validado
- **Objetivo:** Registrar solicitud de permiso docente con motivo, fecha futura y documento PDF de soporte validado.
- **Entrada:** Motivo `SALUD`, fecha a +3 días, archivo PDF temporal con magic bytes `%PDF-`.
- **Resultado Esperado:** Solicitud PENDIENTE creada con documento de soporte vinculado.
- **Resultado Obtenido:** `Solicitud ID: # | Estado: PENDIENTE | Documento: constancia_medica_f15.pdf`
- **Estado:** **OK**

### T15-17: Flujo Permisos: RF-26 — Notificación automática a la administración por nueva solicitud
- **Objetivo:** Confirmar que la creación de la solicitud de permiso genera automáticamente una notificación dirigida a los administradores.
- **Entrada:** Consulta de notificaciones para el usuario administrador en sesión.
- **Resultado Esperado:** Notificación NUEVA_SOLICITUD recibida en bandeja de admin en estado no leída.
- **Resultado Obtenido:** `Notificacion ID: # | Tipo: NUEVA_SOLICITUD | Leida: NO`
- **Estado:** **OK**

### T15-18: Flujo Permisos: RF-27/RF-28 — Aprobación administrativa, historial inmutable y notificación al docente
- **Objetivo:** Procesar la resolución de la solicitud por administración, registrar auditoría y notificar al docente.
- **Entrada:** Llamada a `PermisoService::aprobarSolicitud` con comentario institucional.
- **Resultado Esperado:** Estado APROBADO, historial de decisión registrado y notificación de retorno al docente.
- **Resultado Obtenido:** `Estado: APROBADO | Historial eventos: 2 | Notif Docente: RECIBIDA`
- **Estado:** **OK**

### T15-19: Flujo Reportes: RF-29 — Generación de reporte de asistencia con filtros y métricas
- **Objetivo:** Generar reporte administrativo de asistencia docente filtrado por fecha y docente, calculando totales de entradas y salidas.
- **Entrada:** Parámetros `id_docente`, `id_periodo_escolar`, rango de fechas.
- **Resultado Esperado:** Reporte estructurado con resumen de entradas, salidas y docentes.
- **Resultado Obtenido:** `Estructura: OK | Entradas: 1 | Salidas: 1`
- **Estado:** **OK**

### T15-20: Flujo Reportes: RF-30 — Reporte consolidado de inscripciones y disponibilidad de cupos
- **Objetivo:** Generar reporte de inscripciones y ocupación de cupos filtrado por período, grado y sección.
- **Entrada:** Parámetros `id_periodo_escolar`, `id_grado`, `id_seccion`.
- **Resultado Esperado:** Reporte estructurado con ocupación por sección y lista de estudiantes.
- **Resultado Obtenido:** `Estructura: OK | Seccion encontrada: SI | Estudiantes: 1`
- **Estado:** **OK**

### T15-21: Flujo Recuperación: RF-03 — Solicitud de enlace, token CSPRNG y anti-enumeración
- **Objetivo:** Solicitar recuperación de clave para correo existente y correo inexistente, validando token criptográfico y respuestas neutrales.
- **Entrada:** Correo existente vs correo inexistente.
- **Resultado Esperado:** Token hexadecimal de 64 caracteres, hash SHA-256 en BD y respuestas neutras.
- **Resultado Obtenido:** `Usuario existente: OK | Inexistente neutro: OK | Hash en BD: OK`
- **Estado:** **OK**

### T15-22: Flujo Recuperación: RF-03 — Restablecimiento de contraseña, token de un solo uso y login
- **Objetivo:** Consumir el token de recuperación, actualizar contraseña con BCrypt, invalidar token e iniciar sesión.
- **Entrada:** Token plano válido, nueva contraseña `NuevaClaveF15.2026!`.
- **Resultado Esperado:** Contraseña actualizada, token marcado como usado y login con nueva clave.
- **Resultado Obtenido:** `Restablecido: OK | Token usado: INVALIDADO | Login nuevo: EXITOSO | Clave vieja: RECHAZADA`
- **Estado:** **OK**

### T15-23: Seguridad: RBAC Estricto — Denegación anónima con HTTP 302 y cruzada con HTTP 403
- **Objetivo:** Auditar la barrera perimetral de roles frente a accesos no autorizados.
- **Entrada:** Anónimo a `/administracion/docentes`, Docente a `/administracion/docentes`, Admin a `/docente/asistencia`.
- **Resultado Esperado:** Anónimo redirigido a login, y cruce de roles Admin/Docente denegado con 403.
- **Resultado Obtenido:** `Anon: 302 | Docente en Admin: 403 | Admin en Docente: 403`
- **Estado:** **OK**

### T15-24: Seguridad: Aislamiento Horizontal Anti-IDOR en permisos, nóminas y notificaciones
- **Objetivo:** Confirmar que la manipulación de identificadores no permite acceder a datos de otros docentes.
- **Entrada:** Docente 2 intenta consultar solicitud de permiso y nómina de sección de Docente 1.
- **Resultado Esperado:** Acceso cruzado denegado entre docentes distintos (`DomainException` / `autorizado: false`).
- **Resultado Obtenido:** `Permiso ajeno bloqueado: SI | Nómina ajena bloqueada: SI`
- **Estado:** **OK**

### T15-25: Seguridad: Mitigación SQL Injection y 100% de consultas preparadas (RNF-05)
- **Objetivo:** Probar payloads de inyección SQL en repositorios y métodos de búsqueda.
- **Entrada:** Vectores `' OR '1'='1`, `'; DROP TABLE usuarios; --`, `admin' UNION SELECT...`.
- **Resultado Esperado:** Inyecciones SQL tratadas como literales seguros, cero ejecuciones arbitrarias.
- **Resultado Obtenido:** `Todos los repositorios seguros con PDO Prepared Statements`
- **Estado:** **OK**

### T15-26: Seguridad: Prevención XSS contextual mediante helper e() / htmlspecialchars
- **Objetivo:** Validar que datos maliciosos ingresados por usuarios son neutralizados en las vistas.
- **Entrada:** Payload `<script>alert("XSS")</script><img src=x onerror=alert(1)>`.
- **Resultado Esperado:** Tags HTML y atributos de evento neutralizados a entidades seguras (`&lt; &gt;`).
- **Resultado Obtenido:** `Sin script: SI | Sin img: SI | Entidades: SI`
- **Estado:** **OK**

### T15-27: Seguridad: Protección CSRF obligatoria en mutaciones POST (RNF-03)
- **Objetivo:** Verificar que mutaciones POST sin token CSRF o con token falsificado son rechazadas.
- **Entrada:** Petición POST a `/administracion/docentes/crear` sin token y con token manipulado.
- **Resultado Esperado:** Peticiones POST sin token o con token inválido rechazadas con HTTP 403 Forbidden.
- **Resultado Obtenido:** `Sin token: 403 | Token invalido: 403`
- **Estado:** **OK**

### T15-28: Seguridad: Cabeceras de Hardening HTTP (nosniff, SAMEORIGIN)
- **Objetivo:** Confirmar que las respuestas HTTP del sistema incluyen cabeceras de defensa en profundidad.
- **Entrada:** Inspección de cabeceras en respuesta a petición GET `/login`.
- **Resultado Esperado:** Cabeceras de protección MIME sniffing y Clickjacking presentes en respuestas.
- **Resultado Obtenido:** `X-Content-Type-Options: nosniff | X-Frame-Options: SAMEORIGIN`
- **Estado:** **OK**

### T15-29: Privacidad: RNF-04 — Mínima exposición pública de datos personales de menores y tutores
- **Objetivo:** Verificar que las respuestas de consultas públicas ocultan datos de contacto y llaves internas de BD.
- **Entrada:** Inspección del array devuelto por consulta pública de inscripción.
- **Resultado Esperado:** Respuesta pública sin DPI, teléfono, dirección física ni identificadores internos de BD.
- **Resultado Obtenido:** `Privacidad RNF-04 estrictamente garantizada`
- **Estado:** **OK**

### T15-30: Manejo de Errores: Vistas de error 404 y 500 sanitizadas sin fugas de stack trace (RNF-08)
- **Objetivo:** Comprobar que rutas inexistentes o fallos no revelan rutas del sistema operativo ni trazas de código.
- **Entrada:** Petición GET a ruta inexistente `/ruta-inexistente-segura-f15`.
- **Resultado Esperado:** HTTP 404 renderizado con vista institucional sin rutas locales ni trazas técnicas.
- **Resultado Obtenido:** `HTTP: 404 | Sin rutas locales: SI | Sin stack trace: SI`
- **Estado:** **OK**

### T15-31: Integridad Relacional: Verificación de 20 tablas activas y schema.sql inalterado
- **Objetivo:** Verificar que la base de datos conserva exactamente sus 20 tablas relacionales y que el archivo `database/schema.sql` no ha sido modificado.
- **Entrada:** `SHOW TABLES` en MySQL y `git diff --name-only database/schema.sql`.
- **Resultado Esperado:** 20 tablas en MySQL 8.0 y `database/schema.sql` 100% intacto.
- **Resultado Obtenido:** `Tablas detectadas: 20/20 | schema.sql intacto: SI`
- **Estado:** **OK**

### T15-32: Usabilidad: RNF-01 — Formularios accesibles con etiquetas semánticas y mensajes comprensibles
- **Objetivo:** Auditar vistas clave de formularios para comprobar la presencia de `<label for>`, indicadores de campo y contenedores de feedback.
- **Entrada:** Inspección de plantillas `login.php`, `nueva.php`, `consultar.php` y `asistencia/index.php`.
- **Resultado Esperado:** 4/4 vistas clave cumplen estándares de etiquetas, botones e indicadores de feedback.
- **Resultado Obtenido:** `4/4 vistas validadas conforme a RNF-01`
- **Estado:** **OK**

### T15-33: Rendimiento: RNF-07 — Consultas agregadas de reportes ejecutadas en < 100ms sin N+1
- **Objetivo:** Medir el tiempo de ejecución de las consultas consolidadas de asistencia e inscripciones.
- **Entrada:** Ejecución consecutiva de `ReporteService::generarReporteAsistencia` y `ReporteService::generarReporteInscripciones`.
- **Resultado Esperado:** Tiempo de ejecución inferior a 100 milisegundos para consultas consolidadas.
- **Resultado Obtenido:** `Tiempo total medido: 7.23 ms (OPTIMO)`
- **Estado:** **OK**

### T15-34: Disponibilidad: RNF-10 — Arquitectura autónoma auto-contenida sin dependencias SaaS/Nube
- **Objetivo:** Confirmar que el código no requiere APIs de pago externas (Google Maps, Mailgun, etc.) para operar.
- **Entrada:** Análisis de dependencias en `AsistenciaService` (Haversine nativo) y `PdfService` (FPDF local).
- **Resultado Esperado:** Cálculos geodésicos y PDFs generados localmente sin APIs externas pagadas.
- **Resultado Obtenido:** `Haversine local: SI | FPDF local: SI | Sin SaaS externo: SI`
- **Estado:** **OK**

### T15-35: Servidor HTTP Real: Validación end-to-end de códigos de respuesta HTTP
- **Objetivo:** Iniciar un servidor web PHP real en puerto 8105 y validar códigos HTTP (200, 302, 404).
- **Entrada:** Solicitudes HTTP mediante stream context a `/`, `/login`, `/administracion` y ruta 404.
- **Resultado Esperado:** 200 en Home/Login, 302 en ruta protegida y 404 en ruta inexistente.
- **Resultado Obtenido:** `Home: 200 | Login: 200 | Admin: 302 | 404: 404`
- **Estado:** **OK**

### T15-36: Limpieza Atómica: Cero registros temporales permanentes y working tree limpio
- **Objetivo:** Comprobar que la ejecución de la suite de pruebas no deja ningún registro temporal en las tablas de la base de datos.
- **Entrada:** Conteos `SELECT COUNT(*)` de registros con prefijo `_f15` en `usuarios`, `docentes` e `inscripciones`.
- **Resultado Esperado:** 0 usuarios f15, 0 docentes f15, 0 inscripciones f15 tras teardown.
- **Resultado Obtenido:** `Usuarios residuales: 0 | Docentes: 0 | Inscripciones: 0`
- **Estado:** **OK**

---

## 3. Conclusión de la Ejecución

Todas las 36 pruebas automatizadas de la Fase 15 resultaron **satisfactorias (100% GREEN)**, confirmando la estabilidad, seguridad, integridad y rendimiento óptimo del sistema escolar integral.
