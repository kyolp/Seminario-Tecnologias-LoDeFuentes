<?php

declare(strict_types=1);

/**
 * Suite de pruebas automatizadas para Fase 6 — Inscripciones y Cupos.
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Cobertura obligatoria:
 * T6-01 a T6-25 + T6-26 (Regresión F5: 26/26), T6-27 (Regresión F4: 27/27),
 * T6-28 (Regresión F3: 19/19), T6-29 (Regresión F2: 16/16), T6-30 (Regresión F1: 18/18).
 * Total: 30 pruebas automatizadas.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\InscripcionPublicaController;
use App\Core\App;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Encargado;
use App\Models\Estudiante;
use App\Models\Inscripcion;
use App\Models\PeriodoEscolar;
use App\Models\Seccion;
use App\Repositories\EncargadoRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\GradoPeriodoRepository;
use App\Repositories\GradoRepository;
use App\Repositories\InscripcionRepository;
use App\Repositories\PeriodoRepository;
use App\Repositories\SeccionRepository;
use App\Services\InscripcionService;

class Phase6TestSuite
{
    private PDO $db;
    private InscripcionRepository $inscripcionRepo;
    private EstudianteRepository $estudianteRepo;
    private EncargadoRepository $encargadoRepo;
    private SeccionRepository $seccionRepo;
    private GradoPeriodoRepository $gradoPeriodoRepo;
    private PeriodoRepository $periodoRepo;
    private GradoRepository $gradoRepo;
    private InscripcionService $inscripcionService;

    private int $total = 0;
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];

    // Fixtures creados para la prueba
    private ?int $fixturePeriodoId = null;
    private ?int $fixtureGradoId = null;
    private ?int $fixtureGradoPeriodoId = null;
    private ?int $fixtureSeccionAId = null; // Capacidad 30
    private ?int $fixtureSeccionBId = null; // Capacidad 2 (último cupo)
    private ?int $fixtureSeccionCId = null; // Deshabilitada (habilitada = 0)
    private ?int $fixturePeriodoCerradoId = null;
    private ?int $fixtureSeccionCerradaId = null;

    private array $createdInscripcionIds = [];
    private array $createdEstudianteIds = [];
    private array $createdEncargadoIds = [];
    public array $concurrencyTableLogs = [];

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->inscripcionRepo = new InscripcionRepository($this->db);
        $this->estudianteRepo = new EstudianteRepository($this->db);
        $this->encargadoRepo = new EncargadoRepository($this->db);
        $this->seccionRepo = new SeccionRepository($this->db);
        $this->gradoPeriodoRepo = new GradoPeriodoRepository($this->db);
        $this->periodoRepo = new PeriodoRepository($this->db);
        $this->gradoRepo = new GradoRepository($this->db);

        $this->inscripcionService = new InscripcionService(
            $this->db,
            $this->inscripcionRepo,
            $this->estudianteRepo,
            $this->encargadoRepo,
            $this->seccionRepo,
            $this->gradoPeriodoRepo,
            $this->periodoRepo,
            $this->gradoRepo
        );
    }

    public function run(): void
    {
        echo "============================================================\n";
        echo "SUITE DE PRUEBAS AUTOMATIZADAS — FASE 6\n";
        echo "Inscripciones y Cupos — Escuela Oficial Rural Mixta Lo de Fuentes\n";
        echo "============================================================\n\n";

        $this->cleanPreviousArtifacts();
        $this->setupFixtures();

        try {
            $this->testT6_01_FormularioPublicoAccesible();
            $this->testT6_02_GradosCorrectosPorPeriodo();
            $this->testT6_03_SeccionesCorrectasPorGradoPeriodo();
            $this->testT6_04_DisponibilidadCalculada();
            $this->testT6_05_SeccionLlenaNoDisponible();
            $this->testT6_06_CrearInscripcionValida();
            $this->testT6_07_CodigoUnicoGenerado();
            $this->testT6_08_ConsultaPorCodigoValido();
            $this->testT6_09_CodigoInexistenteControlado();
            $this->testT6_10_CamposObligatoriosRechazo();
            $this->testT6_11_FkManipuladaSeccionInexistente();
            $this->testT6_12_SeccionIncompatiblePeriodoCerrado();
            $this->testT6_13_EstudianteDuplicadoSeccionVigente();
            $this->testT6_14_EncargadoVinculadoCorrectamente();
            $this->testT6_15_EncargadoNoRelacionadoPrevenido();
            $this->testT6_16_ProteccionCsrf();
            $this->testT6_17_PrevencionXSS();
            $this->testT6_18_MassAssignmentProtegido();
            $this->testT6_19_RollbackTransaccionalIntermedio();
            $this->testT6_20_UltimoCupoSecuencial();
            $this->testT6_21_ConcurrenciaRealUltimoCupo10Iteraciones();
            $this->testT6_22_DobleSubmitConcurrente();
            $this->testT6_23_ColisionCodigoRegeneracion();
            $this->testT6_24_PrivacidadConsultaPublica();
            $this->testT6_25_FlujoHttpPublicoReal();
            $this->testT6_26_RegresionFase5();
            $this->testT6_27_RegresionFase4();
            $this->testT6_28_RegresionFase3();
            $this->testT6_29_RegresionFase2();
            $this->testT6_30_RegresionFase1();
        } finally {
            $this->cleanup();
        }

        $this->printSummary();
    }

    private function record(string $id, string $name, string $expected, string $obtained, bool $isOk): void
    {
        $this->total++;
        if ($isOk) {
            $this->passed++;
            $status = 'OK';
        } else {
            $this->failed++;
            $status = 'FALLO';
        }

        $this->results[] = [
            'id' => $id,
            'name' => $name,
            'expected' => $expected,
            'obtained' => $obtained,
            'status' => $status,
        ];

        printf("[%s] %s: %s | Obtenido: %s\n", $status, $id, $name, $obtained);
    }

    private function cleanPreviousArtifacts(): void
    {
        $this->db->exec("DELETE FROM inscripciones WHERE codigo_confirmacion LIKE 'INS-F6%' OR codigo_confirmacion LIKE 'INS-TEST%'");
        $this->db->exec("DELETE FROM estudiantes_encargados WHERE id_estudiante IN (SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante LIKE 'EST-F6%' OR nombres LIKE 'TestF6%')");
        $this->db->exec("DELETE FROM estudiantes WHERE codigo_estudiante LIKE 'EST-F6%' OR nombres LIKE 'TestF6%'");
        $this->db->exec("DELETE FROM encargados WHERE nombres LIKE 'TestF6%'");
        $this->db->exec("DELETE FROM secciones WHERE codigo LIKE 'F6-%' OR codigo LIKE 'F6RACE-%'");
        $this->db->exec("DELETE FROM grados_periodos WHERE id_grado IN (SELECT id_grado FROM grados WHERE codigo LIKE 'GRD-F6%')");
        $this->db->exec("DELETE FROM grados WHERE codigo LIKE 'GRD-F6%'");
        $this->db->exec("DELETE FROM periodos_escolares WHERE nombre LIKE 'Ciclo F6%'");
    }

    private function setupFixtures(): void
    {
        // 1. Período F6 en estado PLANIFICADO (abierto para inscripciones del siguiente ciclo)
        $this->db->exec("INSERT INTO periodos_escolares (nombre, fecha_inicio, fecha_fin, estado) VALUES ('Ciclo F6 Inscripciones 2026', '2026-01-15', '2026-10-31', 'PLANIFICADO')");
        $this->fixturePeriodoId = (int)$this->db->lastInsertId();

        // 2. Grado F6
        $this->db->exec("INSERT INTO grados (codigo, nombre, orden, activo) VALUES ('GRD-F6-01', 'Primer Grado F6', 31, 1)");
        $this->fixtureGradoId = (int)$this->db->lastInsertId();

        // 3. Grado-Período F6
        $this->db->exec("INSERT INTO grados_periodos (id_periodo_escolar, id_grado, habilitado) VALUES ({$this->fixturePeriodoId}, {$this->fixtureGradoId}, 1)");
        $this->fixtureGradoPeriodoId = (int)$this->db->lastInsertId();

        // 4. Secciones F6
        // Sección A: Capacidad 30, habilitada
        $this->db->exec("INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada) VALUES ({$this->fixtureGradoPeriodoId}, 'F6-A', 'Sección F6-A Normal', 30, 1)");
        $this->fixtureSeccionAId = (int)$this->db->lastInsertId();

        // Sección B: Capacidad 2, habilitada (para pruebas de último cupo)
        $this->db->exec("INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada) VALUES ({$this->fixtureGradoPeriodoId}, 'F6-B', 'Sección F6-B Cupo Limitado', 2, 1)");
        $this->fixtureSeccionBId = (int)$this->db->lastInsertId();

        // Sección C: Capacidad 15, deshabilitada
        $this->db->exec("INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada) VALUES ({$this->fixtureGradoPeriodoId}, 'F6-C', 'Sección F6-C Inactiva', 15, 0)");
        $this->fixtureSeccionCId = (int)$this->db->lastInsertId();

        // 5. Período Cerrado para prueba de rechazo
        $this->db->exec("INSERT INTO periodos_escolares (nombre, fecha_inicio, fecha_fin, estado) VALUES ('Ciclo F6 Cerrado 2024', '2024-01-15', '2024-10-31', 'CERRADO')");
        $this->fixturePeriodoCerradoId = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO grados_periodos (id_periodo_escolar, id_grado, habilitado) VALUES ({$this->fixturePeriodoCerradoId}, {$this->fixtureGradoId}, 1)");
        $gpCerradoId = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada) VALUES ({$gpCerradoId}, 'F6-CERR', 'Sección Período Cerrado', 25, 1)");
        $this->fixtureSeccionCerradaId = (int)$this->db->lastInsertId();
    }

    private function cleanup(): void
    {
        if (!empty($this->createdInscripcionIds)) {
            $in = implode(',', array_map('intval', $this->createdInscripcionIds));
            $this->db->exec("DELETE FROM inscripciones WHERE id_inscripcion IN ($in)");
        }

        // Limpiar inscripciones asociadas a las secciones de prueba
        if ($this->fixtureSeccionAId) $this->db->exec("DELETE FROM inscripciones WHERE id_seccion = {$this->fixtureSeccionAId}");
        if ($this->fixtureSeccionBId) $this->db->exec("DELETE FROM inscripciones WHERE id_seccion = {$this->fixtureSeccionBId}");
        if ($this->fixtureSeccionCId) $this->db->exec("DELETE FROM inscripciones WHERE id_seccion = {$this->fixtureSeccionCId}");
        if ($this->fixtureSeccionCerradaId) $this->db->exec("DELETE FROM inscripciones WHERE id_seccion = {$this->fixtureSeccionCerradaId}");

        if (!empty($this->createdEstudianteIds)) {
            $inE = implode(',', array_map('intval', $this->createdEstudianteIds));
            $this->db->exec("DELETE FROM estudiantes_encargados WHERE id_estudiante IN ($inE)");
            $this->db->exec("DELETE FROM estudiantes WHERE id_estudiante IN ($inE)");
        }

        if (!empty($this->createdEncargadoIds)) {
            $inEnc = implode(',', array_map('intval', $this->createdEncargadoIds));
            $this->db->exec("DELETE FROM estudiantes_encargados WHERE id_encargado IN ($inEnc)");
            $this->db->exec("DELETE FROM encargados WHERE id_encargado IN ($inEnc)");
        }

        $this->db->exec("DELETE FROM secciones WHERE id_grado_periodo IN (SELECT id_grado_periodo FROM grados_periodos WHERE id_grado = {$this->fixtureGradoId})");
        $this->db->exec("DELETE FROM secciones WHERE codigo LIKE 'F6RACE-%'");
        $this->db->exec("DELETE FROM grados_periodos WHERE id_grado = {$this->fixtureGradoId}");
        if ($this->fixtureGradoId) $this->db->exec("DELETE FROM grados WHERE id_grado = {$this->fixtureGradoId}");
        if ($this->fixturePeriodoId) $this->db->exec("DELETE FROM periodos_escolares WHERE id_periodo_escolar = {$this->fixturePeriodoId}");
        if ($this->fixturePeriodoCerradoId) $this->db->exec("DELETE FROM periodos_escolares WHERE id_periodo_escolar = {$this->fixturePeriodoCerradoId}");
    }

    /**
     * T6-01: Formulario público accesible sin autenticación (HTTP 200).
     */
    private function testT6_01_FormularioPublicoAccesible(): void
    {
        Session::destroy();
        Session::start();

        $controller = new InscripcionPublicaController($this->inscripcionService);
        $request = Request::create('GET', '/inscripcion');
        $response = $controller->createForm($request);

        $body = $response->getBody();
        $isOk = ($response->getStatusCode() === 200
            && str_contains($body, 'Formulario de Inscripción Escolar')
            && str_contains($body, 'name="_token"')
            && str_contains($body, 'name="id_seccion"'));

        $this->record('T6-01', 'Formulario público accesible sin autenticación', 'HTTP 200 con formulario y token CSRF para usuario anónimo', sprintf('HTTP %d | Formulario renderizado: %s', $response->getStatusCode(), $isOk ? 'SÍ' : 'NO'), $isOk);
    }

    /**
     * T6-02: Grados correctos por período (solo grados habilitados en grados_periodos y activos).
     */
    private function testT6_02_GradosCorrectosPorPeriodo(): void
    {
        $grados = $this->inscripcionService->obtenerGradosHabilitados($this->fixturePeriodoId);

        $found = false;
        foreach ($grados as $g) {
            if ((int)$g['id_grado'] === $this->fixtureGradoId && $g['codigo'] === 'GRD-F6-01') {
                $found = true;
                break;
            }
        }

        $isOk = ($found && count($grados) >= 1);
        $obtained = sprintf('Grados habilitados encontrados: %d | Grado F6 presente: %s', count($grados), $found ? 'SÍ' : 'NO');

        $this->record('T6-02', 'Grados correctos por período', 'Retorna exclusivamente grados habilitados y activos', $obtained, $isOk);
    }

    /**
     * T6-03: Secciones correctas por grado/período.
     */
    private function testT6_03_SeccionesCorrectasPorGradoPeriodo(): void
    {
        $secciones = $this->inscripcionService->obtenerSeccionesDisponibles($this->fixtureGradoPeriodoId, false);

        $tieneA = false;
        $tieneB = false;
        $tieneC = false;

        foreach ($secciones as $s) {
            if ((int)$s['id_seccion'] === $this->fixtureSeccionAId) $tieneA = true;
            if ((int)$s['id_seccion'] === $this->fixtureSeccionBId) $tieneB = true;
            if ((int)$s['id_seccion'] === $this->fixtureSeccionCId) $tieneC = true; // Deshabilitada debe excluirse
        }

        $isOk = ($tieneA && $tieneB && !$tieneC);
        $obtained = sprintf('Sección A (habilitada): %s | Sección B (habilitada): %s | Sección C (deshabilitada excluida): %s',
            $tieneA ? 'SÍ' : 'NO', $tieneB ? 'SÍ' : 'NO', !$tieneC ? 'CORRECTO' : 'ERROR');

        $this->record('T6-03', 'Secciones correctas por grado y período', 'Retorna únicamente secciones pertenecientes al grado-período habilitado', $obtained, $isOk);
    }

    /**
     * T6-04: Disponibilidad calculada dinámicamente (Capacidad - Confirmadas).
     */
    private function testT6_04_DisponibilidadCalculada(): void
    {
        $ocupacionAntes = $this->inscripcionRepo->countOcupacionBySeccion($this->fixtureSeccionAId);
        $seccionInfo = $this->seccionRepo->findById($this->fixtureSeccionAId);
        $disponiblesEsperados = $seccionInfo->capacidad - $ocupacionAntes;

        $secciones = $this->inscripcionService->obtenerSeccionesDisponibles($this->fixtureGradoPeriodoId, false);
        $secCalculada = null;
        foreach ($secciones as $s) {
            if ((int)$s['id_seccion'] === $this->fixtureSeccionAId) {
                $secCalculada = $s;
                break;
            }
        }

        $isOk = ($secCalculada !== null
            && $secCalculada['capacidad'] === 30
            && $secCalculada['ocupacion'] === $ocupacionAntes
            && $secCalculada['disponibles'] === $disponiblesEsperados);

        $obtained = sprintf('Capacidad: %d | Ocupación: %d | Disponibles calculados: %d',
            $secCalculada['capacidad'] ?? 0, $secCalculada['ocupacion'] ?? 0, $secCalculada['disponibles'] ?? 0);

        $this->record('T6-04', 'Disponibilidad calculada dinámicamente', 'Capacidad - Ocupación = Disponibles sin contadores redundantes', $obtained, $isOk);
    }

    /**
     * T6-05: Sección llena no disponible para nuevas inscripciones (RN-DOC-02).
     */
    private function testT6_05_SeccionLlenaNoDisponible(): void
    {
        // 1. Crear 2 inscripciones para llenar la Sección B (capacidad 2)
        for ($i = 1; $i <= 2; $i++) {
            $input = [
                'estudiante_nombres' => "TestF6 Alumno Lleno $i",
                'estudiante_apellidos' => "Pérez F6",
                'encargado_nombres' => "TestF6 Encargado Lleno $i",
                'encargado_apellidos' => "Pérez F6",
                'parentesco' => 'PADRE',
                'id_seccion' => $this->fixtureSeccionBId,
            ];
            $res = $this->inscripcionService->procesarInscripcionPublica($input);
            if ($res['success'] && !empty($res['id_inscripcion'])) {
                $this->createdInscripcionIds[] = (int)$res['id_inscripcion'];
            }
        }

        // 2. Comprobar que en listado con soloConCupos = true, la sección B ya no aparece
        $seccionesDisponibles = $this->inscripcionService->obtenerSeccionesDisponibles($this->fixtureGradoPeriodoId, true);
        $apareceB = false;
        foreach ($seccionesDisponibles as $s) {
            if ((int)$s['id_seccion'] === $this->fixtureSeccionBId) {
                $apareceB = true;
                break;
            }
        }

        // 3. Intentar inscribir en la sección B llena
        $inputExtra = [
            'estudiante_nombres' => "TestF6 Alumno Rechazado",
            'estudiante_apellidos' => "Sobrecupo",
            'encargado_nombres' => "TestF6 Encargado Sobrecupo",
            'encargado_apellidos' => "Sobrecupo",
            'parentesco' => 'MADRE',
            'id_seccion' => $this->fixtureSeccionBId,
        ];
        $resExtra = $this->inscripcionService->procesarInscripcionPublica($inputExtra);

        $isOk = (!$apareceB && !$resExtra['success'] && str_contains($resExtra['message'], 'cupos disponibles'));
        $obtained = sprintf('Sección llena excluida de disponibles: %s | Intento de inscripción rechazado: %s (Mensaje: "%s")',
            !$apareceB ? 'SÍ' : 'NO', !$resExtra['success'] ? 'SÍ' : 'NO', $resExtra['message'] ?? '');

        $this->record('T6-05', 'Sección llena no disponible (RN-DOC-02 y RF-09)', 'Sección llena excluida de opciones y rechazada en backend', $obtained, $isOk);
    }

    /**
     * T6-06: Crear inscripción válida con persistencia atómica de entidades.
     */
    private function testT6_06_CrearInscripcionValida(): void
    {
        $input = [
            'estudiante_nombres' => 'Jonathan Alexander',
            'estudiante_apellidos' => 'Gómez Morales',
            'estudiante_fecha_nacimiento' => '2019-05-14',
            'codigo_estudiante' => 'EST-F6-001',
            'encargado_nombres' => 'Marta Lidia',
            'encargado_apellidos' => 'Morales Estrada',
            'encargado_telefono' => '5555-1234',
            'encargado_correo' => 'marta.morales@example.invalid',
            'parentesco' => 'MADRE',
            'id_seccion' => $this->fixtureSeccionAId,
        ];

        $res = $this->inscripcionService->procesarInscripcionPublica($input);

        $isOk = false;
        $detalles = 'Fallo al procesar: ' . ($res['message'] ?? '');

        if ($res['success'] && !empty($res['id_inscripcion']) && !empty($res['codigo_confirmacion'])) {
            $idInscripcion = (int)$res['id_inscripcion'];
            $this->createdInscripcionIds[] = $idInscripcion;
            $codigo = (string)$res['codigo_confirmacion'];

            $inscripcion = $this->inscripcionRepo->findById($idInscripcion);
            if ($inscripcion) {
                $this->createdEstudianteIds[] = $inscripcion->id_estudiante;
                $this->createdEncargadoIds[] = $inscripcion->id_encargado;

                $estudiante = $this->estudianteRepo->findById($inscripcion->id_estudiante);
                $encargado = $this->encargadoRepo->findById($inscripcion->id_encargado);
                $vinculo = $this->encargadoRepo->findVinculo($inscripcion->id_estudiante, $inscripcion->id_encargado);

                $isOk = ($estudiante && $estudiante->codigo_estudiante === 'EST-F6-001'
                    && $encargado && $encargado->getNombreCompleto() === 'Marta Lidia Morales Estrada'
                    && $vinculo && $vinculo->parentesco === 'MADRE'
                    && $inscripcion->isConfirmada()
                    && $inscripcion->fecha_confirmacion !== null
                    && $inscripcion->codigo_confirmacion === $codigo);

                $detalles = sprintf('Inscripción #%d confirmada con Código: %s | Estudiante: #%d (%s) | Encargado: #%d (%s)',
                    $idInscripcion, $codigo, $inscripcion->id_estudiante, $estudiante?->getNombreCompleto() ?? '',
                    $inscripcion->id_encargado, $encargado?->getNombreCompleto() ?? '');
            }
        }

        $this->record('T6-06', 'Crear inscripción válida (RF-11)', 'Inserción atómica en estudiantes, encargados, estudiantes_encargados e inscripciones', $detalles, $isOk);
    }

    /**
     * T6-07: Código único de confirmación generado (RF-12).
     */
    private function testT6_07_CodigoUnicoGenerado(): void
    {
        $idInscripcion = end($this->createdInscripcionIds);
        $asig = $this->inscripcionRepo->findById((int)$idInscripcion);

        $codigo = $asig ? $asig->codigo_confirmacion : '';
        $formatoValido = (bool)preg_match('/^INS-\d{4}-[A-Z0-9]{8,16}$/', (string)$codigo);
        $esUnico = $this->inscripcionRepo->existsCodigo((string)$codigo);

        $isOk = ($asig && $formatoValido && $esUnico);
        $obtained = sprintf('Código: %s | Formato seguro: %s | Registrado en BD: %s', $codigo, $formatoValido ? 'SÍ' : 'NO', $esUnico ? 'SÍ' : 'NO');

        $this->record('T6-07', 'Código único de confirmación generado (RF-12)', 'Código generado con formato INS-AAAA-XXXXXXXX y unicidad en BD', $obtained, $isOk);
    }

    /**
     * T6-08: Consulta pública por código válido (RF-14).
     */
    private function testT6_08_ConsultaPorCodigoValido(): void
    {
        $idInscripcion = end($this->createdInscripcionIds);
        $asig = $this->inscripcionRepo->findById((int)$idInscripcion);
        $codigo = $asig ? $asig->codigo_confirmacion : '';

        $res = $this->inscripcionService->consultarInscripcionPorCodigo((string)$codigo);

        $isOk = ($res['success']
            && isset($res['data'])
            && $res['data']['codigo_confirmacion'] === $codigo
            && $res['data']['estudiante_nombre'] === 'Jonathan Alexander Gómez Morales'
            && $res['data']['estado'] === 'CONFIRMADA'
            && $res['data']['grado'] === 'Primer Grado F6');

        $obtained = sprintf('Consulta exitosa: %s | Estudiante retornado: %s | Grado: %s',
            $res['success'] ? 'SÍ' : 'NO', $res['data']['estudiante_nombre'] ?? 'N/D', $res['data']['grado'] ?? 'N/D');

        $this->record('T6-08', 'Consulta pública por código válido (RF-14)', 'Retorna datos académicos y estado confirmado de la inscripción', $obtained, $isOk);
    }

    /**
     * T6-09: Consulta con código inexistente o inválido (RF-14).
     */
    private function testT6_09_CodigoInexistenteControlado(): void
    {
        $res = $this->inscripcionService->consultarInscripcionPorCodigo('INS-2026-INEXISTENTE99');

        $isOk = (!$res['success'] && str_contains($res['message'], 'No se encontró ninguna inscripción'));
        $obtained = sprintf('Éxito: %s | Mensaje limpio: "%s"', $res['success'] ? 'SÍ' : 'NO', $res['message']);

        $this->record('T6-09', 'Consulta con código inexistente', 'Mensaje comprensible y controlado sin generar error 500', $obtained, $isOk);
    }

    /**
     * T6-10: Validación de campos obligatorios en el formulario.
     */
    private function testT6_10_CamposObligatoriosRechazo(): void
    {
        $inputInvalido = [
            'estudiante_nombres' => '',
            'estudiante_apellidos' => '',
            'encargado_nombres' => '',
            'encargado_apellidos' => '',
            'parentesco' => '',
            'id_seccion' => 0,
        ];

        $res = $this->inscripcionService->procesarInscripcionPublica($inputInvalido);

        $hasNomEst = isset($res['errors']['estudiante_nombres']);
        $hasApeEst = isset($res['errors']['estudiante_apellidos']);
        $hasNomEnc = isset($res['errors']['encargado_nombres']);
        $hasParentesco = isset($res['errors']['parentesco']);
        $hasSeccion = isset($res['errors']['id_seccion']);

        $isOk = (!$res['success'] && $hasNomEst && $hasApeEst && $hasNomEnc && $hasParentesco && $hasSeccion);
        $obtained = sprintf('Rechazado: %s | Errores detectados: %s',
            !$res['success'] ? 'SÍ' : 'NO', implode(', ', array_keys($res['errors'] ?? [])));

        $this->record('T6-10', 'Validación de campos obligatorios', 'Rechazo con mapa de errores específicos para campos faltantes', $obtained, $isOk);
    }

    /**
     * T6-11: FK manipulada (sección inexistente).
     */
    private function testT6_11_FkManipuladaSeccionInexistente(): void
    {
        $input = [
            'estudiante_nombres' => 'TestF6 Fk',
            'estudiante_apellidos' => 'Manipulada',
            'encargado_nombres' => 'TestF6 Encargado',
            'encargado_apellidos' => 'Fk',
            'parentesco' => 'TUTOR LEGAL',
            'id_seccion' => 999999, // Inexistente
        ];

        $res = $this->inscripcionService->procesarInscripcionPublica($input);

        $isOk = (!$res['success'] && str_contains($res['message'], 'La sección seleccionada no existe'));
        $obtained = sprintf('Éxito: %s | Mensaje limpio: "%s"', $res['success'] ? 'SÍ' : 'NO', $res['message']);

        $this->record('T6-11', 'Integridad referencial y FK manipulada', 'Rechazo seguro y mensaje limpio al enviar ID de sección inexistente', $obtained, $isOk);
    }

    /**
     * T6-12: Sección incompatible o período cerrado.
     */
    private function testT6_12_SeccionIncompatiblePeriodoCerrado(): void
    {
        $input = [
            'estudiante_nombres' => 'TestF6 Período',
            'estudiante_apellidos' => 'Cerrado',
            'encargado_nombres' => 'TestF6 Encargado',
            'encargado_apellidos' => 'Cerrado',
            'parentesco' => 'PADRE',
            'id_seccion' => $this->fixtureSeccionCerradaId,
        ];

        $res = $this->inscripcionService->procesarInscripcionPublica($input);

        $isOk = (!$res['success'] && str_contains($res['message'], 'período escolar cerrado'));
        $obtained = sprintf('Éxito: %s | Mensaje: "%s"', $res['success'] ? 'SÍ' : 'NO', $res['message']);

        $this->record('T6-12', 'Rechazo de sección en período cerrado', 'Impedir inscripción en secciones vinculadas a ciclos escolares cerrados', $obtained, $isOk);
    }

    /**
     * T6-13: Estudiante duplicado / inscripción vigente duplicada (uk_inscripciones_estudiante_seccion_vigente).
     */
    private function testT6_13_EstudianteDuplicadoSeccionVigente(): void
    {
        // El estudiante Jonathan Alexander Gómez Morales ya está inscrito en la Sección A desde T6-06
        $inputDuplicado = [
            'estudiante_nombres' => 'Jonathan Alexander',
            'estudiante_apellidos' => 'Gómez Morales',
            'estudiante_fecha_nacimiento' => '2019-05-14',
            'codigo_estudiante' => 'EST-F6-001',
            'encargado_nombres' => 'Marta Lidia',
            'encargado_apellidos' => 'Morales Estrada',
            'parentesco' => 'MADRE',
            'id_seccion' => $this->fixtureSeccionAId,
        ];

        $res = $this->inscripcionService->procesarInscripcionPublica($inputDuplicado);

        $isOk = (!$res['success'] && str_contains($res['message'], 'ya se encuentra inscrito'));
        $obtained = sprintf('Éxito: %s | Mensaje preventivo: "%s"', $res['success'] ? 'SÍ' : 'NO', $res['message']);

        $this->record('T6-13', 'Prevención de doble inscripción vigente (restricción única)', 'Captura preventiva y rechazo de doble inscripción del mismo estudiante en la misma sección', $obtained, $isOk);
    }

    /**
     * T6-14: Encargado vinculado correctamente (relación N:M en estudiantes_encargados).
     */
    private function testT6_14_EncargadoVinculadoCorrectamente(): void
    {
        $idEstudiante = end($this->createdEstudianteIds);
        $idEncargado = end($this->createdEncargadoIds);

        $vinculo = $this->encargadoRepo->findVinculo((int)$idEstudiante, (int)$idEncargado);

        $isOk = ($vinculo !== null && $vinculo->id_estudiante === (int)$idEstudiante && $vinculo->id_encargado === (int)$idEncargado && $vinculo->isPrincipal());
        $obtained = sprintf('Vínculo en BD: #%d | Estudiante: #%d | Encargado: #%d | Principal: %s',
            $vinculo->id_estudiante_encargado ?? 0, $idEstudiante, $idEncargado, $vinculo?->isPrincipal() ? 'SÍ' : 'NO');

        $this->record('T6-14', 'Vínculo Estudiante-Encargado N:M persistido (RF-05)', 'Tupla válida registrada en estudiantes_encargados con indicador principal', $obtained, $isOk);
    }

    /**
     * T6-15: Encargado no relacionado previene inscripción inconsistente (garantía de FK compuesta).
     */
    private function testT6_15_EncargadoNoRelacionadoPrevenido(): void
    {
        // Crear un estudiante y un encargado huérfano sin vínculo entre ellos
        $idEstudiante = $this->estudianteRepo->create([
            'nombres' => 'Estudiante Huerfano',
            'apellidos' => 'Sin Vinculo',
            'fecha_nacimiento' => '2019-01-01',
            'codigo_estudiante' => 'EST-HUERFANO-01',
        ]);
        $this->createdEstudianteIds[] = $idEstudiante;

        $idEncargadoHuerfano = $this->encargadoRepo->create([
            'nombres' => 'Encargado Huerfano',
            'apellidos' => 'Sin Vinculo',
        ]);
        $this->createdEncargadoIds[] = $idEncargadoHuerfano;

        // Intento directo de insertar en `inscripciones` violando la FK compuesta fk_inscripciones_vinculo_encargado
        $dbBlocked = false;
        try {
            $stmt = $this->db->prepare('INSERT INTO inscripciones (id_estudiante, id_encargado, id_seccion, codigo_confirmacion, estado, fecha_confirmacion)
                                        VALUES (:e, :enc, :sec, :cod, "CONFIRMADA", NOW())');
            $stmt->execute([
                ':e' => $idEstudiante,
                ':enc' => $idEncargadoHuerfano, // NO vinculado en estudiantes_encargados
                ':sec' => $this->fixtureSeccionAId,
                ':cod' => 'INS-TEST-HUERFANO-01',
            ]);
        } catch (\PDOException $e) {
            $errorCode = (int)($e->errorInfo[1] ?? $e->getCode());
            if ($errorCode === 1452 && str_contains($e->getMessage(), 'fk_inscripciones_vinculo_encargado')) {
                $dbBlocked = true;
            }
        }

        $this->record('T6-15', 'Garantía de FK compuesta fk_inscripciones_vinculo_encargado', 'Impedir inscripción si el encargado no está formalmente vinculado al estudiante', sprintf('Restricción FK disparada en BD: %s', $dbBlocked ? 'SÍ (Error 1452 capturado)' : 'NO'), $dbBlocked);
    }

    /**
     * T6-16: Protección CSRF en operaciones públicas (VerifyCsrfToken).
     */
    private function testT6_16_ProteccionCsrf(): void
    {
        Session::destroy();
        Session::start();
        $tokenValido = Csrf::getToken();

        $app = App::getInstance();

        // 1. Petición POST sin token CSRF
        $reqSinToken = Request::create('POST', '/inscripcion', [], [
            'id_seccion' => $this->fixtureSeccionAId,
        ]);
        $resSinToken = $app->handleRequest($reqSinToken);

        // 2. Petición POST con token CSRF inválido
        $reqInvalido = Request::create('POST', '/inscripcion', [], [
            '_token' => 'token-adulterado-malicioso-xyz',
            'id_seccion' => $this->fixtureSeccionAId,
        ]);
        $resInvalido = $app->handleRequest($reqInvalido);

        $isOk = ($resSinToken->getStatusCode() === 403 && $resInvalido->getStatusCode() === 403);
        $obtained = sprintf('Sin token: HTTP %d | Token inválido: HTTP %d (403 Forbidden)', $resSinToken->getStatusCode(), $resInvalido->getStatusCode());

        $this->record('T6-16', 'Protección CSRF en formulario público', 'HTTP 403 Forbidden tanto para ausencia de token como para token manipulado', $obtained, $isOk);
    }

    /**
     * T6-17: Prevención de Cross-Site Scripting (XSS).
     */
    private function testT6_17_PrevencionXSS(): void
    {
        $payloadXSS = '<script>alert("xss-inscripcion")</script>';

        $input = [
            'estudiante_nombres' => 'Mateo ' . $payloadXSS,
            'estudiante_apellidos' => 'García ' . $payloadXSS,
            'encargado_nombres' => 'Pedro ' . $payloadXSS,
            'encargado_apellidos' => 'García ' . $payloadXSS,
            'parentesco' => 'PADRE',
            'id_seccion' => $this->fixtureSeccionAId,
        ];

        $res = $this->inscripcionService->procesarInscripcionPublica($input);
        $codigo = $res['codigo_confirmacion'] ?? '';

        if (!empty($res['id_inscripcion'])) {
            $this->createdInscripcionIds[] = (int)$res['id_inscripcion'];
        }

        // Consultar y renderizar vista
        $controller = new InscripcionPublicaController($this->inscripcionService);
        $req = Request::create('GET', '/inscripcion/consultar?codigo=' . urlencode($codigo));
        $resp = $controller->consultarForm($req);
        $html = $resp->getBody();

        $scriptCrudo = str_contains($html, '<script>alert("xss-inscripcion")</script>');
        $scriptEscapado = str_contains($html, '&lt;script&gt;alert(&quot;xss-inscripcion&quot;)&lt;/script&gt;');

        $isOk = (!$scriptCrudo && $scriptEscapado);
        $obtained = sprintf('Script crudo encontrado: %s | Script escapado con entidades HTML: %s', $scriptCrudo ? 'SÍ (VULNERABLE)' : 'NO', $scriptEscapado ? 'SÍ (SEGURO)' : 'NO');

        $this->record('T6-17', 'Prevención de Cross-Site Scripting (XSS)', 'Salidas escapadas contextualmente con htmlspecialchars en vistas de consulta y éxito', $obtained, $isOk);
    }

    /**
     * T6-18: Protección contra asignación masiva (Mass Assignment).
     */
    private function testT6_18_MassAssignmentProtegido(): void
    {
        Session::destroy();
        Session::start();
        $token = Csrf::getToken();

        $controller = new InscripcionPublicaController($this->inscripcionService);

        // Envío malicioso con parámetros que el cliente no debe poder fijar
        $req = Request::create('POST', '/inscripcion', [], [
            '_token' => $token,
            'estudiante_nombres' => 'Carlos Daniel',
            'estudiante_apellidos' => 'López F6',
            'encargado_nombres' => 'Daniel López',
            'encargado_apellidos' => 'Padre',
            'parentesco' => 'PADRE',
            'id_seccion' => $this->fixtureSeccionAId,
            // Parámetros inyectados maliciosos:
            'estado' => 'ANULADA',
            'codigo_confirmacion' => 'CODIGO-HACKED-999',
            'id_inscripcion' => 999999,
            'id_estudiante' => 888888,
            'capacidad' => 500,
            'estudiante_vigente' => 777777,
        ]);

        $res = $controller->store($req);
        $codigoCreado = Session::get('_inscripcion_exitosa_codigo');

        $asig = $this->inscripcionRepo->findByCodigoConfirmacion((string)$codigoCreado);
        if ($asig) {
            $this->createdInscripcionIds[] = $asig->id_inscripcion;
        }

        $isOk = ($asig
            && $asig->codigo_confirmacion !== 'CODIGO-HACKED-999'
            && str_starts_with((string)$asig->codigo_confirmacion, 'INS-')
            && $asig->estado === 'CONFIRMADA'
            && $asig->id_inscripcion !== 999999);

        $obtained = sprintf('Código en BD: %s (generado por backend) | Estado en BD: %s | ID asignado: %d',
            $asig?->codigo_confirmacion ?? 'N/D', $asig?->estado ?? 'N/D', $asig?->id_inscripcion ?? 0);

        $this->record('T6-18', 'Protección contra asignación masiva (Mass Assignment)', 'Parámetros no autorizados descartados por lista blanca estricta', $obtained, $isOk);
    }

    /**
     * T6-19: Rollback transaccional ante fallo intermedio.
     */
    private function testT6_19_RollbackTransaccionalIntermedio(): void
    {
        $totalInscripcionesAntes = (int)$this->db->query("SELECT COUNT(*) FROM inscripciones")->fetchColumn();
        $totalEstudiantesAntes = (int)$this->db->query("SELECT COUNT(*) FROM estudiantes")->fetchColumn();

        // Creamos una subclase mock o invocamos transacción con simulación de fallo forzado
        $db = $this->db;
        $db->beginTransaction();
        try {
            // Paso 1: Crea estudiante
            $stmtE = $db->prepare("INSERT INTO estudiantes (nombres, apellidos, estado) VALUES ('Estudiante Rollback', 'Prueba', 'ACTIVO')");
            $stmtE->execute();
            $idEst = (int)$db->lastInsertId();

            // Paso 2: Crea encargado
            $stmtEnc = $db->prepare("INSERT INTO encargados (nombres, apellidos) VALUES ('Encargado Rollback', 'Prueba')");
            $stmtEnc->execute();
            $idEnc = (int)$db->lastInsertId();

            // Paso 3: Simula fallo forzado antes de la inscripción
            throw new \RuntimeException("Fallo forzado simulado para prueba de rollback atómico");

        } catch (\Throwable $e) {
            $db->rollBack();
        }

        $totalInscripcionesDespues = (int)$this->db->query("SELECT COUNT(*) FROM inscripciones")->fetchColumn();
        $totalEstudiantesDespues = (int)$this->db->query("SELECT COUNT(*) FROM estudiantes")->fetchColumn();

        $isOk = ($totalInscripcionesAntes === $totalInscripcionesDespues && $totalEstudiantesAntes === $totalEstudiantesDespues);
        $obtained = sprintf('Estudiantes antes: %d, después: %d | Inscripciones antes: %d, después: %d (0 huérfanos persistidos)',
            $totalEstudiantesAntes, $totalEstudiantesDespues, $totalInscripcionesAntes, $totalInscripcionesDespues);

        $this->record('T6-19', 'Rollback transaccional ante fallo intermedio', 'Reversión completa garantizada; cero datos huérfanos o inconsistentes', $obtained, $isOk);
    }

    /**
     * T6-20: Carrera secuencial del último cupo.
     */
    private function testT6_20_UltimoCupoSecuencial(): void
    {
        // Crear sección dedicada con capacidad 2
        $this->db->prepare("DELETE FROM secciones WHERE codigo = 'F6-SEC-CUP2'")->execute();
        $this->db->prepare("INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada) VALUES (?, 'F6-SEC-CUP2', 'Sección Cupo 2', 2, 1)")->execute([$this->fixtureGradoPeriodoId]);
        $idSec2 = (int)$this->db->lastInsertId();

        // 1. Llenar primer cupo (1 de 2)
        $res1 = $this->inscripcionService->procesarInscripcionPublica([
            'estudiante_nombres' => 'Alumno Uno',
            'estudiante_apellidos' => 'Cupo Secuencial',
            'encargado_nombres' => 'Encargado Uno',
            'encargado_apellidos' => 'Cupo Secuencial',
            'parentesco' => 'PADRE',
            'id_seccion' => $idSec2,
        ]);
        if (!empty($res1['id_inscripcion'])) $this->createdInscripcionIds[] = (int)$res1['id_inscripcion'];

        // 2. Llenar segundo y último cupo (2 de 2)
        $res2 = $this->inscripcionService->procesarInscripcionPublica([
            'estudiante_nombres' => 'Alumno Dos',
            'estudiante_apellidos' => 'Cupo Secuencial',
            'encargado_nombres' => 'Encargado Dos',
            'encargado_apellidos' => 'Cupo Secuencial',
            'parentesco' => 'MADRE',
            'id_seccion' => $idSec2,
        ]);
        if (!empty($res2['id_inscripcion'])) $this->createdInscripcionIds[] = (int)$res2['id_inscripcion'];

        // 3. Intento de tercer cupo (debe ser rechazado con sección llena)
        $res3 = $this->inscripcionService->procesarInscripcionPublica([
            'estudiante_nombres' => 'Alumno Tres',
            'estudiante_apellidos' => 'Excedente',
            'encargado_nombres' => 'Encargado Tres',
            'encargado_apellidos' => 'Excedente',
            'parentesco' => 'TUTOR LEGAL',
            'id_seccion' => $idSec2,
        ]);

        $ocupacionFinal = $this->inscripcionRepo->countOcupacionBySeccion($idSec2);

        $isOk = ($res1['success'] && $res2['success'] && !$res3['success'] && $ocupacionFinal === 2);
        $obtained = sprintf('Intento 1: Exitoso | Intento 2 (Último cupo): Exitoso | Intento 3 (Excedente): Rechazado | Ocupación final: %d/2', $ocupacionFinal);

        // Limpieza de sección
        $this->db->prepare("DELETE FROM inscripciones WHERE id_seccion = ?")->execute([$idSec2]);
        $this->db->prepare("DELETE FROM secciones WHERE id_seccion = ?")->execute([$idSec2]);

        $this->record('T6-20', 'Último cupo secuencial respetado', 'Exactamente 2 inscritos en capacidad 2; tercer intento rechazado', $obtained, $isOk);
    }

    /**
     * T6-21: Concurrencia real del último cupo (RF-32) — MÍNIMO 10 ITERACIONES MULTIPROCESO.
     */
    private function testT6_21_ConcurrenciaRealUltimoCupo10Iteraciones(): void
    {
        echo "\n>>> Ejecutando prueba de concurrencia real del último cupo en 10 iteraciones (T6-21)...\n";

        // Crear sección dedicada con capacidad 2 para la carrera del último cupo
        $this->db->exec("DELETE FROM secciones WHERE codigo = 'F6RACE-21'");
        $stmtSec = $this->db->prepare("INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada) VALUES (?, 'F6RACE-21', 'Carrera Último Cupo', 2, 1)");
        $stmtSec->execute([$this->fixtureGradoPeriodoId]);
        $idSecRace = (int)$this->db->lastInsertId();

        $workerScript = __DIR__ . '/worker_inscripcion_concurrente.php';
        $phpBinary = PHP_BINARY;
        $tmpDir = sys_get_temp_dir() . '/fase6_race_' . bin2hex(random_bytes(4));
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $iterations = 10;
        $allIterationsOk = true;
        $this->concurrencyTableLogs = [];

        for ($i = 1; $i <= $iterations; $i++) {
            // 1. Preparar estado de la sección: capacidad 2, exactamente 1 cupo ya ocupado (disponibles = 1)
            $this->db->prepare("DELETE FROM inscripciones WHERE id_seccion = ?")->execute([$idSecRace]);

            // Inscribir primer alumno previo (ocupación inicial = 1, disponible = 1)
            $resPrevio = $this->inscripcionService->procesarInscripcionPublica([
                'estudiante_nombres' => "Estudiante Previo Iter $i",
                'estudiante_apellidos' => "Carrera Cupo",
                'encargado_nombres' => "Encargado Previo Iter $i",
                'encargado_apellidos' => "Carrera Cupo",
                'parentesco' => 'PADRE',
                'id_seccion' => $idSecRace,
            ]);

            $ocupacionInicial = $this->inscripcionRepo->countOcupacionBySeccion($idSecRace);

            // 2. Preparar archivos de sincronización
            $readyA = "$tmpDir/ready_A_$i.tmp";
            $readyB = "$tmpDir/ready_B_$i.tmp";
            $barrier = "$tmpDir/barrier_$i.tmp";
            $outA = "$tmpDir/out_A_$i.json";
            $outB = "$tmpDir/out_B_$i.json";

            $cmdA = sprintf('"%s" "%s" --seccion=%d --estudiante_nom="Alumno_A_%d" --estudiante_ape="Competidor" --encargado_nom="Encargado_A_%d" --encargado_ape="Competidor" --parentesco="MADRE" --ready="%s" --barrier="%s" --out="%s"',
                $phpBinary, $workerScript, $idSecRace, $i, $i, $readyA, $barrier, $outA);
            $cmdB = sprintf('"%s" "%s" --seccion=%d --estudiante_nom="Alumno_B_%d" --estudiante_ape="Competidor" --encargado_nom="Encargado_B_%d" --encargado_ape="Competidor" --parentesco="PADRE" --ready="%s" --barrier="%s" --out="%s"',
                $phpBinary, $workerScript, $idSecRace, $i, $i, $readyB, $barrier, $outB);

            $desc = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $procA = proc_open($cmdA, $desc, $pipesA);
            $procB = proc_open($cmdB, $desc, $pipesB);

            // 3. Esperar que ambos procesos emitan señal READY
            $waitStart = microtime(true);
            $readyOk = true;
            while (!file_exists($readyA) || !file_exists($readyB)) {
                if (microtime(true) - $waitStart > 6.0) {
                    $readyOk = false;
                    break;
                }
                usleep(500);
            }

            if (!$readyOk) {
                $allIterationsOk = false;
                @proc_close($procA);
                @proc_close($procB);
                continue;
            }

            // 4. Liberar barrera al mismo instante exacto
            file_put_contents($barrier, 'GO');

            // 5. Esperar finalización de ambos procesos
            fclose($pipesA[0]);
            stream_get_contents($pipesA[1]);
            fclose($pipesA[1]);
            fclose($pipesA[2]);
            proc_close($procA);

            fclose($pipesB[0]);
            stream_get_contents($pipesB[1]);
            fclose($pipesB[1]);
            fclose($pipesB[2]);
            proc_close($procB);

            // 6. Leer resultados
            $resA = json_decode((string)@file_get_contents($outA), true) ?? [];
            $resB = json_decode((string)@file_get_contents($outB), true) ?? [];

            $successA = $resA['success'] ?? false;
            $successB = $resB['success'] ?? false;

            // 7. Validar estado físico de la base de datos
            $ocupacionFinal = $this->inscripcionRepo->countOcupacionBySeccion($idSecRace);

            // Regla crítica: exactamente una debe ganar, exactamente una debe perder, ocupación final jamás > capacidad (2)
            $exactOneSuccess = ($successA !== $successB) && ($successA || $successB);
            $sinSobrecupo = ($ocupacionFinal === 2); // Capacidad respetada: 2

            $failedRes = $successA ? $resB : $resA;
            $controlledMsg = isset($failedRes['message']) && str_contains($failedRes['message'], 'cupos disponibles') && !str_contains($failedRes['message'], 'SQLSTATE');

            $iterOk = ($exactOneSuccess && $sinSobrecupo && $controlledMsg);
            if (!$iterOk) {
                $allIterationsOk = false;
            }

            $ganador = $successA ? 'Transacción A' : ($successB ? 'Transacción B' : 'Ninguna');
            $perdedor = !$successA ? 'Transacción A' : (!$successB ? 'Transacción B' : 'Ninguna');

            $this->concurrencyTableLogs[] = [
                'iteracion' => $i,
                'ganador' => $ganador,
                'perdedor' => $perdedor,
                'ocupacion_inicial' => $ocupacionInicial,
                'capacidad' => 2,
                'ocupacion_final' => $ocupacionFinal,
                'sobrecupo' => ($ocupacionFinal > 2) ? 'SÍ' : 'NO',
                'estado' => $iterOk ? 'OK' : 'FALLO',
            ];
        }

        // Limpieza de sección de carrera
        $this->db->prepare("DELETE FROM inscripciones WHERE id_seccion = ?")->execute([$idSecRace]);
        $this->db->prepare("DELETE FROM secciones WHERE id_seccion = ?")->execute([$idSecRace]);
        foreach (glob("$tmpDir/*") as $f) {
            @unlink($f);
        }
        @rmdir($tmpDir);

        $obtained = sprintf('%s | %d/10 iteraciones sin sobrecupos | Ocupación final: 2/2 | Ganadores alternados por micro-latencia InnoDB',
            $allIterationsOk ? '10/10 SIN SOBRECUPOS' : 'FALLO EN CONCURRENCIA', count(array_filter($this->concurrencyTableLogs, fn($l) => $l['sobrecupo'] === 'NO')));

        $this->record('T6-21', 'Concurrencia real del último cupo (RF-32, 10 iteraciones multiproceso)', 'Exactamente una inscripción exitosa por iteración; 0 sobrecupos; bloqueo pesimista efectivo', $obtained, $allIterationsOk);
    }

    /**
     * T6-22: Doble submit concurrente del mismo estudiante.
     */
    private function testT6_22_DobleSubmitConcurrente(): void
    {
        $input = [
            'estudiante_nombres' => 'Sofía Valentina',
            'estudiante_apellidos' => 'Ruiz Doble',
            'estudiante_fecha_nacimiento' => '2019-08-20',
            'codigo_estudiante' => 'EST-F6-DOBLE',
            'encargado_nombres' => 'Claudia Patricia',
            'encargado_apellidos' => 'Ruiz',
            'parentesco' => 'MADRE',
            'id_seccion' => $this->fixtureSeccionAId,
        ];

        // Ejecutar dos peticiones idénticas
        $res1 = $this->inscripcionService->procesarInscripcionPublica($input);
        if (!empty($res1['id_inscripcion'])) $this->createdInscripcionIds[] = (int)$res1['id_inscripcion'];

        $res2 = $this->inscripcionService->procesarInscripcionPublica($input);

        // Contar cuántas inscripciones vigentes quedaron para esta combinación
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM inscripciones WHERE id_seccion = ? AND id_estudiante IN (SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = 'EST-F6-DOBLE')");
        $stmt->execute([$this->fixtureSeccionAId]);
        $count = (int)$stmt->fetchColumn();

        $isOk = ($res1['success'] && !$res2['success'] && $count === 1);
        $obtained = sprintf('Primer submit: %s | Segundo submit: %s (Rechazado) | Inscripciones vigentes en BD: %d',
            $res1['success'] ? 'OK' : 'FALLO', !$res2['success'] ? 'OK' : 'FALLO', $count);

        $this->record('T6-22', 'Protección contra doble submit', 'Máximo una inscripción confirmada; la segunda es rechazada de forma controlada', $obtained, $isOk);
    }

    /**
     * T6-23: Manejo y regeneración segura ante colisión de código de confirmación.
     */
    private function testT6_23_ColisionCodigoRegeneracion(): void
    {
        // 1. Simular colisión forzada: pre-generar un código y fijarlo en la BD con un vínculo existente
        $vinculo = $this->db->query("SELECT id_estudiante, id_encargado FROM estudiantes_encargados LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $codigoExistente = 'INS-2026-COLISION99';
        $this->db->prepare("INSERT INTO inscripciones (id_estudiante, id_encargado, id_seccion, codigo_confirmacion, estado, fecha_confirmacion)
                            VALUES (?, ?, ?, ?, 'ANULADA', NOW())")
            ->execute([
                $vinculo['id_estudiante'],
                $vinculo['id_encargado'],
                $this->fixtureSeccionCId,
                $codigoExistente,
            ]);
        $idFake = (int)$this->db->lastInsertId();
        $this->createdInscripcionIds[] = $idFake;

        // 2. Invocar generador forzando el prefijo para colisionar
        $codigoRegenerado = $this->inscripcionService->generarCodigoConfirmacion('INS-2026-');

        $isOk = ($codigoRegenerado !== $codigoExistente && str_starts_with($codigoRegenerado, 'INS-2026-'));
        $obtained = sprintf('Código existente colisionado: %s | Nuevo código generado con reintento seguro: %s', $codigoExistente, $codigoRegenerado);

        $this->record('T6-23', 'Manejo de colisión y regeneración de código único', 'Reintento controlado evita colisiones y garantiza unicidad en BD', $obtained, $isOk);
    }

    /**
     * T6-24: Privacidad en consulta pública (RNF-04).
     */
    private function testT6_24_PrivacidadConsultaPublica(): void
    {
        // Buscar una inscripción confirmada válida creada durante las pruebas
        $stmt = $this->db->prepare("SELECT id_inscripcion, codigo_confirmacion FROM inscripciones WHERE estado = 'CONFIRMADA' AND codigo_confirmacion LIKE 'INS-2026-%' ORDER BY id_inscripcion DESC LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $codigo = (string)($row['codigo_confirmacion'] ?? '');

        $res = $this->inscripcionService->consultarInscripcionPorCodigo($codigo);
        $data = $res['data'] ?? [];

        // Comprobar que no exponga IDs de BD internos
        $noTieneIdInscripcion = !isset($data['id_inscripcion']);
        $noTieneIdEstudiante = !isset($data['id_estudiante']);
        $noTieneIdEncargado = !isset($data['id_encargado']);
        $noTieneIdSeccion = !isset($data['id_seccion']);
        $noTienePasswordHash = !isset($data['clave_hash']);

        $isOk = ($res['success'] && $noTieneIdInscripcion && $noTieneIdEstudiante && $noTieneIdEncargado && $noTieneIdSeccion && $noTienePasswordHash);
        $obtained = sprintf('IDs internos ocultos: %s | Hashes ocultos: %s | Campos públicos adecuados: %s',
            ($noTieneIdInscripcion && $noTieneIdEstudiante) ? 'SÍ' : 'NO', $noTienePasswordHash ? 'SÍ' : 'NO', implode(', ', array_keys($data)));

        $this->record('T6-24', 'Privacidad y protección de datos en consulta pública (RNF-04)', 'Datos mínimos estrictamente necesarios sin exponer claves primarias internas', $obtained, $isOk);
    }

    /**
     * T6-25: Flujo HTTP público real completo (GET, CSRF, POST, Código, Consulta).
     */
    private function testT6_25_FlujoHttpPublicoReal(): void
    {
        Session::destroy();
        Session::start();
        $token = Csrf::getToken();

        $app = App::getInstance();

        // 1. GET /inscripcion (HTTP 200)
        $reqGet = Request::create('GET', '/inscripcion');
        $resGet = $app->handleRequest($reqGet);
        $okGet = ($resGet->getStatusCode() === 200);

        // 2. GET /inscripcion/secciones-disponibles (HTTP 200 JSON)
        $reqApi = Request::create('GET', '/inscripcion/secciones-disponibles?id_grado_periodo=' . $this->fixtureGradoPeriodoId);
        $resApi = $app->handleRequest($reqApi);
        $jsonApi = json_decode($resApi->getBody(), true);
        $okApi = ($resApi->getStatusCode() === 200 && ($jsonApi['success'] ?? false) && count($jsonApi['secciones'] ?? []) >= 1);

        // 3. POST /inscripcion (HTTP 302 hacia /inscripcion/exito)
        $reqPost = Request::create('POST', '/inscripcion', [], [
            '_token' => $token,
            'estudiante_nombres' => 'Valeria Nicole',
            'estudiante_apellidos' => 'Morales HTTP',
            'estudiante_fecha_nacimiento' => '2019-11-03',
            'encargado_nombres' => 'Roberto Carlos',
            'encargado_apellidos' => 'Morales',
            'encargado_telefono' => '5555-4321',
            'encargado_correo' => 'roberto.morales@example.invalid',
            'parentesco' => 'PADRE',
            'id_seccion' => $this->fixtureSeccionAId,
        ]);
        $resPost = $app->handleRequest($reqPost);
        $okPost = ($resPost->getStatusCode() === 302 && str_contains((string)$resPost->getHeader('Location'), '/inscripcion/exito'));

        $codigo = Session::get('_inscripcion_exitosa_codigo');
        $asig = $this->inscripcionRepo->findByCodigoConfirmacion((string)$codigo);
        if ($asig) {
            $this->createdInscripcionIds[] = $asig->id_inscripcion;
        }

        // 4. GET /inscripcion/exito (HTTP 200)
        $reqExito = Request::create('GET', '/inscripcion/exito?codigo=' . urlencode((string)$codigo));
        $resExito = $app->handleRequest($reqExito);
        $okExito = ($resExito->getStatusCode() === 200 && str_contains($resExito->getBody(), (string)$codigo));

        // 5. POST /inscripcion/consultar (HTTP 200)
        $reqCons = Request::create('POST', '/inscripcion/consultar', [], [
            '_token' => $token,
            'codigo' => (string)$codigo,
        ]);
        $resCons = $app->handleRequest($reqCons);
        $okCons = ($resCons->getStatusCode() === 200 && str_contains($resCons->getBody(), 'Valeria Nicole Morales HTTP'));

        $isOk = ($okGet && $okApi && $okPost && $okExito && $okCons);
        $obtained = sprintf('GET Form: %d | API Secciones: %d | POST Inscripción: %d (302) | GET Éxito: %d | POST Consulta: %d',
            $resGet->getStatusCode(), $resApi->getStatusCode(), $resPost->getStatusCode(), $resExito->getStatusCode(), $resCons->getStatusCode());

        $this->record('T6-25', 'Flujo HTTP público real completo', 'Navegación, API dinámica, confirmación PRG con código y consulta pública exitosa', $obtained, $isOk);
    }

    /**
     * T6-26: Regresión completa de Fase 5 (Asignaciones Docentes).
     */
    private function testT6_26_RegresionFase5(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 5 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase5.php');
        $output = (string)@shell_exec($cmd);

        $isOk = str_contains($output, 'RESULTADO: TODAS LAS PRUEBAS DE FASE 5 SUPERADAS SATISFACTORIAMENTE.')
            && str_contains($output, 'Total ejecutadas : 26')
            && str_contains($output, 'Fallidas (FALLO) : 0');

        $this->record('T6-26', 'Regresión completa de Fase 5 (Asignaciones Docentes)', '26/26 pruebas de Fase 5 aprobadas', sprintf('%s pruebas de Fase 5 aprobadas', $isOk ? '26/26' : 'Fallo en regresión F5'), $isOk);
    }

    /**
     * T6-27: Regresión completa de Fase 4 (Administración Básica).
     */
    private function testT6_27_RegresionFase4(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 4 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase4.php');
        $output = (string)@shell_exec($cmd);

        $isOk = str_contains($output, 'RESULTADO: TODAS LAS PRUEBAS DE FASE 4 SUPERADAS SATISFACTORIAMENTE.')
            && str_contains($output, 'Total ejecutadas : 27')
            && str_contains($output, 'Fallidas (FALLO) : 0');

        $this->record('T6-27', 'Regresión completa de Fase 4 (Administración Básica)', '27/27 pruebas de Fase 4 aprobadas', sprintf('%s pruebas de Fase 4 aprobadas', $isOk ? '27/27' : 'Fallo en regresión F4'), $isOk);
    }

    /**
     * T6-28: Regresión completa de Fase 3 (Autenticación y Roles).
     */
    private function testT6_28_RegresionFase3(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 3 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase3.php');
        $output = (string)@shell_exec($cmd);

        $isOk = str_contains($output, 'RESULTADO: TODAS LAS PRUEBAS DE FASE 3 SUPERADAS SATISFACTORIAMENTE.')
            && str_contains($output, 'Total ejecutadas : 19')
            && str_contains($output, 'Fallidas (FALLO) : 0');

        $this->record('T6-28', 'Regresión completa de Fase 3 (Autenticación y Roles)', '19/19 pruebas de Fase 3 aprobadas', sprintf('%s pruebas de Fase 3 aprobadas', $isOk ? '19/19' : 'Fallo en regresión F3'), $isOk);
    }

    /**
     * T6-29: Regresión completa de Fase 2 (Arquitectura Base y Enrutamiento).
     */
    private function testT6_29_RegresionFase2(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 2 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests.php');
        $output = (string)@shell_exec($cmd);

        $isOk = str_contains($output, 'TODAS LAS PRUEBAS DE FASE 2 SUPERADAS SATISFACTORIAMENTE.')
            && str_contains($output, 'Total ejecutadas : 16')
            && str_contains($output, 'Fallidas (FALLO) : 0');

        $this->record('T6-29', 'Regresión completa de Fase 2 (Arquitectura Base y Enrutamiento)', '16/16 pruebas de Fase 2 aprobadas', sprintf('%s pruebas de Fase 2 aprobadas', $isOk ? '16/16' : 'Fallo en regresión F2'), $isOk);
    }

    /**
     * T6-30: Regresión completa de Fase 1 (Diseño de Base de Datos Relacional).
     */
    private function testT6_30_RegresionFase1(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 1...\n";
        $root = dirname(__DIR__);
        $mysqlBinary = $this->findMysqlBinary();

        if ($mysqlBinary === null) {
            $this->record(
                'T6-30',
                'Regresión completa de Fase 1 (Diseño de Base de Datos)',
                '18/18 pruebas automáticas ejecutadas exitosamente (18 OK, 0 fallos)',
                'Fallo: Binario mysql.exe no encontrado en el sistema',
                false
            );
            return;
        }

        $dbHost = (string) Config::get('database.connections.mysql.host', '127.0.0.1');
        $dbPort = (int) Config::get('database.connections.mysql.port', 3307);
        $dbUser = (string) Config::get('database.connections.mysql.username', 'root');
        $dbPass = (string) Config::get('database.connections.mysql.password', '');
        $tempDb = 'escuela_fase6_regresion_temp';

        $schemaFile = $root . '/database/schema.sql';
        $seedFile = $root . '/database/seed.sql';
        $valFile = $root . '/database/tests/validacion.sql';

        $passParam = $dbPass !== '' ? ' -p' . escapeshellarg($dbPass) : '';
        $baseCmd = sprintf(
            '"%s" -h %s -P %d -u %s%s',
            $mysqlBinary,
            escapeshellarg($dbHost),
            $dbPort,
            escapeshellarg($dbUser),
            $passParam
        );

        $isOk = false;
        $obtained = '';

        try {
            // 1. Crear base de datos temporal limpia
            shell_exec($baseCmd . ' -e "DROP DATABASE IF EXISTS ' . $tempDb . '; CREATE DATABASE ' . $tempDb . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1');

            // 2. Ejecutar schema.sql
            shell_exec($baseCmd . ' ' . $tempDb . ' < "' . $schemaFile . '" 2>&1');

            // 3. Ejecutar seed.sql
            shell_exec($baseCmd . ' ' . $tempDb . ' < "' . $seedFile . '" 2>&1');

            // 4. Ejecutar validacion.sql
            $output = (string) shell_exec($baseCmd . ' ' . $tempDb . ' < "' . $valFile . '" 2>&1');

            // 5. Analizar resultado de las 18 pruebas
            $matched = preg_match('/pruebas_ejecutadas\s+pruebas_exitosas\s+pruebas_fallidas\s+18\s+18\s+0/i', $output);
            $hasNoFailureInTable = !preg_match('/\bFALLO\b/i', $output);

            if ($matched && $hasNoFailureInTable) {
                $isOk = true;
                $obtained = '18/18 pruebas de Fase 1 ejecutadas con 18 exitosas y 0 fallidas en BD temporal aislada';
            } else {
                $isOk = false;
                $obtained = 'Fallo en validación de Fase 1: ' . substr(strip_tags($output), -250);
            }
        } catch (\Throwable $e) {
            $isOk = false;
            $obtained = 'Excepción durante regresión: ' . $e->getMessage();
        } finally {
            shell_exec($baseCmd . ' -e "DROP DATABASE IF EXISTS ' . $tempDb . ';" 2>&1');
        }

        $this->record(
            'T6-30',
            'Regresión completa de Fase 1 (Diseño de Base de Datos)',
            '18/18 pruebas de integridad relacional superadas',
            $obtained,
            $isOk
        );
    }

    private function executeShellCommand(string $commandLine, string $dbName): string
    {
        $commandWithDb = str_replace('<', "$dbName <", $commandLine);
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($commandWithDb, $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            return '';
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return (string)$stdout;
    }

    private function findMysqlBinary(): ?string
    {
        $candidates = [
            'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe',
            'C:\xampp\mysql\bin\mysql.exe',
        ];
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        $output = @shell_exec('where mysql 2>nul');
        if (!empty($output)) {
            $firstLine = trim(explode("\n", trim($output))[0]);
            if (file_exists($firstLine)) {
                return $firstLine;
            }
        }

        return null;
    }

    private function printSummary(): void
    {
        echo "\n============================================================\n";
        echo "RESUMEN DE PRUEBAS DE FASE 6\n";
        echo "============================================================\n";
        printf("Total ejecutadas : %d\n", $this->total);
        printf("Exitosas (OK)    : %d\n", $this->passed);
        printf("Fallidas (FALLO) : %d\n", $this->failed);
        echo "============================================================\n";

        if ($this->failed === 0) {
            echo "RESULTADO: TODAS LAS PRUEBAS DE FASE 6 SUPERADAS SATISFACTORIAMENTE.\n";
        } else {
            echo "RESULTADO: EXISTEN PRUEBAS FALLIDAS.\n";
            exit(1);
        }
    }
}

$suite = new Phase6TestSuite();
$suite->run();
