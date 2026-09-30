# Decisiones Técnicas — Fase 6: Inscripciones y Cupos

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 6 — Inscripciones y Cupos  
**Secuencia Correlativa:** DT-37 a DT-42  
**Fecha:** Septiembre 2026  

---

### DT-37: Transacción ACID y Bloqueo Pesimista con `SELECT ... FOR UPDATE` para Garantizar Cero Sobrecupos (RF-32, RN-DOC-02)
- **Contexto:** En los procesos de matrícula escolar en línea, múltiples padres de familia o tutores pueden intentar inscribir simultáneamente en la misma sección cuando resta un único cupo disponible. Sin una serialización estricta a nivel de almacenamiento, lecturas concurrentes sucias o no sincronizadas pueden evaluar `ocupacion < capacidad` al mismo milisegundo, permitiendo dos o más inserciones concurrentes y provocando un grave sobrecupo escolar (*overbooking*), violando la regla institucional `INSCRITOS <= CAPACIDAD` (RN-DOC-02 y RF-32).
- **Decisión:** Implementar serialización estricta de concurrencia mediante bloqueo pesimista a nivel de fila (`SELECT id_seccion, capacidad, habilitada FROM secciones WHERE id_seccion = ? FOR UPDATE`) en el motor de almacenamiento InnoDB, contenido dentro de una transacción ACID aislada (`BEGIN TRANSACTION ... COMMIT / ROLLBACK`).
- **Implementación Técnica:**
  - El método `InscripcionService::procesarInscripcionPublica()` inicia formalmente la transacción con `$this->db->beginTransaction()`.
  - Inmediatamente ejecuta `InscripcionRepository::lockSeccionForUpdate($idSeccion)`, adquiriendo un cerrojo exclusivo sobre la fila de la sección.
  - Toda transacción concurrente que pretenda evaluar cupos en la misma sección es encolada automáticamente por el gestor de bloqueos de MySQL InnoDB hasta que la primera transacción culmine su `COMMIT` o `ROLLBACK`.
  - Bajo el amparo del bloqueo exclusivo, el servicio evalúa si la sección continúa habilitada, si el ciclo escolar no está cerrado y cuenta el número actual de inscripciones vigentes (`COUNT(*) WHERE estado IN ('CONFIRMADA', 'PENDIENTE')`).
  - Si `ocupacion >= capacidad`, la transacción aborta limpiamente mediante `rollBack()` y responde con un mensaje controlado de cupo agotado.
  - Si hay disponibilidad, se persisten las entidades y se realiza el `commit()`.
- **Justificación e Impacto:** Garantiza matemáticamente la invariante física `INSCRITOS <= CAPACIDAD` con 0 sobrecupos bajo cualquier nivel de concurrencia de red o estrés de usuarios simultáneos, verificado exhaustivamente mediante la prueba multiproceso T6-21 con 10 iteraciones y 100% de éxito.

---

### DT-38: Generación Criptográfica y Unicidad del Código de Confirmación con Bucle Anticolisión (RF-12)
- **Contexto:** Al confirmarse una inscripción en línea, el sistema debe otorgar al encargado un código único de confirmación (RF-12) para fines de consulta posterior (RF-14). Si este código fuese secuencial (e.g. `INS-1`, `INS-2`), cualquier tercero podría adivinar identificadores ajenos y consultar datos de otros estudiantes (vulnerabilidad IDOR). Si fuese un hash muy largo (e.g. SHA-256 de 64 caracteres), resultaría impráctico de anotar o comunicar por teléfono para los padres de familia del área rural.
- **Decisión:** Diseñar un formato institucional seguro, mnemotécnico y de alta entropía con estructura `INS-YYYY-XXXXXXXX` (donde `YYYY` es el año en curso y `XXXXXXXX` son 8 caracteres hexadecimales generados con entropía criptográfica fuerte mediante `random_bytes`), respaldado por un bucle activo de reintento contra la restricción `uk_inscripciones_codigo`.
- **Implementación Técnica:**
  - `InscripcionService::generarCodigoConfirmacion()` genera candidatos usando `strtoupper(bin2hex(random_bytes(4)))`. Esto proporciona $2^{32} \approx 4.29 \times 10^9$ combinaciones posibles por año.
  - Antes de asignarlo, consulta `InscripcionRepository::existsCodigo($candidato)`.
  - Si existiese una improbable colisión previa, el algoritmo realiza hasta 10 reintentos automáticos generando un nuevo valor aleatorio. Si tras 10 intentos persiste colisión, el sistema escala automáticamente a 16 caracteres hexadecimales ($2^{64}$).
  - A nivel físico, la tabla `inscripciones` impone `CONSTRAINT uk_inscripciones_codigo UNIQUE (codigo_confirmacion)`. En caso de cualquier carrera atípica de inserción, el código captura la violación de unicidad 1062 y traduce la excepción a un mensaje institucional amigable.
