# MATRIZ Y RESULTADOS DE PRUEBAS AUTOMATIZADAS — FASE 9

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 9 — Gestión de Permisos Docentes  
**Rol:** ANTIGRAVITY — DESARROLLADOR PRINCIPAL  
**Estado:** **100% GREEN (44/44 PRUEBAS SUPERADAS SATISFACTORIAMENTE)**  

---

## 1. Resumen Ejecutivo de la Suite de Pruebas

El archivo de pruebas automatizadas `tests/run_tests_fase9.php` contiene 44 pruebas exhaustivas que cubren de extremo a extremo las reglas de negocio, integridad de base de datos relacional, seguridad de archivos, control de concurrencia y regresión total de las fases previas.

```
============================================================
RESUMEN DE PRUEBAS DE FASE 9: PERMISOS DOCENTES
============================================================
Total ejecutadas : 44
Exitosas (OK)    : 44
Fallidas (FALLO) : 0
============================================================
RESULTADO: TODAS LAS PRUEBAS DE FASE 9 SUPERADAS SATISFACTORIAMENTE (100% GREEN).
```

---

## 2. Detalle Exhaustivo de Pruebas por Bloque

### Bloque 1: Registro de Solicitudes y Validaciones (RF-24, DT-56)
- **`T9-01` [OK]:** Estructura relacional de permisos en MySQL, integridad referencial y restricciones canónicas (`chk_solicitudes_estado`).
- **`T9-02` [OK]:** Registro exitoso de solicitud de permiso válida sin documento de soporte (carácter opcional verificado).
- **`T9-03` [OK]:** Creación atómica del historial de auditoría al registrar solicitud (`estado_anterior = NULL`, `estado_nuevo = PENDIENTE`).
- **`T9-04` [OK]:** Validación estricta de fecha solicitada (obligatoria, formato `Y-m-d` válido y coherencia de calendario).
- **`T9-05` [OK]:** Validación de `tipo_motivo` obligatorio, no vacío tras trim y longitud máxima de 100 caracteres.
- **`T9-06` [OK]:** Validación de `descripcion` obligatoria, no vacía tras trim y longitud máxima de 1000 caracteres.
- **`T9-07` [OK]:** Protección contra parameter tampering / spoofing: el parámetro `id_docente` enviado por el cliente es ignorado, resolviendo siempre la identidad desde la sesión.
- **`T9-08` [OK]:** Protección contra asignación masiva (Mass Assignment): una solicitud nueva jamás puede nacer en estado `APROBADO` o `RECHAZADO`.

### Bloque 2: Carga, Validación y Seguridad de Documentos de Soporte (RF-25, DT-58, DT-59, DT-60)
- **`T9-09` [OK]:** Carga y validación exitosa de documento PDF con verificación de magic bytes reales (`%PDF`).
- **`T9-10` [OK]:** Carga y validación exitosa de imagen PNG legítima.
- **`T9-11` [OK]:** Rechazo estricto de archivo ejecutable o script PHP camuflado con extensión permitida (`malicioso.php.png` o binario con tags PHP).
- **`T9-12` [OK]:** Rechazo estricto de documento que excede la cuota de 5.0 MB ($5,242,880$ bytes).
- **`T9-13` [OK]:** Almacenamiento seguro en disco con nombre hasheado unívoco (`random_bytes`) y saneamiento contra directory traversal.
- **`T9-14` [OK]:** Verificación de inaccesibilidad directa vía HTTP de archivos almacenados en `storage/permisos/` (HTTP 404 / 403).
- **`T9-15` [OK]:** Descarga autorizada de documento por el docente propietario con headers de seguridad (`nosniff`, `Content-Disposition`).
- **`T9-16` [OK]:** Aislamiento horizontal: Docente ajeno no puede descargar el documento de soporte de otro docente (HTTP 403 Forbidden).
- **`T9-17` [OK]:** Descarga autorizada de documento de soporte para Administrador con fines de auditoría directiva.

