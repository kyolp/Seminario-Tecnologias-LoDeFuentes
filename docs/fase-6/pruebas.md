# Suite de Pruebas Automatizadas — Fase 6: Inscripciones y Cupos

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 6 — Inscripciones y Cupos  
**Script Principal:** `tests/run_tests_fase6.php`  
**Worker Concurrente:** `tests/worker_inscripcion_concurrente.php`  
**Fecha de Ejecución:** Septiembre 2026  
**Resultado Global:** 30 / 30 Pruebas Aprobadas (100% OK)  

---

## 1. Resumen Ejecutivo de la Suite

La suite de pruebas automatizadas de Fase 6 cubre de extremo a extremo las funcionalidades de matrícula en línea, control dinámico de cupos, persistencia relacional normalizada, generación segura de comprobantes, consulta pública saneada, defensas web (CSRF, XSS, Mass Assignment, Double-Submit), serialización concurrente multiproceso del último cupo y regresión completa de las 5 fases previas del proyecto.

---

## 2. Matriz Detallada de Pruebas (T6-01 a T6-30)

| ID | Nombre de la Prueba | Alcance / Requisito | Resultado Obtenido | Estado |
|---|---|---|---|:---:|
| **T6-01** | Formulario público accesible sin autenticación | RF-10, RNF-04 | HTTP 200 \| Formulario renderizado: SÍ (Accesible sin sesión) | **OK** |
| **T6-02** | Grados correctos por período escolar | RF-10, RF-09 | Grados habilitados encontrados: 1 \| Grado F6 presente: SÍ | **OK** |
| **T6-03** | Secciones correctas por grado y período | RF-10, RF-09 | Sección A (habilitada): SÍ \| Sección B (habilitada): SÍ \| Sección C (deshabilitada excluida): CORRECTO | **OK** |
| **T6-04** | Disponibilidad calculada dinámicamente | RF-09, RN-CUP-01 | Capacidad: 30 \| Ocupación: 0 \| Disponibles calculados: 30 | **OK** |
| **T6-05** | Sección llena no disponible | RN-DOC-02, RF-09 | Sección llena excluida de disponibles: SÍ \| Intento de inscripción rechazado: SÍ ("No hay cupos disponibles") | **OK** |
| **T6-06** | Crear inscripción válida | RF-11, RF-05 | Inserción atómica en `estudiantes`, `encargados`, `estudiantes_encargados` e `inscripciones` confirmada | **OK** |
| **T6-07** | Código único de confirmación generado | RF-12 | Formato `INS-2026-XXXXXXXX` de alta entropía registrado en BD | **OK** |
| **T6-08** | Consulta pública por código válido | RF-14, RNF-04 | Consulta exitosa: SÍ \| Estudiante retornado correctamente | **OK** |
| **T6-09** | Consulta con código inexistente | RF-14 | Éxito: NO \| Mensaje limpio institucional sin fugas técnicas | **OK** |
| **T6-10** | Validación de campos obligatorios | Robustez, Validación | Errores detectados: `estudiante_nombres`, `estudiante_apellidos`, `encargado_nombres`, `encargado_apellidos`, `parentesco`, `id_seccion` | **OK** |
| **T6-11** | Integridad referencial y FK manipulada | Seguridad, Integridad | Éxito: NO \| Mensaje limpio: "La sección seleccionada no existe." | **OK** |
| **T6-12** | Rechazo de sección en período cerrado | Reglas Académicas | Éxito: NO \| Mensaje: "No se admiten inscripciones en un período escolar cerrado." | **OK** |
| **T6-13** | Prevención de doble inscripción vigente | Restricción Única | Éxito: NO \| Mensaje preventivo: "El estudiante ya se encuentra inscrito en esta sección." | **OK** |
| **T6-14** | Vínculo Estudiante-Encargado N:M persistido | RF-05, Normalización | Vínculo en BD formalizado con indicador principal (`es_principal = 1`) | **OK** |
| **T6-15** | Garantía de FK compuesta en inscripciones | Integridad Física | Intento sin vínculo en `estudiantes_encargados` rechazado por BD (Error 1452 capturado) | **OK** |
| **T6-16** | Protección CSRF en formulario público | Seguridad Web | Petición sin token: HTTP 403 \| Petición con token manipulado: HTTP 403 Forbidden | **OK** |
| **T6-17** | Prevención de Cross-Site Scripting (XSS) | Seguridad Web | Script crudo encontrado: NO \| Script escapado con entidades HTML: SÍ (SEGURO) | **OK** |
| **T6-18** | Protección contra asignación masiva | Seguridad Web | Campos espurios descartados por lista blanca \| Código y estado fijados exclusivamente por backend | **OK** |
| **T6-19** | Rollback transaccional ante fallo intermedio | Integridad ACID | Fallo forzado provocó `rollBack()` total; 0 registros huérfanos persistidos | **OK** |
| **T6-20** | Último cupo secuencial respetado | RF-32, Control Cupos | Intento 1: Exitoso \| Intento 2 (Último cupo): Exitoso \| Intento 3 (Excedente): Rechazado \| Ocupación: 2/2 | **OK** |
| **T6-21** | Concurrencia real del último cupo (10 iteraciones multiproceso) | RF-32, Concurrencia | 10/10 iteraciones sin sobrecupos \| Ocupación final: 2/2 \| Ganadores alternados por micro-latencia InnoDB | **OK** |
| **T6-22** | Protección contra doble submit | Seguridad Web | Primer submit: OK \| Segundo submit: OK (Rechazado) \| Inscripciones vigentes en BD: 1 | **OK** |
| **T6-23** | Manejo de colisión y regeneración de código único | RF-12, Robustez | Código colisionado detectado \| Nuevo código generado con reintento seguro | **OK** |
| **T6-24** | Privacidad en consulta pública | RNF-04 (Evidencia parcial en Fase 6; validación integral en Fase 14), Seguridad | IDs internos de BD ocultos: SÍ \| Hashes ocultos: SÍ \| Datos proyectados mínimos necesarios | **OK** |
| **T6-25** | Flujo HTTP público real completo | Integración Web | GET Form: 200 \| API Secciones: 200 \| POST Inscripción: 302 PRG \| GET Éxito: 200 \| POST Consulta: 200 | **OK** |
| **T6-26** | Regresión completa de Fase 5 | Regresión Histórica | 26 / 26 pruebas de Fase 5 (Asignaciones Docentes) aprobadas | **OK** |
| **T6-27** | Regresión completa de Fase 4 | Regresión Histórica | 27 / 27 pruebas de Fase 4 (Administración Básica) aprobadas | **OK** |
| **T6-28** | Regresión completa de Fase 3 | Regresión Histórica | 19 / 19 pruebas de Fase 3 (Autenticación y Roles) aprobadas | **OK** |
| **T6-29** | Regresión completa de Fase 2 | Regresión Histórica | 16 / 16 pruebas de Fase 2 (Arquitectura Base y Enrutamiento) aprobadas | **OK** |
| **T6-30** | Regresión completa de Fase 1 | Regresión Histórica | 18 / 18 pruebas SQL de Fase 1 (Base de Datos Relacional) aprobadas en BD temporal aislada | **OK** |

