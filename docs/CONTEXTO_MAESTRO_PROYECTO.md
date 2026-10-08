# CONTEXTO MAESTRO DEL PROYECTO

**Documento transversal de referencia y navegación rápida del sistema**  
*Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes*  
*Seminario de Tecnologías — Proyecto Final*  
*Última actualización: Cierre de Fase 9 (Octubre 2026)*

---

> [!NOTE]
> **Propósito de este documento:**  
> Este archivo sirve como **punto de entrada rápido** para que cualquier desarrollador, evaluador o auditor comprenda integralmente la arquitectura, el estado funcional, las relaciones de base de datos, los flujos operativos, las directivas de seguridad y la ubicación exacta de archivos sin tener que releer todo el repositorio.  
> **No sustituye** a `docs/fase-0/matriz-trazabilidad.md`, al diccionario de datos ni a los informes técnicos de cada fase, sino que los indexa y articula.

---

## 1. IDENTIDAD DEL PROYECTO

- **Nombre oficial del sistema:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes.
- **Institución beneficiaria:** Escuela Oficial Rural Mixta Lo de Fuentes.
- **Objetivo general:** Desarrollar e implantar una plataforma web integral, modular, segura y auditable para automatizar la gestión administrativa y académica de la institución, abarcando la estructura escolar (períodos, grados, secciones y aulas), la asignación de docentes, las inscripciones web públicas con control estricto de cupos en tiempo real, el portal docente con listados de estudiantes, el control de asistencia georreferenciado mediante geocerca institucional, la gestión transaccional de permisos laborales con soporte documental, y las futuras fases de notificaciones, reportes gerenciales, comprobantes PDF y recuperación de credenciales.
- **Ubicación local del proyecto:**  
  `c:\Users\David\Documents\Universidad\2026\Segundo semestre\seminario de tecnologias\ProyectoFinal`
- **Control de versiones:**
  - **Rama activa:** `main`
  - **Remoto configurado:** `origin`
  - **Repositorio oficial en GitHub:**  
    `https://github.com/kyolp/Seminario-Tecnologias-LoDeFuentes.git`
- **Regla estricta de publicación:**  
  > [!IMPORTANT]
  > El asistente de desarrollo (Antigravity) puede crear ramas y registrar commits locales confirmados, pero **BAJO NINGUNA CIRCUNSTANCIA DEBE EJECUTAR `git push` DE FORMA AUTOMÁTICA**.  
  > La publicación remota a GitHub continúa siendo administrada manual y exclusivamente por el usuario titular.

---

## 2. STACK TECNOLÓGICO REAL

El proyecto se desarrolla estrictamente bajo una arquitectura nativa sin frameworks pesados ni dependencias de terceros (cero dependencias externas en tiempo de ejecución):

- **Lenguaje Backend:** PHP 8.2.12.
  - Modo estricto activado en todo el código (`declare(strict_types=1);`).
  - Clases y propiedades `readonly` en entidades de dominio (modelos inmutables).
  - Tipado fuerte en firmas de métodos y retornos.
- **Motor de Base de Datos:** MySQL 8.0.42.
  - Motor de almacenamiento transaccional: `InnoDB`.
  - Codificación y colación: `utf8mb4` / `utf8mb4_unicode_ci`.
  - Transacciones ACID con aislamiento estricto y bloqueos pesimistas (`SELECT ... FOR UPDATE`).
  - Integridad física mediante llaves foráneas (`FOREIGN KEY`), restricciones de chequeo (`CHECK`) y columnas virtuales almacenadas (`STORED GENERATED COLUMNS`) para unicidad condicional.
- **Capa de Conexión:** PHP Data Objects (PDO) nativo con sentencias preparadas parametrizadas en el 100% de consultas, emulación desactivada (`ATTR_EMULATE_PREPARES => false`) y manejo de excepciones forzado (`ATTR_ERRMODE => ERRMODE_EXCEPTION`).
- **Frontend:**
  - HTML5 semántico nativo.
  - CSS3 puro responsive (sin Bootstrap, Tailwind ni librerías externas).
  - JavaScript ES6+ modular nativo (sin frameworks JS ni bundlers).
- **APIs del Navegador:**
  - HTML5 Geolocation API (`navigator.geolocation`) para captura segura de coordenadas en asistencia docente.
- **Criptografía y Seguridad Nativa:**
  - Generación de números aleatorios criptográficamente seguros: `random_bytes()`, `bin2hex()`.
  - Hashing de contraseñas: `password_hash()` con algoritmo `PASSWORD_BCRYPT`.
  - Detección de magic bytes y validación MIME de archivos: `finfo(FILEINFO_MIME_TYPE)`.
- **Servidor Web y Enrutamiento:** Apache con módulo `mod_rewrite`, Front Controller único (`public/index.php`) y reglas perimetrales de bloqueo `.htaccess` (`Require all denied` en storage, config y logs).
- **Entorno de Pruebas:** Suites de pruebas automatizadas nativas en PHP CLI con harness HTTP embebido (`cURL` y servidor PHP temporal en puertos efímeros) y scripts concurrentes multiproceso con conexiones PDO independientes.

---

## 3. ARQUITECTURA DEL SISTEMA

El sistema implementa una **Arquitectura en Capas Desacoplada** (Clean Architecture / MVC Modular) gobernada por inversión de dependencias y separación estricta de responsabilidades:

```
[ Cliente Web / Navegador ]
            │
            ▼
    [ public/index.php ]  (Punto de Entrada Único / Front Controller)
            │
            ▼
   [ app/Core/Router.php ] ◄── Pipeline de Middlewares (Auth, RBAC, CSRF)
            │
            ▼
 [ app/Controllers/*Controller.php ]  (Controladores HTTP / Orquestación)
            │
            ▼
   [ app/Services/*Service.php ]      (Capa de Dominio y Reglas de Negocio)
            │
            ▼
[ app/Repositories/*Repository.php ] ◄── Implementa Repositories Interfaces
            │
            ▼
  [ Base de Datos MySQL 8.0 ]        (Vía PDO / Sentencias Preparadas)
```

### Componentes Centrales del Núcleo (`app/Core/`)

