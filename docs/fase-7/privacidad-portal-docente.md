# PRIVACIDAD Y PROTECCIÓN DE DATOS EN EL PORTAL DOCENTE — FASE 7

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase Autorizada:** Fase 7 — Portal Docente  
**Rol:** ANTIGRAVITY — DESARROLLADOR PRINCIPAL  
**Requisitos Satisfechos:** RF-17 (Listado de estudiantes asignados), RNF-04 (Privacidad y protección de datos)

---

## 1. PRINCIPIO DE MINIMIZACIÓN DE DATOS (RNF-04)

En el marco del Requisito No Funcional **RNF-04 (Privacidad)**, el Portal Docente se diseñó bajo una estricta política de minimización de datos (*need-to-know* pedagógico).

Un docente titular de aula requiere conocer exclusivamente los datos académicos indispensables para la conducción del proceso de enseñanza-aprendizaje y control de asistencia en su sección:
- Código de estudiante (código oficial asignado por el sistema/MINEDUC).
- Nombres y apellidos completos.
- Fecha de nacimiento (para fines de contextualización pedagógica y edad de desarrollo).
- Estado de matrícula activa en la sección.

---

## 2. DATOS EXCLUIDOS Y OMITIDOS EN EL PORTAL DOCENTE

Para garantizar el cumplimiento de RNF-04 y proteger la esfera íntima de los estudiantes y sus familias, los siguientes datos **NO son expuestos ni transmitidos** en las respuestas, vistas o modelos del portal docente:

| Categoría de Información | Campo en Base de Datos | Estado en Portal Docente | Justificación de Seguridad y Privacidad |
| :--- | :--- | :--- | :--- |
| **Identificadores del Encargado** | `encargados.cui` | **TOTALMENTE OMITIDO** | Documento personal de identificación protegido legalmente. |
| **Contacto Personal del Encargado** | `encargados.telefono`, `encargados.correo`, `direccion` | **TOTALMENTE OMITIDO** | Canales privados de contacto familiar no requeridos en la nómina ordinaria de aula. |
| **Identificadores Internos de Sistema** | `usuarios.id_usuario`, `docentes.id_docente` | **OCULTOS** | Prevención de ataques de enumeración y recolección de metadatos relacionales. |
| **Secretos de Autenticación** | `usuarios.clave_hash` | **INACCESIBLES** | Exclusión estructural en DTOs y consultas de repositorio. |
| **Tokens y Cookies de Sesión** | `session_id`, `_token` | **INACCESIBLES** | No se renderizan en tablas ni atributos públicos de estudiantes. |

---

## 3. TRAZABILIDAD Y ESTADO OFICIAL DE RNF-04

Conforme a la directiva institucional de control arquitectónico:
- **Estado General de RNF-04:** **PENDIENTE** (su validación integral abarca auditoría global, cifrado en reposo, tránsito HTTPS y pruebas de penetración programadas para la Fase 14 — Despliegue y Pruebas Finales).
- **Aporte y Evidencia Específica de Fase 7:**
  1. Aislamiento horizontal estricto: Cero visibilidad de estudiantes de secciones ajenas (T7-08, T7-10).
  2. Nómina restringida al principio de necesidad pedagógica: Consulta SQL optimizada en `PortalDocenteRepository::findEstudiantesBySeccionDocente` que no realiza `JOIN` con la tabla `encargados` (T7-16).
  3. Saneamiento contra inyecciones XSS: Todos los nombres y campos alfanuméricos se filtran con codificación de entidades HTML (`e()`), impidiendo robo de cookies o ejecución de scripts en el contexto del navegador del docente (T7-15).
