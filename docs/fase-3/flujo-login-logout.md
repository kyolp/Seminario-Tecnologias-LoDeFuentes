# Flujo de Inicio y Cierre de Sesión (Login / Logout)

## 1. Flujo de Inicio de Sesión (`POST /login`)

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuario (Navegador)
    participant R as Router / Middleware
    participant C as AuthController
    participant S as AuthService
    participant Repo as UserRepository
    participant DB as MySQL (usuarios/roles)
    participant Ses as Session

    U->>R: POST /login (usuario, password, _token)
    Note over R: Middleware: VerifyCsrfToken
    alt Token CSRF inválido o ausente
        R-->>U: HTTP 403 Forbidden (Petición Expirada)
    else Token CSRF válido
        R->>C: login(Request)
        C->>S: attempt(usuario, password)
        S->>Repo: findByUsername(usuario)
        Repo->>DB: SELECT u.*, r.nombre FROM usuarios u...
        DB-->>Repo: Registro o NULL
        Repo-->>S: User o NULL

        alt Usuario no encontrado
            S-->>C: false (error: "Credenciales inválidas.")
            C-->>U: HTTP 401 Unauthorized (Vista login con mensaje genérico)
        else Usuario INACTIVO / BLOQUEADO
            S-->>C: false (error: "Credenciales inválidas.")
            C-->>U: HTTP 401 Unauthorized (Vista login con mensaje genérico)
        else Password incorrecta (!password_verify)
            S-->>C: false (error: "Credenciales inválidas.")
            C-->>U: HTTP 401 Unauthorized (Vista login con mensaje genérico)
        else Password correcta (password_verify == true)
            S->>Ses: regenerate(true)
            S->>Ses: set(auth_user_id, auth_role, auth_username)
            S->>Repo: updateLastAccess(id_usuario)
            Repo->>DB: UPDATE usuarios SET ultimo_acceso = NOW()
            S-->>C: true
            alt Rol ADMINISTRADOR
                C-->>U: HTTP 302 Redirección -> /administracion
            else Rol DOCENTE
                C-->>U: HTTP 302 Redirección -> /docente
            end
        end
    end
```

---

## 2. Flujo de Cierre de Sesión (`POST /logout`)

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuario (Navegador)
    participant R as Router / Middleware
    participant C as AuthController
    participant S as AuthService
    participant Ses as Session

    U->>R: POST /logout (_token)
    Note over R: Middleware: VerifyCsrfToken
    alt Token CSRF inválido
        R-->>U: HTTP 403 Forbidden
    else Token CSRF válido
        R->>C: logout(Request)
        C->>S: logout()
        S->>Ses: destroy()
        Note over Ses: $_SESSION = []<br/>Cookie invalidada<br/>session_destroy()
        S-->>C: void
        C-->>U: HTTP 302 Redirección -> /login
    end
```

---

## 3. Principios de Seguridad Aplicados en el Flujo

1. **Mensaje de Error Genérico:**  
   Tanto si el usuario no existe, como si la contraseña no coincide o si la cuenta está inactiva, la respuesta emitida es invariablemente:
   ```text
   Credenciales inválidas.
   ```
   Esto previene ataques de enumeración de usuarios.

2. **Protección contra Fuerza Bruta:**  
   Se documenta que las contraseñas utilizan BCrypt con costo computacional nativo, mitigando ataques de fuerza bruta offline o de diccionario. El rate limiting formal queda reservado para la Fase 14.

3. **Cero Fuga de Contraseñas y Hashes:**  
   En ninguna circunstancia se imprime o registra en logs la contraseña en texto claro ni el hash BCrypt. Los registros en `storage/logs/app.log` solo reportan el nombre de usuario y el resultado de la operación.