1. **`public/index.php` (Front Controller):** Recibe todas las solicitudes HTTP redirigidas por Apache vía `.htaccess`, carga el arranque global (`bootstrap.php`) e inicializa la aplicación.
2. **`app/bootstrap.php`:** Define constantes de rutas del sistema (`ROOT_PATH`, `APP_PATH`, `STORAGE_PATH`, etc.), inicializa el autocargador PSR-4, registra helpers globales, carga variables de entorno desde `.env`, fija la zona horaria (`America/Guatemala`) y configura el log de errores.
3. **`app/autoload.php`:** Autocargador PSR-4 nativo que mapea el espacio de nombres `App\` directamente a la estructura de carpetas de `app/`.
4. **`app/Core/App.php`:** Coordina la inicialización de rutas del sistema, la captura de la petición HTTP actual y el despacho hacia el enrutador.
5. **`app/Core/Router.php`:** Enrutador liviano que gestiona peticiones GET y POST, resolución de parámetros dinámicos en URL (`{id}`), despacho a controladores y ejecución encadenada del pipeline de middlewares.
6. **`app/Core/Request.php`:** Encapsula la petición HTTP de forma segura (`get()`, `post()`, `file()`, `hasFile()`, `header()`, `ip()`, etc.), permitiendo la lectura ordenada de datos y archivos multipart.
7. **`app/Core/Response.php`:** Abstracción para emitir respuestas HTTP con códigos de estado, cabeceras normalizadas, JSON o redirecciones seguras.
8. **`app/Core/Database.php`:** Manejador Singleton de la conexión PDO. Carga la configuración de base de datos desde `.env` con validación fail-fast y enmascara cualquier excepción técnica en un `DatabaseException` para evitar exponer credenciales o trazas SQL.
9. **`app/Core/Session.php`:** Gestión de sesiones PHP seguras con cookies `HttpOnly`, protección `SameSite=Lax` y regeneración preventiva de ID en operaciones de autenticación.
10. **`app/Core/Csrf.php`:** Generador y validador de tokens CSRF unívocos por sesión (32 bytes criptográficos aleatorios).
11. **`app/Core/View.php`:** Motor de plantillas PHP nativo con soporte de layouts compartidos (`views/layouts/main.php`) y paso seguro de variables.
12. **`app/Core/Logger.php`:** Registro centralizado de eventos y errores en `storage/logs/`.
13. **`app/Helpers/helpers.php`:** Funciones de utilidad global: `e()` (escape XSS obligatorio), `csrf_token()`, `csrf_field()`, `url()`, `asset()`, `session()`, `auth()`, `config()`, `old()`.

### Pipeline de Middlewares (`app/Middleware/`)

- **`Authenticate`:** Verifica que exista una sesión autenticada activa (`auth_user_id`). Si el usuario es anónimo, redirige a `/login` preservando un mensaje flash.
- **`RequireRole`:** Aplica control de acceso basado en roles (RBAC). Revalida autoritativamente en tiempo real contra la base de datos el estado activo del usuario y la vigencia de su rol (`ADMINISTRADOR`, `DOCENTE`), respondiendo `HTTP 403 Forbidden` si no cuenta con el privilegio requerido.
- **`VerifyCsrfToken`:** Intercepta todas las peticiones con métodos mutantes (`POST`), validando que el token enviado coincida con el almacenado en la sesión. Responde `HTTP 403 Forbidden` ante omisiones o discrepancias.

---

## 4. ESTRUCTURA DEL PROYECTO

```
ProyectoFinal/
├── .env                              # Variables de entorno activas (fuera de Git)
├── .env.example                      # Plantilla de variables de entorno de referencia
├── .gitignore                        # Reglas de exclusión de Git
├── .htaccess                         # Reescritura perimetral hacia public/
├── README.md                         # Archivo inicial de fase 0 (pendiente de reescritura final)
├── app/                              # Código fuente PHP bajo namespace App\
│   ├── autoload.php                  # Autocargador PSR-4 nativo
│   ├── bootstrap.php                 # Inicialización y definición de rutas del sistema
│   ├── Controllers/                  # Controladores de capa web (19 controladores)
│   ├── Core/                         # Componentes de infraestructura del framework nativo (13 clases)
│   ├── Helpers/                      # Funciones auxiliares globales (helpers.php)
│   ├── Middleware/                   # Filtros de petición HTTP (Auth, RBAC, CSRF)
│   ├── Models/                       # Entidades de dominio fuertemente tipadas (17 modelos)
│   ├── Repositories/                 # Capa de datos con contratos Interface e implementaciones PDO (13 pares)
│   └── Services/                     # Servicios de aplicación y reglas de negocio institucionales (13 servicios)
├── config/                           # Archivos de configuración estructurada
│   ├── app.php                       # Configuración general (nombre, entorno, timezone, debug)
│   └── database.php                  # Parámetros de conexión a MySQL
├── database/                         # Definición de persistencia relacional
│   ├── schema.sql                    # Definición DDL autoritativa de las 20 tablas relacionales
│   └── tests/                        # Scripts de verificación y validación de BD
│       └── validacion.sql            # Suite de pruebas SQL de Fase 1 (18/18 verificaciones)
├── docs/                             # Documentación técnica exhaustiva por fase
│   ├── CONTEXTO_MAESTRO_PROYECTO.md  # ESTE DOCUMENTO (Contexto transversal y navegación rápida)
│   ├── fase-0/                       # Diagnóstico, trazabilidad y preparación
│   ├── fase-1/                       # Modelo relacional, diccionario de datos y decisiones técnicas
│   ├── fase-2/                       # Arquitectura base, router y Front Controller
│   ├── fase-3/                       # Autenticación, sesiones y roles
│   ├── fase-4/                       # Administración escolar básica
│   ├── fase-5/                       # Asignaciones docentes y versionado
│   ├── fase-6/                       # Inscripciones públicas y control concurrente de cupos
│   ├── fase-7/                       # Portal docente y minimización de datos
│   ├── fase-8/                       # Asistencia docente y validación geodésica Haversine
│   └── fase-9/                       # Gestión de permisos docentes, archivos seguros y auditoría
├── public/                           # Raíz web pública accesible por el servidor HTTP
│   ├── .htaccess                     # Reescritura a index.php y protección de acceso directo
│   ├── index.php                     # Front Controller único
│   ├── css/                          # Hojas de estilo CSS nativas
│   └── js/                           # Scripts JavaScript cliente (asistencia.js, geolocalización, UI)
├── storage/                          # Almacenamiento privado del servidor (fuera de public/)
│   ├── .htaccess                     # Blindaje perimetral absoluto ("Require all denied")
│   ├── logs/                         # Archivos de registro de eventos y errores
│   └── permisos/                     # Archivos de soporte de permisos (nombres aleatorios CSPRNG)
├── tests/                            # Suites de pruebas automatizadas ejecutables por CLI
│   ├── harness_geolocation.js        # Harness Node.js para emulación de Geolocation API
│   ├── run_tests.php                 # Suite de pruebas de Fase 2 (16 pruebas)
│   ├── run_tests_fase3.php           # Suite de pruebas de Fase 3 (19 pruebas)
│   ├── run_tests_fase4.php           # Suite de pruebas de Fase 4 (27 pruebas)
│   ├── run_tests_fase5.php           # Suite de pruebas de Fase 5 (26 pruebas)
│   ├── run_tests_fase6.php           # Suite de pruebas de Fase 6 (30 pruebas)
│   ├── run_tests_fase7.php           # Suite de pruebas de Fase 7 (28 pruebas)
│   ├── run_tests_fase8.php           # Suite de pruebas de Fase 8 (40 pruebas)
│   ├── run_tests_fase9.php           # Suite de pruebas de Fase 9 (46 pruebas)
│   ├── worker_asignacion_concurrente.php # Script para prueba de estrés concurrente en Fase 5
│   └── worker_inscripcion_concurrente.php# Script para prueba de estrés concurrente en Fase 6
└── views/                            # Plantillas y vistas PHP nativas
    ├── admin/                        # Vistas administrativas (períodos, grados, secciones, aulas, docentes, asignaciones, geocerca, permisos)
    ├── auth/                         # Vistas de login y autenticación
    ├── docente/                      # Vistas del portal docente (listados de estudiantes, marcaje de asistencia, permisos)
    ├── errors/                       # Vistas de error HTTP (403, 404, 422, 500)
    ├── home/                         # Vista de bienvenida pública institucional
    ├── inscripciones/                # Vistas de formulario de inscripción pública y consulta de estado por código
    └── layouts/                      # Layouts principales reutilizables (main.php)
