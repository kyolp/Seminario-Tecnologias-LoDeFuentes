# Decisiones Técnicas — Fase 13
## Sistema Web — Escuela Oficial Rural Mixta Lo de Fuentes

**Módulo:** Recuperación de Contraseña (RF-03)  
**Fecha:** 15 de octubre de 2026  
**Línea Base Previa:** DT-72 (Fase 12)  

---

### DT-73: Arquitectura en Capas para Recuperación de Contraseña y Almacenamiento Criptográfico de Tokens
- **Contexto:** Se requiere implementar el flujo de recuperación de contraseña institucional (RF-03), permitiendo restablecer el acceso a cuentas de usuarios administrativos y docentes mediante un enlace seguro de recuperación.
- **Decisión:**
  1. Mantener rigurosamente la arquitectura en capas institucional del proyecto:
     - `PasswordRecoveryController`: Gestiona las peticiones HTTP (GET/POST para solicitud y restablecimiento), valida tokens CSRF, redirige a usuarios ya autenticados y delega toda la lógica de negocio al servicio.
     - `PasswordRecoveryService`: Orquesta la generación aleatoria de tokens con CSPRNG (`random_bytes(32)`), el cómputo de su hash SHA-256 (`hash('sha256', $token)`), la expiración a 60 minutos, la revocación de tokens anteriores, la validación de contraseñas nuevas ($\ge 8$ caracteres y confirmación exacta) y el cifrado con BCrypt nativo (`password_hash($nuevaClave, PASSWORD_BCRYPT)`).
     - `TokenRecuperacionRepository` (`TokenRecuperacionRepositoryInterface`): Encapsula todas las operaciones con la tabla preexistente `tokens_recuperacion` utilizando sentencias preparadas PDO.
     - `UserRepository` (`UserRepositoryInterface`): Se complementa con `findByEmail()` y `updatePassword()`, manteniendo la encapsulación de la tabla `usuarios`.
     - Vistas PHP (`views/auth/recuperar-contrasena.php` y `views/auth/restablecer-contrasena.php`): Interfaz escolarizada, accesible y sanitizada contra XSS con `e()`.
  2. No almacenar jamás los tokens en texto claro en la base de datos; persistir únicamente su valor hash SHA-256 (`token_hash`), de manera análoga al almacenamiento de contraseñas.
- **Justificación:** Cumple con el principio de separación de responsabilidades (RNF-08), seguridad de credenciales (RNF-03) y desacoplamiento para pruebas unitarias e integración.

---

### DT-74: Mitigación de Enumeración de Usuarios mediante Mensaje Neutral Indistinguible
- **Contexto:** La solicitud de recuperación de contraseña recibe una dirección de correo electrónico institucional. Si el sistema responde de forma distinta cuando el correo existe frente a cuando no existe o está inactivo, un atacante externo podría enumerar los correos registrados en la institución.
- **Decisión:**
  1. Responder con un mensaje neutral idéntico ante cualquier solicitud: *"Si el correo ingresado se encuentra registrado en el sistema y activo, hemos procesado un enlace seguro para restablecer su contraseña."*
  2. Si el correo no existe en la base de datos o pertenece a un usuario en estado `INACTIVO`, no generar ningún registro de token ni disparar flujos adicionales, pero devolver al usuario la misma respuesta exitosa.
  3. Prevenir fugas de información por canales laterales (timing attacks) manteniendo flujos de ejecución computacionalmente consistentes.
- **Justificación:** Protección de la privacidad institucional y confidencialidad del personal docente y administrativo, mitigando vectores comunes de enumeración y recolección de identidades.

---

### DT-75: Ciclo de Vida, Expiración y Concurrencia de Tokens mediante Bloqueo Pesimista
- **Contexto:** Los tokens de recuperación deben ser de un solo uso, contar con una vigencia temporal acotada y ser resistentes a condiciones de carrera en entornos concurrentes.
- **Decisión:**
  1. **Vigencia temporal:** Cada token posee una expiración fija de exactamente 60 minutos desde su momento de emisión (`fecha_expiracion = NOW() + INTERVAL 60 MINUTE`).
  2. **Unicidad activa:** Al solicitar un nuevo enlace de recuperación, cualquier token previo que permanezca en estado `ACTIVO` para dicho usuario es revocado automáticamente (`estado = 'REVOCADO'`).
  3. **Bloqueo pesimista en base de datos:** Durante la fase de restablecimiento de contraseña, el token y el usuario se consultan bajo una transacción relacional con `SELECT ... FOR UPDATE` en `TokenRecuperacionRepository::findForUpdate()`.
  4. **Un solo uso e inmutabilidad:** Inmediatamente tras actualizar la contraseña con BCrypt, el token transiciona de forma atómica a `estado = 'USADO'` y se registra `fecha_uso = NOW()`. Un token usado o revocado es rechazado de inmediato.
- **Justificación:** Garantiza integridad transaccional (RNF-05), previene condiciones de carrera o reutilización fraudulenta de enlaces en peticiones simultáneas, y acota la ventana de riesgo ante tokens no utilizados.

---

### DT-76: Estrategia de Entorno Académico/Local para Visualización y Registro del Enlace de Recuperación
- **Contexto:** En el entorno de desarrollo local y académico no se cuenta con un servidor SMTP transaccional configurado o con salida a Internet pública, pero se requiere permitir la verificación interactiva y pruebas de extremo a extremo sin interrumpir la experiencia de usuario.
- **Decisión:**
  1. El enlace completo de recuperación con el token generado se construye formalmente a partir de la configuración del sistema (`App::url('/restablecer-contrasena?token=' . $tokenClaro)`).
  2. En el entorno local (`APP_ENV=local` o `testing`), cuando la aplicación procesa una solicitud válida, almacena de forma temporal y controlada el enlace en la sesión flash (`_recovery_link_demo`) y lo registra en el log del sistema, permitiendo que la interfaz muestre una caja de información académica destacando el enlace generado para fines de demostración y pruebas.
  3. En entornos de producción (`APP_ENV=production`), esta ayuda visual se desactiva estrictamente, delegándose al canal de transporte correspondiente.
- **Justificación:** Facilita la evaluación docente y funcional del proyecto en laboratorio y localmente, respetando las limitaciones de conectividad (LIM-DOC-02 y LIM-DOC-04) sin comprometer la seguridad en despliegues reales.
