# DECISIONES TÉCNICAS — FASE 11
## SISTEMA WEB ESCUELA OFICIAL RURAL MIXTA LO DE FUENTES

**Módulo:** Comprobante de Inscripción en PDF (RF-13)  
**Autor:** Antigravity — Desarrollador Principal  
**Fecha:** 8 de octubre de 2026  
**Estado:** VIGENTE / APROBADO  

---

### DT-67: Arquitectura de Generación Local de Comprobantes PDF en Memoria sin Persistencia Temporal ni en Base de Datos y Alcance Estricto de Privacidad

- **Contexto:**  
  El Documento Oficial de Alcance (*Sección 4, Tabla 2 y Sección 9.5*) define textualmente:  
  `RF-13 — Comprobante PDF: El sistema deberá generar un comprobante de inscripción en PDF.`  
  Se requiere que la emisión del comprobante sea 100% local, ligera, segura y no dependa de librerías ni servicios externos de internet. Asimismo, no deben almacenarse archivos `.pdf` temporales ni permanentes en disco (`public/`, `storage/`, `sys_get_temp_dir()`), ni deben agregarse campos o tablas de comprobantes a la base de datos MySQL (`database/schema.sql`).
  
  En concordancia con el principio de **Mínima Exposición** y dado que el comprobante se obtiene a través del código público de confirmación (RF-12), el documento debe limitarse estrictamente a la información respaldada por el requerimiento RF-13 y el flujo real de inscripción, omitiendo elementos no contemplados en el alcance oficial (como líneas de firma de la Dirección, espacios para sello, declaraciones de autenticidad, códigos QR o de barras) y excluyendo datos sensibles no exigidos (CUI del estudiante, CUI del encargado, teléfono o correo del encargado).

- **Decisión:**  
  1. Incorporar la biblioteca estándar open-source **FPDF 1.86** de forma estrictamente local dentro del directorio `app/Libraries/Fpdf/` (`fpdf.php`, fuentes base helvetica/courier/times/zapfdingbats/symbol y métricas php), respetando su licencia permisiva sin requerir dependencias Composer.
  2. Implementar `PdfService` (`app/Services/PdfService.php`) para la composición visual limpia y minimalista del comprobante oficial:
     - Encabezado institucional con franja decorativa azul escolar (`#1A365D`), título institucional de la Escuela Oficial Rural Mixta Lo de Fuentes y fecha de emisión del documento.
     - Código único de confirmación enmarcado con tipografía monoespaciada (`Courier`), visualmente destacado.
     - Datos del Estudiante: Nombres y Apellidos, Grado asignado, Sección asignada y Ciclo escolar.
     - Datos del Encargado: Nombres y Apellidos, y Parentesco (PADRE, MADRE, TUTOR LEGAL).
     - Estado de la inscripción (`CONFIRMADA`) y fechas correspondientes de inscripción y confirmación.
     - **Cero contenido no exigido:** No se incluyen líneas de firma de Dirección, recuadros para sello, declaraciones de autenticidad, ni códigos QR/barras.
     - **Cero exposición excesiva de datos:** No se incluyen CUI del menor, CUI del encargado, teléfono, correo ni claves primarias internas.
  3. Manejo de codificación de caracteres: Conversión de cadenas UTF-8 a Windows-1252 mediante `mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8')` para preservar tildes y eñes (`ñ`, `Ñ`) sin corrupción tipográfica.
  4. Generación al vuelo (on-the-fly) en memoria: `FPDF::Output('S')` genera la cadena binaria del PDF que es transmitida directamente en la respuesta HTTP, sin guardar copias en disco ni en base de datos.
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
  - Cumplimiento total de RF-13 sin adición de elementos fuera de alcance.
  - Aplicación de mínima exposición de datos sensibles. RNF-04 se registra como **EVIDENCIA PARCIAL** (validación integral reservada para Fase 14).
  - Cero dependencias externas y 100% de independencia offline.
  - Cero modificaciones a `database/schema.sql`, manteniendo la arquitectura de base de datos intacta.
