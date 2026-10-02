# SEGURIDAD EN GEOLOCALIZACIÓN Y PREVENCIÓN DE VECTORES DE ATAQUE — FASE 8

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 8 — Asistencia Docente y Geolocalización  
**Rol:** ANTIGRAVITY — DESARROLLADOR PRINCIPAL  
**Requisitos cubiertos:** RF-18 a RF-23, RNF-06  

---

## 1. Vectores de Ataque Analizados y Mitigaciones Implementadas

### 1.1 Ataque de Suplantación de Parámetros de Distancia (*Parameter Tampering*)
- **Vector:** Un usuario malicioso intercepta la petición HTTP y añade o modifica campos como `dentro_radio = 1` y `distancia_metros = 0.0`, enviando a su vez coordenadas fuera de la escuela.
- **Mitigación (DT-49):** `DocenteAsistenciaController` aplica una **whitelist estricta** que solo extrae `latitud`, `longitud` y `precision`. Cualquier otro campo es ignorado. La distancia es calculada forzosamente en el servidor comparando contra la configuración activa en base de datos. Verificado en la prueba automatizada **`T8-30`**.

### 1.2 Ataque de Suplantación de Identidad (*Horizontal Privilege Escalation*)
- **Vector:** Un docente intenta marcar asistencia o consultar el historial en nombre de otro docente inyectando `id_docente` o `id_usuario` en la petición.
- **Mitigación (DT-54):** El identificador del docente se deriva de forma autoritativa exclusivamente de la sesión autenticada (`$_SESSION['auth_user_id']`). Cualquier parámetro externo con nombres de identificadores es completamente ignorado. Verificado en las pruebas **`T8-24`** y **`T8-29`** (escenario 9).

### 1.3 Ataque de Manipulación Temporal (*Timestamp Spoofing*)
- **Vector:** Un usuario altera la hora de su dispositivo o envía un campo `fecha_hora` falso (ej. año 1999) para fingir puntualidad.
- **Mitigación (DT-51):** El backend no acepta ni lee ninguna fecha/hora del cliente. La persistencia asigna `CURRENT_TIMESTAMP(6)` directamente en el motor relacional. Verificado en la prueba **`T8-20`**.

### 1.4 Ataque de Modificación Posterior de Auditoría (*Record Alteration*)
- **Vector:** Intentos de alterar marcajes históricos (distancias, horas o tipos) para encubrir faltas.
- **Mitigación (DT-52):** El repositorio carece de métodos `update` o `delete`. Los marcajes son inmutables por diseño arquitectónico y restricción física `CHECK (dentro_radio = 1)`. Verificado en las pruebas **`T8-26`** y **`T8-27`**.

### 1.5 Cross-Site Scripting (XSS) en Vistas de Historial
- **Vector:** Inyección de secuencias maliciosas en coordenadas, tipos de marcaje o descripciones de configuración.
- **Mitigación:** Todas las salidas HTML en las vistas de asistencia docente y administración están estrictamente saneadas con la función de escape contextual `e(...)` (`htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`). Verificado en la prueba **`T8-28`**.

### 1.6 Cross-Site Request Forgery (CSRF) y Pruebas Negativas Reales (F8-01, T8-38)
- **Vector:** Inducción a marcar entrada o salida sin conocimiento del usuario docente mediante peticiones forjadas desde otros sitios o sin credenciales de formulario legítimas.
- **Mitigación:** Los endpoints `POST /docente/asistencia/entrada` y `POST /docente/asistencia/salida` están protegidos mediante el middleware `VerifyCsrfToken`, exigiendo token criptográfico de sesión sincronizado.
- **Verificación Negativa Real en Servidor Web HTTP (T8-38):**
  - **Caso A:** `POST /docente/asistencia/entrada` sin token CSRF $\to$ HTTP 403 Forbidden. Cero registros persistidos en base de datos.
  - **Caso B:** `POST /docente/asistencia/entrada` con token manipulado (`token_manipulado_invalido_...`) $\to$ HTTP 403 Forbidden. Cero registros persistidos en base de datos.
  - **Caso C:** `POST /docente/asistencia/salida` con token falso $\to$ HTTP 403 Forbidden. Cero registros persistidos en base de datos.
  - **Caso D:** `POST /docente/asistencia/entrada` con token legítimo emitido en la sesión del formulario $\to$ HTTP 200 OK. Confirmación de exactamente 1 registro persistido en la base de datos.

