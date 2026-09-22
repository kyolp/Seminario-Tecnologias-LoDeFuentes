# Diagnóstico, aislamiento y preparación — Fase 0

Fecha de reauditoría: 2026-09-20
Fecha de validación técnica definitiva: 2026-09-22
Directorio autorizado: `C:\Users\David\Documents\Universidad\2026\Segundo semestre\seminario de tecnologias\ProyectoFinal`

## Declaración de aislamiento

Trabajaré exclusivamente con la información contenida en los documentos y
archivos autorizados para este proyecto.

No utilizaré información de otros proyectos, conversaciones, repositorios,
memorias o sistemas.

## 1. Identificación corregida del proyecto

| Campo | Resultado respaldado por el documento oficial |
|---|---|
| Nombre del proyecto | Sistema web integral para la gestión de inscripciones, asignación de secciones y control de asistencia docente de una institución educativa. |
| Institución | Escuela Oficial Rural Mixta Lo de Fuentes. |
| Ubicación | 13 Calle, Lo de Fuentes, Zona 11, Mixco. |
| Objetivo general | Desarrollar un sistema web integral para gestionar las inscripciones y cupos de estudiantes, la asignación de grados, secciones, aulas y docentes, y el control digital de entrada y salida del personal docente, incorporando validación de ubicación, gestión de permisos y generación de comprobantes y reportes para apoyar la administración de la Escuela Oficial Rural Mixta Lo de Fuentes. |
| Actores funcionales | Dirección / Administración; Personal docente; Padre, madre o encargado. |
| Módulos | Administración / Dirección; Inscripciones; Docente; Integración de la información; Reportes y documentos. |
| Tecnologías | HTML, CSS y JavaScript; PHP; MySQL; arquitectura web con separación entre presentación, lógica de negocio y datos. |
| Requerimientos funcionales | 32, identificados oficialmente como RF-01 a RF-32. |
| Requerimientos no funcionales | 10, identificados oficialmente como RNF-01 a RNF-10. |
| Reglas de negocio | Catorce reglas expresas en la sección 11 del documento oficial. |
| Fuera del alcance | Dieciséis exclusiones expresas en la sección 12 del documento oficial. |

### Objetivos específicos

1. Analizar los procesos actuales de inscripción de estudiantes y control de
   asistencia docente para identificar sus necesidades, deficiencias, reglas de
   negocio y requerimientos funcionales y no funcionales.
2. Diseñar y desarrollar la estructura funcional y tecnológica de un sistema
   web que permita administrar estudiantes, grados, secciones, capacidades,
   aulas, docentes, inscripciones, asistencia y permisos, manteniendo la
   información relacionada en una base de datos central.
3. Implementar y evaluar un prototipo funcional mediante casos de prueba que
   permitan verificar el control de cupos, el registro de asistencia con
   validación de ubicación, la gestión de permisos, las consultas y la
   generación de comprobantes y reportes.

Fuente: portada y secciones 7 y 8 del documento oficial.

## 2. Fuente oficial disponible

Documento oficial utilizado:

`C:\Users\David\Documents\Universidad\2026\Segundo semestre\seminario de tecnologias\Documento_Alcance_Escuela_Lo_de_Fuentes_Andersson_Godoy (1).docx`

El documento define el alcance funcional y operativo de la propuesta y declara
que constituye una línea base propuesta para el prototipo, sujeta a cambios
documentados y aprobados durante el levantamiento institucional.

También se utilizaron el Prompt Maestro, el prompt de reauditoría y los
artefactos existentes de Fase 0, exclusivamente para controlar el proceso y
corregir la documentación.

## 3. Actores funcionales corregidos

El documento define **tres actores o roles funcionales**. El estudiante es una
entidad gestionada por el sistema, pero no aparece como actor independiente en
el organigrama funcional.

| Actor o rol | Funciones principales | Fuente documental |
|---|---|---|
| Dirección / Administración | Administrar usuarios, docentes, estudiantes, grados, secciones, cupos, aulas y asignaciones; revisar permisos; consultar reportes; configurar la ubicación autorizada y supervisar el sistema. | Secciones 3.1 y 9.1. |
| Personal docente | Iniciar sesión; consultar asignación y estudiantes; registrar entrada y salida; consultar historial de asistencia; solicitar permisos y consultar su estado. | Secciones 3.1 y 9.3. |
| Padre, madre o encargado | Realizar la inscripción web; registrar datos; seleccionar grado y sección disponible; verificar cupos; obtener comprobante y consultar por código, sin cuenta en la primera versión. | Secciones 3.1 y 9.2. |

No se cuentan como actores el sistema, navegador, base de datos ni estudiante,
porque el documento no los clasifica como roles funcionales.

## 4. Módulos y alcance confirmado

1. **Administración / Dirección:** usuarios y roles; docentes; estudiantes;
   grados; secciones; capacidades; cupos; aulas; asignaciones; permisos;
   configuración de ubicación; asistencia; reportes e inscripciones.
