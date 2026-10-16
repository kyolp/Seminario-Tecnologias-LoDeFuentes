# Registro de Pruebas Automatizadas — Fase 13
## Sistema Web — Escuela Oficial Rural Mixta Lo de Fuentes

**Módulo:** Recuperación de Contraseña (RF-03)  
**Script de Ejecución:** `tests/run_tests_fase13.php`  
**Total de Pruebas:** 30  
**Exitosas (OK):** 30  
**Fallidas (FALLO):** 0  
**Estado:** 100% GREEN (Aprobada)  

---

### Tabla Detallada de Pruebas

| Código | Requisito | Descripción de la Prueba | Resultado Obtenido | Estado |
|---|---|---|---|:---:|
| **T13-01** | Trazabilidad | Trazabilidad documental oficial: RF-03 en Fase 13; Fases posteriores pendientes | RF-03 asignado e implementado en Fase 13; Fases 14, 15 y 16 en estado PENDIENTE | **OK** |
| **T13-02** | RF-03 | Solicitud válida de recuperación con correo registrado existente genera token en BD | Token generado en `tokens_recuperacion` con estado ACTIVO para el usuario | **OK** |
| **T13-03** | RF-03 / RNF-04 | Solicitud con correo inexistente devuelve mensaje neutral idéntico (anti-enumeración) | Mismo mensaje de confirmación, cero tokens generados, mitigación de enumeración | **OK** |
| **T13-04** | RF-03 / RNF-04 | Solicitud con cuenta inactiva devuelve mensaje neutral idéntico sin generar token activo | Mismo mensaje de confirmación, cero tokens para usuario INACTIVO | **OK** |
| **T13-05** | RF-03 / RNF-03 | Token generado es criptográficamente seguro (longitud 64 hex, entropía random_bytes) | Cadena hexadecimal de 64 caracteres generada con CSPRNG seguro (32 bytes) | **OK** |
| **T13-06** | RF-03 / RNF-03 | Almacenamiento seguro: token se persiste exclusivamente como hash SHA-256 en token_hash | Token en texto claro nunca almacenado en BD; hash SHA-256 coincide exactamente | **OK** |
| **T13-07** | RF-03 | Vigencia temporal estricta: fecha_expiracion fijada a 60 minutos posteriores a creación | Expiración fijada exactamente a 3600 segundos (60 minutos) tras la creación | **OK** |
| **T13-08** | RF-03 | Validación exitosa de token activo y vigente | Token recién emitido validado y retornado con datos del usuario titular | **OK** |
| **T13-09** | RF-03 | Rechazo de token inexistente o manipulado | Intento con token desconocido rechazado con mensaje de error controlado | **OK** |
| **T13-10** | RF-03 | Rechazo de token con formato inválido (no hex o longitud distinta a 64 chars) | Caracteres no hexadecimales y longitudes arbitrarias rechazados de inmediato | **OK** |
| **T13-11** | RF-03 | Rechazo de token vencido (fecha_expiracion en el pasado) | Token con fecha de expiración expirada rechazado como no válido | **OK** |
| **T13-12** | RF-03 | Solicitud nueva revoca tokens activos previos del mismo usuario (estado = REVOCADO) | Token anterior marcado como REVOCADO al generar una nueva solicitud | **OK** |
| **T13-13** | RF-03 | Restablecimiento exitoso de contraseña con token válido y confirmación coincidente | Contraseña cambiada con éxito y confirmación retornada | **OK** |
| **T13-14** | RF-03 | Inmutabilidad de uso: token utilizado pasa a estado = USADO con fecha_uso no nula | Estado pasa a USADO y fecha_uso registrada con marca de tiempo del servidor | **OK** |
| **T13-15** | RF-03 | Token no reutilizable: un token USADO es rechazado en intentos posteriores | Segundo intento con el mismo token rechazado como inválido o expirado | **OK** |
| **T13-16** | RF-03 / RNF-03 | Hash de contraseña en BD se actualiza con BCrypt nativo (password_verify OK con nueva clave) | `password_verify` exitoso contra el hash actualizado en tabla `usuarios` | **OK** |
| **T13-17** | RF-03 / RF-01 | Inicio de sesión exitoso con la nueva contraseña (AuthService::attempt) | Sesión iniciada con éxito utilizando la nueva contraseña configurada | **OK** |
| **T13-18** | RF-03 / RF-01 | Rechazo de inicio de sesión con la contraseña anterior tras el restablecimiento | Intento con credencial antigua denegado por AuthService (autenticación fallida) | **OK** |
| **T13-19** | RF-03 | Rechazo de restablecimiento si la confirmación de contraseña no coincide | Rechazo con excepción de validación al no coincidir clave y confirmación | **OK** |
| **T13-20** | RF-03 / RNF-03 | Validación de longitud mínima de contraseña (rechazo de contraseñas < 8 caracteres) | Claves cortas (< 8 caracteres) rechazadas con mensaje explicativo | **OK** |
| **T13-21** | Seguridad | Mitigación de Inyección SQL en parámetros de recuperación y restablecimiento | Sentencias preparadas PDO neutralizan payloads maliciosos en correo y tokens | **OK** |
| **T13-22** | Seguridad | Prevención de Cross-Site Scripting (XSS) mediante escape e() en vistas auth | Salidas sanitizadas con helper de escape HTML institucional en vistas de auth | **OK** |
| **T13-23** | Seguridad | Protección CSRF obligatoria en POST /recuperar-contrasena y /restablecer-contrasena | Peticiones POST sin token CSRF válido rechazadas con HTTP 403 Forbidden | **OK** |
| **T13-24** | Control de Acceso | Intento de acceso directo a /restablecer-contrasena sin token devuelve HTTP 400 | Acceso GET sin parámetro de token bloqueado con HTTP 400 Bad Request | **OK** |
| **T13-25** | Control de Acceso | Redirección de usuario ya autenticado ante rutas de recuperación hacia su panel | Usuario con sesión activa redirigido a su panel correspondiente (admin/docente) | **OK** |
| **T13-26** | Base de Datos | Reutilización de tokens_recuperacion sin alteraciones a schema.sql | Cero alteraciones al esquema: se reutiliza la estructura creada en Fase 1 | **OK** |
| **T13-27** | Concurrencia | Concurrencia y atomicidad: Bloqueo pesimista FOR UPDATE previene condiciones de carrera | Transacción atómica con `SELECT ... FOR UPDATE` previene doble uso simultáneo | **OK** |
| **T13-28** | HTTP Real | Flujo HTTP real completo: Solicitud -> Token -> Formulario -> Cambio -> Login | Peticiones HTTP reales contra servidor embebido en puerto 8101 aprobadas | **OK** |
| **T13-29** | HTTP Real | Flujo HTTP real de casos anómalos: Token vencido/inválido (400) y CSRF ausente (403) | Rechazos HTTP 400 y 403 verificados con cliente HTTP real y servidor embebido | **OK** |
| **T13-30** | No Contaminación | Gestión de fixtures: Cero registros temporales permanentes tras ejecutar la suite | Todos los usuarios y tokens creados por la suite son eliminados al finalizar | **OK** |

---

### Resumen de Ejecución
- **30 pruebas ejecutadas, 30 aprobadas (100%).**
- Pruebas HTTP reales ejecutadas mediante servidor web PHP embebido en puertos temporales (8101 y 8102) y cliente cURL.
- **Alcance de T13-30:** La prueba verifica exclusivamente que la suite de Fase 13 no deje datos residuales ni fixtures permanentes creados durante su propia ejecución (usuarios `admin_f13`, `docente_f13`, `inactivo_f13` y sus tokens correspondientes). No realiza modificaciones sobre datos históricos del sistema, cuya depuración integral permanece reservada para la Fase 16.
