# INFORME DE CORRECCIÓN — FASE 3

## PROYECTO: Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes
## ROL: ANTIGRAVITY — DESARROLLADOR PRINCIPAL
## ESTADO: FASE 3 CORREGIDA Y AUDITABLE

---

## 1. INTRODUCCIÓN Y CONTEXTO

Tras la revisión externa de la **Fase 3 — Autenticación y Roles**, se recibieron dos observaciones de ajuste técnico y documental:
1. **F3-01 — Corrección de trazabilidad de RF/RNF:** Restituir la nomenclatura y clasificación literal oficial de la fuente de requisitos (`docs/fase-0/matriz-trazabilidad.md`), aclarando que `RF-02` corresponde a *Roles y permisos*, que el cierre de sesión es un mecanismo técnico complementario de seguridad de autenticación (ligado a `RF-01` y `RNF-03`), y que `RNF-04` corresponde a *Privacidad* (asignado a Fase 14).
2. **F3-02 — Prueba técnica de fijación de sesión por HTTP real (T3-19):** Implementar la prueba automatizada `T3-19` que despliega un servidor web temporal PHP y comprueba con cURL real que la cabecera `Set-Cookie` entrega un nuevo identificador de sesión tras el login exitoso (`cookie_before != cookie_after`) con código de redirección HTTP 302 hacia `/administracion`.

---

## 2. DETALLE DE CORRECCIÓN F3-01 — TRAZABILIDAD OFICIAL DE RF Y RNF

Se contrastó la documentación contra la fuente oficial establecida en `docs/fase-0/matriz-trazabilidad.md` (sección 10 del documento de alcance oficial de la Escuela Oficial Rural Mixta Lo de Fuentes).

### Clasificación Oficial Verificada

| Código | Denominación Oficial | Fase Asignada | Estado en Fase 3 | Tratamiento y Justificación Técnica |
|---|---|---|---|---|
| **RF-01** | **Autenticación.** Permitir iniciar sesión a usuarios administrativos y docentes mediante credenciales. | 3 | **IMPLEMENTADO Y VERIFICADO** | Cubierto mediante formulario `POST /login`, validación con `password_verify()`, verificación de estado `ACTIVO` y mitigación de Session Fixation. Incluye el **mecanismo complementario de cierre de sesión seguro** (`POST /logout` con token CSRF, destrucción total de sesión e invalidación de cookie en cliente). El logout no es un RF independiente, sino una función técnica esencial del ciclo de vida de la autenticación y seguridad de sesión. |
| **RF-02** | **Roles y permisos.** Restringir las funciones de acuerdo con el rol del usuario. | 3 | **IMPLEMENTADO Y VERIFICADO** | Cubierto mediante segregación estricta en backend entre áreas de `ADMINISTRADOR` (`/administracion`) y `DOCENTE` (`/docente`), middleware `RequireRole`, bloqueo con HTTP 403 Forbidden y validación en tiempo real del rol y estado en base de datos física para prevenir manipulación por cliente. |
| **RF-03** | **Recuperación de contraseña.** Recuperar el acceso mediante correo electrónico o enlace/código. | 13 | **PENDIENTE DE IMPLEMENTACIÓN** | Asignado oficialmente a la Fase 13. Se preserva intacto sin implementación anticipada. |
| **RNF-03** | **Seguridad.** Contraseñas no almacenadas en texto plano y acceso controlado por roles. | 3 / 14 | **IMPLEMENTADO Y VERIFICADO** | Hashing criptográfico mediante BCrypt nativo (`PASSWORD_BCRYPT`), flags estrictos de cookies (`HttpOnly`, `SameSite=Lax`, `Secure` condicional), validación CSRF obligatoria y confidencialidad absoluta (cero contraseñas o hashes expuestos en respuestas o logs). |
| **RNF-04** | **Privacidad.** Datos personales accesibles solo a usuarios autorizados. | 14 | **ASIGNADO A FASE 14** | La arquitectura de autenticación (RF-01) y roles (RF-02 / RNF-03) construida en Fase 3 proporciona el soporte técnico sobre el cual se consolidará la privacidad en la Fase 14. |

