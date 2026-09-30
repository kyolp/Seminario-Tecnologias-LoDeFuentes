# Suite de Pruebas Automatizadas — Fase 5: Asignaciones Docentes

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 5 — Asignaciones Docentes  
**Archivo de Suite:** `tests/run_tests_fase5.php`  
**Total Pruebas:** 25 ejecutadas | 25 exitosas (100% OK) | 0 fallos  
**Fecha de Ejecución:** Septiembre 2026  

---

## 1. Resumen Ejecutivo de la Suite

La suite de pruebas automatizadas de Fase 5 comprende 25 verificaciones exhaustivas:
- **20 Pruebas Unitarias y de Integración (T5-01 a T5-20):** Validan control de acceso RBAC, protección CSRF, validaciones relacionales en cascada, unicidad de asignación activa, columna generada `id_seccion_activa`, preservación histórica, transaccionalidad con rollback en reemplazo docente, protección contra Mass Assignment, integridad referencial y prevención de XSS.
- **1 Prueba de Flujo HTTP Real Completo (T5-21):** Emula una sesión web completa con cookies, redirecciones 302, validaciones de vista 200, rechazos de duplicidad 422, bloqueo RBAC 403 y mutaciones reales verificadas directamente en la base de datos.
- **4 Pruebas de Regresión Integral (T5-22 a T5-25):** Ejecutan en subprocesos aislados las suites completas de Fase 4 (27 pruebas), Fase 3 (19 pruebas), Fase 2 (16 pruebas) y Fase 1 (18 pruebas en base de datos temporal), garantizando cero regresión sobre los módulos previos.

---

## 2. Matriz Detallada de Pruebas

| ID | Nombre de la Prueba | Alcance / Propósito | Resultado Esperado | Resultado Obtenido | Estado |
|---|---|---|---|---|---|
| **T5-01** | Administrador accede a asignaciones | RBAC / Autorización de administrador | HTTP 200 en listado y formulario de creación | `GET /asignaciones: 200 \| GET /crear: 200` | **OK** |
| **T5-02** | Docente intenta administrar asignaciones | RBAC / Rechazo de privilegios insuficientes | HTTP 403 Forbidden para rol DOCENTE en GET y POST | `GET list: 403 \| GET crear: 403 \| POST crear: 403 (403 Forbidden confirmado)` | **OK** |
| **T5-03** | Usuario anónimo es redirigido a login | Autenticación / Protección de rutas | HTTP 302 con cabecera `Location: /login` | `Status 302, Location: /login` | **OK** |
| **T5-04** | Intentos de mutación sin CSRF rechazados | Seguridad CSRF / Integridad | HTTP 403 Forbidden; cero inserciones en BD | `Sin token: 403 \| Token inválido: 403 \| Registros en BD antes: 1, después: 1 (0 mutaciones)` | **OK** |
| **T5-05** | Crear asignación válida con entidades activas | RF-16 / Inserción de negocio y columna generada | Asignación creada en BD en estado ACTIVA; `id_seccion_activa` poblado automáticamente | `Asignación creada: Docente en Sección (Aula) \| Estado: ACTIVA \| Columna generada id_seccion_activa poblada` | **OK** |
| **T5-06** | Rechazo de docente inexistente | Validación relacional / Integridad | Rechazo controlado con mensaje de error específico | `Éxito: NO \| Mensaje: "El docente seleccionado no existe en el sistema."` | **OK** |
| **T5-07** | Rechazo de docente inactivo | Validación de negocio / Estado docente | Rechazo de docentes con estado != ACTIVO | `Éxito: NO \| Mensaje: "El docente seleccionado se encuentra inactivo y no puede ser asignado."` | **OK** |
| **T5-08** | Rechazo de sección inexistente | Validación relacional / Integridad | Rechazo con mensaje descriptivo | `Éxito: NO \| Mensaje: "La sección seleccionada no existe en el sistema."` | **OK** |
| **T5-09** | Rechazo de aula inexistente o inactiva | Validación física / Estado aula | Rechazo tanto para ID inexistente como para aula inactiva | `Inexistente rechazada: SÍ \| Inactiva rechazada: SÍ` | **OK** |
| **T5-10** | Combinación académica inválida rechazada | Contexto académico / Ciclos | Rechazo de secciones en períodos cerrados o incompatibles | `Período cerrado rechazado: SÍ \| Período incompatible rechazado: SÍ` | **OK** |
| **T5-11** | Rechazo de duplicado activo de sección | Unicidad RF-16 / `uk_asignaciones_seccion_activa` | Rechazo preventivo en Service y error 1062 en BD | `Preventivo en Service: RECHAZADO \| Constraint uk_asignaciones_seccion_activa (Error 1062): DISPARADO Y BLOQUEADO` | **OK** |
| **T5-12** | Histórico conservado al cerrar asignación | Auditoría / No eliminación física | Estado pasa a INACTIVA, fecha_fin registrada, columna generada pasa a NULL | `Resultado cierre: Exitoso \| Estado en BD: INACTIVA \| Fecha fin registrada \| id_seccion_activa en BD: NULL (liberado)` | **OK** |
| **T5-13** | Nueva asignación tras cierre anterior | Transición histórica de sección | Nueva asignación activa permitida; histórico preservado | `Nueva asignación creada con éxito \| Históricas en sección: 2 \| Activas vigentes: 1` | **OK** |
| **T5-14** | Edición autorizada de campos permitidos | Mantenimiento / Actualización controlada | Aula física y fecha_fin actualizadas en BD | `Éxito: Asignación actualizada exitosamente \| Aula y Fecha fin actualizadas en BD` | **OK** |
| **T5-15** | Protección contra asignación masiva | Seguridad / Mass Assignment Whitelisting | Parámetros adulterados descartados silenciosamente | `ID asignación: no alterado \| Docente: inmutable \| Sección: inmutable \| Aula: actualizada` | **OK** |
| **T5-16** | Columna generada protegida frente a cliente | Seguridad / SQL Injection & Spoofing | `id_seccion_activa` calculado exclusivamente por MySQL | `id_seccion_activa en BD derivado de id_seccion; valor manipulado en cliente 999999 fue ignorado` | **OK** |
| **T5-17** | Prevención de Cross-Site Scripting (XSS) | Seguridad / Sanitización de salida | Payloads `<script>` escapados con `htmlspecialchars` | `Script crudo encontrado: NO \| Script escapado encontrado: SÍ` | **OK** |
| **T5-18** | Integridad referencial y rechazo de FKs | Integridad referencial / Base de datos | Captura limpia de violaciones FK sin volcado SQL | `Éxito: NO \| Mensaje limpio al usuario: "La sección seleccionada no existe en el sistema."` | **OK** |
| **T5-19** | Manejo de colisión y concurrencia | Concurrencia / Condición de carrera | Primera transacción gana; segunda es rechazada; BD consistente | `Primer intento: OK \| Segundo intento: RECHAZADO \| Asignaciones activas en BD: 1 (integridad garantizada)` | **OK** |
| **T5-20** | Reemplazo atómico y rollback ante fallo | Atomicidad ACID / Transacción PDO | Rollback completo ante excepción; éxito indivisible en flujo normal | `Rollback ante fallo: OK (Previa permaneció ACTIVA) \| Reemplazo exitoso: OK (Previa: INACTIVA, Nueva: ACTIVA)` | **OK** |
| **T5-21** | Flujo HTTP real de asignaciones docentes | Integración End-to-End HTTP | Sesión, login, navegación, validaciones, creación, cierre y cleanup | `Login Admin: 302 \| GET List: 200 \| GET Form: 200 \| POST Crear: 302 \| POST Dup: 422 \| Docente: 403 \| Sin auth: 302 \| Cierre: 302` | **OK** |
| **T5-22** | Regresión completa de Fase 4 | Regresión / Administración Básica | 27 pruebas de Fase 4 ejecutadas y aprobadas | `27/27 pruebas de Fase 4 aprobadas satisfactoriamente` | **OK** |
| **T5-23** | Regresión completa de Fase 3 | Regresión / Autenticación y Roles | 19 pruebas de Fase 3 ejecutadas y aprobadas | `19/19 pruebas de Fase 3 aprobadas satisfactoriamente` | **OK** |
| **T5-24** | Regresión completa de Fase 2 | Regresión / Arquitectura Base | 16 pruebas de Fase 2 ejecutadas y aprobadas | `16/16 pruebas de Fase 2 aprobadas satisfactoriamente` | **OK** |
| **T5-25** | Regresión completa de Fase 1 | Regresión / Base de Datos Relacional | 18 pruebas de validación SQL ejecutadas y aprobadas | `18/18 pruebas de Fase 1 ejecutadas con 18 exitosas y 0 fallidas en BD temporal aislada` | **OK** |

