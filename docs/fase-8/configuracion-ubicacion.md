# CONFIGURACIÓN DE UBICACIÓN INSTITUCIONAL Y GEOCERCA — FASE 8

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 8 — Asistencia Docente y Geolocalización  
**Rol:** ANTIGRAVITY — DESARROLLADOR PRINCIPAL  
**Requisitos cubiertos:** RF-18, RF-19  

---

## 1. Módulo Administrativo de Geocerca (RF-18)

El módulo permite al personal con rol **`ADMINISTRADOR`** definir y actualizar los límites geográficos autorizados de la Escuela Oficial Rural Mixta Lo de Fuentes.

Las rutas registradas son:
- `GET /administracion/ubicacion`: Panel principal con estado de geocerca activa y tabla histórica.
- `GET /administracion/ubicacion/configurar`: Formulario para registrar una nueva geocerca.
- `POST /administracion/ubicacion/configurar`: Procesamiento y activación atómica de la nueva geocerca.

Todas las rutas están protegidas mediante el pipeline de middleware `Authenticate` y `RequireRole('ADMINISTRADOR')`, junto con `VerifyCsrfToken` en operaciones `POST`.

---

## 2. Unicidad Condicional en Base de Datos (DT-53)

El esquema relacional exige que en todo momento exista **a lo sumo una única configuración activa**, permitiendo a la vez conservar un número ilimitado de configuraciones inactivas previas para propósitos de auditoría e histórico.

Esta garantía técnica se implementa en la tabla `configuraciones_ubicacion` mediante una **columna virtual almacenada (STORED GENERATED COLUMN)** y un índice único:

```sql
clave_configuracion_activa TINYINT GENERATED ALWAYS AS (
    CASE WHEN activa = 1 THEN 1 ELSE NULL END
) STORED,

CONSTRAINT uk_configuraciones_ubicacion_activa UNIQUE (
    clave_configuracion_activa
)
```

### Justificación Técnica:
En el estándar SQL y en el motor InnoDB de MySQL, los valores `NULL` no colisionan en un índice único. Cuando una fila tiene `activa = 0`, el valor generado es `NULL` (permitiendo múltiples filas inactivas). Cuando `activa = 1`, el valor generado es `1`, impidiendo físicamente en el motor de base de datos que coexistan dos o más filas activas de forma concurrente.

---

## 3. Versionado Transaccional Atómico

Cuando el administrador guarda una nueva configuración, `UbicacionRepository::crearConfiguracionActiva` ejecuta una transacción atómica:

```sql
START TRANSACTION;

-- Paso 1: Desactivar la configuración activa anterior y estampar fecha de fin
UPDATE configuraciones_ubicacion 
SET activa = 0, fecha_fin = CURRENT_TIMESTAMP(6) 
WHERE activa = 1;

-- Paso 2: Insertar la nueva configuración activa
INSERT INTO configuraciones_ubicacion (
    id_usuario_creador, descripcion, latitud, longitud, radio_metros, fecha_inicio, activa
) VALUES (
    :u, :desc, :lat, :lon, :radio, CURRENT_TIMESTAMP(6), 1
);

COMMIT;
```

Si cualquier paso falla, la transacción realiza un `ROLLBACK` completo, garantizando que el sistema nunca quede en un estado inconsistente o sin geocerca activa.
