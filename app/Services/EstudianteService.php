<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Estudiante;
use App\Repositories\EstudianteRepository;
use App\Repositories\EstudianteRepositoryInterface;
use Throwable;

class EstudianteService
{
    private EstudianteRepositoryInterface $estudianteRepo;

    public function __construct(?EstudianteRepositoryInterface $estudianteRepo = null)
    {
        $this->estudianteRepo = $estudianteRepo ?? new EstudianteRepository();
    }

    /**
     * @return array<int, Estudiante>
     */
    public function listarEstudiantes(?string $search = null): array
    {
        return $this->estudianteRepo->findAll($search);
    }

    public function obtenerEstudiante(int $id): ?Estudiante
    {
        return $this->estudianteRepo->findById($id);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, id_estudiante?: int, message?: string, errors?: array<string, string>}
     */
    public function crearEstudiante(array $input): array
    {
        $errors = [];

        $nombres = trim((string)($input['nombres'] ?? ''));
        $apellidos = trim((string)($input['apellidos'] ?? ''));
        $codigoEstudiante = !empty($input['codigo_estudiante']) ? trim((string)$input['codigo_estudiante']) : null;
        $fechaNacimiento = !empty($input['fecha_nacimiento']) ? trim((string)$input['fecha_nacimiento']) : null;
        $estado = strtoupper(trim((string)($input['estado'] ?? 'ACTIVO')));

        if ($nombres === '') {
            $errors['nombres'] = 'Los nombres del estudiante son obligatorios.';
        } elseif (mb_strlen($nombres) > 100) {
            $errors['nombres'] = 'Los nombres no deben exceder 100 caracteres.';
        }

        if ($apellidos === '') {
            $errors['apellidos'] = 'Los apellidos del estudiante son obligatorios.';
        } elseif (mb_strlen($apellidos) > 100) {
            $errors['apellidos'] = 'Los apellidos no deben exceder 100 caracteres.';
        }

        if ($codigoEstudiante !== null) {
            if (mb_strlen($codigoEstudiante) > 30) {
                $errors['codigo_estudiante'] = 'El código no debe exceder 30 caracteres.';
            } elseif ($this->estudianteRepo->findByCodigo($codigoEstudiante) !== null) {
                $errors['codigo_estudiante'] = 'El código de estudiante ya se encuentra registrado.';
            }
        }

        if ($fechaNacimiento !== null && !$this->validarFecha($fechaNacimiento)) {
            $errors['fecha_nacimiento'] = 'La fecha de nacimiento debe tener formato AAAA-MM-DD.';
        }

        if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
            $estado = 'ACTIVO';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Existen errores de validación.'];
        }

        try {
            $id = $this->estudianteRepo->create([
                'codigo_estudiante' => $codigoEstudiante,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'fecha_nacimiento' => $fechaNacimiento,
                'estado' => $estado,
            ]);

            return [
                'success' => true,
                'id_estudiante' => $id,
                'message' => 'Estudiante registrado exitosamente.',
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al guardar el estudiante: ' . $e->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, message?: string, errors?: array<string, string>}
     */
    public function actualizarEstudiante(int $id, array $input): array
    {
        $estudiante = $this->estudianteRepo->findById($id);
        if (!$estudiante) {
            return ['success' => false, 'message' => 'Estudiante no encontrado.'];
        }

        $errors = [];

        $nombres = trim((string)($input['nombres'] ?? ''));
        $apellidos = trim((string)($input['apellidos'] ?? ''));
        $codigoEstudiante = !empty($input['codigo_estudiante']) ? trim((string)$input['codigo_estudiante']) : null;
        $fechaNacimiento = !empty($input['fecha_nacimiento']) ? trim((string)$input['fecha_nacimiento']) : null;
        $estado = strtoupper(trim((string)($input['estado'] ?? $estudiante->estado)));

        if ($nombres === '') {
            $errors['nombres'] = 'Los nombres son obligatorios.';
        } elseif (mb_strlen($nombres) > 100) {
            $errors['nombres'] = 'Los nombres no deben exceder 100 caracteres.';
        }

        if ($apellidos === '') {
            $errors['apellidos'] = 'Los apellidos son obligatorios.';
        } elseif (mb_strlen($apellidos) > 100) {
            $errors['apellidos'] = 'Los apellidos no deben exceder 100 caracteres.';
        }

        if ($codigoEstudiante !== null) {
            if (mb_strlen($codigoEstudiante) > 30) {
                $errors['codigo_estudiante'] = 'El código no debe exceder 30 caracteres.';
            } else {
                $existente = $this->estudianteRepo->findByCodigo($codigoEstudiante);
                if ($existente && $existente->id_estudiante !== $id) {
                    $errors['codigo_estudiante'] = 'El código ya pertenece a otro estudiante.';
                }
            }
        }

        if ($fechaNacimiento !== null && !$this->validarFecha($fechaNacimiento)) {
            $errors['fecha_nacimiento'] = 'La fecha de nacimiento debe tener formato AAAA-MM-DD.';
        }

        if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
            $estado = 'ACTIVO';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Existen errores de validación.'];
        }

        try {
            $this->estudianteRepo->update($id, [
                'codigo_estudiante' => $codigoEstudiante,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'fecha_nacimiento' => $fechaNacimiento,
                'estado' => $estado,
            ]);

            return ['success' => true, 'message' => 'Estudiante actualizado exitosamente.'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al actualizar estudiante: ' . $e->getMessage()];
        }
    }

    public function cambiarEstado(int $id, string $nuevoEstado): array
    {
        $estudiante = $this->estudianteRepo->findById($id);
        if (!$estudiante) {
            return ['success' => false, 'message' => 'Estudiante no encontrado.'];
        }

        $estado = strtoupper($nuevoEstado) === 'ACTIVO' ? 'ACTIVO' : 'INACTIVO';

        $this->estudianteRepo->updateEstado($id, $estado);

        return ['success' => true, 'message' => "Estado del estudiante modificado a {$estado}."];
    }

    public function eliminarEstudiante(int $id): array
    {
        $estudiante = $this->estudianteRepo->findById($id);
        if (!$estudiante) {
            return ['success' => false, 'message' => 'Estudiante no encontrado.'];
        }

        if ($this->estudianteRepo->hasInscripciones($id)) {
            return [
                'success' => false,
                'message' => 'No es posible eliminar el estudiante porque cuenta con historial de inscripciones. Se recomienda desactivarlo.',
            ];
        }

        try {
            $this->estudianteRepo->delete($id);
            return ['success' => true, 'message' => 'Estudiante eliminado correctamente.'];
        } catch (Throwable) {
            return ['success' => false, 'message' => 'No se puede eliminar el estudiante debido a restricciones de integridad referencial.'];
        }
    }

    private function validarFecha(string $fecha): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }
}
