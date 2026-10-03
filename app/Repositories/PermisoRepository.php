<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Docente;
use App\Models\DocumentoPermiso;
use App\Models\HistorialSolicitudPermiso;
use App\Models\SolicitudPermiso;
use App\Models\User;
use PDO;

/**
 * Implementación PDO del repositorio de permisos docentes.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 9).
 */
class PermisoRepository implements PermisoRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function crearSolicitud(
        int $idDocente,
        string $fechaSolicitada,
        string $tipoMotivo,
        string $descripcion
    ): SolicitudPermiso {
        $sql = 'INSERT INTO solicitudes_permiso (
                    id_docente,
                    fecha_solicitada,
                    tipo_motivo,
                    descripcion,
                    estado,
                    fecha_solicitud,
                    fecha_actualizacion
                ) VALUES (
                    :id_docente,
                    :fecha_solicitada,
                    :tipo_motivo,
                    :descripcion,
                    :estado,
                    CURRENT_TIMESTAMP(6),
                    CURRENT_TIMESTAMP(6)
                )';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_docente' => $idDocente,
            ':fecha_solicitada' => $fechaSolicitada,
            ':tipo_motivo' => $tipoMotivo,
            ':descripcion' => $descripcion,
            ':estado' => SolicitudPermiso::ESTADO_PENDIENTE,
        ]);

        $id = (int) $this->db->lastInsertId();
        $creada = $this->findById($id);
        if ($creada === null) {
            throw new \RuntimeException('Error al recuperar la solicitud de permiso recién creada.');
        }

        return $creada;
    }

    public function crearHistorial(
        int $idSolicitud,
        int $idUsuarioActor,
        ?string $estadoAnterior,
        string $estadoNuevo,
        ?string $comentario = null
    ): HistorialSolicitudPermiso {
        $sql = 'INSERT INTO historial_solicitudes_permiso (
                    id_solicitud_permiso,
                    id_usuario_actor,
                    estado_anterior,
                    estado_nuevo,
                    comentario,
                    fecha_cambio
                ) VALUES (
                    :id_solicitud,
                    :id_actor,
                    :anterior,
                    :nuevo,
                    :comentario,
                    CURRENT_TIMESTAMP(6)
                )';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_solicitud' => $idSolicitud,
            ':id_actor' => $idUsuarioActor,
            ':anterior' => $estadoAnterior,
            ':nuevo' => $estadoNuevo,
            ':comentario' => $comentario,
        ]);

        $id = (int) $this->db->lastInsertId();

        $stmtGet = $this->db->prepare('SELECT * FROM historial_solicitudes_permiso WHERE id_historial_permiso = :id LIMIT 1');
        $stmtGet->execute([':id' => $id]);
        $row = $stmtGet->fetch(PDO::FETCH_ASSOC);

        return new HistorialSolicitudPermiso($row ?: [
            'id_historial_permiso' => $id,
            'id_solicitud_permiso' => $idSolicitud,
            'id_usuario_actor' => $idUsuarioActor,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'comentario' => $comentario,
        ]);
    }

    public function crearDocumento(
        int $idSolicitud,
        string $nombreOriginal,
        string $rutaAlmacenamiento,
        string $tipoMime,
        int $tamanoBytes
    ): DocumentoPermiso {
        $sql = 'INSERT INTO documentos_permiso (
                    id_solicitud_permiso,
                    nombre_original,
                    ruta_almacenamiento,
                    tipo_mime,
                    tamano_bytes,
                    fecha_carga
                ) VALUES (
                    :id_solicitud,
                    :nombre,
                    :ruta,
                    :mime,
                    :tamano,
                    CURRENT_TIMESTAMP(6)
                )';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_solicitud' => $idSolicitud,
            ':nombre' => $nombreOriginal,
            ':ruta' => $rutaAlmacenamiento,
            ':mime' => $tipoMime,
            ':tamano' => $tamanoBytes,
        ]);

        $id = (int) $this->db->lastInsertId();

        $stmtGet = $this->db->prepare('SELECT * FROM documentos_permiso WHERE id_documento_permiso = :id LIMIT 1');
        $stmtGet->execute([':id' => $id]);
        $row = $stmtGet->fetch(PDO::FETCH_ASSOC);

        return new DocumentoPermiso($row ?: [
            'id_documento_permiso' => $id,
            'id_solicitud_permiso' => $idSolicitud,
            'nombre_original' => $nombreOriginal,
            'ruta_almacenamiento' => $rutaAlmacenamiento,
            'tipo_mime' => $tipoMime,
            'tamano_bytes' => $tamanoBytes,
        ]);
    }

    public function findById(int $idSolicitud): ?SolicitudPermiso
    {
        $stmt = $this->db->prepare('SELECT * FROM solicitudes_permiso WHERE id_solicitud_permiso = :id LIMIT 1');
        $stmt->execute([':id' => $idSolicitud]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $solicitud = new SolicitudPermiso($row);
        $this->hidratarRelaciones($solicitud);

        return $solicitud;
    }

    public function findForUpdate(int $idSolicitud): ?SolicitudPermiso
    {
        $stmt = $this->db->prepare('SELECT * FROM solicitudes_permiso WHERE id_solicitud_permiso = :id LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $idSolicitud]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new SolicitudPermiso($row);
    }

    public function actualizarEstado(int $idSolicitud, string $nuevoEstado): bool
    {
        $sql = 'UPDATE solicitudes_permiso
                SET estado = :estado,
                    fecha_actualizacion = CURRENT_TIMESTAMP(6)
                WHERE id_solicitud_permiso = :id';

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':estado' => $nuevoEstado,
            ':id' => $idSolicitud,
        ]);
    }

    public function obtenerSolicitudesDocente(int $idDocente, int $limit = 100, int $offset = 0): array
    {
        $sql = 'SELECT * FROM solicitudes_permiso
                WHERE id_docente = :docente
                ORDER BY fecha_solicitud DESC
                LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':docente', $idDocente, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resultado = [];

        foreach ($rows as $row) {
            $solicitud = new SolicitudPermiso($row);
            $solicitud->documento = $this->obtenerDocumentoSolicitud($solicitud->id_solicitud_permiso);
            $resultado[] = $solicitud;
        }

        return $resultado;
    }

    public function obtenerTodasSolicitudes(array $filtros = [], int $limit = 100, int $offset = 0): array
    {
        $sql = 'SELECT s.*, d.codigo_docente, u.nombres, u.apellidos
                FROM solicitudes_permiso s
                JOIN docentes d ON s.id_docente = d.id_docente
                JOIN usuarios u ON d.id_usuario = u.id_usuario
                WHERE 1=1';

        $params = [];

        if (!empty($filtros['estado'])) {
            $sql .= ' AND s.estado = :estado';
            $params[':estado'] = (string) $filtros['estado'];
        }

        if (!empty($filtros['id_docente'])) {
            $sql .= ' AND s.id_docente = :id_docente';
            $params[':id_docente'] = (int) $filtros['id_docente'];
        }

        if (!empty($filtros['fecha_solicitada'])) {
            $sql .= ' AND s.fecha_solicitada = :fecha_solicitada';
            $params[':fecha_solicitada'] = (string) $filtros['fecha_solicitada'];
        }

        $sql .= ' ORDER BY s.fecha_solicitud DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resultado = [];

        foreach ($rows as $row) {
            $solicitud = new SolicitudPermiso($row);
            $solicitud->documento = $this->obtenerDocumentoSolicitud($solicitud->id_solicitud_permiso);
            
            // Adjuntar datos docentes de presentación
            $docente = \App\Models\Docente::fromArray([
                'id_docente' => $row['id_docente'],
                'codigo_docente' => $row['codigo_docente'] ?? '',
                'nombres' => $row['nombres'] ?? '',
                'apellidos' => $row['apellidos'] ?? '',
            ]);
            $solicitud->docente = $docente;

            $resultado[] = $solicitud;
        }

        return $resultado;
    }

    public function obtenerHistorialSolicitud(int $idSolicitud): array
    {
        $sql = 'SELECT h.*, u.nombre_usuario, u.nombres, u.apellidos, r.nombre AS rol_nombre
                FROM historial_solicitudes_permiso h
                JOIN usuarios u ON h.id_usuario_actor = u.id_usuario
                JOIN roles r ON u.id_rol = r.id_rol
                WHERE h.id_solicitud_permiso = :id
                ORDER BY h.fecha_cambio ASC, h.id_historial_permiso ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idSolicitud]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $historial = [];

        foreach ($rows as $r) {
            $h = new HistorialSolicitudPermiso($r);
            $actor = \App\Models\User::fromArray([
                'id_usuario' => $r['id_usuario_actor'],
                'nombre_usuario' => $r['nombre_usuario'],
                'nombres' => $r['nombres'],
                'apellidos' => $r['apellidos'],
                'rol_nombre' => $r['rol_nombre'],
            ]);
            $h->usuarioActor = $actor;
            $historial[] = $h;
        }

        return $historial;
    }

    public function obtenerDocumentoSolicitud(int $idSolicitud): ?DocumentoPermiso
    {
        $sql = 'SELECT * FROM documentos_permiso
                WHERE id_solicitud_permiso = :id
                ORDER BY id_documento_permiso DESC
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idSolicitud]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? new DocumentoPermiso($row) : null;
    }

    public function obtenerDocumentoById(int $idDocumento): ?DocumentoPermiso
    {
        $sql = 'SELECT * FROM documentos_permiso
                WHERE id_documento_permiso = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idDocumento]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? new DocumentoPermiso($row) : null;
    }

    public function findHistorialBySolicitudId(int $idSolicitud): array
    {
        return $this->obtenerHistorialSolicitud($idSolicitud);
    }

    public function findDocumentoBySolicitudId(int $idSolicitud): ?DocumentoPermiso
    {
        return $this->obtenerDocumentoSolicitud($idSolicitud);
    }

    public function findDocumentoById(int $idDocumento): ?DocumentoPermiso
    {
        return $this->obtenerDocumentoById($idDocumento);
    }

    private function hidratarRelaciones(SolicitudPermiso $solicitud): void
    {
        $solicitud->documento = $this->obtenerDocumentoSolicitud($solicitud->id_solicitud_permiso);
        $solicitud->historial = $this->obtenerHistorialSolicitud($solicitud->id_solicitud_permiso);

        // Docente
        $stmtDoc = $this->db->prepare('SELECT d.*, u.nombres, u.apellidos, u.correo, u.nombre_usuario
                                       FROM docentes d
                                       JOIN usuarios u ON d.id_usuario = u.id_usuario
                                       WHERE d.id_docente = :id LIMIT 1');
        $stmtDoc->execute([':id' => $solicitud->id_docente]);
        $rowDoc = $stmtDoc->fetch(PDO::FETCH_ASSOC);

        if ($rowDoc) {
            $solicitud->docente = \App\Models\Docente::fromArray($rowDoc);
        }
    }
}
