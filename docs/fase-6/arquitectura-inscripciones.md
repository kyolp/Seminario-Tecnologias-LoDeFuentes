# Arquitectura del Módulo de Inscripciones y Cupos — Fase 6

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 6 — Inscripciones y Cupos  
**Fecha:** Septiembre 2026  

---

## 1. Visión General de la Arquitectura

El módulo de **Inscripciones y Cupos** proporciona a la comunidad escolar (especialmente a padres, madres y tutores legales) una interfaz pública accesible en línea para solicitar la matrícula de estudiantes sin requerir autenticación previa.

La arquitectura sigue el patrón establecido en las fases previas del proyecto: **Controller → Service → Repository → PDO / MySQL InnoDB**, garantizando una estricta separación de responsabilidades, alta comprobabilidad y apego a las directivas de seguridad web.

```mermaid
flowchart TD
    subgraph Cliente ["Cliente Web (Público)"]
        Browser["Navegador Web"]
    end

    subgraph Presentacion ["Capa de Presentación"]
        Router["App\\Core\\Router"]
        CSRF["VerifyCsrfToken Middleware"]
        Controller["InscripcionPublicaController"]
        Views["Vistas PHP (nueva, exito, consultar)"]
    end

    subgraph Negocio ["Capa de Lógica de Negocio"]
        Service["InscripcionService"]
        DomainModels["Modelos: Inscripcion, Encargado, EstudianteEncargado"]
    end

    subgraph Datos ["Capa de Persistencia"]
        RepoInsc["InscripcionRepository"]
        RepoEnc["EncargadoRepository"]
        RepoEst["EstudianteRepository"]
        RepoSec["SeccionRepository"]
        Database["App\\Core\\Database (PDO)"]
    end

    subgraph MotorBD ["Motor MySQL 8.0 (InnoDB)"]
        T_Insc["inscripciones"]
        T_Est["estudiantes"]
        T_Enc["encargados"]
        T_Vin["estudiantes_encargados"]
        T_Sec["secciones"]
    end

    Browser -->|HTTP GET / POST| Router
    Router --> CSRF
    CSRF --> Controller
    Controller --> Service
    Controller --> Views
    Service --> DomainModels
    Service --> RepoInsc
    Service --> RepoEnc
    Service --> RepoEst
    Service --> RepoSec
    RepoInsc --> Database
    RepoEnc --> Database
    RepoEst --> Database
    RepoSec --> Database
    Database --> MotorBD
```

---

## 2. Componentes de la Arquitectura

### 2.1 Modelos de Dominio (`app/Models/`)

1. **`Inscripcion.php`**:
   - Entidad inmutable con propiedades `readonly` tipadas estrictamente.
   - Representa el registro de matrícula en `inscripciones` (`id_inscripcion`, `id_estudiante`, `id_encargado`, `id_seccion`, `codigo_confirmacion`, `estado`, `fecha_inscripcion`, `fecha_confirmacion`).
   - Incluye campos informativos calculados a partir de los `JOIN` relacionales (`estudiante_nombres`, `estudiante_apellidos`, `codigo_estudiante`, `encargado_nombres`, `encargado_apellidos`, `parentesco`, `seccion_codigo`, `seccion_nombre`, `grado_nombre`, `periodo_nombre`).
   - Métodos auxiliares de conveniencia: `isConfirmada()`, `isPendiente()`, `getEstudianteNombreCompleto()`, `getEncargadoNombreCompleto()`.

2. **`Encargado.php`**:
   - Representa al tutor legal registrado en la tabla `encargados`.
   - Propiedades: `id_encargado`, `nombres`, `apellidos`, `telefono`, `correo`, `fecha_creacion`, `fecha_actualizacion`.
   - Métodos auxiliares: `getNombreCompleto()`, `toArray()`, `fromArray()`.

3. **`EstudianteEncargado.php`**:
   - Representa la relación asociativa N:M en `estudiantes_encargados`.
   - Propiedades: `id_estudiante_encargado`, `id_estudiante`, `id_encargado`, `parentesco`, `es_principal`.
   - Métodos auxiliares: `isPrincipal()`, `toArray()`, `fromArray()`.

---

### 2.2 Repositorios de Persistencia (`app/Repositories/`)

