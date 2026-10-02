# REPORTE DE PRUEBAS AUTOMATIZADAS — FASE 8

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 8 — Asistencia Docente y Geolocalización  
**Rol:** ANTIGRAVITY — DESARROLLADOR PRINCIPAL  
**Script de pruebas:** `tests/run_tests_fase8.php`  
**Resultado:** **40 / 40 Pruebas Exitosas (100% GREEN)**  

---

## 1. Matriz Consolidada de Pruebas de Fase 8

| ID | Requisito / Componente | Descripción de la Prueba | Resultado |
| :---: | :---: | :--- | :---: |
| **T8-01** | RF-18 / Configuración | Configuración institucional de ubicación válida por administrador (radio 120m, activa=1). | **OK** |
| **T8-02** | RF-18 / DT-53 | Transaccionalidad en activación de geocerca: versión previa pasa a histórico con `fecha_fin` no nula. | **OK** |
| **T8-03** | RF-18 / Validación | Rechazo de configuración de ubicación con radio $\le 0$ metros (`InvalidArgumentException`). | **OK** |
| **T8-04** | RF-18 / Validación | Rechazo de configuración con latitud fuera de rango $[-90, 90]$. | **OK** |
| **T8-05** | RF-18 / Validación | Rechazo de configuración con longitud fuera de rango $[-180, 180]$. | **OK** |
| **T8-06** | RF-18 / Validación | Rechazo de configuración con descripción vacía o $> 150$ caracteres. | **OK** |
| **T8-07** | RF-18 / RBAC | Control de acceso RBAC a configuración de geocerca: DOCENTE (HTTP 403), Anónimo (HTTP 302). | **OK** |
| **T8-08** | RF-18 / Integridad BD | Unicidad condicional de geocerca activa en BD (`uk_configuraciones_ubicacion_activa`). | **OK** |
| **T8-09** | RF-19 / DT-50 | Precisión matemática del cálculo geodésico Haversine con $R = 6,371,000$ m contra referencia conocida. | **OK** |
| **T8-10** | RF-19, RF-20 | Marcaje docente dentro del radio institucional permitido ($\approx 20.6$ m) -> Aceptado y persistido. | **OK** |
| **T8-11** | RF-19 / Frontera | Evaluación inclusiva de la frontera de radio institucional ($\text{distancia} \le \text{radio}$). | **OK** |
| **T8-12** | RF-19 / Rechazo | Rechazo estricto de marcaje fuera del radio institucional ($\approx 1.5$ km) con cero inserciones. | **OK** |
| **T8-13** | RF-19 / Resiliencia | Rechazo de marcaje ante ausencia de geocerca institucional activa con mensaje explicativo. | **OK** |
| **T8-14** | RF-20 / Entrada | Registro exitoso de marcaje de ENTRADA docente (tipo ENTRADA, dentro_radio=1). | **OK** |
| **T8-15** | RF-20 / Secuencia | Prevención de doble marcaje de ENTRADA consecutivo sin salida intermedia (`DomainException`). | **OK** |
| **T8-16** | RF-21 / Salida | Registro exitoso de marcaje de SALIDA docente cerrando la jornada laboral. | **OK** |
| **T8-17** | RF-21 / Secuencia | Rechazo de marcaje de SALIDA cuando no existe registro previo de entrada. | **OK** |
| **T8-18** | RF-21 / Secuencia | Prevención de doble marcaje de SALIDA consecutivo. | **OK** |
| **T8-19** | RF-20, RF-21 / Flujo | Secuencia alternada completa de marcajes: ENTRADA $\to$ SALIDA $\to$ ENTRADA $\to$ SALIDA. | **OK** |
| **T8-20** | RF-22 / DT-51 | Fecha y hora autoritativas de servidor (`CURRENT_TIMESTAMP(6)`), ignorando timestamps de cliente. | **OK** |
| **T8-21** | Seguridad / Estado | Rechazo de marcaje de asistencia para docente en estado INACTIVO. | **OK** |
| **T8-22** | Seguridad / RBAC | Rechazo de marcaje de asistencia para rol ADMINISTRADOR (sin rol DOCENTE -> HTTP 403). | **OK** |
| **T8-23** | Seguridad / Auth | Rechazo de marcaje de asistencia para usuario no autenticado (HTTP 302 a `/login`). | **OK** |
| **T8-24** | RF-23 / DT-54 | Aislamiento horizontal estricto de historial entre docentes (0 contaminación cruzada de datos). | **OK** |
| **T8-25** | RF-23 / Orden | Orden cronológico descendente estricto en historial de marcajes (`fecha_hora DESC`). | **OK** |
| **T8-26** | RNF-06 / DT-52 | Inmutabilidad de marcajes: repositorio carece de métodos de edición o borrado (`update`, `delete`). | **OK** |
| **T8-27** | RNF-06 / Integridad | Integridad física en base de datos: constraint `chk_marcajes_dentro_radio (dentro_radio = 1)`. | **OK** |
| **T8-28** | Seguridad / XSS | Sanitización y prevención de XSS en vistas de asistencia docente mediante `e(...)`. | **OK** |
| **T8-29** | Flujo HTTP Real | Flujo HTTP real completo con servidor PHP embebido y cURL (13 verificaciones integrales). | **OK** |
| **T8-30** | DT-49 / Seguridad | Parámetros maliciosos de cliente descartados (spoofing de dentro_radio/distancia rechazado por backend). | **OK** |
| **T8-31** | Regresión F7 | Regresión completa de Fase 7 (Portal Docente): 28/28 pruebas superadas. | **OK** |
| **T8-32** | Regresión F6 | Regresión completa de Fase 6 (Inscripciones y Cupos): 30/30 pruebas superadas (incluye concurrencia). | **OK** |
| **T8-33** | Regresión F5 | Regresión completa de Fase 5 (Asignaciones Docentes): 26/26 pruebas superadas. | **OK** |
| **T8-34** | Regresión F4 | Regresión completa de Fase 4 (Administración Básica): 27/27 pruebas superadas. | **OK** |
| **T8-35** | Regresión F3 | Regresión completa de Fase 3 (Autenticación y Roles): 19/19 pruebas superadas. | **OK** |
| **T8-36** | Regresión F2 | Regresión completa de Fase 2 (Arquitectura Base y Enrutamiento): 16/16 pruebas superadas. | **OK** |
| **T8-37** | Regresión F1 | Regresión completa de Fase 1 (Diseño de Base de Datos Relacional): 18/18 pruebas superadas. | **OK** |
| **T8-38** | RF-20, RF-21 / CSRF | Protección CSRF real en marcajes (F8-01): servidor embebido y cURL (Casos A, B, C -> 403, 0 persistencia; Caso D -> 200, 1 persistencia). | **OK** |
| **T8-39** | RF-19 / Geolocation | Manejo de errores Geolocation API (F8-02): harness JS para códigos 1, 2, 3 e incompatibilidad; backend rechaza coordenadas ausentes con 422. | **OK** |
| **T8-40** | RF-19, DT-49 / Validación | Validación estricta de coordenadas (F8-03): 13 casos inválidos rechazados con HTTP 422, cero persistencia y cero fugas de traza SQL. | **OK** |

