# Decisiones Técnicas — Fase 3

Este documento registra las decisiones técnicas y arquitectónicas adoptadas durante el desarrollo de la Fase 3 (**Autenticación y Roles**) para el prototipo de la **Escuela Oficial Rural Mixta Lo de Fuentes**.

---

## DT-19 — Pipeline de Middlewares encadenados en el Router

- **Tema:** Ejecución de filtros previos de autenticación, rol y CSRF antes de los controladores.
- **Problema:** En Fase 2 el router registraba arreglos de middlewares pero no los ejecutaba. Se requería una solución nativa sin frameworks externos.
- **Información disponible:** La interfaz `App\Middleware\MiddlewareInterface` define el método `handle(Request $request, callable $next): Response`.
- **Decisión:** Implementar en `Router::dispatch()` una cadena basada en `array_reduce` y clausuras anidadas (patrón Onion / Pipeline), que encadena los middlewares registrados y culmina en la acción del controlador.
- **Justificación:** Respeta la arquitectura nativa, desacopla la seguridad de los controladores y permite reutilizar middlewares en fases futuras.
- **Impacto:** Cualquier ruta puede protegerse simplemente adjuntando los middlewares correspondientes.

---

## DT-20 — Revalidación autoritativa en tiempo real de usuario y rol en Base de Datos

- **Tema:** Prevención de sesiones zombies y control de usuario/rol activo (Reglas 14 y 15).
- **Problema:** Almacenar el rol o estado solo en `$_SESSION` permite que un usuario desactivado o con cambio de rol continúe operando si su sesión ya estaba iniciada.
- **Información disponible:** La base de datos es la única fuente de verdad sobre el estado (`ACTIVO`, `INACTIVO`, `BLOQUEADO`) y el rol del usuario.
- **Decisión:** En `AuthService::user()`, cada vez que se requiere el usuario autenticado, se consulta a MySQL mediante `UserRepository::findById()`. Si el usuario no existe o su estado no es `ACTIVO`, se ejecuta `logout()` inmediatamente, se destruye la sesión y se retorna `null`. Además, el rol se sincroniza siempre con el valor actual de la base de datos.
- **Justificación:** Cumple estrictamente con las reglas 14 y 15 sin introducir sobreingeniería (cero Redis o demonios complejos).
- **Impacto:** La desactivación de un usuario en base de datos toma efecto inmediatamente en su siguiente petición HTTP.

---

## DT-21 — Configuración condicional de cookies de sesión según el entorno

- **Tema:** Atributo `secure` en cookies de sesión.
- **Problema:** Si se exige `secure = true` indiscriminadamente en desarrollo local bajo `http://localhost`, los navegadores rechazan la cookie y las sesiones no funcionan.
- **Información disponible:** En desarrollo se utiliza HTTP local; en producción se debe emplear HTTPS.
- **Decisión:** En `Session::start()`, el flag `secure` se evalúa dinámicamente:
  `$secureCookie = $isHttps && (Config::get('app.env') === 'production')`.
- **Justificación:** Evita romper el entorno local de desarrollo mientras garantiza la máxima protección cuando el sistema sea desplegado bajo HTTPS en producción.
- **Impacto:** Sesiones plenamente funcionales en `localhost:8088` y blindadas en producción.

---

## DT-22 — Normalización y equivalencia de roles institucionales

- **Tema:** Mapeo entre el rol de base de datos (`ADMINISTRACION`) y el requerimiento funcional (`ADMINISTRADOR`).
- **Problema:** El seed relacional original de Fase 1 contiene el valor `'ADMINISTRACION'` en la tabla `roles`, mientras que las especificaciones de Fase 3 y la convención de roles se refieren a `'ADMINISTRADOR'`.
- **Información disponible:** El esquema relacional y las semillas de Fase 1 no deben alterarse arbitrariamente.
- **Decisión:** En la entidad `User::hasRole()`, se establece equivalencia sinónima entre `'ADMINISTRADOR'` y `'ADMINISTRACION'`.
- **Justificación:** Preserva la integridad estricta del modelo y scripts de Fase 1 sin generar discrepancias conceptuales ni requerir migraciones innecesarias.
- **Impacto:** Ambos términos son reconocidos de forma transparente y segura en las comprobaciones de autorización.

---

## DT-23 — Política de Mínimo Privilegio para Rutas Privadas (T3-11)

- **Tema:** Comportamiento ante un usuario autenticado que accede a un área ajena a su perfil funcional.
- **Problema:** Determinar si un `ADMINISTRADOR` debe o no acceder libremente al portal exclusivo de docentes (`/docente`).
- **Información disponible:** El portal docente está concebido para profesores con asignaciones de grado activas. Los administradores gestionan el establecimiento desde `/administracion`.
- **Decisión:** Aplicar el principio de menor privilegio:
  - `/administracion` requiere rol `ADMINISTRADOR`.
  - `/docente` requiere rol `DOCENTE`.
  - Un administrador que intente acceder a `/docente` recibe HTTP 403 Forbidden.
- **Justificación:** Garantiza la estricta segregación de funciones institucional y previene inconsistencias de datos (ej. un administrador intentando marcar asistencia docente sin tener asignación académica).
- **Impacto:** Respuesta HTTP 403 clara y controlada ante accesos cruzados.

---

## DT-24 — Protección CSRF nativa con tokens de 32 bytes

- **Tema:** Mitigación de peticiones mutantes falsificadas en formularios `POST`.
- **Problema:** Solicitudes como `/login` y `/logout` no deben procesarse si provienen de sitios de origen cruzado.
- **Información disponible:** PHP provee `random_bytes()` criptográficamente seguro y `hash_equals()` para validación resistente a ataques por tiempo.
- **Decisión:** Implementar la clase `App\Core\Csrf` y el middleware `VerifyCsrfToken`, vinculando un token de 32 bytes a la sesión y exigiendo su envío en formularios POST mediante el helper `csrf_field()`.
- **Justificación:** Provee una capa defensiva robusta, estándar y reutilizable para todos los formularios que se incorporen en fases posteriores.
- **Impacto:** Formularios seguros con rechazo automático (HTTP 403) ante ausencia o alteración del token.
