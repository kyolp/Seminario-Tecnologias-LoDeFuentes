# Catálogo de Datos de Demostración — Fase 16

**Proyecto:** Sistema web integral para la gestión de inscripciones, asignación de secciones y control de asistencia docente  
**Institución:** Escuela Oficial Rural Mixta Lo de Fuentes  
**Documento:** Inventario de Datos de Demostración Limpios y Coherentes  

---

## 1. Estructura de Roles y Usuarios

### 1.1 Administradores de la Escuela
| Usuario | Nombre Completo | Correo Institucional | Rol | Estado |
|---|---|---|---|---|
| `estuardo.gonzalez` | Estuardo Leonel González Morales | `estuardo.gonzalez@escuela.com` | `ADMINISTRACION` | ACTIVO |
| `patricia.alvarez` | Patricia Sofía Álvarez Castillo | `patricia.alvarez@escuela.com` | `ADMINISTRACION` | ACTIVO |

### 1.2 Personal Docente (12 Profesores)
| Código | Usuario | Nombre Completo | Correo Institucional | Asignación Escolar |
|---|---|---|---|---|
| `DOC-2026-001` | `carlos.mendez` | Carlos Alberto Méndez Pérez | `carlos.mendez@escuela.com` | Primero Primaria - Sección A (Aula 101) |
| `DOC-2026-002` | `maria.lopez` | María Mercedes López García | `maria.lopez@escuela.com` | Primero Primaria - Sección B (Aula 102) |
| `DOC-2026-003` | `diego.castillo` | Diego Alejandro Castillo Hernández | `diego.castillo@escuela.com` | Segundo Primaria - Sección A (Aula 103) |
| `DOC-2026-004` | `sofia.ramirez` | Sofía Alejandra Ramírez Morales | `sofia.ramirez@escuela.com` | Segundo Primaria - Sección B (Aula 104) |
| `DOC-2026-005` | `juan.morales` | Juan Carlos Morales Estrada | `juan.morales@escuela.com` | Tercero Primaria - Sección A (Aula 201) |
| `DOC-2026-006` | `ana.herrera` | Ana Lucía Herrera Gómez | `ana.herrera@escuela.com` | Tercero Primaria - Sección B (Aula 202) |
| `DOC-2026-007` | `fernando.reyes` | Fernando José Reyes Salazar | `fernando.reyes@escuela.com` | Cuarto Primaria - Sección A (Aula 203) |
| `DOC-2026-008` | `claudia.navarro` | Claudia Marina Navarro Paz | `claudia.navarro@escuela.com` | Cuarto Primaria - Sección B (Aula 204) |
| `DOC-2026-009` | `ricardo.delgado` | Ricardo Antonio Delgado Orozco | `ricardo.delgado@escuela.com` | Quinto Primaria - Sección A (Aula 101 - V) |
| `DOC-2026-010` | `elena.figueroa` | Elena Beatriz Figueroa Paredes | `elena.figueroa@escuela.com` | Quinto Primaria - Sección B (Aula 102 - V) |
| `DOC-2026-011` | `victor.sanchez` | Víctor Manuel Sánchez Cordero | `victor.sanchez@escuela.com` | Sexto Primaria - Sección A (Aula 103 - V) |
| `DOC-2026-012` | `patricia.mendoza`| Patricia Eugenia Mendoza Soto | `patricia.mendoza@escuela.com`| Sexto Primaria - Sección B (Aula 104 - V) |

---

## 2. Organización Académica y Espacios Físicos

### 2.1 Períodos Escolares
- **Ciclo Escolar 2026:** Del 15 de enero de 2026 al 31 de octubre de 2026. Estado: `ACTIVO`.
- **Ciclo Escolar 2025:** Del 15 de enero de 2025 al 31 de octubre de 2025. Estado: `CERRADO` (Histórico).

### 2.2 Grados Educativos
1. Primer Grado Primaria (`PRIM-1`, Orden 1)
2. Segundo Grado Primaria (`PRIM-2`, Orden 2)
3. Tercer Grado Primaria (`PRIM-3`, Orden 3)
4. Cuarto Grado Primaria (`PRIM-4`, Orden 4)
5. Quinto Grado Primaria (`PRIM-5`, Orden 5)
6. Sexto Grado Primaria (`PRIM-6`, Orden 6)

### 2.3 Aulas Físicas
- `AUL-101`: Módulo A - Primer Nivel (Capacidad 35 alumnos)
- `AUL-102`: Módulo A - Primer Nivel (Capacidad 35 alumnos)
- `AUL-103`: Módulo B - Primer Nivel (Capacidad 35 alumnos)
- `AUL-104`: Módulo B - Primer Nivel (Capacidad 35 alumnos)
- `AUL-201`: Módulo A - Segundo Nivel (Capacidad 35 alumnos)
- `AUL-202`: Módulo A - Segundo Nivel (Capacidad 35 alumnos)
- `AUL-203`: Módulo B - Segundo Nivel (Capacidad 35 alumnos)
- `AUL-204`: Módulo B - Segundo Nivel (Capacidad 35 alumnos)

### 2.4 Secciones y Balance de Cupos
Para cada uno de los 6 grados se tienen habilitadas las secciones **A** y **B**, con una capacidad de 30 cupos por sección (total: 360 cupos en la escuela).
- 24 inscripciones confirmadas distribuidas homogéneamente (2 por sección).
- Disponibilidad calculada en tiempo real: 28 cupos disponibles por sección.

---

## 3. Expedientes Estudiantiles y Vínculos de Tutela

Se registraron 24 estudiantes activos (`EST-2026-001` a `EST-2026-024`) con fechas de nacimiento entre 2014 y 2019, vinculados a 18 encargados legales con teléfonos de 8 dígitos guatemaltecos (ej. `4123-4567`, `5345-6789`).
Los parentescos registrados corresponden a los vínculos habituales: Padre, Madre, Abuela, Tío o Tutor Legal.

---

## 4. Geocerca y Asistencia Docente

- **Ubicación Institucional:** Sede Central de la Escuela Oficial Rural Mixta Lo de Fuentes.
- **Coordenadas Oficiales:** Latitud `14.6349150`, Longitud `-90.5068820`, Radio permitido: `100.00` metros.
- **Marcajes de Muestra:** 10 marcajes docentes (5 de ENTRADA y 5 de SALIDA) correspondientes a jornadas laborales regulares, con distancias reales menores a 12 metros respecto al punto central de la geocerca (`dentro_radio = 1`).

---

## 5. Permisos Laborales y Notificaciones

- **Solicitud 1:** Consulta médica en IGSS Zona 7 por docente Carlos Méndez. Estado: `APROBADO`.
- **Solicitud 2:** Asistencia a taller técnico pedagógico del MINEDUC por docente María López. Estado: `APROBADO`.
- **Solicitud 3:** Trámite administrativo personal en RENAP por docente Diego Castillo. Estado: `PENDIENTE`.
- **Trazabilidad:** Cada cambio de estado cuenta con su correspondiente registro de auditoría en `historial_solicitudes_permiso` y notificaciones internas dirigidas a la administración y al docente.
