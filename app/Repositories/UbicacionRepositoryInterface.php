<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ConfiguracionUbicacion;

/**
 * Contrato de persistencia para la configuración de ubicación geográfica.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 8 — RF-18).
 */
interface UbicacionRepositoryInterface
{
    /**
     * Obtener la configuración de ubicación actualmente activa.
     */
    public function findConfiguracionActiva(): ?ConfiguracionUbicacion;

    /**
     * Buscar una configuración por su identificador.
     */
    public function findById(int $id): ?ConfiguracionUbicacion;

    /**
     * Obtener todas las configuraciones ordenadas por fecha (histórico).
     *
     * @return array<int, ConfiguracionUbicacion>
     */
    public function findAll(): array;

    /**
     * Crear y activar una nueva configuración institucional, desactivando transaccionalmente
     * cualquier configuración activa previa para mantener la unicidad condicional de BD.
     */
    public function crearConfiguracionActiva(
        int $idUsuarioCreador,
        string $descripcion,
        float $latitud,
        float $longitud,
        float $radioMetros
    ): ConfiguracionUbicacion;
}
