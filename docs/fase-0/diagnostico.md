# Diagnóstico, aislamiento y preparación — Fase 0

Fecha: 2026-09-20  
Directorio autorizado: `C:\Users\David\Documents\Universidad\2026\Segundo semestre\seminario de tecnologias\ProyectoFinal`

## Declaración de aislamiento

Trabajaré exclusivamente con la información contenida en los documentos y
archivos autorizados para este proyecto.

No utilizaré información de otros proyectos, conversaciones, repositorios,
memorias o sistemas.

## 1. Identificación del proyecto

| Campo | Resultado obtenido únicamente del documento autorizado |
|---|---|
| Nombre del proyecto | **NO DEFINIDO EN LOS DOCUMENTOS AUTORIZADOS.** La cadena `escuela-lo-de-fuentes/` aparece solo como ejemplo de una estructura técnica posible y no se adopta como nombre oficial. |
| Institución | **NO DEFINIDO EN LOS DOCUMENTOS AUTORIZADOS.** |
| Objetivo | **NO DEFINIDO EN LOS DOCUMENTOS AUTORIZADOS.** El texto describe módulos y controles parciales, pero no formula un objetivo institucional oficial. |
| Actores | Encargado, estudiante, docente y administración. |
| Módulos | Autenticación y roles; administración básica; asignaciones docentes; inscripciones y cupos; portal docente; asistencia y geolocalización; permisos docentes; notificaciones; comprobante PDF; reportes; recuperación de contraseña. |
| Tecnologías | HTML, CSS y JavaScript; PHP; MySQL. |
| Requerimientos funcionales | Registrados en `matriz-trazabilidad.md` como RF-001 a RF-032. |
| Requerimientos no funcionales | Registrados en `matriz-trazabilidad.md` como RNF-001 a RNF-018. |
| Reglas de negocio | Control estricto de cupos; validación institucional de geolocalización; reglas de marcaje; flujo de inscripción; ciclo de solicitudes de permiso; control por roles e integridad de datos. |
| Fuera del alcance | Tecnologías sustitutas no autorizadas; funcionalidades no descritas; y, durante Fase 0, cualquier esquema completo de base de datos o implementación funcional. No existe un listado funcional completo de exclusiones. |

## 2. Fuente oficial disponible

Solo se recibió el archivo adjunto `Texto pegado.txt`, que contiene el Prompt
Maestro de ejecución. No se encontró ningún archivo en el directorio autorizado
antes de iniciar esta fase. En particular, no se recibió un documento funcional
con datos oficiales de la institución, casos de uso completos, diseño, catálogo
de reportes o reglas detalladas.

## 3. Actores identificados

| Actor | Capacidades expresamente mencionadas |
|---|---|
| Encargado | Iniciar el flujo de inscripción, registrar datos del estudiante y del encargado, seleccionar grado y sección, completar la inscripción, recibir un código de confirmación y un comprobante. |
| Estudiante | Ser registrado e inscrito en un grado y una sección con cupo disponible. No se define acceso directo al sistema. |
| Docente | Consultar perfil, asignación, grado, sección, aula y estudiantes asignados; registrar entrada/salida con validación geográfica; crear, adjuntar, enviar y consultar solicitudes de permiso e historial. |
| Administración | Gestionar entidades básicas; consultar, aprobar o rechazar permisos y registrar la decisión. Los permisos exactos por rol no están detallados. |

No se adopta ningún actor adicional.

## 4. Módulos y alcance identificado

1. Autenticación y roles.
2. Administración de docentes, estudiantes, grados, secciones, capacidades,
   aulas y períodos.
3. Asignaciones entre docente, grado, sección y aula.
4. Inscripciones con validación de cupo, confirmación y consulta por código.
5. Portal docente.
6. Asistencia con geolocalización e historial.
7. Solicitudes de permisos docentes y resolución administrativa.
8. Notificaciones requeridas por el alcance, todavía sin catálogo.
9. Comprobante PDF de inscripción.
10. Reportes establecidos por el alcance, todavía sin catálogo.
11. Recuperación segura de contraseña.
12. Seguridad, endurecimiento y pruebas integrales.

## 5. Reglas de negocio confirmadas

- Nunca se debe permitir que la cantidad de estudiantes inscritos supere la
  capacidad de una sección.
