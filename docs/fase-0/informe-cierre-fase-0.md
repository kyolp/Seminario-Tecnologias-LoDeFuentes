============================================================
# REAUDITORÍA Y CIERRE — FASE 0
============================================================

## 1. MOTIVO DE LA REAUDITORÍA

La Fase 0 terminó como `APROBABLE CON OBSERVACIONES` porque el Prompt Maestro
era la única fuente disponible: el nombre, la institución y el objetivo se
registraron como no definidos; se contabilizaron cuatro actores y dieciocho RNF
derivados de instrucciones generales; además, PHP y MySQL no estaban
disponibles.

La incorporación del documento oficial de alcance hizo necesario corregir de
forma limitada la identidad, los actores y la trazabilidad, y volver a verificar
el entorno técnico, sin iniciar la Fase 1 ni implementar funcionalidades.

## 2. DOCUMENTO OFICIAL UTILIZADO

`C:\Users\David\Documents\Universidad\2026\Segundo semestre\seminario de tecnologias\Documento_Alcance_Escuela_Lo_de_Fuentes_Andersson_Godoy (1).docx`

También se consultaron exclusivamente el Prompt Maestro, el prompt de
reauditoría y los tres artefactos existentes de Fase 0 dentro del directorio
autorizado.

## 3. IDENTIDAD CORREGIDA DEL PROYECTO

Nombre: Sistema web integral para la gestión de inscripciones, asignación de
secciones y control de asistencia docente de una institución educativa.

Institución: Escuela Oficial Rural Mixta Lo de Fuentes.

Ubicación: 13 Calle, Lo de Fuentes, Zona 11, Mixco.

Objetivo: Desarrollar un sistema web integral para gestionar las inscripciones
y cupos de estudiantes, la asignación de grados, secciones, aulas y docentes, y
el control digital de entrada y salida del personal docente, incorporando
validación de ubicación, gestión de permisos y generación de comprobantes y
reportes para apoyar la administración de la Escuela Oficial Rural Mixta Lo de
Fuentes.

Fuente: portada y sección 7 del documento oficial.

## 4. ACTORES CORRECTOS

Cantidad: **3 actores o roles funcionales**.

| Actor o rol | Funciones principales | Sección documental |
|---|---|---|
| Dirección / Administración | Administrar usuarios, docentes, estudiantes, grados, secciones, cupos, aulas y asignaciones; revisar permisos; consultar reportes; configurar ubicación y supervisar el sistema. | 3.1 y 9.1 |
| Personal docente | Iniciar sesión; consultar asignación y estudiantes; registrar entrada/salida e historial; solicitar permisos y consultar su estado. | 3.1 y 9.3 |
| Padre, madre o encargado | Realizar la inscripción; seleccionar grado y sección; verificar cupos; obtener comprobante y consultar por código, sin cuenta en la primera versión. | 3.1 y 9.2 |

El estudiante no se cuenta como actor: el alcance lo define como entidad
gestionada, no como rol funcional o usuario independiente.

## 5. REQUERIMIENTOS FUNCIONALES

Cantidad: **32**.
Rango: **RF-01 a RF-32**.
Primer RF: RF-01 — Autenticación.
Último RF: RF-32 — Integridad de cupos.

La comparación automatizada entre la tabla del documento y la matriz confirmó
un rango continuo, identificadores idénticos, 32 valores únicos y ausencia de
duplicados. No se agregaron RF ajenos al documento.

## 6. REQUERIMIENTOS NO FUNCIONALES

Cantidad: **10**.
Rango: **RNF-01 a RNF-10**.

| ID | Categoría |
|---|---|
| RNF-01 | Usabilidad |
| RNF-02 | Compatibilidad |
| RNF-03 | Seguridad |
| RNF-04 | Privacidad |
| RNF-05 | Integridad |
| RNF-06 | Trazabilidad |
| RNF-07 | Rendimiento |
| RNF-08 | Mantenibilidad |
| RNF-09 | Configurabilidad |
| RNF-10 | Disponibilidad |

No se contabilizaron como RNF las reglas de negocio, criterios de aceptación,
limitaciones, controles del Prompt Maestro ni decisiones técnicas.

## 7. REGLAS DE NEGOCIO

La sección 11 del documento contiene **14 reglas**:

