# INFORME DE DESARROLLO — FASE 1
ANTIGRAVITY

============================================================
INFORME DE DESARROLLO — FASE 1
ANTIGRAVITY
============================================================

## 1. IDENTIFICACIÓN

- **Proyecto:** Sistema web integral para la gestión de inscripciones, asignación de secciones y control de asistencia docente — Escuela Oficial Rural Mixta Lo de Fuentes.
- **Fase:** Fase 1 — Diseño e implementación de base de datos.
- **Rol:** Desarrollador principal — Antigravity.
- **Directorio:** `C:\Users\David\Documents\Universidad\2026\Segundo semestre\seminario de tecnologias\ProyectoFinal`
- **Fecha:** 2026-09-26 (ejecución y validación técnica: 2026-09-23).
- **Commit inicial:** `9524c1c` (`docs(fase-0): cerrar validacion tecnica del entorno`).

## 2. OBJETIVO DE LA FASE

Diseñar e implementar el modelo conceptual, lógico y físico de base de datos en MySQL 8.0.42 con motor InnoDB, juego de caracteres `utf8mb4` e intercalación `utf8mb4_0900_ai_ci`. El entregable comprende la totalidad de entidades, relaciones, cardinalidades, claves primarias, claves foráneas, restricciones de integridad (`CHECK`, `UNIQUE`, `NOT NULL`), índices de optimización, scripts reproducibles (`schema.sql`, `seed.sql`, `tests/validacion.sql`), diccionario de datos y pruebas de integridad ejecutadas, manteniendo un aislamiento riguroso y sin implementar lógica funcional en PHP.

## 3. FUENTES UTILIZADAS

1. **Documento oficial de alcance:** `Documento_Alcance_Escuela_Lo_de_Fuentes_Andersson_Godoy (1).docx` (objetivos, 3 actores, 32 RF, 10 RNF, 14 reglas de negocio, 11 criterios de aceptación y 6 limitaciones).
2. **Prompt Maestro de Antigravity:** roles, prohibiciones, estructura arquitectónica y protocolo de auditoría técnica por Codex.
3. **Artefactos aprobados de Fase 0:** `docs/fase-0/diagnostico.md`, `docs/fase-0/informe-cierre-fase-0.md` y `docs/fase-0/matriz-trazabilidad.md`.
4. **Entorno local validado:** MySQL Community Server 8.0.42 x86_64, motor InnoDB y PHP 8.2.12 CLI con PDO y `pdo_mysql`.

## 4. REQUERIMIENTOS TRABAJADOS

Se proveyó soporte estructural de datos relacional para los siguientes requisitos oficiales (manteniendo su estado funcional en `PENDIENTE`):

- **RF-01, RF-02, RF-03; RNF-03:** Autenticación de usuarios, roles del sistema y soporte para recuperación de contraseña (`roles`, `usuarios`, `tokens_recuperacion`).
- **RF-04, RF-05; RNF-04:** Registro de datos mínimos de estudiantes y encargados con relación N:M (`estudiantes`, `encargados`, `estudiantes_encargados`).
- **RF-06 a RF-10, RF-32; RNF-05, RNF-09:** Configuración escolar por período, habilitación de grados, secciones, capacidades y soporte para control estricto de cupos (`periodos_escolares`, `grados`, `grados_periodos`, `secciones`, `inscripciones`).
- **RF-11, RF-12, RF-14, RF-30, RF-31:** Inscripciones vinculadas a grado, sección y encargado responsable, consulta de confirmación y cruce de datos (`inscripciones`).
- **RF-13:** Soporte para comprobante de inscripción (se genera a partir de la consulta relacional de `inscripciones`; no requiere tabla propia).
- **RF-15 a RF-17:** Perfil de docentes, catálogo de aulas y asignaciones docentes por sección conservando historial (`docentes`, `aulas`, `asignaciones_docentes`).
- **RF-18 a RF-23, RF-29; RNF-06:** Configuración de ubicación institucional con histórico y registro inmutable de marcajes de asistencia con coordenadas, distancia calculada y verificación dentro del radio (`configuraciones_ubicacion`, `marcajes_asistencia`).
- **RF-24 a RF-28; RNF-06:** Solicitudes de permisos docentes, metadatos de documentos adjuntos, historial inmutable de decisiones y notificaciones internas (`solicitudes_permiso`, `historial_solicitudes_permiso`, `documentos_permiso`, `notificaciones`).

