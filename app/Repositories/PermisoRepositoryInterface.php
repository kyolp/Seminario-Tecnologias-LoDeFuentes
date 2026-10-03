<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\DocumentoPermiso;
use App\Models\HistorialSolicitudPermiso;
use App\Models\SolicitudPermiso;

/**
 * Interfaz de repositorio para la gestión de permisos docentes.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 9 — RF-24, RF-25, RF-27, RF-28, RNF-06).
 */
interface PermisoRepositoryInterface
{
    /**
     * Registrar una nueva solicitud de permiso docente con estado inicial PENDIENTE.
     */
    public function crearSolicitud(
        int $idDocente,
        string $fechaSolicitada,
        string $tipoMotivo,
        string $descripcion
    ): SolicitudPermiso;

    /**
     * Registrar un evento inmutable en el historial de transiciones de la solicitud.
     */
    public function crearHistorial(
        int $idSolicitud,
        int $idUsuarioActor,
        ?string $estadoAnterior,
        string $estadoNuevo,
        ?string $comentario = null
    ): HistorialSolicitudPermiso;

    /**
     * Registrar metadatos del documento de respaldo asociado a la solicitud.
     */
    public function crearDocumento(
        int $idSolicitud,
        string $nombreOriginal,
        string $rutaAlmacenamiento,
        string $tipoMime,
        int $tamanoBytes
    ): DocumentoPermiso;

    /**
     * Obtener una solicitud de permiso por su identificador único.
     */
    public function findById(int $idSolicitud): ?SolicitudPermiso;

    /**
     * Obtener y bloquear una solicitud para decisión administrativa mediante SELECT ... FOR UPDATE (DT-62).
     */
    public function findForUpdate(int $idSolicitud): ?SolicitudPermiso;

    /**
     * Actualizar únicamente el estado de la solicitud en base de datos.
     */
    public function actualizarEstado(int $idSolicitud, string $nuevoEstado): bool;

    /**
     * Obtener solicitudes de un docente específico con orden cronológico descendente.
     *
     * @return array<int, SolicitudPermiso>
     */
    public function obtenerSolicitudesDocente(int $idDocente, int $limit = 100, int $offset = 0): array;

    /**
     * Obtener todas las solicitudes para el panel administrativo con filtros opcionales.
     *
     * @param array<string, mixed> $filtros
     * @return array<int, SolicitudPermiso>
     */
    public function obtenerTodasSolicitudes(array $filtros = [], int $limit = 100, int $offset = 0): array;

    /**
     * Obtener el historial completo de transiciones de una solicitud en orden cronológico.
     *
     * @return array<int, HistorialSolicitudPermiso>
     */
    public function obtenerHistorialSolicitud(int $idSolicitud): array;

    /**
     * Obtener el documento de respaldo adjunto a una solicitud.
     */
    public function obtenerDocumentoSolicitud(int $idSolicitud): ?DocumentoPermiso;

    /**
     * Obtener un documento de soporte por su identificador único.
     */
    public function obtenerDocumentoById(int $idDocumento): ?DocumentoPermiso;

    public function findHistorialBySolicitudId(int $idSolicitud): array;

    public function findDocumentoBySolicitudId(int $idSolicitud): ?DocumentoPermiso;

    public function findDocumentoById(int $idDocumento): ?DocumentoPermiso;
}