### Documentos Actualizados
- [matriz-trazabilidad.md](file:///c:/Users/David/Documents/Universidad/2026/Segundo%20semestre/seminario%20de%20tecnologias/ProyectoFinal/docs/fase-0/matriz-trazabilidad.md) (Sección *Actualización controlada de Autenticación y Roles — Fase 3*).
- [informe-cierre-fase-3.md](file:///c:/Users/David/Documents/Universidad/2026/Segundo%20semestre/seminario%20de%20tecnologias/ProyectoFinal/docs/fase-3/informe-cierre-fase-3.md) (Secciones 4, 5, 19, 20, 27 y 28).

---

## 3. DETALLE DE CORRECCIÓN F3-02 — PRUEBA HTTP REAL T3-19

Se implementó la prueba técnica complementaria `T3-19 — Regeneración real de sesión mediante HTTP` dentro de [run_tests_fase3.php](file:///c:/Users/David/Documents/Universidad/2026/Segundo%20semestre/seminario%20de%20tecnologias/ProyectoFinal/tests/run_tests_fase3.php).

### Procedimiento Automatizado Implementado
1. **Detección de puerto efímero:** Método auxiliar `findFreePort(8091, 8099)` para seleccionar un puerto local libre.
2. **Arranque de servidor temporal:** Invocación del servidor web integrado de PHP (`php -S 127.0.0.1:<puerto> -t public`) mediante `proc_open()`.
3. **Sondeo de disponibilidad:** Verificación de conexión socket previa con `fsockopen()` para evitar condiciones de carrera.
4. **Captura cURL inicial (GET /login):**
   - Se procesa `GET http://127.0.0.1:<puerto>/login` con soporte de cookie jar.
   - Se extrae la cookie inicial emitida (`cookie_before`, por ejemplo `"k6eq8e477ghrd2maisetmikgdq"`).
   - Se extrae el token CSRF renderizado en el formulario HTML.
5. **Autenticación cURL (POST /login):**
   - Se envía `POST http://127.0.0.1:<puerto>/login` con `nombre_usuario=admin.demo`, contraseña y token CSRF, adjuntando la cookie previa.
   - Se deshabilita `CURLOPT_FOLLOWLOCATION` para interceptar la respuesta HTTP directa.
6. **Verificaciones de seguridad:**
   - Se verifica código de respuesta HTTP `302 Found`.
   - Se verifica cabecera `Location: /administracion`.
   - Se extrae la nueva cookie entregada en `Set-Cookie` (`cookie_after`, por ejemplo `"1ro3pkcuif7a5n5f9mgoidhd9m"`).
   - Se comprueba que `cookie_before !== cookie_after`.
7. **Limpieza estricta de procesos (`finally`):**
   - En Windows se ejecuta `taskkill /F /T /PID <pid>` y en otros sistemas `proc_terminate()`.
   - Cierre de descriptores de pipes y `proc_close()`.
   - Eliminación del archivo temporal de cookies en disco. Cero procesos huérfanos residuales.

---

## 4. RESULTADOS DE LA SUITE DE PRUEBAS DE FASE 3 (19/19)

Ejecutada mediante:
```powershell
& "C:\xampp\php\php.exe" tests/run_tests_fase3.php
```

```text
============================================================
SUITE DE PRUEBAS AUTOMATIZADAS — FASE 3
Autenticación y Roles — Escuela Oficial Rural Mixta Lo de Fuentes
============================================================

[OK] T3-01: Login válido ADMINISTRADOR | Obtenido: Status 302 -> /administracion, usuario autenticado: admin.demo (Rol: ADMINISTRACION)
[OK] T3-02: Login válido DOCENTE | Obtenido: Status 302 -> /docente, usuario autenticado: docente.demo (Rol: DOCENTE)
[OK] T3-03: Usuario inexistente | Obtenido: Status 401 con mensaje genérico "Credenciales inválidas." sin sesión creada
[OK] T3-04: Contraseña incorrecta | Obtenido: Status 401 con mensaje genérico "Credenciales inválidas." sin revelar existencia de usuario
[OK] T3-05: Usuario inactivo | Obtenido: Status 401: Usuario inactivo rechazado sin iniciar sesión
[OK] T3-06: Regeneración de sesión | Obtenido: Antes: "881713aa30bf8f56a3ef0a6b4ad3feaa" | Después: "d44f6fa76c79e97310d129ab34ad5bef" (Cambió: SÍ)
[OK] T3-07: Ruta privada sin autenticación | Obtenido: Rutas /administracion y /docente redirigen a /login con HTTP 302 sin sesión previa
[OK] T3-08: ADMINISTRADOR accede a ruta administrativa | Obtenido: Status 200: Vista "Área administrativa — sesión válida" renderizada correctamente
[OK] T3-09: DOCENTE accede a ruta docente | Obtenido: Status 200: Vista "Portal docente — sesión válida" renderizada correctamente
[OK] T3-10: DOCENTE intenta ruta administrativa | Obtenido: HTTP 403 Forbidden emitido con vista controlada "Acceso Denegado"
[OK] T3-11: ADMINISTRADOR ante ruta docente (política) | Obtenido: HTTP 403 emitido conforme a la política de mínimo privilegio (área docente reservada a docentes)
[OK] T3-12: Cierre de sesión (Logout) | Obtenido: Logout ejecutó redirección 302, destruyó sesión y bloqueó acceso posterior a /administracion
[OK] T3-13: Manipulación manual de rol | Obtenido: HTTP 403: El backend valida el rol contra base de datos en tiempo real; manipulación client-side rechazada
[OK] T3-14: Protección CSRF en login/logout | Obtenido: POST sin token: 403 | POST con token inválido: 403 | POST con token válido: 302
[OK] T3-15: Usuario desactivado con sesión previa | Obtenido: Usuario desactivado en BD fue bloqueado en la siguiente petición y su sesión fue revocada (HTTP 302 -> /login)
[OK] T3-16: Confidencialidad de contraseñas y hashes | Obtenido: Ningún secreto ni hash BCrypt presente en vistas, logs o respuestas
[OK] T3-17: Regresión completa de Fase 2 | Obtenido: 16/16 pruebas de Fase 2 ejecutadas con 16 OK y 0 fallos
[OK] T3-18: Regresión completa de Fase 1 | Obtenido: 18/18 pruebas de Fase 1 ejecutadas con 18 exitosas y 0 fallidas en BD temporal aislada
[OK] T3-19: Regeneración real de sesión mediante HTTP | Obtenido: Status 302 -> /administracion | Cookie antes: "6me7gnt2da9plrigrv4jticsjg" | Cookie después: "f3710bsbbb4hjof1bhmv86vg8c" (Cambió: SÍ)

============================================================
RESUMEN DE PRUEBAS DE FASE 3
============================================================
Total ejecutadas : 19
Exitosas (OK)    : 19
Fallidas (FALLO) : 0
============================================================
RESULTADO: TODAS LAS PRUEBAS DE FASE 3 SUPERADAS SATISFACTORIAMENTE.
```

---

## 5. RESULTADOS DE REGRESIÓN DE FASE 2 (16/16)

Ejecutada de manera independiente mediante:
```powershell
& "C:\xampp\php\php.exe" tests/run_tests.php
```

```text
============================================================
SUITE DE PRUEBAS AUTOMATIZADAS — FASE 2
Escuela Oficial Rural Mixta Lo de Fuentes
============================================================

[OK] T2-01: Carga de aplicación
[OK] T2-02: Carga de configuración
[OK] T2-03: Conexión PDO
[OK] T2-04: Error controlado de conexión
[OK] T2-05: Ruta registrada válida
[OK] T2-06: Ruta inexistente
[OK] T2-07: Enrutamiento de método GET
[OK] T2-08: Enrutamiento de método POST
[OK] T2-09: Renderizado con layout común
[OK] T2-10: Helper de escape HTML contra XSS
[OK] T2-11: Aislamiento de secretos y .env
[OK] T2-12: Prueba de regresión de Fase 1
[OK] T2-13: Aislamiento de rutas técnicas en producción
[OK] T2-14: Protección perimetral de archivos internos y logs
[OK] T2-15: Validación estricta de configuración de base de datos
[OK] T2-16: Regresión funcional de base de datos de Fase 1

============================================================
RESUMEN DE PRUEBAS DE FASE 2
============================================================
Total ejecutadas : 16
Exitosas (OK)    : 16
Fallidas (FALLO) : 0
============================================================
RESULTADO: TODAS LAS PRUEBAS DE FASE 2 SUPERADAS SATISFACTORIAMENTE.
```

---

## 6. REGRESIÓN DE FASE 1 (BASE DE DATOS RELACIONAL)

Verificada tanto en `T3-18` como en `T2-16`:
- Creación de base de datos temporal aislada.
- Aplicación de [database/schema.sql](file:///c:/Users/David/Documents/Universidad/2026/Segundo%20semestre/seminario%20de%20tecnologias/ProyectoFinal/database/schema.sql) y [database/seed.sql](file:///c:/Users/David/Documents/Universidad/2026/Segundo%20semestre/seminario%20de%20tecnologias/ProyectoFinal/database/seed.sql).
- Ejecución de las 18 pruebas relacionales de [database/tests/validacion.sql](file:///c:/Users/David/Documents/Universidad/2026/Segundo%20semestre/seminario%20de%20tecnologias/ProyectoFinal/database/tests/validacion.sql).
- Resultado: **18/18 OK, 0 fallos**.
- Eliminación de la base de datos temporal tras la prueba.
- Los scripts originales de base de datos se mantuvieron 100% inalterados.

---

## 7. COMMITS LOCALES REGISTRADOS

En estricto cumplimiento de las directivas operativas:
- **No se realizó ningún `git push` a repositorios remotos.**
- Los commits se registraron localmente en la rama `main`:

```text
7488d17 docs(fase-3): corregir trazabilidad oficial de RF y RNF y documentar T3-19
efeb148 test(fase-3): agregar validacion http real de session fixation T3-19
dd611ec docs(fase-3): documentar arquitectura, seguridad, pruebas y trazabilidad
894859d test(fase-3): implementar suite automatizada de pruebas T3-01 a T3-18
0961211 feat(fase-3): implementar servicio de autenticacion, middlewares, controladores y vistas
256c65c feat(fase-3): implementar componentes de sesion, csrf, modelos y repositorios
```

Estado del árbol de trabajo:
`git status`: **working tree clean**.

---

## 8. CONCLUSIÓN Y DECLARACIÓN DE DETENCIÓN

La Fase 3 — Autenticación y Roles ha sido completamente ajustada con el máximo rigor técnico, documental y de auditoría:
- Trazabilidad corregida conforme a la fuente oficial literal.
- Prueba técnica de Session Fixation por HTTP real verificada (`T3-19`).
- 19 de 19 pruebas de Fase 3 superadas exitosamente.
- Regresiones de Fase 1 y Fase 2 100% aprobadas.
- Repositorio local limpio.

```text
=============================================================================
FASE 3 DETENIDA A LA ESPERA DE REVISIÓN EXTERNA Y AUTORIZACIÓN DEL USUARIO.
NO SE HA INICIADO LA FASE 4.
=============================================================================
```
