# Módulos Implementados — Fase 4

**Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes**  
**Fase 4: Administración Básica**  
**Responsable Técnico:** Antigravity — Desarrollador Principal  
**Fecha:** Septiembre 2026  

---

## 1. Módulo de Docentes (`docentes` y `usuarios`)

### Rutas
- `GET /administracion/docentes` — Listado con búsqueda reactiva por nombres, apellidos, código o correo.
- `GET /administracion/docentes/ver?id={id}` — Ficha detallada del docente y usuario vinculado.
- `GET /administracion/docentes/crear` — Formulario de alta institucional.
- `POST /administracion/docentes/crear` — Procesamiento de creación atómica.
- `GET /administracion/docentes/editar?id={id}` — Formulario de modificación.
- `POST /administracion/docentes/editar?id={id}` — Actualización de datos.
- `POST /administracion/docentes/toggle?id={id}` — Activación / Desactivación lógica.
- `POST /administracion/docentes/eliminar?id={id}` — Eliminación física protegida por integridad referencial.

### Características Clave
- **Creación Atómica:** Se crea la cuenta de usuario con hash seguro BCrypt y rol `DOCENTE`, e inmediatamente la entidad docente vinculada por `id_usuario`. Todo se ejecuta bajo una transacción PDO atómica (`beginTransaction()`, `commit()`, `rollBack()`). Si falla el registro docente, el usuario se revierte sin dejar registros huérfanos.
- **Validaciones:** Unicidad de `nombre_usuario`, unicidad de `correo`, formato RFC de correo electrónico, contraseña con longitud mínima de 8 caracteres, formato de fecha `AAAA-MM-DD`.

---

## 2. Módulo de Estudiantes (`estudiantes`)

### Rutas
- `GET /administracion/estudiantes` — Listado de estudiantes con filtro por búsqueda.
- `GET /administracion/estudiantes/ver?id={id}` — Ficha del estudiante.
- `GET /administracion/estudiantes/crear` — Formulario de registro escolar.
- `POST /administracion/estudiantes/crear` — Alta de estudiante.
- `GET /administracion/estudiantes/editar?id={id}` — Edición de datos generales.
- `POST /administracion/estudiantes/editar?id={id}` — Guardado de cambios.
- `POST /administracion/estudiantes/toggle?id={id}` — Modificación de estado (ACTIVO / INACTIVO).
- `POST /administracion/estudiantes/eliminar?id={id}` — Eliminación con validación de historial.

### Características Clave
- **Validaciones:** Nombres y apellidos obligatorios con longitud máxima de 100 caracteres; código único de estudiante (opcional u obligatorio según asignación); fecha de nacimiento válida y no futura.

---

## 3. Módulo de Períodos Escolares (`periodos_escolares`)

### Rutas
- `GET /administracion/periodos` — Listado de ciclos lectivos.
- `GET /administracion/periodos/ver?id={id}` — Ficha y estado del período.
- `GET /administracion/periodos/crear` — Formulario de nuevo ciclo.
- `POST /administracion/periodos/crear` — Registro de período.
- `GET /administracion/periodos/editar?id={id}` — Edición de ciclo.
- `POST /administracion/periodos/editar?id={id}` — Actualización de fechas o estado.
- `POST /administracion/periodos/toggle?id={id}` — Cambio rápido de estado.
- `POST /administracion/periodos/eliminar?id={id}` — Eliminación con comprobación de dependencias.

### Características Clave
- **Unicidad de Período Activo:** En cumplimiento de la regla de negocio institucional y la restricción física `uk_periodos_escolares_un_activo`, el sistema impide la existencia simultánea de dos períodos en estado `ACTIVO`. La validación opera tanto preventivamente a nivel de servicio (`PeriodoService::crearPeriodo`) como reactivamente capturando violaciones de clave única PDO.
- **Rango Temporal:** La fecha de finalización debe ser estrictamente igual o posterior a la fecha de inicio (`chk_periodos_escolares_fechas`).

---

