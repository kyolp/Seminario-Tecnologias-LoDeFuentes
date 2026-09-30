# Arquitectura del Módulo de Asignaciones Docentes — Fase 5

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 5 — Asignaciones Docentes  
**Alcance Oficial:** RF-16 ("Asignaciones. Asignar docentes a grados, secciones y aulas")  
**Fecha:** Septiembre 2026  

---

## 1. Visión General de la Arquitectura

El módulo de **Asignaciones Docentes** se implementa siguiendo el patrón arquitectónico en capas establecido en el proyecto:

$$\text{Cliente HTTP} \longrightarrow \text{Router (Front Controller)} \longrightarrow \text{Middlewares} \longrightarrow \text{Controller} \longrightarrow \text{Service} \longrightarrow \text{Repository} \longrightarrow \text{PDO / MySQL 8.0}$$

Esta separación garantiza:
1. **Desacoplamiento estricto:** El controlador no ejecuta SQL ni contiene reglas complejas de negocio; delega íntegramente en el servicio de dominio.
2. **Defensa en profundidad:** Las restricciones de integridad residen en la base de datos (claves foráneas, restricciones únicas y columna generada `id_seccion_activa`), en la capa de servicio (validaciones semánticas preventivas y transacciones) y en el controlador (validación sintáctica, CSRF y whitelisting).
3. **Mantenibilidad:** El modelo `AsignacionDocente` expone propiedades de solo lectura (`readonly` de PHP 8.2) que previenen mutaciones accidentales del estado en memoria.

---

## 2. Componentes de la Capa de Dominio y Persistencia

### 2.1 Modelo: `app/Models/AsignacionDocente.php`
Representa una entidad de asignación docente, combinando las columnas directas de la tabla `asignaciones_docentes` con los metadatos contextuales académicos obtenidos mediante `JOIN` (nombre del docente, código de sección, grado, ciclo escolar y aula).

- **Inmutabilidad:** Diseñado con propiedades `public readonly` de PHP 8.2.
- **Encapsulación de Estado:** Métodos de conveniencia `isActiva()`, `isCerrada()`.
- **Serialización Segura:** Métodos `toArray()` y constructor estático `fromArray()` para mapeo desde filas PDO.

### 2.2 Repositorio: `app/Repositories/AsignacionRepositoryInterface.php` y `AsignacionRepository.php`
Encapsula todas las sentencias SQL preparadas hacia la tabla `asignaciones_docentes`:
- `findAll(array $filters = []): array`: Consulta paginada/filtrada con soporte de filtros por período escolar, docente, grado, sección, estado ('ACTIVA'/'INACTIVA') y búsqueda textual `q`.
- `findById(int $id): ?AsignacionDocente`: Búsqueda por clave primaria con hidración completa de relaciones.
- `findActiveBySeccion(int $idSeccion): ?AsignacionDocente`: Consulta directa de la asignación actualmente activa en una sección específica.
- `findByDocenteAndPeriodo(int $idDocente, int $idPeriodo): array`: Historial y asignaciones vigentes de un docente en un ciclo lectivo.
- `create(array $data): int`: Inserción parametrizada. **Importante:** Omite de forma deliberada y estricta la columna generada `id_seccion_activa`, permitiendo que el motor InnoDB la calcule de forma determinista.
- `update(int $id, array $data): bool`: Actualización controlada de campos autorizados (`id_aula`, `fecha_fin`).
- `updateEstado(int $id, string $estado, ?string $fechaFin = null): bool`: Cierre o cambio de estado de la asignación.
- `countByDocente()`, `countBySeccion()`, `countByAula()`: Métricas de integridad relacional.

### 2.3 Servicio de Dominio: `app/Services/AsignacionService.php`
Concentra la orquestación y las reglas de negocio académicas:
- **Validación Relacional en Cascada:**
  1. Verifica existencia y estado `ACTIVO` del docente y de su cuenta de usuario vinculada.
  2. Verifica existencia y habilitación de la sección (`habilitada = 1`).
  3. Verifica existencia y habilitación de la asociación grado-período (`grados_periodos.habilitado = 1`).
  4. Verifica que el período escolar no esté en estado `CERRADO`.
  5. Verifica existencia y estado activo del aula física (`activa = 1`).
  6. Valida coherencia cruzada entre sección, grado y período escolar proporcionados.
