# Consulta de Historial de Asistencia Docente y Administrativo

**Proyecto:** Sistema Web Integral — Escuela Oficial Rural Mixta Lo de Fuentes  
**Módulos:** Portal Docente y Gestión Administrativa de Docentes  
**Requisitos Relacionados:** RF-23, RF-29, RNF-04, RNF-06  

---

## 1. Visión General del Ajuste

El historial de asistencia constituye una evidencia crítica laboral e institucional. Este ajuste perfecciona la experiencia de consulta diferenciando de manera estricta los privilegios y las necesidades de información según el rol del usuario:

1. **Docente (Rol DOCENTE — RF-23):** Accede exclusivamente a su historial personal. Se prioriza la privacidad y la claridad operativa, eliminando la sobrecarga técnica (coordenadas geográficas crudas, identificadores internos de claves primarias o foráneas). Se refuerza el aislamiento horizontal (Anti-IDOR).
2. **Administración (Rol ADMINISTRADOR — RF-29):** Puede auditar el historial de marcajes individual de cualquier docente activo o inactivo del establecimiento, reutilizando la infraestructura preexistente de `ReporteService` y enlazando la consulta de forma directa e intuitiva desde el catálogo general de docentes y la ficha individual del docente.

---

## 2. Consulta desde el Portal Docente (RF-23)

### 2.1. Interfaz y Privacidad de Datos
Tanto la vista web (`/docente/asistencia/historial`) como el endpoint JSON asociado presentan únicamente los campos esenciales para el docente:
- **Fecha:** Formateada en día/mes/año (`d/m/Y`).
- **Hora:** Formato 24 horas (`H:i:s`).
- **Tipo de Marcaje:** `ENTRADA` o `SALIDA`.
- **Estado / Resultado:** Confirmación del marcaje dentro del área autorizada (`✓ Marcaje Válido`).

Se excluyen rigurosamente de la respuesta:
- `latitud_dispositivo` y `longitud_dispositivo` (coordenadas crudas innecesarias para el docente).
- `distancia_metros` y `precision_metros`.
- `id_marcaje_asistencia`, `id_usuario`, `id_configuracion_ubicacion`, `id_docente` (claves técnicas de base de datos).

### 2.2. Mitigación Estricta de IDOR (Insecure Direct Object Reference)
El controlador `DocenteAsistenciaController` resuelve la identidad del docente **exclusivamente a partir del identificador de usuario almacenado en la sesión autenticada en el servidor**:
```php
$user = $this->auth->user();
$docente = $this->portalService->obtenerDocentePorUsuarioId($user->id_usuario);
$historial = $this->asistenciaService->obtenerHistorialDocente($docente->id_docente, 100);
```
Cualquier intento deliberado de inyectar identificadores ajenos en la URL (por ejemplo, `GET /docente/asistencia/historial?id_docente=99`) es ignorado por completo, garantizando que el docente solo pueda recibir sus propios registros.

---

## 3. Consulta desde el Portal de Administración (RF-29)

### 3.1. Reutilización de Arquitectura Existente
En lugar de crear endpoints redundantes o duplicar consultas SQL, se aprovecha el motor de reportes institucional ya probado:
- **Ruta Oficial:** `/administracion/reportes/asistencia?id_docente={ID}`
- **Controlador:** `AdminReporteController::asistencia(Request $request)`
- **Servicio:** `ReporteService::generarReporteAsistencia(array $params)`

El servicio valida que el docente exista y filtra los marcajes inmutables de entrada y salida, ofreciendo resúmenes agregados (total de entradas, salidas y fechas).

### 3.2. Puntos de Entrada y Navegación
Se incorporaron accesos directos desde el módulo de personal docente:
1. **Catálogo de Docentes (`views/admin/docentes/index.php`):**
   - En la columna de *Acciones* de cada fila docente, se agregó el enlace `Asistencia`, apuntando a `/administracion/reportes/asistencia?id_docente={id_docente}`.
2. **Ficha Detallada del Docente (`views/admin/docentes/ver.php`):**
   - En la botonera superior de acciones: botón de acceso rápido `🕒 Ver Historial de Asistencia`.
   - En la tabla de datos del docente: fila `Historial de Asistencia` con enlace directo de consulta.

---

## 4. Matriz Comparativa de Acceso

| Característica | Consulta Docente (`/docente/asistencia/historial`) | Consulta Administrativa (`/administracion/reportes/asistencia`) |
|:---|:---:|:---:|
| **Rol requerido** | `DOCENTE` | `ADMINISTRADOR` |
| **Alcance de consulta** | Únicamente el docente en sesión | Cualquier docente del establecimiento |
| **Exposición de Coordenadas** | **NO** (Ocultas por privacidad) | Sí (Auditoría institucional administrativa) |
| **Exposición de IDs Técnicos** | **NO** (Solo fecha, hora, tipo, estado) | Opcional según filtro de reporte |
| **Filtros por Período / Fecha** | Vista de historial reciente (100 registros) | Filtros por rango de fechas y períodos escolares |
| **Protección Anti-IDOR** | Aislamiento forzoso a perfil de sesión | Control de roles y validación de existencia en BD |
