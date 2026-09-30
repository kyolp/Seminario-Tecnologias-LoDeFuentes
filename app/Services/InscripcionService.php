<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Encargado;
use App\Models\Estudiante;
use App\Models\Inscripcion;
use App\Models\PeriodoEscolar;
use App\Models\Seccion;
use App\Repositories\EncargadoRepository;
use App\Repositories\EncargadoRepositoryInterface;
use App\Repositories\EstudianteRepository;
use App\Repositories\EstudianteRepositoryInterface;
use App\Repositories\GradoPeriodoRepository;
use App\Repositories\GradoPeriodoRepositoryInterface;
use App\Repositories\GradoRepository;
use App\Repositories\GradoRepositoryInterface;
use App\Repositories\InscripcionRepository;
use App\Repositories\InscripcionRepositoryInterface;
use App\Repositories\PeriodoRepository;
use App\Repositories\PeriodoRepositoryInterface;
use App\Repositories\SeccionRepository;
use App\Repositories\SeccionRepositoryInterface;
use PDO;
use PDOException;
use Throwable;

/**
 * Servicio de Inscripciones Públicas y Control Concurrente de Cupos (Fase 6).
 *
 * Implementa:
 * - RF-05: Datos del encargado
 * - RF-09: Control automático de cupos
 * - RF-10: Disponibilidad de secciones
 * - RF-11: Inscripción web pública sin login
 * - RF-12: Generación de código único de confirmación
 * - RF-14: Consulta pública segura mediante código
 * - RF-32: Integridad concurrente bajo SELECT ... FOR UPDATE
 */
class InscripcionService
{
    private PDO $db;
    private InscripcionRepositoryInterface $inscripcionRepo;
    private EstudianteRepositoryInterface $estudianteRepo;
    private EncargadoRepositoryInterface $encargadoRepo;
    private SeccionRepositoryInterface $seccionRepo;
    private GradoPeriodoRepositoryInterface $gradoPeriodoRepo;
    private PeriodoRepositoryInterface $periodoRepo;
    private GradoRepositoryInterface $gradoRepo;

    public function __construct(
        ?PDO $db = null,
        ?InscripcionRepositoryInterface $inscripcionRepo = null,
        ?EstudianteRepositoryInterface $estudianteRepo = null,
        ?EncargadoRepositoryInterface $encargadoRepo = null,
        ?SeccionRepositoryInterface $seccionRepo = null,
        ?GradoPeriodoRepositoryInterface $gradoPeriodoRepo = null,
        ?PeriodoRepositoryInterface $periodoRepo = null,
        ?GradoRepositoryInterface $gradoRepo = null
    ) {
        $this->db = $db ?? Database::getConnection();
        $this->inscripcionRepo = $inscripcionRepo ?? new InscripcionRepository($this->db);
        $this->estudianteRepo = $estudianteRepo ?? new EstudianteRepository($this->db);
        $this->encargadoRepo = $encargadoRepo ?? new EncargadoRepository($this->db);
        $this->seccionRepo = $seccionRepo ?? new SeccionRepository($this->db);
        $this->gradoPeriodoRepo = $gradoPeriodoRepo ?? new GradoPeriodoRepository($this->db);
        $this->periodoRepo = $periodoRepo ?? new PeriodoRepository($this->db);
        $this->gradoRepo = $gradoRepo ?? new GradoRepository($this->db);
    }

    /**
     * Obtener el período escolar disponible para inscripciones (ACTIVO o PLANIFICADO más próximo).
     */
    public function obtenerPeriodoInscripcion(): ?PeriodoEscolar
    {
        $sql = "SELECT id_periodo_escolar, nombre, fecha_inicio, fecha_fin, estado
                FROM periodos_escolares
                WHERE estado IN ('ACTIVO', 'PLANIFICADO')
                ORDER BY (CASE WHEN estado = 'ACTIVO' THEN 1 ELSE 2 END) ASC, fecha_inicio DESC
                LIMIT 1";

        $stmt = $this->db->query($sql);
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

        return $row ? PeriodoEscolar::fromArray($row) : null;
    }

