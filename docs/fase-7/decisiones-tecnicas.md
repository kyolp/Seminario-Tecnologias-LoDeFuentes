# DECISIONES TÉCNICAS — FASE 7

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 7 — Portal Docente  
**Rol:** ANTIGRAVITY — DESARROLLADOR PRINCIPAL  
**Secuencia Histórica de DT:**  
- Fase 1: DT-01 a DT-08  
- Fase 2 inicial: DT-09 a DT-14  
- Corrección Fase 2: DT-15 a DT-18  
- Fase 3: DT-19 a DT-24  
- Fase 4: DT-25 a DT-30  
- Fase 5: DT-31 a DT-36  
- Fase 6: DT-37 a DT-42  
- **Fase 7: DT-43 a DT-48**

---

### DT-43: Derivación Autoritativa de Identidad Docente Basada Exclusivamente en Sesión
- **Contexto:** En el acceso al Portal Docente (`/docente`), el sistema debe resolver con certeza matemática la identidad del profesor sin confiar en entradas del cliente.
- **Decisión:** La identidad del docente se deriva estrictamente del flujo:
  $$\text{Session::get('auth\_user\_id')} \longrightarrow \text{usuarios.id\_usuario} \longrightarrow \text{docentes.id\_usuario}$$
  Cualquier parámetro de suplantación como `id_docente` enviado mediante `GET`, `POST` o encabezados personalizados es completamente ignorado.
- **Impacto:** Neutralización total de ataques de suplantación horizontal y parameter tampering a nivel de identidad docente.

---

### DT-44: Doble Barrera de Aislamiento Horizontal en Capas de Control/Servicio y Repositorio Relacional
- **Contexto:** Se requiere impedir que un docente pueda visualizar las asignaciones o la nómina de estudiantes matriculados en secciones correspondientes a otros docentes.
- **Decisión:** Implementar una defensa en profundidad de dos capas:
  1. *Capa Control/Servicio:* Validación previa mediante `isSeccionAsignadaADocente($idDocente, $idSeccion)` que emite de inmediato un código HTTP `403 Forbidden` si la sección solicitada en la URL (`?seccion=X`) no pertenece a las asignaciones activas del docente.
  2. *Capa de Persistencia:* La consulta de estudiantes en `PortalDocenteRepository` incluye un `JOIN` obligatorio con `asignaciones_docentes` filtrando por `ad.id_docente = :id_docente AND ad.estado = 'ACTIVA'`.
- **Impacto:** Si fallara la validación lógica o se invocara el repositorio de forma directa, la base de datos devuelve estrictamente `0` filas, eliminando cualquier vector de fuga de información.

---

### DT-45: Separación Estricta de Responsabilidades (Arquitectura Limpia)
- **Contexto:** Evitar controladores sobrecargados de lógica relacional o vistas que consulten la base de datos de manera directa.
- **Decisión:** Mantener el patrón desacoplado institucional:
  $$\text{DocenteController} \longrightarrow \text{PortalDocenteService} \longrightarrow \text{PortalDocenteRepository} \longrightarrow \text{PDO / MySQL}$$
  El controlador solo procesa la solicitud HTTP, valida sesión y parámetros, e invoca al servicio; el servicio valida reglas de negocio y orquesta el resumen; el repositorio encapsula sentencias preparadas y mapea resultados limpios.
- **Impacto:** Alta testabilidad unitaria mediante inyección de dependencias, bajo acoplamiento y mantenimiento simplificado.

---

### DT-46: Minimización Estricta de Datos del Estudiante (RNF-04)
- **Contexto:** La visualización de nóminas oficiales debe restringirse a los datos que el docente requiere estrictamente para la gestión de aula.
- **Decisión:** Omitir de forma deliberada en las consultas de nómina cualquier información relativa a los encargados (CUI, números de teléfono, correos electrónicos particulares, direcciones residenciales), así como identificadores primarios internos y hashes de contraseñas.
- **Impacto:** Cumplimiento del principio de necesidad pedagógica (*need-to-know*), elevando el estándar de privacidad institucional de menores conforme al requisito RNF-04.

---

### DT-47: Integración Dinámica en Tiempo Real con Inscripciones Confirmadas (RF-31)
- **Contexto:** Cuando un estudiante se inscribe y es confirmado a través del módulo público (Fase 6), su registro debe quedar visible para el maestro titular sin requerir tareas por lotes ni duplicación física de datos.
- **Decisión:** Realizar la consulta de nómina del docente sobre el modelo de datos canónico relacional (`inscripciones` con estado `'CONFIRMADA'` y `estudiantes` en estado `'ACTIVO'`).
- **Impacto:** Consistencia inmediata: cualquier nueva inscripción confirmada o cualquier anulación/cambio de estado se refleja de forma instantánea y automática en la siguiente carga del portal docente.

---

### DT-48: Manejo Defensivo de Estado para Docentes sin Asignación y Soporte Multiasignación
- **Contexto:** Un docente puede no tener asignada una sección en el ciclo actual, o bien puede tener dos o más secciones asignadas simultáneamente.
- **Decisión:** Diseñar el servicio y la vista para soportar ambos escenarios con gracia:
  1. Si no hay asignaciones activas, se devuelve una estructura limpia con `sin_asignacion => true` y se renderiza un estado amigable sin generar excepciones ni errores 500.
  2. Si hay múltiples asignaciones activas, se renderiza un selector de secciones que permite al docente alternar entre sus aulas autorizadas, seleccionando por defecto la primera asignación activa.
- **Impacto:** Experiencia de usuario consistente, robusta, libre de errores y completamente accesible.