```

---

## 5. BASE DE DATOS Y DOMINIOS DE DATOS

El esquema relacional consta de **exactamente 20 tablas** en MySQL 8.0 (`database/schema.sql`), agrupadas orgánicamente por dominio de negocio:

### 1. SEGURIDAD (3 tablas)
- **`roles`:** Catálogo de roles institucionales del sistema (`ADMINISTRADOR`, `DOCENTE`).
- **`usuarios`:** Cuentas de usuario con hash seguro de credenciales (`password_hash`), correo/nombre de usuario, estado (`ACTIVO`, `INACTIVO`) y rol asociado.
- **`tokens_recuperacion`:** Tokens temporales criptográficos con fecha de expiración y estado de uso para restablecimiento de contraseñas (reservado para Fase 13).

### 2. PERSONAS (4 tablas)
- **`docentes`:** Perfil laboral y profesional de docentes activos, vinculados 1:1 con un registro de la tabla `usuarios`.
- **`estudiantes`:** Información personal, fecha de nacimiento, género y código único MINEDUC de los estudiantes.
- **`encargados`:** Datos de contacto, DPI y dirección de los padres o tutores legales.
- **`estudiantes_encargados`:** Tabla relacional pivote que modela la relación N:M entre estudiantes y encargados, definiendo el parentesco y designando al encargado principal.

### 3. ESTRUCTURA ESCOLAR (5 tablas)
- **`periodos_escolares`:** Ciclos lectivos anuales (ej. 2026), fecha de inicio, fecha de fin y estado (`ACTIVO`, `INACTIVO`).
- **`grados`:** Catálogo de grados académicos oficiales de la escuela (ej. Primero Primaria, Segundo Primaria, etc.).
- **`grados_periodos`:** Habilitación y configuración de grados dentro de un ciclo lectivo específico.
- **`secciones`:** Secciones por grado y período (ej. Sección A, B), con su jornada, capacidad máxima y contador de cupo disponible.
- **`aulas`:** Espacios físicos del centro educativo (código, nombre, capacidad física).

### 4. ASIGNACIONES (1 tabla)
- **`asignaciones_docentes`:** Vinculación formal de un docente titular con una sección y un aula para un período escolar, con trazabilidad de estado (`ACTIVA`, `INACTIVA`), fecha de asignación y usuario administrador que la registró.

### 5. INSCRIPCIONES (1 tabla)
- **`inscripciones`:** Registro oficial de matrícula escolar que vincula atómicamente al estudiante, su encargado y la sección seleccionada, generando un código alfanumérico único e irrepetible para consulta pública.

### 6. ASISTENCIA (2 tablas)
- **`configuraciones_ubicacion`:** Geocerca institucional versionada (latitud, longitud, radio de tolerancia en metros, estado activo/inactivo). Garantiza unicidad de geocerca activa mediante columna virtual almacenada e índice único.
- **`marcajes_asistencia`:** Bitácora inmutable de entradas y salidas de los docentes. Almacena coordenadas geográficas, distancia calculada, bandera `dentro_radio` (forzada por CHECK físico `= 1`), tipo (`ENTRADA`, `SALIDA`) y marca temporal de servidor con microsegundos (`CURRENT_TIMESTAMP(6)`).

### 7. PERMISOS Y NOTIFICACIONES (4 tablas)
- **`solicitudes_permiso`:** Solicitudes de permiso laboral tramitadas por los docentes (fecha solicitada, motivo institucional, descripción detallada y estado: `PENDIENTE`, `APROBADO`, `RECHAZADO`).
- **`historial_solicitudes_permiso`:** Bitácora append-only inmutable de transiciones de estado de permisos, con usuario actor responsable, fecha/hora exacta de servidor y comentarios de resolución.
- **`documentos_permiso`:** Metadatos de archivos de respaldo adjuntos a solicitudes (nombre original, nombre físico aleatorio en disco, extensión, tipo MIME real y tamaño en bytes).
- **`notificaciones`:** Tabla para el registro y entrega de alertas y notificaciones del sistema (alcance formal de la Fase 10; tabla existente pero no alterada en fases previas).

---

## 6. MAPA DE RELACIONES PRINCIPALES

El siguiente esquema textual sintetiza las dependencias e integridad referencial (`FOREIGN KEY`) entre las tablas de la base de datos:

```
[roles]
   └──< [usuarios] (fk_usuarios_roles)
           ├── (1:1)── [docentes] (fk_docentes_usuarios)
           │               ├──< [asignaciones_docentes] (fk_asignaciones_docentes)
           │               ├──< [marcajes_asistencia]   (fk_marcajes_docentes)
           │               └──< [solicitudes_permiso]   (fk_solicitudes_docentes)
           │                        ├──< [historial_solicitudes_permiso] (fk_historial_solicitudes)
           │                        ├──< [documentos_permiso]            (fk_documentos_solicitudes)
           │                        └──< [notificaciones]               (fk_notificaciones_solicitudes)
           ├──< [tokens_recuperacion]            (fk_tokens_usuarios)
           ├──< [configuraciones_ubicacion]       (fk_configuraciones_usuario_creador)
           ├──< [marcajes_asistencia]             (fk_marcajes_usuarios)
           ├──< [historial_solicitudes_permiso]   (fk_historial_usuarios)
           └──< [notificaciones]                 (fk_notificaciones_usuarios)

[periodos_escolares]
   └──< [grados_periodos] (fk_grados_periodos_periodos)
           ▲
[grados] ──┘ (fk_grados_periodos_grados)
   └──< [secciones] (fk_secciones_grados_periodos)
           ├──< [asignaciones_docentes] (fk_asignaciones_secciones)
           └──< [inscripciones]         (fk_inscripciones_secciones)

[aulas]
   └──< [asignaciones_docentes] (fk_asignaciones_aulas)

[estudiantes]
   ├──< [estudiantes_encargados] (fk_estudiantes_encargados_estudiantes)
   └──< [inscripciones]          (fk_inscripciones_estudiantes)

[encargados]
   ├──< [estudiantes_encargados] (fk_estudiantes_encargados_encargados)
   └──< [inscripciones]          (fk_inscripciones_encargados)

[estudiantes_encargados]
   └──< [inscripciones] (fk_inscripciones_vinculo_encargado)

[configuraciones_ubicacion]
   └──< [marcajes_asistencia] (fk_marcajes_configuracion)
