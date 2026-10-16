# INFORME DE CIERRE FORMAL — FASE 13
## SISTEMA WEB ESCUELA OFICIAL RURAL MIXTA LO DE FUENTES

**Módulo:** Recuperación de Contraseña (RF-03)  
**Rol:** Antigravity — Desarrollador Principal  
**Fecha:** 15 de octubre de 2026  
**Resultado de Fase:** COMPLETADA, VERIFICADA Y DOCUMENTALMENTE CONFORME  

---

## 1. Verificación Literal de Requisitos Oficiales (Documento de Alcance)

Se consultó directamente el archivo oficial:
`Documento_Alcance_Escuela_Lo_de_Fuentes_Andersson_Godoy (1).docx` (Sección 10.1).

### RF-03
- **Nombre literal:** `Recuperación de contraseña`
- **Descripción literal:** `El sistema deberá permitir recuperar el acceso mediante un mecanismo basado en correo electrónico o enlace/código.`
- **Actores:** Docentes y Personal Administrativo.
- **Alcance funcional:** Permitir restablecer el acceso a las credenciales institucionales de forma segura sin exponer secretos ni posibilitar la enumeración de cuentas.

---

## 2. Componentes Técnicos Implementados en Fase 13

### 2.1 Backend en Tres Capas y Dominio
- `app/Models/TokenRecuperacion.php`: Entidad de dominio que modela el ciclo de vida del token (`ESTADO_ACTIVO`, `ESTADO_USADO`, `ESTADO_REVOCADO`), constantes de expiración (60 minutos) y validaciones de vigencia (`esValido()`).
- `app/Repositories/TokenRecuperacionRepositoryInterface.php`: Contrato desacoplado para persistencia y consulta de tokens (`crear`, `findByTokenHash`, `findForUpdate`, `marcarComoUsado`, `revocarTokensActivosDeUsuario`, `limpiarTokensUsuario`).
- `app/Repositories/TokenRecuperacionRepository.php`: Implementación en PDO con sentencias preparadas, tipado estricto y soporte de bloqueo pesimista `SELECT ... FOR UPDATE`.
- `app/Repositories/UserRepositoryInterface.php` y `UserRepository.php`: Ampliación no invasiva para incluir `findByEmail(string $email): ?User` y `updatePassword(int $userId, string $newPasswordHash): bool`.
- `app/Services/PasswordRecoveryService.php`: Capa de negocio que implementa la generación de tokens CSPRNG (`random_bytes(32)`), cálculo hash SHA-256, revocación de tokens previos, control de anti-enumeración de usuarios, expiración a 60 minutos, validación de contraseñas seguras ($\ge 8$ caracteres) y actualización atómica bajo transacción.
- `app/Controllers/PasswordRecoveryController.php`: Controlador web que gestiona los formularios GET/POST para `/recuperar-contrasena` y `/restablecer-contrasena`, verificación CSRF, redirección de usuarios ya autenticados y manejo de errores con mensajes comprensibles.
- `app/Core/App.php`: Registro formal de rutas públicas seguras para recuperación de contraseñas.

### 2.2 Vistas e Integración Visual
- `views/auth/recuperar-contrasena.php`: Formulario institucional para ingresar el correo electrónico, aviso de privacidad, mensajes de alerta y soporte de entorno local para demostración académica.
- `views/auth/restablecer-contrasena.php`: Formulario para nueva contraseña y confirmación, indicador de seguridad, botones para alternar visibilidad de contraseña y validación accesible.
- `views/auth/login.php`: Enlace visible hacia el flujo de recuperación de contraseña ("¿Olvidó su contraseña?").

---

## 3. Políticas de Seguridad y Privacidad

