# REGISTRO Y EVIDENCIA DE PRUEBAS — FASE 10
## SISTEMA WEB ESCUELA OFICIAL RURAL MIXTA LO DE FUENTES

**Módulo:** Notificaciones (RF-26)  
**Autor:** Antigravity — Desarrollador Principal  
**Fecha:** 8 de octubre de 2026  
**Entorno de Pruebas:** PHP 8.2.12 CLI + MySQL 8.0 (XAMPP Windows)  
**Estado:** 27/27 PRUEBAS SUPERADAS (100% OK)  

---

## 1. Resumen Ejecutivo de la Suite de Pruebas

La suite de pruebas automatizadas de Fase 10 (`tests/run_tests_fase10.php`) fue diseñada para verificar de forma integral la generación, integridad referencial, consistencia transaccional, seguridad RBAC/CSRF/XSS, aislamiento horizontal y consumo de notificaciones administrativas originadas por solicitudes de permisos docentes (RF-26).

| Métrica | Valor |
|---|---|
| **Total de pruebas ejecutadas** | **27** |
| **Pruebas superadas (OK)** | **27** |
| **Pruebas fallidas (FAIL)** | **0** |
| **Tasa de éxito** | **100%** |
| **Pruebas con servidor HTTP real** | Sí (Puerto 8098, end-to-end con cURL) |
| **Regresión de Fase 9 (Permisos Docentes)** | **46/46 (100% OK)** |
| **Regresión de Fases 1 a 8** | **Superada sin fallos** |

---

## 2. Detalle de Pruebas Automatizadas

### Bloque 1: Modelo y Entidad de Notificaciones
- **T10-01:** Verificación de estructura relacional de la tabla `notificaciones` en MySQL (columnas `id_notificacion`, `id_solicitud_permiso`, `id_usuario_destinatario`, `tipo`, `titulo`, `mensaje`, `fecha_creacion`, `fecha_lectura` y checks). **[OK]**
- **T10-02:** Verificación de contrato y entidad `Notificacion` (instanciación con propiedades `readonly`, métodos `isLeida()`, `isPendiente()`, `toArray()` y constantes de clase). **[OK]**

### Bloque 2: Generación Automática y Destinatarios Administrativos (RF-26, DT-64, DT-65)
- **T10-03:** Generación automática de notificación al registrar nueva solicitud de permiso docente vía `PermisoService::crearSolicitud()` (RF-26, DT-64). **[OK]**
- **T10-04:** Notificación dirigida a todos los administradores activos con rol `ADMINISTRADOR` (DT-65). **[OK]**
- **T10-05:** Exclusión de administradores inactivos (`estado != 'ACTIVO'`) como destinatarios (DT-65). **[OK]**
- **T10-06:** Exclusión de usuarios con rol `DOCENTE` como destinatarios de la notificación administrativa (DT-65). **[OK]**
- **T10-07:** Atributos obligatorios y formato institucional: `tipo = 'NUEVA_SOLICITUD'`, título descriptivo y mensaje contextual con nombre de docente y fechas (RF-26, DT-65). **[OK]**
- **T10-08:** Estado inicial no leída: `fecha_lectura IS NULL` y verificación de `isPendiente() === true` (DT-66). **[OK]**
- **T10-09:** Timestamp generado por el servidor con microsegundos `CURRENT_TIMESTAMP(6)` (DT-66). **[OK]**

### Bloque 3: Atomicidad Transaccional e Integridad Referencial (DT-64, DT-57)
- **T10-10:** Atomicidad transaccional: Si la transacción de solicitud falla o se revierte, no debe crearse ninguna notificación (cero huérfanas) (DT-64, DT-57). **[OK]**
- **T10-11:** Integridad referencial: `FK id_solicitud_permiso` vinculada; `ON DELETE RESTRICT` impide eliminar solicitud con notificaciones asociadas (DT-64). **[OK]**

### Bloque 4: Repositorio, Consultas y Gestión de Estado (DT-65, DT-66)
- **T10-12:** Repositorio: Consulta paginada y ordenada DESC por `fecha_creacion` para usuario administrador específico (`findByUsuario`) (DT-65, DT-66). **[OK]**
- **T10-13:** Repositorio y Servicio: Contador de no leídas (`contarNoLeidasPorUsuario`, `obtenerConteoNoLeidas`) concuerda exactamente con `fecha_lectura IS NULL` (DT-66). **[OK]**
- **T10-14:** Repositorio y Servicio: Marcar notificación individual como leída actualiza `fecha_lectura` a timestamp no nulo (`NOW(6)`) (DT-66). **[OK]**
- **T10-15:** Restricción de consistencia temporal: `fecha_lectura >= fecha_creacion` (cumple `CHECK` de tabla) (DT-66). **[OK]**
- **T10-16:** Idempotencia al marcar como leída: Marcar una notificación ya leída no produce error y preserva su lectura original (DT-66). **[OK]**
- **T10-17:** Repositorio y Servicio: Marcar todas las notificaciones de un usuario como leídas (`marcarTodasComoLeidas`) (DT-66). **[OK]**
- **T10-18:** Aislamiento horizontal: Administrador 1 no puede marcar como leída la notificación de Administrador 2 (`id_usuario_destinatario != id_usuario`) (DT-65). **[OK]**

### Bloque 5: Seguridad de Endpoints, RBAC, CSRF y Prevención XSS (DT-66)
- **T10-19:** RBAC y control de acceso: Endpoint `GET /administracion/notificaciones` redirige a `/login` para usuario no autenticado (HTTP 302). **[OK]**
- **T10-20:** RBAC y control de acceso: Endpoint `GET /administracion/notificaciones` deniega acceso para usuario con rol `DOCENTE` (HTTP 403). **[OK]**
- **T10-21:** Protección CSRF: Endpoint `POST /administracion/notificaciones/marcar-leida` deniega petición sin token CSRF válido (HTTP 403). **[OK]**
- **T10-22:** Protección CSRF: Endpoint `POST /administracion/notificaciones/marcar-todas` deniega petición sin token CSRF válido (HTTP 403). **[OK]**
- **T10-23:** Prevención de Cross-Site Scripting (XSS): Sanitización estricta contextual con `htmlspecialchars` (`e()`) en vista `views/admin/notificaciones/index.php`. **[OK]**
- **T10-24:** Endpoint AJAX / JSON: `GET /administracion/notificaciones/conteo` devuelve JSON estructurado con `unread_count` para administrador autenticado. **[OK]**

### Bloque 6: Flujo HTTP Real Completo End-to-End con Servidor Embebido (RF-26)
- **T10-25:** Flujo HTTP real completo end-to-end con servidor embebido (puerto 8098):
  1) Docente inicia sesión HTTP y crea solicitud de permiso con documento PDF adjunto.
  2) Servidor genera automáticamente notificación para Administrador en base de datos.
  3) Administrador inicia sesión HTTP y consulta `GET /administracion/notificaciones`, verificando la notificación y su contador badge (`unread_count = 1`).
  4) Administrador envía `POST /administracion/notificaciones/marcar-leida` con CSRF válido.
  5) Administrador consulta bandeja y confirma estado 'LEÍDA' y decremento del contador a 0. **[OK]**

### Bloque 7: Regresiones Completas
- **T10-26:** Regresión completa de Fase 9 (Gestión de Permisos Docentes: 46/46 superadas). **[OK]**
- **T10-27:** Regresión completa de Fases 1 a 8 (verificando estabilidad de módulos anteriores). **[OK]**
