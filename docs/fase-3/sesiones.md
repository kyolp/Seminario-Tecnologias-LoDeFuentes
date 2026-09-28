# Gestión de Sesiones Seguras — Fase 3

## 1. Configuración de Sesión y Directivas de Seguridad

La clase `App\Core\Session` centraliza el manejo de las sesiones PHP nativas aplicando las mejores prácticas de la industria:

| Directiva | Valor configurado | Propósito de seguridad |
|---|---|---|
| `lifetime` | `0` | La cookie de sesión expira automáticamente al cerrar el navegador web. |
| `path` | `/` | Alcance exclusivo a la raíz de la aplicación. |
| `domain` | `''` | Sin comodines de subdominio no autorizados. |
| `httponly` | `true` | **Mitigación XSS:** La cookie no es accesible a través de JavaScript (`document.cookie`). |
| `samesite` | `Lax` | **Mitigación CSRF:** El navegador restringe el envío de la cookie en peticiones de origen cruzado. |
| `secure` | Condicional | `true` en producción bajo HTTPS; `false` en desarrollo local HTTP para garantizar la funcionalidad sin romper el entorno. |
| `use_strict_mode` | `1` | Rechaza identificadores de sesión inicializados por el cliente no generados por el servidor. |
| `use_only_cookies` | `1` | Deshabilita el paso de session ID por parámetros en la URL (evita fuga en referrers o logs web). |

---

## 2. Diferencia entre Entornos (Desarrollo vs. Producción)

- **Entorno de Desarrollo (`APP_ENV=development` o `local`):**
  - La conexión se realiza usualmente bajo `http://localhost` o IP local sin certificado SSL/TLS.
  - La directiva `secure` permanece en `false` para permitir que el navegador almacene y transmita la cookie de sesión sin errores.
- **Entorno de Producción (`APP_ENV=production`):**
  - Cuando se ejecuta bajo HTTPS (`$_SERVER['HTTPS'] === 'on'` o puerto 443), la directiva `secure` se activa obligatoriamente (`true`).
  - Esto garantiza que las cookies de sesión viajen exclusivamente a través de canales cifrados TLS.

---

## 3. Mitigación de Session Fixation

Inmediatamente después de que un usuario valida sus credenciales en `AuthService::attempt()`, el sistema ejecuta:

```php
Session::regenerate(true);
```

Este procedimiento:
1. Genera un nuevo identificador de sesión criptográfico.
2. Invalida y elimina el identificador previo en el almacenamiento de sesiones.
3. Transmite la nueva cabecera `Set-Cookie` al cliente.

Esto garantiza que cualquier identificador de sesión conocido previamente por un atacante quede inutilizado, satisfaciendo la prueba obligatoria `T3-06`.

---

## 4. Estrategia de Revalidación en Tiempo Real (Reglas 14 y 15)

Para evitar que un usuario conserve privilegios de sesión si su estado o rol fueron modificados en la base de datos física:

1. `Session` únicamente almacena el ID del usuario (`auth_user_id`).
2. Cada llamada a `auth()->user()` consulta de forma autoritativa `UserRepository::findById($userId)` en la base de datos.
3. Si el usuario:
   - Fue eliminado de la base de datos;
   - Fue marcado como `INACTIVO` o `BLOQUEADO`;
   
   El método `user()` ejecuta inmediatamente `logout()`, destruye la sesión y devuelve `null`, forzando la redirección inmediata hacia `/login` en la siguiente solicitud (comprobado en la prueba `T3-15`).
4. Asimismo, el rol activo se sincroniza directamente desde la columna `roles.nombre` en la base de datos, impidiendo que la alteración manual de variables de cliente o cookies eleve privilegios (comprobado en la prueba `T3-13`).
