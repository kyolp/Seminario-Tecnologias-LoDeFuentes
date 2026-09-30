<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Inscripcion;
use PDO;

class InscripcionRepository implements InscripcionRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function findById(int $id): ?Inscripcion
    {
        $sql = 'SELECT i.id_inscripcion, i.id_estudiante, i.id_encargado, i.id_seccion,
                       i.codigo_confirmacion, i.estado, i.fecha_inscripcion, i.fecha_confirmacion, i.fecha_actualizacion,
                       e.nombres AS estudiante_nombres, e.apellidos AS estudiante_apellidos,
                       e.codigo_estudiante, e.fecha_nacimiento AS estudiante_fecha_nacimiento,
                       enc.nombres AS encargado_nombres, enc.apellidos AS encargado_apellidos,
                       enc.telefono AS encargado_telefono, enc.correo AS encargado_correo,
                       ee.parentesco,
                       s.codigo AS seccion_codigo, s.nombre AS seccion_nombre, s.capacidad AS seccion_capacidad,
                       g.nombre AS grado_nombre, p.nombre AS periodo_nombre, p.id_periodo_escolar
                FROM inscripciones i
                JOIN estudiantes e ON e.id_estudiante = i.id_estudiante
                JOIN encargados enc ON enc.id_encargado = i.id_encargado
                JOIN estudiantes_encargados ee ON ee.id_estudiante = i.id_estudiante AND ee.id_encargado = i.id_encargado
                JOIN secciones s ON s.id_seccion = i.id_seccion
                JOIN grados_periodos gp ON gp.id_grado_periodo = s.id_grado_periodo
                JOIN grados g ON g.id_grado = gp.id_grado
                JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar
                WHERE i.id_inscripcion = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Inscripcion::fromArray($data) : null;
    }

    public function findByCodigoConfirmacion(string $codigo): ?Inscripcion
    {
        $sql = 'SELECT i.id_inscripcion, i.id_estudiante, i.id_encargado, i.id_seccion,
                       i.codigo_confirmacion, i.estado, i.fecha_inscripcion, i.fecha_confirmacion, i.fecha_actualizacion,
                       e.nombres AS estudiante_nombres, e.apellidos AS estudiante_apellidos,
                       e.codigo_estudiante, e.fecha_nacimiento AS estudiante_fecha_nacimiento,
                       enc.nombres AS encargado_nombres, enc.apellidos AS encargado_apellidos,
                       enc.telefono AS encargado_telefono, enc.correo AS encargado_correo,
                       ee.parentesco,
                       s.codigo AS seccion_codigo, s.nombre AS seccion_nombre, s.capacidad AS seccion_capacidad,
                       g.nombre AS grado_nombre, p.nombre AS periodo_nombre, p.id_periodo_escolar
                FROM inscripciones i
                JOIN estudiantes e ON e.id_estudiante = i.id_estudiante
                JOIN encargados enc ON enc.id_encargado = i.id_encargado
                JOIN estudiantes_encargados ee ON ee.id_estudiante = i.id_estudiante AND ee.id_encargado = i.id_encargado
                JOIN secciones s ON s.id_seccion = i.id_seccion
                JOIN grados_periodos gp ON gp.id_grado_periodo = s.id_grado_periodo
                JOIN grados g ON g.id_grado = gp.id_grado
                JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar
                WHERE i.codigo_confirmacion = :codigo
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':codigo', trim($codigo), PDO::PARAM_STR);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Inscripcion::fromArray($data) : null;
    }

    public function findVigenteByEstudianteAndSeccion(int $idEstudiante, int $idSeccion): ?Inscripcion
    {
        $sql = 'SELECT id_inscripcion, id_estudiante, id_encargado, id_seccion,
                       codigo_confirmacion, estado, fecha_inscripcion, fecha_confirmacion
                FROM inscripciones
                WHERE id_estudiante = :id_estudiante
                  AND id_seccion = :id_seccion
                  AND estado IN ("CONFIRMADA", "PENDIENTE")
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_estudiante', $idEstudiante, PDO::PARAM_INT);
        $stmt->bindValue(':id_seccion', $idSeccion, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? Inscripcion::fromArray($data) : null;
    }

    public function countOcupacionBySeccion(int $idSeccion): int
    {
        $sql = 'SELECT COUNT(*)
                FROM inscripciones
                WHERE id_seccion = :id_seccion
                  AND estado IN ("CONFIRMADA", "PENDIENTE")';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_seccion', $idSeccion, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    public function lockSeccionForUpdate(int $idSeccion): ?array
    {
        $sql = 'SELECT id_seccion, id_grado_periodo, codigo, nombre, capacidad, habilitada
                FROM secciones
                WHERE id_seccion = :id_seccion
                FOR UPDATE';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_seccion', $idSeccion, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? [
            'id_seccion' => (int)$row['id_seccion'],
            'id_grado_periodo' => (int)$row['id_grado_periodo'],
            'codigo' => (string)$row['codigo'],
            'nombre' => $row['nombre'] !== null ? (string)$row['nombre'] : null,
            'capacidad' => (int)$row['capacidad'],
            'habilitada' => (int)$row['habilitada'],
        ] : null;
    }

    public function create(array $data): int
    {
        // Importante: No incluir columnas generadas (estudiante_vigente, seccion_vigente)
        $sql = 'INSERT INTO inscripciones (id_estudiante, id_encargado, id_seccion, codigo_confirmacion, estado, fecha_confirmacion)
                VALUES (:id_estudiante, :id_encargado, :id_seccion, :codigo_confirmacion, :estado, :fecha_confirmacion)';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_estudiante', (int)$data['id_estudiante'], PDO::PARAM_INT);
        $stmt->bindValue(':id_encargado', (int)$data['id_encargado'], PDO::PARAM_INT);
        $stmt->bindValue(':id_seccion', (int)$data['id_seccion'], PDO::PARAM_INT);
        $stmt->bindValue(':codigo_confirmacion', !empty($data['codigo_confirmacion']) ? (string)$data['codigo_confirmacion'] : null, PDO::PARAM_STR);
        $stmt->bindValue(':estado', (string)($data['estado'] ?? 'CONFIRMADA'), PDO::PARAM_STR);
        $stmt->bindValue(':fecha_confirmacion', !empty($data['fecha_confirmacion']) ? (string)$data['fecha_confirmacion'] : null, PDO::PARAM_STR);
        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    public function existsCodigo(string $codigo): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM inscripciones WHERE codigo_confirmacion = :codigo LIMIT 1');
        $stmt->bindValue(':codigo', trim($codigo), PDO::PARAM_STR);
        $stmt->execute();

        return (bool)$stmt->fetchColumn();
    }

    public function findAll(array $filters = []): array
    {
        $sql = 'SELECT i.id_inscripcion, i.id_estudiante, i.id_encargado, i.id_seccion,
                       i.codigo_confirmacion, i.estado, i.fecha_inscripcion, i.fecha_confirmacion, i.fecha_actualizacion,
                       e.nombres AS estudiante_nombres, e.apellidos AS estudiante_apellidos,
                       e.codigo_estudiante, e.fecha_nacimiento AS estudiante_fecha_nacimiento,
                       enc.nombres AS encargado_nombres, enc.apellidos AS encargado_apellidos,
                       enc.telefono AS encargado_telefono, enc.correo AS encargado_correo,
                       ee.parentesco,
                       s.codigo AS seccion_codigo, s.nombre AS seccion_nombre, s.capacidad AS seccion_capacidad,
                       g.nombre AS grado_nombre, p.nombre AS periodo_nombre, p.id_periodo_escolar
                FROM inscripciones i
                JOIN estudiantes e ON e.id_estudiante = i.id_estudiante
                JOIN encargados enc ON enc.id_encargado = i.id_encargado
                JOIN estudiantes_encargados ee ON ee.id_estudiante = i.id_estudiante AND ee.id_encargado = i.id_encargado
                JOIN secciones s ON s.id_seccion = i.id_seccion
                JOIN grados_periodos gp ON gp.id_grado_periodo = s.id_grado_periodo
                JOIN grados g ON g.id_grado = gp.id_grado
                JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar
                WHERE 1=1';

        $params = [];

        if (!empty($filters['id_seccion'])) {
            $sql .= ' AND i.id_seccion = :id_seccion';
            $params[':id_seccion'] = (int)$filters['id_seccion'];
        }

        if (!empty($filters['id_periodo_escolar'])) {
            $sql .= ' AND p.id_periodo_escolar = :id_periodo_escolar';
            $params[':id_periodo_escolar'] = (int)$filters['id_periodo_escolar'];
        }

        if (!empty($filters['estado'])) {
            $sql .= ' AND i.estado = :estado';
            $params[':estado'] = (string)$filters['estado'];
        }

        $sql .= ' ORDER BY i.fecha_inscripcion DESC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        $list = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $list[] = Inscripcion::fromArray($row);
        }

        return $list;
    }
}
