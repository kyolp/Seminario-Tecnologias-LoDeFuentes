# Plan de Limpieza y Depuración de Base de Datos — Fase 16

**Proyecto:** Sistema web integral para la gestión de inscripciones, asignación de secciones y control de asistencia docente  
**Institución:** Escuela Oficial Rural Mixta Lo de Fuentes, Zona 11 de Mixco, Guatemala  
**Fase:** Fase 16 — Limpieza Final de Base de Datos, Datos de Demostración y Cierre del Proyecto  

---

## 1. Justificación y Objetivos

Durante el ciclo de desarrollo incremental del proyecto (Fases 0 a 15), se ejecutaron cientos de pruebas unitarias, de integración, de concurrencia y de seguridad, generando múltiples registros de prueba temporales, usuarios de prueba (ej. `admin.demo`, `test_doc_*`, `test_cred_*`), inscripciones con códigos transitorios y entidades ficticias acumuladas.

El objetivo de este plan de limpieza es:
1. Respaldar de manera íntegra el estado previo a la limpieza.
2. Identificar y auditar todos los patrones de datos residuales o técnicos.
3. Restablecer el esquema de base de datos a partir de su definición canónica inalterada (`database/schema.sql`).
4. Proveer un conjunto de datos iniciales (`database/seed.sql`) de alta fidelidad, coherente, natural y plenamente representativo del entorno escolar rural guatemalteco.
5. Garantizar la preservación estricta de la integridad relacional de las 20 tablas del sistema.
6. Asegurar cero tokens activos en la tabla de recuperación de credenciales.

---

## 2. Estrategia de Respaldo Previo

Antes de cualquier acción destructiva o de reinicialización, se ejecutaron dos mecanismos de respaldo:
- **Respaldo en Control de Versiones:** Creación de una rama local de git de respaldo:
  ```bash
  git branch respaldo-antes-fase16
  ```
- **Respaldo de Base de Datos Físico:** Volcado nativo completo mediante `mysqldump`:
  ```bash
  mysqldump -h 127.0.0.1 -P 3306 -u Administrador -p Administrador --routines --triggers --events --single-transaction escuela_lo_de_fuentes > database/backups/backup_pre_fase16_20261020_2302.sql
  ```
  El directorio `database/backups/` fue excluido del repositorio mediante `.gitignore` para salvaguardar la privacidad de datos previos.

---

## 3. Inventario y Auditoría de Datos Previos

El análisis del estado previo reveló:
- 6,739 filas totales distribuidas en las tablas del sistema.
- Cientos de usuarios temporales con prefijos `test_doc_`, `test_f5_`, `admin.f*`, etc.
- Correos electrónicos con dominios técnicos como `@example.invalid` o `@escuela.local`.
- Marcajes de asistencia con coordenadas ficticias o registros huérfanos de pruebas previas.
- Residuos de pruebas de concurrencia en asignaciones e inscripciones.

---

## 4. Criterios de Selección y Definición de Datos Limpios

Para el nuevo `database/seed.sql` se establecieron las siguientes directrices institucionales:
1. **Nombres y Apellidos:** Nombres hispanos tradicionales guatemaltecos (ej. Carlos Méndez, María López, Estuardo González, Patricia Álvarez).
2. **Correos Electrónicos:** Formato institucional uniforme: `nombre.apellido@escuela.com`.
3. **Roles Institucionales:** `ADMINISTRACION` (2 usuarios directivos) y `DOCENTE` (12 profesores asignados a los 6 grados y secciones A y B).
4. **Teléfonos de Encargados:** Números telefónicos guatemaltecos de 8 dígitos con prefijos válidos de telefonía móvil local (3, 4, 5).
5. **Códigos de Estudiantes:** Nomenclatura institucional natural: `EST-2026-001` a `EST-2026-024`.
6. **Códigos de Inscripción:** Formato oficial: `INS-2026-00101` a `INS-2026-00124`.
7. **Períodos Académicos:**
   - Ciclo Escolar 2026: `ACTIVO` (2026-01-15 a 2026-10-31).
   - Ciclo Escolar 2025: `CERRADO` (2025-01-15 a 2025-10-31), para pruebas históricas.
8. **Geocerca Escolar:** Escuela Oficial Rural Mixta Lo de Fuentes - Sede Central:
   - Latitud: `14.6349150`
   - Longitud: `-90.5068820`
   - Radio: `100.00` metros
   - **Clasificación de Origen:** Opción B (Coordenadas institucionales proporcionadas y documentadas por el proyecto desde Fase 8 en `docs/fase-8/geolocalizacion.md`).
9. **Marcajes de Asistencia:** 10 marcajes docentes dentro de la geocerca oficial en horarios hábiles escolares (distancia geodésica < 15 metros).
10. **Permisos y Notificaciones:** 3 solicitudes de permiso con justificaciones reales (cita médica IGSS, capacitación MINEDUC, trámite legal) con historial inmutable de transiciones y notificaciones automáticas para directivos y docentes.
11. **Tokens de Recuperación:** 0 tokens activos.

---

## 5. Ejecución del Proceso de Limpieza

1. **Reinicialización del Esquema:**
   Se ejecutó `DROP DATABASE IF EXISTS escuela_lo_de_fuentes` y su posterior recreación limpia con codificación `utf8mb4` e interclasificación `utf8mb4_unicode_ci`.
2. **Aplicación del Esquema Canónico:**
   Se cargó íntegramente `database/schema.sql`, sin realizar la menor alteración a sus definiciones de tablas, claves foráneas, columnas generadas ni disparadores.
3. **Carga del Seed Limpio:**
   Se cargó `database/seed.sql`, verificando la ausencia total de advertencias o errores de restricción.
4. **Exportación de Demostración:**
   Se generó el volcado final `database/final_demo_2026.sql` representativo del estado óptimo para demostraciones y entrega.