- **Prevención de Doble Asignación Activa:**
  - Consulta preventiva mediante `existsActiveForSeccion()`.
  - Captura y traducción semántica de `PDOException` con código 1062 para la restricción única `uk_asignaciones_seccion_activa`.
- **Preservación Histórica:**
  - `cerrarAsignacion()` marca `estado = 'INACTIVA'` y fija la `fecha_fin`, dejando liberada la sección pero conservando intacto el registro para auditoría y reportes históricos.
- **Reemplazo Docente Atómico (`reemplazarDocenteAtomico`):**
  - Ejecuta dentro de una transacción PDO aislada el cierre de la asignación vigente y la apertura de la nueva asignación para el nuevo docente en la misma sección y aula. Si cualquier paso falla, ejecuta `rollBack()` inmediato.

---

## 3. Capa de Aplicación y Presentación

### 3.1 Controlador: `app/Controllers/AdminAsignacionController.php`
- **Protección de Acceso:** Ejecutado bajo el pipeline de middlewares `Authenticate` y `RequireRole('ADMINISTRADOR')`.
- **Whitelisting contra Mass Assignment:** Extrae de manera explícita únicamente los campos autorizados para cada operación (`id_docente`, `id_seccion`, `id_aula`, `fecha_inicio`, `fecha_fin`, `estado`). Parámetros adulterados como `id_asignacion_docente`, `id_seccion_activa` o `is_admin` son completamente descartados.
- **Patrón PRG (Post/Redirect/Get):** Tras una mutación exitosa o con error, se emite una redirección HTTP 302 con mensajes flash en sesión (`_flash_success` o `_flash_error`), evitando el reenvío de formularios por recarga de página.

### 3.2 Vistas Administrativas: `views/admin/asignaciones/`
- `index.php`: Panel de consulta con filtros dinámicos por ciclo escolar, estado y texto libre, badges de estado ("ACTIVA" en verde, "INACTIVA" en gris) y acciones contextuales.
- `crear.php`: Formulario guiado con listas desplegables filtradas (docentes activos, aulas activas, secciones habilitadas con advertencia visual si ya cuentan con docente activo).
- `editar.php`: Formulario de edición restringida: mantiene inmutables el docente y la sección asignada para garantizar la trazabilidad histórica, permitiendo ajustar únicamente el aula y la fecha de finalización.
- `ver.php`: Ficha detallada con datos del docente, sección, grado, período escolar, aula, usuario creador y marcas temporales de auditoría (`fecha_creacion`, `fecha_actualizacion`).
- `reemplazar.php`: Interfaz dedicada para el traspaso de sección de un docente a otro con fecha de corte y motivo de reemplazo.

---

## 4. Matriz de Rutas Registradas

| Método | Ruta URI | Acción en Controlador | Propósito |
|---|---|---|---|
| `GET` | `/administracion/asignaciones` | `index` | Listado general con filtros de búsqueda |
| `GET` | `/administracion/asignaciones/ver` | `show` | Consulta detallada de asignación |
| `GET` | `/administracion/asignaciones/crear` | `createForm` | Formulario de registro de asignación |
| `POST` | `/administracion/asignaciones/crear` | `store` | Procesar creación de asignación |
| `GET` | `/administracion/asignaciones/editar` | `editForm` | Formulario de edición autorizada |
| `POST` | `/administracion/asignaciones/editar` | `update` | Procesar edición controlada |
| `POST` | `/administracion/asignaciones/estado` | `toggleEstado` | Cierre o reactivación de asignación |
| `GET` | `/administracion/asignaciones/reemplazar`| `reemplazarForm`| Formulario de reemplazo docente |
| `POST` | `/administracion/asignaciones/reemplazar`| `reemplazar` | Ejecución atómica de reemplazo |
