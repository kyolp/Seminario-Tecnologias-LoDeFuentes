<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PeriodoEscolar;
use App\Repositories\PeriodoRepository;
use App\Repositories\PeriodoRepositoryInterface;
use PDOException;
use Throwable;

class PeriodoService
{
    private PeriodoRepositoryInterface $periodoRepo;

    public function __construct(?PeriodoRepositoryInterface $periodoRepo = null)
    {
        $this->periodoRepo = $periodoRepo ?? new PeriodoRepository();
    }

    /**
     * @return array<int, PeriodoEscolar>
     */
    public function listarPeriodos(): array
    {
        return $this->periodoRepo->findAll();
    }

    public function obtenerPeriodo(int $id): ?PeriodoEscolar
    {
        return $this->periodoRepo->findById($id);
    }

    public function obtenerPeriodoActivo(): ?PeriodoEscolar
    {
        return $this->periodoRepo->findActivo();
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, id_periodo?: int, message?: string, errors?: array<string, string>}
     */
    public function crearPeriodo(array $input): array
    {
        $errors = [];

        $nombre = trim((string)($input['nombre'] ?? ''));
        $fechaInicio = trim((string)($input['fecha_inicio'] ?? ''));
        $fechaFin = trim((string)($input['fecha_fin'] ?? ''));
        $estado = strtoupper(trim((string)($input['estado'] ?? 'PLANIFICADO')));

        if ($nombre === '') {
            $errors['nombre'] = 'El nombre del período escolar es obligatorio.';
        } elseif (mb_strlen($nombre) > 100) {
            $errors['nombre'] = 'El nombre no debe exceder 100 caracteres.';
        } elseif ($this->periodoRepo->findByNombre($nombre) !== null) {
            $errors['nombre'] = 'Ya existe un período escolar con ese nombre.';
        }

        if ($fechaInicio === '' || !$this->validarFecha($fechaInicio)) {
            $errors['fecha_inicio'] = 'La fecha de inicio es obligatoria y debe tener formato AAAA-MM-DD.';
        }

        if ($fechaFin === '' || !$this->validarFecha($fechaFin)) {
            $errors['fecha_fin'] = 'La fecha de fin es obligatoria y debe tener formato AAAA-MM-DD.';
        }

        if (empty($errors['fecha_inicio']) && empty($errors['fecha_fin'])) {
            if ($fechaFin < $fechaInicio) {
                $errors['fecha_fin'] = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
            }
        }

        if (!in_array($estado, ['PLANIFICADO', 'ACTIVO', 'CERRADO'], true)) {
            $estado = 'PLANIFICADO';
        }

        // Regla: Solo un período activo
        if ($estado === 'ACTIVO') {
            $activoExistente = $this->periodoRepo->findActivo();
            if ($activoExistente !== null) {
                $errors['estado'] = 'Ya existe un período escolar activo (' . $activoExistente->nombre . '). Solo puede existir un período activo simultáneamente.';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Existen errores de validación.'];
        }

        try {
            $id = $this->periodoRepo->create([
                'nombre' => $nombre,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'estado' => $estado,
            ]);

            return [
                'success' => true,
                'id_periodo' => $id,
                'message' => 'Período escolar creado exitosamente.',
            ];
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'uk_periodos_escolares_un_activo') || str_contains($e->getMessage(), 'Duplicate entry')) {
                return [
                    'success' => false,
                    'message' => 'Violación de unicidad: Ya existe un período escolar activo en el sistema.',
                    'errors' => ['estado' => 'Ya existe un período activo en el sistema.'],
                ];
            }
            return ['success' => false, 'message' => 'Error al guardar el período escolar.'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al crear el período: ' . $e->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, message?: string, errors?: array<string, string>}
     */
    public function actualizarPeriodo(int $id, array $input): array
    {
        $periodo = $this->periodoRepo->findById($id);
        if (!$periodo) {
            return ['success' => false, 'message' => 'Período escolar no encontrado.'];
        }

        $errors = [];

        $nombre = trim((string)($input['nombre'] ?? ''));
        $fechaInicio = trim((string)($input['fecha_inicio'] ?? ''));
        $fechaFin = trim((string)($input['fecha_fin'] ?? ''));
        $estado = strtoupper(trim((string)($input['estado'] ?? $periodo->estado)));

        if ($nombre === '') {
            $errors['nombre'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) > 100) {
            $errors['nombre'] = 'El nombre no debe exceder 100 caracteres.';
        } else {
            $existente = $this->periodoRepo->findByNombre($nombre);
            if ($existente && $existente->id_periodo_escolar !== $id) {
                $errors['nombre'] = 'Ya existe otro período escolar con este nombre.';
            }
        }

        if ($fechaInicio === '' || !$this->validarFecha($fechaInicio)) {
            $errors['fecha_inicio'] = 'La fecha de inicio debe tener formato AAAA-MM-DD.';
        }

        if ($fechaFin === '' || !$this->validarFecha($fechaFin)) {
            $errors['fecha_fin'] = 'La fecha de fin debe tener formato AAAA-MM-DD.';
        }

        if (empty($errors['fecha_inicio']) && empty($errors['fecha_fin'])) {
            if ($fechaFin < $fechaInicio) {
                $errors['fecha_fin'] = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
            }
        }

        if (!in_array($estado, ['PLANIFICADO', 'ACTIVO', 'CERRADO'], true)) {
            $estado = $periodo->estado;
        }

        if ($estado === 'ACTIVO') {
            $activoExistente = $this->periodoRepo->findActivo();
            if ($activoExistente !== null && $activoExistente->id_periodo_escolar !== $id) {
                $errors['estado'] = 'Ya existe otro período escolar activo (' . $activoExistente->nombre . ').';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Existen errores de validación.'];
        }

        try {
            $this->periodoRepo->update($id, [
                'nombre' => $nombre,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'estado' => $estado,
            ]);

            return ['success' => true, 'message' => 'Período escolar actualizado exitosamente.'];
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'uk_periodos_escolares_un_activo') || str_contains($e->getMessage(), 'Duplicate entry')) {
                return [
                    'success' => false,
                    'message' => 'Violación de unicidad: Ya existe un período escolar activo en el sistema.',
                    'errors' => ['estado' => 'Ya existe un período activo en el sistema.'],
                ];
            }
            return ['success' => false, 'message' => 'Error al actualizar el período escolar.'];
        }
    }

    public function cambiarEstado(int $id, string $nuevoEstado): array
    {
        $periodo = $this->periodoRepo->findById($id);
        if (!$periodo) {
            return ['success' => false, 'message' => 'Período escolar no encontrado.'];
        }

        $estado = strtoupper(trim($nuevoEstado));
        if (!in_array($estado, ['PLANIFICADO', 'ACTIVO', 'CERRADO'], true)) {
            return ['success' => false, 'message' => 'Estado no válido.'];
        }

        if ($estado === 'ACTIVO') {
            $activoExistente = $this->periodoRepo->findActivo();
            if ($activoExistente !== null && $activoExistente->id_periodo_escolar !== $id) {
                return [
                    'success' => false,
                    'message' => 'Operación rechazada: Ya existe un período activo (' . $activoExistente->nombre . '). Debe cerrar el período activo actual antes de activar uno nuevo.',
                ];
            }
        }

        try {
            $this->periodoRepo->updateEstado($id, $estado);
            return ['success' => true, 'message' => "Estado del período modificado a {$estado}."];
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'uk_periodos_escolares_un_activo') || str_contains($e->getMessage(), 'Duplicate entry')) {
                return [
                    'success' => false,
                    'message' => 'Operación rechazada: La regla de base de datos prohíbe tener más de un período activo simultáneamente.',
                ];
            }
            return ['success' => false, 'message' => 'Error de base de datos al cambiar estado.'];
        }
    }

    public function eliminarPeriodo(int $id): array
    {
        $periodo = $this->periodoRepo->findById($id);
        if (!$periodo) {
            return ['success' => false, 'message' => 'Período no encontrado.'];
        }

        if ($this->periodoRepo->hasGradosPeriodos($id)) {
            return [
                'success' => false,
                'message' => 'No es posible eliminar el período escolar porque contiene grados configurados. Se recomienda cambiar su estado a CERRADO.',
            ];
        }

        try {
            $this->periodoRepo->delete($id);
            return ['success' => true, 'message' => 'Período escolar eliminado correctamente.'];
        } catch (Throwable) {
            return ['success' => false, 'message' => 'No se puede eliminar el período escolar debido a dependencias en la base de datos.'];
        }
    }

    private function validarFecha(string $fecha): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }
}
