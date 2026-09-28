# Estructura del Proyecto — Fase 2

## Propósito

Definir la organización física y lógica de directorios y componentes técnicos de la aplicación web para la **Escuela Oficial Rural Mixta Lo de Fuentes**, respetando la separación arquitectónica por capas:

```text
Presentación
    ↓
Controladores
    ↓
Servicios / Lógica de negocio (preparada para Fase 3+)
    ↓
Repositorios / Acceso a datos (preparada para Fase 3+)
    ↓
Capa de Conexión PDO / MySQL
```

## Árbol de directorios implementado

```text
ProyectoFinal/
├── app/
│   ├── Controllers/
│   │   ├── BaseController.php      # Controlador base con datos comunes y helpers
│   │   ├── HomeController.php      # Controlador para inicio y estado del sistema
│   │   └── TestController.php      # Controlador para pruebas técnicas GET/POST
│   ├── Core/
│   │   ├── App.php                 # Orquestador del ciclo de vida y captura de errores
│   │   ├── Config.php              # Repositorio de configuración con notación de puntos
│   │   ├── Controller.php          # Clase base abstracta de controladores
│   │   ├── Database.php            # Capa centralizada de conexión PDO MySQL
│   │   ├── DatabaseException.php   # Excepción que enmascara secretos y credenciales
│   │   ├── Env.php                 # Parser seguro de variables de entorno (.env)
│   │   ├── Logger.php              # Registro seguro de logs sin exponer secretos
│   │   ├── Request.php             # Abstracción de la solicitud HTTP
│   │   ├── Response.php            # Abstracción de la respuesta HTTP
│   │   ├── Router.php              # Enrutador HTTP para métodos GET y POST
│   │   └── View.php                # Motor de renderizado con layouts
│   ├── Helpers/
│   │   └── helpers.php             # Funciones globales (e, url, redirect, config, view)
│   ├── Middleware/
│   │   └── MiddlewareInterface.php # Interfaz base para control de acceso (Fase 3+)
│   ├── Models/
│   │   └── .gitkeep                # Reservado para entidades de dominio (Fase 3+)
│   ├── Repositories/
│   │   └── .gitkeep                # Reservado para repositorios de datos (Fase 3+)
│   ├── Services/
│   │   └── .gitkeep                # Reservado para servicios de negocio (Fase 3+)
│   ├── autoload.php                # Autocargador PSR-4 nativo para App\
│   └── bootstrap.php               # Inicialización y arranque centralizado
├── config/
│   ├── app.php                     # Configuración de nombre, entorno, debug y URL
│   └── database.php                # Configuración de conexión MySQL PDO
├── database/                       # Artefactos aprobados de Fase 1
│   ├── schema.sql                  # Esquema DDL físico (20 tablas)
│   ├── seed.sql                    # Datos de prueba ficticios
│   └── tests/
│       └── validacion.sql          # Procedimiento de validación de base de datos
├── docs/
│   ├── fase-0/                     # Diagnóstico y matriz de trazabilidad base
│   ├── fase-1/                     # Documentación de diseño y base de datos
│   └── fase-2/                     # Documentación de estructura base (esta fase)
├── public/                         # Raíz pública web (Front Controller)
│   ├── .htaccess                   # Reescritura Apache hacia index.php
│   ├── index.php                   # Punto de entrada único a la aplicación
│   ├── css/
│   │   └── app.css                 # Estilos visuales institucionales base
│   ├── js/
│   │   └── app.js                  # JavaScript base para el cliente
│   └── img/
│       └── .gitkeep                # Recursos gráficos e institucionales
├── storage/
│   └── logs/
│       └── .gitkeep                # Registro de logs locales (ignorado por Git)
├── tests/
│   └── run_tests.php               # Suite de pruebas automatizadas T2-01 a T2-12
├── .env.example                    # Plantilla de configuración sin credenciales
├── .gitignore                      # Exclusiones de Git (.env, logs, temporales)
└── README.md                       # Documentación inicial del proyecto
```

## Responsabilidad de cada componente

| Directorio / Archivo | Responsabilidad técnica |
|---|---|
| `public/index.php` | Punto de entrada único. Carga el arranque, recibe la petición y emite la respuesta. |
| `app/bootstrap.php` | Define constantes de rutas, carga autoloader, helpers, entorno y configuración. |
| `app/autoload.php` | Autocarga de clases bajo el estándar PSR-4 sin dependencias externas. |
| `app/Core/` | Núcleo del framework MVC ligero (App, Router, Request, Response, Database, View). |
| `app/Controllers/` | Receptores de solicitudes que delegan a capas inferiores y devuelven respuestas. |
| `config/` | Archivos de configuración desacoplados del código fuente. |
| `views/layouts/` | Plantilla estructural común (`main.php`) para consistencia visual institucional. |
| `views/errors/` | Respuestas controladas para 404, 500 y errores de base de datos protegidos. |
| `storage/logs/` | Destino de bitácoras de eventos y errores con saneamiento de secretos. |
| `tests/run_tests.php` | Validador automatizado CLI de la infraestructura técnica base. |