1. **`InscripcionRepositoryInterface.php` y `InscripcionRepository.php`**:
   - `findById(int $id): ?Inscripcion`: Consulta un registro por llave primaria con `JOIN` a todas las entidades vinculadas.
   - `findByCodigoConfirmacion(string $codigo): ?Inscripcion`: Búsqueda exacta por código único institucional.
   - `findVigenteByEstudianteAndSeccion(int $idEstudiante, int $idSeccion): ?Inscripcion`: Detección de matrículas activas previas.
   - `countOcupacionBySeccion(int $idSeccion): int`: Conteo de inscripciones vigentes (`CONFIRMADA` o `PENDIENTE`).
   - `lockSeccionForUpdate(int $idSeccion): ?array`: Adquisición de bloqueo exclusivo a nivel de fila (`SELECT ... FOR UPDATE`) en InnoDB.
   - `create(array $data): int`: Inserción controlada que omite estrictamente las columnas generadas almacenadas `estudiante_vigente` y `seccion_vigente`.
   - `existsCodigo(string $codigo): bool`: Verificación rápida de colisión para el generador aleatorio.
   - `findAll(int $limit, int $offset): array`: Listado paginado para auditoría.

2. **`EncargadoRepositoryInterface.php` y `EncargadoRepository.php`**:
   - `findById(int $id): ?Encargado`: Búsqueda por ID.
   - `findMatching(string $nombres, string $apellidos): ?Encargado`: Reutilización de encargados existentes.
   - `create(array $data): int`: Registro de nuevos encargados.
   - `linkEstudiante(int $idEstudiante, int $idEncargado, string $parentesco, bool $esPrincipal): int`: Registro del vínculo formal en `estudiantes_encargados`.
   - `hasVinculo(int $idEstudiante, int $idEncargado): bool`: Comprobación de existencia del vínculo.
   - `findVinculo(int $idEstudiante, int $idEncargado): ?EstudianteEncargado`: Consulta del vínculo con detalles de parentesco.

---

### 2.3 Capa de Servicio (`app/Services/InscripcionService.php`)

El servicio orquesta la lógica de negocio y las reglas de validación académica:

- **`obtenerPeriodoInscripcion()`**: Localiza el ciclo lectivo con estado `'ACTIVO'` o `'PLANIFICADO'` que no se encuentre cerrado.
- **`obtenerGradosHabilitados(int $idPeriodo)`**: Consulta únicamente los grados con `grados_periodos.habilitado = 1`.
- **`obtenerSeccionesDisponibles(int $idGradoPeriodo, bool $soloDisponibles)`**: Calcula para cada sección habilitada su ocupación actual (`COUNT(*)`) y sus cupos restantes (`capacidad - ocupacion`). Filtra secciones llenas cuando se invoca para el formulario público (RN-DOC-02).
- **`generarCodigoConfirmacion(string $prefijo)`**: Genera códigos no secuenciales `INS-YYYY-XXXXXXXX` con entropía criptográfica fuerte y bucle de reintento.
- **`procesarInscripcionPublica(array $input)`**: Orquestador indivisible de matrícula ejecutado bajo transacción ACID con aislamiento y bloqueo pesimista.
- **`consultarInscripcionPorCodigo(string $codigo)`**: Consulta saneada que proyecta exclusivamente datos seguros sin exponer claves primarias.

---

### 2.4 Controlador Público (`app/Controllers/InscripcionPublicaController.php`)

Controlador sin requerimiento de autenticación expuesto en Internet:

- **`createForm(Request $request): Response`**: Renderiza el formulario público de inscripción con el ciclo escolar vigente y los grados habilitados.
- **`getSeccionesDisponibles(Request $request): Response`**: Endpoint API JSON que alimenta dinámicamente el selector de secciones vía JavaScript según el grado seleccionado, excluyendo secciones sin cupos disponibles.
- **`store(Request $request): Response`**: Procesa la solicitud `POST`. Aplica lista blanca de atributos (*whitelisting*), invoca al servicio y aplica el patrón Post/Redirect/Get (PRG) redirigiendo a la pantalla de éxito con `HTTP 302`.
- **`success(Request $request): Response`**: Muestra la constancia digital con el código de confirmación obtenido.
- **`consultarForm(Request $request): Response`**: Renderiza la interfaz de búsqueda por código.
- **`consultar(Request $request): Response`**: Procesa la consulta del comprobante y muestra la ficha con los datos del estudiante inscrito.