1. No superar la capacidad configurada de una sección.
2. No mostrar secciones llenas para nuevas inscripciones.
3. Informar cuando no existan cupos en el grado.
4. No implementar lista de espera en la primera versión.
5. Configurar secciones y capacidades por período, sin fijarlas en código.
6. Generar un código por cada inscripción confirmada.
7. No crear cuenta para el padre, madre o encargado en la primera versión.
8. Exigir inicio de sesión docente antes del marcaje.
9. Solicitar y validar ubicación antes del marcaje.
10. Aceptar el marcaje solo dentro del radio autorizado.
11. Registrar entrada y salida con fecha y hora.
12. Impedir acceso docente a funciones administrativas.
13. Conservar estado e historial de permisos.
14. Relacionar grados, secciones, aulas y asignaciones para alimentar el listado
    docente.

## 8. CRITERIOS DE ACEPTACIÓN

La sección 14 del documento contiene **11 criterios**:

1. Crear grados, secciones y capacidades sin modificar código.
2. Impedir superar el cupo máximo.
3. Asociar la inscripción confirmada con grado y sección.
4. Mostrar al docente los estudiantes de su sección.
5. Permitir entrada y salida solo después de validar ubicación.
6. Conservar fecha y hora de asistencia.
7. Enviar, notificar, aprobar/rechazar y consultar permisos.
8. Generar el comprobante PDF de inscripción.
9. Generar un reporte de asistencia docente.
10. Mostrar únicamente funciones correspondientes al rol.
11. Documentar pruebas exitosas y de validación/error.

## 9. CORRECCIONES REALIZADAS

### Cambio 1

Archivo: `docs/fase-0/diagnostico.md`
Sección modificada: Identificación del proyecto.
Valor anterior: nombre, institución y objetivo “NO DEFINIDO EN LOS DOCUMENTOS
AUTORIZADOS”.
Valor nuevo: título, Escuela Oficial Rural Mixta Lo de Fuentes, ubicación y
objetivo general literales del documento.
Motivo: incorporación de la fuente oficial.
Fuente documental: portada y secciones 7 y 8.

### Cambio 2

Archivo: `docs/fase-0/diagnostico.md`
Sección modificada: Actores.
Valor anterior: cuatro actores: encargado, estudiante, docente y administración.
Valor nuevo: tres roles: Dirección / Administración, Personal docente y Padre,
madre o encargado.
Motivo: el estudiante es una entidad gestionada, no un actor funcional definido.
Fuente documental: secciones 3.1, 9.1, 9.2 y 9.3.

### Cambio 3

Archivo: `docs/fase-0/diagnostico.md`
Sección modificada: Módulos, reglas, criterios, exclusiones, pendientes y
ambigüedades.
Valor anterior: extracción parcial del Prompt Maestro, con catálogos y datos
institucionales indicados como no definidos.
Valor nuevo: cinco áreas de alcance, 14 reglas, 11 criterios, 16 exclusiones y
12 pendientes institucionales extraídos del documento oficial.
Motivo: reemplazar inferencias preliminares por la línea base aprobable.
Fuente documental: secciones 9, 11, 12, 14, 15 y 17.

### Cambio 4

Archivo: `docs/fase-0/diagnostico.md`
Sección modificada: Entorno técnico y estado de preparación.
Valor anterior: Docker pendiente de comprobación y documento funcional ausente.
Valor nuevo: Docker cliente 28.0.4 verificado con motor inactivo; documento
oficial disponible; PHP/MySQL continúan ausentes y la conectividad permanece
bloqueada.
Motivo: reflejar el estado actual del entorno y resolver la observación
documental.
Fuente documental: comandos ejecutados durante la reauditoría.

### Cambio 5

Archivo: `docs/fase-0/matriz-trazabilidad.md`
Sección modificada: Requerimientos funcionales.
Valor anterior: RF-001 a RF-032, descripciones derivadas del Prompt Maestro y
algunas correspondencias distintas a la línea base.
Valor nuevo: RF-01 a RF-32 con los nombres y alcances de la sección 10.1.
Motivo: igualar identificadores, orden y contenido con el documento oficial.
Fuente documental: tabla de requerimientos funcionales de la sección 10.1.

### Cambio 6

Archivo: `docs/fase-0/matriz-trazabilidad.md`
Sección modificada: Requerimientos no funcionales.
Valor anterior: 18 RNF derivados de instrucciones técnicas y de proceso.
Valor nuevo: 10 RNF oficiales, RNF-01 a RNF-10.
Motivo: no convertir reglas, criterios, limitaciones o decisiones en RNF.
Fuente documental: tabla de requerimientos no funcionales de la sección 10.2.

### Cambio 7

Archivo: `docs/fase-0/matriz-trazabilidad.md`
Sección modificada: Clasificación complementaria.
Valor anterior: reglas, criterios y limitaciones no estaban trazados como
categorías independientes.
Valor nuevo: secciones separadas para 14 reglas, 11 criterios y 6 limitaciones,
con identificadores internos claramente señalados.
Motivo: conservar la clasificación del documento sin alterar los conteos RF/RNF.
Fuente documental: secciones 11, 14 y 15.

