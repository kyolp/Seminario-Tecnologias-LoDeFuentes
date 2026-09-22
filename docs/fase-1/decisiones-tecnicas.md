# Decisiones técnicas de base de datos — Fase 1

## DT-01

**Tema:** Convenciones del modelo.

**Problema:** El alcance no define nombres físicos ni tipos SQL.

**Información disponible:** El proyecto y la documentación están en español y
MySQL 8.0.42 está validado.

**Decisión:** Usar español, `snake_case`, PK `id_<entidad>`, `utf8mb4` y
`utf8mb4_0900_ai_ci`.

**Justificación:** Mantiene uniformidad y compatibilidad con el entorno.

**Impacto:** Todos los scripts y documentos comparten una convención.

**Requerimientos relacionados:** RNF-05, RNF-08.

**¿Configurable posteriormente?:** No aplica; es una convención técnica.

## DT-02

**Tema:** Usuarios, roles y docentes.

**Problema:** Deben evitarse datos personales duplicados y no debe autenticarse
al encargado.

**Información disponible:** Solo administración y docentes inician sesión; el
encargado no tiene cuenta.

**Decisión:** Guardar identidad y credencial en `usuarios`; relacionar
`docentes` 1:1 con usuario; mantener `encargados` sin credenciales.

**Justificación:** Satisface seguridad y evita duplicar nombres y correo.

**Impacto:** Un docente no puede existir sin usuario asociado.

**Requerimientos relacionados:** RF-01, RF-02, RF-05, RF-15, RN-DOC-07.

**¿Configurable posteriormente?:** Sí, sin crear autenticación para encargados
en esta versión.

## DT-03

**Tema:** Relación estudiante–encargado.

**Problema:** El documento no define si la relación es uno a uno o muchos a
muchos.

**Información disponible:** Un encargado se asocia a una inscripción.

**Decisión:** Modelar muchos a muchos mediante `estudiantes_encargados` y hacer
que cada inscripción señale al encargado responsable.

**Justificación:** Permite hermanos y múltiples encargados sin imponer una regla
institucional no confirmada.

**Impacto:** La BD valida que el encargado de la inscripción esté vinculado con
el estudiante.

**Requerimientos relacionados:** RF-05, RF-11, RNF-05.

**¿Configurable posteriormente?:** Sí.

## DT-04

**Tema:** Configuración temporal de grados y secciones.

**Problema:** La oferta cambia por ciclo escolar.

**Información disponible:** Grados, secciones y capacidad deben configurarse
por período.

**Decisión:** Usar `periodos_escolares → grados_periodos → secciones`.

**Justificación:** Evita fijar la oferta anual en el catálogo `grados`.

**Impacto:** Un mismo grado puede tener secciones y capacidades distintas cada
período.

**Requerimientos relacionados:** RF-06 a RF-08, RN-DOC-05, RNF-09.

**¿Configurable posteriormente?:** Sí, desde administración.

## DT-05

**Tema:** Cupos.

**Problema:** Guardar ocupados o disponibles duplicaría información y permitiría
inconsistencias.

**Información disponible:** Debe mantenerse `inscritos <= capacidad`, incluso
con operaciones concurrentes.

**Decisión:** Guardar solo la capacidad y calcular ocupados desde inscripciones
confirmadas. La futura confirmación usará transacción y bloqueo de la sección.

**Justificación:** Elimina contadores redundantes y permite satisfacer RF-32.

**Impacto:** La capa de servicio deberá ejecutar `SELECT ... FOR UPDATE`.

**Requerimientos relacionados:** RF-08 a RF-10, RF-32, RN-DOC-01 a RN-DOC-03.

**¿Configurable posteriormente?:** La capacidad sí; la estrategia de integridad
no debe relajarse.

## DT-06

**Tema:** Datos directos y derivados de la inscripción.

**Problema:** Repetir período y grado puede producir contradicciones.

**Información disponible:** Toda sección pertenece a un grado habilitado en un
período.

**Decisión:** Almacenar estudiante, encargado responsable y sección; derivar
grado y período por relaciones.

**Justificación:** Mantiene la normalización y la coherencia.

**Impacto:** Las consultas administrativas incorporarán dos uniones adicionales.

**Requerimientos relacionados:** RF-11, RF-30, RF-31, RNF-05.

**¿Configurable posteriormente?:** No se recomienda duplicar los datos.

## DT-07

**Tema:** Asignaciones docentes.

**Problema:** Grado, sección y aula cambian por período y debe conservarse
historial.

**Información disponible:** El docente consulta una asignación vigente.

**Decisión:** Crear `asignaciones_docentes` con fechas y estado; garantizar una
sola asignación activa por sección mediante una clave generada única.

**Justificación:** Evita fijar la asignación dentro de `docentes`.

**Impacto:** Un docente puede atender varias secciones y una sección conserva
sus reasignaciones históricas.

**Requerimientos relacionados:** RF-16, RF-17, RF-31, RN-DOC-14.

**¿Configurable posteriormente?:** Sí.

## DT-08

**Tema:** Asistencia.

**Problema:** El documento no define reglas exactas para marcajes duplicados.

**Información disponible:** Cada marcaje requiere docente, fecha, hora y tipo.

**Decisión:** Registrar cada entrada o salida como evento independiente; no
imponer todavía unicidad diaria.

**Justificación:** Preserva el historial sin inventar reglas de jornada.

**Impacto:** La lógica de duplicados se definirá antes de implementar el módulo.

**Requerimientos relacionados:** RF-20 a RF-23, RN-DOC-11.

**¿Configurable posteriormente?:** Sí.

## DT-09

**Tema:** Configuración geográfica.