### Bloque 3: Bandeja Administrativa, Filtros y Resolución Transaccional (RF-27, RF-28, DT-56, DT-61)
- **`T9-18` [OK]:** Listado personal de permisos del docente con orden cronológico descendente y aislamiento horizontal estricto.
- **`T9-19` [OK]:** Detalle de permiso: Denegación de acceso a solicitud ajena para rol docente (HTTP 403).
- **`T9-20` [OK]:** Bandeja administrativa global con filtros combinados por estado (`PENDIENTE`, `APROBADO`, `RECHAZADO`) y por docente.
- **`T9-21` [OK]:** Aprobación administrativa de solicitud de permiso con registro de comentario directivo.
- **`T9-22` [OK]:** Rechazo administrativo de solicitud de permiso con justificación obligatoria.
- **`T9-23` [OK]:** Inserción inmutable de la transición en `historial_solicitudes_permiso` con `id_usuario_actor` y `CURRENT_TIMESTAMP(6)`.
- **`T9-24` [OK]:** Inmutabilidad de estado terminal: solicitudes en estado `APROBADO` o `RECHAZADO` no admiten mutaciones posteriores.
- **`T9-25` [OK]:** Control de roles (RBAC): Docente no puede ejecutar endpoints administrativos de resolución (HTTP 403).

### Bloque 4: Seguridad Perimetral, Integridad y Trazabilidad (RNF-06, DT-56, DT-57)
- **`T9-26` [OK]:** Protección CSRF estricta en todos los endpoints POST de registro y resolución.
- **`T9-27` [OK]:** Sanitización rigurosa contra Cross-Site Scripting (XSS) en formularios y tablas del portal docente y administrativo.
- **`T9-28` [OK]:** Inmutabilidad arquitectónica: Cero endpoints y cero métodos en repositorio para editar o borrar el historial.
- **`T9-29` [OK]:** Rechazo de registro de permisos para docentes inactivos o usuarios no autenticados (HTTP 302 / 403).
- **`T9-30` [OK]:** Integridad referencial en base de datos: claves foráneas `RESTRICT` impiden borrado accidental de usuarios o solicitudes con historial.

### Bloque 5: Concurrencia Multiproceso Real y Atomicidad Transaccional (DT-57, DT-62)
- **`T9-31` [OK]:** Concurrencia real multiproceso en resolución con bloqueo pesimista `SELECT ... FOR UPDATE`: dos procesos simultáneos intentan resolver la misma solicitud; exactamente uno triunfa y el segundo es rechazado limpiamente sin sobreescritura.
- **`T9-32` [OK]:** Atomicidad transaccional y rollback completo: ante fallo simulado en la inserción del historial, la solicitud permanece intacta en su estado original.

### Bloque 6: Pruebas HTTP Reales con Servidor PHP Embebido y cURL (RF-24, RF-25, RF-27, RF-28)
- **`T9-33` [OK]:** Flujo HTTP real completo del Docente: Login real con cookies, GET formulario, extracción de token CSRF, envío POST multipart con adjunto binario y visualización en historial.
- **`T9-34` [OK]:** Flujo HTTP real completo del Administrador: Login real, consulta de bandeja, visualización de detalle y POST de aprobación.
- **`T9-35` [OK]:** Verificación HTTP de blindaje de almacenamiento: petición GET directa a archivo en `storage/permisos/` devuelve HTTP 404 / 403.

### Bloque 7: Control Estricto de Alcance de Fase 10
- **`T9-36` [OK]:** Verificación de alcance de `RF-26`: Cero filas creadas en `notificaciones` y cero acoplamiento funcional introducido durante la ejecución de la Fase 9 (`RF-26 PENDIENTE — FASE 10`).

### Bloque 8: Regresiones Completas de Fases Previas (Fases 1 a 8)
- **`T9-37` [OK]:** Regresión completa de **Fase 8** (Asistencia y Geolocalización) — 40/40 pruebas OK.
- **`T9-38` [OK]:** Regresión completa de **Fase 7** (Portal Docente) — 28/28 pruebas OK.
- **`T9-39` [OK]:** Regresión completa de **Fase 6** (Inscripciones y Cupos) — 30/30 pruebas OK.
- **`T9-40` [OK]:** Regresión completa de **Fase 5** (Asignaciones Docentes) — 26/26 pruebas OK.
- **`T9-41` [OK]:** Regresión completa de **Fase 4** (Administración Básica) — 27/27 pruebas OK.
- **`T9-42` [OK]:** Regresión completa de **Fase 3** (Autenticación y Roles) — 19/19 pruebas OK.
- **`T9-43` [OK]:** Regresión completa de **Fase 2** (Arquitectura Base) — 16/16 pruebas OK.
- **`T9-44` [OK]:** Regresión completa de **Fase 1** (Base de Datos Relacional) — 18/18 pruebas de integridad OK en base aislada.
