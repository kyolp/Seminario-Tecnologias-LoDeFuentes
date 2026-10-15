# Registro de Pruebas Automatizadas — Fase 12
## Sistema Web — Escuela Oficial Rural Mixta Lo de Fuentes

**Módulo:** Reportes Institucionales (RF-29 y RF-30)  
**Script de Ejecución:** `tests/run_tests_fase12.php`  
**Total de Pruebas:** 30  
**Exitosas (OK):** 30  
**Fallidas (FALLO):** 0  
**Estado:** 100% GREEN (Aprobada)  

---

### Tabla Detallada de Pruebas

| Código | Requisito | Descripción de la Prueba | Resultado Obtenido | Estado |
|---|---|---|---|:---:|
| **T12-01** | Trazabilidad | Trazabilidad documental oficial: RF-29 y RF-30 en Fase 12; RF-03 pendiente en Fase 13 | RF-29 y RF-30 verificados en matriz de Fase 12; RF-03 asignado a Fase 13 | **OK** |
| **T12-02** | RF-29 | Consulta básica de marcajes de asistencia por administración con datos reales | Total: 4 marcajes (3 entradas, 1 salida) obtenidos de base de datos | **OK** |
| **T12-03** | RF-29 | Filtro por docente específico aísla exclusivamente sus marcajes | Exactamente 3 marcajes del Docente 1 con docente único en resumen | **OK** |
| **T12-04** | RF-29 | Criterio técnico auxiliar: filtro por período escolar acota las fechas de marcajes al ciclo lectivo | Ciclo 2026: 4 marcajes; Ciclo 2025: 0 marcajes | **OK** |
| **T12-05** | RF-29 | Filtro por rango de fechas acota estrictamente la consulta | Rango 2026-03-10: 3 marcajes (2 entradas, 1 salida) | **OK** |
| **T12-06** | RF-29 | Validación de consistencia temporal: rechazo estricto si fecha_fin < fecha_inicio | InvalidArgumentException capturada con mensaje explicativo | **OK** |
| **T12-07** | RF-29 | Filtro por tipo de marcaje discrimina con precisión entradas y salidas | Entradas: 2, Salidas: 1 para el docente evaluado | **OK** |
| **T12-08** | RF-29 | Consulta con filtros sin coincidencias devuelve estructura vacía controlada sin error | Array vacío y contadores en cero sin excepciones no controladas | **OK** |
| **T12-09** | RF-29 | Inmutabilidad estricta: generar reportes no muta registros de asistencia | Conteo en tabla idéntico antes y después de consultas | **OK** |
| **T12-10** | RF-29 / RNF-04 | Mínima exposición: reporte no expone coordenadas privadas ni credenciales | Sin lat/lng personales de dispositivos ni contraseñas; solo distancia institucional | **OK** |
| **T12-11** | RF-30 | Consulta general de cupos por sección y listado de inscritos por período | 2 secciones, 4 inscripciones, capacidad total 7 | **OK** |
| **T12-12** | RF-30 | Cálculo de cupos reutiliza estrictamente la regla institucional de Fase 6 | Ocupados = 2 coincidiendo con conteo de InscripcionRepository | **OK** |
| **T12-13** | RF-30 | Sección con capacidad colmada reporta estado LLENO y cero disponibles | Capacidad 2, ocupados 2 -> Estado LLENO, disponibles 0 | **OK** |
| **T12-14** | RF-30 | Sección con cupos disponibles reporta estado DISPONIBLE y saldo exacto | Capacidad 5, ocupados 1 -> Estado DISPONIBLE, disponibles 4 (anulada no descuenta) | **OK** |
| **T12-15** | RF-30 | Filtro de inscripciones por ciclo escolar aísla las inscripciones del ciclo | Ciclo 2026: 4 estudiantes; Ciclo 2025: 0 estudiantes | **OK** |
| **T12-16** | RF-30 | Filtro por grado aísla exclusivamente los estudiantes del grado solicitado | 4 estudiantes pertenecientes al Grado 1 evaluado | **OK** |
| **T12-17** | RF-30 | Filtro por sección aísla los estudiantes asignados a dicha sección | Sección 1: 2 estudiantes; Sección 2: 2 estudiantes | **OK** |
| **T12-18** | RF-30 | Filtro por estado de inscripción discrimina confirmadas, pendientes y anuladas | Sección 1: 1 confirmada, 1 pendiente | **OK** |
| **T12-19** | RF-30 | Consulta sin coincidencias devuelve estructura vacía controlada sin error | Listados vacíos y métricas en cero sin fallos de ejecución | **OK** |
| **T12-20** | RF-30 / RNF-04 | Privacidad de menores: no se exponen teléfonos, correos ni datos de contacto | Sin teléfonos ni correos de encargados en la consulta administrativa | **OK** |
| **T12-21** | Seguridad | Mitigación de Inyección SQL en parámetros de filtros de reportes | Validación previa bloqueó payload y sentencia preparada neutralizó inyección | **OK** |
| **T12-22** | Seguridad | Prevención de Cross-Site Scripting (XSS) mediante escape e() en vistas | Cero salidas no escapadas; 100% de interpolaciones con e() | **OK** |
| **T12-23** | Seguridad | Parámetros maliciosos o inexistentes rechazados de forma controlada | Docente inexistente y estado fuera de whitelist rechazados | **OK** |
| **T12-24** | RBAC | Acceso anónimo a /administracion/reportes redirige a /login (HTTP 302) | Redirección 302 hacia /login confirmada | **OK** |
| **T12-25** | RBAC | Rol DOCENTE denegado con HTTP 403 Forbidden en reportes administrativos | HTTP 403 en reporte de asistencia y en reporte de inscripciones | **OK** |
| **T12-26** | HTTP Real | Consulta de reporte de asistencia por administrador autenticado en servidor embebido | HTTP 200 con tabla de asistencia, tipo ENTRADA y geocerca institucional | **OK** |
| **T12-27** | HTTP Real | Consulta de inscripciones y cupos por administrador autenticado en servidor embebido | HTTP 200 con tabla de cupos (LLENO y DISPONIBLE) y listado de inscritos | **OK** |
| **T12-28** | HTTP Real | Acceso anónimo redirige a login (302) y docente recibe 403 Forbidden | HTTP 302 a login para anónimo y HTTP 403 para docente | **OK** |
| **T12-29** | Base de Datos | schema.sql intacto sin tablas ni campos redundantes de reportes | Cero tablas de reportes o estadísticas en schema.sql y en motor MySQL | **OK** |
| **T12-30** | No Contaminación | Fase 12 no deja nuevos fixtures o datos temporales permanentes después de ejecutar su suite | Fixtures creados por Fase 12 eliminados al 100% (cero residuos de usuarios, marcajes, inscripciones o secciones de prueba de Fase 12) | **OK** |

---

### Resumen de Ejecución
- **30 pruebas ejecutadas, 30 aprobadas (100%).**
- Pruebas HTTP reales ejecutadas mediante servidor web PHP embebido en puerto 8100 y cliente cURL.
- **Alcance de T12-30:** La prueba verifica exclusivamente que la suite de Fase 12 no deje datos residuales ni fixtures permanentes creados durante su propia ejecución. No se realiza afirmación sobre la totalidad de la base de datos histórica, cuya limpieza integral y depuración de datos semilla está formalmente reservada para la Fase 16.
