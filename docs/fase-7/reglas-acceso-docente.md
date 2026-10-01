# REGLAS DE ACCESO Y AISLAMIENTO HORIZONTAL — FASE 7

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 7 — Portal Docente  
**Rol:** ANTIGRAVITY — DESARROLLADOR PRINCIPAL  
**Requisitos Satisfechos:** RF-02 (Roles y permisos), RF-17 (Listado de estudiantes asignados), RNF-04 (Privacidad parcial de datos)

---

## 1. PRINCIPIOS DE ACCESO Y PRIVILEGIO MÍNIMO

El Portal Docente (`/docente`) implementa un modelo estricto de control de acceso fundamentado en el principio de mínimo privilegio y defensa en profundidad:

1. **Autenticación Obligatoria:** Las solicitudes sin sesión válida o anónimas son interceptadas por el middleware `Authenticate` y redirigidas inmediatamente a `/login` con código HTTP `302`.
2. **Segregación Vertical por Rol:** El acceso al portal docente requiere exclusivamente el rol `DOCENTE`. Usuarios con rol `ADMINISTRADOR` o roles ajenos son rechazados con HTTP `403 Forbidden`. La administración del centro educativo no interactúa con el portal del docente, preservando la separación funcional de responsabilidades.
3. **Revalidación Autoritativa en Tiempo Real:** El controlador consulta el estado vigente del usuario y de su ficha en la tabla `docentes`. Si el usuario o el docente ha sido desactivado administrativamente (`estado = 'INACTIVO'`), el acceso se revoca de inmediato emitiendo HTTP `403` con mensaje explicativo institucional.

---

## 2. DERIVACIÓN AUTORITATIVA DE IDENTIDAD

- **Inmutabilidad de Identidad:** La identidad del docente se deriva estrictamente de:
  $$\text{Session::get('auth\_user\_id')} \longrightarrow \text{usuarios.id\_usuario} \longrightarrow \text{docentes.id\_usuario}$$
- **Rechazo de Parámetros de Suplantación:** El sistema jamás acepta ni procesa un identificador de docente (`id_docente`, `docente_id`, `teacher_id`) suministrado por el cliente a través de parámetros de consulta (`query string`), cuerpo de la solicitud (`POST payload`), encabezados HTTP o cookies modificables.
- **Inmunidad ante Parameter Tampering:** Si un atacante inyecta `?id_docente=999`, el controlador ignora por completo el parámetro y carga únicamente la información del docente vinculado a la sesión autenticada.

---

## 3. AISLAMIENTO HORIZONTAL ENTRE DOCENTES

El aislamiento horizontal garantiza que ningún docente pueda acceder, visualizar ni manipular información académica, secciones o nóminas de estudiantes pertenecientes a otros docentes del centro educativo.

### 3.1. Barrera en Capa de Control y Servicio (HTTP 403)
Cuando una solicitud incluye el parámetro de sección (`?seccion=X` o `?id_seccion=X`), el controlador y el servicio verifican si dicha sección forma parte de las asignaciones formalmente activas del docente (`isSeccionAsignadaADocente($idDocente, $idSeccion)`):
```php
if (!$this->portalService->isSeccionAsignadaADocente($docente->id_docente, $idSeccionSolicitada)) {
    return new Response(View::render('errors.403', [
        'title' => 'Acceso Denegado - 403',
        'message' => 'No tiene autorización para consultar la nómina de estudiantes ni la información de la sección solicitada.',
        'userRole' => 'DOCENTE',
        'requiredRole' => 'DOCENTE TITULAR DE LA SECCIÓN',
    ], 'main'), 403, ['Content-Type' => 'text/html; charset=utf-8']);
}
```

### 3.2. Barrera a Nivel de Repositorio y Base de Datos (SQL Join Constraint)
A nivel de persistencia relacional, la consulta SQL que recupera los estudiantes de una sección no se basa únicamente en `id_seccion`, sino que ejecuta un `JOIN` vinculante con la tabla `asignaciones_docentes`:
```sql
SELECT e.id_estudiante, e.codigo_estudiante, e.nombres, e.apellidos, e.fecha_nacimiento,
       i.codigo_confirmacion, i.fecha_confirmacion, i.estado AS inscripcion_estado
FROM inscripciones i
JOIN estudiantes e ON e.id_estudiante = i.id_estudiante
JOIN asignaciones_docentes ad ON ad.id_seccion = i.id_seccion
WHERE i.id_seccion = :id_seccion
  AND ad.id_docente = :id_docente
  AND ad.estado = 'ACTIVA'
  AND i.estado = 'CONFIRMADA'
  AND e.estado = 'ACTIVO'
ORDER BY e.apellidos ASC, e.nombres ASC;
```

**Resultado de la Doble Barrera:**
- Si el Docente A intenta forzar en su URL el ID de la Sección del Docente B:
  1. El controlador detecta la discrepancia y emite de inmediato un código HTTP `403`.
  2. Si se invocara directamente la capa del repositorio, la sentencia SQL retorna `0` filas debido a la condición `ad.id_docente = :id_docente AND ad.estado = 'ACTIVA'`.
  3. Cero fuga de datos entre secciones y docentes.

---

## 4. CASOS ESPECIALES DE ACCESO

### 4.1. Docente sin Asignación Activa
- Si un docente ingresa al sistema pero aún no tiene una sección asignada por la administración escolar para el período en curso, el sistema no emite errores 500 ni bloqueos.
- Se presenta una tarjeta institucional con estado amigable:
  > *"Actualmente no tiene una sección asignada. Usted no posee ninguna asignación docente activa en el ciclo lectivo en curso. Para registrar la titularidad de su grado y sección, comuníquese con la dirección administrativa de la escuela."*

### 4.2. Docente con Múltiples Secciones Activas
- Si un docente imparte docencia en más de una sección activa (por ejemplo, multigrado o doble sección), el portal despliega un selector de secciones activas en la parte superior.
- Al alternar entre secciones (`?seccion=ID`), el sistema revalida la titularidad de la sección seleccionada y actualiza la nómina de estudiantes correspondientes de forma instantánea.

### 4.3. Asignaciones Históricas o Cerradas
- Las asignaciones con estado `INACTIVA` quedan archivadas en la base de datos para efectos de trazabilidad histórica (Fase 5), pero son omitidas por completo de las consultas del portal docente activo.
