<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\AsignacionDocente;
use App\Repositories\AsignacionRepository;
use App\Repositories\AsignacionRepositoryInterface;
use App\Repositories\AulaRepository;
use App\Repositories\AulaRepositoryInterface;
use App\Repositories\DocenteRepository;
use App\Repositories\DocenteRepositoryInterface;
use App\Repositories\GradoPeriodoRepository;
use App\Repositories\GradoPeriodoRepositoryInterface;
use App\Repositories\PeriodoRepository;
use App\Repositories\PeriodoRepositoryInterface;
use App\Repositories\SeccionRepository;
use App\Repositories\SeccionRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\UserRepositoryInterface;
use PDO;
use PDOException;
use Throwable;

class AsignacionService
{
    private PDO $db;
    private AsignacionRepositoryInterface $asignacionRepo;
    private DocenteRepositoryInterface $docenteRepo;
    private SeccionRepositoryInterface $seccionRepo;
    private AulaRepositoryInterface $aulaRepo;
    private PeriodoRepositoryInterface $periodoRepo;
    private GradoPeriodoRepositoryInterface $gradoPeriodoRepo;
    private UserRepositoryInterface $userRepo;

    public function __construct(
        ?PDO $db = null,
        ?AsignacionRepositoryInterface $asignacionRepo = null,
        ?DocenteRepositoryInterface $docenteRepo = null,
        ?SeccionRepositoryInterface $seccionRepo = null,
        ?AulaRepositoryInterface $aulaRepo = null,
        ?PeriodoRepositoryInterface $periodoRepo = null,
        ?GradoPeriodoRepositoryInterface $gradoPeriodoRepo = null,
        ?UserRepositoryInterface $userRepo = null
    ) {
        $this->db = $db ?? Database::getConnection();
        $this->asignacionRepo = $asignacionRepo ?? new AsignacionRepository($this->db);
        $this->docenteRepo = $docenteRepo ?? new DocenteRepository($this->db);
        $this->seccionRepo = $seccionRepo ?? new SeccionRepository($this->db);
        $this->aulaRepo = $aulaRepo ?? new AulaRepository($this->db);
        $this->periodoRepo = $periodoRepo ?? new PeriodoRepository($this->db);
        $this->gradoPeriodoRepo = $gradoPeriodoRepo ?? new GradoPeriodoRepository($this->db);
        $this->userRepo = $userRepo ?? new UserRepository($this->db);
    }

    /**
     * Listar asignaciones con filtros opcionales.
     *
     * @param array<string, mixed> $filters
     * @return array<AsignacionDocente>
     */
    public function listarAsignaciones(array $filters = []): array
    {
        return $this->asignacionRepo->findAll($filters);
    }

    /**
     * Obtener asignación por ID.
     */
    public function obtenerAsignacion(int $id): ?AsignacionDocente
    {
        return $this->asignacionRepo->findById($id);
    }