---

## 2. Detalle de Pruebas Incorporadas en Corrección de Fase 8

### T8-29 — Flujo HTTP Real Completo de Asistencia Docente (13 Verificaciones)
Ejecución sobre servidor web PHP embebido en puerto efímero `8095` y cliente cURL real con persistencia de cookies de sesión:
1. **Login DOCENTE:** `POST /login` con credenciales legítimas $\to$ HTTP 302 Redirección a `/docente`.
2. **Acceso al módulo:** `GET /docente/asistencia` $\to$ HTTP 200 OK con formulario de asistencia y geocerca activa.
3. **Extracción de CSRF:** Token `_token` emitido por el formulario recuperado satisfactoriamente.
4. **Marcaje de Entrada dentro de radio:** `POST /docente/asistencia/entrada` (latitud: 14.6349150, longitud: -90.5068820) $\to$ HTTP 200 JSON (`exito: true`).
5. **Persistencia física en BD:** Verificación directa en tabla `marcajes_asistencia` confirmando registro tipo `ENTRADA` con `dentro_radio = 1` y $\text{distancia} \le 100.0\text{ m}$.
6. **Rechazo de doble entrada consecutiva:** Repetición inmediata de `POST /docente/asistencia/entrada` $\to$ HTTP 422 JSON denegado por secuencia inválida.
7. **Marcaje de Salida dentro de radio:** `POST /docente/asistencia/salida` con coordenadas válidas $\to$ HTTP 200 JSON (`exito: true`).
8. **Consulta de historial personal:** `GET /docente/asistencia` $\to$ HTTP 200 OK renderizando la tabla "Mi Historial Reciente de Asistencia" con los marcajes inmutables.
9. **Descarte de spoofing de identidad:** Intento de envío de parámetro `id_docente` de un docente ajeno $\to$ El servidor lo ignora y procesa el marcaje exclusivamente para la identidad de la sesión; la base de datos confirma 0 registros atribuidos al docente ajeno.
10. **Rechazo de coordenadas fuera de radio:** `POST /docente/asistencia/entrada` con desfase geográfico (+0.05°) $\to$ HTTP 422 JSON denegado por distancia excedida.
11. **Cero persistencia fuera de radio:** Consulta directa a la base de datos confirmando 0 inserciones asociadas al intento fuera de radio.
12. **Control de acceso por rol:** Usuario con rol `ADMINISTRADOR` intenta acceder a `GET /docente/asistencia` $\to$ HTTP 403 Forbidden.
13. **Control de acceso anónimo:** Solicitud sin cookies de sesión $\to$ HTTP 302 Redirección a `/login`.
14. **Finalización limpia:** Proceso servidor finalizado mediante `taskkill` y archivos temporales de cookies eliminados.