    /**
     * Obtener grados habilitados para un período escolar.
     *
     * @return array<array{id_grado: int, id_grado_periodo: int, codigo: string, nombre: string, orden: int}>
     */
    public function obtenerGradosHabilitados(int $idPeriodo): array
    {
        $sql = "SELECT g.id_grado, gp.id_grado_periodo, g.codigo, g.nombre, g.orden
                FROM grados_periodos gp
                JOIN grados g ON g.id_grado = gp.id_grado
                WHERE gp.id_periodo_escolar = :periodo
                  AND gp.habilitado = 1
                  AND g.activo = 1
                ORDER BY g.orden ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':periodo' => $idPeriodo]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener secciones disponibles con cálculo dinámico de ocupación y cupos.
     * Si $soloConCupos = true, filtra las secciones llenas (RN-DOC-02).
     *
     * @return array<array{
     *     id_seccion: int,
     *     codigo: string,
     *     nombre: ?string,
     *     capacidad: int,
     *     ocupacion: int,
     *     disponibles: int,
     *     tiene_cupo: bool
     * }>
     */
    public function obtenerSeccionesDisponibles(int $idGradoPeriodo, bool $soloConCupos = false): array
    {
        $sql = "SELECT s.id_seccion, s.codigo, s.nombre, s.capacidad, s.habilitada
                FROM secciones s
                WHERE s.id_grado_periodo = :gp
                  AND s.habilitada = 1
                ORDER BY s.codigo ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':gp' => $idGradoPeriodo]);
        $secciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($secciones as $sec) {
            $idSec = (int)$sec['id_seccion'];
            $capacidad = (int)$sec['capacidad'];
            $ocupacion = $this->inscripcionRepo->countOcupacionBySeccion($idSec);
            $disponibles = max(0, $capacidad - $ocupacion);
            $tieneCupo = ($disponibles > 0);

            if ($soloConCupos && !$tieneCupo) {
                continue; // Cumplimiento de RN-DOC-02
            }

            $resultado[] = [
                'id_seccion' => $idSec,
                'codigo' => (string)$sec['codigo'],
                'nombre' => $sec['nombre'] !== null ? (string)$sec['nombre'] : null,
                'capacidad' => $capacidad,
                'ocupacion' => $ocupacion,
                'disponibles' => $disponibles,
                'tiene_cupo' => $tieneCupo,
            ];
        }

        return $resultado;
    }

    /**
     * Generar un código único de confirmación (RF-12).
     * Formato criptográficamente impredecible: INS-AAAA-XXXXXXXX
     */
    public function generarCodigoConfirmacion(?string $prefijo = null): string
    {
        $prefijo = $prefijo ?? ('INS-' . date('Y') . '-');
        $maxIntentos = 10;

        for ($i = 0; $i < $maxIntentos; $i++) {
            $aleatorio = strtoupper(bin2hex(random_bytes(4))); // 8 caracteres hexadecimales
            $codigo = $prefijo . $aleatorio;

            if (!$this->inscripcionRepo->existsCodigo($codigo)) {
                return $codigo;
            }
        }

        // Respaldo de alta entropía en caso extremo de colisión consecutiva
        return $prefijo . strtoupper(substr(bin2hex(random_bytes(8)), 0, 10));
    }

    /**
     * Procesar inscripción pública de forma atómica y segura con bloqueo pesimista (SELECT ... FOR UPDATE).
     *
     * @param array<string, mixed> $input
     * @return array{success: bool, codigo_confirmacion?: string, id_inscripcion?: int, message: string, errors?: array<string, string>}
     */
    public function procesarInscripcionPublica(array $input): array
    {
        $errors = [];

        // 1. Sanitización y extracción segura (Whitelisting estricto)
        $estudianteNombres = trim((string)($input['estudiante_nombres'] ?? ''));
        $estudianteApellidos = trim((string)($input['estudiante_apellidos'] ?? ''));
        $estudianteFechaNac = !empty($input['estudiante_fecha_nacimiento']) ? trim((string)$input['estudiante_fecha_nacimiento']) : null;
        $codigoEstudiante = !empty($input['codigo_estudiante']) ? trim((string)$input['codigo_estudiante']) : null;

        $encargadoNombres = trim((string)($input['encargado_nombres'] ?? ''));
        $encargadoApellidos = trim((string)($input['encargado_apellidos'] ?? ''));
        $encargadoTelefono = !empty($input['encargado_telefono']) ? trim((string)$input['encargado_telefono']) : null;
        $encargadoCorreo = !empty($input['encargado_correo']) ? trim((string)$input['encargado_correo']) : null;
        $parentesco = strtoupper(trim((string)($input['parentesco'] ?? '')));

        $idSeccion = (int)($input['id_seccion'] ?? 0);

        // 2. Validaciones sintácticas del Estudiante
        if ($estudianteNombres === '') {
            $errors['estudiante_nombres'] = 'Los nombres del estudiante son obligatorios.';
        } elseif (mb_strlen($estudianteNombres) > 100) {
            $errors['estudiante_nombres'] = 'Los nombres no deben exceder 100 caracteres.';
        }

        if ($estudianteApellidos === '') {
            $errors['estudiante_apellidos'] = 'Los apellidos del estudiante son obligatorios.';
        } elseif (mb_strlen($estudianteApellidos) > 100) {
            $errors['estudiante_apellidos'] = 'Los apellidos no deben exceder 100 caracteres.';
        }

        if ($estudianteFechaNac !== null && !$this->validarFecha($estudianteFechaNac)) {
            $errors['estudiante_fecha_nacimiento'] = 'La fecha de nacimiento debe tener el formato AAAA-MM-DD.';
        }

        // 3. Validaciones sintácticas del Encargado (RF-05)
        if ($encargadoNombres === '') {
            $errors['encargado_nombres'] = 'Los nombres del encargado son obligatorios.';
        } elseif (mb_strlen($encargadoNombres) > 100) {
            $errors['encargado_nombres'] = 'Los nombres del encargado no deben exceder 100 caracteres.';
        }

        if ($encargadoApellidos === '') {
            $errors['encargado_apellidos'] = 'Los apellidos del encargado son obligatorios.';
        } elseif (mb_strlen($encargadoApellidos) > 100) {
            $errors['encargado_apellidos'] = 'Los apellidos del encargado no deben exceder 100 caracteres.';
        }

        if ($encargadoCorreo !== null && !filter_var($encargadoCorreo, FILTER_VALIDATE_EMAIL)) {
            $errors['encargado_correo'] = 'El correo electrónico del encargado no tiene un formato válido.';
        }

        if ($parentesco === '') {
            $errors['parentesco'] = 'Debe especificar el parentesco con el estudiante (e.g. PADRE, MADRE, TUTOR).';
        } elseif (mb_strlen($parentesco) > 50) {
            $errors['parentesco'] = 'El parentesco no debe exceder 50 caracteres.';
        }

        // 4. Validación de Sección
        if ($idSeccion <= 0) {
            $errors['id_seccion'] = 'Debe seleccionar una sección para la inscripción.';
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
                'message' => 'Por favor verifique los campos del formulario de inscripción.',
            ];
        }

        // 5. Transacción atómica con Bloqueo Pesimista (RF-32, DT-37)
        $this->db->beginTransaction();

        try {
            // 5.1 Bloquear la fila de la sección seleccionada con SELECT ... FOR UPDATE
            $seccion = $this->inscripcionRepo->lockSeccionForUpdate($idSeccion);

            if (!$seccion) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'errors' => ['id_seccion' => 'La sección seleccionada no existe en el sistema.'],
                    'message' => 'La sección seleccionada no existe.',
                ];
            }

            if ($seccion['habilitada'] !== 1) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'errors' => ['id_seccion' => 'La sección seleccionada no se encuentra habilitada.'],
                    'message' => 'La sección seleccionada no se encuentra habilitada.',
                ];
            }

            // 5.2 Verificar coherencia de Grado-Período y Período no CERRADO
            $gp = $this->gradoPeriodoRepo->findById($seccion['id_grado_periodo']);
            if (!$gp || !$gp->isHabilitado()) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'errors' => ['id_seccion' => 'El grado escolar de la sección no está habilitado.'],
                    'message' => 'El grado escolar no está habilitado para el período.',
                ];
            }

            $periodo = $this->periodoRepo->findById($gp->id_periodo_escolar);
            if (!$periodo || $periodo->isCerrado()) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'errors' => ['id_seccion' => 'El período escolar se encuentra cerrado.'],
                    'message' => 'No se admiten inscripciones en un período escolar cerrado.',
                ];
            }

            // 5.3 Control estricto de cupos bajo bloqueo transaccional (RF-09, RF-32)
            $ocupacionActual = $this->inscripcionRepo->countOcupacionBySeccion($idSeccion);
            if ($ocupacionActual >= $seccion['capacidad']) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'errors' => ['id_seccion' => 'La sección ha alcanzado su capacidad máxima (sin cupo disponible).'],
                    'message' => 'No hay cupos disponibles en la sección seleccionada.',
                ];
            }

            // 5.4 Identificar o crear al Estudiante
            $idEstudiante = 0;
            if ($codigoEstudiante !== null) {
                $estExistente = $this->estudianteRepo->findByCodigo($codigoEstudiante);
                if ($estExistente) {
                    $idEstudiante = $estExistente->id_estudiante;
                }
            }

            if ($idEstudiante === 0) {
                // Verificar si existe estudiante homónimo con misma fecha de nacimiento
                $sqlMatch = 'SELECT id_estudiante FROM estudiantes WHERE LOWER(nombres) = LOWER(:nom) AND LOWER(apellidos) = LOWER(:ape)';
                $paramsMatch = [':nom' => $estudianteNombres, ':ape' => $estudianteApellidos];
                if ($estudianteFechaNac !== null) {
                    $sqlMatch .= ' AND fecha_nacimiento = :fn';
                    $paramsMatch[':fn'] = $estudianteFechaNac;
                }
                $sqlMatch .= ' LIMIT 1';

                $stmtM = $this->db->prepare($sqlMatch);
                $stmtM->execute($paramsMatch);
                $idMatch = $stmtM->fetchColumn();

                if ($idMatch) {
                    $idEstudiante = (int)$idMatch;
                } else {
                    $idEstudiante = $this->estudianteRepo->create([
                        'codigo_estudiante' => $codigoEstudiante,
                        'nombres' => $estudianteNombres,
                        'apellidos' => $estudianteApellidos,
                        'fecha_nacimiento' => $estudianteFechaNac,
                        'estado' => 'ACTIVO',
                    ]);
                }
            }

            // 5.5 Prevenir doble inscripción vigente del mismo estudiante en la misma sección
            $inscripcionVigente = $this->inscripcionRepo->findVigenteByEstudianteAndSeccion($idEstudiante, $idSeccion);
            if ($inscripcionVigente) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'errors' => ['general' => 'El estudiante ya cuenta con una inscripción vigente en esta sección.'],
                    'message' => 'El estudiante ya se encuentra inscrito en esta sección.',
                ];
            }

            // 5.6 Identificar o crear al Encargado (RF-05)
            $encargadoExistente = $this->encargadoRepo->findMatching(
                $encargadoNombres,
                $encargadoApellidos,
                $encargadoTelefono,
                $encargadoCorreo
            );

            if ($encargadoExistente) {
                $idEncargado = $encargadoExistente->id_encargado;
            } else {
                $idEncargado = $this->encargadoRepo->create([
                    'nombres' => $encargadoNombres,
                    'apellidos' => $encargadoApellidos,
                    'telefono' => $encargadoTelefono,
                    'correo' => $encargadoCorreo,
                ]);
            }

            // 5.7 Establecer vínculo Estudiante-Encargado (integridad N:M)
            $this->encargadoRepo->linkEstudiante($idEstudiante, $idEncargado, $parentesco, true);

            // 5.8 Generar código único de confirmación (RF-12)
            $codigoConfirmacion = $this->generarCodigoConfirmacion();

            // 5.9 Crear la inscripción CONFIRMADA
            $idInscripcion = $this->inscripcionRepo->create([
                'id_estudiante' => $idEstudiante,
                'id_encargado' => $idEncargado,
                'id_seccion' => $idSeccion,
                'codigo_confirmacion' => $codigoConfirmacion,
                'estado' => 'CONFIRMADA',
                'fecha_confirmacion' => date('Y-m-d H:i:s'),
            ]);

            // 5.10 Confirmar transacción atómica
            $this->db->commit();

            return [
                'success' => true,
                'codigo_confirmacion' => $codigoConfirmacion,
                'id_inscripcion' => $idInscripcion,
                'message' => '¡Inscripción confirmada exitosamente!',
            ];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            if ($e instanceof PDOException) {
                $code = (int)($e->errorInfo[1] ?? $e->getCode());
                $msg = $e->getMessage();

                if ($code === 1062) {
                    if (str_contains($msg, 'uk_inscripciones_estudiante_seccion_vigente')) {
                        return [
                            'success' => false,
                            'errors' => ['general' => 'El estudiante ya cuenta con una inscripción vigente en esta sección.'],
                            'message' => 'El estudiante ya se encuentra inscrito en esta sección.',
                        ];
                    }
                    if (str_contains($msg, 'uk_inscripciones_codigo')) {
                        // Reintento si colisionó el código
                        return [
                            'success' => false,
                            'errors' => ['general' => 'Conflicto temporal al generar el código único de confirmación. Por favor intente nuevamente.'],
                            'message' => 'Conflicto temporal en el código de confirmación. Intente nuevamente.',
                        ];
                    }
                }
            }

            return [
                'success' => false,
                'errors' => ['general' => 'Ocurrió un error al procesar la inscripción. Por favor intente nuevamente.'],
                'message' => 'Ocurrió un error al registrar la inscripción.',
            ];
        }
    }

    /**
     * Consulta pública segura mediante código único de confirmación (RF-14).
     * Retorna exclusivamente datos necesarios, protegiendo privacidad (RNF-04).
     *
     * @return array{
     *     success: bool,
     *     message?: string,
     *     data?: array{
     *         codigo_confirmacion: string,
     *         estudiante_nombre: string,
     *         codigo_estudiante: ?string,
     *         grado: string,
     *         seccion: string,
     *         periodo: string,
     *         encargado_nombre: string,
     *         parentesco: string,
     *         fecha_inscripcion: string,
     *         fecha_confirmacion: ?string,
     *         estado: string
     *     }
     * }
     */
    public function consultarInscripcionPorCodigo(string $codigo): array
    {
        $codigoLimpio = trim($codigo);
        if ($codigoLimpio === '') {
            return [
                'success' => false,
                'message' => 'Por favor ingrese un código de confirmación válido.',
            ];
        }

        $inscripcion = $this->inscripcionRepo->findByCodigoConfirmacion($codigoLimpio);

        if (!$inscripcion) {
            return [
                'success' => false,
                'message' => 'No se encontró ninguna inscripción con el código ingresado. Verifique que esté escrito exactamente como en su comprobante.',
            ];
        }

        // Sanitización para privacidad pública: no exponer IDs de BD ni información sensible
        return [
            'success' => true,
            'data' => [
                'codigo_confirmacion' => (string)$inscripcion->codigo_confirmacion,
                'estudiante_nombre' => $inscripcion->getEstudianteNombreCompleto(),
                'codigo_estudiante' => $inscripcion->codigo_estudiante,
                'grado' => (string)($inscripcion->grado_nombre ?? 'Grado no especificado'),
                'seccion' => (string)($inscripcion->seccion_codigo ?? '') . ($inscripcion->seccion_nombre ? ' (' . $inscripcion->seccion_nombre . ')' : ''),
                'periodo' => (string)($inscripcion->periodo_nombre ?? 'Ciclo Escolar'),
                'encargado_nombre' => $inscripcion->getEncargadoNombreCompleto(),
                'parentesco' => (string)($inscripcion->parentesco ?? 'Encargado'),
                'fecha_inscripcion' => substr($inscripcion->fecha_inscripcion, 0, 10),
                'fecha_confirmacion' => $inscripcion->fecha_confirmacion ? substr($inscripcion->fecha_confirmacion, 0, 10) : null,
                'estado' => $inscripcion->estado,
            ],
        ];
    }

    private function validarFecha(string $date): bool
    {
        $parts = explode('-', $date);
        if (count($parts) !== 3) {
            return false;
        }
        [$year, $month, $day] = $parts;
        if (!ctype_digit($year) || !ctype_digit($month) || !ctype_digit($day)) {
            return false;
        }
        return checkdate((int)$month, (int)$day, (int)$year);
    }
}