    /**
     * Crear una nueva asignación docente con validaciones de negocio e integridad en cascada.
     *
     * @param array<string, mixed> $input
     * @return array{success: bool, id_asignacion_docente?: int, message: string, errors?: array<string, string>}
     */
    public function crearAsignacion(array $input, int $idUsuarioCreador): array
    {
        $errors = [];

        $idDocente = (int)($input['id_docente'] ?? 0);
        $idSeccion = (int)($input['id_seccion'] ?? 0);
        $idAula = (int)($input['id_aula'] ?? 0);
        $fechaInicio = trim((string)($input['fecha_inicio'] ?? ''));
        $fechaFin = !empty($input['fecha_fin']) ? trim((string)$input['fecha_fin']) : null;
        $estado = strtoupper(trim((string)($input['estado'] ?? 'ACTIVA')));

        // 1. Validación de Docente
        if ($idDocente <= 0) {
            $errors['id_docente'] = 'Debe seleccionar un docente.';
        } else {
            $docente = $this->docenteRepo->findById($idDocente);
            if (!$docente) {
                $errors['id_docente'] = 'El docente seleccionado no existe en el sistema.';
            } elseif (!$docente->isActivo()) {
                $errors['id_docente'] = 'El docente seleccionado se encuentra inactivo y no puede ser asignado.';
            } else {
                $user = $this->userRepo->findById($docente->id_usuario);
                if (!$user || $user->estado !== 'ACTIVO') {
                    $errors['id_docente'] = 'El usuario asociado al docente no se encuentra activo.';
                }
            }
        }

        // 2. Validación de Sección y Contexto Académico
        $seccion = null;
        $gradoPeriodo = null;
        if ($idSeccion <= 0) {
            $errors['id_seccion'] = 'Debe seleccionar una sección.';
        } else {
            $seccion = $this->seccionRepo->findById($idSeccion);
            if (!$seccion) {
                $errors['id_seccion'] = 'La sección seleccionada no existe en el sistema.';
            } elseif (!$seccion->isHabilitada()) {
                $errors['id_seccion'] = 'La sección seleccionada no se encuentra habilitada.';
            } else {
                $gradoPeriodo = $this->gradoPeriodoRepo->findById($seccion->id_grado_periodo);
                if (!$gradoPeriodo || !$gradoPeriodo->isHabilitado()) {
                    $errors['id_seccion'] = 'El grado correspondiente a la sección no se encuentra habilitado para este período escolar.';
                } else {
                    $periodo = $this->periodoRepo->findById($gradoPeriodo->id_periodo_escolar);
                    if (!$periodo) {
                        $errors['id_seccion'] = 'El período escolar asociado a la sección no existe.';
                    } elseif ($periodo->isCerrado()) {
                        $errors['id_seccion'] = 'No se pueden registrar asignaciones en un período escolar cerrado.';
                    }
                }
            }
        }

        // 3. Validación de Aula
        if ($idAula <= 0) {
            $errors['id_aula'] = 'Debe seleccionar un aula.';
        } else {
            $aula = $this->aulaRepo->findById($idAula);
            if (!$aula) {
                $errors['id_aula'] = 'El aula seleccionada no existe en el sistema.';
            } elseif (!$aula->isActiva()) {
                $errors['id_aula'] = 'El aula seleccionada no se encuentra activa.';
            }
        }

        // 4. Validación de Coherencia Académica (si se proporcionaron filtros de contexto)
        if ($seccion && $gradoPeriodo) {
            if (!empty($input['id_periodo_escolar']) && (int)$input['id_periodo_escolar'] !== $gradoPeriodo->id_periodo_escolar) {
                $errors['id_seccion'] = 'La sección seleccionada no corresponde al período escolar especificado.';
            }
            if (!empty($input['id_grado']) && (int)$input['id_grado'] !== $gradoPeriodo->id_grado) {
                $errors['id_seccion'] = 'La sección seleccionada no corresponde al grado escolar especificado.';
            }
        }

        // 5. Validación de Fechas
        if ($fechaInicio === '') {
            $errors['fecha_inicio'] = 'La fecha de inicio de la asignación es obligatoria.';
        } elseif (!$this->validarFecha($fechaInicio)) {
            $errors['fecha_inicio'] = 'La fecha de inicio debe tener el formato AAAA-MM-DD.';
        }

        if ($fechaFin !== null) {
            if (!$this->validarFecha($fechaFin)) {
                $errors['fecha_fin'] = 'La fecha de fin debe tener el formato AAAA-MM-DD.';
            } elseif ($fechaInicio !== '' && $this->validarFecha($fechaInicio) && $fechaFin < $fechaInicio) {
                $errors['fecha_fin'] = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
            }
        }

        // 6. Validación de Estado
        if (!in_array($estado, ['ACTIVA', 'INACTIVA'], true)) {
            $estado = 'ACTIVA';
        }

        // 7. Validación Preventiva de Unicidad Activa por Sección (DT-32)
        if (empty($errors['id_seccion']) && $estado === 'ACTIVA') {
            if ($this->asignacionRepo->existsActiveForSeccion($idSeccion)) {
                $errors['id_seccion'] = 'La sección ya cuenta con una asignación docente activa.';
            }
        }

        // 8. Validación Preventiva de Unicidad de Inicio (DT-33)
        if (empty($errors)) {
            if ($this->asignacionRepo->existsDuplicateInicio($idDocente, $idSeccion, $idAula, $fechaInicio)) {
                $errors['general'] = 'Ya existe un registro de asignación para el mismo docente, sección, aula y fecha de inicio.';
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
                'message' => $errors['general'] ?? ($errors['id_seccion'] ?? ($errors['id_docente'] ?? ($errors['id_aula'] ?? 'Existen errores de validación.'))),
            ];
        }

        // 9. Persistencia con captura defensiva de integridad relacional (DT-32)
        try {
            $idAsignacion = $this->asignacionRepo->create([
                'id_docente' => $idDocente,
                'id_seccion' => $idSeccion,
                'id_aula' => $idAula,
                'id_usuario_creador' => $idUsuarioCreador,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'estado' => $estado,
            ]);

            return [
                'success' => true,
                'id_asignacion_docente' => $idAsignacion,
                'message' => 'Asignación docente registrada exitosamente.',
            ];
        } catch (PDOException $e) {
            $code = (int)($e->errorInfo[1] ?? $e->getCode());
            $msg = $e->getMessage();

            if ($code === 1062) {
                if (str_contains($msg, 'uk_asignaciones_seccion_activa')) {
                    return [
                        'success' => false,
                        'errors' => ['id_seccion' => 'Conflicto de asignación: La sección ya cuenta con una asignación docente activa.'],
                        'message' => 'Conflicto de asignación: La sección ya cuenta con una asignación docente activa.',
                    ];
                }
                if (str_contains($msg, 'uk_asignaciones_inicio')) {
                    return [
                        'success' => false,
                        'errors' => ['general' => 'Ya existe un registro de asignación para el mismo docente, sección, aula y fecha de inicio.'],
                        'message' => 'Ya existe un registro de asignación idéntico en fecha de inicio.',
                    ];
                }
                return [
                    'success' => false,
                    'errors' => ['general' => 'Conflicto de unicidad en la asignación docente.'],
                    'message' => 'Conflicto de clave duplicada en la base de datos.',
                ];
            }

            if ($code === 1452) {
                return [
                    'success' => false,
                    'errors' => ['general' => 'Error de integridad referencial: Uno de los identificadores no es válido en el sistema.'],
                    'message' => 'Error de integridad referencial: Datos foráneos inexistentes.',
                ];
            }

            return [
                'success' => false,
                'errors' => ['general' => 'Error de base de datos al registrar la asignación.'],
                'message' => 'Error de base de datos al registrar la asignación.',
            ];
        }
    }

