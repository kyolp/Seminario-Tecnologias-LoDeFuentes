# Sistema Web — Escuela Oficial Rural Mixta Lo de Fuentes

Plataforma web integral para la gestión académica, control de inscripciones con cupos en tiempo real, asignación de secciones, gestión de permisos docentes y registro geolocalizado de asistencia docente para la Escuela Oficial Rural Mixta Lo de Fuentes (Zona 11 de Mixco, Guatemala).

---

## Funcionalidades Principales

- **Gestión Académica e Institucional:** Control de ciclos escolares, catálogo jerárquico de grados (Primero a Sexto Primaria), secciones (A y B) y administración de aulas físicas.
- **Administración de Personal y Estudiantes:** Expedientes de docentes, alumnos y tutores/encargados legales con vínculos familiares documentados.
- **Asignaciones Académicas:** Asignación de docentes a secciones y aulas físicas por período lectivo, garantizando unicidad y registro histórico inmutable.
- **Inscripciones Públicas y Control de Cupos:** Módulo web público para registro de inscripciones, cálculo de cupos en tiempo real bajo concurrencia, bloqueo automático ante sección llena y generación de código único de confirmación.
- **Comprobante de Inscripción Oficial:** Generación al vuelo de comprobantes individuales de inscripción en formato PDF para los encargados de familia.
- **Portal Docente:** Panel personalizado para docentes donde consultan sus secciones asignadas, nómina de alumnos inscritos, marcaje de asistencia y solicitudes de permisos laborales.
- **Control de Asistencia con Geocerca:** Registro de entrada y salida docente validado geodésicamente (fórmula de Haversine) contra la geocerca institucional oficial de la escuela.
- **Gestión de Permisos y Notificaciones:** Flujo de solicitudes de permiso con documentos adjuntos (PDF/imágenes), alertas automáticas a la administración y bandeja de resolución con historial inmutable.
- **Reportes Institucionales:** Reportes administrativos con filtros por docente, período, grado, sección y rango de fechas para asistencia e inscripciones.
- **Seguridad y Control de Acceso:** Control de acceso basado en roles (RBAC), protección estricta contra CSRF, XSS, inyección SQL (100% sentencias preparadas PDO), hardening de sesiones y cabeceras HTTP de seguridad.

---

## Tecnologías Utilizadas

- **Backend:** PHP 8.2 (Arquitectura limpia por capas: Controladores, Servicios, Repositorios y Modelos con PDO).
- **Base de Datos:** MySQL 8.0 (20 tablas relacionales, integridad referencial, transacciones ACID InnoDB y columnas generadas).
- **Frontend:** HTML5 semántico, CSS3 modular responsivo y JavaScript moderno estándar (sin dependencias externas).
- **Generación de Documentos:** FPDF 1.86 (integrado localmente para generación de comprobantes PDF).

---

## Requisitos del Sistema

- PHP 8.2 o superior con extensiones activas: `pdo`, `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`.
- Servidor de base de datos MySQL 8.0 o MariaDB equivalente.
- Servidor web Apache con `mod_rewrite` habilitado, Nginx o el servidor web embebido de PHP para desarrollo.

---

## Instalación y Puesta en Marcha

### 1. Clonar o descargar el repositorio
Ubicar los archivos en el directorio de trabajo del servidor web.

### 2. Configurar variables de entorno
Copiar el archivo de plantilla `.env.example` como `.env` y configurar los parámetros de conexión correspondientes a su entorno local:

```bash
cp .env.example .env
```

Configurar los parámetros de base de datos:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=escuela_lo_de_fuentes
DB_USERNAME=su_usuario_mysql
DB_PASSWORD=su_contrasena_mysql
```

### 3. Inicializar la Base de Datos
Crear la base de datos e importar el esquema canónico y los datos iniciales de demostración:

```bash
mysql -u su_usuario -p -e "CREATE DATABASE escuela_lo_de_fuentes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u su_usuario -p escuela_lo_de_fuentes < database/schema.sql
mysql -u su_usuario -p escuela_lo_de_fuentes < database/seed.sql
```

Alternativamente, para cargar el volcado completo de demostración:
```bash
mysql -u su_usuario -p escuela_lo_de_fuentes < database/final_demo_2026.sql
```

### 4. Ejecutar el Servidor
Iniciar la aplicación mediante el servidor embebido de PHP apuntando al directorio público:

```bash
php -S 127.0.0.1:8000 -t public
```

Acceder desde el navegador a `http://127.0.0.1:8000`.

---

## Estructura del Proyecto

```text
├── app/                  # Núcleo de la aplicación
│   ├── Controllers/      # Controladores HTTP (Admin, Docente, Público, Auth)
│   ├── Core/             # Router, Request, Response, View, Database, Logger, Csrf
│   ├── Middleware/       # Control de roles (RequireRole), autenticación y CSRF
│   ├── Models/           # Entidades del dominio
│   ├── Repositories/     # Acceso a datos y persistencia con PDO
│   └── Services/         # Lógica de negocio y reglas institucionales
├── config/               # Archivos de configuración (app, database)
├── database/             # Esquema relacional (schema.sql), datos demo (seed.sql) y respaldos
├── docs/                 # Documentación técnica, matrices de requisitos y cierre
├── public/               # Document root público: index.php, recursos CSS y JS
├── storage/              # Almacenamiento privado de adjuntos y logs del sistema
├── tests/                # Suites de pruebas automatizadas unitarias, de integración y regresión
└── views/                # Vistas y plantillas del sistema
```

---

## Verificación y Pruebas Automatizadas

El sistema cuenta con una cobertura integral de pruebas que validan los requisitos funcionales (RF-01 a RF-32) y no funcionales (RNF-01 a RNF-10).

Para ejecutar la verificación completa del sistema:

```bash
# Validación funcional integral y compatibilidad
php tests/run_tests_fase15.php

# Validación final de integridad de datos y cierre
php tests/run_tests_fase16.php
```

---

## Estado del Proyecto

- **Fases del proyecto:** Fases 0 a 16 completamente implementadas, verificadas y documentadas.
- **Alcance funcional:** 100% de requisitos funcionales (RF-01 a RF-32) validados.
- **Requisitos de calidad:** 100% de requisitos no funcionales (RNF-01 a RNF-10) cubiertos.
- **Base de datos:** Datos de demostración limpios, consistentes y representativos del contexto escolar guatemalteco.