### 1.7 Manejo de Errores de Geolocation API en Cliente y Servidor (F8-02, T8-39)
- **Vector:** Fallos de obtención de geolocalización en el navegador o envío deliberado de solicitudes con coordenadas vacías para forzar comportamientos por defecto.
- **Mitigación en Frontend (W3C Geolocation API):**
  - Manejo exhaustivo de códigos de error de la API:
    - Código 1 (`PERMISSION_DENIED`): Mensaje amigable advirtiendo que el usuario bloqueó el permiso de ubicación; el formulario no se envía y los botones se re-habilitan.
    - Código 2 (`POSITION_UNAVAILABLE`): Mensaje indicando que la señal de GPS/red no está disponible; el formulario no se envía.
    - Código 3 (`TIMEOUT`): Mensaje de tiempo de espera agotado al consultar la posición satelital; el formulario no se envía.
    - Navegadores sin soporte de `navigator.geolocation`: Detección preventiva que bloquea el envío y muestra instrucción de compatibilidad.
  - Verificado en el arnés de ejecución JavaScript automatizado (`tests/harness_geolocation.js`).
- **Mitigación en Backend:** El servidor rechaza inmediatamente con HTTP 422 Unprocessable Content cualquier solicitud sin coordenadas o con campos vacíos. **Nunca se aplican valores por defecto ficticios ni coordenadas simuladas**. Verificado en la prueba **`T8-39`**.

### 1.8 Validación Estricta de Coordenadas Recibidas (F8-03, T8-40)
- **Vector:** Inyección de coordenadas geográficas fuera de rango, no numéricas, cadenas vacías, valores nulos o infinitos para provocar fallos de punto flotante o errores de base de datos no controlados.
- **Mitigación:** `AsistenciaService::validarCoordenadasCliente` aplica validaciones de tipo estricto, rango decimal y funciones matemáticas de punto flotante:
  - Rango de latitud: $[-90.0, 90.0]$ y `!is_nan()` y `!is_infinite()`.
  - Rango de longitud: $[-180.0, 180.0]$ y `!is_nan()` y `!is_infinite()`.
  - Descarte de cadenas no numéricas, nulos o valores ausentes arrojando `InvalidArgumentException`.
- **Verificación Automatizada (T8-40):** 13 combinaciones anómalas (lat > 90, lat < -90, lon > 180, lon < -180, strings alfanuméricos, vacíos, nulos, INF, NAN, campos omitidos) son capturadas por el controlador retornando HTTP 422 Unprocessable Content con mensaje controlado. En todos los casos se comprueba:
  1. `exito: false`.
  2. Cero registros persistidos en `marcajes_asistencia`.
  3. Cero fugas de información interna, trazas (`Stack trace`), excepciones de PDO (`PDOException`) o códigos de error SQL (`SQLSTATE`).

---

## 2. Limitaciones de Seguridad Inherentes al Navegador (DT-55)

> [!NOTE]
> En la arquitectura web estándar, la API W3C `navigator.geolocation` depende de los servicios de ubicación del sistema operativo del dispositivo cliente (GPS, antenas celulares, puntos de acceso Wi-Fi).
>
> **Consideraciones de Seguridad:**
> 1. **Contexto Seguro Requerido:** En entornos de producción, los navegadores modernos restringen el acceso a `navigator.geolocation` exclusivamente a conexiones seguras (`HTTPS` o `localhost`).
> 2. **GPS Spoofing a Nivel de OS:** Herramientas de depuración del desarrollador o aplicaciones de "Fake GPS" en dispositivos móviles con permisos de desarrollador habilitados pueden simular coordenadas falsas a nivel del sistema operativo. La aplicación web mitiga esto calculando la distancia geodésica autoritativa en el servidor e implementando validaciones de consistencia temporal y secuencias lógicas de marcaje.