    /**
     * Actualizar campos permitidos de una asignación existente.
     * Preserva la inmutabilidad histórica del par docente-sección (DT-33).
     *
     * @param array<string, mixed> $input
     * @return array{success: bool, message: string, errors?: array<string, string>}
     */
    public function actualizarAsignacion(int $id, array $input): array
    {
        $asignacion = $this->asignacionRepo->findById($id);
        if (!$asignacion) {
            return ['success' => false, 'message' => 'Asignación no encontrada.'];
        }

        $errors = [];
        $idAula = (int)($input['id_aula'] ?? $asignacion->id_aula);
        $fechaInicio = trim((string)($input['fecha_inicio'] ?? $asignacion->fecha_inicio));
        $fechaFin = !empty($input['fecha_fin']) ? trim((string)$input['fecha_fin']) : null;
        $estado = strtoupper(trim((string)($input['estado'] ?? $asignacion->estado)));

        // Validación de Aula
        if ($idAula <= 0) {
            $errors['id_aula'] = 'Debe seleccionar un aula válida.';
        } else {
            $aula = $this->aulaRepo->findById($idAula);
            if (!$aula) {
                $errors['id_aula'] = 'El aula seleccionada no existe en el sistema.';
            } elseif (!$aula->isActiva() && $idAula !== $asignacion->id_aula) {
                $errors['id_aula'] = 'El aula seleccionada no se encuentra activa.';
            }
        }

        // Validación de Fechas
        if ($fechaInicio === '') {
            $errors['fecha_inicio'] = 'La fecha de inicio es obligatoria.';
        } elseif (!$this->validarFecha($fechaInicio)) {
            $errors['fecha_inicio'] = 'La fecha de inicio debe tener el formato AAAA-MM-DD.';
        }

        if ($fechaFin !== null) {
            if (!$this->validarFecha($fechaFin)) {
                $errors['fecha_fin'] = 'La fecha de fin debe tener el formato AAAA-MM-DD.';
            } elseif ($fechaInicio !== '' && $this->validarFecha($fechaInicio) && $fechaFin < $fechaInicio) {
                $errors['fecha_fin'] = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
            }
        }

        if (!in_array($estado, ['ACTIVA', 'INACTIVA'], true)) {
            $estado = $asignacion->estado;
        }

        // Si se cambia el estado a ACTIVA, verificar que no haya otra asignación activa para la misma sección
        if ($estado === 'ACTIVA' && !$asignacion->isActiva()) {
            if ($this->asignacionRepo->existsActiveForSeccion($asignacion->id_seccion, $id)) {
                $errors['estado'] = 'No se puede activar esta asignación porque la sección ya cuenta con otra asignación activa.';
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
                'message' => 'Existen errores de validación.',
            ];
        }

        try {
            $this->asignacionRepo->update($id, [
                'id_aula' => $idAula,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'estado' => $estado,
            ]);

            return ['success' => true, 'message' => 'Asignación actualizada exitosamente.'];
        } catch (PDOException $e) {
            $code = (int)($e->errorInfo[1] ?? $e->getCode());
            if ($code === 1062) {
                return ['success' => false, 'message' => 'Conflicto de asignación: La sección ya cuenta con una asignación activa.'];
            }
            return ['success' => false, 'message' => 'Error al actualizar asignación.'];
        }
    }

