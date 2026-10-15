# Decisiones Técnicas — Fase 12
## Sistema Web — Escuela Oficial Rural Mixta Lo de Fuentes

**Módulo:** Reportes Institucionales (RF-29 y RF-30) + Actualización de README  
**Fecha:** 12 de octubre de 2026  
**Línea Base Previa:** DT-67 (Fase 11)  

---

### DT-68: Arquitectura en Capas de Reportes Administrativos de Solo Lectura y Desacoplamiento por Interfaces
- **Contexto:** Se requiere implementar las consultas administrativas oficiales para el Reporte de Asistencia Docente (RF-29) y la Consulta de Inscripciones y Cupos (RF-30).
- **Decisión:** 
  1. Mantener rigurosamente la arquitectura en capas institucional:
     - `AdminReporteController` maneja la petición HTTP, verifica RBAC (`ADMINISTRADOR`) y delega al servicio.
     - `ReporteService` aplica validaciones de negocio, coherencia de filtros y consolidación de métricas.
     - `ReporteRepository` encapsula las sentencias SQL preparadas con PDO (`ReporteRepositoryInterface`).
     - Vistas PHP (`views/admin/reportes/`) dedicadas exclusivamente a presentación.
  2. Cero SQL en controladores, servicios, vistas o scripts del frontend.
- **Justificación:** Garantiza separación de responsabilidades, mantenibilidad (RNF-08) y testabilidad unitaria e integración independiente.

---

### DT-69: Reutilización de la Regla Institucional de Cupos sin Tablas de Estadísticas ni Contadores Paralelos
- **Contexto:** RF-30 exige reportar la capacidad, ocupación y disponibilidad de cupos por sección y grado.
- **Decisión:** Reutilizar estrictamente la regla matemática validada en la Fase 6, donde los cupos ocupados corresponden a inscripciones en estado `CONFIRMADA` o `PENDIENTE` dentro de cada sección (`COALESCE(ocupados, 0)`), mientras que las inscripciones `ANULADA` o `RECHAZADA` no restan cupos.
- **Justificación:** Evita inconsistencias de datos, previene la creación de contadores duplicados o paralelos y prescinde de tablas redundantes de reportes en la base de datos, manteniendo `database/schema.sql` completamente estable e intacto.

---

### DT-70: Validación Rigurosa de Filtros Temporales y Acotamiento Técnico por Período Escolar
- **Contexto:** RF-29 requiere reportes por docente y período.
- **Decisión:** 
  1. Validar en la capa de servicio el formato estricto ISO 8601 (`YYYY-MM-DD`) y la existencia en calendario (`checkdate`), aplicando coherencia cronológica (`fecha_fin >= fecha_inicio`).
  2. Implementar como **criterio técnico auxiliar de filtrado** que al seleccionar un período escolar (`id_periodo_escolar`) sin fechas manuales, la consulta acote automáticamente los marcajes al rango de fechas (`fecha_inicio` y `fecha_fin`) de dicho ciclo lectivo.
- **Justificación:** Garantiza consultas coherentes, previene errores de rango y respeta la definición del requisito oficial sin imponer reglas artificiales de puntualidad o tardanza.

---

### DT-71: Política de Mínima Exposición y Privacidad de Menores (RNF-04) en Reportes
- **Contexto:** Los reportes administrativos exponen datos de asistencia y de estudiantes inscritos.
- **Decisión:**
  1. **En asistencia:** Mostrar nombre del docente, código, fecha, hora, tipo de marcaje (`ENTRADA`/`SALIDA`), geocerca institucional y distancia en metros. No exponer contraseñas, hashes ni las coordenadas geográficas directas (`latitud_dispositivo`, `longitud_dispositivo`) del teléfono personal del docente.
  2. **En inscripciones:** Mostrar código de confirmación, nombre del estudiante, código estudiantil, grado, sección y estado. No exponer teléfonos, correos ni datos de contacto de los encargados, protegiendo la confidencialidad de los menores.
- **Justificación:** Cumplimiento del principio de minimización de datos personales y salvaguarda de la privacidad de la comunidad educativa (RNF-04).

---

### DT-72: Mecanismo de No Contaminación de Base de Datos para Pruebas de Fase 12
- **Contexto:** Se requiere asegurar que la suite de Fase 12 no deje datos residuales ni contamine la base de datos con nuevos registros permanentes.
- **Decisión:** Implementar en `Phase12TestSuite`:
  1. Identificadores y códigos prefijados controlados (`F12-`, `adm_f12_`, `doc_f12_`).
  2. Registro en memoria de todas las entidades creadas durante la preparación de la suite.
  3. Ejecución obligatoria de `cleanup()` en bloque `finally`, borrando registros en orden inverso estricto de claves foráneas.
  4. Prueba automatizada específica (T12-30) que audita que la suite de Fase 12 no deje nuevos fixtures o datos temporales tras su ejecución.
- **Justificación:** Garantiza que la ejecución de la suite de Fase 12 sea inocua para la base de datos, manteniendo intacto el estado previo y reservando la depuración global de datos históricos para la Fase 16.
