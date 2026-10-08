<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Notificacion;
use App\Models\SolicitudPermiso;
use App\Repositories\NotificacionRepository;
use App\Repositories\NotificacionRepositoryInterface;
use DomainException;
use PDO;

/**
 * Servicio de negocio para el módulo de Notificaciones.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 10 — RF-26).
 *
 * Implementa las decisiones técnicas:
 * - DT-64: Despacho transaccional atómico de notificaciones ante nueva solicitud docente.
 * - DT-65: Determinación autoritativa de destinatarios administrativos y aislamiento por usuario.
 * - DT-66: Marcas de tiempo autoritativas de servidor y control de lectura inmutable.
 */
class NotificacionService
{
    private PDO $db;
    private NotificacionRepositoryInterface $notificacionRepo;

    public function __construct(
        ?NotificacionRepositoryInterface $notificacionRepo = null,
        ?PDO $db = null
    ) {
        $this->db = $db ?? Database::getConnection();
        $this->notificacionRepo = $notificacionRepo ?? new NotificacionRepository($this->db);
    }

    /**
     * Notificar a la administración sobre la creación de una nueva solicitud de permiso (RF-26, DT-64, DT-65).
     *
     * @param SolicitudPermiso $solicitud Solicitud recién persistida en estado PENDIENTE.
     * @param string|null $docenteNombre Nombre completo del docente solicitante.
     * @return int Cantidad de notificaciones generadas para la administración.
     */
    public function notificarNuevaSolicitud(SolicitudPermiso $solicitud, ?string $docenteNombre = null): int
    {
        $nombreDocenteLimpio = trim((string) ($docenteNombre ?? 'Docente titular'));
        if ($nombreDocenteLimpio === '') {
            $nombreDocenteLimpio = 'Docente titular';
        }

        $titulo = 'Nueva solicitud de permiso docente';
        $mensaje = sprintf(
            'El/La docente %s ha registrado una nueva solicitud de permiso para la fecha %s (Motivo: %s).',
            $nombreDocenteLimpio,
            $solicitud->fecha_solicitada,
            $solicitud->tipo_motivo
        );

        // Truncado preventivo defensivo para cumplir restricciones VARCHAR del esquema MySQL
        if (mb_strlen($titulo) > 150) {
            $titulo = mb_substr($titulo, 0, 147) . '...';
        }
        if (mb_strlen($mensaje) > 500) {
            $mensaje = mb_substr($mensaje, 0, 497) . '...';
        }

        return $this->notificacionRepo->crearParaAdministradores(
            $solicitud->id_solicitud_permiso,
            Notificacion::TIPO_NUEVA_SOLICITUD,
            $titulo,
            $mensaje
        );
    }

    /**
     * Obtener listado de notificaciones de un usuario con aislamiento horizontal estricto (DT-65).
     *
     * @return array<Notificacion>
     */
    public function obtenerNotificacionesUsuario(
        int $idUsuario,
        ?bool $soloNoLeidas = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        return $this->notificacionRepo->findByUsuario($idUsuario, $soloNoLeidas, $limit, $offset);
    }

    /**
     * Obtener el conteo de notificaciones pendientes de lectura de un usuario (para insignias en UI).
     */
    public function obtenerConteoNoLeidas(int $idUsuario): int
    {
        return $this->notificacionRepo->contarNoLeidasPorUsuario($idUsuario);
    }

    /**
     * Consultar una notificación específica verificando pertenencia estricta al usuario.
     */
    public function obtenerNotificacion(int $idNotificacion, int $idUsuario): Notificacion
    {
        $notificacion = $this->notificacionRepo->findById($idNotificacion);
        if ($notificacion === null) {
            throw new DomainException('La notificación solicitada no existe.');
        }

        if ($notificacion->id_usuario_destinatario !== $idUsuario) {
            throw new DomainException('No tiene privilegios para acceder a esta notificación.');
        }

        return $notificacion;
    }

    /**
     * Marcar una notificación como leída con validación estricta de propiedad (DT-66).
     */
    public function marcarLeida(int $idNotificacion, int $idUsuario): bool
    {
        // 1. Verificar existencia y propiedad antes de mutar
        $notificacion = $this->notificacionRepo->findById($idNotificacion);
        if ($notificacion === null) {
            throw new DomainException('La notificación solicitada no existe.');
        }

        if ($notificacion->id_usuario_destinatario !== $idUsuario) {
            throw new DomainException('No tiene privilegios para modificar el estado de esta notificación.');
        }

        // Si ya estaba leída, retornar true sin re-escribir
        if ($notificacion->isLeida()) {
            return true;
        }

        return $this->notificacionRepo->marcarComoLeida($idNotificacion, $idUsuario);
    }

    /**
     * Marcar todas las notificaciones pendientes del usuario como leídas.
     */
    public function marcarTodasLeidas(int $idUsuario): int
    {
        return $this->notificacionRepo->marcarTodasComoLeidas($idUsuario);
    }
}
