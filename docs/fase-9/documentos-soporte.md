# GESTIÓN DE DOCUMENTOS DE SOPORTE — FASE 9

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 9 — Gestión de Permisos Docentes  
**Rol:** ANTIGRAVITY — DESARROLLADOR PRINCIPAL  

---

## 1. Alcance y Requisitos (RF-25)

El requisito funcional **RF-25** estipula la capacidad opcional del docente de adjuntar constancias o documentos justificativos (médicos, personales, oficiales) al momento de registrar su solicitud de permiso.

Para garantizar la seguridad, disponibilidad e integridad física del servidor escolar, el sistema implementa una estricta política de validación en dos capas (cliente y backend) y almacenamiento aislado.

---

## 2. Especificación Técnica de Archivos Permitidos

| Criterio | Especificación | Justificación Técnica |
| :--- | :--- | :--- |
| **Extensiones Permitidas** | `.pdf`, `.png`, `.jpg`, `.jpeg`, `.webp` | Formatos estándar para constancias documentales e imágenes escaneadas. |
| **Tipos MIME Permitidos** | `application/pdf`, `image/png`, `image/jpeg`, `image/webp` | Whitelist validada por magic bytes binarios (`finfo_open(FILEINFO_MIME_TYPE)`). |
| **Tamaño Máximo** | **5.0 MB** ($5,242,880$ bytes) | Balance entre calidad de escaneo y protección contra saturación de almacenamiento. |
| **Carácter del Documento** | Opcional en el registro | Si el docente no adjunta comprobante, la solicitud se procesa normalmente (`RF-24`). |

---

## 3. Arquitectura de Almacenamiento Privado (DT-58)

Los archivos subidos **nunca** se almacenan dentro del directorio público (`public/`) ni son accesibles directamente por URL del navegador.

```
storage/
└── permisos/
    ├── .gitkeep
    ├── .htaccess (Deny from all)
    └── 8f1b2c3d4e5f...pdf (Nombre unívoco hasheado sin extensión ejecutable)
```

### Proceso de Ingesta y Almacenamiento:
1. **Recepción Multipart:** El framework normaliza la carga en `$request->file('documento')`.
2. **Sanitización de Nombre Original:** Se extrae el nombre original con `basename()` y `strip_tags()` para fines visuales en reportes y consultas.
3. **Generación de Nombre en Disco:** Se genera un identificador criptográfico único:
   ```php
   $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
   $safeFilename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;
   $targetPath = $storageDir . DIRECTORY_SEPARATOR . $safeFilename;
   ```
4. **Almacenamiento Desacoplado:** El binario se guarda en disco y en la tabla `documentos_permiso` se registran únicamente sus metadatos:
   - `nombre_original`: Nombre para visualización y descarga.
   - `ruta_almacenamiento`: Ruta física interna en el servidor.
   - `tipo_mime`: Tipo MIME verificado por inspección profunda.
   - `tamano_bytes`: Peso exacto en bytes para auditoría.

---

## 4. Streaming y Descarga Segura (DT-59)

Toda descarga o visualización de documentos se realiza exclusivamente a través de los controladores del backend (`DocentePermisoController::descargarDocumento` o `AdminPermisoController::descargarDocumento`), implementando:

1. **Control de Autorización Previo:**
   - Si el actor es `DOCENTE`, el sistema valida que el permiso pertenezca a su propio `id_docente` (aislamiento horizontal).
   - Si el actor es `ADMINISTRACION`, se permite el acceso a cualquier comprobante registrado.
2. **Encabezados HTTP de Seguridad Estricta:**
   ```http
   Content-Type: application/pdf (o MIME correspondiente)
   Content-Disposition: attachment; filename="constancia_medica.pdf"
   Content-Length: 1048576
   X-Content-Type-Options: nosniff
   Cache-Control: private, no-store, must-revalidate
   Pragma: no-cache
   ```
3. **Prevención de Path Traversal:**
   La ruta física se valida con `realpath()` asegurando que resida estrictamente dentro de `storage/permisos/`.