    /**
     * Desactivar o cerrar una asignación docente conservando el registro histórico (DT-33).
     */
    public function cerrarAsignacion(int $id, ?string $fechaFin = null): array
    {
        $asignacion = $this->asignacionRepo->findById($id);
        if (!$asignacion) {
            return ['success' => false, 'message' => 'Asignación no encontrada.'];
        }

        $fechaCierre = $fechaFin ?? date('Y-m-d');
        if (!$this->validarFecha($fechaCierre)) {
            $fechaCierre = date('Y-m-d');
        }

        if ($fechaCierre < $asignacion->fecha_inicio) {
            $fechaCierre = $asignacion->fecha_inicio;
        }

        $ok = $this->asignacionRepo->updateEstado($id, 'INACTIVA', $fechaCierre);
        if ($ok) {
            return ['success' => true, 'message' => 'Asignación cerrada exitosamente. Registro conservado en el historial.'];
        }

        return ['success' => false, 'message' => 'No se pudo cerrar la asignación.'];
    }

    /**
     * Alternar estado entre ACTIVA e INACTIVA.
     */
    public function cambiarEstado(int $id, string $nuevoEstado, ?string $fechaFin = null): array
    {
        $asignacion = $this->asignacionRepo->findById($id);
        if (!$asignacion) {
            return ['success' => false, 'message' => 'Asignación no encontrada.'];
        }

        $nuevoEstado = strtoupper(trim($nuevoEstado));
        if (!in_array($nuevoEstado, ['ACTIVA', 'INACTIVA'], true)) {
            return ['success' => false, 'message' => 'Estado no válido.'];
        }

        if ($nuevoEstado === 'ACTIVA') {
            if ($this->asignacionRepo->existsActiveForSeccion($asignacion->id_seccion, $id)) {
                return [
                    'success' => false,
                    'message' => 'No se puede activar esta asignación porque la sección ya cuenta con otra asignación activa.',
                ];
            }
            $this->asignacionRepo->updateEstado($id, 'ACTIVA', null);
            return ['success' => true, 'message' => 'Asignación activada exitosamente.'];
        }

        return $this->cerrarAsignacion($id, $fechaFin);
    }