1. **Mitigación de Enumeración de Cuentas (RNF-04):** Ante solicitudes con correos inexistentes o de usuarios inactivos, el sistema responde con el mismo mensaje neutral y afirmativo que para correos válidos, sin registrar tokens ni revelar la existencia de la cuenta.
2. **Criptografía Segura de Tokens (RNF-03):** Se emplean 32 bytes de entropía criptográfica (`random_bytes`), representados en 64 caracteres hexadecimales. El token en texto claro jamás se almacena en la base de datos; únicamente se almacena su hash SHA-256.
3. **Expiración y Revocación Temporal:** Los tokens expiran estrictamente a los 60 minutos de su creación. Si el usuario realiza una nueva solicitud, los tokens previos aún activos se marcan automáticamente como `REVOCADO`.
4. **Un Solo Uso y Bloqueo Pesimista:** Durante el restablecimiento, el token es consultado con `SELECT ... FOR UPDATE` dentro de una transacción PDO. Tras la actualización de la contraseña, el token se transiciona a `USADO` con la fecha y hora de uso (`CURRENT_TIMESTAMP`), impidiendo su reutilización en cualquier intento posterior.
5. **Cifrado de Contraseñas:** Las nuevas contraseñas se almacenan protegidas exclusivamente mediante BCrypt nativo (`PASSWORD_BCRYPT`).

---

## 4. Estado de la Base de Datos y Reserva de Fase 16

- **Esquema relacional (`database/schema.sql`):** **INTACTO (0 modificaciones).**
  - La Fase 13 reutilizó al 100% la tabla relacional `tokens_recuperacion` creada e indexada desde la Fase 1.
- **Depuración global de la base de datos:** **NO realizada en Fase 13.**
  - La suite de Fase 13 limpia rigurosamente sus propios datos temporales (T13-30).
  - La depuración integral de la base de datos y homogeneización de fixtures históricos está reservada formal y explícitamente para la **Fase 16**.

---

## 5. Resumen de Decisiones Técnicas (DT-73 a DT-76)

| Código | Título | Resumen de Decisión |
|---|---|---|
| **DT-73** | Arquitectura en Capas para Recuperación de Contraseña y Almacenamiento Criptográfico | Controller -> Service -> Repository; generación de tokens con CSPRNG y persistencia exclusiva de hash SHA-256; contraseñas con BCrypt. |
| **DT-74** | Mitigación de Enumeración de Usuarios mediante Mensaje Neutral Indistinguible | Respuesta neutra idéntica tanto para correos registrados como inexistentes o inactivos; protección contra recolección de identidades. |
| **DT-75** | Ciclo de Vida, Expiración y Concurrencia de Tokens mediante Bloqueo Pesimista | Vigencia fija de 60 minutos; revocación automática de tokens activos anteriores; transacción atómica con `SELECT ... FOR UPDATE` y transición inmutable a `USADO`. |
| **DT-76** | Estrategia de Entorno Académico/Local para Visualización y Registro de Enlace | En local/testing, visualización controlada en sesión flash y logs para facilitar la evaluación sin requerir servidor SMTP público. |

---

## 6. Resultados de Pruebas y Regresión Acumulada

- **Suite de Fase 13 (`tests/run_tests_fase13.php`):** 30 / 30 aprobadas (100% GREEN).
  - Incluye verificación en servidor HTTP real embebido (puertos 8101 y 8102) con cURL.
- **Regresión Histórica Acumulada (Fases 1 a 13):**
  - Fase 3 (Autenticación y Roles): 19 / 19 OK
  - Fase 6 (Inscripciones Web y Cupos): 30 / 30 OK
  - Fase 8 (Asistencia y Geolocalización): 40 / 40 OK
  - Fase 9 (Permisos Docentes): 46 / 46 OK
  - Fase 10 (Notificaciones Administrativas): 27 / 27 OK
  - Fase 11 (Comprobante PDF): 29 / 29 OK
  - Fase 12 (Reportes Institucionales): 30 / 30 OK
  - Fase 13 (Recuperación de Contraseña): 30 / 30 OK
  - **TOTAL PROYECTO: 366 / 366 pruebas aprobadas (100% GREEN).**

---

## 7. Control de Alcance

- **Fase 14 (Seguridad, Privacidad y Hardening Global):** NO iniciada.
- **Fase 15 (Pruebas Integrales y Compatibilidad):** NO iniciada.
- **Fase 16 (Limpieza de BD y Entrega Final):** NO iniciada.
- **Git Push:** Cero ejecuciones de git push.