## 5. PLAN EJECUTADO

1. Análisis y delimitación del universo del discurso institucional a partir del documento oficial.
2. Definición y registro de 8 decisiones técnicas fundamentales (`DT-01` a `DT-08`).
3. Construcción del modelo conceptual y diagrama entidad-relación en Mermaid (`docs/fase-1/modelo-conceptual.md`).
4. Construcción del modelo lógico en Tercera Forma Normal (3NF) (`docs/fase-1/modelo-logico.md`).
5. Redacción del script DDL físico `database/schema.sql` con 20 tablas, 26 claves foráneas, restricciones `CHECK`, índices y columnas generadas.
6. Redacción del script `database/seed.sql` con datos mínimos puramente ficticios e identificados explícitamente como `DATO DE PRUEBA`.
7. Creación y ejecución del procedimiento de validación automatizada `database/tests/validacion.sql` y documentación de resultados en `docs/fase-1/pruebas-base-datos.md`.
8. Redacción del diccionario de datos exhaustivo en `docs/fase-1/diccionario-datos.md`.
9. Actualización controlada de la matriz de trazabilidad en `docs/fase-0/matriz-trazabilidad.md`.
10. Elaboración del informe de cierre y consolidación del paquete de auditoría para Codex.

## 6. TRABAJO REALIZADO

- Se crearon 20 tablas normalizadas sin redundancias nocivas ni entidades fuera de alcance.
- Se implementaron 26 claves foráneas configuradas con `ON UPDATE RESTRICT ON DELETE RESTRICT` para blindar la integridad referencial.
- Se definieron 3 columnas generadas almacenadas (`STORED`) para resolver la unicidad condicional en registros activos sin recurrir a disparadores:
  - `periodos_escolares.clave_periodo_activo`
  - `asignaciones_docentes.id_seccion_activa`
  - `configuraciones_ubicacion.clave_configuracion_activa`
- Se crearon índices únicos condicionales sobre `inscripciones` (`estudiante_vigente`, `seccion_vigente`) para impedir que un estudiante tenga más de una inscripción vigente simultánea en una misma sección.
- Se aplicaron restricciones `CHECK` estrictas para rangos de fechas (`fecha_fin >= fecha_inicio`), coordenadas geográficas válidas (`latitud` entre -90 y 90, `longitud` entre -180 y 180), capacidades estrictamente positivas (`capacidad > 0`) y consistencia en tokens y archivos.
- Se establecieron índices B-tree optimizados en todas las claves foráneas y columnas de consulta frecuente.

## 7. DECISIONES TÉCNICAS

- **DT-01 — Convenciones del modelo:** Nomenclatura en español, `snake_case`, PKs numéricas sin signo autoincrementales (`id_<entidad>`), juego `utf8mb4` e intercalación `utf8mb4_0900_ai_ci`.
- **DT-02 — Usuarios, roles y docentes:** Credenciales e identidad centralizadas en `usuarios`; extensión institucional 1:1 en `docentes`; encargados sin cuentas ni credenciales en esta versión.
- **DT-03 — Relación estudiante–encargado:** Modelado N:M mediante tabla intermedia `estudiantes_encargados`, con puntero en `inscripciones` y clave foránea compuesta que exige que el encargado registrado sea tutor acreditado del estudiante.
- **DT-04 — Configuración por período:** Jerarquía `periodos_escolares -> grados_periodos -> secciones`, permitiendo oferta y capacidades dinámicas por ciclo.
- **DT-05 — Control de cupos sin redundancia:** La capacidad es un valor estático en `secciones`; la ocupación se calcula desde las inscripciones con estado `CONFIRMADA`. No se usan contadores redundantes; la concurrencia se garantizará en la capa de servicios con transacciones y `SELECT ... FOR UPDATE`.
- **DT-06 — Asistencia inmutable y geolocalización:** Registro inmutable de cada marcaje con coordenadas `DECIMAL(10, 7)`, distancia métrica y restricción física `dentro_radio = 1`.
- **DT-07 — Historial de solicitudes de permisos:** Tabla inmutable `historial_solicitudes_permiso` que audita cada transición de estado, usuario actor, fecha y comentario; notificaciones internas asociadas a solicitudes.
- **DT-08 — Tokens de recuperación seguros:** Almacenamiento exclusivo del hash del token (`token_hash`), con fecha de expiración forzosa y estado para garantizar uso único.