### T8-38 — Protección CSRF Real en Marcajes de Asistencia (F8-01)
Prueba HTTP real sobre servidor web PHP embebido en puerto `8096` evaluando los 4 escenarios de seguridad CSRF:
- **Caso A (POST entrada sin token):** Solicitud mutante sin campo `_token` $\to$ HTTP 403 Forbidden. Cero persistencia en `marcajes_asistencia`.
- **Caso B (POST entrada con token manipulado):** Solicitud mutante con `_token = "token_manipulado_invalido_fase8_xyz"` $\to$ HTTP 403 Forbidden. Cero persistencia en base de datos.
- **Caso C (POST salida sin token válido):** Solicitud mutante `POST /docente/asistencia/salida` con `_token = "token_falso_salida_123"` $\to$ HTTP 403 Forbidden. Cero persistencia en base de datos.
- **Caso D (POST entrada con token legítimo):** Solicitud con token extraído del formulario de sesión activa $\to$ HTTP 200 JSON (`exito: true`). Exactamente 1 fila persistida en la base de datos.

### T8-39 — Manejo de Errores de Geolocation API (F8-02)
- **Frontend (Harness Headless en Node.js `tests/harness_geolocation.js`):**
  - **Caso A (código 1 — `PERMISSION_DENIED`):** Muestra alerta "Permiso denegado por el usuario para acceder a la ubicación", no envía formulario y mantiene coordenadas vacías.
  - **Caso B (código 2 — `POSITION_UNAVAILABLE`):** Muestra alerta "Ubicación no disponible", no envía formulario y re-habilita botones.
  - **Caso C (código 3 — `TIMEOUT`):** Muestra alerta "Tiempo de espera agotado al obtener la ubicación", no envía formulario y re-habilita botones.
  - **Caso D (Sin soporte de `navigator.geolocation`):** Muestra advertencia clara de incompatibilidad del navegador, impidiendo el envío.
- **Backend:** Peticiones HTTP con coordenadas vacías o ausentes devuelven HTTP 422 Unprocessable Content, sin generar marcajes ni recurrir a valores de respaldo ficticios.

### T8-40 — Validación Estricta de Coordenadas Recibidas (F8-03)
Evaluación exhaustiva de 13 combinaciones anómalas contra `DocenteAsistenciaController::marcarEntrada`:
1. `latitud > 90` (ej. 90.000001) $\to$ HTTP 422 JSON.
2. `latitud < -90` (ej. -90.000001) $\to$ HTTP 422 JSON.
3. `longitud > 180` (ej. 180.000001) $\to$ HTTP 422 JSON.
4. `longitud < -180` (ej. -180.000001) $\to$ HTTP 422 JSON.
5. `latitud` alfanumérica (`"no_valida"`) $\to$ HTTP 422 JSON.
6. `longitud` alfanumérica (`"invalida_lon"`) $\to$ HTTP 422 JSON.
7. `latitud` cadena vacía (`""`) $\to$ HTTP 422 JSON.
8. `longitud` cadena vacía (`""`) $\to$ HTTP 422 JSON.
9. `latitud` nula (`null`) $\to$ HTTP 422 JSON.
10. `longitud` nula (`null`) $\to$ HTTP 422 JSON.
11. `latitud` con valor infinito (`"INF"`) $\to$ HTTP 422 JSON.
12. `longitud` con valor no numérico (`"NAN"`) $\to$ HTTP 422 JSON.
13. Campos de coordenadas completamente ausentes (`[]`) $\to$ HTTP 422 JSON.

**Verificación transversal:** En los 13 casos el mensaje es claro y controlado (`exito: false`), no se filtran excepciones internas (`PDOException`, `Stack trace`, `SQLSTATE`), y la base de datos confirma **0 filas persistidas**.

---

## 3. Resumen Estadístico de Ejecución

- **Total de pruebas ejecutadas:** 40
- **Total de pruebas aprobadas:** 40 (100% GREEN)
- **Total de pruebas fallidas:** 0 (0%)
- **Pruebas unitarias y funcionales directas de Fase 8:** 33 pruebas.
- **Regresiones históricas acumuladas:** 7 suites completas (Fases 1 a 7: 164 pruebas) ejecutadas y superadas sin fallos.
