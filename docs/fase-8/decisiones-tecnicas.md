# DECISIONES TÉCNICAS — FASE 8

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 8 — Asistencia Docente y Geolocalización  
**Rol:** ANTIGRAVITY — DESARROLLADOR PRINCIPAL  
**Secuencia Histórica de DT:**  
- Fase 1: DT-01 a DT-08  
- Fase 2 inicial: DT-09 a DT-14  
- Corrección Fase 2: DT-15 a DT-18  
- Fase 3: DT-19 a DT-24  
- Fase 4: DT-25 a DT-30  
- Fase 5: DT-31 a DT-36  
- Fase 6: DT-37 a DT-42  
- Fase 7: DT-43 a DT-48  
- **Fase 8: DT-49 a DT-55**

---

### DT-49: Cálculo Autoritativo de Distancia Geodésica Exclusivamente en Backend
- **Contexto:** Las peticiones HTTP provenientes del navegador pueden ser interceptadas y manipuladas para inyectar flags de pertenencia a la geocerca (`dentro_radio = 1`) o distancias falsas (`distancia = 0.0`).
- **Decisión:** El controlador y servicio de asistencia aplican una política estricta de whitelist. Cualquier cálculo previo o bandera enviada por el cliente es deliberadamente descartada. El backend recupera las coordenadas centrales de la institución desde la base de datos y computa la distancia geodésica mediante su propio motor matemático.
- **Impacto:** Neutralización total de ataques de parameter tampering en validación de presencia física.

---

### DT-50: Adopción de la Fórmula Geodésica de Haversine con Radio Terrestre Medio ($R = 6,371,000$ m)
- **Contexto:** Se requiere un cálculo de distancia métrica exacto sobre la curvatura terrestre para radios de pequeña y mediana escala (50 a 500 metros).
- **Decisión:** Implementar la formulación estándar de Haversine con radio medio $R = 6,371,000$ metros y acotamiento de dominio ($a = \max(0.0, \min(1.0, a))$) para prevenir errores de punto flotante en cálculo de arco seno/arco tangente. La regla de frontera es estrictamente inclusiva ($\text{distancia} \le \text{radio}$).
- **Impacto:** Precisión métrica sub-centimétrica con cero discrepancias entre plataformas.

---

### DT-51: Marca Temporal Autoritativa de Servidor (`CURRENT_TIMESTAMP(6)`)
- **Contexto:** El registro de jornada laboral docente requiere certeza temporal jurídica inalterable frente a relojes locales desfasados o manipulados.
- **Decisión:** La marca temporal de fecha y hora se genera unívocamente a nivel del motor de base de datos MySQL mediante la función `CURRENT_TIMESTAMP(6)`. El sistema rechaza cualquier parámetro temporal enviado en la solicitud HTTP.
- **Impacto:** Certidumbre legal, auditoría forense inalterable y cumplimiento de RF-22 y RNF-06.

---

### DT-52: Inmutabilidad Absoluta de Marcajes de Asistencia y Restricción de Integridad Física
- **Contexto:** Los registros de asistencia laboral no deben ser susceptibles de alteración posterior ni de eliminación por parte de ningún rol del sistema.
- **Decisión:** `AsistenciaRepositoryInterface` no define métodos `update` ni `delete`. Además, la base de datos refuerza la integridad física mediante el constraint `CHECK (dentro_radio = 1)`, impidiendo almacenar registros inválidos.
- **Impacto:** Blindaje de auditoría permanente y trazabilidad técnica comprobada para RNF-06.

---

### DT-53: Unicidad Condicional de Geocerca Institucional Activa y Versionado Histórico Transaccional
- **Contexto:** La escuela debe contar con exactamente una geocerca activa en un momento dado, pero debe conservar el historial completo de cambios previos para auditoría de marcajes pasados.
- **Decisión:** Utilizar una columna virtual almacenada (`clave_configuracion_activa TINYINT GENERATED ALWAYS AS (CASE WHEN activa = 1 THEN 1 ELSE NULL END) STORED`) con índice `UNIQUE`. La activación de una nueva geocerca se realiza dentro de una transacción `START TRANSACTION ... COMMIT` que desactiva la previa estampando `fecha_fin = CURRENT_TIMESTAMP(6)`.
- **Impacto:** Cero colisiones de geocercas activas concurrentes y auditoría histórica completa sin pérdida de trazabilidad.

---

### DT-54: Resolución de Identidad Docente Estricta desde la Sesión Autenticada y Aislamiento Horizontal
- **Contexto:** Se debe evitar que un docente registre marcajes o consulte el historial de asistencia correspondiente a otro profesor.
- **Decisión:** La identidad del docente se resuelve de forma autoritativa mediante la sesión autenticada `auth_user_id` vinculada a `docentes.id_usuario`. En todas las consultas y escrituras, el `id_docente` proviene del modelo resuelto en servidor, garantizando aislamiento horizontal estricto.
- **Impacto:** Cumplimiento del principio de mínimo privilegio y neutralización de escalamiento horizontal de privilegios.

---

### DT-55: Gestión de Seguridad en API Geolocation del Navegador y Restricciones de Contexto Seguro
- **Contexto:** La API `navigator.geolocation` del consorcio W3C requiere condiciones de seguridad para ejecutarse y puede generar diversos códigos de error.
- **Decisión:** El frontend implementa control explícito para los códigos W3C: `PERMISSION_DENIED` (código 1), `POSITION_UNAVAILABLE` (código 2) y `TIMEOUT` (código 3). Se documenta formalmente la exigencia de contexto seguro (`HTTPS` o `localhost`) para entornos operativos de producción.
- **Impacto:** Experiencia de usuario clara ante rechazos o fallos de sensor GPS y cumplimiento de las directivas modernas de seguridad en navegadores.
