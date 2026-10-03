# DECISIONES TÉCNICAS — FASE 9: GESTIÓN DE PERMISOS DOCENTES

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 9 — Gestión de Permisos Docentes  
**Rol:** ANTIGRAVITY — DESARROLLADOR PRINCIPAL  

---

## DT-56: Máquina de Estados y Ciclo de Vida de Solicitudes de Permiso

- **Estado:** IMPLEMENTADA Y VALIDADA.
- **Contexto:** Las solicitudes de permiso docente requieren un ciclo de vida claro y predecible entre el docente que solicita y la administración que resuelve (`RF-24`, `RF-27`).
- **Decisión:** 
  1. Se implementa un modelo de estados con tres valores canónicos: `PENDIENTE`, `APROBADO`, `RECHAZADO`, respaldado por la restricción física de MySQL `chk_solicitudes_estado CHECK (estado IN ('PENDIENTE', 'APROBADO', 'RECHAZADO'))`.
  2. Toda solicitud nueva inicia obligatoriamente en `PENDIENTE`.
  3. Los estados `APROBADO` y `RECHAZADO` son terminales e inmutables: no se permiten transiciones inversas o modificaciones posteriores una vez resuelta la solicitud.
- **Consecuencias:** Integridad conceptual absoluta, imposibilidad de solicitudes huérfanas de estado y prevención de alteraciones retroactivas.

---

## DT-57: Registro Transaccional Atómico e Inmutable en Historial de Auditoría

- **Estado:** IMPLEMENTADA Y VALIDADA.
- **Contexto:** El requisito `RNF-06` exige no repudio y trazabilidad completa de los eventos de permisos.
- **Decisión:**
  1. Cada evento de ciclo de vida (creación, aprobación, rechazo) inserta obligatoriamente una fila en `historial_solicitudes_permiso` dentro de la misma transacción de base de datos.
  2. La entidad historial es de sólo adición (**append-only**): el repositorio no implementa métodos de actualización ni borrado.
  3. Se registra el actor exacto (`id_usuario_actor`), estado anterior, nuevo estado, comentario y marca de tiempo autoritativa del servidor `CURRENT_TIMESTAMP(6)`.
  4. Ante cualquier fallo en la inserción del historial, se ejecuta `rollBack()` transaccional completo en la solicitud principal.
- **Consecuencias:** Cero desincronización entre el estado actual y la bitácora histórica; trazabilidad inmutable y blindaje contra repudio.

---

## DT-58: Almacenamiento Aislado Fuera de la Raíz Pública y Prevención de Path Traversal

- **Estado:** IMPLEMENTADA Y VALIDADA.
- **Contexto:** Los documentos de soporte pueden contener constancias médicas o información privada, y no deben ser accesibles directamente mediante el servidor web ni permitir manipulación del sistema de archivos.
- **Decisión:**
  1. Los archivos se almacenan en el directorio privado `storage/permisos/`, ubicado estrictamente fuera de `public/`.
  2. Se incluye un archivo `.htaccess` con `Require all denied` para bloquear accesos directos por HTTP.
  3. El nombre de almacenamiento en disco se genera de forma aleatoria mediante `bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext`, desacoplándolo del nombre original del cliente.
  4. Se aplica saneamiento estricto con `basename()` y `realpath()` para evitar cualquier vulnerabilidad de directory traversal (`../`).
- **Consecuencias:** Imposibilidad de acceso no autorizado vía navegador, prevención de colisiones de nombres y protección perimetral del servidor.

---

## DT-59: Streaming Seguro y Control de Descargas Autorizadas con Headers Restrictivos