- **Justificación e Impacto:** Satisface simultáneamente la ergonomía para los padres de familia y la imprevisibilidad criptográfica contra ataques de fuerza bruta o enumeración, garantizando códigos institucionales únicos e irrepetibles.

---

### DT-39: Desacoplamiento de Entidades Relacionales `Estudiante`, `Encargado` y Vínculo Compuesto N:M `estudiantes_encargados` (RF-05, RF-11)
- **Contexto:** En el contexto escolar guatemalteco, un estudiante puede tener registrados múltiples encargados (madre, padre, tutor legal), y a su vez un mismo encargado de familia puede tener varios hijos inscritos en diferentes grados o secciones de la escuela. Registrar al encargado como simples columnas planas de texto dentro de la tabla `inscripciones` o dentro de `estudiantes` provocaría redundancia, duplicidad y pérdida de normalización relacional (violando RF-05 y la 3NF).
- **Decisión:** Respetar rigurosamente el diseño relacional normalizado establecido en Fase 1, gestionando de forma atómica y coordinada:
  1. La entidad `estudiantes` (datos del alumno y código único estudiantil).
  2. La entidad `encargados` (datos del tutor/padre de familia y medios de contacto).
  3. La tabla intermedia asociativa `estudiantes_encargados` (relación N:M, parentesco e indicador de encargado principal).
  4. La entidad `inscripciones`, vinculada a la relación existente mediante la clave foránea compuesta `fk_inscripciones_vinculo_encargado`.
- **Implementación Técnica:**
  - El método `InscripcionService::procesarInscripcionPublica()` busca si el estudiante ya existe (por `codigo_estudiante` o coincidencia de nombres, apellidos y fecha de nacimiento). Si no existe, lo crea a través de `EstudianteRepository`.
  - Verifica si el encargado ya está registrado por coincidencia de nombres y apellidos. Si no existe, lo registra en `EncargadoRepository`.
  - Comprueba si existe el vínculo en `estudiantes_encargados` mediante `EncargadoRepository::hasVinculo()`. Si no existe, lo inserta formalmente con el parentesco proporcionado y `es_principal = 1`.
  - Únicamente tras confirmar la existencia del vínculo relacional, inserta la tupla en `inscripciones`.
  - Esto garantiza el estricto cumplimiento físico de la restricción `CONSTRAINT fk_inscripciones_vinculo_encargado FOREIGN KEY (id_estudiante, id_encargado) REFERENCES estudiantes_encargados (id_estudiante, id_encargado)`.
- **Justificación e Impacto:** Elimina la duplicidad de datos familiares, permite a futuro que un padre consulte o asocie a todos sus hijos en una sola cuenta y salvaguarda la integridad referencial compuesta en base de datos.

---

### DT-40: Protección de Privacidad en Consulta Pública y Ocultamiento de Metadatos Internos (RF-14, RNF-04)
- **Contexto:** La funcionalidad de consulta de estado por código de confirmación (RF-14) es de acceso público en Internet para facilitar a los padres de familia conocer el estado de su solicitud sin requerir usuario ni contraseña. Sin embargo, exponer en dicha respuesta pública identificadores internos de base de datos (`id_inscripcion`, `id_estudiante`, `id_encargado`, `id_seccion`), llaves foráneas o metadatos técnicos de infraestructura violaría los principios de mínima exposición y seguridad defensiva (RNF-03 y RNF-04).
- **Decisión:** Implementar un filtro estricto de proyección y sanitización de datos públicos en `InscripcionService::consultarInscripcionPorCodigo()`, retornando únicamente información legible, institucional y no sensible.
- **Implementación Técnica:**
  - La consulta requiere coincidencia exacta sobre el código alfanumérico saneado.
  - La carga del registro se hace mediante un JOIN controlado con `estudiantes`, `encargados`, `secciones`, `grados` y `periodos_escolares`.
  - El servicio construye un arreglo asociativo explícito que descarta cualquier clave primaria interna (`id_*`), hashes o columnas de auditoría:
    ```php
    return [
        'success' => true,
        'data' => [
            'codigo_confirmacion' => $inscripcion->codigo_confirmacion,
            'estudiante_nombre'   => $inscripcion->getEstudianteNombreCompleto(),
            'codigo_estudiante'   => $inscripcion->codigo_estudiante,
            'grado'               => $inscripcion->grado_nombre,
            'seccion'             => $inscripcion->seccion_codigo,
            'periodo'             => $inscripcion->periodo_nombre,
            'encargado_nombre'    => $inscripcion->getEncargadoNombreCompleto(),
            'parentesco'          => $inscripcion->parentesco,
            'fecha_inscripcion'   => substr($inscripcion->fecha_inscripcion, 0, 10),
            'fecha_confirmacion'  => $inscripcion->fecha_confirmacion ? substr($inscripcion->fecha_confirmacion, 0, 10) : null,
            'estado'              => $inscripcion->estado,
        ],
    ];
    ```
  - La vista `views/inscripciones/consultar.php` escapa todas las variables mediante `e()` antes de renderizarlas en el DOM.
