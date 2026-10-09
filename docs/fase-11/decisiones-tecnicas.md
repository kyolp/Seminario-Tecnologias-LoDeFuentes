# DECISIONES TÉCNICAS — FASE 11
## SISTEMA WEB ESCUELA OFICIAL RURAL MIXTA LO DE FUENTES

**Módulo:** Comprobante de Inscripción en PDF (RF-13)  
**Autor:** Antigravity — Desarrollador Principal  
**Fecha:** 8 de octubre de 2026  
**Estado:** VIGENTE / APROBADO  

---

### DT-67: Arquitectura de Generación Local de Comprobantes PDF en Memoria sin Persistencia Temporal ni en Base de Datos

- **Contexto:**  
  El Documento Oficial de Alcance (*Sección 4, Tabla 2*) define el requerimiento funcional:  
  `RF-13 — Comprobante de inscripción: Generar documento oficial en PDF con la información de la inscripción confirmada.`  
  Se requiere que la emisión del comprobante sea 100% local, ligera, segura y no dependa de librerías ni servicios externos de internet (evitando dependencias externas, navegadores headless pesados o llamadas de API externas). Asimismo, no deben almacenarse archivos `.pdf` temporales ni permanentes en disco (`public/`, `storage/`, `sys_get_temp_dir()`), ni deben agregarse campos/tablas de comprobantes a la base de datos MySQL (`database/schema.sql`).

- **Decisión:**  
  1. Incorporar la biblioteca estándar open-source **FPDF 1.86** de forma estrictamente local dentro del directorio `app/Libraries/Fpdf/` (`fpdf.php`, fuentes base helvetica/courier/times/zapfdingbats/symbol y métricas php), respetando íntegramente su licencia permissiva y sin requerir dependencias Composer.
  2. Implementar `PdfService` (`app/Services/PdfService.php`) para la composición visual limpia, profesional y vectorizada del comprobante oficial:
     - Encabezado institucional con franja decorativa azul escolar (`#1A365D`), título institucional de la Escuela Oficial Rural Mixta Lo de Fuentes y fecha de emisión.
     - Código de confirmación enmarcado con tipografía monoespaciada (`Courier`), visualmente destacado.
     - Tablas estructuradas para Datos del Estudiante (nombres, apellidos, CUI, grado, sección y ciclo escolar), Datos del Encargado (nombres, apellidos, CUI, teléfono, parentesco) y Datos de Inscripción.
     - Sección de validación institucional con leyenda de autenticidad y espacio para firma / sello de la Dirección.
  3. Manejo riguroso de codificación de caracteres: Conversión de cadenas UTF-8 a Windows-1252 mediante `mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8')` para preservar tildes, eñes (`ñ`, `Ñ`) y símbolos especiales sin corrupción tipográfica ni caídas.
  4. Generación estrictamente al vuelo (on-the-fly) en memoria: `FPDF::Output('S')` genera la cadena binaria del PDF que es transmitida directamente en el cuerpo de la respuesta HTTP, sin guardar copias en disco ni en base de datos.
  5. **Manejo de estados e invariantes:**
     - La generación de comprobante solo se autoriza para inscripciones en estado `CONFIRMADA` con código de confirmación válido (`codigo_confirmacion IS NOT NULL`).
     - Intentos de acceder con estado `PENDIENTE`, `RECHAZADA` o `ANULADA` devuelven errores HTTP explícitos (`HTTP 409 Conflict` / `HTTP 400 Bad Request` / `HTTP 404 Not Found`).
  6. **Seguridad y Cabeceras HTTP:**
     - `Content-Type: application/pdf`
     - `Content-Disposition: attachment; filename="comprobante-inscripcion-{CODIGO}.pdf"`
     - `Content-Length: {BYTES}`
     - `X-Content-Type-Options: nosniff`
     - `Cache-Control: private, no-transform, no-store, must-revalidate`

- **Consecuencias:**  
  - Generación de comprobantes en milisegundos sin sobrecarga de disco ni fragmentación de almacenamiento.
  - Cero dependencias externas y 100% de independencia offline.
  - Cero modificaciones a `database/schema.sql`, manteniendo la arquitectura de la base de datos limpia e intacta.
  - Cumplimiento total de RF-13 y máxima seguridad en el manejo de datos personales.
