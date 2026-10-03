# SEGURIDAD Y VALIDACIÓN DE ARCHIVOS — FASE 9

**Proyecto:** Sistema Web para la Escuela Oficial Rural Mixta Lo de Fuentes  
**Fase:** Fase 9 — Gestión de Permisos Docentes  
**Rol:** ANTIGRAVITY — DESARROLLADOR PRINCIPAL  

---

## 1. Amenazas Mitigadas y Principios de Seguridad

La recepción de archivos subidos por usuarios representa uno de los vectores de ataque más críticos en aplicaciones web (inyección de código remoto RCE, ejecución de scripts PHP, path traversal, ataques de denegación de servicio por agotamiento de disco y filtración de datos).

Para neutralizar estas amenazas, el sistema aplica una estrategia de defensa en profundidad (**Defense in Depth**):

```mermaid
flowchart TD
    Upload[Archivo Recibido en Request] --> ValExt{1. Extensión en Whitelist?\n(pdf, png, jpg, jpeg, webp)}
    ValExt -->|No| RechazoExt[Rechazo HTTP 422\n'Extensión no permitida']
    ValExt -->|Sí| ValSize{2. Tamaño <= 5MB?\n(<= 5,242,880 bytes)}
    ValSize -->|No| RechazoSize[Rechazo HTTP 422\n'El archivo excede 5MB']
    ValSize -->|Sí| ValMime{3. Magic Bytes (finfo)?\n(MIME Whitelist)}
    ValMime -->|No| RechazoMime[Rechazo HTTP 422\n'Tipo de archivo no permitido']
    ValMime -->|Sí| ValContent{4. Detección de Scripts?\n(Sin tags <?php, eval, etc.)}
    ValContent -->|Detectado| RechazoScript[Rechazo HTTP 422\n'Contenido no seguro']
    ValContent -->|Limpio| StoreDisk[5. Guardar en storage/permisos/\nNombre Hasheado Aleatorio]
    StoreDisk --> ProtectDir[6. Protección Perimetral\nDeny from all en storage/]
```

---

## 2. Doble Capa de Validación: Extensión y Magic Bytes (DT-60)

No se confía en la cabecera `Content-Type` ni en la extensión enviada por el navegador cliente, ya que ambas son fácilmente falsificables mediante cURL, proxies o manipulación de formularios.

### 2.1 Whitelist Estricta de Extensiones:
```php
private const EXTENSIONES_PERMITIDAS = ['pdf', 'png', 'jpg', 'jpeg', 'webp'];
```

### 2.2 Inspección de Magic Bytes mediante `finfo`:
El sistema examina los primeros bytes del archivo en el sistema operativo mediante la extensión nativa `fileinfo`:
```php
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeReal = finfo_file($finfo, $tempFile);
finfo_close($finfo);

private const MIMES_PERMITIDOS = [
    'application/pdf',
    'image/png',
    'image/jpeg',
    'image/webp',
];
```

### 2.3 Rechazo de Scripts Camuflados (Polyglot / Embedded PHP):
Para evitar archivos políglotas (imágenes válidas que contienen código PHP malicioso incrustado en comentarios o metadatos EXIF), el servicio realiza un escaneo de seguridad en el flujo de bytes buscando firmas como `<?php`, `<?=`, `<script`, o `eval(`:
```php
$contenidoInicio = file_get_contents($tempFile, false, null, 0, 4096);
if (preg_match('/<\?(php|=)/i', $contenidoInicio)) {
    throw new ValidationException(['documento' => 'El archivo contiene secuencias ejecutables no permitidas.']);
}
```

---

## 3. Almacenamiento Aislado y Blindaje Perimetral (DT-58)

1. **Ubicación Externa:** `storage/permisos/` reside fuera del document root (`public/`). Ningún servidor web (Apache, Nginx, PHP CLI) puede servir estos archivos directamente mediante URL.
2. **Directiva `.htaccess` Restrictiva:**
   ```apache
   # storage/permisos/.htaccess
   Require all denied
   ```
3. **Ofuscación Criptográfica de Nombres:**
   El nombre de archivo en disco se genera con entropía criptográfica unívoca:
   `bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension`
   Esto impide predecir rutas, enumerar archivos o sobrescribir documentos de otros usuarios.
4. **Saneamiento contra Directory Traversal:**
   Se aplican `basename()` y `realpath()` garantizando que la ruta resuelta permanezca estrictamente confinada dentro de `storage/permisos/`.

---

## 4. Streaming Seguro y Control de Descargas (DT-59)

El acceso a los documentos se efectúa únicamente bajo control programático:
- **Autenticación y Autorización:** Se comprueba el rol de sesión y la titularidad del registro (`id_docente`).
- **Encabezado `X-Content-Type-Options: nosniff`:** Obliga al navegador a respetar estrictamente el tipo MIME declarado, evitando que intente interpretar el archivo como HTML o JavaScript (MIME Sniffing).
- **Encabezado `Content-Disposition`:** Fuerza la descarga o visualización inline sin riesgo de ejecución en el contexto del dominio.