```

---

## 7. FLUJOS FUNCIONALES OPERATIVOS

### Flujo 1: Autenticación y Control de Acceso
1. El usuario envía credenciales a `POST /login`.
2. `AuthController` delega en `AuthService`.
3. Se verifica existencia del usuario y vigencia de la cuenta.
4. Validación criptográfica mediante `password_verify()`.
5. Si es válido, se regenera la sesión (`Session::regenerate()`), se almacena la identidad mínima y el middleware `RequireRole` revalida en cada petición el rol en BD (`ADMINISTRADOR` o `DOCENTE`), redirigiendo a su respectivo portal.

### Flujo 2: Administración Escolar Básica
1. El administrador gestiona períodos lectivos anuales (creación, edición y activación).
2. Se habilitan los grados autorizados para el período activo (`grados_periodos`).
3. Se crean secciones con asignación de jornada y cupo máximo de capacidad.
4. Se registran aulas y cuentas docentes con generación automática de usuario.

### Flujo 3: Asignación Docente
1. El administrador selecciona un docente activo, una sección disponible y un aula.
2. `AsignacionService` valida de forma atómica y transaccional que la sección no tenga ya un docente activo y que el docente no esté solapado en la misma jornada.
3. Si existe una asignación previa para esa sección, se desactiva lógicamente (`estado = 'INACTIVA'`) preservando la historia.
4. Se registra la nueva asignación activa con el usuario administrador responsable.

### Flujo 4: Inscripción Pública y Control Concurrente de Cupos
1. Un padre de familia o encargado accede al formulario público `GET /inscripciones`.
2. Se muestran únicamente las secciones con cupo disponible real (`cupo_disponible > 0`).
3. Al enviar `POST /inscripciones`, `InscripcionService` abre una transacción ACID y ejecuta un **bloqueo pesimista exclusivo**:
   `SELECT cupo_disponible, capacidad FROM secciones WHERE id = ? FOR UPDATE`.
4. Si `cupo_disponible <= 0`, la transacción aborta inmediatamente con rollback (cero sobrecupo).
5. Si hay cupo, se crea o reutiliza el registro del estudiante y del encargado, se genera el vínculo, se crea la inscripción, se decrementa atómicamente el cupo de la sección (`cupo_disponible = cupo_disponible - 1`) y se genera un código alfanumérico único de confirmación (ej. `EORM-XXXX-XXXX`).
6. El encargado puede consultar en `GET /inscripciones/consultar` el estado básico con su código sin requerir usuario ni contraseña, minimizando la exposición de datos personales.

### Flujo 5: Portal Docente
1. El docente autenticado accede a `GET /docente`.
2. `PortalDocenteService` resuelve la identidad del docente **únicamente** a partir de la sesión autenticada (`auth_user_id`), descartando parámetros del cliente.
3. Se consulta la asignación activa del docente para el período lectivo vigente.
4. Se cargan los estudiantes oficialmente inscritos en su sección.
5. Se aplica doble barrera de aislamiento horizontal: un docente no puede ver asignaciones ni alumnos de secciones ajenas (HTTP 403 / DomainException).

### Flujo 6: Asistencia Docente y Geolocalización
1. El docente accede a `GET /docente/asistencia`.
2. El cliente obtiene las coordenadas mediante `navigator.geolocation` y las envía vía `POST /docente/asistencia/entrada` o `salida` con token CSRF.
3. `AsistenciaService` consulta la geocerca institucional activa en base de datos.
4. El backend calcula de forma autoritativa la distancia geodésica mediante la **fórmula de Haversine** ($R = 6,371,000$ m). Parámetros como `dentro_radio` provistos por el cliente son descartados.
5. Se evalúa la regla de frontera inclusiva ($\text{distancia} \le \text{radio}$). Si está fuera, se rechaza sin persistencia (`HTTP 422`).
6. Se valida la máquina de estados: no permite doble entrada consecutiva ni salida sin entrada previa.
7. El marcaje se inserta de forma inmutable con la fecha y hora exacta del servidor (`CURRENT_TIMESTAMP(6)`).
8. El docente consulta su historial personal con orden cronológico descendente y aislamiento horizontal estricto.

### Flujo 7: Gestión de Permisos Docentes
1. El docente ingresa a `GET /docente/permisos/crear`.
2. Registra la fecha solicitada, motivo institucional, descripción obligatoria y adjunta opcionalmente un documento de soporte (PDF, PNG, JPG, JPEG, WEBP; máx. 5 MB).
3. `PermisoService` valida la extensión y el tipo MIME real por magic bytes (`finfo`).
4. El archivo se almacena fuera del webroot (`storage/permisos/`) con un nombre criptográficamente aleatorio generado por CSPRNG (`permiso_<64-hex>.<ext>`) para mitigar colisiones y Path Traversal.
5. La solicitud nace en estado inmutable `PENDIENTE` y se crea atómicamente el primer registro en `historial_solicitudes_permiso`.
6. El administrador revisa la bandeja en `GET /administracion/permisos`, con filtros por estado y docente.
7. Al resolver (aprobar o rechazar), se ejecuta una transacción con bloqueo pesimista `SELECT ... FOR UPDATE` (probado en concurrencia real). Se actualiza el estado a terminal (`APROBADO` o `RECHAZADO`, prohibiendo re-resolución) y se asienta el historial de auditoría con la observación administrativa.
8. La descarga de soportes se realiza por streaming seguro con cabeceras estrictas y validación de propiedad (docente solo sus propios archivos, administrador todos).

### Flujo 8: Notificaciones
- **Estado:** `PENDIENTE — FASE 10`.
- Durante las Fases 1 a 9 la tabla `notificaciones` se mantuvo intacta y con cero registros persistidos ($\Delta \text{notificaciones} = 0$).

---

## 8. MATRIZ RESUMIDA DE REQUISITOS (RF-01 A RF-32)

| RF | Módulo Oficial | Denominación Literal Oficial | Fase Asignada | Estado Funcional |
|:---:|:---|:---|:---:|:---:|
| **RF-01** | Autenticación y roles | Autenticación | 3 | IMPLEMENTADO Y VALIDADO |
| **RF-02** | Autenticación y roles | Roles y permisos | 3 | IMPLEMENTADO Y VALIDADO |
| **RF-03** | Recuperación de contraseña | Recuperación de contraseña | 13 | PENDIENTE |
| **RF-04** | Administración básica | Gestión de estudiantes | 4 | IMPLEMENTADO Y VALIDADO |
| **RF-05** | Inscripciones y cupos | Datos del encargado | 6 | IMPLEMENTADO Y VALIDADO |
| **RF-06** | Administración básica | Gestión de grados | 4 | IMPLEMENTADO Y VALIDADO |
| **RF-07** | Administración básica | Gestión de secciones | 4 | IMPLEMENTADO Y VALIDADO |
| **RF-08** | Administración básica | Capacidad por sección | 4 | IMPLEMENTADO Y VALIDADO |
| **RF-09** | Inscripciones y cupos | Control automático de cupos | 6 | IMPLEMENTADO Y VALIDADO |
| **RF-10** | Inscripciones y cupos | Disponibilidad | 6 | IMPLEMENTADO Y VALIDADO |
| **RF-11** | Inscripciones y cupos | Inscripción web | 6 | IMPLEMENTADO Y VALIDADO |
| **RF-12** | Inscripciones y cupos | Confirmación | 6 | IMPLEMENTADO Y VALIDADO |
| **RF-13** | Comprobantes PDF | Comprobante PDF | 11 | PENDIENTE |
| **RF-14** | Inscripciones y cupos | Consulta por código | 6 | IMPLEMENTADO Y VALIDADO |
| **RF-15** | Administración básica | Gestión de docentes | 4 | IMPLEMENTADO Y VALIDADO |
| **RF-16** | Asignaciones docentes | Asignaciones | 5 | IMPLEMENTADO Y VALIDADO |
| **RF-17** | Portal docente | Listado de estudiantes | 7 | IMPLEMENTADO Y VALIDADO |
| **RF-18** | Asistencia y geolocalización | Configuración de ubicación | 8 | IMPLEMENTADO Y VALIDADO |
| **RF-19** | Asistencia y geolocalización | Validación geográfica | 8 | IMPLEMENTADO Y VALIDADO |
| **RF-20** | Asistencia y geolocalización | Marcaje de entrada | 8 | IMPLEMENTADO Y VALIDADO |
| **RF-21** | Asistencia y geolocalización | Marcaje de salida | 8 | IMPLEMENTADO Y VALIDADO |
| **RF-22** | Asistencia y geolocalización | Registro de fecha y hora | 8 | IMPLEMENTADO Y VALIDADO |
| **RF-23** | Asistencia y geolocalización | Historial de asistencia | 8 | IMPLEMENTADO Y VALIDADO |
| **RF-24** | Gestión de permisos | Solicitud de permiso | 9 | IMPLEMENTADO Y VALIDADO |
| **RF-25** | Gestión de permisos | Documento de respaldo | 9 | IMPLEMENTADO Y VALIDADO |
| **RF-26** | Notificaciones | Notificación de permiso | 10 | IMPLEMENTADO Y VALIDADO |
| **RF-27** | Gestión de permisos | Aprobación/rechazo | 9 | IMPLEMENTADO Y VALIDADO |
| **RF-28** | Gestión de permisos | Estado de solicitud | 9 | IMPLEMENTADO Y VALIDADO |
| **RF-29** | Reportes y exportación | Reporte de asistencia | 12 | PENDIENTE |
| **RF-30** | Reportes y exportación | Consulta de inscripciones | 12 | PENDIENTE |
| **RF-31** | Integración de módulos | Integración de módulos | 6/7 | IMPLEMENTADO Y VALIDADO |
| **RF-32** | Inscripciones y cupos | Integridad de cupos | 6 | IMPLEMENTADO Y VALIDADO |

### Requerimientos No Funcionales (RNF Clave)
- **RNF-03 (Seguridad):** IMPLEMENTADO Y VALIDADO en Fase 3 (`AuthService`, `RequireRole`, hashing seguro BCRYPT).
- **RNF-04 (Privacidad):** EVIDENCIA AMPLIADA en Fases 6, 7 y 10 (Ocultamiento de datos sensibles en consulta pública, aislamiento horizontal docente y aislamiento de notificaciones entre administradores). *Validación global e integral reservada para la Fase 14*.
- **RNF-05 (Integridad):** IMPLEMENTADO Y VALIDADO en Fase 1 (Esquema relacional con 20 tablas, constraints, checks y claves foráneas).
- **RNF-06 (Trazabilidad):** EVIDENCIA AMPLIADA en Fases 8, 9 y 10 (Inmutabilidad de marcajes con microsegundos de servidor, bitácora append-only en `historial_solicitudes_permiso` y registro temporal de lectura en notificaciones). *Validación integral de auditoría del sistema reservada para la Fase 14*.

---

## 9. ESTADO DE LAS FASES DEL PROYECTO

| Fase | Denominación Oficial | Estado |
|:---:|:---|:---:|
| **Fase 0** | Diagnóstico, aislamiento y preparación | COMPLETADA ✅ |
| **Fase 1** | Base de datos y modelo relacional | COMPLETADA ✅ |
| **Fase 2** | Estructura base de la aplicación y arquitectura | COMPLETADA ✅ |
| **Fase 3** | Autenticación, sesiones y roles | COMPLETADA ✅ |
| **Fase 4** | Administración escolar básica | COMPLETADA ✅ |
| **Fase 5** | Asignaciones docentes y versionado | COMPLETADA ✅ |
| **Fase 6** | Inscripciones públicas y control de cupos | COMPLETADA ✅ |
| **Fase 7** | Portal docente y minimización de datos | COMPLETADA ✅ |
| **Fase 8** | Asistencia docente y geolocalización | COMPLETADA ✅ |
| **Fase 9** | Gestión de permisos docentes | COMPLETADA ✅ |
| **Fase 10** | Notificaciones | COMPLETADA ✅ |
| **Fase 11** | Comprobantes PDF | **PENDIENTE (Siguiente fase)** |
| **Fase 12** | Reportes y exportación | PENDIENTE |
| **Fase 13** | Recuperación de contraseñas | PENDIENTE |
| **Fase 14** | Endurecimiento integral de seguridad y privacidad | PENDIENTE |
| **Fase 15** | Pruebas integrales del sistema | PENDIENTE |
| **Fase 16** | Cierre, entrega y documentación final | PENDIENTE |

---

## 10. RESUMEN DE PRUEBAS AUTOMATIZADAS POR FASE

Totales consolidados y certificados en los informes técnicos de cierre:

| Fase | Suite / Script de Pruebas | Pruebas Ejecutadas | Resultado | Tasa de Éxito |
|:---:|:---|:---:|:---:|:---:|
| **Fase 1** | `database/tests/validacion.sql` | 18 | 18 OK | 100% |
| **Fase 2** | `tests/run_tests.php` | 16 | 16 OK | 100% |
| **Fase 3** | `tests/run_tests_fase3.php` | 19 | 19 OK | 100% |
| **Fase 4** | `tests/run_tests_fase4.php` | 27 | 27 OK | 100% |
| **Fase 5** | `tests/run_tests_fase5.php` | 26 | 26 OK | 100% |
| **Fase 6** | `tests/run_tests_fase6.php` | 30 | 30 OK | 100% |
| **Fase 7** | `tests/run_tests_fase7.php` | 28 | 28 OK | 100% |
| **Fase 8** | `tests/run_tests_fase8.php` | 40 | 40 OK | 100% |
| **Fase 9** | `tests/run_tests_fase9.php` | 46 | 46 OK | 100% |
| **Fase 10** | `tests/run_tests_fase10.php` | 27 | 27 OK | 100% |
| **TOTAL** | **10 Suites Automatizadas** | **277** | **277 OK** | **100% GREEN** |

---

## 11. ÍNDICE DE DECISIONES TÉCNICAS (DT-01 A DT-66)

| Decisión | Tema Central | Fase | Documento de Referencia |
|:---|:---|:---:|:---|
| **DT-01** | Motor InnoDB y soporte transaccional ACID | Fase 1 | `docs/fase-1/decisiones-tecnicas.md` |
| **DT-02** | Colación UTF8MB4 Unicode para soporte de caracteres | Fase 1 | `docs/fase-1/decisiones-tecnicas.md` |
| **DT-03** | Tipado fuerte y restricciones estrictas en columnas | Fase 1 | `docs/fase-1/decisiones-tecnicas.md` |
| **DT-04** | Integridad referencial con llaves foráneas formales | Fase 1 | `docs/fase-1/decisiones-tecnicas.md` |
| **DT-05** | Control de cupos escolares mediante contadores enteros en BD | Fase 1 | `docs/fase-1/decisiones-tecnicas.md` |
| **DT-06** | Código alfanumérico único para consulta pública de inscripción | Fase 1 | `docs/fase-1/decisiones-tecnicas.md` |
| **DT-07** | Aislamiento entre usuarios y perfiles laborales docentes | Fase 1 | `docs/fase-1/decisiones-tecnicas.md` |
| **DT-08** | Tabla pivote `estudiantes_encargados` para relación N:M | Fase 1 | `docs/fase-1/decisiones-tecnicas.md` |
| **DT-09** | Autocargador PSR-4 nativo sin dependencias de terceros | Fase 2 | `docs/fase-2/decisiones-tecnicas.md` |
| **DT-10** | Punto único de entrada (Front Controller) y reescritura `.htaccess` | Fase 2 | `docs/fase-2/decisiones-tecnicas.md` |
| **DT-11** | Parser nativo de variables de entorno `.env` | Fase 2 | `docs/fase-2/decisiones-tecnicas.md` |
| **DT-12** | Aislamiento y enmascaramiento de errores SQL con `DatabaseException` | Fase 2 | `docs/fase-2/decisiones-tecnicas.md` |
| **DT-13** | Enrutador ligero con soporte de métodos HTTP y vistas de error | Fase 2 | `docs/fase-2/decisiones-tecnicas.md` |
| **DT-14** | Motor de plantillas nativo con layouts y escape XSS obligatorio (`e()`) | Fase 2 | `docs/fase-2/decisiones-tecnicas.md` |
| **DT-15** | Aislamiento de rutas técnicas de diagnóstico en entornos productivos | Fase 2 | `docs/fase-2/decisiones-tecnicas.md` |
| **DT-16** | Protección perimetral multicapa para archivos sensibles y storage | Fase 2 | `docs/fase-2/decisiones-tecnicas.md` |
| **DT-17** | Validación fail-fast estricta de configuración de base de datos | Fase 2 | `docs/fase-2/decisiones-tecnicas.md` |
| **DT-18** | Verificación continua y automatizada de regresión de Fase 1 | Fase 2 | `docs/fase-2/decisiones-tecnicas.md` |
| **DT-19** | Pipeline de Middlewares encadenados en el Router | Fase 3 | `docs/fase-3/decisiones-tecnicas.md` |
| **DT-20** | Revalidación autoritativa en tiempo real de usuario y rol en BD | Fase 3 | `docs/fase-3/decisiones-tecnicas.md` |
| **DT-21** | Configuración condicional de cookies de sesión según el entorno | Fase 3 | `docs/fase-3/decisiones-tecnicas.md` |
| **DT-22** | Normalización y equivalencia de roles institucionales | Fase 3 | `docs/fase-3/decisiones-tecnicas.md` |
| **DT-23** | Política de mínimo privilegio para rutas privadas RBAC | Fase 3 | `docs/fase-3/decisiones-tecnicas.md` |
| **DT-24** | Protección CSRF nativa con tokens de 32 bytes criptográficos | Fase 3 | `docs/fase-3/decisiones-tecnicas.md` |
| **DT-25** | Separación estricta Controller → Service → Repository | Fase 4 | `docs/fase-4/decisiones-tecnicas.md` |
| **DT-26** | Modelos inmutables de dominio en PHP 8.2 (`readonly`) | Fase 4 | `docs/fase-4/decisiones-tecnicas.md` |
| **DT-27** | Transaccionalidad atómica en el ciclo de vida del docente | Fase 4 | `docs/fase-4/decisiones-tecnicas.md` |
| **DT-28** | Manejo preventivo y defensivo de período escolar activo | Fase 4 | `docs/fase-4/decisiones-tecnicas.md` |
| **DT-29** | Validación estricta de capacidad entera positiva en secciones | Fase 4 | `docs/fase-4/decisiones-tecnicas.md` |
| **DT-30** | Desactivación lógica prioritaria frente a borrado físico | Fase 4 | `docs/fase-4/decisiones-tecnicas.md` |
| **DT-31** | Unicidad de asignación docente mediante columna virtual y clave compuesta | Fase 5 | `docs/fase-5/decisiones-tecnicas.md` |
| **DT-32** | Preservación histórica y cierre no destructivo en asignaciones | Fase 5 | `docs/fase-5/decisiones-tecnicas.md` |
| **DT-33** | Operación atómica de reemplazo docente con transacción ACID | Fase 5 | `docs/fase-5/decisiones-tecnicas.md` |
| **DT-34** | Inmutabilidad de claves relacionales en edición de asignación | Fase 5 | `docs/fase-5/decisiones-tecnicas.md` |
| **DT-35** | Desacoplamiento de capas Modelo-Repositorio-Servicio-Controlador | Fase 5 | `docs/fase-5/decisiones-tecnicas.md` |
| **DT-36** | Aislamiento estricto de Fase 5 sin adelantar funcionalidades | Fase 5 | `docs/fase-5/decisiones-tecnicas.md` |
| **DT-37** | Bloqueo pesimista `SELECT ... FOR UPDATE` para control de cupos | Fase 6 | `docs/fase-6/decisiones-tecnicas.md` |
| **DT-38** | Generación criptográfica y formato de código único de confirmación | Fase 6 | `docs/fase-6/decisiones-tecnicas.md` |
| **DT-39** | Desacoplamiento de entidades `Estudiante`, `Encargado` e `Inscripcion` | Fase 6 | `docs/fase-6/decisiones-tecnicas.md` |
| **DT-40** | Protección de privacidad en consulta pública y ocultamiento de IDs | Fase 6 | `docs/fase-6/decisiones-tecnicas.md` |
| **DT-41** | Arquitectura pública de inscripciones desacoplada de sesión | Fase 6 | `docs/fase-6/decisiones-tecnicas.md` |
| **DT-42** | Delimitación estricta de Fase 6 frente a comprobantes PDF (Fase 11) | Fase 6 | `docs/fase-6/decisiones-tecnicas.md` |
| **DT-43** | Derivación autoritativa de identidad docente basada exclusivamente en sesión | Fase 7 | `docs/fase-7/decisiones-tecnicas.md` |
| **DT-44** | Doble barrera de aislamiento horizontal en controladores y servicios | Fase 7 | `docs/fase-7/decisiones-tecnicas.md` |
| **DT-45** | Separación estricta de responsabilidades en el Portal Docente | Fase 7 | `docs/fase-7/decisiones-tecnicas.md` |
| **DT-46** | Minimización estricta de datos del estudiante expuestos al docente (RNF-04) | Fase 7 | `docs/fase-7/decisiones-tecnicas.md` |
| **DT-47** | Integración dinámica en tiempo real con inscripciones confirmadas | Fase 7 | `docs/fase-7/decisiones-tecnicas.md` |
| **DT-48** | Manejo defensivo de estado para docentes sin asignación activa | Fase 7 | `docs/fase-7/decisiones-tecnicas.md` |
| **DT-49** | Cálculo autoritativo de distancia geodésica exclusivamente en backend | Fase 8 | `docs/fase-8/decisiones-tecnicas.md` |
| **DT-50** | Adopción de la fórmula de Haversine con radio terrestre medio ($R = 6,371,000$ m) | Fase 8 | `docs/fase-8/decisiones-tecnicas.md` |
| **DT-51** | Marca temporal autoritativa de servidor (`CURRENT_TIMESTAMP(6)`) | Fase 8 | `docs/fase-8/decisiones-tecnicas.md` |
| **DT-52** | Inmutabilidad de marcajes y restricción física (`dentro_radio = 1`) | Fase 8 | `docs/fase-8/decisiones-tecnicas.md` |
| **DT-53** | Unicidad condicional de geocerca activa y versionado histórico | Fase 8 | `docs/fase-8/decisiones-tecnicas.md` |
| **DT-54** | Resolución de identidad docente desde sesión y aislamiento horizontal | Fase 8 | `docs/fase-8/decisiones-tecnicas.md` |
| **DT-55** | Gestión de seguridad en API Geolocation del navegador y contextos seguros | Fase 8 | `docs/fase-8/decisiones-tecnicas.md` |
| **DT-56** | Máquina de estados e inmutabilidad de estados terminales en permisos | Fase 9 | `docs/fase-9/decisiones-tecnicas.md` |
| **DT-57** | Bitácora append-only inmutable en `historial_solicitudes_permiso` | Fase 9 | `docs/fase-9/decisiones-tecnicas.md` |
| **DT-58** | Almacenamiento aislado de archivos fuera del webroot (`storage/permisos/`) | Fase 9 | `docs/fase-9/decisiones-tecnicas.md` |
| **DT-59** | Streaming seguro y control de descargas autorizadas con cabeceras estrictas | Fase 9 | `docs/fase-9/decisiones-tecnicas.md` |
| **DT-60** | Validación de archivos en doble capa: whitelist de extensiones y magic bytes | Fase 9 | `docs/fase-9/decisiones-tecnicas.md` |
| **DT-61** | Aislamiento horizontal de solicitudes de permiso por sesión activa | Fase 9 | `docs/fase-9/decisiones-tecnicas.md` |
| **DT-62** | Control de concurrencia pesimista (`SELECT ... FOR UPDATE`) en resoluciones | Fase 9 | `docs/fase-9/decisiones-tecnicas.md` |
| **DT-63** | Nombre físico aleatorio criptográfico CSPRNG (`bin2hex(random_bytes(32))`) | Fase 9 | `docs/fase-9/decisiones-tecnicas.md` |
| **DT-64** | Arquitectura de notificaciones en BD y transacción atómica (`solicitudes_permiso`) | Fase 10 | `docs/fase-10/decisiones-tecnicas.md` |
| **DT-65** | Resolución dinámica de administradores activos y formato institucional estricto | Fase 10 | `docs/fase-10/decisiones-tecnicas.md` |
| **DT-66** | Gestión de lectura, aislamiento horizontal y conteo AJAX en tiempo real | Fase 10 | `docs/fase-10/decisiones-tecnicas.md` |

---

## 12. MAPA DE ARCHIVOS POR MÓDULO FUNCIONAL

### Módulo 1: Autenticación y Control de Acceso (Fase 3)
- **Controller:** `app/Controllers/AuthController.php`
- **Service:** `app/Services/AuthService.php`
- **Repository:** `app/Repositories/UserRepositoryInterface.php`, `app/Repositories/UserRepository.php`
- **Model:** `app/Models/User.php`
- **Views:** `views/auth/login.php`
- **Tests:** `tests/run_tests_fase3.php` (T3-01 a T3-19)
- **Docs:** `docs/fase-3/`

### Módulo 2: Administración Escolar Básica (Fase 4)
- **Controllers:**  
  `app/Controllers/AdminController.php`, `AdminPeriodoController.php`, `AdminGradoController.php`, `AdminSeccionController.php`, `AdminAulaController.php`, `AdminDocenteController.php`, `AdminEstudianteController.php`
- **Services:**  
  `app/Services/PeriodoService.php`, `GradoService.php`, `SeccionService.php`, `AulaService.php`, `DocenteService.php`, `EstudianteService.php`
- **Repositories:**  
  `PeriodoRepository.php`, `GradoRepository.php`, `GradoPeriodoRepository.php`, `SeccionRepository.php`, `AulaRepository.php`, `DocenteRepository.php`, `EstudianteRepository.php`
- **Models:**  
  `PeriodoEscolar.php`, `Grado.php`, `GradoPeriodo.php`, `Seccion.php`, `Aula.php`, `Docente.php`, `Estudiante.php`
- **Views:** `views/admin/` (períodos, grados, secciones, aulas, docentes, estudiantes)
- **Tests:** `tests/run_tests_fase4.php` (T4-01 a T4-27)
- **Docs:** `docs/fase-4/`

### Módulo 3: Asignaciones Docentes (Fase 5)
- **Controller:** `app/Controllers/AdminAsignacionController.php`
- **Service:** `app/Services/AsignacionService.php`
- **Repository:** `app/Repositories/AsignacionRepositoryInterface.php`, `app/Repositories/AsignacionRepository.php`
- **Model:** `app/Models/AsignacionDocente.php`
- **Views:** `views/admin/asignaciones/` (`index.php`, `crear.php`, `editar.php`)
- **Tests:** `tests/run_tests_fase5.php` (T5-01 a T5-26), `tests/worker_asignacion_concurrente.php`
- **Docs:** `docs/fase-5/`

### Módulo 4: Inscripciones Públicas y Control de Cupos (Fase 6)
- **Controller:** `app/Controllers/InscripcionPublicaController.php`
- **Service:** `app/Services/InscripcionService.php`
- **Repositories:**  
  `app/Repositories/InscripcionRepositoryInterface.php`, `InscripcionRepository.php`, `EncargadoRepositoryInterface.php`, `EncargadoRepository.php`, `EstudianteRepositoryInterface.php`, `EstudianteRepository.php`
- **Models:** `app/Models/Inscripcion.php`, `Encargado.php`, `Estudiante.php`, `EstudianteEncargado.php`
- **Views:** `views/inscripciones/` (`index.php`, `confirmacion.php`, `consultar.php`, `resultado.php`)
- **Tests:** `tests/run_tests_fase6.php` (T6-01 a T6-30), `tests/worker_inscripcion_concurrente.php`
- **Docs:** `docs/fase-6/`

### Módulo 5: Portal Docente (Fase 7)
- **Controller:** `app/Controllers/DocenteController.php`
- **Service:** `app/Services/PortalDocenteService.php`
- **Repository:** `app/Repositories/PortalDocenteRepositoryInterface.php`, `app/Repositories/PortalDocenteRepository.php`
- **Models:** `app/Models/Docente.php`, `AsignacionDocente.php`, `Seccion.php`, `Estudiante.php`
- **Views:** `views/docente/` (`index.php`)
- **Tests:** `tests/run_tests_fase7.php` (T7-01 a T7-28)
- **Docs:** `docs/fase-7/`

### Módulo 6: Asistencia Docente y Geolocalización (Fase 8)
- **Controllers:** `app/Controllers/AdminUbicacionController.php`, `app/Controllers/DocenteAsistenciaController.php`
- **Services:** `app/Services/UbicacionService.php`, `app/Services/AsistenciaService.php`
- **Repositories:** `app/Repositories/UbicacionRepositoryInterface.php`, `UbicacionRepository.php`, `AsistenciaRepositoryInterface.php`, `AsistenciaRepository.php`
- **Models:** `app/Models/ConfiguracionUbicacion.php`, `app/Models/MarcajeAsistencia.php`
- **Client Script:** `public/js/asistencia.js`
- **Views:** `views/admin/ubicacion/` (`index.php`, `configurar.php`), `views/docente/asistencia/` (`index.php`, `historial.php`)
- **Tests:** `tests/run_tests_fase8.php` (T8-01 a T8-40), `tests/harness_geolocation.js`
- **Docs:** `docs/fase-8/`

### Módulo 7: Gestión de Permisos Docentes (Fase 9)
- **Controllers:** `app/Controllers/DocentePermisoController.php`, `app/Controllers/AdminPermisoController.php`
- **Service:** `app/Services/PermisoService.php`
- **Repository:** `app/Repositories/PermisoRepositoryInterface.php`, `app/Repositories/PermisoRepository.php`
- **Models:** `app/Models/SolicitudPermiso.php`, `app/Models/DocumentoPermiso.php`, `app/Models/HistorialSolicitudPermiso.php`
- **Views:** `views/docente/permisos/` (`index.php`, `crear.php`, `ver.php`), `views/admin/permisos/` (`index.php`, `ver.php`)
- **Storage:** `storage/permisos/` (archivos adjuntos protegidos con `.htaccess`)
- **Tests:** `tests/run_tests_fase9.php` (T9-01 a T9-46)
- **Docs:** `docs/fase-9/`

### Módulo 8: Notificaciones Administrativas (Fase 10)
- **Controller:** `app/Controllers/AdminNotificacionController.php`
- **Service:** `app/Services/NotificacionService.php`
- **Repository:** `app/Repositories/NotificacionRepositoryInterface.php`, `app/Repositories/NotificacionRepository.php`
- **Model:** `app/Models/Notificacion.php`
- **Views:** `views/admin/notificaciones/` (`index.php`)
- **Tests:** `tests/run_tests_fase10.php` (T10-01 a T10-27)
- **Docs:** `docs/fase-10/`

---

## 13. GUÍA PARA LOCALIZAR Y APLICAR CAMBIOS

| Necesidad o Tarea | Qué Archivos Revisar y Modificar Primero | Pruebas de Validación Inmediata |
|---|---|---|
| **Cambiar lógica de Login o Sesiones** | `AuthController.php`, `AuthService.php`, `Session.php`, `Authenticate.php`, `RequireRole.php` | `php tests/run_tests_fase3.php` |
| **Modificar Roles o Privilegios RBAC** | `RequireRole.php`, `AuthService.php`, `schema.sql` (tabla `roles`) | `php tests/run_tests_fase3.php` |
| **Ajustar Cupos o Inscripción Escolar** | `InscripcionService.php`, `InscripcionRepository.php`, `InscripcionPublicaController.php` | `php tests/run_tests_fase6.php` |
| **Cambiar Consulta Pública de Matrícula** | `InscripcionPublicaController.php`, `InscripcionService.php`, `views/inscripciones/consultar.php` | `php tests/run_tests_fase6.php` (T6-08, T6-24) |
| **Modificar Asignaciones de Maestros** | `AsignacionService.php`, `AsignacionRepository.php`, `AdminAsignacionController.php` | `php tests/run_tests_fase5.php` |
| **Cambiar Vista del Portal Docente** | `DocenteController.php`, `PortalDocenteService.php`, `views/docente/index.php` | `php tests/run_tests_fase7.php` |
| **Ajustar Geocerca o Fórmula Haversine** | `AsistenciaService.php`, `UbicacionService.php`, `asistencia.js`, `AdminUbicacionController.php` | `php tests/run_tests_fase8.php` |
| **Modificar Flujo o Reglas de Permisos** | `PermisoService.php`, `PermisoRepository.php`, `DocentePermisoController.php`, `AdminPermisoController.php` | `php tests/run_tests_fase9.php` |
| **Ajustar Subida o Tipos de Archivos** | `PermisoService.php` (`validarArchivoSoporte`), `Request.php` (`file`), `.htaccess` en `storage/` | `php tests/run_tests_fase9.php` (T9-09..T9-17, T9-45) |
| **Agregar o Modificar Rutas Web** | `app/Core/App.php` (método `registerRoutes`), `Router.php` | `php tests/run_tests.php` |
| **Modificar Estructura de Base de Datos** | `database/schema.sql`, `docs/fase-1/diccionario-datos.md`, entidades `app/Models/` | Ejecutar suites completas de regresión de Fases 1 a 9 |

---

## 14. POLÍTICAS TRANSVERSALES DE SEGURIDAD DEFENSIVA

1. **Sentencias Preparadas en PDO:** Cero concatenación directa de variables en consultas SQL. Todo parámetro dinámico pasa a través de `:placeholders` vinculados de forma segura.
2. **Criptografía de Contraseñas:** Uso exclusivo de `password_hash()` con `PASSWORD_BCRYPT` y verificación mediante `password_verify()`. Cero almacenamiento en texto plano o funciones débiles (MD5/SHA1).
3. **Manejo Seguro de Sesiones:** Sesiones PHP configuradas con `use_strict_mode=1`, `cookie_httponly=1`, `cookie_samesite=Lax` y regeneración periódica obligatoria tras autenticación (`session_regenerate_id(true)`).
4. **Protección CSRF Obligatoria:** Tokens de 32 bytes aleatorios criptográficos generados por `Csrf.php`, integrados en formularios mediante `csrf_field()` y validados estrictamente por `VerifyCsrfToken` en todo método mutante (`POST`).
5. **Control de Acceso Basado en Roles (RBAC):** Middleware `RequireRole` que valida en tiempo real la vigencia de la cuenta y el rol del usuario directamente contra la base de datos (rechaza privilegios congelados en sesión).
6. **Whitelist de Parámetros y Descarte de Entrada Maliciosa:** Los controladores y servicios extraen únicamente los campos autorizados. Parámetros manipulados desde el cliente (como IDs de docente forzados o marcas de tiempo falsificadas) son descartados por diseño.
7. **Mitigación de Cross-Site Scripting (XSS):** Todo valor emitido en plantillas hacia el HTML debe pasar por la función `e()` (`htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`).
8. **Protección Perimetral y Variables de Entorno:** El archivo `.env` reside fuera del document root y jamás se incluye en el control de versiones. Directivas `.htaccess` con `Require all denied` protegen `storage/`, `config/`, `database/` y `docs/`.
9. **Almacenamiento y Streaming Seguro de Archivos:**
   - Subida exclusivamente fuera del webroot (`storage/permisos/`).
   - Detección de tipo MIME real por magic bytes mediante `finfo(FILEINFO_MIME_TYPE)`.
   - Nombres físicos criptográficamente aleatorios de 64 caracteres hexadecimales generados por CSPRNG (`bin2hex(random_bytes(32))`), mitigando Parameter Tampering y Path Traversal.
   - Streaming de descarga con cabeceras estrictas (`X-Content-Type-Options: nosniff`, `Content-Disposition: attachment`, `Cache-Control: private, no-cache, no-store`).
10. **Marcas Temporales Autoritativas:** Toda fecha/hora de auditoría (asistencia, cambios de estado en permisos, transacciones) se genera directamente en el motor de base de datos (`CURRENT_TIMESTAMP(6)` con microsegundos). Se prohíbe utilizar la hora del dispositivo cliente.
11. **Aislamiento Horizontal Estricto:** La identidad del usuario se deriva exclusivamente de la sesión segura del servidor (`auth_user_id`). Se prohíbe que un usuario acceda o modifique registros de otro docente o sección mediante alteración de IDs en parámetros GET o POST.
12. **Cero Confianza en el Cliente:** Toda validación realizada en JavaScript o en el navegador es meramente cosmética; la totalidad de reglas de negocio, cálculos geodésicos y límites de capacidad se computan y validan estrictamente en el backend.

---

## 15. GESTIÓN DE GIT Y GITHUB

- **Rama principal:** `main`
- **Remoto configurado:** `origin`
- **URL oficial:** `https://github.com/kyolp/Seminario-Tecnologias-LoDeFuentes.git`
- **Política de Operación:**
  - El asistente de desarrollo (Antigravity) trabaja sobre el árbol local y genera commits atómicos bien documentados respetando conventional commits (ej. `docs(fase-9): ...`, `test(fase-8): ...`, `fix(...): ...`).
  - **PROHIBIDO EL PUSH AUTOMÁTICO:** Antigravity **NUNCA** ejecutará `git push`. La sincronización hacia GitHub es decisión y responsabilidad manual del usuario.
  - El árbol de trabajo (`working tree`) debe mantenerse limpio (`clean`) al finalizar cada tarea.