> **DECISIONES TÉCNICAS DEFINIDAS PARA ESTA FASE:** DT-01 a DT-08 documentadas. No existen decisiones bloqueantes pendientes al inicio de la implementación.

## 8. ARCHIVOS CREADOS

1. `docs/fase-1/decisiones-tecnicas.md`: Registro formal de las decisiones técnicas `DT-01` a `DT-08`.
2. `docs/fase-1/modelo-conceptual.md`: Modelo conceptual, justificación de entidades, cardinalidades y diagrama Mermaid.
3. `docs/fase-1/modelo-logico.md`: Modelo lógico normalizado en 3NF, relaciones y definiciones de claves.
4. `database/schema.sql`: Script DDL físico ejecutable con 20 tablas, 26 FKs, restricciones `CHECK`, índices y columnas `STORED`.
5. `database/seed.sql`: Script de datos de prueba mínimos ficticios (`DATO DE PRUEBA`) con hashes seguros y dominios `.invalid`.
6. `database/tests/validacion.sql`: Procedimiento almacenado `validar_fase_1()` con 18 casos de prueba automatizados.
7. `docs/fase-1/pruebas-base-datos.md`: Bitácora y matriz de resultados de las 18 pruebas ejecutadas en MySQL 8.0.42.
8. `docs/fase-1/diccionario-datos.md`: Diccionario exhaustivo de las 20 tablas con campos, tipos, claves y descripciones.
9. `docs/fase-1/informe-cierre-fase-1.md`: Este informe formal de cierre y paquete de auditoría.

## 9. ARCHIVOS MODIFICADOS

1. `docs/fase-0/matriz-trazabilidad.md`:
   - **Motivo:** Incorporar la sección controlada "Actualización controlada de soporte de datos — Fase 1".
   - **Cambio:** Mapeo de tablas físicas hacia los RF y RNF correspondientes, conservando el estado funcional como `PENDIENTE`.
   - **Impacto:** Trazabilidad documental intacta y verificable sin adelanto funcional.

## 10. BASE DE DATOS

- **Cambios:** Creación completa del esquema físico en MySQL 8.0.42.
- **Tablas (20):**
  1. `roles`
  2. `usuarios`
  3. `docentes`
  4. `estudiantes`
  5. `encargados`
  6. `estudiantes_encargados`
  7. `periodos_escolares`
  8. `grados`
  9. `grados_periodos`
  10. `secciones`
  11. `aulas`
  12. `asignaciones_docentes`
  13. `inscripciones`
  14. `configuraciones_ubicacion`
  15. `marcajes_asistencia`
  16. `solicitudes_permiso`
  17. `historial_solicitudes_permiso`
  18. `documentos_permiso`
  19. `notificaciones`
  20. `tokens_recuperacion`
- **Restricciones:** 20 PKs autoincrementales sin signo, 26 FKs restrictivas, 11 UNIQUE simples y condicionales, 24 CHECK de dominio y congruencia.
- **Índices:** Índices B-tree en todas las claves foráneas, columnas de ordenamiento y búsquedas frecuentes.

## 11. FUNCIONALIDADES IMPLEMENTADAS

**NINGUNA** en código de aplicación PHP. No se implementaron routers, controladores, modelos PHP, vistas, autenticación web ni formularios, respetando la regla estricta de no adelantar trabajo de fases posteriores.

## 12. PRUEBAS REALIZADAS

Matriz de 18 pruebas automatizadas ejecutadas mediante `database/tests/validacion.sql` sobre esquema desechable:

