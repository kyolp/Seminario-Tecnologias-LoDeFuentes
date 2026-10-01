# MATRIZ DE PRUEBAS DE FASE 7 — PORTAL DOCENTE

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 7 — Portal Docente  
**Rol:** ANTIGRAVITY — DESARROLLADOR PRINCIPAL  
**Script de Ejecución:** `tests/run_tests_fase7.php`  
**Total de Pruebas:** 27 pruebas automatizadas  
**Resultado:** 27/27 OK (100% de éxito, 0 fallos)

---

## 1. DETALLE DE PRUEBAS DE FASE 7 (T7-01 A T7-27)

| Código | Requisito / Propósito | Descripción Técnica | Resultado Esperado | Estado |
| :--- | :--- | :--- | :--- | :---: |
| **T7-01** | RF-02 / RF-17 | Acceso autenticado con rol DOCENTE a `/docente` | HTTP 200 con vista del portal docente cargada y bienvenida institucional | **OK** |
| **T7-02** | RF-02 | Acceso de usuario no autenticado a `/docente` | Redirección HTTP 302 hacia `/login` sin exposición de contenido | **OK** |
| **T7-03** | RF-02 | Acceso de rol ADMINISTRADOR a `/docente` denegado | HTTP 403 Forbidden bajo política de privilegio mínimo | **OK** |
| **T7-04** | RF-17 | Perfil docente completo y veraz en el encabezado | Nombre completo, código de docente, usuario y correo correspondientes al usuario en sesión | **OK** |
| **T7-05** | RF-17 | Contexto académico de asignación activa visible | Ciclo escolar, grado, sección y aula física asignada presentes en pantalla | **OK** |
| **T7-06** | RF-17 | Docente sin asignación activa recibe mensaje amigable | HTTP 200 sin errores 500, mostrando aviso cortés sobre ausencia de asignación | **OK** |
| **T7-07** | RF-17 | Nómina oficial de estudiantes asignados desplegada | Listado de estudiantes confirmados con nombres, apellidos, código y fecha de nacimiento | **OK** |
| **T7-08** | RF-17 / RNF-04 | Exclusión de estudiantes pertenecientes a otras secciones | Estudiantes de secciones ajenas ausentes de la nómina del docente | **OK** |
| **T7-09** | RF-17 | Exclusión de inscripciones no confirmadas o inactivas | Registros en estado ANULADA, RECHAZADA o con estudiante INACTIVO excluidos | **OK** |
| **T7-10** | RF-17 / RNF-04 | Aislamiento horizontal por sección (Parameter Tampering) | Parámetro `?seccion=X` ajeno rechazado con HTTP 403 y 0 registros en consulta SQL | **OK** |
| **T7-11** | RF-17 / RNF-04 | Manipulación de `id_docente` en la petición es ignorada | Identidad extraída exclusivamente de la sesión; inyección de parámetro cliente ignorada | **OK** |
| **T7-12** | RF-17 / RNF-04 | Manipulación de `id_asignacion` es inocua | Método `findAsignacionByIdAndDocente` restringe búsqueda por `id_docente` devolviendo `null` | **OK** |
| **T7-13** | RF-17 | Soporte multiasignación y selector de secciones | Docente con múltiples secciones puede alternar entre ellas visualizando sus respectivas nóminas | **OK** |
| **T7-14** | RF-17 | Consistencia matemática en conteo de estudiantes | Contador reportado (`total_estudiantes`), badge de cupos y filas HTML coinciden exactamente | **OK** |
| **T7-15** | RNF-03 | Prevención contra Cross-Site Scripting (XSS) | Nombres con payloads `<script>` o `<img>` saneados con entidades HTML mediante `e()` | **OK** |
| **T7-16** | RNF-04 | Minimización de datos y privacidad del estudiante | Sin exposición de datos de encargados (teléfonos, correos), contraseñas ni hashes | **OK** |
| **T7-17** | RF-02 | Revocación de acceso a docente en estado INACTIVO | HTTP 403 Forbidden cuando `docentes.estado = 'INACTIVO'` | **OK** |
| **T7-18** | RF-17 | Aislamiento de asignación inactiva / histórica | Asignaciones con `estado != 'ACTIVA'` no se muestran en portal ni permiten listar alumnos | **OK** |
| **T7-19** | RF-31 | Integración en tiempo real Fase 6 $\to$ Fase 7 | Nueva inscripción confirmada en línea aparece de inmediato en la nómina sin copia manual | **OK** |
| **T7-20** | RF-31 | Reflejo inmediato de cambios de estado en inscripciones | Inscripción anulada desaparece en tiempo real del portal docente en la siguiente carga | **OK** |
| **T7-21** | Integración | Flujo HTTP real completo con servidor embebido y cURL | GET `/login` $\to$ POST `/login` $\to$ 302 `/docente` $\to$ GET `/docente` 200 con nómina | **OK** |
| **T7-22** | Regresión | Regresión completa de Fase 6 (Inscripciones y Cupos) | 30/30 pruebas superadas con 0 fallos | **OK** |
| **T7-23** | Regresión | Regresión completa de Fase 5 (Asignaciones Docentes) | 26/26 pruebas superadas con 0 fallos | **OK** |
| **T7-24** | Regresión | Regresión completa de Fase 4 (Administración Básica) | 27/27 pruebas superadas con 0 fallos | **OK** |
| **T7-25** | Regresión | Regresión completa de Fase 3 (Autenticación y Roles) | 19/19 pruebas superadas con 0 fallos | **OK** |
| **T7-26** | Regresión | Regresión completa de Fase 2 (Arquitectura Base) | 16/16 pruebas superadas con 0 fallos | **OK** |
| **T7-27** | Regresión | Regresión completa de Fase 1 (Base de Datos Relacional) | 18/18 pruebas superadas con 0 fallos en BD temporal aislada | **OK** |

---

## 2. RESULTADOS GLOBALES DE REGRESIÓN ACUMULADA

- Pruebas propias de Fase 7: **21/21 OK**
- Regresiones de Fases 1 a 6: **6/6 suites aprobadas**
  - Fase 6: 30/30 OK
  - Fase 5: 26/26 OK
  - Fase 4: 27/27 OK
  - Fase 3: 19/19 OK
  - Fase 2: 16/16 OK
  - Fase 1: 18/18 OK
- **Total de pruebas verificadas en el ciclo:** **157 pruebas acumuladas en verde**.