---

## 3. Evidencia del Flujo HTTP Real (T5-21)

La prueba `T5-21` simula la interacción de un navegador web a través del Front Controller del sistema:

1. **Autenticación del Administrador:**
   - Envío de credenciales a `/login`.
   - Respuesta: HTTP 302 hacia `/administracion`. Cookie de sesión establecida.
2. **Acceso a Vistas Administrativas:**
   - Petición `GET /administracion/asignaciones`: HTTP 200 con listado renderizado.
   - Petición `GET /administracion/asignaciones/crear`: HTTP 200 con formulario y token CSRF válido.
3. **Creación Válida de Asignación:**
   - Envío `POST /administracion/asignaciones/crear` con token CSRF y datos de prueba.
   - Respuesta: HTTP 302 hacia `/administracion/asignaciones`.
   - Verificación directa en BD: Registro creado con `estado = 'ACTIVA'` y `id_seccion_activa` coincidente con `id_seccion`.
4. **Intento de Asignación Duplicada:**
   - Envío de un segundo `POST` para la misma sección con otro docente.
   - Respuesta: HTTP 422 Unprocessable Entity con mensaje de error en vista. Base de datos inalterada.
5. **Aislamiento por Rol:**
   - Autenticación con cuenta de docente (`docente.demo`).
   - Petición `GET /administracion/asignaciones`: HTTP 403 Forbidden.
6. **Protección a Invitados:**
   - Petición sin cookie de sesión a `/administracion/asignaciones`: HTTP 302 hacia `/login`.
7. **Cierre de Asignación por HTTP:**
   - Envío `POST /administracion/asignaciones/estado` con acción `cerrar`.
   - Respuesta: HTTP 302.
   - Verificación en BD: `estado = 'INACTIVA'`, `id_seccion_activa = NULL`.
