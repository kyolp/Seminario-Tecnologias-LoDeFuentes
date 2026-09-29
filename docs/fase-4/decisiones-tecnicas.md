# Decisiones Técnicas — Fase 4

**Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes**  
**Fase 4: Administración Básica**  
**Responsable Técnico:** Antigravity — Desarrollador Principal  
**Fecha:** Septiembre 2026  

---

## DT-17 — Separación Estricta Controller-Service-Repository

- **Contexto:** La Fase 4 introduce la gestión administrativa de 7 entidades relacionales (`docentes`, `estudiantes`, `periodos_escolares`, `grados`, `grados_periodos`, `secciones`, `aulas`).
- **Decisión:** Prohibir terminantemente el uso de SQL directo en controladores y vistas. La lógica se distribuye en:
  1. `Controller`: Orquestación HTTP, captura de input sanitizado y retorno de vistas o redirecciones con mensajes flash.
  2. `Service`: Reglas de validación, transacciones PDO, lógica de unicidad y control de errores amigables.
  3. `Repository`: Consultas preparadas parametrizadas en MySQL y construcción de objetos de dominio inmutables.
- **Impacto:** Alta testabilidad unitaria, código desacoplado y facilidad para incorporar nuevas fases sin regresiones.

---

## DT-18 — Modelos Inmutables de Dominio en PHP 8.2

- **Contexto:** El paso de datos entre repositorios, servicios y plantillas de vista puede propiciar alteraciones inadvertidas de estado si se utilizan arreglos asociativos sin estructura fija.
- **Decisión:** Modelar las 7 entidades mediante clases dedicadas en `app/Models/` con propiedades `public readonly` y tipado estricto.
- **Impacto:** Integridad estructural en tiempo de compilación y ejecución; autocompletado y claridad en vistas y controladores.

---

## DT-19 — Transaccionalidad Atómica en el Ciclo de Vida del Docente

- **Contexto:** Un docente en el modelo institucional se compone de un registro en `usuarios` (credenciales y datos personales) y un registro en `docentes` (datos profesionales e ingreso).
- **Decisión:** Implementar transacciones PDO con `beginTransaction()`, `commit()` y `rollBack()` dentro de `DocenteService::crearDocente()`. Incluir un hook opcional de inyección de fallos (`$simularFalloDocente`) para permitir pruebas unitarias y de integración del rollback automático.
- **Impacto:** Cero inconsistencias relacionales o usuarios huérfanos sin entidad docente asociada.

---

## DT-20 — Manejo Dual de la Restricción de Período Activo Único

- **Contexto:** La restricción física `uk_periodos_escolares_un_activo` en MySQL rechaza múltiples registros con `clave_periodo_activo = 1`.
- **Decisión:** Aplicar verificación dual:
  1. *Preventiva:* Consulta rápida en `PeriodoService` para advertir amigablemente al administrador antes de ejecutar el INSERT/UPDATE.
  2. *Defensiva:* Captura de excepciones `PDOException` con código de clave duplicada (`1062` / `uk_periodos_escolares_un_activo`), traduciéndolas a mensajes comprensibles para el usuario.
- **Impacto:** Experiencia de usuario limpia sin errores no controlados, protegiendo al 100% la integridad de la base de datos.

---

## DT-21 — Validación Estricta de Capacidad Entera en Secciones

- **Contexto:** Las secciones albergan los cupos de inscripción escolar. Un valor menor o igual a 0 provocaría fallas lógicas en inscripciones posteriores.
- **Decisión:** Exigir `capacidad > 0` obligatoria de tipo entero en `SeccionService`, verificando que el valor no contenga decimales ni caracteres alfabéticos, respaldado además por el constraint de base de datos `chk_secciones_capacidad`.
- **Impacto:** Garantía de coherencia aritmética para las asignaciones e inscripciones de fases posteriores.

---

## DT-22 — Desactivación Lógica vs. Borrado Físico

- **Contexto:** La eliminación de entidades con registros históricos (como docentes con asignaciones de aula o grados asociados a ciclos) causaría violaciones de integridad referencial (`ON DELETE RESTRICT`).
- **Decisión:** Proveer en la interfaz botones de alternancia de estado (Activar/Desactivar, Habilitar/Deshabilitar). En los métodos `delete`, verificar preventivamente si existen relaciones dependientes antes de intentar el DELETE; de existir, bloquear la acción y sugerir la desactivación lógica.
- **Impacto:** Preservación del histórico institucional y apego a las normas del MINEDUC.
