<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Seccion;
use App\Repositories\GradoPeriodoRepository;
use App\Repositories\GradoPeriodoRepositoryInterface;
use App\Repositories\SeccionRepository;
use App\Repositories\SeccionRepositoryInterface;
use PDOException;
use Throwable;

class SeccionService
{
    private SeccionRepositoryInterface $seccionRepo;
    private GradoPeriodoRepositoryInterface $gradoPeriodoRepo;

    public function __construct(
        ?SeccionRepositoryInterface $seccionRepo = null,
        ?GradoPeriodoRepositoryInterface $gradoPeriodoRepo = null
    ) {
        $this->seccionRepo = $seccionRepo ?? new SeccionRepository();
        $this->gradoPeriodoRepo = $gradoPeriodoRepo ?? new GradoPeriodoRepository();
    }

    /**
     * @return array<int, Seccion>
     */
    public function listarSecciones(?int $gradoPeriodoId = null): array
    {
        return $this->seccionRepo->findAll($gradoPeriodoId);
    }

    public function obtenerSeccion(int $id): ?Seccion
    {
        return $this->seccionRepo->findById($id);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, id_seccion?: int, message?: string, errors?: array<string, string>}
     */
    public function crearSeccion(array $input): array
    {
        $errors = [];

        $idGradoPeriodo = (int)($input['id_grado_periodo'] ?? 0);
        $codigo = strtoupper(trim((string)($input['codigo'] ?? '')));
        $nombre = !empty($input['nombre']) ? trim((string)$input['nombre']) : null;
        $habilitada = isset($input['habilitada']) ? (int)$input['habilitada'] : 1;

        // Validación de FK
        if ($idGradoPeriodo <= 0 || $this->gradoPeriodoRepo->findById($idGradoPeriodo) === null) {
            $errors['id_grado_periodo'] = 'La asociación de grado y período seleccionada no existe en el sistema.';
        }

        // Validación de código
        if ($codigo === '') {
            $errors['codigo'] = 'El código o letra de sección es obligatorio.';
        } elseif (mb_strlen($codigo) > 20) {
            $errors['codigo'] = 'El código no debe exceder 20 caracteres.';
        } elseif ($idGradoPeriodo > 0 && $this->seccionRepo->findByGradoPeriodoAndCodigo($idGradoPeriodo, $codigo) !== null) {
            $errors['codigo'] = "La sección '{$codigo}' ya existe para este grado en este período.";
        }

        if ($nombre !== null && mb_strlen($nombre) > 100) {
            $errors['nombre'] = 'El nombre descriptivo no debe exceder 100 caracteres.';
        }

        // Validación estricta de Capacidad: obligatoria, numérica, entera, estrictamente mayor a 0
        $rawCapacidad = $input['capacidad'] ?? null;
        if ($rawCapacidad === null || $rawCapacidad === '' || !is_numeric($rawCapacidad)) {
            $errors['capacidad'] = 'La capacidad de la sección es obligatoria y debe ser un valor numérico entero.';
        } else {
            $capacidadInt = (int)$rawCapacidad;
            if ((string)$capacidadInt !== (string)trim((string)$rawCapacidad) && (float)$rawCapacidad != $capacidadInt) {
                $errors['capacidad'] = 'La capacidad debe ser un número entero sin decimales.';
            } elseif ($capacidadInt <= 0) {
                $errors['capacidad'] = 'La capacidad de la sección debe ser un número entero estrictamente mayor a 0.';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Existen errores de validación.'];
        }

        try {
            $id = $this->seccionRepo->create([
                'id_grado_periodo' => $idGradoPeriodo,
                'codigo' => $codigo,
                'nombre' => $nombre,
                'capacidad' => (int)$rawCapacidad,
                'habilitada' => $habilitada === 1 ? 1 : 0,
            ]);

            return [
                'success' => true,
                'id_seccion' => $id,
                'message' => 'Sección creada exitosamente.',
            ];
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'uk_secciones_contexto')) {
                return [
                    'success' => false,
                    'message' => "Ya existe una sección con el código '{$codigo}' en este grado y período.",
                    'errors' => ['codigo' => 'Código de sección duplicado en este grado.'],
                ];
            }
            return ['success' => false, 'message' => 'Error de base de datos al guardar la sección.'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al crear la sección: ' . $e->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, message?: string, errors?: array<string, string>}
     */
    public function actualizarSeccion(int $id, array $input): array
    {
        $seccion = $this->seccionRepo->findById($id);
        if (!$seccion) {
            return ['success' => false, 'message' => 'Sección no encontrada.'];
        }

        $errors = [];

        $codigo = strtoupper(trim((string)($input['codigo'] ?? '')));
        $nombre = !empty($input['nombre']) ? trim((string)$input['nombre']) : null;
        $habilitada = isset($input['habilitada']) ? (int)$input['habilitada'] : $seccion->habilitada;

        if ($codigo === '') {
            $errors['codigo'] = 'El código de sección es obligatorio.';
        } elseif (mb_strlen($codigo) > 20) {
            $errors['codigo'] = 'El código no debe exceder 20 caracteres.';
        } else {
            $existente = $this->seccionRepo->findByGradoPeriodoAndCodigo($seccion->id_grado_periodo, $codigo);
            if ($existente && $existente->id_seccion !== $id) {
                $errors['codigo'] = "La sección '{$codigo}' ya existe en este grado y período.";
            }
        }

        if ($nombre !== null && mb_strlen($nombre) > 100) {
            $errors['nombre'] = 'El nombre no debe exceder 100 caracteres.';
        }

        $rawCapacidad = $input['capacidad'] ?? null;
        if ($rawCapacidad === null || $rawCapacidad === '' || !is_numeric($rawCapacidad)) {
            $errors['capacidad'] = 'La capacidad de la sección es obligatoria y numérica.';
        } else {
            $capacidadInt = (int)$rawCapacidad;
            if ($capacidadInt <= 0) {
                $errors['capacidad'] = 'La capacidad de la sección debe ser un número entero estrictamente mayor a 0.';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Existen errores de validación.'];
        }

        try {
            $this->seccionRepo->update($id, [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'capacidad' => (int)$rawCapacidad,
                'habilitada' => $habilitada === 1 ? 1 : 0,
            ]);

            return ['success' => true, 'message' => 'Sección actualizada exitosamente.'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al actualizar sección: ' . $e->getMessage()];
        }
    }

    public function cambiarHabilitada(int $id, int $habilitada): array
    {
        $seccion = $this->seccionRepo->findById($id);
        if (!$seccion) {
            return ['success' => false, 'message' => 'Sección no encontrada.'];
        }

        $this->seccionRepo->updateHabilitada($id, $habilitada === 1 ? 1 : 0);
        $txt = $habilitada === 1 ? 'habilitada' : 'deshabilitada';
        return ['success' => true, 'message' => "Sección {$txt} exitosamente."];
    }

    public function eliminarSeccion(int $id): array
    {
        $seccion = $this->seccionRepo->findById($id);
        if (!$seccion) {
            return ['success' => false, 'message' => 'Sección no encontrada.'];
        }

        if ($this->seccionRepo->hasAsignaciones($id) || $this->seccionRepo->hasInscripciones($id)) {
            return [
                'success' => false,
                'message' => 'No es posible eliminar la sección porque cuenta con asignaciones o inscripciones vinculadas. Deshabilítela en su lugar.',
            ];
        }

        try {
            $this->seccionRepo->delete($id);
            return ['success' => true, 'message' => 'Sección eliminada correctamente.'];
        } catch (Throwable) {
            return ['success' => false, 'message' => 'No se puede eliminar la sección debido a restricciones de integridad referencial.'];
        }
    }
}
