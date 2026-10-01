# Reglas de Negocio y Control de Cupos — Fase 6

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 6 — Inscripciones y Cupos  
**Fecha:** Septiembre 2026  

---

## 1. Invariante Física de Matrícula: Cero Sobrecupos

La regla fundamental e inquebrantable del sistema escolar de la Escuela Oficial Rural Mixta Lo de Fuentes es:

$$\text{INSCRITOS VIGENTES} \le \text{CAPACIDAD DE LA SECCIÓN}$$

Bajo ninguna circunstancia operacional, técnica o concurrente el número de alumnos inscritos en una sección puede exceder la capacidad física y pedagógica asignada (`secciones.capacidad`).

---

## 2. Definición y Cálculo Dinámico de Ocupación

La ocupación de una sección no se almacena como un contador estático susceptible a desincronizaciones, sino que se calcula dinámicamente mediante consultas indexadas sobre los registros reales vigentes:

$$\text{Ocupación} = \sum [\text{inscripciones con } \text{id\_seccion} = s \land \text{estado} \in (\text{'CONFIRMADA'}, \text{'PENDIENTE'})]$$

Las inscripciones con estado `'RECHAZADA'` o `'ANULADA'` no consumen cupo escolar.

### Fórmula de Cupos Disponibles

$$\text{Cupos Disponibles} = \max(0, \text{capacidad} - \text{ocupación})$$

---

## 3. Matriz de Reglas de Negocio (RN)

> [!NOTE]
> Los identificadores `RN-CUP-*` y `RN-DOC-*` son referencias técnicas internas de trazabilidad del proyecto y no constituyen identificadores codificados literalmente en el documento oficial de alcance de la institución. Las reglas provienen del alcance funcional aprobado y se formulan para control estricto de ingeniería.

| Identificador | Nombre de la Regla | Descripción y Comportamiento en el Sistema | Mecanismo de Control |
|---|---|---|---|
| **RN-CUP-01** | Invariante de Capacidad Máxima | No se permite registrar una inscripción si la ocupación actual es igual o mayor a la capacidad configurada de la sección. | Bloqueo pesimista `SELECT ... FOR UPDATE` + verificación previa a la inserción en `InscripcionService`. |
| **RN-CUP-02** | Exclusión de Secciones Llenas en Portal Público | Toda sección cuya disponibilidad sea igual a cero es excluida automáticamente del listado de opciones para el usuario público (RN-DOC-02 y RF-09). | Filtro `cupos_disponibles > 0` en `InscripcionService::obtenerSeccionesDisponibles()` y API JSON. |
| **RN-CUP-03** | Ciclo Escolar Válido y Abierto | Las inscripciones solo se admiten en períodos escolares en estado `'ACTIVO'` o `'PLANIFICADO'` que no se encuentren cerrados administrativamente. | Verificación en cascada de `periodos_escolares.estado` y rechazo inmediato si el ciclo escolar está cerrado. |
| **RN-CUP-04** | Habilitación Jerárquica de Grados y Secciones | Una sección solo es seleccionable si su grado está habilitado en el período (`grados_periodos.habilitado = 1`) y la sección está habilitada (`secciones.habilitada = 1`). | Consulta relacional estricta en repositorio y verificación de integridad previa a la transacción. |
| **RN-CUP-05** | Prevención de Doble Inscripción Vigente | Un estudiante no puede contar con dos inscripciones activas (`CONFIRMADA` o `PENDIENTE`) dentro de la misma sección en el mismo ciclo. | Columna generada almacenada `(estudiante_vigente, seccion_vigente)` con restricción física `uk_inscripciones_estudiante_seccion_vigente`. |
| **RN-CUP-06** | Integridad del Vínculo Familiar N:M | Toda inscripción debe registrar formalmente la relación entre el estudiante y el encargado en `estudiantes_encargados` antes de crear el registro de matrícula. | Clave foránea compuesta `CONSTRAINT fk_inscripciones_vinculo_encargado FOREIGN KEY (id_estudiante, id_encargado) REFERENCES estudiantes_encargados`. |
| **RN-CUP-07** | Estado Inicial de Inscripción Pública | Las inscripciones realizadas en línea por el encargado se registran directamente con estado `'CONFIRMADA'` al superar todas las validaciones de cupo, asignando código único institucional y fecha de confirmación. | Cumplimiento del check constraint `chk_inscripciones_confirmacion` que exige `codigo_confirmacion IS NOT NULL` y `fecha_confirmacion IS NOT NULL`. |
| **RN-CUP-08** | Cero Eliminaciones Físicas | Los registros de inscripción nunca se eliminan mediante `DELETE`. Cualquier anulación posterior se realiza mediante cambio de estado a `'ANULADA'`. | Integridad relacional en `InscripcionRepository` y anulación lógica no destructiva. |

---

## 4. Comportamiento ante el "Último Cupo"

Cuando en una sección con capacidad $N$ existen exactamente $N - 1$ inscripciones confirmadas:

1. **Estado inicial:** Cupos disponibles = 1. La sección aparece como disponible en el formulario en línea.
2. **Primera solicitud exitosa:**
   - La primera transacción adquiere el bloqueo exclusivo sobre la sección (`FOR UPDATE`).
   - Evalúa `ocupación = N - 1 < N`.
   - Persiste al estudiante, encargado, vínculo e inscripción con estado `CONFIRMADA`.
   - Ejecuta `COMMIT`. La ocupación pasa inmediatamente a $N$.
3. **Solicitudes posteriores o concurrentes:**
   - Si otra solicitud estaba encolada, despierta tras el `COMMIT` y lee de inmediato `ocupación = N >= N`.
   - Detecta la ausencia de cupo, ejecuta `ROLLBACK` y retorna al usuario el mensaje controlado:
     `"No hay cupos disponibles en la sección seleccionada."`
   - Si un usuario refresca el formulario, la sección ya no aparece entre las opciones disponibles.
