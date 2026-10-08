<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Notificacion;
use PDO;

/**
 * Repositorio PDO / MySQL para la entidad Notificacion.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 10 — RF-26).
 */
class NotificacionRepository implements NotificacionRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Crear un nuevo registro de notificación individual en base de datos.
     */
    public function crear(
        int $idSolicitud,
        int $idUsuarioDestinatario,
        string $tipo,
        string $titulo,
        string $mensaje
    ): Notificacion {
        $sql = 'INSERT INTO notificaciones (
                    id_solicitud_permiso,
                    id_usuario_destinatario,
                    tipo,
                    titulo,
                    mensaje,
                    fecha_creacion,
                    fecha_lectura
                ) VALUES (
                    :id_solicitud,
                    :id_destinatario,
                    :tipo,
                    :titulo,
                    :mensaje,
                    CURRENT_TIMESTAMP(6),
                    NULL
                )';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_solicitud', $idSolicitud, PDO::PARAM_INT);
        $stmt->bindValue(':id_destinatario', $idUsuarioDestinatario, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', trim($tipo), PDO::PARAM_STR);
        $stmt->bindValue(':titulo', trim($titulo), PDO::PARAM_STR);
        $stmt->bindValue(':mensaje', trim($mensaje), PDO::PARAM_STR);
        $stmt->execute();

        $idNotificacion = (int) $this->db->lastInsertId();

        $creada = $this->findById($idNotificacion);
        if ($creada !== null) {
            return $creada;
        }

        return new Notificacion(
            id_notificacion: $idNotificacion,
            id_solicitud_permiso: $idSolicitud,
            id_usuario_destinatario: $idUsuarioDestinatario,
            tipo: trim($tipo),
            titulo: trim($titulo),
            mensaje: trim($mensaje),
            fecha_creacion: date('Y-m-d H:i:s'),
            fecha_lectura: null
        );
    }

    /**
     * Crear notificaciones para todos los usuarios administradores activos (RF-26, DT-65).
     */
    public function crearParaAdministradores(
        int $idSolicitud,
        string $tipo,
        string $titulo,
        string $mensaje
    ): int {
        $adminIds = $this->findIdsAdministradoresActivos();
        if (empty($adminIds)) {
            return 0;
        }

        $insertadas = 0;
        foreach ($adminIds as $adminId) {
            $this->crear($idSolicitud, $adminId, $tipo, $titulo, $mensaje);
            $insertadas++;
        }

        return $insertadas;
    }

    /**
     * Buscar una notificación por su ID primario.
     */
    public function findById(int $id): ?Notificacion
    {
        $sql = 'SELECT n.id_notificacion, n.id_solicitud_permiso, n.id_usuario_destinatario,
                       n.tipo, n.titulo, n.mensaje, n.fecha_creacion, n.fecha_lectura,
                       sp.fecha_solicitada, sp.tipo_motivo, sp.estado AS estado_solicitud,
                       CONCAT(u_doc.nombres, " ", u_doc.apellidos) AS docente_nombre
                FROM notificaciones n
                JOIN solicitudes_permiso sp ON sp.id_solicitud_permiso = n.id_solicitud_permiso
                JOIN docentes d ON d.id_docente = sp.id_docente
                JOIN usuarios u_doc ON u_doc.id_usuario = d.id_usuario
                WHERE n.id_notificacion = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }

        return Notificacion::fromArray($data);
    }

    /**
     * Listar notificaciones pertenecientes a un usuario destinatario específico (aislamiento horizontal).
     *
     * @return array<Notificacion>
     */
    public function findByUsuario(
        int $idUsuario,
        ?bool $soloNoLeidas = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $sql = 'SELECT n.id_notificacion, n.id_solicitud_permiso, n.id_usuario_destinatario,
                       n.tipo, n.titulo, n.mensaje, n.fecha_creacion, n.fecha_lectura,
                       sp.fecha_solicitada, sp.tipo_motivo, sp.estado AS estado_solicitud,
                       CONCAT(u_doc.nombres, " ", u_doc.apellidos) AS docente_nombre
                FROM notificaciones n
                JOIN solicitudes_permiso sp ON sp.id_solicitud_permiso = n.id_solicitud_permiso
                JOIN docentes d ON d.id_docente = sp.id_docente
                JOIN usuarios u_doc ON u_doc.id_usuario = d.id_usuario
                WHERE n.id_usuario_destinatario = :id_usuario';

        if ($soloNoLeidas === true) {
            $sql .= ' AND n.fecha_lectura IS NULL';
        } elseif ($soloNoLeidas === false) {
            $sql .= ' AND n.fecha_lectura IS NOT NULL';
        }

        $sql .= ' ORDER BY n.fecha_creacion DESC, n.id_notificacion DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();

        $notificaciones = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $notificaciones[] = Notificacion::fromArray($row);
        }

        return $notificaciones;
    }

    /**
     * Contar la cantidad de notificaciones pendientes de lectura de un usuario.
     */
    public function contarNoLeidasPorUsuario(int $idUsuario): int
    {
        $sql = 'SELECT COUNT(*) 
                FROM notificaciones 
                WHERE id_usuario_destinatario = :id_usuario 
                  AND fecha_lectura IS NULL';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Marcar una notificación específica como leída (actualiza fecha_lectura a CURRENT_TIMESTAMP(6)).
     * Valida que pertenezca al usuario destinatario indicado.
     */
    public function marcarComoLeida(int $idNotificacion, int $idUsuarioDestinatario): bool
    {
        $sql = 'UPDATE notificaciones
                SET fecha_lectura = CURRENT_TIMESTAMP(6)
                WHERE id_notificacion = :id_notificacion
                  AND id_usuario_destinatario = :id_usuario
                  AND fecha_lectura IS NULL';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_notificacion', $idNotificacion, PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario', $idUsuarioDestinatario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Marcar todas las notificaciones pendientes de un usuario como leídas.
     */
    public function marcarTodasComoLeidas(int $idUsuarioDestinatario): int
    {
        $sql = 'UPDATE notificaciones
                SET fecha_lectura = CURRENT_TIMESTAMP(6)
                WHERE id_usuario_destinatario = :id_usuario
                  AND fecha_lectura IS NULL';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuarioDestinatario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Obtener los IDs de todos los usuarios activos con rol administrativo (DT-65).
     *
     * @return array<int>
     */
    public function findIdsAdministradoresActivos(): array
    {
        $sql = 'SELECT u.id_usuario
                FROM usuarios u
                JOIN roles r ON r.id_rol = u.id_rol
                WHERE r.nombre IN ("ADMINISTRACION", "ADMINISTRADOR")
                  AND u.estado = "ACTIVO"
                ORDER BY u.id_usuario ASC';

        $stmt = $this->db->query($sql);
        $ids = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = (int) $row['id_usuario'];
        }

        return $ids;
    }

    /**
     * Obtener todas las notificaciones asociadas a una solicitud de permiso específica.
     *
     * @return array<Notificacion>
     */
    public function findBySolicitudId(int $idSolicitud): array
    {
        $sql = 'SELECT n.id_notificacion, n.id_solicitud_permiso, n.id_usuario_destinatario,
                       n.tipo, n.titulo, n.mensaje, n.fecha_creacion, n.fecha_lectura,
                       sp.fecha_solicitada, sp.tipo_motivo, sp.estado AS estado_solicitud,
                       CONCAT(u_doc.nombres, " ", u_doc.apellidos) AS docente_nombre
                FROM notificaciones n
                JOIN solicitudes_permiso sp ON sp.id_solicitud_permiso = n.id_solicitud_permiso
                JOIN docentes d ON d.id_docente = sp.id_docente
                JOIN usuarios u_doc ON u_doc.id_usuario = d.id_usuario
                WHERE n.id_solicitud_permiso = :id_solicitud
                ORDER BY n.id_notificacion ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_solicitud', $idSolicitud, PDO::PARAM_INT);
        $stmt->execute();

        $notificaciones = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $notificaciones[] = Notificacion::fromArray($row);
        }

        return $notificaciones;
    }
}
