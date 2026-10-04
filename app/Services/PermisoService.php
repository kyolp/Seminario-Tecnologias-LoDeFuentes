<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\DocumentoPermiso;
use App\Models\HistorialSolicitudPermiso;
use App\Models\SolicitudPermiso;
use App\Repositories\DocenteRepository;
use App\Repositories\DocenteRepositoryInterface;
use App\Repositories\PermisoRepository;
use App\Repositories\PermisoRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\UserRepositoryInterface;
use DomainException;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Servicio de negocio para la Gestión de Permisos Docentes.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 9 — RF-24, RF-25, RF-27, RF-28, RNF-06).
 *
 * Implementa las decisiones técnicas:
 * - DT-56: Historial inmutable y estados canónicos (PENDIENTE, APROBADO, RECHAZADO).
 * - DT-57: Transición de estado transaccional atómica.
 * - DT-58: Almacenamiento protegido fuera del webroot (storage/permisos/).
 * - DT-59: Descarga autorizada y headers seguros.
 * - DT-60: Validación exhaustiva de tipo MIME real en servidor con finfo y whitelist.
 * - DT-61: Identidad docente resuelta desde sesión y aislamiento horizontal estricto.
 * - DT-62: Bloqueo pesimista para decisión concurrente (SELECT ... FOR UPDATE).
 */
class PermisoService
{
    public const MAX_BYTES_ARCHIVO = 5242880; // 5 MB (límite técnico de implementación)
    public const CARPETA_ALMACENAMIENTO = 'storage/permisos';

