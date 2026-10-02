<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ConfiguracionUbicacion;
use App\Repositories\UbicacionRepository;
use App\Repositories\UbicacionRepositoryInterface;
use DomainException;
use InvalidArgumentException;

/**
 * Servicio de lógica de negocio para la administración de ubicación geográfica.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 8 — RF-18).
 */
class UbicacionService
{
    private UbicacionRepositoryInterface $repo;

    public function __construct(?UbicacionRepositoryInterface $repo = null)
    {
        $this->repo = $repo ?? new UbicacionRepository();
    }

    /**
     * Obtener la configuración activa actual.
     */
    public function obtenerConfiguracionActiva(): ?ConfiguracionUbicacion
    {
        return $this->repo->findConfiguracionActiva();
    }

    /**
     * Obtener todas las configuraciones ordenadas por histórico.
     *
     * @return array<int, ConfiguracionUbicacion>
     */
    public function obtenerHistorialConfiguraciones(): array
    {
        return $this->repo->findAll();
    }

    /**
     * Validar y activar una nueva configuración institucional de ubicación y radio.
     *
     * @param int $idUsuarioCreador
     * @param array{
     *     descripcion?: string,
     *     latitud?: mixed,
     *     longitud?: mixed,
     *     radio_metros?: mixed
     * } $data
     * @throws InvalidArgumentException Si los datos ingresados no cumplen con las validaciones geográficas.
     */
    public function configurarNuevaUbicacion(int $idUsuarioCreador, array $data): ConfiguracionUbicacion
    {
        $descripcion = trim((string) ($data['descripcion'] ?? ''));
        if ($descripcion === '') {
            throw new InvalidArgumentException('La descripción de la ubicación es obligatoria.');
        }
        if (mb_strlen($descripcion) > 150) {
            throw new InvalidArgumentException('La descripción no puede exceder los 150 caracteres.');
        }

        // Validación estricta de latitud (-90 a 90)
        if (!isset($data['latitud']) || !is_numeric($data['latitud'])) {
            throw new InvalidArgumentException('La latitud debe ser un valor numérico válido.');
        }
        $latitud = (float) $data['latitud'];
        if ($latitud < -90.0 || $latitud > 90.0) {
            throw new InvalidArgumentException('La latitud debe estar comprendida entre -90.0 y 90.0 grados.');
        }

        // Validación estricta de longitud (-180 a 180)
        if (!isset($data['longitud']) || !is_numeric($data['longitud'])) {
            throw new InvalidArgumentException('La longitud debe ser un valor numérico válido.');
        }
        $longitud = (float) $data['longitud'];
        if ($longitud < -180.0 || $longitud > 180.0) {
            throw new InvalidArgumentException('La longitud debe estar comprendida entre -180.0 y 180.0 grados.');
        }

        // Validación de radio en metros (> 0)
        if (!isset($data['radio_metros']) || !is_numeric($data['radio_metros'])) {
            throw new InvalidArgumentException('El radio permitido debe ser un valor numérico válido.');
        }
        $radioMetros = (float) $data['radio_metros'];
        if ($radioMetros <= 0.0) {
            throw new InvalidArgumentException('El radio permitido debe ser estrictamente mayor a 0 metros.');
        }

        return $this->repo->crearConfiguracionActiva(
            $idUsuarioCreador,
            $descripcion,
            $latitud,
            $longitud,
            $radioMetros
        );
    }
}
