<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\MarcajeAsistencia;
use PDO;

/**
 * Repositorio PDO para la persistencia inmutable de marcajes de asistencia docente.
 * Aplica el principio de inmutabilidad (DT-52) y aislamiento horizontal por id_docente (DT-54).
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 8 — RF-20 a RF-23).
 */
class AsistenciaRepository implements AsistenciaRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Registrar un marcaje válido dentro del radio.
     * La fecha y hora se establecen autoritativamente mediante CURRENT_TIMESTAMP(6).
     */
    public function crearMarcaje(
        int $idDocente,
        int $idUsuario,
        int $idConfiguracion,
        string $tipo,
        float $latitud,
        float $longitud,
        ?float $precision,
        float $distancia
    ): MarcajeAsistencia {
        $tipo = strtoupper(trim($tipo));

        $sql = 'INSERT INTO marcajes_asistencia (
                    id_docente,
                    id_usuario,
                    id_configuracion_ubicacion,
                    fecha_hora,
                    tipo,
                    latitud_dispositivo,
                    longitud_dispositivo,
                    precision_metros,
                    distancia_metros,
                    dentro_radio
                ) VALUES (
                    :d,
                    :u,
                    :c,
                    CURRENT_TIMESTAMP(6),
                    :tipo,
                    :lat,
                    :lon,
                    :prec,
                    :dist,
                    1
                )';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':d', $idDocente, PDO::PARAM_INT);
        $stmt->bindValue(':u', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':c', $idConfiguracion, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindValue(':lat', $latitud);
        $stmt->bindValue(':lon', $longitud);
        if ($precision !== null) {
            $stmt->bindValue(':prec', $precision);
        } else {
            $stmt->bindValue(':prec', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':dist', $distancia);

        $stmt->execute();
        $id = (int) $this->db->lastInsertId();

        $marcaje = $this->findMarcajeByIdAndDocente($id, $idDocente);
        if ($marcaje === null) {
            throw new \RuntimeException('No fue posible recuperar el registro de marcaje recién creado.');
        }

        return $marcaje;
    }

    /**
     * Obtener el último marcaje del docente.
     */
    public function findUltimoMarcajeDocente(int $idDocente): ?MarcajeAsistencia
    {
        $sql = 'SELECT m.*, c.descripcion AS configuracion_descripcion
                FROM marcajes_asistencia m
                JOIN configuraciones_ubicacion c ON c.id_configuracion_ubicacion = m.id_configuracion_ubicacion
                WHERE m.id_docente = :d
                ORDER BY m.fecha_hora DESC, m.id_marcaje_asistencia DESC
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':d', $idDocente, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? new MarcajeAsistencia($row) : null;
    }

    /**
     * Consultar el historial de marcajes de un docente específico.
     *
     * @return array<int, MarcajeAsistencia>
     */
    public function findMarcajesByDocente(int $idDocente, int $limit = 50): array
    {
        $sql = 'SELECT m.*, c.descripcion AS configuracion_descripcion
                FROM marcajes_asistencia m
                JOIN configuraciones_ubicacion c ON c.id_configuracion_ubicacion = m.id_configuracion_ubicacion
                WHERE m.id_docente = :d
                ORDER BY m.fecha_hora DESC, m.id_marcaje_asistencia DESC
                LIMIT :lim';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':d', $idDocente, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new MarcajeAsistencia($row);
        }

        return $results;
    }

    /**
     * Buscar un marcaje por ID y docente asegurando aislamiento horizontal.
     */
    public function findMarcajeByIdAndDocente(int $idMarcaje, int $idDocente): ?MarcajeAsistencia
    {
        $sql = 'SELECT m.*, c.descripcion AS configuracion_descripcion
                FROM marcajes_asistencia m
                JOIN configuraciones_ubicacion c ON c.id_configuracion_ubicacion = m.id_configuracion_ubicacion
                WHERE m.id_marcaje_asistencia = :id AND m.id_docente = :d
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $idMarcaje, PDO::PARAM_INT);
        $stmt->bindValue(':d', $idDocente, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? new MarcajeAsistencia($row) : null;
    }

    /**
     * Contar el total de marcajes de un docente.
     */
    public function countMarcajesByDocente(int $idDocente): int
    {
        $sql = 'SELECT COUNT(*) FROM marcajes_asistencia WHERE id_docente = :d';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':d', $idDocente, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