    public const EXTENSIONES_PERMITIDAS = ['pdf', 'png', 'jpg', 'jpeg', 'webp'];
    public const MIMES_PERMITIDOS = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
    ];

    private PDO $db;
    private PermisoRepositoryInterface $permisoRepo;
    private DocenteRepositoryInterface $docenteRepo;
    private UserRepositoryInterface $userRepo;
    private string $storageBasePath;

    public function __construct(
        ?PermisoRepositoryInterface $permisoRepo = null,
        ?DocenteRepositoryInterface $docenteRepo = null,
        ?UserRepositoryInterface $userRepo = null,
        ?PDO $db = null,
        ?string $storageBasePath = null
    ) {
        $this->db = $db ?? Database::getConnection();
        $this->permisoRepo = $permisoRepo ?? new PermisoRepository($this->db);
        $this->docenteRepo = $docenteRepo ?? new DocenteRepository($this->db);
        $this->userRepo = $userRepo ?? new UserRepository($this->db);
        $this->storageBasePath = $storageBasePath ?? dirname(__DIR__, 2);
    }

    /**
     * Registrar una solicitud de permiso docente con adjunto de soporte opcional (RF-24, RF-25).
     *
     * @param int $idDocente Resuelto unívocamente desde la sesión del usuario.
     * @param int $idUsuario ID de usuario actor de la sesión.
     * @param array<string, mixed> $datos Campos del formulario (fecha_solicitada, tipo_motivo, descripcion).
     * @param array<string, mixed>|null $archivoSubido Array de subida ($_FILES['soporte']).
     */
    public function crearSolicitud(
        int $idDocente,
        int $idUsuario,
        array $datos,
        ?array $archivoSubido = null
    ): SolicitudPermiso {
        // 1. Validar que la cuenta docente exista y esté ACTIVA (DT-61)
        $docente = $this->docenteRepo->findById($idDocente);
        if ($docente === null || !$docente->isActivo()) {
            throw new DomainException('La cuenta docente no se encuentra activa o no existe.');
        }

        // 2. Validar campos obligatorios de la solicitud
        $fechaSolicitada = trim((string) ($datos['fecha_solicitada'] ?? ''));
        $tipoMotivo = trim((string) ($datos['tipo_motivo'] ?? ''));
        $descripcion = trim((string) ($datos['descripcion'] ?? ''));

        if ($fechaSolicitada === '' || !$this->validarFormatoFecha($fechaSolicitada)) {
            throw new InvalidArgumentException('La fecha solicitada es obligatoria y debe tener formato válido (YYYY-MM-DD).');
        }

        if ($tipoMotivo === '') {
            throw new InvalidArgumentException('El tipo de motivo es obligatorio.');
        }
        if (mb_strlen($tipoMotivo) > 100) {
            throw new InvalidArgumentException('El tipo de motivo no puede exceder 100 caracteres.');
        }

        if ($descripcion === '') {
            throw new InvalidArgumentException('La descripción es obligatoria.');
        }
        if (mb_strlen($descripcion) > 1000) {
            throw new InvalidArgumentException('La descripción no puede exceder 1000 caracteres.');
        }

        // 3. Validar y procesar archivo de soporte si fue provisto (RF-25, DT-58, DT-60)
        $datosArchivo = null;
        if ($archivoSubido !== null && isset($archivoSubido['error']) && (int) $archivoSubido['error'] !== UPLOAD_ERR_NO_FILE) {
            $datosArchivo = $this->validarYProcesarArchivoSoporte($archivoSubido);
        }

        // 4. Transacción atómica de persistencia (DT-57)
        $this->db->beginTransaction();
        $archivoMovidoRuta = null;

        try {
            // A. Insertar solicitud en estado PENDIENTE autoritativo
            $solicitud = $this->permisoRepo->crearSolicitud(
                $idDocente,
                $fechaSolicitada,
                $tipoMotivo,
                $descripcion
            );

            // B. Registrar evento inicial inmutable en historial
            $this->permisoRepo->crearHistorial(
                $solicitud->id_solicitud_permiso,
                $idUsuario,
                null,
                SolicitudPermiso::ESTADO_PENDIENTE,
                'Creación de solicitud de permiso'
            );

            // C. Si hubo archivo válido, mover a almacenamiento protegido y persistir metadatos
            if ($datosArchivo !== null) {
                $dirDestino = $this->storageBasePath . DIRECTORY_SEPARATOR . self::CARPETA_ALMACENAMIENTO;
                if (!is_dir($dirDestino)) {
                    if (!@mkdir($dirDestino, 0755, true) && !is_dir($dirDestino)) {
                        throw new RuntimeException('No fue posible crear el directorio de almacenamiento de soportes.');
                    }
                }

                $nombreGuardado = 'permiso_' . bin2hex(random_bytes(32)) . '.' . $datosArchivo['extension'];
                $rutaDestinoFisica = $dirDestino . DIRECTORY_SEPARATOR . $nombreGuardado;
                $rutaRelativaAlmacenamiento = self::CARPETA_ALMACENAMIENTO . '/' . $nombreGuardado;

                // Mover archivo temporal
                if (is_uploaded_file($datosArchivo['tmp_path'])) {
                    if (!move_uploaded_file($datosArchivo['tmp_path'], $rutaDestinoFisica)) {
                        throw new RuntimeException('Fallo al mover el archivo subido al almacenamiento seguro.');
                    }
                } else {
                    // Para entornos de testing donde el archivo se prepara en disco
                    if (!copy($datosArchivo['tmp_path'], $rutaDestinoFisica)) {
                        throw new RuntimeException('Fallo al almacenar el archivo de soporte.');
                    }
                }
                $archivoMovidoRuta = $rutaDestinoFisica;

                // Insertar metadatos en documentos_permiso
                $documento = $this->permisoRepo->crearDocumento(
                    $solicitud->id_solicitud_permiso,
                    $datosArchivo['nombre_original'],
                    $rutaRelativaAlmacenamiento,
                    $datosArchivo['mime'],
                    $datosArchivo['size']
                );
                $solicitud->documento = $documento;
            }

            $this->db->commit();
            return $solicitud;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($archivoMovidoRuta !== null && file_exists($archivoMovidoRuta)) {
                @unlink($archivoMovidoRuta);
            }
            throw $e;
        }
    }

    /**
     * Aprobar una solicitud de permiso docente con bloqueo pesimista y registro de historial (RF-27, DT-56, DT-62).
     */
    public function aprobarSolicitud(
        int $idSolicitud,
        int $idUsuarioAdmin,
        ?string $comentario = null
    ): SolicitudPermiso {
        return $this->procesarDecisionAdministrativa(
            $idSolicitud,
            $idUsuarioAdmin,
            SolicitudPermiso::ESTADO_APROBADO,
            $comentario
        );
    }

    /**
     * Rechazar una solicitud de permiso docente con bloqueo pesimista y registro de historial (RF-27, DT-56, DT-62).
     */
    public function rechazarSolicitud(
        int $idSolicitud,
        int $idUsuarioAdmin,
        ?string $comentario = null
    ): SolicitudPermiso {
        return $this->procesarDecisionAdministrativa(
            $idSolicitud,
            $idUsuarioAdmin,
            SolicitudPermiso::ESTADO_RECHAZADO,
            $comentario
        );
    }

    /**
     * Consultar solicitudes propias de un docente (RF-28).
     *
     * @return array<int, SolicitudPermiso>
     */
    public function obtenerSolicitudesDocente(int $idDocente): array
    {
        return $this->permisoRepo->obtenerSolicitudesDocente($idDocente);
    }

    /**
     * Obtener el detalle de una solicitud propia verificando aislamiento horizontal (RF-28, DT-61).
     */
    public function obtenerDetalleSolicitudDocente(int $idSolicitud, int $idDocente): SolicitudPermiso
    {
        $solicitud = $this->permisoRepo->findById($idSolicitud);
        if ($solicitud === null) {
            throw new DomainException('La solicitud de permiso no existe.');
        }

        // Aislamiento horizontal estricto: comprobar que la solicitud pertenece al docente
        if ($solicitud->id_docente !== $idDocente) {
            throw new DomainException('Acceso no autorizado: la solicitud no pertenece a su cuenta docente.');
        }

        return $solicitud;
    }

    /**
     * Listar solicitudes para el panel de administración con filtros (RF-27).
     *
     * @param array<string, mixed> $filtros
     * @return array<int, SolicitudPermiso>
     */
    public function obtenerSolicitudesAdmin(array $filtros = []): array
    {
        return $this->permisoRepo->obtenerTodasSolicitudes($filtros);
    }

    /**
     * Obtener detalle de una solicitud para la vista de administración (RF-27).
     */
    public function obtenerDetalleSolicitudAdmin(int $idSolicitud): SolicitudPermiso
    {
        $solicitud = $this->permisoRepo->findById($idSolicitud);
        if ($solicitud === null) {
            throw new DomainException('La solicitud de permiso no existe.');
        }

        return $solicitud;
    }

    /**
     * Obtener documento de soporte para descarga de docente verificando pertenencia (RF-25, RF-28, DT-59).
     *
     * @return array{documento: DocumentoPermiso, rutaFisica: string}
     */
    public function obtenerDocumentoParaDocente(int $idDocumento, int $idDocente): array
    {
        $doc = $this->permisoRepo->obtenerDocumentoById($idDocumento);
        if ($doc === null) {
            throw new DomainException('El documento de soporte solicitado no existe.');
        }

        $solicitud = $this->permisoRepo->findById($doc->id_solicitud_permiso);
        if ($solicitud === null || $solicitud->id_docente !== $idDocente) {
            throw new DomainException('Acceso denegado: el documento solicitado no pertenece a sus solicitudes.');
        }

        $rutaFisica = $this->resolverRutaFisicaArchivo($doc->ruta_almacenamiento);
        if (!file_exists($rutaFisica)) {
            throw new RuntimeException('El archivo físico del documento de soporte no fue encontrado en el servidor.');
        }

        return ['documento' => $doc, 'rutaFisica' => $rutaFisica];
    }

    /**
     * Obtener documento de soporte para descarga administrativa (RF-25, RF-27, DT-59).
     *
     * @return array{documento: DocumentoPermiso, rutaFisica: string}
     */
    public function obtenerDocumentoParaAdmin(int $idDocumento): array
    {
        $doc = $this->permisoRepo->obtenerDocumentoById($idDocumento);
        if ($doc === null) {
            throw new DomainException('El documento de soporte solicitado no existe.');
        }

        $rutaFisica = $this->resolverRutaFisicaArchivo($doc->ruta_almacenamiento);
        if (!file_exists($rutaFisica)) {
            throw new RuntimeException('El archivo físico del documento de soporte no fue encontrado en el servidor.');
        }

        return ['documento' => $doc, 'rutaFisica' => $rutaFisica];
    }

    /**
     * Proceso atómico de decisión administrativa con bloqueo pesimista (DT-57, DT-62).
     */
    private function procesarDecisionAdministrativa(
        int $idSolicitud,
        int $idUsuarioAdmin,
        string $nuevoEstado,
        ?string $comentario = null
    ): SolicitudPermiso {
        // Validar usuario administrador
        $admin = $this->userRepo->findById($idUsuarioAdmin);
        if ($admin === null || !$admin->isActive() || !$admin->isAdmin()) {
            throw new DomainException('Acceso denegado: solo usuarios administradores activos pueden tomar decisiones sobre permisos.');
        }

        $comentarioLimpio = $comentario !== null ? trim($comentario) : null;
        if ($comentarioLimpio !== null && mb_strlen($comentarioLimpio) > 500) {
            throw new InvalidArgumentException('El comentario de resolución no puede exceder 500 caracteres.');
        }

        $this->db->beginTransaction();

        try {
            // Bloqueo pesimista exclusivo de la fila en BD (DT-62)
            $solicitud = $this->permisoRepo->findForUpdate($idSolicitud);

            if ($solicitud === null) {
                throw new DomainException('La solicitud de permiso no existe.');
            }

            // Máquina de estados: solo PENDIENTE puede pasar a APROBADO o RECHAZADO (DT-56)
            if (!$solicitud->isPendiente()) {
                throw new DomainException(sprintf(
                    'Operación inválida: La solicitud #%d ya fue resuelta previamente (Estado actual: %s).',
                    $idSolicitud,
                    $solicitud->estado
                ));
            }

            // 1. Actualizar estado
            $this->permisoRepo->actualizarEstado($idSolicitud, $nuevoEstado);

            // 2. Insertar historial inmutable de decisión (RNF-06, DT-56)
            $this->permisoRepo->crearHistorial(
                $idSolicitud,
                $idUsuarioAdmin,
                SolicitudPermiso::ESTADO_PENDIENTE,
                $nuevoEstado,
                $comentarioLimpio !== '' ? $comentarioLimpio : null
            );

            $this->db->commit();

            // Retornar entidad actualizada con relaciones hidratadas
            $actualizada = $this->permisoRepo->findById($idSolicitud);
            if ($actualizada === null) {
                throw new RuntimeException('Error al recuperar la solicitud actualizada.');
            }

            return $actualizada;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Validar rigurosamente el archivo de soporte adjunto (RF-25, DT-60).
     *
     * @param array<string, mixed> $archivo
     * @return array{nombre_original: string, tmp_path: string, mime: string, size: int, extension: string}
     */
    private function validarYProcesarArchivoSoporte(array $archivo): array
    {
        $errorCode = (int) ($archivo['error'] ?? UPLOAD_ERR_OK);
        if ($errorCode !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Error durante la carga del archivo de soporte (código: ' . $errorCode . ').');
        }

        $tmpPath = (string) ($archivo['tmp_name'] ?? '');
        if ($tmpPath === '' || !file_exists($tmpPath)) {
            throw new InvalidArgumentException('El archivo temporal de soporte no existe o no es accesible.');
        }

        $size = (int) ($archivo['size'] ?? 0);
        if ($size <= 0) {
            throw new InvalidArgumentException('El archivo adjunto no puede estar vacío.');
        }
        if ($size > self::MAX_BYTES_ARCHIVO) {
            throw new InvalidArgumentException('El archivo adjunto excede el límite máximo permitido de 5 MB.');
        }

        // Sanitización contra path traversal y nombres maliciosos
        $nombreCrudo = (string) ($archivo['name'] ?? 'soporte');
        $nombreOriginal = self::sanitizarNombreArchivo($nombreCrudo);
        if ($nombreOriginal === '') {
            throw new InvalidArgumentException('El nombre del archivo adjunto no es válido.');
        }

        // Extensión
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        if (!in_array($extension, self::EXTENSIONES_PERMITIDAS, true)) {
            throw new InvalidArgumentException(sprintf(
                'Extensión de archivo no permitida (.%s). Formatos autorizados: %s.',
                $extension,
                implode(', ', self::EXTENSIONES_PERMITIDAS)
            ));
        }

        // Validación estricta de Magic Bytes / Tipo MIME real con finfo (DT-60)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new RuntimeException('No fue posible inicializar el analizador de tipos de archivo en el servidor.');
        }
        $mimeReal = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        if (!is_string($mimeReal) || !in_array($mimeReal, self::MIMES_PERMITIDOS, true)) {
            throw new InvalidArgumentException(sprintf(
                'El contenido real del archivo no corresponde a un formato seguro autorizado (Tipo detectado: %s).',
                is_string($mimeReal) ? $mimeReal : 'desconocido'
            ));
        }

        // Coherencia entre extensión y tipo MIME
        $mimeEsperado = self::MIMES_PERMITIDOS[$extension] ?? null;
        if ($mimeEsperado !== null && $mimeReal !== $mimeEsperado) {
            // Manejar compatibilidad jpeg/jpg
            if (!($extension === 'jpg' && $mimeReal === 'image/jpeg')) {
                throw new InvalidArgumentException('El contenido del archivo no coincide con su extensión declarada.');
            }
        }

        return [
            'nombre_original' => $nombreOriginal,
            'tmp_path' => $tmpPath,
            'mime' => $mimeReal,
            'size' => $size,
            'extension' => $extension,
        ];
    }

    /**
     * Sanitizar nombre de archivo previniendo Path Traversal (DT-58).
     */
    public static function sanitizarNombreArchivo(string $nombre): string
    {
        // 1. Remover caracteres nulos
        $nombre = str_replace("\0", '', $nombre);

        // 2. Extraer únicamente el nombre base descartando cualquier ruta
        $nombre = basename($nombre);

        // 3. Reemplazar secuencias de path traversal y caracteres peligrosos
        $nombre = preg_replace('/(\.\.[\/\\\\])+/', '', $nombre);
        $nombre = preg_replace('/[^a-zA-Z0-9_\-\. ]/u', '_', (string) $nombre);

        return trim((string) $nombre);
    }

    private function validarFormatoFecha(string $fecha): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $m)) {
            return false;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }

    private function resolverRutaFisicaArchivo(string $rutaRelativa): string
    {
        return $this->storageBasePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa);
    }
}
