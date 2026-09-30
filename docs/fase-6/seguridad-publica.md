# Seguridad Defensiva en Módulos Públicos — Fase 6

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 6 — Inscripciones y Cupos  
**Fecha:** Septiembre 2026  

---

## 1. Análisis de Superficie de Ataque en Portal Público

El módulo de inscripciones escolares y consulta de estado es la primera funcionalidad del sistema expuesta abiertamente a Internet sin autenticación previa. Esta apertura requiere una arquitectura defensiva multicapa para mitigar los principales vectores de ataque del *OWASP Top 10*.

---

## 2. Vectores Mitigados y Controles Implementados

### 2.1 Protección contra Cross-Site Request Forgery (CSRF)
- **Amenaza:** Un sitio web malicioso podría forzar al navegador de una víctima a enviar solicitudes POST no deseadas para saturar cupos escolares o registrar datos espurios.
- **Defensa:**
  - Todas las rutas `POST` públicas (`/inscripcion`, `/inscripcion/consultar`) están protegidas por el middleware `VerifyCsrfToken`.
  - Los formularios incluyen el campo oculto `<?= \App\Core\Csrf::field() ?>`.
  - Peticiones sin token o con token alterado son bloqueadas de inmediato con `HTTP 403 Forbidden` antes de llegar a la lógica de negocio (validado en T6-16).

---

### 2.2 Prevención de Cross-Site Scripting (XSS)
- **Amenaza:** Inyección de cadenas con etiquetas JavaScript maliciosas (`<script>alert(...)`, `<img onerror=...>`) en los campos de nombres, apellidos, teléfono o notas para ejecutar código en los navegadores de los operadores escolares o del propio padre de familia.
- **Defensa:**
  - Todas las salidas en las vistas públicas (`nueva.php`, `exito.php`, `consultar.php`) utilizan la función de escape contextual `e($valor)`.
  - La función convierte caracteres especiales en sus entidades HTML correspondientes (`&lt;`, `&gt;`, `&quot;`, `&#039;`, `&amp;`) mediante `htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
  - Validado en T6-17: se inyectaron scripts en nombres del estudiante y del encargado, verificando que el HTML renderizado neutraliza la ejecución y no contiene etiquetas crudas.

---

### 2.3 Mitigación de Asignación Masiva (*Mass Assignment*)
- **Amenaza:** Un usuario atacante puede enviar parámetros HTTP adicionales no contemplados en el formulario original (e.g. `estado=CONFIRMADA`, `id_rol=1`, `codigo_confirmacion=MI-CODIGO`, `is_admin=1`, `estudiante_vigente=999`) con la intención de sobreescribir atributos de control.
- **Defensa:**
  - El controlador `InscripcionPublicaController::store()` aplica una lista blanca estricta (*whitelisting*):
    ```php
    $allowedKeys = [
        'estudiante_nombres',
        'estudiante_apellidos',
        'estudiante_fecha_nacimiento',
        'codigo_estudiante',
        'encargado_nombres',
        'encargado_apellidos',
        'encargado_telefono',
        'encargado_correo',
        'parentesco',
        'id_seccion',
    ];
    ```
  - Todo parámetro no incluido en esta lista es ignorado y descartado de forma transparente.
  - El código de confirmación, la fecha de confirmación y el estado son generados exclusivamente por la lógica interna del backend, impidiendo manipulación externa (validado en T6-18).

---

### 2.4 Control de Doble Envío (*Double-Submit*)
- **Amenaza:** Usuarios con conexiones móviles lentas o inestables pueden presionar el botón de envío varias veces consecutivas, o la red puede duplicar paquetes, intentando crear múltiples registros idénticos.
- **Defensa:**
  - A nivel de base de datos, las columnas generadas almacenadas `estudiante_vigente` y `seccion_vigente` imponen la restricción `CONSTRAINT uk_inscripciones_estudiante_seccion_vigente UNIQUE (estudiante_vigente, seccion_vigente)`.
  - El primer envío procesa y confirma la inscripción; el segundo envío es capturado por la restricción única y abortado limpiamente sin crear registros duplicados (validado en T6-22).
  - Adicionalmente, el controlador implementa el patrón **Post/Redirect/Get (PRG)**: una vez confirmada la inscripción, redirige con `HTTP 302` a `/inscripcion/exito?codigo=...`, eliminando el reenvío involuntario al recargar la página.

---

### 2.5 Privacidad y Protección de Datos en Consulta Pública (RNF-04)
- **Amenaza:** Exposición de identificadores correlativos de base de datos (`id_inscripcion`, `id_estudiante`), lo que permitiría a un atacante inferir cuántos alumnos se han registrado o intentar ataques de referencia directa a objetos inseguros (IDOR).
- **Defensa:**
  - El método `InscripcionService::consultarInscripcionPorCodigo()` filtra la respuesta devolviendo únicamente datos públicos y humanamente comprensibles (nombre, grado, sección, ciclo escolar, parentesco, fecha y estado).
  - Se eliminan explícitamente todas las claves primarias (`id_*`), llaves foráneas y contraseñas.
  - La consulta exige coincidencia exacta contra el código alfanumérico no secuencial `INS-YYYY-XXXXXXXX` generado criptográficamente, haciendo inviable la enumeración o el rastreo no autorizado (validado en T6-08, T6-09 y T6-24).

---

### 2.6 Prevención de Inyección SQL (SQLi)
- **Amenaza:** Manipulación de los parámetros de búsqueda o inserción para alterar la sintaxis de las consultas de base de datos.
- **Defensa:**
  - El 100% de las consultas en `InscripcionRepository` y `EncargadoRepository` utilizan sentencias preparadas de PDO con enlace estricto de parámetros tipados (`:param` o `?`).
  - Cero concatenaciones de variables de entrada en consultas SQL.
