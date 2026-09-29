# Transacciones e Integridad — Fase 4

**Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes**  
**Fase 4: Administración Básica**  
**Responsable Técnico:** Antigravity — Desarrollador Principal  
**Fecha:** Septiembre 2026  

---

## 1. Atomicidad Transaccional en Creación de Docentes

El proceso de alta de docentes requiere insertar registros vinculados en dos tablas distintas:
1. `usuarios`: Cuenta de acceso al sistema con contraseña encriptada, correo institucional y rol asignado.
2. `docentes`: Ficha profesional con código docente, fecha de ingreso y vinculación a `id_usuario`.

### Mecanismo de Control Transaccional
En `App\Services\DocenteService::crearDocente()`:
```php
$this->db->beginTransaction();
try {
    // 1. Insertar usuario
    $stmtUser->execute();
    $idUsuario = (int) $this->db->lastInsertId();

    // 2. Insertar docente vinculado
    $idDocente = $this->docenteRepo->createDocente($idUsuario, $codigoDocente, $fechaIngreso, $estado);

    $this->db->commit();
} catch (Throwable $e) {
    if ($this->db->inTransaction()) {
        $this->db->rollBack();
    }
    return ['success' => false, 'message' => 'Error al registrar docente: ' . $e->getMessage()];
}
```

### Verificación Automatizada (Pruebas T4-05 y T4-06)
- **Caso Exitoso (T4-05):** Ambos registros son confirmados de manera indivisible en la base de datos.
- **Caso de Fallo (T4-06):** Se simula una excepción intermedia tras la creación del usuario; el bloque `catch` ejecuta `rollBack()`. La prueba verifica fehacientemente que ni el usuario ni el docente persisten en la base de datos, previniendo cuentas huérfanas en el sistema.

---

## 2. Regla de Período Escolar Activo Único

La institución educativa opera un único ciclo lectivo regular a la vez. En `database/schema.sql`, esta regla se modela mediante una columna generada almacenada (`STORED`):
```sql
clave_periodo_activo TINYINT GENERATED ALWAYS AS (
    CASE WHEN estado = 'ACTIVO' THEN 1 ELSE NULL END
) STORED,
CONSTRAINT uk_periodos_escolares_un_activo UNIQUE (clave_periodo_activo)
```

### Gestión en `PeriodoService`
- **Capa Preventiva:** Antes de insertar o actualizar un período a estado `ACTIVO`, `PeriodoService` consulta `$this->periodoRepo->findActivo()`. Si ya existe uno, retorna un error de validación claro: *"Ya existe un período escolar activo ({nombre}). Solo puede existir un período activo simultáneamente."*
- **Capa Defensiva:** Si ocurriese una condición de carrera, el motor MySQL dispara una violación de la restricción `uk_periodos_escolares_un_activo`, la cual es capturada específicamente convirtiéndola en un mensaje amigable al usuario final.

---

## 3. Capacidad Estrictamente Mayor a Cero en Secciones

En el esquema relacional institucional, la tabla `secciones` impone:
```sql
CONSTRAINT chk_secciones_capacidad CHECK (capacidad > 0)
```

### Manejo en `SeccionService`
- La capacidad es un campo **obligatorio**.
- Debe ser de tipo numérico entero (se rechazan decimales, valores nulos y cadenas alfabéticas).
- Debe ser **estrictamente mayor a 0** ($capacidad \ge 1$).
- La prueba `T4-17` valida que valores `0`, `-10`, `abc` y `25.5` sean rechazados con errores claros en el formulario.

---

## 4. Política de Eliminación y `ON DELETE RESTRICT`

En conformidad con las decisiones técnicas de Fase 1 (DT-05):
- Todas las claves foráneas tienen configurada la acción `ON DELETE RESTRICT`.
- No existen eliminaciones en cascada que puedan provocar pérdida involuntaria de registros académicos.
- **Desactivación Preferente:** Los controladores y servicios promueven el cambio de estado lógico (`ACTIVO` $\to$ `INACTIVO`, `HABILITADO` $\to$ `DESHABILITADO`) en lugar del borrado físico.
- Si un administrador intenta eliminar una entidad con dependencias existentes (por ejemplo, un docente con asignaciones históricas o un grado vinculado a un período), el servicio intercepta la comprobación relacional (`hasAsignaciones()`, `hasGradosPeriodos()`, `hasSecciones()`) y emite una advertencia comprensible en pantalla solicitando la desactivación lógica.