    /**
     * Reemplazo atómico de docente en una sección bajo transacción PDO (DT-34).
     * Cierra la asignación activa previa y da de alta la nueva asignación en una operación indivisible.
     *
     * @return array{success: bool, id_asignacion_docente?: int, message: string}
     */
    public function reemplazarDocenteAtomico(
        int $idAsignacionActual,
        int $nuevoIdDocente,
        int $idAula,
        string $fechaInicio,
        int $idUsuarioCreador,
        bool $simularFallo = false
    ): array {
        $actual = $this->asignacionRepo->findById($idAsignacionActual);
        if (!$actual) {
            return ['success' => false, 'message' => 'La asignación actual a reemplazar no existe.'];
        }

        if (!$actual->isActiva()) {
            return ['success' => false, 'message' => 'Solo se pueden reemplazar asignaciones actualmente activas.'];
        }

        // Validar nuevo docente
        $nuevoDocente = $this->docenteRepo->findById($nuevoIdDocente);
        if (!$nuevoDocente || !$nuevoDocente->isActivo()) {
            return ['success' => false, 'message' => 'El nuevo docente seleccionado no existe o está inactivo.'];
        }

        // Validar nueva aula
        $aula = $this->aulaRepo->findById($idAula);
        if (!$aula || !$aula->isActiva()) {
            return ['success' => false, 'message' => 'El aula seleccionada no existe o se encuentra inactiva.'];
        }

        if (!$this->validarFecha($fechaInicio)) {
            return ['success' => false, 'message' => 'La fecha de inicio no tiene un formato válido (AAAA-MM-DD).'];
        }

        $this->db->beginTransaction();
        try {
            // 1. Cerrar asignación actual
            $fechaCierre = date('Y-m-d', (int)strtotime($fechaInicio . ' -1 day'));
            if ($fechaCierre < $actual->fecha_inicio) {
                $fechaCierre = $actual->fecha_inicio;
            }
            $this->asignacionRepo->updateEstado($idAsignacionActual, 'INACTIVA', $fechaCierre);

            // Hook para prueba de rollback transaccional T5-20
            if ($simularFallo) {
                throw new \RuntimeException('Fallo forzado para validación de rollback transaccional.');
            }

            // 2. Crear nueva asignación activa para la misma sección
            $newId = $this->asignacionRepo->create([
                'id_docente' => $nuevoIdDocente,
                'id_seccion' => $actual->id_seccion,
                'id_aula' => $idAula,
                'id_usuario_creador' => $idUsuarioCreador,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => null,
                'estado' => 'ACTIVA',
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'id_asignacion_docente' => $newId,
                'message' => 'Docente reemplazado exitosamente de forma atómica.',
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Error durante el reemplazo atómico: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Obtener listado de docentes activos para dropdowns de asignación.
     *
     * @return array<array{id_docente: int, nombre_completo: string, codigo: string}>
     */
    public function obtenerDocentesActivos(): array
    {
        $sql = 'SELECT d.id_docente, d.codigo_docente,
                       TRIM(CONCAT(u.nombres, " ", u.apellidos)) AS nombre_completo
                FROM docentes d
                JOIN usuarios u ON u.id_usuario = d.id_usuario
                WHERE d.estado = "ACTIVO" AND u.estado = "ACTIVO"
                ORDER BY u.apellidos ASC, u.nombres ASC';

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener listado de aulas activas para dropdowns de asignación.
     *
     * @return array<array{id_aula: int, codigo: string, nombre: string}>
     */
    public function obtenerAulasActivas(): array
    {
        $sql = 'SELECT id_aula, codigo, nombre, ubicacion_referencia
                FROM aulas
                WHERE activa = 1
                ORDER BY codigo ASC';

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener listado de períodos escolares para filtros.
     *
     * @return array<array{id_periodo_escolar: int, nombre: string, estado: string}>
     */
    public function obtenerPeriodos(): array
    {
        $sql = 'SELECT id_periodo_escolar, nombre, estado, fecha_inicio, fecha_fin
                FROM periodos_escolares
                ORDER BY fecha_inicio DESC';

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener secciones disponibles con detalle de grado y período, indicando si ya tienen docente activo asignado.
     *
     * @return array<array{id_seccion: int, codigo: string, nombre: ?string, capacidad: int, id_grado: int, grado_nombre: string, id_periodo_escolar: int, periodo_nombre: string, tiene_docente_activo: int, docente_actual: ?string}>
     */
    public function obtenerSeccionesDisponibles(?int $idPeriodo = null): array
    {
        $sql = 'SELECT s.id_seccion, s.codigo, s.nombre, s.capacidad,
                       g.id_grado, g.nombre AS grado_nombre,
                       p.id_periodo_escolar, p.nombre AS periodo_nombre,
                       CASE WHEN asig_activa.id_asignacion_docente IS NOT NULL THEN 1 ELSE 0 END AS tiene_docente_activo,
                       TRIM(CONCAT(u_doc.nombres, " ", u_doc.apellidos)) AS docente_actual
                FROM secciones s
                JOIN grados_periodos gp ON gp.id_grado_periodo = s.id_grado_periodo
                JOIN grados g ON g.id_grado = gp.id_grado
                JOIN periodos_escolares p ON p.id_periodo_escolar = gp.id_periodo_escolar
                LEFT JOIN asignaciones_docentes asig_activa 
                       ON asig_activa.id_seccion = s.id_seccion AND asig_activa.estado = "ACTIVA"
                LEFT JOIN docentes d_doc ON d_doc.id_docente = asig_activa.id_docente
                LEFT JOIN usuarios u_doc ON u_doc.id_usuario = d_doc.id_usuario
                WHERE s.habilitada = 1 AND gp.habilitado = 1';

        $params = [];
        if ($idPeriodo !== null && $idPeriodo > 0) {
            $sql .= ' AND p.id_periodo_escolar = :id_periodo';
            $params[':id_periodo'] = $idPeriodo;
        }

        $sql .= ' ORDER BY p.fecha_inicio DESC, g.orden ASC, s.codigo ASC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function validarFecha(string $fecha): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return false;
        }

        [$y, $m, $d] = explode('-', $fecha);
        return checkdate((int)$m, (int)$d, (int)$y);
    }
}
