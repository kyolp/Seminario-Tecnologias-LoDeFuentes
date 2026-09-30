# Decisiones Técnicas — Fase 4

**Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes**  
**Fase 4: Administración Básica**  
**Responsable Técnico:** Antigravity — Desarrollador Principal  
**Fecha:** Septiembre 2026 (Actualizado tras corrección controlada)

---

## DT-25 — Separación Estricta Controller → Service → Repository → PDO/MySQL

- **Contexto:** La Fase 4 introduce la gestión administrativa de 7 entidades relacionales (`docentes`, `estudiantes`, `periodos_escolares`, `grados`, `grados_periodos`, `secciones`, `aulas`).
- **Decisión:** Prohibir terminantemente el uso de SQL directo en controladores y vistas. La lógica se distribuye formalmente en tres capas:
  1. `Controller`: Orquestación HTTP, captura de input sanitizado con whitelist estricto, verificación de sesión/roles y retorno de vistas o redirecciones con mensajes flash.
  2. `Service`: Reglas de validación de negocio, transacciones atómicas PDO, control de concurrencia y unicidad, y traducción de excepciones en mensajes comprensibles.
  3. `Repository`: Consultas preparadas parametrizadas en MySQL vía PDO y construcción de objetos de dominio inmutables.
- **Impacto:** Separación clara de responsabilidades, alta testabilidad unitaria y de integración, y desacoplamiento estructural frente a las siguientes fases.

---

## DT-26 — Modelos Inmutables de Dominio en PHP 8.2 (readonly)

- **Contexto:** El transporte de entidades entre capas mediante arreglos asociativos sin estructura fija favorece la mutación accidental de estado y dificulta el tipado estático.
- **Decisión:** Modelar las 7 entidades relacionales mediante clases dedicadas en `app/Models/` con propiedades `public readonly` y tipado estricto en PHP 8.2.
- **Impacto:** Integridad estructural en tiempo de ejecución, prevención de alteraciones accidentales de estado y claridad en plantillas de vista y servicios.

---

## DT-27 — Transaccionalidad Atómica en el Ciclo de Vida del Docente (Usuario + Docente)

- **Contexto:** La entidad institucional de un docente requiere simultáneamente un registro en `usuarios` (credenciales, rol `DOCENTE` y estado) y un registro en `docentes` (código institucional y fecha de ingreso).
- **Decisión:** Encapsular la creación de ambas entidades bajo transacciones explícitas PDO (`beginTransaction()`, `commit()`, `rollBack()`) dentro de `DocenteService::crearDocente()`. Se incluye un parámetro opcional de inyección de fallos (`$simularFalloDocente`) para verificar el rollback determinista en la suite de pruebas.
- **Impacto:** Cero inconsistencias relacionales, eliminación de usuarios huérfanos sin perfil docente y atomicidad absoluta en la persistencia.

---

## DT-28 — Manejo Preventivo y Defensivo de Período Escolar Activo Único

- **Contexto:** La regla institucional y la restricción física `uk_periodos_escolares_un_activo` en MySQL prohíben la coexistencia de múltiples períodos escolares con estado `ACTIVO`.
- **Decisión:** Implementar una estrategia de protección dual:
  1. *Preventiva:* Consulta rápida en `PeriodoService` antes de cualquier inserción o actualización para validar amigablemente la inexistencia de otro ciclo activo.
  2. *Defensiva:* Captura controlada de `PDOException` con código de error `1062` referente a `uk_periodos_escolares_un_activo`, traduciéndola en mensaje de error de validación sin exponer trazas internas.
- **Impacto:** Experiencia de usuario limpia sin excepciones no controladas y garantía total de cumplimiento de la restricción del modelo relacional.

---

## DT-29 — Validación Estricta de Capacidad Entera Positiva ($capacidad > 0$) en Secciones

- **Contexto:** Las secciones albergan los cupos de inscripción escolar. Capacidades de valor cero, negativas o con decimales provocarían inconsistencias graves en fases posteriores.
- **Decisión:** Validar en `SeccionService` que el parámetro `capacidad` sea estrictamente numérico entero mayor a cero (`$capacidad > 0`), validando tanto en creación como en edición, respaldado además por el constraint físico `chk_secciones_capacidad`.
- **Impacto:** Coherencia aritmética obligatoria y garantía de que todas las secciones creadas en el sistema ofrecen cupos válidos para los procesos de matrícula.

---

## DT-30 — Desactivación Lógica Prioritaria y Respeto a ON DELETE RESTRICT

- **Contexto:** La eliminación física de entidades vinculadas a registros históricos (docentes con asignaciones, secciones con inscripciones) viola las restricciones de clave foránea `ON DELETE RESTRICT`.
- **Decisión:** Priorizar la desactivación lógica mediante banderas de estado (`ACTIVO`/`INACTIVO`, `activa = 0/1`, `habilitado = 0/1`). En los métodos `delete`, verificar preventivamente relaciones dependientes antes de ejecutar `DELETE`; si existen, rechazar la eliminación y orientar al administrador hacia la desactivación.
- **Impacto:** Preservación inalterable del historial académico y trazabilidad institucional requerida por la administración escolar.