---

## 16. ESTADO ACTUAL DEL PROYECTO

- **Última fase completada y validada:** **FASE 9 — GESTIÓN DE PERMISOS DOCENTES** (Con corrección documental final y suite de 46/46 pruebas aprobadas).
- **Siguiente fase pendiente de inicio:** **FASE 10 — MÓDULO DE NOTIFICACIONES**.
- **Requisito funcional pendiente inmediato:** **RF-26 (Notificación de permiso)**.
- **Último commit registrado tras cierre documental de Fase 9:** `19eeae9` (*"docs(fase-9): corregir denominaciones oficiales RF-18 a RF-23 en trazabilidad"*).

---

## 17. ALCANCES PENDIENTES Y HOJA DE RUTA FUTURA

El desarrollo futuro debe ajustarse estrictamente a las fases planificadas, sin adelantar código ni invadir módulos posteriores:

1. **Fase 10 — Módulo de Notificaciones:** Implementación del canal de alertas internas para administración ante nuevas solicitudes de permiso (**RF-26**). Poblado funcional de la tabla `notificaciones`.
2. **Fase 11 — Comprobantes PDF:** Generación en el servidor de comprobantes oficiales de inscripción en formato PDF para los encargados (**RF-13**).
3. **Fase 12 — Reportes y Exportación:** Generación y exportación de reportes de asistencia docente por rango de fechas (**RF-29**) y consulta administrativa de inscripciones y ocupación de cupos (**RF-30**).
4. **Fase 13 — Recuperación de Contraseñas:** Flujo de restablecimiento seguro de credenciales mediante tokens temporales y envío de enlaces seguros (**RF-03**).
5. **Fase 14 — Endurecimiento Integral de Seguridad y Privacidad:** Validación integral de **RNF-04** (Privacidad global) y **RNF-06** (Auditoría transversal y bitácoras globales), políticas de cabeceras HTTP (CSP, HSTS) y pruebas de penetración defensiva.
6. **Fase 15 — Pruebas Integrales del Sistema:** Cobertura de extremo a extremo (E2E) de los 32 requerimientos funcionales y verificación de usabilidad y compatibilidad (**RNF-01**, **RNF-02**).
7. **Fase 16 — Cierre, Entrega y Documentación Final:** Manual de usuario, manual técnico de despliegue y entrega final.