2. **Inscripciones:** estudiante y encargado; grado; sección disponible;
   validación y actualización de cupo; confirmación; PDF; consulta por código e
   integración con el listado docente.
3. **Docente:** autenticación y recuperación; perfil; asignación; estudiantes;
   permisos; geolocalización; entrada, salida e historial.
4. **Integración de la información:** asociación de inscripciones con grado,
   sección, docente y aula; configuración dinámica por período.
5. **Reportes y documentos:** comprobante PDF; consultas de estudiantes y
   cupos; reportes e historial de asistencia; consulta de permisos.

Fuente: sección 9 del documento oficial.

## 5. Reglas de negocio confirmadas

1. Una sección no podrá aceptar más estudiantes que la capacidad configurada
   por administración.
2. Una sección llena no deberá aparecer como opción disponible para nuevas
   inscripciones.
3. Si todas las secciones habilitadas de un grado están llenas, el sistema
   deberá indicar que no hay cupos disponibles.
4. No se implementará lista de espera en la primera versión.
5. La cantidad de secciones y su capacidad serán configurables por período y no
   estarán fijadas en el código.
6. Cada inscripción confirmada deberá generar un código de confirmación.
7. El padre, madre o encargado no tendrá cuenta de usuario en la primera
   versión.
8. El docente deberá iniciar sesión antes de intentar marcar asistencia.
9. El sistema deberá solicitar y validar la ubicación del dispositivo antes de
   permitir el marcaje.
10. El marcaje solo será válido cuando el dispositivo se encuentre dentro del
    radio autorizado por administración.
11. La entrada y salida deberán quedar registradas con fecha y hora.
12. El docente no podrá acceder a funciones administrativas.
13. Las solicitudes de permiso deberán tener un estado y conservar su
    historial.
14. Los grados, secciones, aulas y asignaciones deberán estar relacionados para
    que la inscripción alimente automáticamente el listado docente.

Fuente: sección 11 del documento oficial.

## 6. Criterios de aceptación confirmados

1. Crear grados, secciones y capacidades sin modificar el código.
2. Impedir superar el cupo máximo configurado.
3. Asociar cada inscripción confirmada con su grado y sección.
4. Permitir que el docente asignado visualice los estudiantes de su sección.
5. Permitir entrada y salida únicamente después de validar la ubicación.
6. Conservar fecha y hora en los registros de asistencia.
7. Enviar, notificar, aprobar o rechazar y consultar posteriormente una
   solicitud de permiso.
8. Generar un comprobante de inscripción en PDF.
9. Generar un reporte de asistencia docente.
10. Mostrar a cada usuario solo las funciones correspondientes a su rol.
11. Documentar casos de prueba exitosos y casos de validación o error.

Fuente: sección 14 del documento oficial.

## 7. Requerimientos no funcionales confirmados

| ID | Categoría | Descripción oficial |
|---|---|---|
| RNF-01 | Usabilidad | Formularios y botones claros, con mensajes comprensibles para usuarios no técnicos. |
| RNF-02 | Compatibilidad | Funcionamiento en navegadores web modernos, computadoras y dispositivos compatibles con geolocalización. |
| RNF-03 | Seguridad | Contraseñas no almacenadas en texto plano y acceso controlado por roles. |
| RNF-04 | Privacidad | Datos personales accesibles únicamente a usuarios autorizados. |
| RNF-05 | Integridad | Relaciones consistentes para registros de inscripción, cupos y asistencia. |
| RNF-06 | Trazabilidad | Fecha y usuario asociado en asistencia y decisiones de permisos cuando corresponda. |
| RNF-07 | Rendimiento | Respuesta en tiempo razonable bajo la carga prevista para la institución. |
| RNF-08 | Mantenibilidad | Separación entre presentación, lógica de negocio y acceso a datos. |
| RNF-09 | Configurabilidad | Grados, secciones, capacidades, aulas y ubicación modificables desde administración sin alterar código. |
| RNF-10 | Disponibilidad | Dependencia de la infraestructura donde se aloje el prototipo o futura instalación. |

Fuente: sección 10.2 del documento oficial. Las reglas, criterios, limitaciones y
decisiones técnicas no se contabilizan como RNF adicionales.

## 8. Exclusiones y límites confirmados

No forman parte de la primera versión:

1. Gestión de notas o calificaciones académicas.
2. Pagos, cobros o facturación.
3. Inventario institucional.
4. Biblioteca y préstamos.
5. Transporte escolar.
6. Mensajería o chat interno.
7. Aplicación móvil nativa para Android o iOS.
8. Reconocimiento facial o Face ID.
9. Lectores de huella u otros dispositivos biométricos.
10. Código QR como mecanismo principal de asistencia o inscripción.
11. Lista de espera para cupos.
12. Estadísticas y analítica avanzada como requisito principal.
13. Integración con plataformas gubernamentales o sistemas externos no
    definidos.
14. Infraestructura física especializada.
15. Auditoría integral de ciberseguridad.
16. Garantía de puesta en producción permanente; el entregable es un prototipo
    funcional evaluable.

