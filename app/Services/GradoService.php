<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Grado;
use App\Models\GradoPeriodo;
use App\Repositories\GradoPeriodoRepository;
use App\Repositories\GradoPeriodoRepositoryInterface;
use App\Repositories\GradoRepository;
use App\Repositories\GradoRepositoryInterface;
use App\Repositories\PeriodoRepository;
use App\Repositories\PeriodoRepositoryInterface;
use PDOException;
use Throwable;

class GradoService
{
    private GradoRepositoryInterface $gradoRepo;
    private GradoPeriodoRepositoryInterface $gradoPeriodoRepo;
    private PeriodoRepositoryInterface $periodoRepo;

    public function __construct(
        ?GradoRepositoryInterface $gradoRepo = null,
        ?GradoPeriodoRepositoryInterface $gradoPeriodoRepo = null,
        ?PeriodoRepositoryInterface $periodoRepo = null
    ) {
        $this->gradoRepo = $gradoRepo ?? new GradoRepository();
        $this->gradoPeriodoRepo = $gradoPeriodoRepo ?? new GradoPeriodoRepository();
        $this->periodoRepo = $periodoRepo ?? new PeriodoRepository();
    }

    /**
     * @return array<int, Grado>
     */
    public function listarGrados(): array
    {
        return $this->gradoRepo->findAll();
    }

    public function obtenerGrado(int $id): ?Grado
    {
        return $this->gradoRepo->findById($id);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, id_grado?: int, message?: string, errors?: array<string, string>}
     */
    public function crearGrado(array $input): array
    {
        $errors = [];

        $codigo = trim((string)($input['codigo'] ?? ''));
        $nombre = trim((string)($input['nombre'] ?? ''));
        $orden = (int)($input['orden'] ?? 0);
        $activo = isset($input['activo']) ? (int)$input['activo'] : 1;

        if ($codigo === '') {
            $errors['codigo'] = 'El código del grado es obligatorio.';
        } elseif (mb_strlen($codigo) > 30) {
            $errors['codigo'] = 'El código no debe exceder 30 caracteres.';
        } elseif ($this->gradoRepo->findByCodigo($codigo) !== null) {
            $errors['codigo'] = 'Ya existe un grado con ese código.';
        }

        if ($nombre === '') {
            $errors['nombre'] = 'El nombre del grado es obligatorio.';
        } elseif (mb_strlen($nombre) > 100) {
            $errors['nombre'] = 'El nombre no debe exceder 100 caracteres.';
        } elseif ($this->gradoRepo->findByNombre($nombre) !== null) {
            $errors['nombre'] = 'Ya existe un grado con ese nombre.';
        }

        if ($orden <= 0) {
            $errors['orden'] = 'El número de orden debe ser un entero estrictamente positivo (mayor a 0).';
        } elseif ($this->gradoRepo->findByOrden($orden) !== null) {
            $errors['orden'] = 'Ya existe un grado con ese número de orden jerárquico.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Existen errores de validación.'];
        }