- El control de cupo debe ejecutarse en el servidor y, cuando corresponda, en
  la base de datos; el riesgo de concurrencia exige transacción, bloqueo,
  validación final y reversión.
- Deben configurarse la latitud, longitud y radio permitido de la institución.
- La ubicación del docente debe validarse antes del marcaje y la decisión
  crítica no puede depender únicamente de JavaScript.
- La asistencia debe contemplar entrada duplicada, salida duplicada, salida sin
  entrada, ubicación rechazada y ubicación no disponible. La respuesta exacta
  para cada caso no está definida.
- La inscripción debe seguir el orden: encargado, datos del estudiante, datos
  del encargado, grado, sección disponible, validación de cupo, inscripción,
  código de confirmación y comprobante.
- El docente debe poder crear, adjuntar cuando corresponda, enviar y consultar
  el estado e historial de sus solicitudes de permiso.
- Administración debe poder consultar, aprobar o rechazar solicitudes y dejar
  registrada la decisión.
- Las contraseñas nunca deben almacenarse en texto plano y las credenciales
  reales nunca deben versionarse.

## 6. Criterios de aceptación identificados

- El sistema no acepta una inscripción que haga superar la capacidad de la
  sección, incluso ante solicitudes concurrentes.
- El marcaje solo se acepta después de la validación de ubicación en el
  servidor dentro de la configuración institucional.
- El flujo de inscripción respeta la secuencia definida y produce código de
  confirmación y comprobante.
- El docente puede completar y consultar el ciclo descrito de permisos.
- Administración puede decidir solicitudes y la decisión queda registrada.
- La autenticación usa hash/verificación de contraseña, sesiones seguras,
  autorización por roles y rutas protegidas.
- Un requerimiento solo obtiene estado `VALIDADO` después de una prueba
  satisfactoria.

Los criterios detallados para cada pantalla, reporte, notificación y mensaje de
error están **NO DEFINIDOS EN LOS DOCUMENTOS AUTORIZADOS**.

## 7. Requerimientos no funcionales identificados

- Separación entre presentación, lógica de negocio y acceso a datos.
- Uso de PHP, MySQL, HTML, CSS y JavaScript sin sustituciones no autorizadas.
- PDO y consultas preparadas.
- Validación del lado del servidor y escape de salida.
- Sesiones PHP con regeneración de identificador.
- Autorización por roles y protección CSRF.
- Validación de archivos cargados y control de acceso.
- Manejo seguro de errores y configuración por variables de entorno.
- Claves foráneas y restricciones de integridad.
- Trazabilidad de requerimientos, pruebas y cambios mediante Git.
- Organización mantenible similar a MVC, sujeta a confirmación del alcance.

No se definieron métricas cuantitativas de rendimiento, disponibilidad,
compatibilidad, accesibilidad, usabilidad, respaldo, retención o recuperación.

## 8. Exclusiones y límites

### Confirmado

- Laravel, Node.js, Java, Spring Boot, .NET, React, Angular, Vue u otra
  tecnología equivalente no pueden sustituir la pila principal sin
  autorización expresa.
- En Fase 0 no se puede diseñar la base de datos completa ni implementar login,
  CRUD, inscripción, geolocalización, permisos, notificaciones, reportes, PDF o
  cualquier fase posterior.

### No definido

- Exclusiones funcionales generales del producto: **NO DEFINIDO EN LOS
  DOCUMENTOS AUTORIZADOS**.

## 9. Datos pendientes

1. Nombre oficial del proyecto y de la institución.
2. Objetivo general y objetivos específicos.
3. Documento funcional oficial y prioridad de requerimientos.
4. Datos institucionales: dirección, contacto, logotipo y demás identidad.
5. Latitud, longitud y radio autorizado.
6. Grados, secciones, aulas, períodos, capacidades y calendarios oficiales.
7. Roles exactos, permisos por rol y quién crea cada cuenta.
8. Campos obligatorios, formatos y validaciones de docentes, estudiantes y
   encargados.
