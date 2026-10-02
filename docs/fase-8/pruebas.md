# REPORTE DE PRUEBAS AUTOMATIZADAS — FASE 8

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 8 — Asistencia Docente y Geolocalización  
**Rol:** ANTIGRAVITY — DESARROLLADOR PRINCIPAL  
**Script de pruebas:** `tests/run_tests_fase8.php`  
**Resultado:** **37 / 37 Pruebas Exitosas (100% GREEN)**  

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
| **T8-10** | RF-19, RF-20 | Marcaje docente dentro del radio institucional permitido ($\approx 30$ m) -> Aceptado y persistido. | **OK** |
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
| **T8-29** | Flujo HTTP Real | Flujo HTTP real completo con servidor PHP embebido y cURL: Login $\to$ Form $\to$ Entrada $\to$ Doble $\to$ Fuera $\to$ Salida. | **OK** |
| **T8-30** | DT-49 / Seguridad | Parámetros maliciosos de cliente descartados (spoofing de dentro_radio/distancia rechazado por backend). | **OK** |
| **T8-31** | Regresión F7 | Regresión completa de Fase 7 (Portal Docente): 28/28 pruebas superadas. | **OK** |
| **T8-32** | Regresión F6 | Regresión completa de Fase 6 (Inscripciones y Cupos): 30/30 pruebas superadas (incluye concurrencia). | **OK** |
| **T8-33** | Regresión F5 | Regresión completa de Fase 5 (Asignaciones Docentes): 26/26 pruebas superadas. | **OK** |
| **T8-34** | Regresión F4 | Regresión completa de Fase 4 (Administración Básica): 27/27 pruebas superadas. | **OK** |
| **T8-35** | Regresión F3 | Regresión completa de Fase 3 (Autenticación y Roles): 19/19 pruebas superadas. | **OK** |
| **T8-36** | Regresión F2 | Regresión completa de Fase 2 (Arquitectura Base y Enrutamiento): 16/16 pruebas superadas. | **OK** |
| **T8-37** | Regresión F1 | Regresión completa de Fase 1 (Diseño de Base de Datos Relacional): 18/18 pruebas superadas. | **OK** |

---

## 2. Resumen Estadístico de Ejecución

- **Total de pruebas ejecutadas:** 37
- **Total de pruebas aprobadas:** 37 (100%)
- **Total de pruebas fallidas:** 0 (0%)
- **Regresiones históricas acumuladas:** 164 pruebas de Fases 1 a 7 ejecutadas y verificadas sin regresión técnica.