### Cambio 8

Archivo: `docs/fase-0/informe-cierre-fase-0.md`
Sección modificada: Informe completo de cierre.
Valor anterior: informe de la primera auditoría basado únicamente en el Prompt
Maestro.
Valor nuevo: informe de reauditoría con identidad, conteos, entorno, control de
cambios y resultado actualizados.
Motivo: cerrar formalmente la corrección controlada solicitada.
Fuente documental: documento oficial, verificaciones actuales y diff de Git.

## 10. ENTORNO TÉCNICO

PHP: **NO DISPONIBLE**. `php --version` no reconoce un ejecutable.
MySQL: **NO DISPONIBLE**. `mysql --version` no reconoce un ejecutable y no se
detectó servicio MySQL/MariaDB.
Git: **DISPONIBLE**. Versión 2.54.0.windows.1; rama `main`; historial preservado.
Docker: cliente 28.0.4, build b8034c0; **Docker Engine NO ACTIVO**.

Composer y MariaDB tampoco están disponibles. No se instaló ni sustituyó
ninguna tecnología.

## 11. PRUEBA PHP → MYSQL

Estado: **BLOQUEADA**.

Resultado: no fue posible ejecutar la conexión porque faltan el runtime PHP, un
cliente o servidor MySQL operativo y parámetros autorizados de conexión. El
motor Docker tampoco está activo. No se creó base temporal, tabla ni archivo de
conectividad, y no se utilizaron credenciales reales o inventadas.

Versión PHP: no disponible.
Versión MySQL: no disponible.
Host: no definido.
Puerto: no definido.
Mecanismo previsto: PDO MySQL, pendiente de disponibilidad.

## 12. ARCHIVOS MODIFICADOS

1. `docs/fase-0/diagnostico.md`
2. `docs/fase-0/matriz-trazabilidad.md`
3. `docs/fase-0/informe-cierre-fase-0.md`

No se creó ni modificó código PHP, esquema SQL, configuración de aplicación o
artefacto correspondiente a una fase posterior.

## 13. COMMITS

Hash: `4140745`
Mensaje: `chore(fase-0): preparar diagnóstico y trazabilidad inicial`

Hash: `3c5341f`
Mensaje: `docs(fase-0): registrar informe de cierre`

Hash: `5b74af5`
Mensaje: `docs(fase-0): corregir diagnostico y trazabilidad`

El presente informe será incorporado mediante un commit documental de cierre
después de su generación; su hash se comunica al usuario porque no puede
incluirse dentro de sí mismo antes de existir.

## 14. AUDITORÍA DE AISLAMIENTO

[x] Solo se utilizó el directorio autorizado para archivos del proyecto.
[x] Solo se utilizó el documento oficial adjunto y los prompts autorizados.
[x] No se utilizaron otros proyectos.
[x] No se utilizaron memorias externas.
[x] No se desarrolló ninguna fase posterior.
[x] No se detectó contaminación de otros proyectos.
[x] Se preservó el historial Git.
[x] No se instalaron ni sustituyeron tecnologías.

## 15. OBSERVACIONES PENDIENTES

1. Instalar o habilitar un runtime PHP compatible.
2. Instalar o habilitar un servidor y cliente MySQL compatible.
3. Proporcionar una configuración de diagnóstico autorizada: host, puerto, base
   y credenciales seguras, sin versionar secretos.
4. Ejecutar y documentar una conexión mínima PHP → MySQL.
5. Confirmar los doce datos institucionales pendientes de la sección 17 del
   documento, especialmente coordenadas, radio, capacidades e infraestructura.
6. Definir métricas verificables para “tiempo razonable” y versiones de
   navegadores compatibles en una fase posterior autorizada.

Componente exacto que impide cerrar completamente la Fase 0: entorno PHP/MySQL
operativo y prueba de conexión satisfactoria.

## 16. RESULTADO FINAL DE FASE 0

**APROBABLE CON OBSERVACIONES**

La identidad, actores y trazabilidad quedaron corregidos y verificados; no hay
contaminación y Git funciona correctamente. No se selecciona `APROBABLE` porque
el entorno requerido continúa incompleto y la prueba PHP → MySQL está
bloqueada, incumpliendo los puntos 8 y 9 del criterio de cierre solicitado.

## 17. AUTORIZACIÓN

“LA REAUDITORÍA DE FASE 0 HA FINALIZADO.

NO INICIARÉ LA FASE 1 HASTA RECIBIR AUTORIZACIÓN
EXPLÍCITA DEL USUARIO.”

============================================================
# FIN DE LA REAUDITORÍA
============================================================
