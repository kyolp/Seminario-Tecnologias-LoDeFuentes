<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Repositorio PDO para reportes y consultas institucionales de asistencia docente e inscripciones y cupos.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 12 — RF-29 y RF-30).
 */
class ReporteRepository implements ReporteRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Consultar marcajes de asistencia docente según filtros autorizados (RF-29).
     *
     * @param array<string, mixed> $filtros Criterios: id_docente, fecha_inicio, fecha_fin, tipo, orden
     * @return array<int, array<string, mixed>>
     */
    public function findMarcajesAsistencia(array $filtros = []): array
    {
        $sql = 'SELECT m.id_marcaje_asistencia,
                       m.id_docente,
                       m.fecha_hora,
                       m.tipo,
                       m.distancia_metros,
                       m.dentro_radio,
                       d.codigo_docente,
                       d.estado AS docente_estado,
                       u.nombres AS docente_nombres,
                       u.apellidos AS docente_apellidos,
                       u.nombre_usuario AS docente_usuario,
                       c.descripcion AS configuracion_descripcion
                FROM marcajes_asistencia m
                JOIN docentes d ON d.id_docente = m.id_docente
                JOIN usuarios u ON u.id_usuario = d.id_usuario
                JOIN configuraciones_ubicacion c ON c.id_configuracion_ubicacion = m.id_configuracion_ubicacion
                WHERE 1=1';

        $params = [];

        if (!empty($filtros['id_docente'])) {
            $sql .= ' AND m.id_docente = :id_docente';
            $params[':id_docente'] = (int) $filtros['id_docente'];
        }

        if (!empty($filtros['fecha_inicio'])) {
            $sql .= ' AND DATE(m.fecha_hora) >= :fecha_inicio';
            $params[':fecha_inicio'] = (string) $filtros['fecha_inicio'];
        }

        if (!empty($filtros['fecha_fin'])) {
            $sql .= ' AND DATE(m.fecha_hora) <= :fecha_fin';
            $params[':fecha_fin'] = (string) $filtros['fecha_fin'];
        }

        if (!empty($filtros['tipo'])) {
            $sql .= ' AND m.tipo = :tipo';
            $params[':tipo'] = strtoupper((string) $filtros['tipo']);
        }

        $orden = (isset($filtros['orden']) && strtoupper((string) $filtros['orden']) === 'ASC') ? 'ASC' : 'DESC';
        $sql .= " ORDER BY m.fecha_hora {$orden}, m.id_marcaje_asistencia {$orden}";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $param => $val) {
            if (is_int($val)) {
                $stmt->bindValue($param, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($param, $val, PDO::PARAM_STR);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Consultar resumen de estado de cupos por sección según filtros (RF-30).
     * Reutiliza estrictamente la lógica de ocupación validada (inscripciones CONFIRMADAS y PENDIENTES).
     *
     * @param array<string, mixed> $filtros Criterios: id_periodo_escolar, id_grado, id_seccion
     * @return array<int, array<string, mixed>>
     */
    public function findResumenCuposPorSeccion(array $filtros = []): array
    {
        $sql = 'SELECT s.id_seccion,
                       s.codigo AS seccion_codigo,
                       s.nombre AS seccion_nombre,
                       s.capacidad,
                       s.habilitada AS seccion_habilitada,
                       g.id_grado,
                       g.codigo AS grado_codigo,
                       g.nombre AS grado_nombre,
                       p.id_periodo_escolar,
                       p.nombre AS periodo_nombre,
                       p.estado AS periodo_estado,
                       COALESCE(i_calc.ocupados, 0) AS cupos_ocupados,
                       GREATEST(0, CAST(s.capacidad AS SIGNED) - COALESCE(i_calc.ocupados, 0)) AS cupos_disponibles,
                       CASE 
                           WHEN COALESCE(i_calc.ocupados, 0) >= s.capacidad THEN "LLENO"
                           ELSE "DISPONIBLE"
                       END AS estado_cupo
                FROM secciones s
                JOIN grados_periodos gp ON gp.id_grado_periodo = s.id_grado_periodo
                JOIN grados g ON g.id_grado = gp.id_grado
                JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar
                LEFT JOIN (
                    SELECT id_seccion, COUNT(*) AS ocupados
                    FROM inscripciones
                    WHERE estado IN ("CONFIRMADA", "PENDIENTE")
                    GROUP BY id_seccion
                ) i_calc ON i_calc.id_seccion = s.id_seccion
                WHERE 1=1';

        $params = [];

        if (!empty($filtros['id_periodo_escolar'])) {
            $sql .= ' AND p.id_periodo_escolar = :id_periodo_escolar';
            $params[':id_periodo_escolar'] = (int) $filtros['id_periodo_escolar'];
        }

        if (!empty($filtros['id_grado'])) {
            $sql .= ' AND g.id_grado = :id_grado';
            $params[':id_grado'] = (int) $filtros['id_grado'];
        }

        if (!empty($filtros['id_seccion'])) {
            $sql .= ' AND s.id_seccion = :id_seccion';
            $params[':id_seccion'] = (int) $filtros['id_seccion'];
        }

        $sql .= ' ORDER BY p.nombre ASC, g.orden ASC, g.nombre ASC, s.codigo ASC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $param => $val) {
            $stmt->bindValue($param, $val, PDO::PARAM_INT);
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Asegurar tipos de datos adecuados
        return array_map(function (array $row): array {
            $row['id_seccion'] = (int) $row['id_seccion'];
            $row['capacidad'] = (int) $row['capacidad'];
            $row['seccion_habilitada'] = (int) $row['seccion_habilitada'];
            $row['id_grado'] = (int) $row['id_grado'];
            $row['id_periodo_escolar'] = (int) $row['id_periodo_escolar'];
            $row['cupos_ocupados'] = (int) $row['cupos_ocupados'];
            $row['cupos_disponibles'] = (int) $row['cupos_disponibles'];
            return $row;
        }, $rows);
    }

    /**
     * Consultar estudiantes inscritos según filtros (RF-30).
     *
     * @param array<string, mixed> $filtros Criterios: id_periodo_escolar, id_grado, id_seccion, estado
     * @return array<int, array<string, mixed>>
     */
    public function findInscripcionesDetalle(array $filtros = []): array
    {
        $sql = 'SELECT i.id_inscripcion,
                       i.codigo_confirmacion,
                       i.estado AS inscripcion_estado,
                       i.fecha_inscripcion,
                       i.fecha_confirmacion,
                       e.id_estudiante,
                       e.codigo_estudiante,
                       e.nombres AS estudiante_nombres,
                       e.apellidos AS estudiante_apellidos,
                       s.id_seccion,
                       s.codigo AS seccion_codigo,
                       s.nombre AS seccion_nombre,
                       s.capacidad AS seccion_capacidad,
                       g.id_grado,
                       g.nombre AS grado_nombre,
                       p.id_periodo_escolar,
                       p.nombre AS periodo_nombre
                FROM inscripciones i
                JOIN estudiantes e ON e.id_estudiante = i.id_estudiante
                JOIN secciones s ON s.id_seccion = i.id_seccion
                JOIN grados_periodos gp ON gp.id_grado_periodo = s.id_grado_periodo
                JOIN grados g ON g.id_grado = gp.id_grado
                JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar
                WHERE 1=1';

        $params = [];

        if (!empty($filtros['id_periodo_escolar'])) {
            $sql .= ' AND p.id_periodo_escolar = :id_periodo_escolar';
            $params[':id_periodo_escolar'] = (int) $filtros['id_periodo_escolar'];
        }

        if (!empty($filtros['id_grado'])) {
            $sql .= ' AND g.id_grado = :id_grado';
            $params[':id_grado'] = (int) $filtros['id_grado'];
        }

        if (!empty($filtros['id_seccion'])) {
            $sql .= ' AND s.id_seccion = :id_seccion';
            $params[':id_seccion'] = (int) $filtros['id_seccion'];
        }

        if (!empty($filtros['estado'])) {
            $sql .= ' AND i.estado = :estado';
            $params[':estado'] = strtoupper((string) $filtros['estado']);
        }

        $sql .= ' ORDER BY i.fecha_inscripcion DESC, i.id_inscripcion DESC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $param => $val) {
            if (is_int($val)) {
                $stmt->bindValue($param, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($param, $val, PDO::PARAM_STR);
            }
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $row): array {
            $row['id_inscripcion'] = (int) $row['id_inscripcion'];
            $row['id_estudiante'] = (int) $row['id_estudiante'];
            $row['id_seccion'] = (int) $row['id_seccion'];
            $row['id_grado'] = (int) $row['id_grado'];
            $row['id_periodo_escolar'] = (int) $row['id_periodo_escolar'];
            $row['seccion_capacidad'] = (int) $row['seccion_capacidad'];
            return $row;
        }, $rows);
    }
}
