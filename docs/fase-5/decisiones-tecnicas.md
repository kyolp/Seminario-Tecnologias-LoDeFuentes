# Decisiones Técnicas — Fase 5: Asignaciones Docentes

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 5 — Asignaciones Docentes  
**Secuencia Correlativa:** DT-31 a DT-36  
**Fecha:** Septiembre 2026  

---

### DT-31: Modelo de Datos y Gestión de Unicidad Mediante Columna Generada `id_seccion_activa`
- **Contexto:** En una escuela de nivel primario, cada sección académica (grado y sección) debe tener exactamente un docente titular activo en un momento dado, pero puede tener múltiples asignaciones históricas pasadas a lo largo del tiempo o dentro del mismo ciclo lectivo si existió rotación.
- **Decisión:** Aprovechar la columna generada almacenada (`STORED`) definida en el esquema de Fase 1:
  `id_seccion_activa BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN estado = 'ACTIVA' THEN id_seccion ELSE NULL END) STORED`
  junto con la restricción `CONSTRAINT uk_asignaciones_seccion_activa UNIQUE (id_seccion_activa)`.
- **Implementación Técnica:**
  - En las operaciones de inserción y actualización (`INSERT` / `UPDATE`) del `AsignacionRepository`, la columna `id_seccion_activa` se omite de forma explícita de las listas de columnas y parámetros SQL.
  - El motor InnoDB calcula automáticamente el valor a nivel de almacenamiento.
  - Si una petición entrante incluye fraudulentamente un valor para `id_seccion_activa`, este es descartado de inmediato por el controlador y el repositorio, evitando errores de solo lectura o intentos de alteración forzada.
- **Justificación e Impacto:** Garantiza integridad física inquebrantable a nivel de base de datos sin depender exclusivamente de bloqueos lógicos en la aplicación, permitiendo al mismo tiempo ilimitados registros con `estado = 'INACTIVA'` gracias al tratamiento estándar de valores `NULL` en índices únicos.

---

### DT-32: Preservación Histórica y Cierre No Destructivo en Lugar de Eliminación Física
- **Contexto:** En un sistema de gestión escolar, eliminar físicamente registros de asignaciones (`DELETE FROM asignaciones_docentes`) destruiría la trazabilidad de qué docente impartió clases en qué aula y fechas. Esto imposibilitaría auditar calificaciones, generar reportes históricos de asistencia (Fase 8 y Fase 12) o constatar traspasos de grado.
- **Decisión:** Establecer una política de cero eliminaciones físicas en operaciones administrativas de asignaciones. Toda finalización de asignación se realiza mediante un cierre no destructivo:
  - Se actualiza el campo `estado` a `'INACTIVA'`.
  - Se registra la `fecha_fin` correspondiente.
- **Implementación Técnica:**
  - `AsignacionService::cerrarAsignacion()` implementa la lógica de transición.
  - El botón "Cerrar Asignación" en las vistas administrativas ejecuta una solicitud `POST` protegida por CSRF hacia `/administracion/asignaciones/estado` con la acción de cierre.
  - Al actualizarse el estado a `'INACTIVA'`, la columna `id_seccion_activa` pasa automáticamente a `NULL`, liberando la sección para una asignación futura sin perder el registro previo.
- **Justificación e Impacto:** Cumple con las mejores prácticas de auditoría institucional y garantiza que los módulos futuros de asistencia y reportes dispongan siempre de la genealogía completa de asignaciones docentes.

---

### DT-33: Operación Atómica de Reemplazo Docente con Transacción PDO y Rollback
- **Contexto:** Cuando un docente renuncia, toma una licencia prolongada o es trasladado, la sección debe asignarse a un nuevo docente titular. Si este proceso se realiza manualmente en dos pasos desconectados (cierre del docente A y posterior creación del docente B), un error intermedio o una interrupción de red puede dejar la sección en un estado inconsistente (huérfana o con doble asignación).
- **Decisión:** Implementar un flujo dedicado de "Reemplazo Docente" ejecutado de forma indivisible mediante transacciones PDO con aislamiento ACID.
- **Implementación Técnica:**
  - El método `AsignacionService::reemplazarDocenteAtomico()` inicia una transacción con `$this->db->beginTransaction()`.
  - En el primer paso, cierra la asignación saliente fijando `estado = 'INACTIVA'` y `fecha_fin = <fecha_corte>`.
  - En el segundo paso, registra la nueva asignación para el nuevo docente con la misma sección, aula, `estado = 'ACTIVA'` y `fecha_inicio = <fecha_reemplazo>`.
  - Si cualquiera de los pasos falla o arroja una excepción, se ejecuta inmediatamente `$this->db->rollBack()`.