| ID | Objetivo | Procedimiento | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|---|
| T-01 | Cantidad de tablas | Conteo en `information_schema.tables` | 20 | 20 | OK |
| T-02 | Cobertura de claves primarias | Conteo de PK en `table_constraints` | 20 tablas | 20 tablas | OK |
| T-03 | Cantidad de claves foráneas | Conteo de FK en `table_constraints` | 26 | 26 | OK |
| T-04 | PK rechaza duplicado | Inserción de ID de rol existente | Error 1062 | Error 1062 | OK |
| T-05 | FK rechaza referencia inexistente | Inserción de docente con `id_usuario` inexistente | Error 1452 | Error 1452 | OK |
| T-06 | UNIQUE rechaza valor repetido | Inserción de rol con nombre existente | Error 1062 | Error 1062 | OK |
| T-07 | NOT NULL rechaza ausencia | Inserción de rol con nombre nulo | Error 1048 | Error 1048 | OK |
| T-08 | CHECK rechaza capacidad cero | Inserción de sección con capacidad 0 | Error 3819 | Error 3819 | OK |
| T-09 | Solo un período activo | Inserción de un segundo período con estado 'ACTIVO' | Error 1062 | Error 1062 | OK |
| T-10 | Solo una asignación activa por sección | Inserción de segunda asignación activa para la misma sección | Error 1062 | Error 1062 | OK |
| T-11 | Consulta integral de inscripción | Join completo entre inscripción, estudiante, encargado, sección, grado y período | 1 fila relacionada | 1 fila relacionada | OK |
| T-12 | Consulta de asignación docente | Join completo entre asignación, docente, usuario, sección y aula | 1 fila relacionada | 1 fila relacionada | OK |
| T-13 | Cupos iniciales calculados | Conteo dinámico de capacidad menos confirmadas | 29 disponibles | 29 disponibles | OK |
| T-14 | Ocupación de sección | Inserción masiva controlada de prueba hasta alcanzar 30 confirmadas | 30 confirmadas | 30 confirmadas | OK |
| T-15 | Decisión de cupo al llegar a 30/30 | Evaluación de disponibilidad tras alcanzar capacidad máxima | RECHAZAR | RECHAZAR | OK |
| T-16 | Persistencia de marcaje válido | Inserción de marcaje dentro del radio | 1 marcaje | 1 marcaje | OK |
| T-17 | Flujo relacional de permisos | Join entre solicitud, historial, documento y notificación | 1 relación completa | 1 relación completa | OK |
| T-18 | Token de recuperación no vencido | Inserción y consulta de token activo con expiración a futuro | 1 token activo | 1 token activo | OK |

**Resumen:** 18 ejecutadas, 18 exitosas, 0 fallidas.

## 13. ERRORES ENCONTRADOS

Durante la primera validación se detectaron avisos por el uso de `TINYINT(1)` en MySQL 8.0, así como la necesidad de asegurar la unicidad condicional para registros activos sin requerir triggers.

## 14. CORRECCIONES REALIZADAS

Se sustituyó `TINYINT(1)` por `TINYINT UNSIGNED` con restricciones `CHECK (col IN (0, 1))` y se diseñaron columnas virtuales generadas `STORED` con valores condicionales `CASE WHEN estado = 'ACTIVO' THEN ... ELSE NULL END`, permitiendo que el motor aproveche la regla de que valores `NULL` no colisionan en índices únicos en MySQL.

## 15. PROBLEMAS NO RESUELTOS

Ninguno. El esquema físico, los scripts de semillas y las pruebas automatizadas son 100% reproducibles y no presentaron errores.

## 16. REQUERIMIENTOS PENDIENTES

Todos los requerimientos funcionales (RF-01 a RF-32) y no funcionales permanecen funcionalmente pendientes, correspondiendo su desarrollo a las Fases 2 a 15.

## 17. RIESGOS

- **Control de concurrencia en cupos (RF-32):** El modelo físico ofrece las restricciones e índices necesarios, pero la garantía ante condiciones de carrera depende críticamente de que la capa de servicio implemente transacciones con `SELECT ... FOR UPDATE` en la Fase 6.
- **Validación de geolocalización:** El cálculo de distancia en backend (mediante fórmula de Haversine o funciones espaciales) debe alinearse con la precisión métrica definida en `marcajes_asistencia` en la Fase 8.

## 18. SEGURIDAD

- Contraseñas almacenadas exclusivamente como hashes (`VARCHAR(255)`) con restricción `chk_usuarios_clave_hash` que exige longitud mínima de 60 caracteres.
- Prohibición absoluta de texto plano para credenciales y tokens.
- Tokens de recuperación con almacenamiento exclusivo de hash (`token_hash`) y control estricto de expiración y uso único.
- Semillas con datos ficticios (`DATO DE PRUEBA`), correos con dominios `.invalid` y teléfonos simulados.
- Ningún secreto ni credencial de base de datos incluido en scripts ni en el repositorio Git.

