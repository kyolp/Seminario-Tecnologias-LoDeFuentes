<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Notificacion;

/**
 * Contrato de persistencia para el módulo de Notificaciones.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 10 — RF-26).
 */
interface NotificacionRepositoryInterface
{
    /**
     * Crear un nuevo registro de notificación individual.
     */
    public function crear(
        int $idSolicitud,
        int $idUsuarioDestinatario,
        string $tipo,
        string $titulo,
        string $mensaje
    ): Notificacion;

    /**
     * Crear notificaciones para todos los usuarios administradores activos (RF-26, DT-65).
     * Retorna el número de registros insertados.
     */
    public function crearParaAdministradores(
        int $idSolicitud,
        string $tipo,
        string $titulo,
        string $mensaje
    ): int;

    /**
     * Buscar una notificación por su ID primario.
     */
    public function findById(int $id): ?Notificacion;

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
    ): array;

    /**
     * Contar la cantidad de notificaciones pendientes de lectura de un usuario.
     */
    public function contarNoLeidasPorUsuario(int $idUsuario): int;

    /**
     * Marcar una notificación específica como leída (actualiza fecha_lectura a CURRENT_TIMESTAMP(6)).
     * Valida que pertenezca al usuario destinatario indicado.
     */
    public function marcarComoLeida(int $idNotificacion, int $idUsuarioDestinatario): bool;

    /**
     * Marcar todas las notificaciones pendientes de un usuario como leídas.
     * Retorna la cantidad de notificaciones actualizadas.
     */
    public function marcarTodasComoLeidas(int $idUsuarioDestinatario): int;

    /**
     * Obtener los IDs de todos los usuarios activos con rol administrativo.
     *
     * @return array<int>
     */
    public function findIdsAdministradoresActivos(): array;

    /**
     * Obtener todas las notificaciones asociadas a una solicitud de permiso específica.
     *
     * @return array<Notificacion>
     */
    public function findBySolicitudId(int $idSolicitud): array;
}