---

## 3. Log de Ejecución Automatizada

```
============================================================
SUITE DE PRUEBAS AUTOMATIZADAS — FASE 6
Inscripciones y Cupos — Escuela Oficial Rural Mixta Lo de Fuentes
============================================================

[OK] T6-01: Formulario público accesible sin autenticación | Obtenido: HTTP 200 | Formulario renderizado: SÍ
[OK] T6-02: Grados correctos por período | Obtenido: Grados habilitados encontrados: 1 | Grado F6 presente: SÍ
[OK] T6-03: Secciones correctas por grado y período | Obtenido: Sección A (habilitada): SÍ | Sección B (habilitada): SÍ | Sección C (deshabilitada excluida): CORRECTO
[OK] T6-04: Disponibilidad calculada dinámicamente | Obtenido: Capacidad: 30 | Ocupación: 0 | Disponibles calculados: 30
[OK] T6-05: Sección llena no disponible (RN-DOC-02 y RF-09) | Obtenido: Sección llena excluida de disponibles: SÍ | Intento de inscripción rechazado: SÍ (Mensaje: "No hay cupos disponibles en la sección seleccionada.")
[OK] T6-06: Crear inscripción válida (RF-11) | Obtenido: Inscripción #106 confirmada con Código: INS-2026-A85FB1FA | Estudiante: #95 (Jonathan Alexander Gómez Morales) | Encargado: #71 (Marta Lidia Morales Estrada)
[OK] T6-07: Código único de confirmación generado (RF-12) | Obtenido: Código: INS-2026-A85FB1FA | Formato seguro: SÍ | Registrado en BD: SÍ
[OK] T6-08: Consulta pública por código válido (RF-14) | Obtenido: Consulta exitosa: SÍ | Estudiante retornado: Jonathan Alexander Gómez Morales | Grado: Primer Grado F6
[OK] T6-09: Consulta con código inexistente | Obtenido: Éxito: NO | Mensaje limpio: "No se encontró ninguna inscripción con el código ingresado. Verifique que esté escrito exactamente como en su comprobante."
[OK] T6-10: Validación de campos obligatorios | Obtenido: Rechazado: SÍ | Errores detectados: estudiante_nombres, estudiante_apellidos, encargado_nombres, encargado_apellidos, parentesco, id_seccion
[OK] T6-11: Integridad referencial y FK manipulada | Obtenido: Éxito: NO | Mensaje limpio: "La sección seleccionada no existe."
[OK] T6-12: Rechazo de sección en período cerrado | Obtenido: Éxito: NO | Mensaje: "No se admiten inscripciones en un período escolar cerrado."
[OK] T6-13: Prevención de doble inscripción vigente (restricción única) | Obtenido: Éxito: NO | Mensaje preventivo: "El estudiante ya se encuentra inscrito en esta sección."
[OK] T6-14: Vínculo Estudiante-Encargado N:M persistido (RF-05) | Obtenido: Vínculo en BD: #92 | Estudiante: #95 | Encargado: #71 | Principal: SÍ
[OK] T6-15: Garantía de FK compuesta fk_inscripciones_vinculo_encargado | Obtenido: Restricción FK disparada en BD: SÍ (Error 1452 capturado)
[OK] T6-16: Protección CSRF en formulario público | Obtenido: Sin token: HTTP 403 | Token inválido: HTTP 403 (403 Forbidden)
[OK] T6-17: Prevención de Cross-Site Scripting (XSS) | Obtenido: Script crudo encontrado: NO | Script escapado con entidades HTML: SÍ (SEGURO)
[OK] T6-18: Protección contra asignación masiva (Mass Assignment) | Obtenido: Código en BD: INS-2026-C4E3492C (generado por backend) | Estado en BD: CONFIRMADA | ID asignado: 109
[OK] T6-19: Rollback transaccional ante fallo intermedio | Obtenido: Estudiantes antes: 63, después: 63 | Inscripciones antes: 35, después: 35 (0 huérfanos persistidos)
[OK] T6-20: Último cupo secuencial respetado | Obtenido: Intento 1: Exitoso | Intento 2 (Último cupo): Exitoso | Intento 3 (Excedente): Rechazado | Ocupación final: 2/2

>>> Ejecutando prueba de concurrencia real del último cupo en 10 iteraciones (T6-21)...
[OK] T6-21: Concurrencia real del último cupo (RF-32, 10 iteraciones multiproceso) | Obtenido: 10/10 SIN SOBRECUPOS | 10/10 iteraciones sin sobrecupos | Ocupación final: 2/2 | Ganadores alternados por micro-latencia InnoDB
[OK] T6-22: Protección contra doble submit | Obtenido: Primer submit: OK | Segundo submit: OK (Rechazado) | Inscripciones vigentes en BD: 1
[OK] T6-23: Manejo de colisión y regeneración de código único | Obtenido: Código existente colisionado: INS-2026-COLISION99 | Nuevo código generado con reintento seguro: INS-2026-1523E00A
[OK] T6-24: Privacidad y protección de datos en consulta pública (RNF-04) | Obtenido: IDs internos ocultos: SÍ | Hashes ocultos: SÍ | Campos públicos adecuados: codigo_confirmacion, estudiante_nombre, codigo_estudiante, grado, seccion, periodo, encargado_nombre, parentesco, fecha_inscripcion, fecha_confirmacion, estado
[OK] T6-25: Flujo HTTP público real completo | Obtenido: GET Form: 200 | API Secciones: 200 | POST Inscripción: 302 (302) | GET Éxito: 200 | POST Consulta: 200

>>> Ejecutando regresión completa de Fase 5 en proceso aislado...
[OK] T6-26: Regresión completa de Fase 5 (Asignaciones Docentes) | Obtenido: 26/26 pruebas de Fase 5 aprobadas

>>> Ejecutando regresión completa de Fase 4 en proceso aislado...
[OK] T6-27: Regresión completa de Fase 4 (Administración Básica) | Obtenido: 27/27 pruebas de Fase 4 aprobadas

>>> Ejecutando regresión completa de Fase 3 en proceso aislado...
[OK] T6-28: Regresión completa de Fase 3 (Autenticación y Roles) | Obtenido: 19/19 pruebas de Fase 3 aprobadas

>>> Ejecutando regresión completa de Fase 2 en proceso aislado...
[OK] T6-29: Regresión completa de Fase 2 (Arquitectura Base y Enrutamiento) | Obtenido: 16/16 pruebas de Fase 2 aprobadas

>>> Ejecutando regresión completa de Fase 1...
[OK] T6-30: Regresión completa de Fase 1 (Diseño de Base de Datos) | Obtenido: 18/18 pruebas de Fase 1 ejecutadas con 18 exitosas y 0 fallidas en BD temporal aislada

============================================================
RESUMEN DE PRUEBAS DE FASE 6
============================================================
Total ejecutadas : 30
Exitosas (OK)    : 30
Fallidas (FALLO) : 0
============================================================
RESULTADO: TODAS LAS PRUEBAS DE FASE 6 SUPERADAS SATISFACTORIAMENTE.
```