---

## 3. Diagrama de Secuencia: Inscripción Pública y Control de Cupos

```mermaid
sequenceDiagram
    autonumber
    actor Tutor as Padre / Encargado
    participant Browser as Navegador Web
    participant Router as Router / CSRF
    participant Ctrl as InscripcionPublicaController
    participant Serv as InscripcionService
    participant RepoSec as SeccionRepository
    participant RepoInsc as InscripcionRepository
    participant DB as MySQL InnoDB

    Tutor->>Browser: Completa formulario y presiona "Confirmar Inscripción"
    Browser->>Router: POST /inscripcion (con _token CSRF y datos)
    Router->>Router: Verificar token CSRF (VerifyCsrfToken)
    Router->>Ctrl: store(request)
    Ctrl->>Ctrl: Filtrar parámetros con lista blanca (Whitelisting)
    Ctrl->>Serv: procesarInscripcionPublica(payload)
    
    Serv->>DB: BEGIN TRANSACTION
    
    Note over Serv,DB: Bloqueo pesimista para serializar transacciones concurrentes
    Serv->>RepoInsc: lockSeccionForUpdate(idSeccion)
    RepoInsc->>DB: SELECT * FROM secciones WHERE id_seccion = ? FOR UPDATE
    DB-->>RepoInsc: Fila bloqueada exclusivamente
    
    Serv->>Serv: Verificar habilitación de sección y estado de período
    Serv->>RepoInsc: countOcupacionBySeccion(idSeccion)
    RepoInsc->>DB: SELECT COUNT(*) WHERE estado IN ('CONFIRMADA', 'PENDIENTE')
    DB-->>Serv: Ocupación actual
    
    alt Ocupación >= Capacidad
        Serv->>DB: ROLLBACK
        Serv-->>Ctrl: [success: false, message: "No hay cupos disponibles"]
        Ctrl-->>Browser: HTTP 422 con errores en formulario
    else Hay cupos disponibles (Ocupación < Capacidad)
        Serv->>Serv: Identificar o crear Estudiante
        Serv->>Serv: Identificar o crear Encargado
        Serv->>Serv: Registrar vínculo N:M en estudiantes_encargados
        Serv->>Serv: Generar código seguro INS-YYYY-XXXXXXXX
        Serv->>RepoInsc: create(inscripcion CONFIRMADA)
        RepoInsc->>DB: INSERT INTO inscripciones (...)
        Serv->>DB: COMMIT
        Serv-->>Ctrl: [success: true, codigo: "INS-2026-...", id: ...]
        Ctrl->>Browser: HTTP 302 Redirect (/inscripcion/exito?codigo=...)
        Browser->>Ctrl: GET /inscripcion/exito?codigo=...
        Ctrl-->>Browser: HTTP 200 (Renderiza comprobante con código)
    end
```

---

## 4. Diagrama de Secuencia: Consulta Pública por Código

```mermaid
sequenceDiagram
    autonumber
    actor Usuario as Padre / Consultor
    participant Browser as Navegador Web
    participant Ctrl as InscripcionPublicaController
    participant Serv as InscripcionService
    participant RepoInsc as InscripcionRepository
    participant DB as MySQL InnoDB

    Usuario->>Browser: Ingresa código INS-2026-XXXXXXXX y presiona "Consultar"
    Browser->>Ctrl: POST /inscripcion/consultar (con _token y código)
    Ctrl->>Serv: consultarInscripcionPorCodigo(codigo)
    Serv->>RepoInsc: findByCodigoConfirmacion(codigo)
    RepoInsc->>DB: SELECT con JOIN a estudiantes, encargados, secciones, grados
    DB-->>RepoInsc: Tupla relacional
    
    alt Código no existe en BD
        Serv-->>Ctrl: [success: false, message: "No se encontró ninguna inscripción..."]
        Ctrl-->>Browser: HTTP 200 con alerta informativa institucional
    else Código encontrado
        Serv->>Serv: Sanitizar respuesta (descartar IDs primarios, hashes, llaves foráneas)
        Serv-->>Ctrl: [success: true, data: { estudiante, grado, seccion, encargado, estado }]
        Ctrl-->>Browser: HTTP 200 con ficha segura de inscripción
    end
```
