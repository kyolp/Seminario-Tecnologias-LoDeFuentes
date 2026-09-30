# Reglas de Negocio e Integridad — Asignaciones Docentes (Fase 5)

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 5 — Asignaciones Docentes  
**Alcance Oficial:** RF-16, RN-DOC-14  
**Fecha:** Septiembre 2026  

---

## 1. Unicidad de Asignación Activa por Sección

### 1.1 Regla Fundamental
> Una sección académica solo puede tener **un único docente titular asignado con estado 'ACTIVA'** en un momento determinado.

### 1.2 Mecanismo Técnico en Base de Datos
La restricción está asegurada físicamente en el motor de base de datos MySQL 8.0 / InnoDB mediante la combinación de una columna generada almacenada (`STORED`) y una restricción única:

```sql
id_seccion_activa BIGINT UNSIGNED GENERATED ALWAYS AS (
    CASE WHEN estado = 'ACTIVA' THEN id_seccion ELSE NULL END
) STORED,
CONSTRAINT uk_asignaciones_seccion_activa UNIQUE (id_seccion_activa)
```

**Comportamiento del motor:**
1. Cuando una asignación tiene `estado = 'ACTIVA'`, `id_seccion_activa` toma el valor numérico de `id_seccion`.
2. Si se intenta insertar o activar una segunda asignación para la misma sección, el motor dispara un error de duplicidad:
   `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '<id_seccion>' for key 'asignaciones_docentes.uk_asignaciones_seccion_activa'`.
3. Cuando una asignación pasa a `estado = 'INACTIVA'`, `id_seccion_activa` se evalúa como `NULL`. En el estándar SQL y en InnoDB, múltiples valores `NULL` no colisionan en un índice único, permitiendo conservar infinitos registros históricos inactivos para esa misma sección.

### 1.3 Validación Preventiva en Capa de Servicio
Antes de ejecutar la inserción en base de datos, `AsignacionService` consulta si existe una asignación activa:
```php
if ($estado === 'ACTIVA' && $this->asignacionRepo->existsActiveForSeccion($idSeccion)) {
    return [
        'success' => false,
        'message' => 'La sección seleccionada ya cuenta con una asignación docente activa vigente.',
        'errors' => ['id_seccion' => 'Ya existe un docente asignado activamente a esta sección.']
    ];
}
```
Si ocurriese una condición de carrera (concurrencia simultánea), la capa de servicio captura la excepción PDO y retorna un mensaje amigable al usuario sin exponer volcados de SQL:
```php
catch (\PDOException $e) {
    if ((int)($e->errorInfo[1] ?? 0) === 1062 && str_contains($e->getMessage(), 'uk_asignaciones_seccion_activa')) {
        return [
            'success' => false,
            'message' => 'Conflicto de asignación: La sección ya fue asignada por otro usuario.',
            'errors' => ['id_seccion' => 'Asignación activa duplicada detectada.']
        ];
    }
}
```

---

## 2. Validación Relacional en Cascada

Una asignación no puede registrarse si alguna de las entidades de su cadena relacional se encuentra inactiva, cerrada o deshabilitada:

1. **Docente y Usuario:**
   - El registro `docentes` debe existir y tener `estado = 'ACTIVO'`.
   - El registro correspondiente en `usuarios` debe existir y tener `estado = 'ACTIVO'`.
   - Si el docente está inactivo o suspendido, no puede ser asignado a ninguna sección.
2. **Sección:**
   - La sección debe existir en la tabla `secciones` y tener `habilitada = 1`.
3. **Grado-Período Escolar:**
   - La sección debe estar vinculada a una tupla válida en `grados_periodos` con `habilitado = 1`.
4. **Período Escolar:**
   - El período escolar vinculado (`periodos_escolares`) no debe estar en estado `'CERRADO'`.
5. **Aula Física:**
   - El aula física seleccionada (`aulas`) debe existir y tener `activa = 1`. No se permite asignar aulas en mantenimiento o dadas de baja.
6. **Coherencia Académica Cruzada:**
   - Si el formulario envía parámetros de contexto (`id_periodo_escolar` o `id_grado`), el servicio verifica que la sección pertenezca estrictamente al período y grado indicados.

---

## 3. Preservación del Historial Docente

### 3.1 Política de No Eliminación Física
> **Queda terminantemente prohibido ejecutar `DELETE` sobre la tabla `asignaciones_docentes` en flujos operativos del sistema.**

- Cuando un docente deja de atender una sección (por renuncia, rotación, permiso prolongado o fin de ciclo), la asignación **se cierra** mediante una actualización de estado:
  - `estado = 'INACTIVA'`
  - `fecha_fin = <fecha_de_cierre>`
- Esto garantiza:
  1. Que se mantenga la trazabilidad de qué docente impartió clases en esa sección durante un período de fechas.
  2. Que en la Fase 8 (Asistencia) y Fase 12 (Reportes), se puedan reconstruir con precisión los marcajes y permisos vinculados a la titularidad docente de ese momento histórico.

---

## 4. Reemplazo Atómico de Docentes

### 4.1 Escenario Operativo
Cuando un docente debe ser sustituido por otro en una sección ya asignada:
- No se debe sobreescribir el campo `id_docente` de la asignación existente, ya que destruiría el historial de quién inició el ciclo escolar en dicha sección.
- El procedimiento correcto consiste en:
  1. Cerrar la asignación del docente saliente fijando `fecha_fin` y `estado = 'INACTIVA'`.
  2. Crear inmediatamente la nueva asignación para el docente entrante con `estado = 'ACTIVA'`, la misma sección, la misma aula y la nueva `fecha_inicio`.

### 4.2 Garantía Transaccional ACID
Ambos pasos deben ejecutarse de manera indivisible dentro de una transacción de base de datos (`beginTransaction` / `commit`):
- Si el cierre de la asignación previa falla $\implies$ `rollBack()`.
- Si la creación de la nueva asignación falla (por ejemplo, por colisión de fechas o datos inválidos) $\implies$ `rollBack()` completo, restaurando la asignación previa en estado `'ACTIVA'` sin pérdida de consistencia.

---

## 5. Inmutabilidad y Whitelisting

### 5.1 Edición de Asignaciones Existentes
En el formulario y controlador de edición (`AdminAsignacionController::update`):
- Los campos estructurales `id_docente` e `id_seccion` son **estrictamente de solo lectura / inmutables**. No se permite reasignar sección o docente desde la edición estándar (debe usarse el flujo formal de Reemplazo).
- Únicamente se permite modificar:
  - `id_aula`: Permite registrar cambios físicos de aula dentro del establecimiento escolar.
  - `fecha_fin`: Permite programar o actualizar la fecha de término de la asignación.
- Parámetros arbitrarios (`id_asignacion_docente`, `id_seccion_activa`, `id_usuario_creador`, roles, etc.) son descartados mediante filtrado por lista blanca en el controlador.