## 4. Módulo de Catálogo de Grados (`grados`)

### Rutas
- `GET /administracion/grados` — Listado del catálogo general de grados.
- `GET /administracion/grados/crear` — Formulario de alta.
- `POST /administracion/grados/crear` — Registro de grado.
- `GET /administracion/grados/editar?id={id}` — Formulario de edición.
- `POST /administracion/grados/editar?id={id}` — Actualización de orden o descripción.
- `POST /administracion/grados/toggle?id={id}` — Activación / Desactivación.
- `POST /administracion/grados/eliminar?id={id}` — Eliminación protegida por `ON DELETE RESTRICT`.

### Características Clave
- **Orden Jerárquico:** Restricción de orden entero estrictamente positivo ($orden > 0$) y único para ordenar la progresión pedagógica (`chk_grados_orden`, `uk_grados_orden`).
- **Desactivación Preferente:** Si un grado ya tiene relaciones históricas en períodos, el sistema bloquea su eliminación física e instruye al usuario a desactivarlo lógicamente.

---

## 5. Módulo de Grados por Período (`grados_periodos`)

### Rutas
- `GET /administracion/grados-periodos` — Listado de oferta académica con selector/filtro de ciclo lectivo.
- `GET /administracion/grados-periodos/crear` — Formulario de asignación curricular.
- `POST /administracion/grados-periodos/crear` — Registro de vinculación.
- `POST /administracion/grados-periodos/toggle?id={id}` — Habilitar / Deshabilitar grado en el período.
- `POST /administracion/grados-periodos/eliminar?id={id}` — Remover grado de la oferta del ciclo.

### Características Clave
- **Integridad y Unicidad:** Verifica la existencia previa de la clave foránea de período escolar y de grado. Impide duplicar el mismo grado dentro del mismo período lectivo (`uk_grados_periodos`).

---

## 6. Módulo de Secciones (`secciones`)

### Rutas
- `GET /administracion/secciones` — Listado de secciones con filtro por Grado/Período.
- `GET /administracion/secciones/ver?id={id}` — Detalle de sección y capacidad.
- `GET /administracion/secciones/crear` — Formulario de apertura de sección.
- `POST /administracion/secciones/crear` — Alta de sección con cupo.
- `GET /administracion/secciones/editar?id={id}` — Edición de sección y capacidad.
- `POST /administracion/secciones/editar?id={id}` — Actualización.
- `POST /administracion/secciones/toggle?id={id}` — Habilitar / Deshabilitar sección.
- `POST /administracion/secciones/eliminar?id={id}` — Eliminación con chequeo de inscripciones o asignaciones.

### Características Clave
- **Capacidad Obligatoria Mayor a Cero:** Se exige de forma estricta que la capacidad sea un número entero y estrictamente mayor a 0 ($capacidad > 0$), validado tanto a nivel de formulario, de servicio (`SeccionService`) y de base de datos (`chk_secciones_capacidad`). Valores de 0, negativos, decimales o alfanuméricos son rechazados con mensajes específicos de error.
- **Contexto Único:** Unicidad del código de sección dentro del mismo grado y período (`uk_secciones_contexto`).

---

## 7. Módulo de Aulas (`aulas`)

### Rutas
- `GET /administracion/aulas` — Catálogo de espacios físicos.
- `GET /administracion/aulas/crear` — Formulario de alta.
- `POST /administracion/aulas/crear` — Registro de aula.
- `GET /administracion/aulas/editar?id={id}` — Edición de aula y ubicación de referencia.
- `POST /administracion/aulas/editar?id={id}` — Actualización de aula.
- `POST /administracion/aulas/toggle?id={id}` — Modificación de estado activo.
- `POST /administracion/aulas/eliminar?id={id}` — Eliminación física con verificación relacional.

### Características Clave
- **Código Físico Único:** Restricción de unicidad sobre el código de aula (`uk_aulas_codigo`).
- **Ubicación:** Soporte para referencias físicas descriptivas (edificio, nivel, módulo).