- **Justificación e Impacto:** Elimina por completo condiciones de carrera y fallos parciales durante la rotación de personal docente, garantizando una transición inmediata, documentada y atómica.

---

### DT-34: Inmutabilidad de Claves Relacionales en Edición Estándar y Whitelisting Estricto
- **Contexto:** En el mantenimiento administrativo cotidiano de asignaciones, permitir que un usuario modifique arbitrariamente el docente o la sección de un registro ya existente causaría corrupción del historial de asistencias asociadas a ese registro primario (`id_asignacion_docente`). Además, formularios abiertos a `$_POST` crudo son susceptibles a ataques de asignación masiva (*Mass Assignment*).
- **Decisión:** Restringir la edición estándar (`AdminAsignacionController::update`) a campos no estructurales e implementar filtrado estricto por lista blanca.
- **Implementación Técnica:**
  - En la vista `editar.php`, los campos de docente, sección, grado y período se presentan únicamente como datos informativos de solo lectura.
  - El controlador extrae exclusivamente los campos autorizados:
    ```php
    $allowed = ['id_aula', 'fecha_fin'];
    ```
    Cualquier campo adicional recibido en la petición HTTP (como `id_docente`, `id_seccion`, `id_seccion_activa`, `is_admin`, etc.) es descartado de inmediato.
- **Justificación e Impacto:** Protege la integridad referencial histórica del sistema y neutraliza vectores de ataque por inyección de atributos no autorizados.

---

### DT-35: Desacoplamiento de Capas Modelo-Repositorio-Servicio-Controlador-Vistas para Asignaciones
- **Contexto:** Mantener la consistencia arquitectónica del sistema exige que el módulo de asignaciones no contamine los controladores con consultas SQL ni ubique reglas de validación complejas en las vistas.
- **Decisión:** Extender la arquitectura en capas establecida en Fases 2, 3 y 4:
  - `AsignacionDocente`: Modelo inmutable con propiedades `readonly` y métodos de conveniencia.
  - `AsignacionRepositoryInterface` / `AsignacionRepository`: Aislamiento de consultas PDO parametrizadas y encapsulación de SQL.
  - `AsignacionService`: Orquestación de validaciones académicas en cascada, transacciones y lógica de negocio.
  - `AdminAsignacionController`: Manejo de peticiones HTTP, validación CSRF, redirecciones PRG y renderizado.
  - Vistas PHP puras bajo `views/admin/asignaciones/` con layouts estándar y escape XSS mediante `e()`.
- **Justificación e Impacto:** Facilita la comprobabilidad unitaria e integrada del módulo mediante inyección de dependencias y mantiene un código limpio, legible y altamente mantenible (RNF-08).

---

### DT-36: Aislamiento Estricto de la Fase 5 sin Adelantar Funcionalidades Futuras
- **Contexto:** El proyecto escolar contempla múltiples módulos interconectados (Fase 6: Matrícula y control concurrente de cupos; Fase 7: Portal docente y listado de estudiantes; Fase 8: Asistencia con geocercas). Existe el riesgo de acoplar prematuramente código de fases no autorizadas dentro de la Fase 5.
- **Decisión:** Delimitar con rigor absoluto el alcance de la Fase 5 a la gestión administrativa de asignaciones (RF-16).
- **Implementación Técnica:**
  - RF-17 ("Listado de estudiantes por docente") permanece clasificado como `PENDIENTE` para la Fase 7.
  - No se añadieron rutas de marcaje de asistencia ni lógica de geolocalización (reservadas para Fase 8).
  - No se implementaron formularios de inscripción de alumnos ni reservas de cupos (reservados para Fase 6).
  - Las asociaciones de datos quedan listas a nivel de base de datos y repositorios para que las fases posteriores consuman la titularidad de sección sin necesidad de refactorizar el esquema.
- **Justificación e Impacto:** Preserva la disciplina de desarrollo fase por fase, evita deuda técnica por suposiciones tempranas y permite una validación externa limpia y enfocada en el requerimiento aprobado.
