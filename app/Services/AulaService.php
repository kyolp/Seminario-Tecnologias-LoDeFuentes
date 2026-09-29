<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Aula;
use App\Repositories\AulaRepository;
use App\Repositories\AulaRepositoryInterface;
use Throwable;

class AulaService
{
    private AulaRepositoryInterface $aulaRepo;

    public function __construct(?AulaRepositoryInterface $aulaRepo = null)
    {
        $this->aulaRepo = $aulaRepo ?? new AulaRepository();
    }

    /**
     * @return array<int, Aula>
     */
    public function listarAulas(): array
    {
        return $this->aulaRepo->findAll();
    }

    public function obtenerAula(int $id): ?Aula
    {
        return $this->aulaRepo->findById($id);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, id_aula?: int, message?: string, errors?: array<string, string>}
     */
    public function crearAula(array $input): array
    {
        $errors = [];

        $codigo = trim((string)($input['codigo'] ?? ''));
        $nombre = !empty($input['nombre']) ? trim((string)$input['nombre']) : null;
        $ubicacion = !empty($input['ubicacion_referencia']) ? trim((string)$input['ubicacion_referencia']) : null;
        $activa = isset($input['activa']) ? (int)$input['activa'] : 1;

        if ($codigo === '') {
            $errors['codigo'] = 'El código del aula es obligatorio.';
        } elseif (mb_strlen($codigo) > 30) {
            $errors['codigo'] = 'El código no debe exceder 30 caracteres.';
        } elseif ($this->aulaRepo->findByCodigo($codigo) !== null) {
            $errors['codigo'] = 'Ya existe un aula con ese código.';
        }

        if ($nombre !== null && mb_strlen($nombre) > 100) {
            $errors['nombre'] = 'El nombre no debe exceder 100 caracteres.';
        }

        if ($ubicacion !== null && mb_strlen($ubicacion) > 255) {
            $errors['ubicacion_referencia'] = 'La ubicación no debe exceder 255 caracteres.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Existen errores de validación.'];
        }

        try {
            $id = $this->aulaRepo->create([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'ubicacion_referencia' => $ubicacion,
                'activa' => $activa === 1 ? 1 : 0,
            ]);

            return [
                'success' => true,
                'id_aula' => $id,
                'message' => 'Aula registrada exitosamente.',
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al crear el aula: ' . $e->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, message?: string, errors?: array<string, string>}
     */
    public function actualizarAula(int $id, array $input): array
    {
        $aula = $this->aulaRepo->findById($id);
        if (!$aula) {
            return ['success' => false, 'message' => 'Aula no encontrada.'];
        }

        $errors = [];

        $codigo = trim((string)($input['codigo'] ?? ''));
        $nombre = !empty($input['nombre']) ? trim((string)$input['nombre']) : null;
        $ubicacion = !empty($input['ubicacion_referencia']) ? trim((string)$input['ubicacion_referencia']) : null;
        $activa = isset($input['activa']) ? (int)$input['activa'] : $aula->activa;

        if ($codigo === '') {
            $errors['codigo'] = 'El código del aula es obligatorio.';
        } elseif (mb_strlen($codigo) > 30) {
            $errors['codigo'] = 'El código no debe exceder 30 caracteres.';
        } else {
            $existente = $this->aulaRepo->findByCodigo($codigo);
            if ($existente && $existente->id_aula !== $id) {
                $errors['codigo'] = 'Ya existe otra aula con ese código.';
            }
        }

        if ($nombre !== null && mb_strlen($nombre) > 100) {
            $errors['nombre'] = 'El nombre no debe exceder 100 caracteres.';
        }

        if ($ubicacion !== null && mb_strlen($ubicacion) > 255) {
            $errors['ubicacion_referencia'] = 'La ubicación no debe exceder 255 caracteres.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Existen errores de validación.'];
        }

        try {
            $this->aulaRepo->update($id, [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'ubicacion_referencia' => $ubicacion,
                'activa' => $activa === 1 ? 1 : 0,
            ]);

            return ['success' => true, 'message' => 'Aula actualizada exitosamente.'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al actualizar aula: ' . $e->getMessage()];
        }
    }

    public function cambiarActiva(int $id, int $activa): array
    {
        $aula = $this->aulaRepo->findById($id);
        if (!$aula) {
            return ['success' => false, 'message' => 'Aula no encontrada.'];
        }

        $this->aulaRepo->updateActiva($id, $activa === 1 ? 1 : 0);
        $txt = $activa === 1 ? 'activada' : 'desactivada';
        return ['success' => true, 'message' => "Aula {$txt} exitosamente."];
    }

    public function eliminarAula(int $id): array
    {
        $aula = $this->aulaRepo->findById($id);
        if (!$aula) {
            return ['success' => false, 'message' => 'Aula no encontrada.'];
        }

        if ($this->aulaRepo->hasAsignaciones($id)) {
            return [
                'success' => false,
                'message' => 'No es posible eliminar el aula porque cuenta con asignaciones históricas o vigentes. Desactívela en su lugar.',
            ];
        }

        try {
            $this->aulaRepo->delete($id);
            return ['success' => true, 'message' => 'Aula eliminada correctamente.'];
        } catch (Throwable) {
            return ['success' => false, 'message' => 'No se puede eliminar el aula debido a restricciones referenciales.'];
        }
    }
}