Fuente: sección 12 del documento oficial.

## 9. Información pendiente de confirmación institucional

1. Razón social o denominación legal exacta.
2. Misión, visión y valores oficiales.
3. Logotipo oficial y lineamientos de identidad visual.
4. Año de fundación y reseña histórica.
5. Teléfonos y correos institucionales.
6. Sitio web y redes sociales oficiales, si existen.
7. Nombre y cargo del director o stakeholder que aprobará los avances.
8. Fechas exactas de inicio, hitos y entrega.
9. Infraestructura tecnológica disponible.
10. Política institucional para tratamiento y resguardo de datos personales.
11. Ubicación exacta de referencia para geolocalización y radio permitido.
12. Capacidades iniciales por grado y sección para la demostración.

Fuente: sección 17 del documento oficial.

## 10. Ambigüedades y precisiones pendientes

- La institución se identifica como Escuela Oficial Rural Mixta Lo de Fuentes,
  pero su razón social o denominación legal exacta sigue pendiente.
- Los 12 docentes y 2 colaboradores administrativos se presentan como
  información previamente definida, sin constancia de confirmación
  institucional dentro del documento.
- “Tiempo razonable” y “navegadores modernos” no tienen métricas o versiones
  concretas.
- El mecanismo definitivo de notificaciones y recuperación de contraseña aún
  debe definirse.
- La ubicación exacta y el radio de geolocalización siguen pendientes.
- El documento no define reglas precisas para entrada duplicada, salida
  duplicada o salida sin entrada.
- El documento contiene un salto de numeración de la sección 1 a la sección 3;
  no aparece una sección 2.

## 11. Decisiones técnicas de Fase 0

### DT-001 — Mantener la estructura documental

- Problema: la estructura de aplicación corresponde a fases posteriores.
- Motivo: esta reauditoría solo corrige documentación de Fase 0.
- Solución: conservar únicamente la estructura documental y Git ya preparados.
- Impacto: no se adelanta arquitectura, base de datos ni funcionalidad.
- Posibilidad de modificación futura: sí, en la fase autorizada correspondiente.

### DT-002 — Validar la conexión PHP → MySQL con recursos temporales

- Problema resuelto: PHP, PDO, `pdo_mysql` y MySQL ya están disponibles y
  operativos en el entorno local.
- Motivo: era necesario comprobar la cadena PHP → PDO → MySQL → `SELECT 1` sin
  diseñar el esquema funcional del sistema.
- Solución: crear una base y un usuario locales temporales con permiso `SELECT`
  mínimo, ejecutar la prueba mediante PHP CLI y eliminarlos al terminar.
- Impacto: la conectividad técnica quedó verificada sin crear tablas,
  funcionalidades o credenciales permanentes.
- Posibilidad de modificación futura: la configuración real de la aplicación
  se definirá únicamente en la fase autorizada correspondiente.

## 12. Revisión del directorio y contaminación

El directorio autorizado existe, corresponde al repositorio preparado para el
proyecto y contiene únicamente los artefactos creados durante la Fase 0. No se
encontraron nombres de otras instituciones, empresas, sistemas, tecnologías
ajenas ni scripts de bases de datos.

**Posible contaminación detectada: NINGUNA.**

## 13. Entorno técnico verificado nuevamente

| Elemento | Resultado actual |
|---|---|
| Sistema operativo | Microsoft Windows NT 10.0.26200.0, x64. |
| PowerShell | 7.6.5. |
| Git | 2.54.0.windows.1; repositorio en rama `main`; historial intacto. |
| PHP | 8.2.12 CLI, x64 ZTS; disponible mediante `PATH` en `C:\xampp\php\php.exe`. |
| php.ini | Cargado desde `C:\xampp\php\php.ini`. |
| PDO | Disponible y verificado. |
| pdo_mysql | Disponible y verificado; controlador PDO `mysql`. |
| MySQL | Community Server 8.0.42 para Win64, disponible mediante `PATH`. |
| Servicio MySQL | `MySQL80` en estado `Running`, inicio `Automatic`. |
| Conectividad MySQL | `127.0.0.1:3306` en escucha; prueba TCP satisfactoria. |
| PHP → MySQL | Conexión PDO satisfactoria; `SELECT 1` devolvió `1`. |
| Recursos temporales | Base y usuario de diagnóstico eliminados después de la prueba. |

## 14. Estado de preparación

La identidad, los actores y la línea base documental permanecen sin cambios.
Git está configurado, no existe contaminación detectada y la validación técnica
quedó completada: PHP, PDO, `pdo_mysql`, MySQL Community Server, el servicio
`MySQL80`, el puerto 3306, la conexión PDO y `SELECT 1` fueron verificados.
No quedaron credenciales, archivos de prueba, usuarios ni bases temporales.
Por ello, la Fase 0 queda **APROBABLE**.

No se ha iniciado ni preparado trabajo de la Fase 1.
