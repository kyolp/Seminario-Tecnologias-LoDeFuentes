# Arquitectura Administrativa — Fase 4

**Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes**  
**Fase 4: Administración Básica**  
**Responsable Técnico:** Antigravity — Desarrollador Principal  
**Fecha:** Septiembre 2026  

---

## 1. Patrón Arquitectónico Adoptado

La Fase 4 implementa de forma rigurosa el patrón en capas **Controller -> Service -> Repository -> PDO/MySQL**, garantizando una estricta separación de responsabilidades, alta cohesión y bajo acoplamiento:

```
+-------------------------------------------------------------------------------+
|                               HTTP Request                                    |
+-------------------------------------------------------------------------------+
                                      |
                                      v
+-------------------------------------------------------------------------------+
|                            Middlewares de Ruta                                |
|  - Authenticate (302 a /login si no hay sesión activa)                        |
|  - RequireRole('ADMINISTRADOR') (403 Forbidden si el rol no es administrativo)|
|  - VerifyCsrfToken (403 Forbidden ante ausencia o token CSRF inválido en POST)|
+-------------------------------------------------------------------------------+
                                      |
                                      v
+-------------------------------------------------------------------------------+
|                            Capa de Controladores                              |
|  - AdminDocenteController, AdminEstudianteController, AdminPeriodoController  |
|  - AdminGradoController, AdminGradoPeriodoController, AdminSeccionController  |
|  - AdminAulaController                                                        |
|  Responsabilidad: Extraer inputs, invocar servicio, redirigir o renderizar    |
|  vistas. CERO SQL ni lógica relacional compleja en controladores.             |
+-------------------------------------------------------------------------------+
                                      |
                                      v
+-------------------------------------------------------------------------------+
|                              Capa de Servicios                                |
|  - DocenteService, EstudianteService, PeriodoService, GradoService             |
|  - SeccionService, AulaService                                                |
|  Responsabilidad: Reglas de negocio, validaciones de dominio, transacciones    |
|  atómicas (PDO transactions), unicidad lógica y captura de excepciones.       |
+-------------------------------------------------------------------------------+
                                      |
                                      v
+-------------------------------------------------------------------------------+
|                            Capa de Repositorios                               |
|  - DocenteRepository, EstudianteRepository, PeriodoRepository                 |
|  - GradoRepository, GradoPeriodoRepository, SeccionRepository, AulaRepository|
|  Responsabilidad: Consultas PDO preparadas, binding de parámetros y mapeo a  |
|  modelos inmutables. CERO lógica de presentación ni dependencias de sesión.  |
+-------------------------------------------------------------------------------+
                                      |
                                      v
+-------------------------------------------------------------------------------+
|                     Base de Datos Relacional (MySQL 8.0)                      |
|  - Tablas: usuarios, docentes, estudiantes, periodos_escolares, grados,        |
|    grados_periodos, secciones, aulas                                          |
|  - Restricciones: PKs, FKs ON DELETE RESTRICT, UNIQUE, CHECK constraints      |
+-------------------------------------------------------------------------------+
```

---

## 2. Modelos de Dominio Inmutables

Cada entidad cuenta con su modelo representativo en `app/Models/`:
- `Docente`: Encapsula datos de `docentes` y campos vinculados de `usuarios` (`nombre_usuario`, `correo`, `nombres`, `apellidos`, `fecha_ingreso`, `codigo_docente`, `estado`).
- `Estudiante`: Encapsula `codigo_estudiante`, `nombres`, `apellidos`, `fecha_nacimiento`, `estado`.
- `PeriodoEscolar`: Encapsula `nombre`, `fecha_inicio`, `fecha_fin`, `estado` (`PLANIFICADO`, `ACTIVO`, `CERRADO`), y métodos de estado `isActivo()`, `isPlanificado()`, `isCerrado()`.
- `Grado`: Encapsula `codigo`, `nombre`, `orden`, `activo`.
- `GradoPeriodo`: Encapsula la relación `id_periodo_escolar`, `id_grado`, `habilitado`, con nombres descriptivos resueltos vía JOIN.
- `Seccion`: Encapsula `codigo`, `nombre`, `capacidad` (entero obligatorio $> 0$), `habilitada`, y nombres de grado y período vinculados.
- `Aula`: Encapsula `codigo`, `nombre`, `ubicacion_referencia`, `activa`.

Todos los modelos utilizan propiedades `readonly` fuertemente tipadas en PHP 8.2, impidiendo mutaciones de estado imprevistas fuera del flujo controlado de repositorios y servicios.

---

## 3. Manejo de Sesión y Control de Acceso

Las rutas administrativas se encuentran centralizadas bajo el prefijo `/administracion`:
- Toda solicitud requiere superar en serie:
  1. `App\Middleware\Authenticate`: Valida sesión viva y usuario activo contra la BD en tiempo real.
  2. `App\Middleware\RequireRole('ADMINISTRADOR')`: Permite acceso únicamente a usuarios con rol `ADMINISTRACION` o `ADMINISTRADOR`. Emite HTTP 403 Forbidden controlado ante usuarios con rol `DOCENTE`.
  3. `App\Middleware\VerifyCsrfToken`: Aplicado a todas las rutas `POST`. Verifica el token `_token` emitido por `Csrf::getToken()`.

---

## 4. Estrategia de Vistas y Layouts

Las vistas administrativas residen en `views/admin/`:
- Utilizan el layout institucional unificado `views/layouts/main.php`.
- Cada módulo provee `index.php` (listado con búsqueda/filtros y acciones), `crear.php` (formulario con feedback de errores y retención de valores previos `old`), `editar.php` (formulario de edición) y vistas de detalle `ver.php` donde corresponde.
- Todo output dinámico se sanitiza estrictamente con `e()` (`htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`) previniendo ataques de Cross-Site Scripting (XSS).
