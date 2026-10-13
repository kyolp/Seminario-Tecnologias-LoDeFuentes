# Sistema Web — Escuela Oficial Rural Mixta Lo de Fuentes

Este proyecto es un sistema web desarrollado para apoyar la gestión académica y administrativa de la Escuela Oficial Rural Mixta Lo de Fuentes, ubicada en la 13 Calle, Lo de Fuentes, Zona 11 de Mixco, Guatemala.

El sistema organiza los procesos de inscripción estudiantil, control automático de cupos, asignación de secciones y registro geolocalizado de asistencia docente.

---

## Funcionalidades principales

- **Administración académica:** Gestión de períodos escolares (ciclos), grados educativos, secciones y aulas físicas.
- **Gestión docente y estudiantil:** Catálogo de docentes y expedientes básicos de estudiantes y encargados.
- **Asignaciones docentes:** Vinculación de docentes con grados, secciones y aulas por ciclo escolar.
- **Inscripciones y control de cupos:** Formulario web público para inscripciones con cálculo en tiempo real de disponibilidad por sección, bloqueo ante cupo lleno y generación de código de confirmación.
- **Comprobante en PDF:** Generación al vuelo de comprobantes individuales de inscripción en formato PDF para los encargados.
- **Portal docente:** Consulta de secciones y estudiantes asignados, marcaje de asistencia y solicitudes de permisos laborales con archivos de respaldo.
- **Control de asistencia y geolocalización:** Marcaje de entrada y salida con validación de proximidad geográfica respecto a la ubicación institucional del centro educativo.
- **Gestión de permisos y notificaciones:** Bandeja administrativa para revisión y resolución de permisos docentes, con alertas y notificaciones internas del sistema.
- **Reportes institucionales:** Consultas administrativas de marcajes de asistencia por docente y período, así como balance de ocupación de cupos e inscripciones por grado y sección.

---

## Tecnologías utilizadas

- **PHP 8.2** (Arquitectura limpia en capas: Controlador, Servicio y Repositorio con PDO)
- **MySQL 8.0** (Base de datos relacional con restricciones de integridad, transacciones y claves foráneas)
- **HTML5, CSS3 y JavaScript** (Interfaz web responsiva sin frameworks externos pesados)
- **FPDF 1.86** (Generación local de documentos PDF en memoria)

---

## Requisitos del entorno

- Servidor web Apache con módulo `mod_rewrite` habilitado (o PHP Built-in Server).
- PHP 8.2 o superior con extensiones activas: `pdo`, `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`.
- MySQL 8.0 o MariaDB equivalente.

---

## Instalación y configuración

1. **Clonar o descargar el repositorio:**
   Ubicar el proyecto en el directorio de trabajo o en el document root del servidor web (por ejemplo, `htdocs` en XAMPP).

2. **Configurar variables de entorno:**
   Copiar el archivo de ejemplo a `.env` y ajustar los parámetros de conexión:
   ```bash
   cp .env.example .env
   ```
   Configurar los valores de base de datos (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_PORT`).

3. **Cargar la base de datos:**
   Importar la estructura relacional y los datos iniciales en MySQL:
   ```bash
   mysql -u root -p nombre_base_datos < database/schema.sql
   mysql -u root -p nombre_base_datos < database/seed.sql
   ```

4. **Iniciar la aplicación:**
   Se puede servir mediante Apache o utilizando el servidor embebido de PHP desde la raíz del proyecto:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```
   Acceder desde el navegador a `http://127.0.0.1:8000`.

---

## Estructura del repositorio

```text
├── app/            # Controladores, Servicios, Repositorios, Modelos, Middleware y Núcleo
├── config/         # Configuraciones generales de base de datos y aplicación
├── database/       # Definición de esquema relacional (schema.sql) y datos iniciales (seed.sql)
├── docs/           # Documentación por fases, matriz de trazabilidad y decisiones técnicas
├── public/         # Punto de entrada público (index.php), hojas de estilo CSS y scripts JS
├── storage/        # Almacenamiento privado para respaldos de permisos (fuera del document root)
├── tests/          # Suites de pruebas automatizadas y regresiones por fase
└── views/          # Plantillas y vistas de administración, docentes e inscripciones
```

---

## Pruebas automatizadas

El proyecto cuenta con suites de pruebas automatizadas para verificar la integridad funcional, validaciones de seguridad (CSRF, XSS, RBAC, inyección SQL) y reglas de negocio. Para ejecutarlas:

```bash
php tests/run_tests_fase12.php
```

---

## Estado del proyecto

- **Fases 0 a 11:** Implementadas, verificadas y cerradas.
- **Fase 12:** Implementada y validada (Reportes de Asistencia Docente RF-29 y Consulta de Inscripciones y Cupos RF-30).
- **Próximas fases:** Recuperación de contraseña (Fase 13), validación de requisitos no funcionales (Fases 14 y 15) y depuración final de datos para demostración (Fase 16).
