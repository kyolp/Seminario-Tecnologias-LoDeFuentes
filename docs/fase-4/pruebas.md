# Suite de Pruebas — Fase 4

**Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes**  
**Fase 4: Administración Básica**  
**Responsable Técnico:** Antigravity — Desarrollador Principal  
**Fecha:** Septiembre 2026  

---

## 1. Cobertura de Pruebas Automatizadas

El archivo ejecutable `tests/run_tests_fase4.php` cubre de forma integral los 25 casos de prueba obligatorios correspondientes a la Fase 4:

| Identificador | Caso de Prueba | Tipo de Validación | Resultado |
|---|---|---|:---:|
| **T4-01** | Administrador accede a rutas administrativas | RBAC / HTTP 200 en las 8 rutas de administración | **OK** |
| **T4-02** | Docente no puede acceder a administración | RBAC / HTTP 403 Forbidden para rol DOCENTE | **OK** |
| **T4-03** | Usuario no autenticado es redirigido a login | Middleware Authenticate / HTTP 302 hacia `/login` | **OK** |
| **T4-04** | Intentos de mutación (POST) sin CSRF son rechazados | Middleware VerifyCsrfToken / HTTP 403 | **OK** |
| **T4-05** | Creación atómica de docente | Transaccional / Inserción en `usuarios` y `docentes` | **OK** |
| **T4-06** | Rollback atómico ante fallo en creación de docente | Atomicidad / Transacción revertida sin huérfanos | **OK** |
| **T4-07** | Edición de datos de docente | Persistencia / Modificación exitosa en BD | **OK** |
| **T4-08** | Desactivación de docente | Lógica / Cambio de estado a INACTIVO sin pérdida | **OK** |
| **T4-09** | Creación de estudiante con datos válidos | Persistencia / Registro en `estudiantes` | **OK** |
| **T4-10** | Validación de campos obligatorios en estudiante | Dominio / Rechazo con mensajes específicos | **OK** |
| **T4-11** | Creación de período escolar válido | Dominio / Registro en `periodos_escolares` | **OK** |
| **T4-12** | Rechazo de segundo período escolar activo simultáneo | Integridad / Restricción `uk_periodos_escolares_un_activo` | **OK** |
| **T4-13** | Creación de grado con orden de progresión válido | Dominio / Restricción de orden jerárquico positivo | **OK** |
| **T4-14** | Asociación de grado a período escolar existente | Relacional / Inserción en `grados_periodos` | **OK** |
| **T4-15** | Rechazo de grado duplicado en el mismo período | Integridad / Restricción `uk_grados_periodos` | **OK** |
| **T4-16** | Creación de sección con capacidad válida | Dominio / Capacidad entera positiva persistida | **OK** |
| **T4-17** | Rechazo de capacidad inválida en sección | Validación / Rechazo de 0, negativo, string y decimal | **OK** |
| **T4-18** | Capacidad mayor a 0 persistida y editable | Persistencia / Actualización de cupos en BD | **OK** |
| **T4-19** | Creación de aula y validación de código único | Integridad / Restricción `uk_aulas_codigo` | **OK** |
| **T4-20** | Integridad referencial: rechazo de FKs inexistentes | Relacional / Rechazo preventivo ante IDs foráneos | **OK** |
| **T4-21** | Prevención XSS en renderizado administrativo | Seguridad / Neutralización de payloads `<script>` | **OK** |
| **T4-22** | Protección contra asignación masiva (mass assignment) | Seguridad / Whitelist estricto en controladores/servicios | **OK** |
| **T4-23** | Regresión completa de Fase 3 (Autenticación y Roles) | Regresión / 19 de 19 pruebas de Fase 3 exitosas | **OK** |
| **T4-24** | Regresión completa de Fase 2 (Arquitectura Base) | Regresión / 16 de 16 pruebas de Fase 2 exitosas | **OK** |
| **T4-25** | Regresión completa de Fase 1 (Base de Datos) | Regresión / 18 de 18 pruebas relacionales en BD temporal | **OK** |

---

## 2. Evidencia de Pruebas HTTP Reales (Cliente cURL y Servidor PHP)

Se ejecutó una prueba de navegación HTTP real con servidor efímero PHP CLI (`php -S 127.0.0.1:8097 -t public`) y cURL, obteniendo:
- `POST /login` $\to$ `HTTP 302` (Autenticación de `admin.demo` exitosa con cookie de sesión emitida y token CSRF validado).
- `GET /administracion` $\to$ `HTTP 200`
- `GET /administracion/docentes` $\to$ `HTTP 200`
- `GET /administracion/docentes/crear` $\to$ `HTTP 200`
- `GET /administracion/estudiantes` $\to$ `HTTP 200`
- `GET /administracion/estudiantes/crear` $\to$ `HTTP 200`
- `GET /administracion/periodos` $\to$ `HTTP 200`
- `GET /administracion/periodos/crear` $\to$ `HTTP 200`
- `GET /administracion/grados` $\to$ `HTTP 200`
- `GET /administracion/grados/crear` $\to$ `HTTP 200`
- `GET /administracion/grados-periodos` $\to$ `HTTP 200`
- `GET /administracion/grados-periodos/crear` $\to$ `HTTP 200`
- `GET /administracion/secciones` $\to$ `HTTP 200`
- `GET /administracion/secciones/crear` $\to$ `HTTP 200`
- `GET /administracion/aulas` $\to$ `HTTP 200`
- `GET /administracion/aulas/crear` $\to$ `HTTP 200`

---

## 3. Resumen Global de Regresiones

- **Fase 1 (Base de Datos):** 18/18 pruebas aprobadas en base de datos temporal aislada.
- **Fase 2 (Infraestructura):** 16/16 pruebas aprobadas en proceso independiente.
- **Fase 3 (Autenticación y Roles):** 19/19 pruebas aprobadas en proceso independiente.
- **Fase 4 (Administración Básica):** 25/25 pruebas aprobadas satisfactoriamente.
- **Total de pruebas en suite combinada:** 78 pruebas ejecutadas, **78 exitosas, 0 fallidas**.
