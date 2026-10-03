# HISTORIAL DE AUDITORÍA Y TRAZABILIDAD — FASE 9

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 9 — Gestión de Permisos Docentes  
**Rol:** ANTIGRAVITY — DESARROLLADOR PRINCIPAL  

---

## 1. Principio de Inmutabilidad y No Repudio (RNF-06, DT-56, DT-57)

El requisito no funcional **RNF-06** exige trazabilidad estricta y garantías de no repudio sobre todas las decisiones administrativas y movimientos de permisos institucionales.

Para satisfacer esta exigencia, la entidad `historial_solicitudes_permiso` funciona como un libro mayor de auditoría de sólo adición (**append-only ledger**):

1. **Inmutabilidad Absoluta en Capa de Aplicación:**
   - La interfaz `PermisoRepositoryInterface` y su implementación `PermisoRepository` carecen intencionalmente de cualquier método de edición (`updateHistorial`) o eliminación (`deleteHistorial`).
   - La única operación admitida sobre el historial es `crearHistorial(array $datos): int`.
2. **Protección Perimetral y Controladores:**
   - Ninguna ruta ni controlador web expone endpoints que permitan mutar o truncar el historial de decisiones.

---

## 2. Estructura Física y Restricciones Relacionales en MySQL

La tabla física `historial_solicitudes_permiso` cuenta con un esquema blindado mediante restricciones de integridad referencial y de dominio:

```sql
CREATE TABLE historial_solicitudes_permiso (
    id_historial_permiso BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_solicitud_permiso BIGINT UNSIGNED NOT NULL,
    id_usuario_actor BIGINT UNSIGNED NOT NULL,
    estado_anterior VARCHAR(20) NULL,
    estado_nuevo VARCHAR(20) NOT NULL,
    comentario VARCHAR(500) NULL,
    fecha_cambio DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT pk_historial_solicitudes_permiso PRIMARY KEY (id_historial_permiso),
    CONSTRAINT chk_historial_estado_anterior CHECK (
        estado_anterior IS NULL
        OR estado_anterior IN ('PENDIENTE', 'APROBADO', 'RECHAZADO')
    ),
    CONSTRAINT chk_historial_estado_nuevo CHECK (
        estado_nuevo IN ('PENDIENTE', 'APROBADO', 'RECHAZADO')
    ),
    CONSTRAINT chk_historial_transicion CHECK (
        estado_anterior IS NULL OR estado_anterior <> estado_nuevo
    ),
    CONSTRAINT fk_historial_solicitudes FOREIGN KEY (id_solicitud_permiso)
        REFERENCES solicitudes_permiso (id_solicitud_permiso)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_historial_usuarios FOREIGN KEY (id_usuario_actor)
        REFERENCES usuarios (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    INDEX idx_historial_solicitud_fecha (
        id_solicitud_permiso, fecha_cambio
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

## 3. Garantías de Auditoría

| Campo | Garantía Técnica | Justificación de Auditoría |
| :--- | :--- | :--- |
| `id_solicitud_permiso` | Clave foránea `RESTRICT` | Impide que solicitudes con historial sean eliminadas físicamente. |
| `id_usuario_actor` | Clave foránea `RESTRICT` hacia `usuarios` | Identifica sin ambigüedad a la persona física que ejecutó la acción (docente o administrador). |
| `estado_anterior` | Nullable solo en creación inicial | Documenta el estado previo exacto antes de la transición. |
| `estado_nuevo` | No nulo, evaluado por `CHECK` | Registra el nuevo estado adquirido. |
| `comentario` | Texto explicativo sanitizado | Justificación formal de la decisión (especialmente requerida en caso de rechazo). |
| `fecha_cambio` | `CURRENT_TIMESTAMP(6)` autoritativo | Marca temporal de microsegundos generada por el motor MySQL. Se descarta cualquier reloj de cliente. |

---

## 4. Atomicidad Transaccional y Prevención de Fugas de Estado (DT-57)

Cada cambio de estado en `solicitudes_permiso` debe ir acompañado obligatoria e indisolublemente de su correspondiente registro en `historial_solicitudes_permiso`.

Si la inserción en el historial llegase a fallar (por violación de longitud, desconexión o error imprevisto), la transacción ejecuta un `rollBack()` completo:

```php
$this->permisoRepo->beginTransaction();
try {
    $this->permisoRepo->updateEstado($id, $nuevoEstado);
    $this->permisoRepo->crearHistorial([
        'id_solicitud_permiso' => $id,
        'id_usuario_actor' => $idUsuarioActor,
        'estado_anterior' => 'PENDIENTE',
        'estado_nuevo' => $nuevoEstado,
        'comentario' => $comentario,
    ]);
    $this->permisoRepo->commit();
} catch (\Throwable $e) {
    $this->permisoRepo->rollBack();
    throw $e;
}
```

Esta garantía impide la existencia de solicitudes resueltas sin trazabilidad documental o historiales huérfanos.