9. Relaciones permitidas entre estudiantes y encargados.
10. Estados y reglas completas de una inscripción.
11. Formato, unicidad, expiración y recuperación del código de confirmación.
12. Reglas exactas para los casos anómalos de asistencia.
13. Tipos de permiso, campos, adjuntos permitidos, límites y flujo de decisión.
14. Catálogo, canal, destinatario y momento de cada notificación.
15. Contenido y formato oficial del comprobante PDF.
16. Catálogo, filtros, columnas y permisos de cada reporte.
17. Canal y política de expiración de recuperación de contraseña.
18. Política de datos personales, auditoría, retención, respaldo y restauración.
19. Navegadores/dispositivos soportados y requisitos de accesibilidad.
20. Entorno objetivo de despliegue y versiones requeridas de PHP y MySQL.

## 10. Ambigüedades

- La carpeta de ejemplo `escuela-lo-de-fuentes/` podría sugerir un nombre, pero
  el mismo documento declara que la estructura es solo una propuesta técnica.
- “Administración” puede representar un actor, un rol o varios roles; no se
  especifica.
- No se define si el encargado tendrá cuenta autenticada.
- No se define si un estudiante puede tener más de un encargado o múltiples
  inscripciones por período.
- No se define qué ocurre exactamente ante cada caso inválido de asistencia.
- No se define si los permisos requieren uno o varios niveles de aprobación.
- No se define cuáles notificaciones ni reportes están “requeridos por el
  alcance”.
- No se define el contenido del PDF ni su validez institucional.
- No se define el método de recuperación de contraseña.

## 11. Decisiones técnicas propuestas

### DT-001 — Mantener solo una estructura documental en Fase 0

- Problema: el Prompt Maestro ofrece una estructura de aplicación posible,
  pero advierte que debe comprobarse contra el proyecto actual.
- Motivo: faltan el nombre oficial, el documento funcional y el entorno PHP.
- Solución: crear únicamente `docs/fase-0`, `README.md`, `.gitignore` y el
  repositorio Git; aplazar carpetas de aplicación hasta la fase correspondiente.
- Impacto: evita cristalizar supuestos o adelantar trabajo de Fase 2.
- Modificación futura: sí; la arquitectura se definirá en Fase 2 una vez
  aprobado el diseño de datos y confirmado el alcance.

### DT-002 — No ejecutar una conexión PHP → MySQL

- Problema: se solicita una prueba mínima “si corresponde”.
- Motivo: PHP y los clientes MySQL/MariaDB no están disponibles en el entorno y
  no existen host, puerto, base, usuario ni contraseña autorizados.
- Solución: documentar la prueba como bloqueada, sin inventar credenciales ni
  descargar dependencias o imágenes.
- Impacto: la compatibilidad PHP/MySQL queda pendiente.
- Modificación futura: sí; repetir la prueba cuando exista un entorno y una
  configuración autorizados.

## 12. Revisión del directorio y contaminación

El directorio autorizado existe. Antes de la preparación estaba vacío, por lo
que no podía confirmarse su pertenencia a un proyecto mediante archivos
existentes. Su nombre es `ProyectoFinal` y coincide de manera genérica con un
proyecto académico, pero no identifica institución ni sistema.

No se encontraron nombres de otras instituciones, empresas, sistemas,
tecnologías ajenas, scripts de bases de datos ni archivos sospechosos.

**Posible contaminación detectada: NINGUNA.**

## 13. Entorno disponible

| Elemento | Resultado |
|---|---|
| Sistema operativo | Microsoft Windows NT 10.0.26200.0, x64 |
| PowerShell | 7.6.5 |
| Git | 2.54.0.windows.1 |
| Repositorio al inicio | No existía |
| Repositorio preparado | Git inicializado en rama `main` dentro del directorio autorizado |
| PHP | No disponible desde el entorno actual |
| MySQL | No disponible desde el entorno actual |
| MariaDB | No disponible desde el entorno actual |
| Composer | No disponible desde el entorno actual |
| Servicio MySQL/MariaDB | No se detectó un servicio con esos nombres |
| Docker CLI | Disponible; el motor se verificará como prueba de entorno |
| Visual Studio Code CLI | Disponible |

## 14. Estado de preparación

La documentación y el control de versiones de Fase 0 quedan preparados. El
desarrollo funcional no debe comenzar hasta recibir el documento de alcance
faltante o una confirmación expresa de que el Prompt Maestro constituye todo el
alcance, y hasta contar con autorización explícita para la Fase 1.

