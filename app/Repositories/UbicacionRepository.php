<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ConfiguracionUbicacion;
use PDO;

/**
 * Repositorio PDO para la administración de configuraciones de ubicación institucional.
 * Implementa el manejo transaccional de históricos y la unicidad condicional (DT-53).
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 8 — RF-18).
 */
class UbicacionRepository implements UbicacionRepositoryInterface
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /**
     * Obtener la configuración activa actual.
     */
    public function findConfiguracionActiva(): ?ConfiguracionUbicacion
    {
        $sql = 'SELECT c.*, CONCAT(u.nombres, " ", u.apellidos) AS usuario_creador_nombre
                FROM configuraciones_ubicacion c
                LEFT JOIN usuarios u ON u.id_usuario = c.id_usuario_creador
                WHERE c.activa = 1
                LIMIT 1';

        $stmt = $this->db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? new ConfiguracionUbicacion($row) : null;
    }

    /**
     * Buscar una configuración por ID.
     */
    public function findById(int $id): ?ConfiguracionUbicacion
    {
        $sql = 'SELECT c.*, CONCAT(u.nombres, " ", u.apellidos) AS usuario_creador_nombre
                FROM configuraciones_ubicacion c
                LEFT JOIN usuarios u ON u.id_usuario = c.id_usuario_creador
                WHERE c.id_configuracion_ubicacion = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? new ConfiguracionUbicacion($row) : null;
    }

    /**
     * Listar todas las configuraciones ordenadas descendentemente por fecha.
     *
     * @return array<int, ConfiguracionUbicacion>
     */
    public function findAll(): array
    {
        $sql = 'SELECT c.*, CONCAT(u.nombres, " ", u.apellidos) AS usuario_creador_nombre
                FROM configuraciones_ubicacion c
                LEFT JOIN usuarios u ON u.id_usuario = c.id_usuario_creador
                ORDER BY c.activa DESC, c.fecha_creacion DESC';

        $stmt = $this->db->query($sql);
        $results = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new ConfiguracionUbicacion($row);
        }

        return $results;
    }

    /**
     * Crear y activar una nueva configuración institucional de forma transaccional.
     */
    public function crearConfiguracionActiva(
        int $idUsuarioCreador,
        string $descripcion,
        float $latitud,
        float $longitud,
        float $radioMetros
    ): ConfiguracionUbicacion {
        $this->db->beginTransaction();

        try {
            // 1. Desactivar configuración activa anterior preservando fecha_fin para auditoría
            $stmtDeactivate = $this->db->prepare(
                'UPDATE configuraciones_ubicacion 
                 SET activa = 0, fecha_fin = CURRENT_TIMESTAMP(6) 
                 WHERE activa = 1'
            );
            $stmtDeactivate->execute();

            // 2. Insertar la nueva configuración activa
            $stmtInsert = $this->db->prepare(
                'INSERT INTO configuraciones_ubicacion 
                 (id_usuario_creador, descripcion, latitud, longitud, radio_metros, fecha_inicio, activa)
                 VALUES (:u, :desc, :lat, :lon, :radio, CURRENT_TIMESTAMP(6), 1)'
            );
            $stmtInsert->execute([
                ':u' => $idUsuarioCreador,
                ':desc' => $descripcion,
                ':lat' => $latitud,
                ':lon' => $longitud,
                ':radio' => $radioMetros,
            ]);

            $newId = (int) $this->db->lastInsertId();
            $this->db->commit();

            $config = $this->findById($newId);
            if ($config === null) {
                throw new \RuntimeException('No fue posible recuperar la configuración de ubicación recién creada.');
            }

            return $config;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
