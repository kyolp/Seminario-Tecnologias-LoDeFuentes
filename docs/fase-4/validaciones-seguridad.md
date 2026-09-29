# Validaciones y Seguridad — Fase 4

**Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes**  
**Fase 4: Administración Básica**  
**Responsable Técnico:** Antigravity — Desarrollador Principal  
**Fecha:** Septiembre 2026  

---

## 1. Control de Acceso Basado en Roles (RBAC)

El acceso a las rutas administrativas está estrictamente restringido al rol `ADMINISTRADOR` (y su sinónimo institucional `ADMINISTRACION`):
1. **Verificación de Autenticación (`Authenticate`):** Intercepta la petición y valida la existencia de una sesión válida. Si el usuario no ha iniciado sesión, emite redirección inmediata `HTTP 302` hacia `/login`.
2. **Autorización Estricta (`RequireRole('ADMINISTRADOR')`):** Verifica que el rol del usuario corresponda a la administración. Si un docente autenticado (`docente.demo`) intenta acceder a `/administracion` o a cualquiera de sus submódulos, el sistema bloquea el acceso emitiendo `HTTP 403 Forbidden` con una vista institucional de error amigable (`views/errors/403.php`).
3. **Validación en Tiempo Real:** El middleware comprueba el estado activo del usuario en la base de datos en cada petición, invalidando sesiones de usuarios revocados inmediatamente.

---

## 2. Mitigación contra Falsificación de Peticiones en Sitios Cruzados (CSRF)

1. **Tokens Criptográficos por Sesión:** Cada formulario emitido en los módulos administrativos incluye un campo oculto generado mediante `csrf_field()` con el token emitido por `Csrf::getToken()`.
2. **Middleware `VerifyCsrfToken`:** Se aplica de forma obligatoria a todas las rutas `POST` de mutación (crear, editar, toggle de estado, eliminar). Peticiones sin token o con tokens manipulados son rechazadas con `HTTP 403 Forbidden`, impidiendo que terceros ejecuten acciones en nombre del administrador.

---

## 3. Prevención de Cross-Site Scripting (XSS)

1. **Escapado Automático en Vistas:** Todas las vistas administrativas (`views/admin/**/*.php`) renderizan datos ingresados por usuarios utilizando exclusivamente el helper `e($string)`:
   ```php
   htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
   ```
2. **Validación de Inyección:** La suite de pruebas verifica (`T4-21`) que inyecciones de payloads como `<script>alert("XSS")</script>` sean neutralizadas y transformadas en entidades seguras (`&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;`), imposibilitando la ejecución de scripts en el navegador del cliente.

---

## 4. Protección contra Asignación Masiva (Mass Assignment)

1. **Mapeo Explícito (Whitelist):** Ningún controlador pasa arreglos `$_POST` o `$request->all()` directamente a los repositorios o consultas de base de datos.
2. **Aislamiento en Servicios:** Cada método de servicio extrae y sanitiza únicamente los campos autorizados para el esquema respectivo:
   ```php
   $input = [
       'codigo' => (string)$request->input('codigo', ''),
       'nombre' => (string)$request->input('nombre', ''),
       'capacidad' => $request->input('capacidad', ''),
       'habilitada' => (int)$request->input('habilitada', 1),
   ];
   ```
3. **Campos Espurios Descartados:** Pruebas automatizadas (`T4-22`) confirman que campos maliciosos como `is_admin`, `rol => 'SUPERADMIN'` o `password_hash` inyectados en la solicitud son ignorados y no alteran la base de datos.

---

## 5. Prevención de Inyección SQL

1. **Consultas Preparadas con PDO:** Cero concatenación de cadenas SQL en toda la aplicación. Todas las interacciones con MySQL en `app/Repositories/` emplean sentencias preparadas (`$db->prepare()`) con enlaces fuertemente tipados (`PDO::PARAM_INT`, `PDO::PARAM_STR`).
2. **Escapado de Parámetros de Búsqueda:** Búsquedas textuales sanitizan comodines `%` y pasan los términos de consulta como valores enlazados seguros.