## 19. TRAZABILIDAD

Se actualizó la matriz en `docs/fase-0/matriz-trazabilidad.md` manteniendo la estricta correspondencia bidireccional entre requerimientos oficiales, entidades físicas, decisiones técnicas y pruebas ejecutadas.

## 20. COMMITS REALIZADOS

- `fa41ce7 docs(fase-1): definir modelo conceptual y logico`
- `09affc6 feat(fase-1): crear esquema inicial de base de datos`
- `b53e506 test(fase-1): validar integridad y recreacion del esquema`
- `c13c113 docs(fase-1): documentar diccionario de datos y trazabilidad`
- `[commit final] docs(fase-1): cerrar desarrollo y preparar auditoria tecnica`

## 21. GIT FINAL

- **Rama:** `main`
- **Estado:** Limpio (sin archivos modificados ni untracked tras el commit de cierre)
- **Repositorio limpio:** SÍ

## 22. AUDITORÍA DE AISLAMIENTO

- [x] Solo se utilizó este proyecto.
- [x] No se consultaron otros repositorios.
- [x] No se mezcló código de otros proyectos.
- [x] No se añadieron requerimientos externos.
- [x] No se incorporaron credenciales.
- [x] No se adelantó trabajo de otra fase.

## 23. AUTOVERIFICACIÓN DE ANTIGRAVITY

**LISTA PARA AUDITORÍA**

## 24. JUSTIFICACIÓN

El diseño de base de datos cumple con la totalidad del alcance de datos previsto para la Escuela Oficial Rural Mixta Lo de Fuentes. Cubre las 20 tablas derivadas de los requerimientos oficiales y de las decisiones técnicas documentadas para soportar el modelo relacional, sin ampliar el alcance ni omitir requerimientos. Las restricciones físicas de integridad referencial, unicidad, nulabilidad y chequeo fueron validadas satisfactoriamente mediante 18 pruebas automatizadas sin fallos. No se implementó código de aplicación PHP ni se introdujeron credenciales. El repositorio se encuentra limpio y documentado.

## 25. ELEMENTOS QUE CODEX DEBE REVISAR

1. **Esquema relacional en `database/schema.sql`:** Verificar las 20 tablas, 26 claves foráneas con reglas restrictivas, restricciones `CHECK`, índices B-tree y las 3 columnas generadas almacenadas para unicidad condicional.
2. **Semillas ficticias en `database/seed.sql`:** Verificar que no contengan datos reales, credenciales en texto plano ni correos operativos.
3. **Guion y resultados de pruebas en `database/tests/validacion.sql` y `docs/fase-1/pruebas-base-datos.md`:** Verificar la reproducibilidad de las 18 pruebas y su cobertura sobre restricciones y cálculos.
4. **Documentación conceptual, lógica y diccionario:** Verificar la coherencia entre `docs/fase-1/modelo-conceptual.md`, `docs/fase-1/modelo-logico.md`, `docs/fase-1/decisiones-tecnicas.md` y `docs/fase-1/diccionario-datos.md`.
5. **Aislamiento y no adelanto de trabajo:** Confirmar que no existen clases PHP, controladores, rutas ni formularios pertenecientes a fases futuras.

## 26. SIGUIENTE FASE PREVISTA

**FASE 2 — ESTRUCTURA BASE DE LA APLICACIÓN**
*(NO IMPLEMENTAR).*

## 27. DECLARACIÓN DE CIERRE

“ANTIGRAVITY HA FINALIZADO LA FASE 1.

EL PROYECTO QUEDA DETENIDO Y PREPARADO PARA AUDITORÍA
TÉCNICA INDEPENDIENTE POR CODEX.

NO INICIARÉ LA SIGUIENTE FASE NI REALIZARÉ CAMBIOS
ADICIONALES HASTA RECIBIR AUTORIZACIÓN EXPLÍCITA DEL
USUARIO DESPUÉS DE LA AUDITORÍA.”

============================================================
FIN DEL INFORME
============================================================