> [!WARNING]
> **Reescritura Obligatoria de `README.md` (Fase 16):**  
> El archivo `README.md` actual en la raíz del proyecto proviene de la Fase 0 preliminar y presenta una redacción anticuada semejante a un prompt generado automáticamente. **Deberá ser completamente reescrito y modernizado al llegar a la Fase 16** como parte de la entrega institucional definitiva.

---

## 18. PROTOCOLO PARA FUTURAS SESIONES DE DESARROLLO

Cualquier agente, desarrollador o auditor que retome el proyecto debe seguir obligatoriamente este protocolo de 9 pasos:

1. **Paso 1 — Lectura Inicial:** Leer este archivo (`docs/CONTEXTO_MAESTRO_PROYECTO.md`) para orientarse rápidamente en el estado actual, reglas y estructura.
2. **Paso 2 — Identificar el Módulo Afectado:** Determinar con precisión a qué fase y módulo pertenece la tarea solicitada (consultar la sección *Matriz Resumida de Requisitos*).
3. **Paso 3 — Consultar el Mapa de Archivos:** Ubicar en la sección *Mapa de Archivos por Módulo* los controladores, servicios, repositorios, modelos y vistas pertinentes.
4. **Paso 4 — Apertura Quirúrgica de Archivos:** Abrir únicamente los archivos estrictamente necesarios para la modificación. Evitar abrir o alterar componentes de fases ajenas.
5. **Paso 5 — Revisar la Documentación de la Fase:** Consultar la carpeta `docs/fase-X/` correspondiente para comprender las decisiones técnicas (`DT-XX`) y reglas institucionales aplicadas.
6. **Paso 6 — Consultar Persistencia:** Revisar `database/schema.sql` y el diccionario de datos únicamente si la tarea involucra columnas o relaciones relacionales.
7. **Paso 7 — Ejecutar Pruebas del Módulo:** Correr la suite PHP CLI del módulo modificado (ej. `php tests/run_tests_faseX.php`) para comprobar que el 100% de casos pase en verde.
8. **Paso 8 — Ejecutar Regresiones:** Ejecutar las suites de pruebas de las fases previas relacionadas para garantizar que no existan regresiones colaterales.
9. **Paso 9 — Actualizar Documentación:** Registrar los cambios en la matriz de trazabilidad y en el informe de la fase correspondiente, actualizando este contexto maestro si se introducen nuevas decisiones técnicas o componentes arquitectónicos.

---

## 19. POLÍTICA DE MANTENIMIENTO DE ESTE ARCHIVO

Para preservar la concisión y utilidad de este documento como brújula rápida del repositorio:

- **Cuándo actualizar este archivo:**
  - Al culminar y cerrar formalmente una fase del proyecto.
  - Cuando se agregue un nuevo módulo, controlador o servicio arquitectónico principal.
  - Cuando se introduzca una nueva Decisión Técnica de alto impacto (`DT-XX`).
  - Cuando cambie una relación relacional entre tablas de la base de datos.
  - Cuando cambie el estado de un requisito funcional o no funcional (**RF / RNF**).
  - Cuando se incorpore una nueva suite de pruebas automatizadas al acumulado.
- **Cuándo NO actualizar este archivo:**
  - Por correcciones ortográficas, de redacción o estilo en vistas o documentación.
  - Por ajustes menores de CSS, márgenes o colores.
  - Por refactorizaciones internas de código que no modifiquen contratos de interfaces ni flujos de datos.
  - Para vaciar bitácoras de depuración temporal o informes circunstanciales de auditoría.
