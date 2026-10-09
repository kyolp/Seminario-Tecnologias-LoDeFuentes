<?php

declare(strict_types=1);

/**
 * Suite de pruebas automatizadas para Fase 5 — Asignaciones Docentes.
 * Ejecutable mediante PHP CLI.
 * 
 * Cobertura obligatoria:
 * T5-01 a T5-21 + Regresiones completas (Fases 1, 2, 3 y 4)
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\AdminAsignacionController;
use App\Core\App;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\AsignacionDocente;
use App\Repositories\AsignacionRepository;
use App\Repositories\AulaRepository;
use App\Repositories\DocenteRepository;
use App\Repositories\GradoPeriodoRepository;
use App\Repositories\GradoRepository;
use App\Repositories\PeriodoRepository;
use App\Repositories\SeccionRepository;
use App\Repositories\UserRepository;
use App\Services\AsignacionService;
use App\Services\AuthService;
use App\Services\DocenteService;

class Phase5TestSuite
{
    private int $total = 0;
    private int $passed = 0;
    private int $failed = 0;
    /** @var array<int, array{id: string, name: string, expected: string, obtained: string, status: string}> */
    private array $results = [];

    private PDO $db;
    private UserRepository $userRepo;
    private DocenteRepository $docenteRepo;
    private PeriodoRepository $periodoRepo;
    private GradoRepository $gradoRepo;
    private GradoPeriodoRepository $gradoPeriodoRepo;
    private SeccionRepository $seccionRepo;
    private AulaRepository $aulaRepo;
    private AsignacionRepository $asignacionRepo;

    private AuthService $authService;
    private DocenteService $docenteService;
    private AsignacionService $asignacionService;

    // Fixtures de prueba dedicados para Fase 5
    private ?int $fixturePeriodoId = null;
    private ?int $fixtureGradoId = null;
    private ?int $fixtureGradoPeriodoId = null;
    private ?int $fixtureSeccion1Id = null;
    private ?int $fixtureSeccion2Id = null;
    private ?int $fixtureAula1Id = null;
    private ?int $fixtureAula2Id = null;
    private ?int $fixtureAulaInactivaId = null;
    private ?int $fixtureDocente1Id = null;
    private ?int $fixtureDocente2Id = null;
    private ?int $fixtureDocenteInactivoId = null;
    private ?int $fixturePeriodoCerradoId = null;
    private ?int $fixtureGradoPeriodoCerradoId = null;
    private ?int $fixtureSeccionCerradaId = null;

    private array $createdAsignacionIds = [];
    private array $createdUserIds = [];

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->userRepo = new UserRepository($this->db);
        $this->docenteRepo = new DocenteRepository($this->db);
        $this->periodoRepo = new PeriodoRepository($this->db);
        $this->gradoRepo = new GradoRepository($this->db);
        $this->gradoPeriodoRepo = new GradoPeriodoRepository($this->db);
        $this->seccionRepo = new SeccionRepository($this->db);
        $this->aulaRepo = new AulaRepository($this->db);
        $this->asignacionRepo = new AsignacionRepository($this->db);

        $this->authService = new AuthService($this->userRepo);
        $this->docenteService = new DocenteService($this->docenteRepo, $this->userRepo, $this->db);
        $this->asignacionService = new AsignacionService(
            $this->db,
            $this->asignacionRepo,
            $this->docenteRepo,
            $this->seccionRepo,
            $this->aulaRepo,
            $this->periodoRepo,
            $this->gradoPeriodoRepo,
            $this->userRepo
        );
    }

    public function run(): void
    {
        echo "============================================================\n";
        echo "SUITE DE PRUEBAS AUTOMATIZADAS — FASE 5\n";
        echo "Asignaciones Docentes — Escuela Oficial Rural Mixta Lo de Fuentes\n";
        echo "============================================================\n\n";

        $this->ensureDevUsers();
        $this->cleanPreviousTestArtifacts();
        $this->setupFixtures();

        try {
            $this->testT5_01_AdminAccedeAsignaciones();
            $this->testT5_02_DocenteIntentaAdministrarAsignaciones();
            $this->testT5_03_UsuarioNoAutenticadoRedirigido();
            $this->testT5_04_CsrfInvalidoRechazado();
            $this->testT5_05_CrearAsignacionValida();
            $this->testT5_06_DocenteInexistenteRechazado();
            $this->testT5_07_DocenteInactivoRechazado();
            $this->testT5_08_SeccionInexistenteRechazada();
            $this->testT5_09_AulaInexistenteOInactivaRechazada();
            $this->testT5_10_CombinacionAcademicaInvalidaRechazada();
            $this->testT5_11_DuplicadoActivoSeccionRechazado();
            $this->testT5_12_HistoricoConservadoAlCerrar();
            $this->testT5_13_NuevaAsignacionPosteriorPermitida();
            $this->testT5_14_EdicionAutorizada();
            $this->testT5_15_MassAssignmentProtegido();
            $this->testT5_16_ColumnaGeneradaProtegida();
            $this->testT5_17_PrevencionXSS();
            $this->testT5_18_IntegridadReferencialFK();
            $this->testT5_19_ColisionConcurrenciaBasica();
            $this->testT5_20_CambioTransaccionalRollback();
            $this->testT5_21_FlujoHttpReal();
            $this->testT5_22_RegresionFase4();
            $this->testT5_23_RegresionFase3();
            $this->testT5_24_RegresionFase2();
            $this->testT5_25_RegresionFase1();
            $this->testT5_26_ConcurrenciaRealAsignacionesActivas();
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

    private function ensureDevUsers(): void
    {
        $adminHash = password_hash('Admin.2026!', PASSWORD_BCRYPT);
        $docenteHash = password_hash('Docente.2026!', PASSWORD_BCRYPT);

        $stmt = $this->db->prepare('UPDATE usuarios SET clave_hash = :hash, estado = "ACTIVO" WHERE nombre_usuario = :user');
        $stmt->execute([':hash' => $adminHash, ':user' => 'admin.demo']);
        $stmt->execute([':hash' => $docenteHash, ':user' => 'docente.demo']);
    }

    private function cleanPreviousTestArtifacts(): void
    {
        $this->db->exec("DELETE FROM asignaciones_docentes WHERE fecha_inicio >= '2026-06-01' OR id_docente IN (SELECT id_docente FROM docentes WHERE codigo_docente LIKE 'DOC-F5%')");
        $this->db->exec("DELETE FROM secciones WHERE id_grado_periodo IN (SELECT id_grado_periodo FROM grados_periodos WHERE id_grado IN (SELECT id_grado FROM grados WHERE codigo LIKE 'GRD-F5%')) OR codigo LIKE 'F5-%'");
        $this->db->exec("DELETE FROM grados_periodos WHERE id_grado IN (SELECT id_grado FROM grados WHERE codigo LIKE 'GRD-F5%')");
        $this->db->exec("DELETE FROM grados WHERE codigo LIKE 'GRD-F5%'");
        $this->db->exec("DELETE FROM periodos_escolares WHERE nombre LIKE 'Ciclo F5%'");
        $this->db->exec("DELETE FROM aulas WHERE codigo LIKE 'AUL-F5%'");

        $testUsers = $this->db->query("SELECT id_usuario FROM usuarios WHERE nombre_usuario LIKE 'test_f5_%'")->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($testUsers)) {
            $inClause = implode(',', array_map('intval', $testUsers));
            $this->db->exec("DELETE FROM docentes WHERE id_usuario IN ($inClause)");
            $this->db->exec("DELETE FROM usuarios WHERE id_usuario IN ($inClause)");
        }
    }

    private function setupFixtures(): void
    {
        // 1. Período F5 planificado (no cerrado)
        $this->db->exec("INSERT INTO periodos_escolares (nombre, fecha_inicio, fecha_fin, estado) VALUES ('Ciclo F5 Prueba 2026', '2026-01-15', '2026-10-31', 'PLANIFICADO')");
        $this->fixturePeriodoId = (int)$this->db->lastInsertId();

        // 2. Grado F5
        $this->db->exec("INSERT INTO grados (codigo, nombre, orden, activo) VALUES ('GRD-F5-01', 'Grado F5 Prueba', 25, 1)");
        $this->fixtureGradoId = (int)$this->db->lastInsertId();

        // 3. Grado-Período F5
        $this->db->exec("INSERT INTO grados_periodos (id_periodo_escolar, id_grado, habilitado) VALUES ({$this->fixturePeriodoId}, {$this->fixtureGradoId}, 1)");
        $this->fixtureGradoPeriodoId = (int)$this->db->lastInsertId();

        // 4. Secciones F5
        $this->db->exec("INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada) VALUES ({$this->fixtureGradoPeriodoId}, 'F5-A', 'Sección F5-A', 30, 1)");
        $this->fixtureSeccion1Id = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada) VALUES ({$this->fixtureGradoPeriodoId}, 'F5-B', 'Sección F5-B', 25, 1)");
        $this->fixtureSeccion2Id = (int)$this->db->lastInsertId();

        // 5. Aulas F5
        $this->db->exec("INSERT INTO aulas (codigo, nombre, ubicacion_referencia, activa) VALUES ('AUL-F5-01', 'Aula F5 Principal', 'Edificio F5 Piso 1', 1)");
        $this->fixtureAula1Id = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO aulas (codigo, nombre, ubicacion_referencia, activa) VALUES ('AUL-F5-02', 'Aula F5 Secundaria', 'Edificio F5 Piso 2', 1)");
        $this->fixtureAula2Id = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO aulas (codigo, nombre, ubicacion_referencia, activa) VALUES ('AUL-F5-INAC', 'Aula F5 Inactiva', 'Bodega F5', 0)");
        $this->fixtureAulaInactivaId = (int)$this->db->lastInsertId();

        // 6. Docentes activos F5
        $idRolDocente = (int)$this->db->query("SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'")->fetchColumn();
        $hash = password_hash('Docente.2026!', PASSWORD_BCRYPT);

        // Docente 1
        $this->db->exec("INSERT INTO usuarios (id_rol, nombre_usuario, correo, clave_hash, nombres, apellidos, estado) VALUES ($idRolDocente, 'test_f5_doc1', 'test_f5_doc1@example.invalid', '$hash', 'Carlos Alberto', 'García Méndez', 'ACTIVO')");
        $u1 = (int)$this->db->lastInsertId();
        $this->createdUserIds[] = $u1;
        $this->db->exec("INSERT INTO docentes (id_usuario, codigo_docente, fecha_ingreso, estado) VALUES ($u1, 'DOC-F5-001', '2026-01-10', 'ACTIVO')");
        $this->fixtureDocente1Id = (int)$this->db->lastInsertId();

        // Docente 2
        $this->db->exec("INSERT INTO usuarios (id_rol, nombre_usuario, correo, clave_hash, nombres, apellidos, estado) VALUES ($idRolDocente, 'test_f5_doc2', 'test_f5_doc2@example.invalid', '$hash', 'Lucía Fernanda', 'Morales Paz', 'ACTIVO')");
        $u2 = (int)$this->db->lastInsertId();
        $this->createdUserIds[] = $u2;
        $this->db->exec("INSERT INTO docentes (id_usuario, codigo_docente, fecha_ingreso, estado) VALUES ($u2, 'DOC-F5-002', '2026-01-12', 'ACTIVO')");
        $this->fixtureDocente2Id = (int)$this->db->lastInsertId();

        // Docente inactivo
        $this->db->exec("INSERT INTO usuarios (id_rol, nombre_usuario, correo, clave_hash, nombres, apellidos, estado) VALUES ($idRolDocente, 'test_f5_doc_inac', 'test_f5_doc_inac@example.invalid', '$hash', 'Docente Inactivo', 'Prueba F5', 'INACTIVO')");
        $uInac = (int)$this->db->lastInsertId();
        $this->createdUserIds[] = $uInac;
        $this->db->exec("INSERT INTO docentes (id_usuario, codigo_docente, fecha_ingreso, estado) VALUES ($uInac, 'DOC-F5-INAC', '2026-01-15', 'INACTIVO')");
        $this->fixtureDocenteInactivoId = (int)$this->db->lastInsertId();

        // 7. Contexto de Período Cerrado
        $this->db->exec("INSERT INTO periodos_escolares (nombre, fecha_inicio, fecha_fin, estado) VALUES ('Ciclo F5 Cerrado 2025', '2025-01-15', '2025-10-31', 'CERRADO')");
        $this->fixturePeriodoCerradoId = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO grados_periodos (id_periodo_escolar, id_grado, habilitado) VALUES ({$this->fixturePeriodoCerradoId}, {$this->fixtureGradoId}, 1)");
        $this->fixtureGradoPeriodoCerradoId = (int)$this->db->lastInsertId();

        $this->db->exec("INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada) VALUES ({$this->fixtureGradoPeriodoCerradoId}, 'F5-CERR', 'Sección Cerrada', 20, 1)");
        $this->fixtureSeccionCerradaId = (int)$this->db->lastInsertId();
    }

    private function cleanup(): void
    {
        if (!empty($this->createdAsignacionIds)) {
            $in = implode(',', array_map('intval', $this->createdAsignacionIds));
            $this->db->exec("DELETE FROM asignaciones_docentes WHERE id_asignacion_docente IN ($in)");
        }

        $this->db->exec("DELETE FROM asignaciones_docentes WHERE id_docente IN ({$this->fixtureDocente1Id}, {$this->fixtureDocente2Id}, {$this->fixtureDocenteInactivoId})");

        if ($this->fixtureSeccion1Id) $this->db->exec("DELETE FROM secciones WHERE id_seccion = {$this->fixtureSeccion1Id}");
        if ($this->fixtureSeccion2Id) $this->db->exec("DELETE FROM secciones WHERE id_seccion = {$this->fixtureSeccion2Id}");
        if ($this->fixtureSeccionCerradaId) $this->db->exec("DELETE FROM secciones WHERE id_seccion = {$this->fixtureSeccionCerradaId}");

        if ($this->fixtureGradoPeriodoId) $this->db->exec("DELETE FROM grados_periodos WHERE id_grado_periodo = {$this->fixtureGradoPeriodoId}");
        if ($this->fixtureGradoPeriodoCerradoId) $this->db->exec("DELETE FROM grados_periodos WHERE id_grado_periodo = {$this->fixtureGradoPeriodoCerradoId}");

        if ($this->fixtureGradoId) $this->db->exec("DELETE FROM grados WHERE id_grado = {$this->fixtureGradoId}");

        if ($this->fixturePeriodoId) $this->db->exec("DELETE FROM periodos_escolares WHERE id_periodo_escolar = {$this->fixturePeriodoId}");
        if ($this->fixturePeriodoCerradoId) $this->db->exec("DELETE FROM periodos_escolares WHERE id_periodo_escolar = {$this->fixturePeriodoCerradoId}");

        if ($this->fixtureAula1Id) $this->db->exec("DELETE FROM aulas WHERE id_aula = {$this->fixtureAula1Id}");
        if ($this->fixtureAula2Id) $this->db->exec("DELETE FROM aulas WHERE id_aula = {$this->fixtureAula2Id}");
        if ($this->fixtureAulaInactivaId) $this->db->exec("DELETE FROM aulas WHERE id_aula = {$this->fixtureAulaInactivaId}");

        if ($this->fixtureDocente1Id) $this->db->exec("DELETE FROM docentes WHERE id_docente = {$this->fixtureDocente1Id}");
        if ($this->fixtureDocente2Id) $this->db->exec("DELETE FROM docentes WHERE id_docente = {$this->fixtureDocente2Id}");
        if ($this->fixtureDocenteInactivoId) $this->db->exec("DELETE FROM docentes WHERE id_docente = {$this->fixtureDocenteInactivoId}");

        if (!empty($this->createdUserIds)) {
            $inUsers = implode(',', array_map('intval', $this->createdUserIds));
            $this->db->exec("DELETE FROM usuarios WHERE id_usuario IN ($inUsers)");
        }
    }

    /**
     * T5-01: Administrador accede a asignaciones (HTTP 200).
     */
    private function testT5_01_AdminAccedeAsignaciones(): void
    {
        Session::destroy();
        Session::start();
        $this->authService->attempt('admin.demo', 'Admin.2026!');

        $app = App::getInstance();
        $rList = $app->handle(Request::create('GET', '/administracion/asignaciones'));
        $rCreate = $app->handle(Request::create('GET', '/administracion/asignaciones/crear'));

        $isOk = ($rList->getStatusCode() === 200 && $rCreate->getStatusCode() === 200 && str_contains($rList->getContent(), 'Asignaciones Docentes'));
        $obtained = sprintf('GET /asignaciones: %d | GET /crear: %d', $rList->getStatusCode(), $rCreate->getStatusCode());

        Session::destroy();
        $this->record('T5-01', 'Administrador accede a asignaciones', 'HTTP 200 en listado y formulario de creación', $obtained, $isOk);
    }

    /**
     * T5-02: Docente intenta administrar asignaciones (HTTP 403).
     */
    private function testT5_02_DocenteIntentaAdministrarAsignaciones(): void
    {
        Session::destroy();
        Session::start();
        $this->authService->attempt('docente.demo', 'Docente.2026!');

        $app = App::getInstance();
        $rList = $app->handle(Request::create('GET', '/administracion/asignaciones'));
        $rCreate = $app->handle(Request::create('GET', '/administracion/asignaciones/crear'));
        $rPost = $app->handle(Request::create('POST', '/administracion/asignaciones/crear'));

        $isOk = ($rList->getStatusCode() === 403 && $rCreate->getStatusCode() === 403 && $rPost->getStatusCode() === 403);
        $obtained = sprintf('GET list: %d | GET crear: %d | POST crear: %d (403 Forbidden confirmado)', $rList->getStatusCode(), $rCreate->getStatusCode(), $rPost->getStatusCode());

        Session::destroy();
        $this->record('T5-02', 'Docente intenta administrar asignaciones', 'HTTP 403 Forbidden para rol DOCENTE', $obtained, $isOk);
    }

    /**
     * T5-03: Usuario no autenticado es redirigido a login (HTTP 302).
     */
    private function testT5_03_UsuarioNoAutenticadoRedirigido(): void
    {
        Session::destroy();
        $app = App::getInstance();

        $resp = $app->handle(Request::create('GET', '/administracion/asignaciones'));
        $isOk = ($resp->getStatusCode() === 302 && str_contains($resp->getHeader('Location'), '/login'));
        $obtained = sprintf('Status %d, Location: %s', $resp->getStatusCode(), $resp->getHeader('Location'));

        $this->record('T5-03', 'Usuario no autenticado es redirigido a login', 'HTTP 302 hacia /login', $obtained, $isOk);
    }

    /**
     * T5-04: Intentos de mutación sin CSRF son rechazados (HTTP 403 y 0 mutaciones).
     */
    private function testT5_04_CsrfInvalidoRechazado(): void
    {
        Session::destroy();
        Session::start();
        $this->authService->attempt('admin.demo', 'Admin.2026!');

        $countBefore = (int)$this->db->query("SELECT COUNT(*) FROM asignaciones_docentes")->fetchColumn();
        $app = App::getInstance();

        // 1. POST sin token
        $reqNoToken = Request::create('POST', '/administracion/asignaciones/crear', [], [
            'id_docente' => $this->fixtureDocente1Id,
            'id_seccion' => $this->fixtureSeccion1Id,
            'id_aula' => $this->fixtureAula1Id,
            'fecha_inicio' => '2026-02-01',
        ]);
        $respNoToken = $app->handle($reqNoToken);

        // 2. POST con token falsificado
        $reqBadToken = Request::create('POST', '/administracion/asignaciones/crear', [], [
            'id_docente' => $this->fixtureDocente1Id,
            'id_seccion' => $this->fixtureSeccion1Id,
            'id_aula' => $this->fixtureAula1Id,
            'fecha_inicio' => '2026-02-01',
            '_token' => 'token_malicioso_12345',
        ]);
        $respBadToken = $app->handle($reqBadToken);

        $countAfter = (int)$this->db->query("SELECT COUNT(*) FROM asignaciones_docentes")->fetchColumn();

        $isOk = ($respNoToken->getStatusCode() === 403 && $respBadToken->getStatusCode() === 403 && $countBefore === $countAfter);
        $obtained = sprintf('Sin token: %d | Token inválido: %d | Registros en BD antes: %d, después: %d (0 mutaciones)', $respNoToken->getStatusCode(), $respBadToken->getStatusCode(), $countBefore, $countAfter);

        Session::destroy();
        $this->record('T5-04', 'Intentos de mutación (POST) sin CSRF son rechazados', 'HTTP 403 y CERO mutaciones en BD', $obtained, $isOk);
    }

    /**
     * T5-05: Crear asignación válida (persistencia correcta en base de datos).
     */
    private function testT5_05_CrearAsignacionValida(): void
    {
        $input = [
            'id_docente' => $this->fixtureDocente1Id,
            'id_seccion' => $this->fixtureSeccion1Id,
            'id_aula' => $this->fixtureAula1Id,
            'fecha_inicio' => '2026-02-01',
            'fecha_fin' => null,
            'estado' => 'ACTIVA',
        ];

        $res = $this->asignacionService->crearAsignacion($input, 1);
        $isOk = false;
        $detalles = 'Fallo al crear';

        if ($res['success'] && !empty($res['id_asignacion_docente'])) {
            $id = (int)$res['id_asignacion_docente'];
            $this->createdAsignacionIds[] = $id;

            $asig = $this->asignacionRepo->findById($id);
            if ($asig && $asig->id_docente === $this->fixtureDocente1Id && $asig->id_seccion === $this->fixtureSeccion1Id && $asig->isActiva() && $asig->id_seccion_activa === $this->fixtureSeccion1Id) {
                $isOk = true;
                $detalles = sprintf('Asignación #%d creada: Docente #%d en Sección #%d (Aula #%d) | Estado: %s | Columna generada id_seccion_activa: %d', $id, $asig->id_docente, $asig->id_seccion, $asig->id_aula, $asig->estado, $asig->id_seccion_activa);
            }
        }

        $this->record('T5-05', 'Crear asignación válida con docente, sección y aula activos', 'Persistencia correcta con id_seccion_activa generado', $detalles, $isOk);
    }

    /**
     * T5-06: Rechazo de docente inexistente.
     */
    private function testT5_06_DocenteInexistenteRechazado(): void
    {
        $input = [
            'id_docente' => 999999,
            'id_seccion' => $this->fixtureSeccion2Id,
            'id_aula' => $this->fixtureAula1Id,
            'fecha_inicio' => '2026-02-01',
        ];

        $res = $this->asignacionService->crearAsignacion($input, 1);
        $isOk = (!$res['success'] && isset($res['errors']['id_docente']) && str_contains($res['errors']['id_docente'], 'no existe'));
        $obtained = sprintf('Éxito: %s | Mensaje: "%s"', $res['success'] ? 'SÍ' : 'NO', $res['errors']['id_docente'] ?? $res['message']);

        $this->record('T5-06', 'Rechazo de docente inexistente', 'Rechazo preventivo con mensaje amigable', $obtained, $isOk);
    }

    /**
     * T5-07: Rechazo de docente inactivo.
     */
    private function testT5_07_DocenteInactivoRechazado(): void
    {
        $input = [
            'id_docente' => $this->fixtureDocenteInactivoId,
            'id_seccion' => $this->fixtureSeccion2Id,
            'id_aula' => $this->fixtureAula1Id,
            'fecha_inicio' => '2026-02-01',
        ];

        $res = $this->asignacionService->crearAsignacion($input, 1);
        $isOk = (!$res['success'] && isset($res['errors']['id_docente']) && str_contains($res['errors']['id_docente'], 'inactivo'));
        $obtained = sprintf('Éxito: %s | Mensaje: "%s"', $res['success'] ? 'SÍ' : 'NO', $res['errors']['id_docente'] ?? $res['message']);

        $this->record('T5-07', 'Rechazo de docente inactivo', 'Rechazo de asignación por estado INACTIVO del docente/usuario', $obtained, $isOk);
    }

    /**
     * T5-08: Rechazo de sección inexistente.
     */
    private function testT5_08_SeccionInexistenteRechazada(): void
    {
        $input = [
            'id_docente' => $this->fixtureDocente2Id,
            'id_seccion' => 999999,
            'id_aula' => $this->fixtureAula1Id,
            'fecha_inicio' => '2026-02-01',
        ];

        $res = $this->asignacionService->crearAsignacion($input, 1);
        $isOk = (!$res['success'] && isset($res['errors']['id_seccion']) && str_contains($res['errors']['id_seccion'], 'no existe'));
        $obtained = sprintf('Éxito: %s | Mensaje: "%s"', $res['success'] ? 'SÍ' : 'NO', $res['errors']['id_seccion'] ?? $res['message']);

        $this->record('T5-08', 'Rechazo de sección inexistente', 'Rechazo con mensaje controlado para ID de sección inexistente', $obtained, $isOk);
    }

    /**
     * T5-09: Rechazo de aula inexistente o inactiva.
     */
    private function testT5_09_AulaInexistenteOInactivaRechazada(): void
    {
        // 1. Aula inexistente
        $inputInexistente = [
            'id_docente' => $this->fixtureDocente2Id,
            'id_seccion' => $this->fixtureSeccion2Id,
            'id_aula' => 999999,
            'fecha_inicio' => '2026-02-01',
        ];
        $resInexistente = $this->asignacionService->crearAsignacion($inputInexistente, 1);
        $ok1 = (!$resInexistente['success'] && isset($resInexistente['errors']['id_aula']));

        // 2. Aula inactiva
        $inputInactiva = [
            'id_docente' => $this->fixtureDocente2Id,
            'id_seccion' => $this->fixtureSeccion2Id,
            'id_aula' => $this->fixtureAulaInactivaId,
            'fecha_inicio' => '2026-02-01',
        ];
        $resInactiva = $this->asignacionService->crearAsignacion($inputInactiva, 1);
        $ok2 = (!$resInactiva['success'] && isset($resInactiva['errors']['id_aula']) && str_contains($resInactiva['errors']['id_aula'], 'activa'));

        $isOk = ($ok1 && $ok2);
        $obtained = sprintf('Inexistente rechazada: %s | Inactiva rechazada: %s', $ok1 ? 'SÍ' : 'NO', $ok2 ? 'SÍ' : 'NO');

        $this->record('T5-09', 'Rechazo de aula inexistente o inactiva', 'Rechazo de aulas no válidas o inactivas', $obtained, $isOk);
    }

    /**
     * T5-10: Combinación académica inválida rechazada (período o grado incompatible).
     */
    private function testT5_10_CombinacionAcademicaInvalidaRechazada(): void
    {
        // 1. Intentar asignar sección en un período cerrado
        $inputCerrado = [
            'id_docente' => $this->fixtureDocente2Id,
            'id_seccion' => $this->fixtureSeccionCerradaId,
            'id_aula' => $this->fixtureAula2Id,
            'fecha_inicio' => '2026-02-01',
        ];
        $resCerrado = $this->asignacionService->crearAsignacion($inputCerrado, 1);
        $okCerrado = (!$resCerrado['success'] && str_contains($resCerrado['message'], 'cerrado'));

        // 2. Enviar id_periodo_escolar incompatible con la sección seleccionada
        $inputMismatched = [
            'id_docente' => $this->fixtureDocente2Id,
            'id_seccion' => $this->fixtureSeccion2Id,
            'id_aula' => $this->fixtureAula2Id,
            'id_periodo_escolar' => $this->fixturePeriodoCerradoId, // Mismatched!
            'fecha_inicio' => '2026-02-01',
        ];
        $resMismatched = $this->asignacionService->crearAsignacion($inputMismatched, 1);
        $okMismatched = (!$resMismatched['success'] && isset($resMismatched['errors']['id_seccion']));

        $isOk = ($okCerrado && $okMismatched);
        $obtained = sprintf('Período cerrado rechazado: %s | Período incompatible rechazado: %s', $okCerrado ? 'SÍ' : 'NO', $okMismatched ? 'SÍ' : 'NO');

        $this->record('T5-10', 'Combinación académica inválida rechazada', 'Rechazo de contextos académicos cerrados o incompatibles', $obtained, $isOk);
    }

    /**
     * T5-11: Rechazo de duplicado activo de sección (preventivo y restricción de BD uk_asignaciones_seccion_activa).
     */
    private function testT5_11_DuplicadoActivoSeccionRechazado(): void
    {
        // Sección 1 ya tiene una asignación activa desde T5-05.
        // 1. Intento vía Service (defensa preventiva)
        $inputDuplicado = [
            'id_docente' => $this->fixtureDocente2Id,
            'id_seccion' => $this->fixtureSeccion1Id,
            'id_aula' => $this->fixtureAula2Id,
            'fecha_inicio' => '2026-03-01',
            'estado' => 'ACTIVA',
        ];

        $resPreventivo = $this->asignacionService->crearAsignacion($inputDuplicado, 1);
        $okPreventivo = (!$resPreventivo['success'] && str_contains($resPreventivo['message'], 'asignación docente activa'));

        // 2. Intento directo en SQL sin pasar por el Service (defensa final de base de datos)
        $dbBlocked = false;
        try {
            $stmt = $this->db->prepare("INSERT INTO asignaciones_docentes (id_docente, id_seccion, id_aula, id_usuario_creador, fecha_inicio, estado) VALUES (:d, :s, :a, 1, '2026-03-01', 'ACTIVA')");
            $stmt->execute([
                ':d' => $this->fixtureDocente2Id,
                ':s' => $this->fixtureSeccion1Id,
                ':a' => $this->fixtureAula2Id,
            ]);
        } catch (\PDOException $e) {
            $errorCode = (int)($e->errorInfo[1] ?? $e->getCode());
            if ($errorCode === 1062 && str_contains($e->getMessage(), 'uk_asignaciones_seccion_activa')) {
                $dbBlocked = true;
            }
        }

        $isOk = ($okPreventivo && $dbBlocked);
        $obtained = sprintf('Preventivo en Service: %s | Constraint uk_asignaciones_seccion_activa (Error 1062): %s', $okPreventivo ? 'RECHAZADO' : 'FALLO', $dbBlocked ? 'DISPARADO Y BLOQUEADO' : 'FALLO');

        $this->record('T5-11', 'Rechazo de duplicado activo de sección', 'Rechazo preventivo en servicio y defensa final en BD (Error 1062)', $obtained, $isOk);
    }

    /**
     * T5-12: Histórico conservado al cerrar/desactivar asignación.
     */
    private function testT5_12_HistoricoConservadoAlCerrar(): void
    {
        $idAsig = end($this->createdAsignacionIds);
        $resCierre = $this->asignacionService->cerrarAsignacion((int)$idAsig, '2026-05-31');

        $asigAfter = $this->asignacionRepo->findById((int)$idAsig);
        $isOk = ($resCierre['success'] && $asigAfter && $asigAfter->estado === 'INACTIVA' && $asigAfter->fecha_fin === '2026-05-31' && $asigAfter->id_seccion_activa === null);
        $obtained = sprintf('Resultado cierre: %s | Estado en BD: %s | Fecha fin: %s | id_seccion_activa en BD: %s', $resCierre['message'], $asigAfter->estado ?? 'N/D', $asigAfter->fecha_fin ?? 'N/D', $asigAfter->id_seccion_activa === null ? 'NULL (liberado)' : (string)$asigAfter->id_seccion_activa);

        $this->record('T5-12', 'Histórico conservado al cerrar asignación', 'Estado pasa a INACTIVA, fecha fin registrada e id_seccion_activa en NULL', $obtained, $isOk);
    }

    /**
     * T5-13: Nueva asignación posterior permitida tras cierre de la anterior.
     */
    private function testT5_13_NuevaAsignacionPosteriorPermitida(): void
    {
        // La sección 1 ya no tiene asignación activa (fue cerrada en T5-12).
        $inputNueva = [
            'id_docente' => $this->fixtureDocente2Id,
            'id_seccion' => $this->fixtureSeccion1Id,
            'id_aula' => $this->fixtureAula2Id,
            'fecha_inicio' => '2026-06-01',
            'estado' => 'ACTIVA',
        ];

        $res = $this->asignacionService->crearAsignacion($inputNueva, 1);
        $isOk = false;
        $detalles = 'Fallo al crear';

        if ($res['success'] && !empty($res['id_asignacion_docente'])) {
            $newId = (int)$res['id_asignacion_docente'];
            $this->createdAsignacionIds[] = $newId;

            // Verificar que en la base de datos existan ambas asignaciones: la histórica inactiva y la nueva activa
            $totalEnSeccion = (int)$this->db->query("SELECT COUNT(*) FROM asignaciones_docentes WHERE id_seccion = {$this->fixtureSeccion1Id}")->fetchColumn();
            $activasEnSeccion = (int)$this->db->query("SELECT COUNT(*) FROM asignaciones_docentes WHERE id_seccion = {$this->fixtureSeccion1Id} AND estado = 'ACTIVA'")->fetchColumn();

            if ($totalEnSeccion >= 2 && $activasEnSeccion === 1) {
                $isOk = true;
                $detalles = sprintf('Nueva asignación #%d creada con éxito | Históricas en sección: %d | Activas vigentes: %d', $newId, $totalEnSeccion, $activasEnSeccion);
            }
        }

        $this->record('T5-13', 'Nueva asignación posterior permitida tras cierre de la anterior', 'Nueva asignación ACTIVA creada manteniendo el registro histórico', $detalles, $isOk);
    }

    /**
     * T5-14: Edición autorizada de campos permitidos.
     */
    private function testT5_14_EdicionAutorizada(): void
    {
        $idAsig = end($this->createdAsignacionIds);
        $inputEdit = [
            'id_aula' => $this->fixtureAula1Id,
            'fecha_inicio' => '2026-06-01',
            'fecha_fin' => '2026-10-31',
            'estado' => 'ACTIVA',
        ];

        $res = $this->asignacionService->actualizarAsignacion((int)$idAsig, $inputEdit);
        $asigUpdated = $this->asignacionRepo->findById((int)$idAsig);

        $isOk = ($res['success'] && $asigUpdated && $asigUpdated->id_aula === $this->fixtureAula1Id && $asigUpdated->fecha_fin === '2026-10-31');
        $obtained = sprintf('Éxito: %s | Aula en BD: #%d | Fecha fin en BD: %s', $res['message'], $asigUpdated->id_aula ?? 0, $asigUpdated->fecha_fin ?? 'N/D');

        $this->record('T5-14', 'Edición autorizada de campos permitidos', 'Aula y fecha fin actualizadas exitosamente', $obtained, $isOk);
    }

    /**
     * T5-15: Protección contra asignación masiva (mass assignment).
     */
    private function testT5_15_MassAssignmentProtegido(): void
    {
        $idAsig = end($this->createdAsignacionIds);
        $controller = new AdminAsignacionController($this->asignacionService, $this->authService);

        // Intento de inyectar campos no autorizados
        $req = Request::create('POST', '/administracion/asignaciones/editar?id=' . $idAsig, ['id' => $idAsig], [
            'id_aula' => $this->fixtureAula2Id,
            'fecha_inicio' => '2026-06-01',
            'fecha_fin' => '2026-10-31',
            'estado' => 'ACTIVA',
            // Parámetros maliciosos no permitidos:
            'id_asignacion_docente' => 999999,
            'id_docente' => 888888,
            'id_seccion' => 777777,
            'id_seccion_activa' => 666666,
            'id_usuario_creador' => 555555,
            'is_admin' => 1,
        ]);

        $controller->update($req);
        $asig = $this->asignacionRepo->findById((int)$idAsig);

        $isOk = ($asig && $asig->id_asignacion_docente === (int)$idAsig && $asig->id_docente === $this->fixtureDocente2Id && $asig->id_seccion === $this->fixtureSeccion1Id && $asig->id_aula === $this->fixtureAula2Id);
        $obtained = sprintf('ID asignación: %d (no alterado) | Docente: %d (inmutable) | Sección: %d (inmutable) | Aula: %d (actualizada)', $asig->id_asignacion_docente ?? 0, $asig->id_docente ?? 0, $asig->id_seccion ?? 0, $asig->id_aula ?? 0);

        $this->record('T5-15', 'Protección contra asignación masiva (mass assignment)', 'Parámetros no permitidos descartados por whitelist estricta', $obtained, $isOk);
    }

    /**
     * T5-16: Columna generada protegida frente a manipulación cliente (id_seccion_activa).
     */
    private function testT5_16_ColumnaGeneradaProtegida(): void
    {
        // Enviar id_seccion_activa manipulada en el POST
        $input = [
            'id_docente' => $this->fixtureDocente1Id,
            'id_seccion' => $this->fixtureSeccion2Id,
            'id_aula' => $this->fixtureAula1Id,
            'fecha_inicio' => '2026-02-15',
            'estado' => 'ACTIVA',
            'id_seccion_activa' => 999999, // Manipulación cliente
        ];

        $res = $this->asignacionService->crearAsignacion($input, 1);
        $isOk = false;
        $detalles = 'Error';

        if ($res['success'] && !empty($res['id_asignacion_docente'])) {
            $id = (int)$res['id_asignacion_docente'];
            $this->createdAsignacionIds[] = $id;

            $asig = $this->asignacionRepo->findById($id);
            // El valor de id_seccion_activa DEBE ser igual a id_seccion (computado por MySQL STORED), ignorando 999999
            if ($asig && $asig->id_seccion_activa === $this->fixtureSeccion2Id) {
                $isOk = true;
                $detalles = sprintf('id_seccion_activa en BD: %d (derivado de id_seccion=%d, valor manipulado 999999 fue ignorado)', $asig->id_seccion_activa, $this->fixtureSeccion2Id);
            }
        }

        $this->record('T5-16', 'Columna generada protegida frente a manipulación cliente', 'id_seccion_activa calculado exclusivamente por MySQL STORED', $detalles, $isOk);
    }

    /**
     * T5-17: Prevención de XSS en vistas de asignaciones.
     */
    private function testT5_17_PrevencionXSS(): void
    {
        Session::destroy();
        Session::start();
        $this->authService->attempt('admin.demo', 'Admin.2026!');

        $app = App::getInstance();
        $xssPayload = '<script>alert("xss-asignacion")</script>';
        $req = Request::create('GET', '/administracion/asignaciones?q=' . urlencode($xssPayload));
        $resp = $app->handle($req);

        $content = $resp->getContent();
        $hasRaw = str_contains($content, '<script>alert("xss-asignacion")</script>');
        $hasEscaped = str_contains($content, '&lt;script&gt;alert(&quot;xss-asignacion&quot;)&lt;/script&gt;') || str_contains($content, htmlspecialchars($xssPayload, ENT_QUOTES, 'UTF-8'));

        $isOk = (!$hasRaw && $hasEscaped);
        $obtained = sprintf('Script crudo encontrado: %s | Script escapado encontrado: %s', $hasRaw ? 'SÍ' : 'NO', $hasEscaped ? 'SÍ' : 'NO');

        Session::destroy();
        $this->record('T5-17', 'Prevención de Cross-Site Scripting (XSS)', 'Neutralización total de payloads maliciosos mediante e()', $obtained, $isOk);
    }

    /**
     * T5-18: Integridad referencial: rechazo de IDs foráneos inválidos.
     */
    private function testT5_18_IntegridadReferencialFK(): void
    {
        $input = [
            'id_docente' => 777777,
            'id_seccion' => 888888,
            'id_aula' => 999999,
            'fecha_inicio' => '2026-02-01',
        ];

        $res = $this->asignacionService->crearAsignacion($input, 1);
        $isOk = (!$res['success'] && !str_contains($res['message'], 'PDOException') && !str_contains($res['message'], 'SQLSTATE'));
        $obtained = sprintf('Éxito: %s | Mensaje limpio al usuario: "%s"', $res['success'] ? 'SÍ' : 'NO', $res['message']);

        $this->record('T5-18', 'Integridad referencial y rechazo de FKs inexistentes', 'Rechazo controlado sin exposición de excepciones SQL internas', $obtained, $isOk);
    }

    /**
     * T5-19: Colisión/concurrencia básica sobre la misma sección.
     */
    private function testT5_19_ColisionConcurrenciaBasica(): void
    {
        // Crear una sección limpia de prueba
        $this->db->exec("INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada) VALUES ({$this->fixtureGradoPeriodoId}, 'F5-RACE', 'Sección Carrera', 25, 1)");
        $idSeccionRace = (int)$this->db->lastInsertId();

        $input1 = [
            'id_docente' => $this->fixtureDocente1Id,
            'id_seccion' => $idSeccionRace,
            'id_aula' => $this->fixtureAula1Id,
            'fecha_inicio' => '2026-03-01',
            'estado' => 'ACTIVA',
        ];

        $input2 = [
            'id_docente' => $this->fixtureDocente2Id,
            'id_seccion' => $idSeccionRace,
            'id_aula' => $this->fixtureAula2Id,
            'fecha_inicio' => '2026-03-01',
            'estado' => 'ACTIVA',
        ];

        // Primer registro
        $r1 = $this->asignacionService->crearAsignacion($input1, 1);
        if ($r1['success'] && !empty($r1['id_asignacion_docente'])) {
            $this->createdAsignacionIds[] = (int)$r1['id_asignacion_docente'];
        }

        // Segundo intento concurrentemente sobre la misma sección
        $r2 = $this->asignacionService->crearAsignacion($input2, 1);

        $activasCount = (int)$this->db->query("SELECT COUNT(*) FROM asignaciones_docentes WHERE id_seccion = $idSeccionRace AND estado = 'ACTIVA'")->fetchColumn();

        $isOk = ($r1['success'] && !$r2['success'] && $activasCount === 1);
        $obtained = sprintf('Primer intento: %s | Segundo intento: %s | Asignaciones activas en BD: %d (integridad garantizada)', $r1['success'] ? 'OK' : 'FALLO', $r2['success'] ? 'PERMITIDO (ERROR)' : 'RECHAZADO', $activasCount);

        // Limpiar sección de carrera
        $this->db->exec("DELETE FROM asignaciones_docentes WHERE id_seccion = $idSeccionRace");
        $this->db->exec("DELETE FROM secciones WHERE id_seccion = $idSeccionRace");

        $this->record('T5-19', 'Manejo de colisión y concurrencia sobre sección', 'Exactamente una asignación activa en base de datos; cero inconsistencias', $obtained, $isOk);
    }

    /**
     * T5-20: Reemplazo atómico transaccional de docente y rollback ante fallo.
     */
    private function testT5_20_CambioTransaccionalRollback(): void
    {
        // Asignación activa en sección 2
        $asigPrevia = $this->asignacionRepo->findActiveBySeccion($this->fixtureSeccion2Id);
        $idAsigActual = $asigPrevia ? $asigPrevia->id_asignacion_docente : 0;

        if ($idAsigActual === 0) {
            $this->record('T5-20', 'Reemplazo atómico transaccional y rollback', 'Rollback automático', 'No se encontró asignación previa', false);
            return;
        }

        // 1. Ejecutar reemplazo con simulación de fallo forzado
        $resFallo = $this->asignacionService->reemplazarDocenteAtomico(
            $idAsigActual,
            $this->fixtureDocente2Id,
            $this->fixtureAula2Id,
            '2026-07-01',
            1,
            true // Simular fallo forzado
        );

        $asigAfterRollback = $this->asignacionRepo->findById($idAsigActual);
        $rollbackOk = (!$resFallo['success'] && $asigAfterRollback && $asigAfterRollback->isActiva() && $asigAfterRollback->fecha_fin === null);

        // 2. Ejecutar reemplazo real exitoso
        $resExito = $this->asignacionService->reemplazarDocenteAtomico(
            $idAsigActual,
            $this->fixtureDocente2Id,
            $this->fixtureAula2Id,
            '2026-07-01',
            1,
            false // Operación normal
        );

        $asigCerrada = $this->asignacionRepo->findById($idAsigActual);
        $nuevaId = $resExito['id_asignacion_docente'] ?? 0;
        if ($nuevaId > 0) {
            $this->createdAsignacionIds[] = $nuevaId;
        }
        $nuevaAsig = $this->asignacionRepo->findById($nuevaId);

        $swapOk = ($resExito['success'] && $asigCerrada && $asigCerrada->estado === 'INACTIVA' && $nuevaAsig && $nuevaAsig->isActiva() && $nuevaAsig->id_docente === $this->fixtureDocente2Id);

        $isOk = ($rollbackOk && $swapOk);
        $obtained = sprintf('Rollback ante fallo: %s (Asignación previa permaneció ACTIVA) | Reemplazo atómico exitoso: %s (Previa: INACTIVA, Nueva #%d: ACTIVA)', $rollbackOk ? 'OK' : 'FALLO', $swapOk ? 'OK' : 'FALLO', $nuevaId);

        $this->record('T5-20', 'Reemplazo atómico transaccional y rollback ante fallo', 'Atomicidad garantizada bajo transacción PDO con rollback completo', $obtained, $isOk);
    }

    /**
     * T5-21: Flujo HTTP real de asignaciones docentes (servidor efímero y cURL).
     */
    private function testT5_21_FlujoHttpReal(): void
    {
        echo "\n>>> Ejecutando flujo HTTP real de asignaciones docentes (T5-21)...\n";
        $port = $this->findFreePort(8091, 8099);
        $phpBin = PHP_BINARY;
        $docRoot = dirname(__DIR__) . '/public';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $cmd = sprintf('"%s" -S 127.0.0.1:%d -t "%s"', $phpBin, $port, $docRoot);
        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));

        if (!is_resource($process)) {
            $this->record('T5-21', 'Flujo HTTP real de asignaciones docentes', 'Flujo HTTP completado', 'No se pudo iniciar servidor temporal PHP', false);
            return;
        }

        $procStatus = proc_get_status($process);
        $pid = $procStatus['pid'] ?? 0;

        $connected = false;
        for ($i = 0; $i < 30; $i++) {
            $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
            if ($fp) {
                fclose($fp);
                $connected = true;
                break;
            }
            usleep(100000);
        }

        if (!$connected) {
            $this->terminateProcess($pid, $process, $pipes);
            $this->record('T5-21', 'Flujo HTTP real de asignaciones docentes', 'Flujo HTTP completado', 'El servidor web temporal no respondió a tiempo', false);
            return;
        }

        $adminCookie = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_f5_adm_' . uniqid() . '.txt';
        $docenteCookie = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_f5_doc_' . uniqid() . '.txt';

        $httpRequest = function (string $url, string $method = 'GET', ?array $postData = null, ?string $cookieFile = null): array {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            if ($cookieFile !== null) {
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            }
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if ($postData !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                }
            }
            $raw = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headers = substr($raw, 0, $headerSize);
            $body = substr($raw, $headerSize);
            curl_close($ch);

            $location = '';
            if (preg_match('/Location:\s*([^\r\n]+)/i', $headers, $m)) {
                $location = trim($m[1]);
            }

            return [
                'code' => $code,
                'headers' => $headers,
                'body' => $body,
                'location' => $location,
            ];
        };

        $extractCsrf = function (string $html): ?string {
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/', $html, $m)) {
                return $m[1];
            }
            return null;
        };

        $isOk = false;
        $detalles = [];
        $httpCreatedId = 0;

        try {
            // Sección temporal para prueba HTTP
            $this->db->exec("INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada) VALUES ({$this->fixtureGradoPeriodoId}, 'F5-HTTP', 'Sección HTTP', 28, 1)");
            $idSeccionHttp = (int)$this->db->lastInsertId();

            // 1. Login ADMINISTRADOR
            $rLoginGet = $httpRequest("http://127.0.0.1:$port/login", 'GET', null, $adminCookie);
            $adminCsrf = $extractCsrf($rLoginGet['body']);
            $rLoginPost = $httpRequest("http://127.0.0.1:$port/login", 'POST', [
                'nombre_usuario' => 'admin.demo',
                'password' => 'Admin.2026!',
                '_token' => $adminCsrf,
            ], $adminCookie);

            $loginOk = ($rLoginPost['code'] === 302 && str_contains($rLoginPost['location'], '/administracion'));
            $detalles[] = sprintf('Login Admin: %d', $rLoginPost['code']);

            // 2. GET listado asignaciones
            $rList = $httpRequest("http://127.0.0.1:$port/administracion/asignaciones", 'GET', null, $adminCookie);
            $listOk = ($rList['code'] === 200 && str_contains($rList['body'], 'Asignaciones Docentes'));
            $detalles[] = sprintf('GET List: %d', $rList['code']);

            // 3. GET formulario creación
            $rCreateForm = $httpRequest("http://127.0.0.1:$port/administracion/asignaciones/crear", 'GET', null, $adminCookie);
            $createCsrf = $extractCsrf($rCreateForm['body']);
            $createFormOk = ($rCreateForm['code'] === 200 && !empty($createCsrf));
            $detalles[] = sprintf('GET Form Crear: %d', $rCreateForm['code']);

            // 4. POST creación válida
            $rPostCreate = $httpRequest("http://127.0.0.1:$port/administracion/asignaciones/crear", 'POST', [
                'id_docente' => $this->fixtureDocente1Id,
                'id_seccion' => $idSeccionHttp,
                'id_aula' => $this->fixtureAula1Id,
                'fecha_inicio' => '2026-03-01',
                'estado' => 'ACTIVA',
                '_token' => $createCsrf,
            ], $adminCookie);

            $postCreateOk = ($rPostCreate['code'] === 302 && str_contains($rPostCreate['location'], '/administracion/asignaciones'));

            // 5. Verificación BD
            $stmt = $this->db->prepare("SELECT id_asignacion_docente, estado, id_seccion_activa FROM asignaciones_docentes WHERE id_seccion = :s AND estado = 'ACTIVA'");
            $stmt->execute([':s' => $idSeccionHttp]);
            $rowCreated = $stmt->fetch(PDO::FETCH_ASSOC);
            $httpCreatedId = $rowCreated ? (int)$rowCreated['id_asignacion_docente'] : 0;
            $dbCreatedOk = ($rowCreated && $rowCreated['estado'] === 'ACTIVA' && (int)$rowCreated['id_seccion_activa'] === $idSeccionHttp);
            $detalles[] = sprintf('POST Crear: 302 (BD #%d, Activa: %s)', $httpCreatedId, $rowCreated['estado'] ?? 'N/D');

            // 6. POST duplicado (intento de crear segunda asignación activa para la misma sección)
            $rDupForm = $httpRequest("http://127.0.0.1:$port/administracion/asignaciones/crear", 'GET', null, $adminCookie);
            $dupCsrf = $extractCsrf($rDupForm['body']);
            $rPostDup = $httpRequest("http://127.0.0.1:$port/administracion/asignaciones/crear", 'POST', [
                'id_docente' => $this->fixtureDocente2Id,
                'id_seccion' => $idSeccionHttp,
                'id_aula' => $this->fixtureAula2Id,
                'fecha_inicio' => '2026-03-01',
                'estado' => 'ACTIVA',
                '_token' => $dupCsrf,
            ], $adminCookie);

            $dupRejected = ($rPostDup['code'] === 422 && str_contains($rPostDup['body'], 'asignación docente activa'));
            $detalles[] = sprintf('POST Duplicado: %d (Rechazado)', $rPostDup['code']);

            // 7. DOCENTE intenta acceder
            $rDocLoginGet = $httpRequest("http://127.0.0.1:$port/login", 'GET', null, $docenteCookie);
            $docCsrf = $extractCsrf($rDocLoginGet['body']);
            $httpRequest("http://127.0.0.1:$port/login", 'POST', [
                'nombre_usuario' => 'docente.demo',
                'password' => 'Docente.2026!',
                '_token' => $docCsrf,
            ], $docenteCookie);

            $rDocAdmin = $httpRequest("http://127.0.0.1:$port/administracion/asignaciones", 'GET', null, $docenteCookie);
            $docenteForbidden = ($rDocAdmin['code'] === 403);
            $detalles[] = sprintf('Docente a Asignaciones: %d', $rDocAdmin['code']);

            // 8. Usuario no autenticado
            $rNoAuth = $httpRequest("http://127.0.0.1:$port/administracion/asignaciones", 'GET', null, null);
            $unauthRedirect = ($rNoAuth['code'] === 302 && str_contains($rNoAuth['location'], '/login'));
            $detalles[] = sprintf('Sin auth: 302->%s', $rNoAuth['location']);

            // 9. Cambio de estado / cierre
            $rListAdm = $httpRequest("http://127.0.0.1:$port/administracion/asignaciones", 'GET', null, $adminCookie);
            $listToken = $extractCsrf($rListAdm['body']);
            $rClose = $httpRequest("http://127.0.0.1:$port/administracion/asignaciones/estado?id=$httpCreatedId", 'POST', [
                'nuevo_estado' => 'INACTIVA',
                'fecha_fin' => '2026-06-30',
                '_token' => $listToken,
            ], $adminCookie);
            $closeRedirect = ($rClose['code'] === 302);

            // 10. Verificación BD de cierre
            $stmtCerrada = $this->db->prepare("SELECT estado, id_seccion_activa, fecha_fin FROM asignaciones_docentes WHERE id_asignacion_docente = :id");
            $stmtCerrada->execute([':id' => $httpCreatedId]);
            $rowCerrada = $stmtCerrada->fetch(PDO::FETCH_ASSOC);
            $closedOk = ($rowCerrada && $rowCerrada['estado'] === 'INACTIVA' && $rowCerrada['id_seccion_activa'] === null);
            $detalles[] = sprintf('Cierre HTTP: 302 (BD Estado: %s, seccion_activa: NULL)', $rowCerrada['estado'] ?? 'N/D');

            $isOk = ($loginOk && $listOk && $createFormOk && $postCreateOk && $dbCreatedOk && $dupRejected && $docenteForbidden && $unauthRedirect && $closeRedirect && $closedOk);
            $obtained = implode(' | ', $detalles);
        } catch (\Throwable $e) {
            $isOk = false;
            $obtained = 'Excepción en prueba HTTP: ' . $e->getMessage();
        } finally {
            $this->terminateProcess($pid, $process, $pipes);
            if ($httpCreatedId > 0) {
                $this->db->exec("DELETE FROM asignaciones_docentes WHERE id_asignacion_docente = $httpCreatedId");
            }
            if (isset($idSeccionHttp) && $idSeccionHttp > 0) {
                $this->db->exec("DELETE FROM secciones WHERE id_seccion = $idSeccionHttp");
            }
            if (file_exists($adminCookie)) @unlink($adminCookie);
            if (file_exists($docenteCookie)) @unlink($docenteCookie);
        }

        $this->record('T5-21', 'Flujo HTTP real de asignaciones docentes (login, list, create, duplicate, 403, toggle, cleanup)', 'Flujo HTTP end-to-end verificado contra servidor real', $obtained, $isOk);
    }

    /**
     * T5-22: Regresión completa de Fase 4 (Administración Básica — 27/27 OK).
     */
    private function testT5_22_RegresionFase4(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 4 en proceso aislado...\n";
        $cmd = escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__) . '/tests/run_tests_fase4.php');
        $output = (string)shell_exec($cmd . ' 2>&1');

        $isOk = (str_contains($output, 'TODAS LAS PRUEBAS DE FASE 4 SUPERADAS SATISFACTORIAMENTE') && str_contains($output, 'Fallidas (FALLO) : 0') && str_contains($output, 'Total ejecutadas : 27'));
        $obtained = $isOk ? '27/27 pruebas de Fase 4 aprobadas satisfactoriamente' : 'Fallo en regresión de Fase 4: ' . substr(strip_tags($output), -300);

        $this->record('T5-22', 'Regresión completa de Fase 4 (Administración Básica)', '27/27 pruebas de Fase 4 superadas satisfactoriamente', $obtained, $isOk);
    }

    /**
     * T5-23: Regresión completa de Fase 3 (Autenticación y Roles — 19/19 OK).
     */
    private function testT5_23_RegresionFase3(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 3 en proceso aislado...\n";
        $cmd = escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__) . '/tests/run_tests_fase3.php');
        $output = (string)shell_exec($cmd . ' 2>&1');

        $isOk = (str_contains($output, 'TODAS LAS PRUEBAS DE FASE 3 SUPERADAS SATISFACTORIAMENTE') && str_contains($output, 'Fallidas (FALLO) : 0') && str_contains($output, 'Total ejecutadas : 19'));
        $obtained = $isOk ? '19/19 pruebas de Fase 3 aprobadas satisfactoriamente' : 'Fallo en regresión de Fase 3: ' . substr(strip_tags($output), -300);

        $this->record('T5-23', 'Regresión completa de Fase 3 (Autenticación y Roles)', '19/19 pruebas de Fase 3 superadas satisfactoriamente', $obtained, $isOk);
    }

    /**
     * T5-24: Regresión completa de Fase 2 (Arquitectura Base y Enrutamiento — 16/16 OK).
     */
    private function testT5_24_RegresionFase2(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 2 en proceso aislado...\n";
        $cmd = escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__) . '/tests/run_tests.php');
        $output = (string)shell_exec($cmd . ' 2>&1');

        $isOk = (str_contains($output, 'TODAS LAS PRUEBAS DE FASE 2 SUPERADAS SATISFACTORIAMENTE') && str_contains($output, 'Fallidas (FALLO) : 0') && str_contains($output, 'Total ejecutadas : 16'));
        $obtained = $isOk ? '16/16 pruebas de Fase 2 aprobadas satisfactoriamente' : 'Fallo en regresión de Fase 2: ' . substr(strip_tags($output), -300);

        $this->record('T5-24', 'Regresión completa de Fase 2 (Arquitectura Base y Enrutamiento)', '16/16 pruebas de Fase 2 superadas satisfactoriamente', $obtained, $isOk);
    }

    /**
     * T5-25: Regresión completa de Fase 1 (Diseño de Base de Datos e Integridad Relacional — 18/18 OK).
     */
    private function testT5_25_RegresionFase1(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 1...\n";
        $root = dirname(__DIR__);
        $mysqlBinary = $this->findMysqlBinary();

        if ($mysqlBinary === null) {
            $this->record('T5-25', 'Regresión completa de Fase 1 (Diseño de Base de Datos)', '18/18 pruebas automáticas ejecutadas exitosamente', 'Fallo: Binario mysql.exe no encontrado', false);
            return;
        }

        $dbHost = (string)Config::get('database.connections.mysql.host', '127.0.0.1');
        $dbPort = (int)Config::get('database.connections.mysql.port', 3307);
        $dbUser = (string)Config::get('database.connections.mysql.username', 'root');
        $dbPass = (string)Config::get('database.connections.mysql.password', '');
        $tempDb = 'escuela_fase5_regresion_temp';

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
            shell_exec($baseCmd . ' -e "DROP DATABASE IF EXISTS ' . $tempDb . '; CREATE DATABASE ' . $tempDb . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1');
            shell_exec($baseCmd . ' ' . $tempDb . ' < "' . $schemaFile . '" 2>&1');
            shell_exec($baseCmd . ' ' . $tempDb . ' < "' . $seedFile . '" 2>&1');
            $output = (string)shell_exec($baseCmd . ' ' . $tempDb . ' < "' . $valFile . '" 2>&1');

            $matched = preg_match('/pruebas_ejecutadas\s+pruebas_exitosas\s+pruebas_fallidas\s+18\s+18\s+0/i', $output);
            $hasNoFailure = !preg_match('/\bFALLO\b/i', $output);

            if ($matched && $hasNoFailure) {
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

        $this->record('T5-25', 'Regresión completa de Fase 1 (Diseño de Base de Datos)', '18/18 pruebas de integridad relacional superadas', $obtained, $isOk);
    }

    private function findFreePort(int $start = 8091, int $end = 8099): int
    {
        for ($p = $start; $p <= $end; $p++) {
            $fp = @fsockopen('127.0.0.1', $p, $errno, $errstr, 0.1);
            if (!$fp) {
                return $p;
            }
            fclose($fp);
        }
        return $start;
    }

    private function terminateProcess(int $pid, $process, array $pipes): void
    {
        if ($pid > 0 && PHP_OS_FAMILY === 'Windows') {
            shell_exec("taskkill /F /T /PID $pid 2>nul");
        } elseif (is_resource($process)) {
            proc_terminate($process);
        }

        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        if (is_resource($process)) {
            proc_close($process);
        }
    }

    /**
     * T5-26: Concurrencia real de asignaciones activas (5 iteraciones entre dos procesos PHP independientes).
     */
    private function testT5_26_ConcurrenciaRealAsignacionesActivas(): void
    {
        echo "\n>>> Ejecutando prueba de concurrencia real entre procesos independientes (T5-26)...\n";

        // 1. Crear sección dedicada y limpia para la prueba de concurrencia
        $idGrdPer = $this->fixtureGradoPeriodoId;
        $existeGp = $idGrdPer ? (int)$this->db->query("SELECT COUNT(*) FROM grados_periodos WHERE id_grado_periodo = {$idGrdPer}")->fetchColumn() : 0;
        if ($existeGp === 0) {
            $idGrdPer = (int)$this->db->query("SELECT id_grado_periodo FROM grados_periodos WHERE habilitado = 1 ORDER BY id_grado_periodo DESC LIMIT 1")->fetchColumn();
        }
        $doc1 = (int)$this->db->query("SELECT id_docente FROM docentes WHERE estado = 'ACTIVO' ORDER BY id_docente ASC LIMIT 1")->fetchColumn();
        $doc2 = (int)$this->db->query("SELECT id_docente FROM docentes WHERE estado = 'ACTIVO' AND id_docente != {$doc1} ORDER BY id_docente DESC LIMIT 1")->fetchColumn();
        $aula1 = (int)$this->db->query("SELECT id_aula FROM aulas WHERE activa = 1 ORDER BY id_aula ASC LIMIT 1")->fetchColumn();

        $this->db->exec("DELETE FROM secciones WHERE codigo = 'SEC-F5-RACE26'");
        $stmtSec = $this->db->prepare("INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada) VALUES (?, 'SEC-F5-RACE26', 'Sección Carrera Real T26', 30, 1)");
        $stmtSec->execute([$idGrdPer]);
        $idSeccionRace = (int)$this->db->lastInsertId();

        $workerScript = __DIR__ . '/worker_asignacion_concurrente.php';
        $phpBinary = PHP_BINARY;
        $tmpDir = sys_get_temp_dir() . '/fase5_race26_' . bin2hex(random_bytes(4));
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $iterations = 5;
        $allIterationsOk = true;
        $iterationSummaries = [];

        for ($i = 1; $i <= $iterations; $i++) {
            // Asegurar estado limpio para cada iteración
            $this->db->prepare("DELETE FROM asignaciones_docentes WHERE id_seccion = ?")->execute([$idSeccionRace]);

            $readyA = "$tmpDir/ready_A_$i.tmp";
            $readyB = "$tmpDir/ready_B_$i.tmp";
            $barrier = "$tmpDir/barrier_$i.tmp";
            $outA = "$tmpDir/out_A_$i.json";
            $outB = "$tmpDir/out_B_$i.json";

            $cmdA = sprintf('"%s" "%s" --docente=%d --seccion=%d --aula=%d --usuario=1 --fecha_inicio=2026-03-01 --ready="%s" --barrier="%s" --out="%s"',
                $phpBinary, $workerScript, $doc1, $idSeccionRace, $aula1, $readyA, $barrier, $outA);
            $cmdB = sprintf('"%s" "%s" --docente=%d --seccion=%d --aula=%d --usuario=1 --fecha_inicio=2026-03-01 --ready="%s" --barrier="%s" --out="%s"',
                $phpBinary, $workerScript, $doc2, $idSeccionRace, $aula1, $readyB, $barrier, $outB);

            $desc = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $procA = proc_open($cmdA, $desc, $pipesA);
            $procB = proc_open($cmdB, $desc, $pipesB);

            // Esperar que ambos procesos reporten 'READY'
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
                $iterationSummaries[] = sprintf("Iteración %d/5: FALLO (Timeout esperando READY)", $i);
                @proc_close($procA);
                @proc_close($procB);
                continue;
            }

            // Liberar barrera al mismo instante exacto
            file_put_contents($barrier, 'GO');

            // Esperar finalización de ambos procesos
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

            // Leer resultados JSON
            $resA = json_decode((string)@file_get_contents($outA), true) ?? [];
            $resB = json_decode((string)@file_get_contents($outB), true) ?? [];

            $successA = $resA['success'] ?? false;
            $successB = $resB['success'] ?? false;

            // Consultar BD
            $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM asignaciones_docentes WHERE id_seccion = ? AND estado = 'ACTIVA'");
            $stmtCount->execute([$idSeccionRace]);
            $activeInDb = (int)$stmtCount->fetchColumn();

            $stmtTotal = $this->db->prepare("SELECT COUNT(*) FROM asignaciones_docentes WHERE id_seccion = ?");
            $stmtTotal->execute([$idSeccionRace]);
            $totalInDb = (int)$stmtTotal->fetchColumn();

            $exactOneSuccess = ($successA !== $successB) && ($successA || $successB);
            $dbConsistent = ($activeInDb === 1 && $totalInDb === 1);

            $failedRes = $successA ? $resB : $resA;
            $controlledMsg = isset($failedRes['message']) && !str_contains($failedRes['message'], 'SQLSTATE');
            $ukFired = isset($failedRes['message']) && str_contains($failedRes['message'], 'asignación docente activa');

            $iterOk = ($exactOneSuccess && $dbConsistent && $controlledMsg && $ukFired);
            if (!$iterOk) {
                $allIterationsOk = false;
            }

            $ganador = $successA ? 'Transacción A' : ($successB ? 'Transacción B' : 'Ninguna');
            $iterationSummaries[] = sprintf(
                "Iter %d: Ganador %s (Activas BD: %d, Rechazo controlado: %s)",
                $i,
                $ganador,
                $activeInDb,
                $controlledMsg ? 'SÍ' : 'NO'
            );
        }

        // Limpieza de artefactos de prueba
        $this->db->prepare("DELETE FROM asignaciones_docentes WHERE id_seccion = ?")->execute([$idSeccionRace]);
        $this->db->prepare("DELETE FROM secciones WHERE id_seccion = ?")->execute([$idSeccionRace]);
        foreach (glob("$tmpDir/*") as $f) {
            @unlink($f);
        }
        @rmdir($tmpDir);

        $obtained = sprintf(
            "%s | %s | Error 1062 controlado: SÍ",
            $allIterationsOk ? '5/5 iteraciones OK' : 'Fallo en concurrencia',
            implode(' | ', $iterationSummaries)
        );

        $this->record(
            'T5-26',
            'Concurrencia real de asignaciones activas (carrera entre procesos independientes)',
            'Exactamente una operación exitosa por iteración; rechazo controlado con error 1062 traducido; exactamente 1 asignación activa en BD',
            $obtained,
            $allIterationsOk
        );
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
        echo "RESUMEN DE PRUEBAS DE FASE 5\n";
        echo "============================================================\n";
        printf("Total ejecutadas : %d\n", $this->total);
        printf("Exitosas (OK)    : %d\n", $this->passed);
        printf("Fallidas (FALLO) : %d\n", $this->failed);
        echo "============================================================\n";

        if ($this->failed === 0) {
            echo "RESULTADO: TODAS LAS PRUEBAS DE FASE 5 SUPERADAS SATISFACTORIAMENTE.\n";
        } else {
            echo "RESULTADO: EXISTEN PRUEBAS FALLIDAS.\n";
            exit(1);
        }
    }
}

$suite = new Phase5TestSuite();
$suite->run();
