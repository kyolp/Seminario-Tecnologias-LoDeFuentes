# Pruebas de base de datos — Fase 1

## Entorno verificado

- Motor: MySQL Community Server 8.0.42.
- Cliente de ejecución: MySQL Workbench 8.0 CE.
- Motor de tablas: InnoDB.
- Juego de caracteres: `utf8mb4`.
- Intercalación: `utf8mb4_0900_ai_ci`.
- Fecha de ejecución: 2026-09-23.
- Base utilizada: esquema temporal aislado, sin datos institucionales reales.

## Procedimiento reproducible

1. Crear una base vacía con `utf8mb4_0900_ai_ci`.
2. Seleccionarla como base activa.
3. Ejecutar `database/schema.sql`.
4. Ejecutar `database/seed.sql`.
5. Ejecutar `database/tests/validacion.sql` una sola vez sobre esa base nueva.
6. Comprobar el resumen final: 18 ejecutadas, 18 exitosas y 0 fallidas.
7. Destruir la base temporal al finalizar la validación.

El script de validación crea únicamente un procedimiento y datos ficticios en
la base desechable. No debe ejecutarse sobre una base productiva ni sobre una
base que ya contenga los seeds.

## Resultado de recreación

La ejecución integral desde una base nueva procesó 78 sentencias: creación de
base, 20 tablas, restricciones e índices, seeds ficticios, procedimiento de
validación y sus dos conjuntos de resultados. La recreación corregida no
produjo errores. Los avisos deprecados de `TINYINT(1)` observados en la primera
corrida fueron eliminados cambiando los indicadores a `TINYINT UNSIGNED`.

## Matriz de pruebas ejecutadas

| # | Prueba | Resultado esperado | Resultado observado | Estado |
|---:|---|---|---|---|
| 1 | Cantidad de tablas | 20 | 20 | OK |
| 2 | Cobertura de claves primarias | 20 tablas | 20 tablas | OK |
| 3 | Cantidad de claves foráneas | 26 | 26 | OK |
| 4 | PK rechaza duplicado | Error MySQL 1062 | Error 1062 | OK |
| 5 | FK rechaza referencia inexistente | Error MySQL 1452 | Error 1452 | OK |
| 6 | UNIQUE rechaza valor repetido | Error MySQL 1062 | Error 1062 | OK |
| 7 | NOT NULL rechaza ausencia | Error MySQL 1048 | Error 1048 | OK |
| 8 | CHECK rechaza capacidad cero | Error MySQL 3819 | Error 3819 | OK |
| 9 | Solo un período activo | Error MySQL 1062 | Error 1062 | OK |
| 10 | Solo una asignación activa por sección | Error MySQL 1062 | Error 1062 | OK |
| 11 | Consulta integral de inscripción | 1 fila relacionada | 1 fila | OK |
| 12 | Consulta de asignación docente | 1 fila relacionada | 1 fila | OK |
| 13 | Cupos iniciales calculados | 29 disponibles | 29 disponibles | OK |
| 14 | Ocupación de sección | 30 confirmadas de 30 | 30 confirmadas | OK |
| 15 | Decisión de cupo al llegar a 30/30 | RECHAZAR | RECHAZAR | OK |
| 16 | Persistencia de marcaje válido | 1 marcaje | 1 marcaje | OK |
| 17 | Flujo permiso–historial–documento–notificación | 1 relación completa | 1 relación | OK |
| 18 | Token de recuperación no vencido | 1 token activo | 1 token | OK |

**Resumen:** 18 pruebas ejecutadas, 18 exitosas, 0 fallidas.

## Validación de cupos y concurrencia

La prueba llenó una sección ficticia hasta 30 inscripciones confirmadas y
verificó que el cálculo de disponibilidad devolviera cero y la decisión fuera
`RECHAZAR`. La base no incorpora un disparador que cuente otras filas, porque
esa solución dificultaría el control transaccional. RF-32 deberá implementarse
en la futura capa de servicio mediante transacción, bloqueo de la sección con
`SELECT ... FOR UPDATE`, conteo de confirmadas y confirmación solo si existe
disponibilidad.

## Datos de prueba

Los nombres, correos con dominio `.invalid`, teléfonos, códigos, coordenadas
`0,0`, capacidades y fechas usados son ficticios. Los hashes de contraseña no
se acompañan de contraseñas en texto plano. No se incorporó ningún dato real de
la Escuela Lo de Fuentes.

## Conclusión

El esquema físico, los seeds y el guion de validación son reproducibles en
MySQL 8.0.42. Las restricciones estructurales y las consultas representativas
del alcance de datos de la Fase 1 quedaron verificadas.