- **Justificación e Impacto:** Cumple plenamente con la normativa de privacidad y protección de datos estudiantiles, impidiendo que terceros deduzcan volúmenes de registros internos o ejecuten minería de metadatos mediante herramientas automatizadas.

---

### DT-41: Arquitectura Pública de Inscripciones: Desacoplamiento Controller-Service-Repository y Mitigación de Vectores Web
- **Contexto:** El módulo de inscripción pública es la primera superficie expuesta a usuarios no autenticados en el sistema escolar. Esto lo convierte en el blanco principal de potenciales ataques: peticiones forjadas (*Cross-Site Request Forgery* - CSRF), inyección de scripts (*Cross-Site Scripting* - XSS), inyección masiva de parámetros (*Mass Assignment*) y envíos accidentales múltiples por doble clic del usuario (*Double-Submit*).
- **Decisión:** Establecer defensas en profundidad en todas las capas del módulo:
  1. Enrutamiento público con verificación CSRF universal (`VerifyCsrfToken`).
  2. Filtrado estricto por lista blanca (*whitelisting*) en el controlador `InscripcionPublicaController`.
  3. Prevención de doble submit mediante la restricción única física de base de datos `uk_inscripciones_estudiante_seccion_vigente`.
  4. Redirección Post/Redirect/Get (PRG) tras el registro exitoso.
  5. Escapado contextual exhaustivo en todas las vistas públicas.
- **Implementación Técnica:**
  - El formulario incluye el token CSRF `<?= \App\Core\Csrf::field() ?>`. Cualquier POST sin token o con token alterado es abortado de inmediato con `HTTP 403 Forbidden`.
  - El controlador extrae únicamente los 10 campos requeridos del formulario; parámetros espurios (`id_rol`, `estado`, `codigo_confirmacion`, `id_usuario`, etc.) son completamente ignorados.
  - Si un usuario presiona repetidamente el botón de envío o dos peticiones idénticas llegan en paralelo, la columna generada almacenada `estudiante_vigente` y `seccion_vigente` junto con `uk_inscripciones_estudiante_seccion_vigente` rechazan el segundo intento garantizando que solo una tupla quede registrada.
  - Al completar el registro, el controlador redirige con `HTTP 302` hacia `/inscripcion/exito?codigo=...`, evitando reenvíos de formulario al recargar la página.
- **Justificación e Impacto:** Otorga resiliencia y robustez de grado de producción a la interfaz web pública, aislando los datos escolares de intentos de manipulación o saturación accidental.

---

### DT-42: Delimitación Estricta de Fase 6 y Protección de Límites Funcionales hacia Fases Posteriores
- **Contexto:** Las inscripciones escolares conectan directamente con módulos posteriores: el listado de alumnos asignados por docente (Fase 7: RF-17), el control de asistencias (Fase 8: RF-20 a RF-25), la generación de comprobantes oficiales en PDF (Fase 11: RF-13) y los reportes directivos de matrícula (Fase 12: RF-30). Incorporar prematuramente estas funcionalidades en Fase 6 violaría la disciplina metodológica de desarrollo fase por fase del proyecto.
- **Decisión:** Mantener una delimitación quirúrgica del alcance de Fase 6:
  - RF-13 ("Generación de comprobante en formato PDF descargable") permanece en estado `PENDIENTE` para su implementación centralizada en la Fase 11 mediante el motor de renderizado de documentos que se autorice en dicha fase. En Fase 6 se provee la pantalla de confirmación institucional HTML imprimible con el código oficial (RF-12).
  - RF-17 ("Listado de estudiantes asignados por docente titular") permanece `PENDIENTE` para el Portal Docente en Fase 7.
  - RF-20 a RF-25 ("Asistencia diaria y geocerca escolar") permanecen `PENDIENTE` para la Fase 8.
  - RF-30 ("Reporte administrativo consolidado de matrícula") permanece `PENDIENTE` para la Fase 12.
- **Implementación Técnica:**
  - No se añadieron librerías de generación de PDF pesadas de forma improvisada ni rutas de descarga de archivos.
  - No se expuso el acceso de docentes ni administradores a la edición de expedientes estudiantiles más allá de los modelos y repositorios autorizados.
  - Todas las estructuras de base de datos (`inscripciones`, `estudiantes`, `encargados`, `estudiantes_encargados`) quedan completamente pobladas, normalizadas y consistentes para ser consumidas fluidamente por las fases 7 a 13.
- **Justificación e Impacto:** Garantiza un avance ordenado y sin dispersión funcional, manteniendo la base de código limpia y lista para la revisión externa y autorización controlada del usuario.