        try {
            $id = $this->gradoRepo->create([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'orden' => $orden,
                'activo' => $activo === 1 ? 1 : 0,
            ]);

            return [
                'success' => true,
                'id_grado' => $id,
                'message' => 'Grado creado exitosamente.',
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al registrar el grado: ' . $e->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, message?: string, errors?: array<string, string>}
     */
    public function actualizarGrado(int $id, array $input): array
    {
        $grado = $this->gradoRepo->findById($id);
        if (!$grado) {
            return ['success' => false, 'message' => 'Grado no encontrado.'];
        }

        $errors = [];

        $codigo = trim((string)($input['codigo'] ?? ''));
        $nombre = trim((string)($input['nombre'] ?? ''));
        $orden = (int)($input['orden'] ?? 0);
        $activo = isset($input['activo']) ? (int)$input['activo'] : $grado->activo;

        if ($codigo === '') {
            $errors['codigo'] = 'El código es obligatorio.';
        } elseif (mb_strlen($codigo) > 30) {
            $errors['codigo'] = 'El código no debe exceder 30 caracteres.';
        } else {
            $existente = $this->gradoRepo->findByCodigo($codigo);
            if ($existente && $existente->id_grado !== $id) {
                $errors['codigo'] = 'Ya existe otro grado con este código.';
            }
        }

        if ($nombre === '') {
            $errors['nombre'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) > 100) {
            $errors['nombre'] = 'El nombre no debe exceder 100 caracteres.';
        } else {
            $existente = $this->gradoRepo->findByNombre($nombre);
            if ($existente && $existente->id_grado !== $id) {
                $errors['nombre'] = 'Ya existe otro grado con este nombre.';
            }
        }

        if ($orden <= 0) {
            $errors['orden'] = 'El orden debe ser mayor a 0.';
        } else {
            $existente = $this->gradoRepo->findByOrden($orden);
            if ($existente && $existente->id_grado !== $id) {
                $errors['orden'] = 'Ya existe otro grado con este orden.';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Existen errores de validación.'];
        }

        try {
            $this->gradoRepo->update($id, [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'orden' => $orden,
                'activo' => $activo === 1 ? 1 : 0,
            ]);

            return ['success' => true, 'message' => 'Grado actualizado exitosamente.'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error al actualizar grado: ' . $e->getMessage()];
        }
    }

    public function cambiarActivo(int $id, int $activo): array
    {
        $grado = $this->gradoRepo->findById($id);
        if (!$grado) {
            return ['success' => false, 'message' => 'Grado no encontrado.'];
        }

        $this->gradoRepo->updateActivo($id, $activo === 1 ? 1 : 0);
        $estadoTxt = $activo === 1 ? 'activado' : 'desactivado';
        return ['success' => true, 'message' => "Grado {$estadoTxt} exitosamente."];
    }

    public function eliminarGrado(int $id): array
    {
        $grado = $this->gradoRepo->findById($id);
        if (!$grado) {
            return ['success' => false, 'message' => 'Grado no encontrado.'];
        }

        if ($this->gradoRepo->hasGradosPeriodos($id)) {
            return [
                'success' => false,
                'message' => 'No es posible eliminar el grado porque se encuentra asociado a períodos escolares. Desactívelo en su lugar.',
            ];
        }

        try {
            $this->gradoRepo->delete($id);
            return ['success' => true, 'message' => 'Grado eliminado correctamente.'];
        } catch (Throwable) {
            return ['success' => false, 'message' => 'No se puede eliminar el grado debido a restricciones relacionales.'];
        }
    }

    // --- GRADOS POR PERÍODO ---

    /**
     * @return array<int, GradoPeriodo>
     */
    public function listarGradosPeriodos(?int $periodoId = null): array
    {
        return $this->gradoPeriodoRepo->findAll($periodoId);
    }

    public function obtenerGradoPeriodo(int $id): ?GradoPeriodo
    {
        return $this->gradoPeriodoRepo->findById($id);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, id_grado_periodo?: int, message?: string, errors?: array<string, string>}
     */
    public function asociarGradoPeriodo(array $input): array
    {
        $errors = [];

        $idPeriodo = (int)($input['id_periodo_escolar'] ?? 0);
        $idGrado = (int)($input['id_grado'] ?? 0);
        $habilitado = isset($input['habilitado']) ? (int)$input['habilitado'] : 1;

        if ($idPeriodo <= 0 || $this->periodoRepo->findById($idPeriodo) === null) {
            $errors['id_periodo_escolar'] = 'El período escolar seleccionado no existe en el sistema.';
        }

        if ($idGrado <= 0 || $this->gradoRepo->findById($idGrado) === null) {
            $errors['id_grado'] = 'El grado seleccionado no existe en el sistema.';
        }

        if (empty($errors)) {
            $existente = $this->gradoPeriodoRepo->findByPeriodoAndGrado($idPeriodo, $idGrado);
            if ($existente !== null) {
                $errors['duplicado'] = 'El grado ya se encuentra configurado para este período escolar.';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'message' => $errors['duplicado'] ?? 'Error de validación o clave foránea inexistente.'];
        }

        try {
            $id = $this->gradoPeriodoRepo->create([
                'id_periodo_escolar' => $idPeriodo,
                'id_grado' => $idGrado,
                'habilitado' => $habilitado === 1 ? 1 : 0,
            ]);

            return [
                'success' => true,
                'id_grado_periodo' => $id,
                'message' => 'Grado asociado al período escolar exitosamente.',
            ];
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'uk_grados_periodos')) {
                return [
                    'success' => false,
                    'message' => 'Duplicado rechazado: Este grado ya está asociado al período.',
                    'errors' => ['duplicado' => 'Este grado ya está asociado al período.'],
                ];
            }
            return ['success' => false, 'message' => 'Error relacional al asociar grado y período.'];
        }
    }

    public function cambiarHabilitadoGradoPeriodo(int $id, int $habilitado): array
    {
        $gp = $this->gradoPeriodoRepo->findById($id);
        if (!$gp) {
            return ['success' => false, 'message' => 'Configuración de grado por período no encontrada.'];
        }

        $this->gradoPeriodoRepo->updateHabilitado($id, $habilitado === 1 ? 1 : 0);
        $txt = $habilitado === 1 ? 'habilitado' : 'deshabilitado';
        return ['success' => true, 'message' => "Grado {$txt} en el período."];
    }

    public function eliminarGradoPeriodo(int $id): array
    {
        $gp = $this->gradoPeriodoRepo->findById($id);
        if (!$gp) {
            return ['success' => false, 'message' => 'Configuración no encontrada.'];
        }

        if ($this->gradoPeriodoRepo->hasSecciones($id)) {
            return [
                'success' => false,
                'message' => 'No es posible remover el grado del período porque contiene secciones creadas. Deshabilítelo en su lugar.',
            ];
        }

        try {
            $this->gradoPeriodoRepo->delete($id);
            return ['success' => true, 'message' => 'Grado removido del período escolar.'];
        } catch (Throwable) {
            return ['success' => false, 'message' => 'No se puede remover el grado por restricciones referenciales.'];
        }
    }
}