**Problema:** La ubicación debe ser editable y cada marcaje debe poder auditar
la configuración aplicada.

**Información disponible:** Latitud, longitud y radio están pendientes de
confirmación.

**Decisión:** Versionar configuraciones de ubicación y referenciar una desde
cada marcaje; no incluir coordenadas en los seeds.

**Justificación:** Conserva trazabilidad sin inventar datos institucionales.

**Impacto:** Solo una configuración puede estar activa.

**Requerimientos relacionados:** RF-18, RF-19, RNF-06, RNF-09.

**¿Configurable posteriormente?:** Sí.

## DT-10

**Tema:** Permisos e historial.

**Problema:** Actualizar únicamente el estado borraría la evidencia de
decisiones anteriores.

**Información disponible:** Debe conservarse estado, decisión, usuario y fecha.

**Decisión:** Mantener estado vigente en `solicitudes_permiso` y cada transición
en `historial_solicitudes_permiso`.

**Justificación:** Cumple trazabilidad sin una auditoría genérica.

**Impacto:** La futura capa de servicio actualizará ambos registros en una
transacción.

**Requerimientos relacionados:** RF-24, RF-27, RF-28, RNF-06, RN-DOC-13.

**¿Configurable posteriormente?:** Los estados adicionales requerirán una
decisión aprobada.

## DT-11

**Tema:** Documentos de respaldo.

**Problema:** Guardar binarios en MySQL aumentaría tamaño y acoplamiento.

**Información disponible:** Solo se exige adjuntar documentación.

**Decisión:** Persistir nombre, ruta, MIME, tamaño y fecha; guardar el archivo
fuera de MySQL en una fase posterior.

**Justificación:** Mantiene la BD enfocada en metadatos.

**Impacto:** La capa de archivos deberá proteger y validar la ruta.

**Requerimientos relacionados:** RF-25, RNF-04.

**¿Configurable posteriormente?:** Sí.

## DT-12

**Tema:** Notificaciones.

**Problema:** El mecanismo de correo aún no está definido.

**Información disponible:** Administración debe recibir un aviso por nueva
solicitud.

**Decisión:** Persistir notificaciones internas vinculadas a permisos; no crear
integraciones externas.

**Justificación:** Proporciona soporte de datos sin anticipar correo.

**Impacto:** El envío externo queda para una fase autorizada.

**Requerimientos relacionados:** RF-26, LIM-DOC-02.

**¿Configurable posteriormente?:** Sí.

## DT-13

**Tema:** Recuperación de contraseña.

**Problema:** Debe soportarse uso único sin guardar el token secreto.

**Información disponible:** El mecanismo final puede usar enlace o código.

**Decisión:** Guardar solo `token_hash`, expiración, estado y fecha de uso.

**Justificación:** Reduce exposición y admite ambos mecanismos.

**Impacto:** El token original existirá solo durante la solicitud futura.

**Requerimientos relacionados:** RF-03, RNF-03.

**¿Configurable posteriormente?:** Sí.

## DT-14

**Tema:** Desactivación y borrado.

**Problema:** El borrado físico puede destruir históricos.

**Información disponible:** El alcance usa desactivar, habilitar y deshabilitar.

**Decisión:** Usar estado o `activo` en usuarios, docentes, estudiantes, grados,
secciones y aulas; proteger históricos con FK `RESTRICT`.

**Justificación:** Preserva inscripciones, asistencias y permisos.

**Impacto:** Las operaciones normales no eliminarán entidades referenciadas.

**Requerimientos relacionados:** RF-04, RF-06, RF-07, RF-15, RNF-05.

**¿Configurable posteriormente?:** Sí, sin perder historia.

## DT-15

**Tema:** Auditoría técnica.

**Problema:** Una auditoría empresarial excedería el alcance.

**Información disponible:** La trazabilidad obligatoria se concentra en
asistencia y decisiones de permisos.

**Decisión:** No crear una tabla genérica de auditoría; usar entidades
específicas de marcajes, configuración e historial de permisos.

**Justificación:** Cubre RNF-06 con menor complejidad y mayor integridad.

**Impacto:** Nuevos eventos auditables exigirán una decisión futura.

**Requerimientos relacionados:** RNF-06.

**¿Configurable posteriormente?:** Sí.

## DT-16

**Tema:** Datos básicos de estudiantes y encargados.

**Problema:** El documento no enumera campos exactos.

**Información disponible:** Solo exige datos básicos y datos necesarios del
encargado.

**Decisión:** **DECISIÓN TÉCNICA PROPUESTA:** nombres y apellidos obligatorios;
fecha de nacimiento y código institucional opcionales para estudiante;
teléfono y correo opcionales para encargado.

**Justificación:** Permite el prototipo sin inventar identificadores oficiales,
direcciones o datos obligatorios no confirmados.

**Impacto:** El levantamiento institucional podrá ajustar campos antes de la
interfaz funcional.

**Requerimientos relacionados:** RF-04, RF-05, RNF-04.

**¿Configurable posteriormente?:** Sí, mediante cambio aprobado.

## DT-17

**Tema:** Marcas de tiempo.

**Problema:** Agregar fechas a todas las tablas produciría ruido sin utilidad.

**Información disponible:** Se exige trazabilidad en operaciones concretas.

**Decisión:** Usar fechas de creación/actualización en entidades mutables y
fechas específicas en eventos; omitirlas en catálogos asociativos cuando no
aportan evidencia adicional.

**Justificación:** Conserva trazabilidad útil sin campos indiscriminados.

**Impacto:** El diccionario identifica cada uso.

**Requerimientos relacionados:** RNF-06, RNF-08.

**¿Configurable posteriormente?:** Sí.
