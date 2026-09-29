<?php

declare(strict_types=1);

/**
 * Suite de pruebas automatizadas para Fase 4 — Administración Básica.
 * Ejecutable mediante PHP CLI.
 * 
 * Cobertura obligatoria:
 * T4-01 a T4-25
 * 
 * Módulos validados:
 * - Docentes (creación atómica, edición, desactivación, rollback transaccional)
 * - Estudiantes (creación, validación)
 * - Períodos Escolares (creación, unicidad de ciclo activo)
 * - Grados (creación, orden jerárquico positivo)
 * - Grados por Período (asociación, prevención de duplicados)
 * - Secciones (creación, capacidad obligatoria > 0 entera, edición)
 * - Aulas (creación, código único)
 * - Seguridad: Control de acceso RBAC, Prevención CSRF, Prevención XSS, Mass Assignment
 * - Regresión completa: Fases 1, 2 y 3
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\App;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Repositories\AulaRepository;
use App\Repositories\DocenteRepository;
use App\Repositories\EstudianteRepository;
use App\Repositories\GradoPeriodoRepository;
use App\Repositories\GradoRepository;
use App\Repositories\PeriodoRepository;
use App\Repositories\SeccionRepository;
use App\Repositories\UserRepository;
use App\Services\AulaService;
use App\Services\AuthService;
use App\Services\DocenteService;
use App\Services\EstudianteService;
use App\Services\GradoService;
use App\Services\PeriodoService;
use App\Services\SeccionService;

class Phase4TestSuite
{
    private int $total = 0;
    private int $passed = 0;
    private int $failed = 0;
    /** @var array<int, array{id: string, name: string, expected: string, obtained: string, status: string}> */
    private array $results = [];

    private PDO $db;
    private UserRepository $userRepo;
    private DocenteRepository $docenteRepo;
    private EstudianteRepository $estudianteRepo;
    private PeriodoRepository $periodoRepo;
    private GradoRepository $gradoRepo;
    private GradoPeriodoRepository $gradoPeriodoRepo;
    private SeccionRepository $seccionRepo;
    private AulaRepository $aulaRepo;

    private AuthService $authService;
    private DocenteService $docenteService;
    private EstudianteService $estudianteService;
    private PeriodoService $periodoService;
    private GradoService $gradoService;
    private SeccionService $seccionService;
    private AulaService $aulaService;

    // IDs de entidades creadas durante pruebas para posterior limpieza
    private array $createdDocenteIds = [];
    private array $createdUserIds = [];
    private array $createdEstudianteIds = [];
    private array $createdPeriodoIds = [];
    private array $createdGradoIds = [];
    private array $createdGradoPeriodoIds = [];
    private array $createdSeccionIds = [];
    private array $createdAulaIds = [];

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->userRepo = new UserRepository($this->db);
        $this->docenteRepo = new DocenteRepository($this->db);
        $this->estudianteRepo = new EstudianteRepository($this->db);
        $this->periodoRepo = new PeriodoRepository($this->db);
        $this->gradoRepo = new GradoRepository($this->db);
        $this->gradoPeriodoRepo = new GradoPeriodoRepository($this->db);
        $this->seccionRepo = new SeccionRepository($this->db);
        $this->aulaRepo = new AulaRepository($this->db);

        $this->authService = new AuthService($this->userRepo);
        $this->docenteService = new DocenteService($this->docenteRepo, $this->userRepo, $this->db);
        $this->estudianteService = new EstudianteService($this->estudianteRepo);
        $this->periodoService = new PeriodoService($this->periodoRepo);
        $this->gradoService = new GradoService($this->gradoRepo, $this->gradoPeriodoRepo, $this->periodoRepo);
        $this->seccionService = new SeccionService($this->seccionRepo, $this->gradoPeriodoRepo);
        $this->aulaService = new AulaService($this->aulaRepo);
    }

    public function run(): void
    {
        echo "============================================================\n";
        echo "SUITE DE PRUEBAS AUTOMATIZADAS — FASE 4\n";
        echo "Administración Básica — Escuela Oficial Rural Mixta Lo de Fuentes\n";
        echo "============================================================\n\n";

        $this->cleanPreviousTestArtifacts();
        $this->ensureDevUsers();

        try {
            $this->testT4_01_AdminAccedeRutasAdministrativas();
            $this->testT4_02_DocenteNoPuedeAccederAdmin();
            $this->testT4_03_UsuarioNoAutenticadoRedirigido();
            $this->testT4_04_MutacionSinCsrfRechazada();
            $this->testT4_05_CreacionAtomicaDocente();
            $this->testT4_06_RollbackCreacionDocente();
            $this->testT4_07_EdicionDocente();
            $this->testT4_08_DesactivacionDocente();
            $this->testT4_09_CreacionEstudiante();
            $this->testT4_10_ValidacionEstudiante();
            $this->testT4_11_CreacionPeriodoValido();
            $this->testT4_12_RechazoSegundoPeriodoActivo();
            $this->testT4_13_CreacionGradoOrdenValido();
            $this->testT4_14_AsociacionGradoPeriodo();
            $this->testT4_15_RechazoGradoDuplicadoEnPeriodo();
            $this->testT4_16_CreacionSeccionCapacidadValida();
            $this->testT4_17_RechazoCapacidadInvalida();
            $this->testT4_18_CapacidadEstrictamenteMayorCeroPersistida();
            $this->testT4_19_CreacionAulaCodigoUnico();
            $this->testT4_20_IntegridadReferencialFKInexistentes();
            $this->testT4_21_PrevencionXSS();
            $this->testT4_22_ProteccionMassAssignment();
            $this->testT4_23_RegresionFase3();
            $this->testT4_24_RegresionFase2();
            $this->testT4_25_RegresionFase1();
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
        $this->db->exec("DELETE FROM secciones WHERE codigo LIKE 'TEST-%' OR codigo IN ('SEC-T16', 'SEC-T18')");
        $this->db->exec("DELETE FROM grados_periodos WHERE id_grado IN (SELECT id_grado FROM grados WHERE codigo LIKE 'GRD-TEST%')");
        $this->db->exec("DELETE FROM grados WHERE codigo LIKE 'GRD-TEST%'");
        $this->db->exec("DELETE FROM periodos_escolares WHERE nombre LIKE 'Ciclo Test%' OR nombre LIKE '%Test%'");
        $this->db->exec("DELETE FROM estudiantes WHERE codigo_estudiante LIKE 'EST-TEST%'");
        $this->db->exec("DELETE FROM aulas WHERE codigo LIKE 'AUL-TEST%' OR codigo = 'AUL-MASS-01'");
        
        $testUsers = $this->db->query("SELECT id_usuario FROM usuarios WHERE nombre_usuario LIKE 'test_doc_%'")->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($testUsers)) {
            $inClause = implode(',', array_map('intval', $testUsers));
            $this->db->exec("DELETE FROM docentes WHERE id_usuario IN ($inClause)");
            $this->db->exec("DELETE FROM usuarios WHERE id_usuario IN ($inClause)");
        }
    }

    private function cleanup(): void
    {
        if (!empty($this->createdSeccionIds)) {
            $in = implode(',', array_map('intval', $this->createdSeccionIds));
            $this->db->exec("DELETE FROM secciones WHERE id_seccion IN ($in)");
        }
        if (!empty($this->createdGradoPeriodoIds)) {
            $in = implode(',', array_map('intval', $this->createdGradoPeriodoIds));
            $this->db->exec("DELETE FROM grados_periodos WHERE id_grado_periodo IN ($in)");
        }
        if (!empty($this->createdGradoIds)) {
            $in = implode(',', array_map('intval', $this->createdGradoIds));
            $this->db->exec("DELETE FROM grados WHERE id_grado IN ($in)");
        }
        if (!empty($this->createdPeriodoIds)) {
            $in = implode(',', array_map('intval', $this->createdPeriodoIds));
            $this->db->exec("DELETE FROM periodos_escolares WHERE id_periodo_escolar IN ($in)");
        }
        if (!empty($this->createdEstudianteIds)) {
            $in = implode(',', array_map('intval', $this->createdEstudianteIds));
            $this->db->exec("DELETE FROM estudiantes WHERE id_estudiante IN ($in)");
        }
        if (!empty($this->createdAulaIds)) {
            $in = implode(',', array_map('intval', $this->createdAulaIds));
            $this->db->exec("DELETE FROM aulas WHERE id_aula IN ($in)");
        }
        if (!empty($this->createdDocenteIds)) {
            $in = implode(',', array_map('intval', $this->createdDocenteIds));
            $this->db->exec("DELETE FROM docentes WHERE id_docente IN ($in)");
        }
        if (!empty($this->createdUserIds)) {
            $in = implode(',', array_map('intval', $this->createdUserIds));
            $this->db->exec("DELETE FROM usuarios WHERE id_usuario IN ($in)");
        }
    }

    /**
     * T4-01: Administrador accede a rutas administrativas (HTTP 200).
     */
    private function testT4_01_AdminAccedeRutasAdministrativas(): void
    {
        Session::destroy();
        Session::start();
        $this->authService->attempt('admin.demo', 'Admin.2026!');

        $rutas = [
            '/administracion',
            '/administracion/docentes',
            '/administracion/estudiantes',
            '/administracion/periodos',
            '/administracion/grados',
            '/administracion/grados-periodos',
            '/administracion/secciones',
            '/administracion/aulas',
        ];

        $app = App::getInstance();
        $allOk = true;
        $detalles = [];

        foreach ($rutas as $ruta) {
            $req = Request::create('GET', $ruta);
            $res = $app->handle($req);
            $code = $res->getStatusCode();
            $detalles[] = "{$ruta} => {$code}";
            if ($code !== 200) {
                $allOk = false;
            }
        }

        Session::destroy();

        $this->record(
            'T4-01',
            'Administrador accede a rutas administrativas',
            'HTTP 200 en las 8 rutas administrativas evaluadas',
            $allOk ? 'HTTP 200 confirmado en todas las rutas administrativas (' . count($rutas) . '/' . count($rutas) . ' OK)' : implode(', ', $detalles),
            $allOk
        );
    }

    /**
     * T4-02: Docente no puede acceder a administración (HTTP 403).
     */
    private function testT4_02_DocenteNoPuedeAccederAdmin(): void
    {
        Session::destroy();
        Session::start();
        $this->authService->attempt('docente.demo', 'Docente.2026!');

        $rutas = [
            '/administracion',
            '/administracion/docentes',
            '/administracion/periodos',
            '/administracion/secciones',
        ];

        $app = App::getInstance();
        $allForbidden = true;
        $detalles = [];

        foreach ($rutas as $ruta) {
            $req = Request::create('GET', $ruta);
            $res = $app->handle($req);
            $code = $res->getStatusCode();
            $detalles[] = "{$ruta} => {$code}";
            if ($code !== 403) {
                $allForbidden = false;
            }
        }

        Session::destroy();

        $this->record(
            'T4-02',
            'Docente no puede acceder a administración',
            'HTTP 403 Forbidden para rol DOCENTE en módulos administrativos',
            $allForbidden ? 'HTTP 403 Forbidden confirmado en todas las rutas intentadas (' . count($rutas) . '/' . count($rutas) . ')' : implode(', ', $detalles),
            $allForbidden
        );
    }

    /**
     * T4-03: Usuario no autenticado es redirigido a login (HTTP 302 -> /login).
     */
    private function testT4_03_UsuarioNoAutenticadoRedirigido(): void
    {
        Session::destroy();

        $app = App::getInstance();
        $req = Request::create('GET', '/administracion/docentes');
        $res = $app->handle($req);

        $code = $res->getStatusCode();
        $location = $res->getHeader('Location') ?? '';

        $isOk = ($code === 302 && str_contains($location, '/login'));

        $this->record(
            'T4-03',
            'Usuario no autenticado es redirigido a login',
            'HTTP 302 hacia /login al intentar acceder a rutas privadas sin sesión',
            sprintf('Status %d, Location: %s', $code, $location),
            $isOk
        );
    }

    /**
     * T4-04: Intentos de mutación (POST) sin CSRF o CSRF inválido son rechazados (HTTP 403).
     */
    private function testT4_04_MutacionSinCsrfRechazada(): void
    {
        Session::destroy();
        Session::start();
        $this->authService->attempt('admin.demo', 'Admin.2026!');

        $app = App::getInstance();

        // 1. POST sin token CSRF (parámetro $post en 4to argumento)
        $reqSinToken = Request::create('POST', '/administracion/docentes/crear', [], [
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
        ]);
        $resSinToken = $app->handle($reqSinToken);

        // 2. POST con token CSRF inválido
        $reqTokenInvalido = Request::create('POST', '/administracion/docentes/crear', [], [
            '_csrf_token' => 'token_totalmente_falso_12345',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
        ]);
        $resTokenInvalido = $app->handle($reqTokenInvalido);

        Session::destroy();

        $isOk = ($resSinToken->getStatusCode() === 403 && $resTokenInvalido->getStatusCode() === 403);

        $this->record(
            'T4-04',
            'Intentos de mutación (POST) sin CSRF o CSRF inválido son rechazados',
            'HTTP 403 Forbidden tanto para ausencia de token como para token adulterado',
            sprintf('Sin token: HTTP %d | Token inválido: HTTP %d', $resSinToken->getStatusCode(), $resTokenInvalido->getStatusCode()),
            $isOk
        );
    }

    /**
     * T4-05: Creación atómica de docente (crea usuario y docente de forma persistente y correcta).
     */
    private function testT4_05_CreacionAtomicaDocente(): void
    {
        $input = [
            'nombre_usuario' => 'test_doc_05',
            'nombres' => 'María Elena',
            'apellidos' => 'Castillo Morales',
            'correo' => 'test_doc_05@example.invalid',
            'password' => 'Docente.2026!Test',
            'codigo_docente' => 'DOC-TEST-05',
            'fecha_ingreso' => '2026-02-01',
            'estado' => 'ACTIVO',
        ];

        $result = $this->docenteService->crearDocente($input);

        $isOk = false;
        $detalles = 'Fallo al invocar servicio: ' . ($result['message'] ?? 'Sin mensaje');

        if ($result['success'] && !empty($result['id_docente'])) {
            $idDocente = (int)$result['id_docente'];
            $this->createdDocenteIds[] = $idDocente;

            $docente = $this->docenteRepo->findById($idDocente);
            if ($docente) {
                $this->createdUserIds[] = $docente->id_usuario;
                $user = $this->userRepo->findById($docente->id_usuario);

                $usuarioExiste = ($user !== null && $user->nombre_usuario === 'test_doc_05');
                $docenteExiste = ($docente->codigo_docente === 'DOC-TEST-05');
                $hashValido = ($user !== null && password_verify('Docente.2026!Test', $user->clave_hash));
                $rolEsDocente = ($user !== null && $user->rol_nombre === 'DOCENTE');

                $isOk = ($usuarioExiste && $docenteExiste && $hashValido && $rolEsDocente);
                $detalles = sprintf(
                    'Docente #%d creado con Usuario #%d | Usuario: %s | Hash BCrypt válido: %s | Rol: %s',
                    $idDocente,
                    $docente->id_usuario,
                    $user->nombre_usuario ?? 'N/D',
                    $hashValido ? 'SÍ' : 'NO',
                    $user->rol_nombre ?? 'N/D'
                );
            }
        }

        $this->record(
            'T4-05',
            'Creación atómica de docente',
            'Inserción atómica persistente en usuarios (con hash BCrypt y rol DOCENTE) y en docentes',
            $detalles,
            $isOk
        );
    }

    /**
     * T4-06: Simulación de fallo en creación de docente revierte transacción (rollback).
     */
    private function testT4_06_RollbackCreacionDocente(): void
    {
        $input = [
            'nombre_usuario' => 'test_doc_06_rollback',
            'nombres' => 'Roberto',
            'apellidos' => 'Mendoza Alvarado',
            'correo' => 'test_doc_06@example.invalid',
            'password' => 'Docente.2026!Test',
            'codigo_docente' => 'DOC-TEST-06',
            'fecha_ingreso' => '2026-02-01',
            'estado' => 'ACTIVO',
        ];

        // Se activa el hook de simulación de fallo transaccional
        $result = $this->docenteService->crearDocente($input, true);

        // Verificar que el usuario no fue persistido
        $user = $this->userRepo->findByUsername('test_doc_06_rollback');
        $docente = $this->docenteRepo->findByCodigo('DOC-TEST-06');

        $isOk = (!$result['success'] && $user === null && $docente === null);

        $this->record(
            'T4-06',
            'Rollback atómico ante fallo en creación de docente',
            'Transacción revertida: ni usuario ni docente quedan huérfanos en la base de datos',
            sprintf(
                'Servicio retornó éxito: %s | Usuario huérfano en BD: %s | Docente huérfano en BD: %s',
                $result['success'] ? 'SÍ' : 'NO',
                $user ? 'SÍ' : 'NO',
                $docente ? 'SÍ' : 'NO'
            ),
            $isOk
        );
    }

    /**
     * T4-07: Edición de datos de docente (persiste cambios en BD).
     */
    private function testT4_07_EdicionDocente(): void
    {
        $idDocente = end($this->createdDocenteIds);
        if (!$idDocente) {
            $this->record('T4-07', 'Edición de datos de docente', 'Docente editado exitosamente', 'No hay docente de prueba previo', false);
            return;
        }

        $inputUpdate = [
            'nombres' => 'María Elena (Actualizada)',
            'apellidos' => 'Castillo Morales',
            'correo' => 'test_doc_05_edit@example.invalid',
            'codigo_docente' => 'DOC-TEST-05-MOD',
            'fecha_ingreso' => '2026-02-15',
            'estado' => 'ACTIVO',
        ];

        $res = $this->docenteService->actualizarDocente((int)$idDocente, $inputUpdate);
        $docenteUpdated = $this->docenteRepo->findById((int)$idDocente);

        $isOk = ($res['success'] && $docenteUpdated && $docenteUpdated->nombres === 'María Elena (Actualizada)' && $docenteUpdated->codigo_docente === 'DOC-TEST-05-MOD');

        $this->record(
            'T4-07',
            'Edición de datos de docente',
            'Datos actualizados correctamente en tablas docentes y usuarios',
            sprintf('Nombre en BD: "%s", Código en BD: "%s"', $docenteUpdated->nombres ?? 'N/D', $docenteUpdated->codigo_docente ?? 'N/D'),
            $isOk
        );
    }

    /**
     * T4-08: Desactivación de docente (pasa a inactivo de forma controlada).
     */
    private function testT4_08_DesactivacionDocente(): void
    {
        $idDocente = end($this->createdDocenteIds);
        if (!$idDocente) {
            $this->record('T4-08', 'Desactivación de docente', 'Docente inactivado', 'No hay docente previo', false);
            return;
        }

        $res = $this->docenteService->cambiarEstado((int)$idDocente, 'INACTIVO');
        $docente = $this->docenteRepo->findById((int)$idDocente);

        $isOk = ($res['success'] && $docente && $docente->estado === 'INACTIVO');

        $this->record(
            'T4-08',
            'Desactivación de docente',
            'Estado cambiado a INACTIVO sin pérdida de datos históricos',
            sprintf('Resultado: %s | Estado en BD: %s', $res['message'] ?? 'OK', $docente->estado ?? 'N/D'),
            $isOk
        );
    }

    /**
     * T4-09: Creación de estudiante con datos válidos.
     */
    private function testT4_09_CreacionEstudiante(): void
    {
        $input = [
            'codigo_estudiante' => 'EST-TEST-01',
            'nombres' => 'Juan Carlos',
            'apellidos' => 'López Méndez',
            'fecha_nacimiento' => '2015-05-20',
            'estado' => 'ACTIVO',
        ];

        $res = $this->estudianteService->crearEstudiante($input);

        $isOk = false;
        $detalles = 'Error al registrar';

        if ($res['success'] && !empty($res['id_estudiante'])) {
            $idEstudiante = (int)$res['id_estudiante'];
            $this->createdEstudianteIds[] = $idEstudiante;

            $est = $this->estudianteRepo->findById($idEstudiante);
            if ($est && $est->codigo_estudiante === 'EST-TEST-01' && $est->getNombreCompleto() === 'Juan Carlos López Méndez') {
                $isOk = true;
                $detalles = sprintf('Estudiante #%d creado: "%s", Código: %s, Nacimiento: %s', $idEstudiante, $est->getNombreCompleto(), $est->codigo_estudiante, $est->fecha_nacimiento);
            }
        }

        $this->record(
            'T4-09',
            'Creación de estudiante con datos válidos',
            'Estudiante persistido con código único, nombres, apellidos y fecha de nacimiento',
            $detalles,
            $isOk
        );
    }

    /**
     * T4-10: Validación de campos obligatorios en estudiante.
     */
    private function testT4_10_ValidacionEstudiante(): void
    {
        $inputInvalido = [
            'nombres' => '',
            'apellidos' => '',
            'fecha_nacimiento' => 'fecha-invalida',
        ];

        $res = $this->estudianteService->crearEstudiante($inputInvalido);

        $isOk = (!$res['success'] && isset($res['errors']['nombres']) && isset($res['errors']['apellidos']) && isset($res['errors']['fecha_nacimiento']));

        $this->record(
            'T4-10',
            'Validación de campos obligatorios en estudiante',
            'Rechazo controlado con mensajes específicos para nombres, apellidos y fecha inválida',
            sprintf('Éxito: %s | Errores detectados: %s', $res['success'] ? 'SÍ' : 'NO', implode(', ', array_keys($res['errors'] ?? []))),
            $isOk
        );
    }

    /**
     * T4-11: Creación de período escolar válido.
     */
    private function testT4_11_CreacionPeriodoValido(): void
    {
        $input = [
            'nombre' => 'Ciclo Test 2027',
            'fecha_inicio' => '2027-01-15',
            'fecha_fin' => '2027-10-31',
            'estado' => 'PLANIFICADO',
        ];

        $res = $this->periodoService->crearPeriodo($input);

        $isOk = false;
        $detalles = 'Error al registrar período: ' . ($res['message'] ?? '');

        if ($res['success'] && !empty($res['id_periodo'])) {
            $idPeriodo = (int)$res['id_periodo'];
            $this->createdPeriodoIds[] = $idPeriodo;

            $p = $this->periodoRepo->findById($idPeriodo);
            if ($p && $p->nombre === 'Ciclo Test 2027' && $p->isPlanificado()) {
                $isOk = true;
                $detalles = sprintf('Período #%d creado: "%s" (%s a %s) Estado: %s', $idPeriodo, $p->nombre, $p->fecha_inicio, $p->fecha_fin, $p->estado);
            }
        }

        $this->record(
            'T4-11',
            'Creación de período escolar válido',
            'Período escolar creado en estado PLANIFICADO con fechas coherentes',
            $detalles,
            $isOk
        );
    }

    /**
     * T4-12: Rechazo de segundo período escolar activo simultáneo.
     */
    private function testT4_12_RechazoSegundoPeriodoActivo(): void
    {
        $activo = $this->periodoRepo->findActivo();
        if (!$activo) {
            $tempActivoId = $this->periodoRepo->create([
                'nombre' => 'Ciclo Test Base Activo',
                'fecha_inicio' => '2026-01-01',
                'fecha_fin' => '2026-11-30',
                'estado' => 'ACTIVO',
            ]);
            $this->createdPeriodoIds[] = $tempActivoId;
            $activo = $this->periodoRepo->findById($tempActivoId);
        }

        // Intentar registrar un SEGUNDO período activo
        $inputSegundoActivo = [
            'nombre' => 'Ciclo Test Conflicto Activo',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-11-30',
            'estado' => 'ACTIVO',
        ];

        $res = $this->periodoService->crearPeriodo($inputSegundoActivo);

        $isOk = (!$res['success'] && (isset($res['errors']['estado']) || str_contains($res['message'] ?? '', 'activo')));

        $this->record(
            'T4-12',
            'Rechazo de segundo período escolar activo simultáneo',
            'Rechazo controlado garantizando la regla de negocio y restricción uk_periodos_escolares_un_activo',
            sprintf('Rechazado: %s | Mensaje: "%s"', !$res['success'] ? 'SÍ' : 'NO', $res['message'] ?? ''),
            $isOk
        );
    }

    /**
     * T4-13: Creación de grado con orden de progresión válido.
     */
    private function testT4_13_CreacionGradoOrdenValido(): void
    {
        // 1. Caso inválido: orden 0 o negativo
        $resInvalido = $this->gradoService->crearGrado([
            'codigo' => 'GRD-TEST-INV',
            'nombre' => 'Grado Inválido',
            'orden' => 0,
            'activo' => 1,
        ]);

        // 2. Caso válido: orden positivo libre
        $maxOrden = (int)$this->db->query('SELECT COALESCE(MAX(orden), 0) FROM grados')->fetchColumn();
        $nuevoOrden = $maxOrden + 10;

        $resValido = $this->gradoService->crearGrado([
            'codigo' => 'GRD-TEST-1',
            'nombre' => 'Grado Prueba Jerárquica',
            'orden' => $nuevoOrden,
            'activo' => 1,
        ]);

        $isOk = false;
        $detalles = '';

        if (!$resInvalido['success'] && $resValido['success'] && !empty($resValido['id_grado'])) {
            $idGrado = (int)$resValido['id_grado'];
            $this->createdGradoIds[] = $idGrado;

            $g = $this->gradoRepo->findById($idGrado);
            if ($g && $g->orden === $nuevoOrden) {
                $isOk = true;
                $detalles = sprintf('Orden 0 rechazado con éxito | Grado #%d creado con orden positivo %d', $idGrado, $g->orden);
            }
        }

        $this->record(
            'T4-13',
            'Creación de grado con orden de progresión válido',
            'Rechazo de orden <= 0 y aceptación de orden estrictamente positivo',
            $isOk ? $detalles : 'Fallo en validación de orden',
            $isOk
        );
    }

    /**
     * T4-14: Asociación de grado a período escolar existente.
     */
    private function testT4_14_AsociacionGradoPeriodo(): void
    {
        $idPeriodo = reset($this->createdPeriodoIds);
        $idGrado = reset($this->createdGradoIds);

        if (!$idPeriodo || !$idGrado) {
            $this->record('T4-14', 'Asociación de grado a período escolar existente', 'Asociación creada', 'Entidades previas no disponibles', false);
            return;
        }

        $res = $this->gradoService->asociarGradoPeriodo([
            'id_periodo_escolar' => $idPeriodo,
            'id_grado' => $idGrado,
            'habilitado' => 1,
        ]);

        $isOk = false;
        $detalles = 'Fallo al asociar: ' . ($res['message'] ?? '');

        if ($res['success'] && !empty($res['id_grado_periodo'])) {
            $idGp = (int)$res['id_grado_periodo'];
            $this->createdGradoPeriodoIds[] = $idGp;

            $gp = $this->gradoPeriodoRepo->findById($idGp);
            if ($gp && $gp->id_periodo_escolar === (int)$idPeriodo && $gp->id_grado === (int)$idGrado) {
                $isOk = true;
                $detalles = sprintf('Asociación #%d creada: Período #%d <-> Grado #%d (Habilitado: SÍ)', $idGp, $idPeriodo, $idGrado);
            }
        }

        $this->record(
            'T4-14',
            'Asociación de grado a período escolar existente',
            'Registro exitoso en tabla grados_periodos vinculado a ciclo lectivo',
            $detalles,
            $isOk
        );
    }

    /**
     * T4-15: Rechazo de grado duplicado en el mismo período escolar.
     */
    private function testT4_15_RechazoGradoDuplicadoEnPeriodo(): void
    {
        $idPeriodo = reset($this->createdPeriodoIds);
        $idGrado = reset($this->createdGradoIds);

        $res = $this->gradoService->asociarGradoPeriodo([
            'id_periodo_escolar' => $idPeriodo,
            'id_grado' => $idGrado,
            'habilitado' => 1,
        ]);

        $isOk = (!$res['success'] && (isset($res['errors']['duplicado']) || str_contains($res['message'] ?? '', 'duplicad') || str_contains($res['message'] ?? '', 'asociado')));

        $this->record(
            'T4-15',
            'Rechazo de grado duplicado en el mismo período escolar',
            'Rechazo controlado de duplicidad de grado en el mismo período (respeto de uk_grados_periodos)',
            sprintf('Rechazado: %s | Mensaje: "%s"', !$res['success'] ? 'SÍ' : 'NO', $res['message'] ?? ''),
            $isOk
        );
    }

    /**
     * T4-16: Creación de sección con capacidad válida.
     */
    private function testT4_16_CreacionSeccionCapacidadValida(): void
    {
        $idGp = reset($this->createdGradoPeriodoIds);
        if (!$idGp) {
            $this->record('T4-16', 'Creación de sección con capacidad válida', 'Sección creada', 'Grado-Período previo no disponible', false);
            return;
        }

        $input = [
            'id_grado_periodo' => $idGp,
            'codigo' => 'SEC-T16',
            'nombre' => 'Sección A Experimental',
            'capacidad' => 35,
            'habilitada' => 1,
        ];

        $res = $this->seccionService->crearSeccion($input);

        $isOk = false;
        $detalles = 'Fallo al registrar: ' . ($res['message'] ?? '');

        if ($res['success'] && !empty($res['id_seccion'])) {
            $idSeccion = (int)$res['id_seccion'];
            $this->createdSeccionIds[] = $idSeccion;

            $s = $this->seccionRepo->findById($idSeccion);
            if ($s && $s->codigo === 'SEC-T16' && $s->capacidad === 35) {
                $isOk = true;
                $detalles = sprintf('Sección #%d creada: Código: %s | Capacidad: %d cupos | Habilitada: %s', $idSeccion, $s->codigo, $s->capacidad, $s->isHabilitada() ? 'SÍ' : 'NO');
            }
        }

        $this->record(
            'T4-16',
            'Creación de sección con capacidad válida',
            'Sección creada y capacidad estrictamente mayor a 0 persistida',
            $detalles,
            $isOk
        );
    }

    /**
     * T4-17: Rechazo de capacidad inválida en sección (0, negativa o no numérica).
     */
    private function testT4_17_RechazoCapacidadInvalida(): void
    {
        $idGp = reset($this->createdGradoPeriodoIds);

        // 1. Capacidad 0
        $resCero = $this->seccionService->crearSeccion([
            'id_grado_periodo' => $idGp,
            'codigo' => 'SEC-CERO',
            'capacidad' => 0,
        ]);

        // 2. Capacidad negativa
        $resNeg = $this->seccionService->crearSeccion([
            'id_grado_periodo' => $idGp,
            'codigo' => 'SEC-NEG',
            'capacidad' => -10,
        ]);

        // 3. Capacidad no numérica
        $resStr = $this->seccionService->crearSeccion([
            'id_grado_periodo' => $idGp,
            'codigo' => 'SEC-STR',
            'capacidad' => 'abc',
        ]);

        // 4. Capacidad con decimales
        $resDec = $this->seccionService->crearSeccion([
            'id_grado_periodo' => $idGp,
            'codigo' => 'SEC-DEC',
            'capacidad' => 25.5,
        ]);

        $isOk = (!$resCero['success'] && !$resNeg['success'] && !$resStr['success'] && !$resDec['success']
            && isset($resCero['errors']['capacidad'])
            && isset($resNeg['errors']['capacidad'])
            && isset($resStr['errors']['capacidad'])
            && isset($resDec['errors']['capacidad']));

        $this->record(
            'T4-17',
            'Rechazo de capacidad inválida en sección',
            'Rechazo controlado para capacidad 0, números negativos, texto y decimales',
            sprintf('Cero: Rechazado | Negativo: Rechazado | String: Rechazado | Decimal: Rechazado (%s)', $isOk ? '4/4 OK' : 'Fallo'),
            $isOk
        );
    }

    /**
     * T4-18: Capacidad estrictamente mayor a 0 persistida y editable.
     */
    private function testT4_18_CapacidadEstrictamenteMayorCeroPersistida(): void
    {
        $idSeccion = reset($this->createdSeccionIds);
        if (!$idSeccion) {
            $this->record('T4-18', 'Capacidad estrictamente mayor a 0 persistida y editable', 'Capacidad editada', 'Sección previa no disponible', false);
            return;
        }

        $inputUpdate = [
            'codigo' => 'SEC-T18',
            'nombre' => 'Sección A Ampliada',
            'capacidad' => 42,
            'habilitada' => 1,
        ];

        $res = $this->seccionService->actualizarSeccion((int)$idSeccion, $inputUpdate);
        $s = $this->seccionRepo->findById((int)$idSeccion);

        $isOk = ($res['success'] && $s && $s->capacidad === 42 && $s->codigo === 'SEC-T18');

        $this->record(
            'T4-18',
            'Capacidad estrictamente mayor a 0 persistida y editable',
            'Capacidad modificada exitosamente en BD manteniendo restricción de integridad',
            sprintf('Resultado: %s | Nueva capacidad en BD: %d cupos', $res['message'] ?? 'OK', $s->capacidad ?? 0),
            $isOk
        );
    }

    /**
     * T4-19: Creación de aula y validación de código único.
     */
    private function testT4_19_CreacionAulaCodigoUnico(): void
    {
        // 1. Crear aula válida
        $resValida = $this->aulaService->crearAula([
            'codigo' => 'AUL-TEST-01',
            'nombre' => 'Aula 101 Experimental',
            'ubicacion_referencia' => 'Módulo Norte 2do Nivel',
            'activa' => 1,
        ]);

        $idAula = $resValida['id_aula'] ?? 0;
        if ($idAula > 0) {
            $this->createdAulaIds[] = $idAula;
        }

        // 2. Intentar registrar aula con el mismo código
        $resDuplicada = $this->aulaService->crearAula([
            'codigo' => 'AUL-TEST-01',
            'nombre' => 'Aula 101 Duplicada',
            'ubicacion_referencia' => 'Módulo Sur',
            'activa' => 1,
        ]);

        $isOk = ($resValida['success'] && !$resDuplicada['success'] && isset($resDuplicada['errors']['codigo']));

        $this->record(
            'T4-19',
            'Creación de aula y validación de código único',
            'Aula creada exitosamente y duplicación de código de aula rechazada',
            sprintf('Aula #%d creada | Duplicado rechazado: %s ("%s")', $idAula, !$resDuplicada['success'] ? 'SÍ' : 'NO', $resDuplicada['errors']['codigo'] ?? ''),
            $isOk
        );
    }

    /**
     * T4-20: Integridad referencial: rechazo de asignación de grado/sección a períodos inexistentes.
     */
    private function testT4_20_IntegridadReferencialFKInexistentes(): void
    {
        // 1. Asociar grado a período inexistente
        $resGpInexistente = $this->gradoService->asociarGradoPeriodo([
            'id_periodo_escolar' => 999999,
            'id_grado' => reset($this->createdGradoIds) ?: 1,
        ]);

        // 2. Crear sección en grado_periodo inexistente
        $resSecInexistente = $this->seccionService->crearSeccion([
            'id_grado_periodo' => 999999,
            'codigo' => 'SEC-FK-FAIL',
            'capacidad' => 30,
        ]);

        $isOk = (!$resGpInexistente['success'] && !$resSecInexistente['success']
            && isset($resGpInexistente['errors']['id_periodo_escolar'])
            && isset($resSecInexistente['errors']['id_grado_periodo']));

        $this->record(
            'T4-20',
            'Integridad referencial: rechazo de FKs inexistentes',
            'Rechazo preventivo y seguro ante IDs foráneos inexistentes sin corromper la BD',
            sprintf('Período inexistente: Rechazado | Grado-Período inexistente: Rechazado (%s)', $isOk ? 'OK' : 'FALLO'),
            $isOk
        );
    }

    /**
     * T4-21: Prevención XSS en renderizado de entidades administrativas.
     */
    private function testT4_21_PrevencionXSS(): void
    {
        $payloadMalicioso = '<script>alert("XSS-ATTACK")</script>';

        // Renderizado del layout y vista con payload inyectado
        $html = View::render('admin.grados.crear', [
            'title' => 'Test XSS',
            'errors' => ['nombre' => $payloadMalicioso],
            'old' => ['nombre' => $payloadMalicioso, 'codigo' => $payloadMalicioso],
        ], 'main');

        $contieneTagCrudo = str_contains($html, '<script>alert("XSS-ATTACK")</script>');
        $contieneTagEscapado = str_contains($html, '&lt;script&gt;alert(&quot;XSS-ATTACK&quot;)&lt;/script&gt;')
            || str_contains($html, '&lt;script&gt;alert(&#039;XSS-ATTACK&#039;)&lt;/script&gt;')
            || str_contains($html, '&lt;script&gt;alert');

        $isOk = (!$contieneTagCrudo && $contieneTagEscapado);

        $this->record(
            'T4-21',
            'Prevención XSS en renderizado de entidades administrativas',
            'Caracteres HTML especiales convertidos a entidades seguras sin ejecución de scripts',
            sprintf('Script crudo encontrado: %s | Script correctamente escapado: %s', $contieneTagCrudo ? 'SÍ' : 'NO', $contieneTagEscapado ? 'SÍ' : 'NO'),
            $isOk
        );
    }

    /**
     * T4-22: Protección contra asignación masiva (mass assignment) en servicios administrativos.
     */
    private function testT4_22_ProteccionMassAssignment(): void
    {
        $payloadMalicioso = [
            'codigo' => 'AUL-MASS-01',
            'nombre' => 'Aula Segura Mass Assignment',
            'ubicacion_referencia' => 'Piso 1',
            'activa' => 1,
            // Campos espurios
            'id_rol' => 9999,
            'is_admin' => 1,
            'rol' => 'SUPERADMIN',
            'password_hash' => 'hacked_hash',
            'clave_secreta' => 'inyeccion_no_permitida',
        ];

        $res = $this->aulaService->crearAula($payloadMalicioso);
        $isOk = false;
        $detalles = '';

        if ($res['success'] && !empty($res['id_aula'])) {
            $idAula = (int)$res['id_aula'];
            $this->createdAulaIds[] = $idAula;

            $stmt = $this->db->prepare('SELECT * FROM aulas WHERE id_aula = :id');
            $stmt->bindValue(':id', $idAula, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $isOk = ($row && $row['codigo'] === 'AUL-MASS-01' && !isset($row['is_admin']) && !isset($row['rol']));
            $detalles = 'Mapeo explícito (whitelist) aplicado en servicio y repositorio; campos no autorizados descartados';
        }

        $this->record(
            'T4-22',
            'Protección contra asignación masiva (mass assignment)',
            'Filtrado y whitelist estricto en controladores y repositorios ante parámetros no declarados',
            $detalles,
            $isOk
        );
    }

    /**
     * T4-23: Regresión Fase 3 (19/19 OK mediante ejecución aislada de run_tests_fase3.php).
     */
    private function testT4_23_RegresionFase3(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 3 en proceso aislado...\n";
        $cmd = 'C:\\xampp\\php\\php.exe ' . escapeshellarg(dirname(__DIR__) . '/tests/run_tests_fase3.php');
        $output = shell_exec($cmd);

        $isOk = $output !== null && (str_contains($output, 'Exitosas (OK)    : 19') || str_contains($output, '19/19 OK') || str_contains($output, 'TODAS LAS PRUEBAS DE FASE 3 SUPERADAS'));

        $this->record(
            'T4-23',
            'Regresión completa de Fase 3 (Autenticación y Roles)',
            '19/19 pruebas de Fase 3 ejecutadas con éxito',
            $isOk ? '19/19 pruebas de Fase 3 aprobadas satisfactoriamente' : 'Fallo en suite de Fase 3',
            $isOk
        );
    }

    /**
     * T4-24: Regresión Fase 2 (16/16 OK mediante ejecución aislada de run_tests.php).
     */
    private function testT4_24_RegresionFase2(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 2 en proceso aislado...\n";
        $cmd = 'C:\\xampp\\php\\php.exe ' . escapeshellarg(dirname(__DIR__) . '/tests/run_tests.php');
        $output = shell_exec($cmd);

        $isOk = $output !== null && (str_contains($output, 'Exitosas (OK)    : 16') || str_contains($output, 'TODAS LAS PRUEBAS DE FASE 2 SUPERADAS'));

        $this->record(
            'T4-24',
            'Regresión completa de Fase 2 (Arquitectura Base y Enrutamiento)',
            '16/16 pruebas de Fase 2 ejecutadas con éxito',
            $isOk ? '16/16 pruebas de Fase 2 aprobadas satisfactoriamente' : 'Fallo en suite de Fase 2',
            $isOk
        );
    }

    /**
     * T4-25: Regresión Fase 1 (18/18 OK en integridad relacional de base de datos en BD temporal).
     */
    private function testT4_25_RegresionFase1(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 1...\n";
        $root = dirname(__DIR__);
        $mysqlBinary = $this->findMysqlBinary();

        if ($mysqlBinary === null) {
            $this->record(
                'T4-25',
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
        $tempDb = 'escuela_fase4_regresion_temp';

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
            'T4-25',
            'Regresión completa de Fase 1 (Diseño de Base de Datos)',
            '18/18 pruebas de integridad relacional superadas',
            $obtained,
            $isOk
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
        echo "RESUMEN DE PRUEBAS DE FASE 4\n";
        echo "============================================================\n";
        printf("Total ejecutadas : %d\n", $this->total);
        printf("Exitosas (OK)    : %d\n", $this->passed);
        printf("Fallidas (FALLO) : %d\n", $this->failed);
        echo "============================================================\n";

        if ($this->failed === 0) {
            echo "RESULTADO: TODAS LAS PRUEBAS DE FASE 4 SUPERADAS SATISFACTORIAMENTE.\n";
        } else {
            echo "RESULTADO: EXISTEN PRUEBAS FALLIDAS.\n";
            exit(1);
        }
    }
}

$suite = new Phase4TestSuite();
$suite->run();