- **Estado:** IMPLEMENTADA Y VALIDADA.
- **Contexto:** La descarga o visualización de comprobantes debe estar restringida exclusivamente a usuarios autorizados y transmitirse con seguridad.
- **Decisión:**
  1. El acceso se realiza exclusivamente a través de controladores PHP autorizados (`DocentePermisoController::descargarDocumento` y `AdminPermisoController::descargarDocumento`).
  2. Se verifica autorización previa: los docentes solo acceden a documentos de sus propias solicitudes; los administradores tienen acceso global.
  3. El streaming HTTP envía encabezados restrictivos:
     - `Content-Type`: MIME verificado del archivo.
     - `Content-Disposition`: `inline` o `attachment` con nombre original saneado.
     - `X-Content-Type-Options: nosniff`: Mitigación de ataques MIME Confusion / Sniffing.
     - `Cache-Control: private, no-store, must-revalidate`: Prevención de almacenamiento en caches compartidas o proxies.
- **Consecuencias:** Control de acceso riguroso, confidencialidad de datos personales y prevención de ejecución de contenido no confiable.

---

## DT-60: Validación de Archivos en Doble Capa: Whitelist de Extensiones y Magic Bytes por `finfo`

- **Estado:** IMPLEMENTADA Y VALIDADA.
- **Contexto:** Atacantes pueden intentar subir scripts PHP maliciosos o archivos ejecutables renombrándolos con extensiones legítimas como `.pdf` o `.png`.
- **Decisión:**
  1. **Capa 1 (Extensión):** Whitelist estricta permitiendo únicamente `pdf`, `png`, `jpg`, `jpeg`, `webp`.
  2. **Capa 2 (Magic Bytes):** Inspección del flujo binario en disco mediante `finfo_open(FILEINFO_MIME_TYPE)` contrastado contra `application/pdf`, `image/png`, `image/jpeg`, `image/webp`.
  3. **Capa 3 (Filtro Antiejecutable):** Escaneo de los primeros 4KB para detectar secuencias de script incrustadas (`<?php`, `<?=`, `<script`).
  4. **Límite de Tamaño:** Límite máximo estricto de 5.0 MB ($5,242,880$ bytes).
- **Consecuencias:** Neutralización completa de vectores de ataque por subida de código malicioso o desbordamiento de almacenamiento.

---

## DT-61: Aislamiento Horizontal y Resolución de Identidad Docente Estricta desde Sesión

- **Estado:** IMPLEMENTADA Y VALIDADA.
- **Contexto:** Un docente no debe poder registrar permisos a nombre de otro colega ni consultar solicitudes o documentos ajenos.
- **Decisión:**
  1. El parámetro `id_docente` enviado en el cuerpo de peticiones o URL es descartado completamente.
  2. La identidad del docente se resuelve de manera autoritativa desde la sesión autenticada (`AuthService::user()` $\to$ `DocenteRepository::findByUserId()`).
  3. Toda consulta de permisos del docente filtra forzosamente por `WHERE id_docente = :id_docente_sesion`.
  4. Intentos de acceder a un ID de solicitud ajeno devuelven HTTP 403 Forbidden.
- **Consecuencias:** Aislamiento horizontal infranqueable y garantía de privacidad entre docentes.

---

## DT-62: Control de Concurrencia Pesimista (`SELECT ... FOR UPDATE`) en Resolución de Permisos

- **Estado:** IMPLEMENTADA Y VALIDADA.
- **Contexto:** En escenarios de concurrencia (múltiples administradores evaluando solicitudes en paralelo o doble envío de formulario), pueden producirse colisiones o estados inconsistentes.
- **Decisión:**
  1. La resolución administrativa de permisos se ejecuta dentro de una transacción InnoDB (`beginTransaction()`).
  2. Se adquiere un bloqueo pesimista exclusivo a nivel de fila mediante `SELECT * FROM solicitudes_permiso WHERE id_solicitud_permiso = :id FOR UPDATE`.
  3. Se verifica bajo el lock que la solicitud continúe en estado `PENDIENTE`. Si otro proceso ya la resolvió, se rechaza la operación inmediatamente con excepción de dominio y se efectúa `rollBack()`.
- **Consecuencias:** Prevención de condiciones de carrera, garantizando que una solicitud sólo pueda ser aprobada o rechazada exactamente una vez.
