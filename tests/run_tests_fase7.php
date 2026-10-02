<?php

declare(strict_types=1);

/**
 * Suite de pruebas automatizadas para Fase 7 — Portal Docente.
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Cobertura obligatoria:
 * T7-01 a T7-20: Pruebas unitarias, funcionales, de seguridad y aislamiento horizontal.
 * T7-21: Flujo HTTP real mediante servidor embebido PHP y cURL.
 * T7-22: Regresión Fase 6 (30/30).
 * T7-23: Regresión Fase 5 (26/26).
 * T7-24: Regresión Fase 4 (27/27).
 * T7-25: Regresión Fase 3 (19/19).
 * T7-26: Regresión Fase 2 (16/16).
 * T7-27: Regresión Fase 1 (18/18).
 * Total: 27 pruebas automatizadas.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\DocenteController;
use App\Core\App;
use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\AsignacionDocente;
use App\Models\Docente;
use App\Models\User;
use App\Repositories\AsignacionRepository;
use App\Repositories\AulaRepository;
use App\Repositories\DocenteRepository;
use App\Repositories\GradoPeriodoRepository;
use App\Repositories\GradoRepository;
use App\Repositories\InscripcionRepository;
use App\Repositories\PeriodoRepository;
use App\Repositories\PortalDocenteRepository;
use App\Repositories\SeccionRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\PortalDocenteService;

class Phase7TestSuite
{
    private PDO $db;
    private UserRepository $userRepo;
    private DocenteRepository $docenteRepo;
    private PeriodoRepository $periodoRepo;
    private GradoRepository $gradoRepo;
    private GradoPeriodoRepository $gradoPeriodoRepo;
    private SeccionRepository $seccionRepo;
    private AulaRepository $aulaRepo;
    private AsignacionRepository $asignacionRepo;
    private InscripcionRepository $inscripcionRepo;
    private PortalDocenteRepository $portalRepo;

    private AuthService $authService;
    private PortalDocenteService $portalService;

    private int $total = 0;
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];

    // Fixtures creados para las pruebas
    private ?int $fixturePeriodoId = null;
    private ?int $fixtureGradoId = null;
    private ?int $fixtureGradoPeriodoId = null;
    private ?int $fixtureSeccionAId = null; // Asignada a Docente 1
    private ?int $fixtureSeccionBId = null; // Asignada a Docente 1 (multi-asignación)
    private ?int $fixtureSeccionCId = null; // Asignada a Docente 2 (para aislamiento horizontal)
    private ?int $fixtureAula1Id = null;
    private ?int $fixtureAula2Id = null;
    private ?int $fixtureAula3Id = null;

    private ?int $fixtureUserDocente1Id = null;
    private ?int $fixtureDocente1Id = null;
    private ?int $fixtureUserDocente2Id = null;
    private ?int $fixtureDocente2Id = null;
    private ?int $fixtureUserDocenteLibreId = null;
    private ?int $fixtureDocenteLibreId = null;
    private ?int $fixtureUserDocenteInactivoId = null;
    private ?int $fixtureDocenteInactivoId = null;
    private ?int $fixtureUserAdminId = null;

    private ?int $fixtureAsignacionAId = null;
    private ?int $fixtureAsignacionBId = null;
    private ?int $fixtureAsignacionCId = null;
    private ?int $fixtureAsignacionInactivaId = null;

    private array $createdEstudianteIds = [];
    private array $createdInscripcionIds = [];

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
        $this->inscripcionRepo = new InscripcionRepository($this->db);
        $this->portalRepo = new PortalDocenteRepository($this->db);

        $this->authService = new AuthService($this->userRepo);
        $this->portalService = new PortalDocenteService($this->portalRepo);
    }

    public function run(): void
    {
        echo "============================================================\n";
        echo "SUITE DE PRUEBAS AUTOMATIZADAS — FASE 7\n";
        echo "Portal Docente — Escuela Oficial Rural Mixta Lo de Fuentes\n";
        echo "============================================================\n\n";

        $this->cleanPreviousArtifacts();
        $this->setupFixtures();

        try {
            $this->testT7_01_DocenteAccesoHttp200();
            $this->testT7_02_AnonimoRedirigidoLogin();
            $this->testT7_03_AdminRechazado403();
            $this->testT7_04_PerfilDocenteCorrecto();
            $this->testT7_05_ContextoAcademicoVisible();
            $this->testT7_06_DocenteSinAsignacionEstadoAmigable();
            $this->testT7_07_NominaEstudiantesConfirmadosRF17();
            $this->testT7_08_ExclusionEstudiantesOtrasSecciones();
            $this->testT7_09_ExclusionInscripcionesNoConfirmadasOInactivas();
            $this->testT7_10_AislamientoHorizontalPorSeccion();
            $this->testT7_11_ManipulacionIdDocenteIgnorada();
            $this->testT7_12_ManipulacionIdAsignacionRechazada();
            $this->testT7_13_SoporteMultiplesAsignaciones();
            $this->testT7_14_ConsistenciaContadorEstudiantes();
            $this->testT7_15_PrevencionXSSNombresEstudiantes();
            $this->testT7_16_MinimizacionPrivacidadEstudiantesRNF04();
            $this->testT7_17_RevocacionAccesoDocenteInactivo();
            $this->testT7_18_AislamientoAsignacionInactiva();
            $this->testT7_19_IntegracionFase6Fase7RF31();
            $this->testT7_20_ReflejoCambioEstadoInscripcion();
            $this->testT7_21_FlujoHttpReal();
            $this->testT7_22_RegresionFase6();
            $this->testT7_23_RegresionFase5();
            $this->testT7_24_RegresionFase4();
            $this->testT7_25_RegresionFase3();
            $this->testT7_26_RegresionFase2();
            $this->testT7_27_RegresionFase1();
            $this->testT7_28_SeguridadHttpReal();
        } finally {
            $this->cleanup();
        }

        $this->printSummary();
    }

    private function record(string $code, string $description, string $expected, string $obtained, bool $passed): void
    {
        $this->total++;
        if ($passed) {
            $this->passed++;
            $status = 'OK';
        } else {
            $this->failed++;
            $status = 'FALLO';
        }

        $this->results[] = [
            'code' => $code,
            'description' => $description,
            'expected' => $expected,
            'obtained' => $obtained,
            'status' => $status,
        ];

        printf("[%s] %s: %s\n", $status, $code, $description);
        if (!$passed) {
            printf("       Esperado: %s\n", $expected);
            printf("       Obtenido: %s\n", $obtained);
        }
    }

    private function setupFixtures(): void
    {
        // 1. Roles
        $roleDocenteId = (int)$this->db->query("SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'")->fetchColumn();
        $roleAdminId = (int)$this->db->query("SELECT id_rol FROM roles WHERE nombre IN ('ADMINISTRACION', 'ADMINISTRADOR') LIMIT 1")->fetchColumn();

        // 2. Usuarios y Docentes
        // Docente 1 (Titular principal con 2 asignaciones)
        $hash = password_hash('DocenteFase7Pass!', PASSWORD_BCRYPT);
        $stmtU = $this->db->prepare('INSERT INTO usuarios (id_rol, nombre_usuario, clave_hash, correo, nombres, apellidos, estado) VALUES (:r, :u, :p, :c, :n, :a, "ACTIVO")');
        
        $u1 = 'f7_docente_' . uniqid();
        $c1 = $u1 . '@escuela.edu.gt';
        $stmtU->execute([':r' => $roleDocenteId, ':u' => $u1, ':p' => $hash, ':c' => $c1, ':n' => 'Marta Elena', ':a' => 'Morales Solís']);
        $this->fixtureUserDocente1Id = (int)$this->db->lastInsertId();

        $cd1 = 'DOC-F7-01-' . rand(100, 999);
        $stmtD = $this->db->prepare('INSERT INTO docentes (id_usuario, codigo_docente, fecha_ingreso, estado) VALUES (:u, :c, "2024-01-15", "ACTIVO")');
        $stmtD->execute([':u' => $this->fixtureUserDocente1Id, ':c' => $cd1]);
        $this->fixtureDocente1Id = (int)$this->db->lastInsertId();

        // Docente 2 (Para prueba de aislamiento horizontal de secciones)
        $u2 = 'f7_doc2_' . uniqid();
        $c2 = $u2 . '@escuela.edu.gt';
        $stmtU->execute([':r' => $roleDocenteId, ':u' => $u2, ':p' => $hash, ':c' => $c2, ':n' => 'Carlos Andrés', ':a' => 'Pérez Ramos']);
        $this->fixtureUserDocente2Id = (int)$this->db->lastInsertId();

        $cd2 = 'DOC-F7-02-' . rand(100, 999);
        $stmtD->execute([':u' => $this->fixtureUserDocente2Id, ':c' => $cd2]);
        $this->fixtureDocente2Id = (int)$this->db->lastInsertId();

        // Docente Libre (Sin asignaciones docentes)
        $u3 = 'f7_libre_' . uniqid();
        $c3 = $u3 . '@escuela.edu.gt';
        $stmtU->execute([':r' => $roleDocenteId, ':u' => $u3, ':p' => $hash, ':c' => $c3, ':n' => 'Lucía Regina', ':a' => 'Castillo Gómez']);
        $this->fixtureUserDocenteLibreId = (int)$this->db->lastInsertId();

        $cd3 = 'DOC-F7-03-' . rand(100, 999);
        $stmtD->execute([':u' => $this->fixtureUserDocenteLibreId, ':c' => $cd3]);
        $this->fixtureDocenteLibreId = (int)$this->db->lastInsertId();

        // Docente Inactivo
        $u4 = 'f7_inactivo_' . uniqid();
        $c4 = $u4 . '@escuela.edu.gt';
        $stmtU->execute([':r' => $roleDocenteId, ':u' => $u4, ':p' => $hash, ':c' => $c4, ':n' => 'Roberto José', ':a' => 'Méndez Ruiz']);
        $this->fixtureUserDocenteInactivoId = (int)$this->db->lastInsertId();

        $cd4 = 'DOC-F7-04-' . rand(100, 999);
        $stmtDInac = $this->db->prepare('INSERT INTO docentes (id_usuario, codigo_docente, fecha_ingreso, estado) VALUES (:u, :c, "2024-01-15", "INACTIVO")');
        $stmtDInac->execute([':u' => $this->fixtureUserDocenteInactivoId, ':c' => $cd4]);
        $this->fixtureDocenteInactivoId = (int)$this->db->lastInsertId();

        // Usuario Administrador
        $uAdmin = 'f7_admin_' . uniqid();
        $cAdmin = $uAdmin . '@escuela.edu.gt';
        $stmtU->execute([':r' => $roleAdminId, ':u' => $uAdmin, ':p' => $hash, ':c' => $cAdmin, ':n' => 'Administrador', ':a' => 'Prueba']);
        $this->fixtureUserAdminId = (int)$this->db->lastInsertId();

        // 3. Estructura Académica (Periodo ACTIVO existente, Grado, GradoPeriodo, Secciones, Aulas)
        $this->fixturePeriodoId = (int)$this->db->query("SELECT id_periodo_escolar FROM periodos_escolares WHERE estado = 'ACTIVO' LIMIT 1")->fetchColumn();
        if ($this->fixturePeriodoId === 0) {
            // Si no hay activo, tomar el primero disponible
            $this->fixturePeriodoId = (int)$this->db->query("SELECT id_periodo_escolar FROM periodos_escolares ORDER BY id_periodo_escolar DESC LIMIT 1")->fetchColumn();
        }

        $stmtG = $this->db->prepare('INSERT INTO grados (codigo, nombre, orden, activo) VALUES (:c, :n, :o, 1)');
        $gCode = 'GRD-F7-' . rand(100, 999);
        $gOrden = (int)$this->db->query("SELECT IFNULL(MAX(orden), 0) + 1 FROM grados")->fetchColumn();
        $stmtG->execute([':c' => $gCode, ':n' => 'Tercero Primaria F7 ' . uniqid(), ':o' => $gOrden]);
        $this->fixtureGradoId = (int)$this->db->lastInsertId();

        $stmtGP = $this->db->prepare('INSERT INTO grados_periodos (id_grado, id_periodo_escolar, habilitado) VALUES (:g, :p, 1)');
        $stmtGP->execute([':g' => $this->fixtureGradoId, ':p' => $this->fixturePeriodoId]);
        $this->fixtureGradoPeriodoId = (int)$this->db->lastInsertId();

        // Aulas
        $stmtAu = $this->db->prepare('INSERT INTO aulas (codigo, nombre, ubicacion_referencia, activa) VALUES (:c, :n, "Pabellón F7", 1)');
        $aCod1 = 'A-F7-1-' . rand(10, 99);
        $stmtAu->execute([':c' => $aCod1, ':n' => 'Aula 1 Fase 7']);
        $this->fixtureAula1Id = (int)$this->db->lastInsertId();

        $aCod2 = 'A-F7-2-' . rand(10, 99);
        $stmtAu->execute([':c' => $aCod2, ':n' => 'Aula 2 Fase 7']);
        $this->fixtureAula2Id = (int)$this->db->lastInsertId();

        $aCod3 = 'A-F7-3-' . rand(10, 99);
        $stmtAu->execute([':c' => $aCod3, ':n' => 'Aula 3 Fase 7']);
        $this->fixtureAula3Id = (int)$this->db->lastInsertId();

        // Secciones
        $stmtSec = $this->db->prepare('INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada) VALUES (:gp, :c, :n, 30, 1)');
        
        // Sección A (Docente 1)
        $stmtSec->execute([':gp' => $this->fixtureGradoPeriodoId, ':c' => 'A', ':n' => 'Sección A Matutina']);
        $this->fixtureSeccionAId = (int)$this->db->lastInsertId();

        // Sección B (Docente 1, multi-asignación)
        $stmtSec->execute([':gp' => $this->fixtureGradoPeriodoId, ':c' => 'B', ':n' => 'Sección B Matutina']);
        $this->fixtureSeccionBId = (int)$this->db->lastInsertId();

        // Sección C (Docente 2, aislamiento)
        $stmtSec->execute([':gp' => $this->fixtureGradoPeriodoId, ':c' => 'C', ':n' => 'Sección C Matutina']);
        $this->fixtureSeccionCId = (int)$this->db->lastInsertId();

        // 4. Asignaciones Docentes
        $stmtAsig = $this->db->prepare('INSERT INTO asignaciones_docentes (id_docente, id_seccion, id_aula, id_usuario_creador, fecha_inicio, estado) VALUES (:d, :s, :a, :u, "2026-01-15", "ACTIVA")');
        
        // Asignación 1: Docente 1 en Sección A
        $stmtAsig->execute([':d' => $this->fixtureDocente1Id, ':s' => $this->fixtureSeccionAId, ':a' => $this->fixtureAula1Id, ':u' => $this->fixtureUserAdminId]);
        $this->fixtureAsignacionAId = (int)$this->db->lastInsertId();

        // Asignación 2: Docente 1 en Sección B
        $stmtAsig->execute([':d' => $this->fixtureDocente1Id, ':s' => $this->fixtureSeccionBId, ':a' => $this->fixtureAula2Id, ':u' => $this->fixtureUserAdminId]);
        $this->fixtureAsignacionBId = (int)$this->db->lastInsertId();

        // Asignación 3: Docente 2 en Sección C
        $stmtAsig->execute([':d' => $this->fixtureDocente2Id, ':s' => $this->fixtureSeccionCId, ':a' => $this->fixtureAula3Id, ':u' => $this->fixtureUserAdminId]);
        $this->fixtureAsignacionCId = (int)$this->db->lastInsertId();

        // Asignación 4: Asignación Inactiva/Cerrada para Docente 1
        $stmtAsigInac = $this->db->prepare('INSERT INTO asignaciones_docentes (id_docente, id_seccion, id_aula, id_usuario_creador, fecha_inicio, fecha_fin, estado) VALUES (:d, :s, :a, :u, "2025-01-15", "2025-10-31", "INACTIVA")');
        $stmtAsigInac->execute([':d' => $this->fixtureDocente1Id, ':s' => $this->fixtureSeccionAId, ':a' => $this->fixtureAula1Id, ':u' => $this->fixtureUserAdminId]);
        $this->fixtureAsignacionInactivaId = (int)$this->db->lastInsertId();

        // 5. Estudiantes e Inscripciones
        $this->createEstudiantesAndInscripciones();
    }

    private function createEstudiantesAndInscripciones(): void
    {
        // Encargado de prueba
        $stmtEnc = $this->db->prepare('INSERT INTO encargados (nombres, apellidos, telefono, correo) VALUES ("Juan Padre", "Pérez López", "55551234", "encargado@test.com")');
        $stmtEnc->execute();
        $encargadoId = (int)$this->db->lastInsertId();

        $stmtEst = $this->db->prepare('INSERT INTO estudiantes (codigo_estudiante, nombres, apellidos, fecha_nacimiento, estado) VALUES (:c, :n, :a, :f, :est)');
        $stmtVinculo = $this->db->prepare('INSERT INTO estudiantes_encargados (id_estudiante, id_encargado, parentesco, es_principal) VALUES (:est, :enc, "PADRE", 1)');
        $stmtIns = $this->db->prepare('INSERT INTO inscripciones (id_estudiante, id_seccion, id_encargado, codigo_confirmacion, estado, fecha_confirmacion) VALUES (:est, :sec, :enc, :cod, :estado, :fconf)');

        // Estudiante 1 (Sección A, Confirmado, Activo)
        $cEst1 = 'EST-F7-001-' . rand(100, 999);
        $stmtEst->execute([':c' => $cEst1, ':n' => 'Alejandro', ':a' => 'Álvarez Gómez', ':f' => '2016-04-12', ':est' => 'ACTIVO']);
        $e1 = (int)$this->db->lastInsertId();
        $this->createdEstudianteIds[] = $e1;
        $stmtVinculo->execute([':est' => $e1, ':enc' => $encargadoId]);
        $stmtIns->execute([':est' => $e1, ':sec' => $this->fixtureSeccionAId, ':enc' => $encargadoId, ':cod' => 'CONF-F7-01-' . uniqid(), ':estado' => 'CONFIRMADA', ':fconf' => '2026-02-01 10:00:00']);
        $this->createdInscripcionIds[] = (int)$this->db->lastInsertId();

        // Estudiante 2 (Sección A, Confirmado, Activo con XSS en nombre)
        $cEst2 = 'EST-F7-002-' . rand(100, 999);
        $stmtEst->execute([':c' => $cEst2, ':n' => '<script>alert("XSS")</script> & Sofía', ':a' => 'Bermúdez <img src=x onerror=1>', ':f' => '2016-07-20', ':est' => 'ACTIVO']);
        $e2 = (int)$this->db->lastInsertId();
        $this->createdEstudianteIds[] = $e2;
        $stmtVinculo->execute([':est' => $e2, ':enc' => $encargadoId]);
        $stmtIns->execute([':est' => $e2, ':sec' => $this->fixtureSeccionAId, ':enc' => $encargadoId, ':cod' => 'CONF-F7-02-' . uniqid(), ':estado' => 'CONFIRMADA', ':fconf' => '2026-02-01 10:00:00']);
        $this->createdInscripcionIds[] = (int)$this->db->lastInsertId();

        // Estudiante 3 (Sección A, Inscripción ANULADA) -> Debe ser ignorado
        $cEst3 = 'EST-F7-003-' . rand(100, 999);
        $stmtEst->execute([':c' => $cEst3, ':n' => 'Estudiante', ':a' => 'Anulado', ':f' => '2016-01-01', ':est' => 'ACTIVO']);
        $e3 = (int)$this->db->lastInsertId();
        $this->createdEstudianteIds[] = $e3;
        $stmtVinculo->execute([':est' => $e3, ':enc' => $encargadoId]);
        $stmtIns->execute([':est' => $e3, ':sec' => $this->fixtureSeccionAId, ':enc' => $encargadoId, ':cod' => 'CONF-F7-03-' . uniqid(), ':estado' => 'ANULADA', ':fconf' => '2026-02-01 10:00:00']);
        $this->createdInscripcionIds[] = (int)$this->db->lastInsertId();

        // Estudiante 4 (Sección A, Inscripción RECHAZADA) -> Debe ser ignorado
        $cEst4 = 'EST-F7-004-' . rand(100, 999);
        $stmtEst->execute([':c' => $cEst4, ':n' => 'Estudiante', ':a' => 'Rechazado', ':f' => '2016-02-02', ':est' => 'ACTIVO']);
        $e4 = (int)$this->db->lastInsertId();
        $this->createdEstudianteIds[] = $e4;
        $stmtVinculo->execute([':est' => $e4, ':enc' => $encargadoId]);
        $stmtIns->execute([':est' => $e4, ':sec' => $this->fixtureSeccionAId, ':enc' => $encargadoId, ':cod' => null, ':estado' => 'RECHAZADA', ':fconf' => null]);
        $this->createdInscripcionIds[] = (int)$this->db->lastInsertId();

        // Estudiante 5 (Sección A, Inscripción CONFIRMADA pero Estudiante INACTIVO) -> Debe ser ignorado
        $cEst5 = 'EST-F7-005-' . rand(100, 999);
        $stmtEst->execute([':c' => $cEst5, ':n' => 'Estudiante', ':a' => 'Inactivo', ':f' => '2016-03-03', ':est' => 'INACTIVO']);
        $e5 = (int)$this->db->lastInsertId();
        $this->createdEstudianteIds[] = $e5;
        $stmtVinculo->execute([':est' => $e5, ':enc' => $encargadoId]);
        $stmtIns->execute([':est' => $e5, ':sec' => $this->fixtureSeccionAId, ':enc' => $encargadoId, ':cod' => 'CONF-F7-05-' . uniqid(), ':estado' => 'CONFIRMADA', ':fconf' => '2026-02-01 10:00:00']);
        $this->createdInscripcionIds[] = (int)$this->db->lastInsertId();

        // Estudiante 6 (Sección B, Confirmado, Activo)
        $cEst6 = 'EST-F7-006-' . rand(100, 999);
        $stmtEst->execute([':c' => $cEst6, ':n' => 'Daniela', ':a' => 'Fuentes Marroquín', ':f' => '2016-09-10', ':est' => 'ACTIVO']);
        $e6 = (int)$this->db->lastInsertId();
        $this->createdEstudianteIds[] = $e6;
        $stmtVinculo->execute([':est' => $e6, ':enc' => $encargadoId]);
        $stmtIns->execute([':est' => $e6, ':sec' => $this->fixtureSeccionBId, ':enc' => $encargadoId, ':cod' => 'CONF-F7-06-' . uniqid(), ':estado' => 'CONFIRMADA', ':fconf' => '2026-02-01 10:00:00']);
        $this->createdInscripcionIds[] = (int)$this->db->lastInsertId();

        // Estudiante 7 (Sección C - Docente 2, Confirmado, Activo)
        $cEst7 = 'EST-F7-007-' . rand(100, 999);
        $stmtEst->execute([':c' => $cEst7, ':n' => 'Guillermo', ':a' => 'Hernández Castillo', ':f' => '2016-11-25', ':est' => 'ACTIVO']);
        $e7 = (int)$this->db->lastInsertId();
        $this->createdEstudianteIds[] = $e7;
        $stmtVinculo->execute([':est' => $e7, ':enc' => $encargadoId]);
        $stmtIns->execute([':est' => $e7, ':sec' => $this->fixtureSeccionCId, ':enc' => $encargadoId, ':cod' => 'CONF-F7-07-' . uniqid(), ':estado' => 'CONFIRMADA', ':fconf' => '2026-02-01 10:00:00']);
        $this->createdInscripcionIds[] = (int)$this->db->lastInsertId();
    }

    private function cleanPreviousArtifacts(): void
    {
        // Sesión limpia
        Session::reset();
    }

    private function cleanup(): void
    {
        Session::reset();

        try {
            // Eliminar inscripciones de prueba
            if (!empty($this->createdInscripcionIds)) {
                $in = implode(',', array_map('intval', $this->createdInscripcionIds));
                $this->db->exec("DELETE FROM inscripciones WHERE id_inscripcion IN ($in)");
            }

            // Eliminar estudiantes de prueba
            if (!empty($this->createdEstudianteIds)) {
                $in = implode(',', array_map('intval', $this->createdEstudianteIds));
                $this->db->exec("DELETE FROM estudiantes_encargados WHERE id_estudiante IN ($in)");
                $this->db->exec("DELETE FROM estudiantes WHERE id_estudiante IN ($in)");
            }

            // Eliminar asignaciones
            $this->db->exec("DELETE FROM asignaciones_docentes WHERE id_seccion IN ({$this->fixtureSeccionAId}, {$this->fixtureSeccionBId}, {$this->fixtureSeccionCId})");

            // Eliminar secciones
            $this->db->exec("DELETE FROM secciones WHERE id_seccion IN ({$this->fixtureSeccionAId}, {$this->fixtureSeccionBId}, {$this->fixtureSeccionCId})");

            // Eliminar grados_periodos
            if ($this->fixtureGradoPeriodoId) {
                $this->db->exec("DELETE FROM grados_periodos WHERE id_grado_periodo = {$this->fixtureGradoPeriodoId}");
            }

            // Eliminar grados
            if ($this->fixtureGradoId) {
                $this->db->exec("DELETE FROM grados WHERE id_grado = {$this->fixtureGradoId}");
            }

            // Eliminar aulas
            $this->db->exec("DELETE FROM aulas WHERE id_aula IN ({$this->fixtureAula1Id}, {$this->fixtureAula2Id}, {$this->fixtureAula3Id})");

            // Eliminar docentes
            $this->db->exec("DELETE FROM docentes WHERE id_docente IN ({$this->fixtureDocente1Id}, {$this->fixtureDocente2Id}, {$this->fixtureDocenteLibreId}, {$this->fixtureDocenteInactivoId})");

            // Eliminar usuarios
            $this->db->exec("DELETE FROM usuarios WHERE id_usuario IN ({$this->fixtureUserDocente1Id}, {$this->fixtureUserDocente2Id}, {$this->fixtureUserDocenteLibreId}, {$this->fixtureUserDocenteInactivoId}, {$this->fixtureUserAdminId})");
        } catch (\Throwable $e) {
            // Silencioso en cleanup
        }
    }

    private function loginAsUser(int $idUsuario): void
    {
        Session::start();
        Session::set('auth_user_id', $idUsuario);
    }

    /**
     * T7-01: Acceso exitoso de usuario con rol DOCENTE a /docente (HTTP 200).
     */
    private function testT7_01_DocenteAccesoHttp200(): void
    {
        $this->loginAsUser($this->fixtureUserDocente1Id);

        $controller = new DocenteController($this->authService, $this->portalService);
        $request = Request::create('GET', '/docente');
        $response = $controller->index($request);

        $passed = $response->getStatusCode() === 200
            && str_contains($response->getBody(), 'Portal Docente')
            && str_contains($response->getBody(), 'Marta Elena');

        $this->record(
            'T7-01',
            'Acceso autenticado con rol DOCENTE a /docente',
            'HTTP 200 con vista del portal docente cargada',
            sprintf('HTTP %d, vista %s', $response->getStatusCode(), $passed ? 'OK' : 'ERR'),
            $passed
        );
    }

    /**
     * T7-02: Acceso de usuario anónimo redirigido a /login (HTTP 302).
     */
    private function testT7_02_AnonimoRedirigidoLogin(): void
    {
        Session::reset();

        $controller = new DocenteController($this->authService, $this->portalService);
        $request = Request::create('GET', '/docente');
        $response = $controller->index($request);

        $location = $response->getHeader('Location');
        $passed = $response->getStatusCode() === 302 && $location === '/login';

        $this->record(
            'T7-02',
            'Acceso de usuario no autenticado a /docente',
            'HTTP 302 hacia /login',
            sprintf('HTTP %d con Location: %s', $response->getStatusCode(), $location ?? 'null'),
            $passed
        );
    }

    /**
     * T7-03: Usuario con rol ADMINISTRADOR rechazado de /docente (HTTP 403).
     */
    private function testT7_03_AdminRechazado403(): void
    {
        $this->loginAsUser($this->fixtureUserAdminId);

        $controller = new DocenteController($this->authService, $this->portalService);
        $request = Request::create('GET', '/docente');
        $response = $controller->index($request);

        $passed = $response->getStatusCode() === 403
            && str_contains($response->getBody(), '403')
            && str_contains($response->getBody(), 'No posee los privilegios suficientes');

        $this->record(
            'T7-03',
            'Acceso de rol ADMINISTRADOR a /docente denegado',
            'HTTP 403 Forbidden',
            sprintf('HTTP %d (%s)', $response->getStatusCode(), $passed ? 'Acceso Denegado' : 'No bloqueado'),
            $passed
        );
    }

    /**
     * T7-04: Veracidad de información del perfil del docente en pantalla.
     */
    private function testT7_04_PerfilDocenteCorrecto(): void
    {
        $this->loginAsUser($this->fixtureUserDocente1Id);

        $controller = new DocenteController($this->authService, $this->portalService);
        $request = Request::create('GET', '/docente');
        $response = $controller->index($request);
        $body = $response->getBody();

        $docente = $this->docenteRepo->findById($this->fixtureDocente1Id);

        $hasNombre = str_contains($body, 'Marta Elena Morales Solís');
        $hasCodigo = str_contains($body, $docente->codigo_docente);
        $hasEstado = str_contains($body, 'ACTIVO');

        $passed = $hasNombre && $hasCodigo && $hasEstado;

        $this->record(
            'T7-04',
            'Perfil docente completo y veraz en el encabezado',
            'Nombre completo, código docente y estado activo presentes',
            sprintf('Nombre: %s, Código: %s, Estado: %s', $hasNombre ? 'OK' : 'NO', $hasCodigo ? 'OK' : 'NO', $hasEstado ? 'OK' : 'NO'),
            $passed
        );
    }

    /**
     * T7-05: Visibilidad del contexto académico activo (periodo, grado, sección, aula).
     */
    private function testT7_05_ContextoAcademicoVisible(): void
    {
        $this->loginAsUser($this->fixtureUserDocente1Id);

        $controller = new DocenteController($this->authService, $this->portalService);
        $request = Request::create('GET', '/docente');
        $response = $controller->index($request);
        $body = $response->getBody();

        $hasSeccionA = str_contains($body, 'Sección A') || str_contains($body, '— A');
        $hasCapacidad = str_contains($body, '30') && str_contains($body, 'cupos');
        $hasAula = str_contains($body, 'Aula 1 Fase 7') || str_contains($body, 'A-F7-1-');

        $passed = $hasSeccionA && $hasCapacidad && $hasAula;

        $this->record(
            'T7-05',
            'Contexto académico de asignación activa visible',
            'Sección, capacidad de aula y grado correctamente desplegados',
            sprintf('Sección A: %s, Capacidad: %s, Aula: %s', $hasSeccionA ? 'OK' : 'NO', $hasCapacidad ? 'OK' : 'NO', $hasAula ? 'OK' : 'NO'),
            $passed
        );
    }

    /**
     * T7-06: Docente sin asignaciones activas recibe mensaje amigable (HTTP 200).
     */
    private function testT7_06_DocenteSinAsignacionEstadoAmigable(): void
    {
        $this->loginAsUser($this->fixtureUserDocenteLibreId);

        $controller = new DocenteController($this->authService, $this->portalService);
        $request = Request::create('GET', '/docente');
        $response = $controller->index($request);
        $body = $response->getBody();

        $passed = $response->getStatusCode() === 200
            && str_contains($body, 'Actualmente no tiene una sección asignada')
            && !str_contains($body, 'Fatal error')
            && !str_contains($body, 'Exception');

        $this->record(
            'T7-06',
            'Docente sin asignación activa recibe mensaje amigable',
            'HTTP 200 con tarjeta de estado vacío amigable',
            sprintf('HTTP %d con mensaje amigable %s', $response->getStatusCode(), $passed ? 'detectado' : 'no detectado'),
            $passed
        );
    }

    /**
     * T7-07: Nómina oficial de estudiantes inscritos y confirmados (RF-17).
     */
    private function testT7_07_NominaEstudiantesConfirmadosRF17(): void
    {
        $this->loginAsUser($this->fixtureUserDocente1Id);

        $controller = new DocenteController($this->authService, $this->portalService);
        $request = Request::create('GET', '/docente');
        $response = $controller->index($request);
        $body = $response->getBody();

        // Estudiante 1 debe aparecer
        $hasEst1 = str_contains($body, 'Álvarez Gómez, Alejandro') && str_contains($body, '2016-04-12');
        // Estudiante 2 debe aparecer
        $hasEst2 = str_contains($body, 'Sofía') && str_contains($body, '2016-07-20');

        $passed = $hasEst1 && $hasEst2;

        $this->record(
            'T7-07',
            'Nómina de estudiantes asignados desplegada (RF-17)',
            'Estudiantes confirmados visibles con nombres, apellidos y fecha nacimiento',
            sprintf('Estudiante 1: %s, Estudiante 2: %s', $hasEst1 ? 'OK' : 'NO', $hasEst2 ? 'OK' : 'NO'),
            $passed
        );
    }

    /**
     * T7-08: Exclusión estricta de estudiantes de otras secciones ajenas.
     */
    private function testT7_08_ExclusionEstudiantesOtrasSecciones(): void
    {
        $this->loginAsUser($this->fixtureUserDocente1Id);

        $controller = new DocenteController($this->authService, $this->portalService);
        // Consulta Sección A
        $request = Request::create('GET', '/docente?seccion=' . $this->fixtureSeccionAId);
        $response = $controller->index($request);
        $body = $response->getBody();

        // Estudiante 7 (perteneciente a Sección C / Docente 2) NO debe aparecer
        $hasEst7 = str_contains($body, 'Hernández Castillo, Guillermo');
        // Estudiante 6 (Sección B) tampoco debe aparecer en Sección A
        $hasEst6 = str_contains($body, 'Fuentes Marroquín, Daniela');

        $passed = !$hasEst7 && !$hasEst6;

        $this->record(
            'T7-08',
            'Exclusión de estudiantes pertenecientes a otras secciones',
            'Estudiantes ajenos ausentes de la nómina de la sección consultada',
            sprintf('Estudiante ajeno ausente: %s', $passed ? 'CONFIRMADO (0 fugas)' : 'FALLO (datos expuestos)'),
            $passed
        );
    }

    /**
     * T7-09: Exclusión de inscripciones no confirmadas o estudiantes inactivos.
     */
    private function testT7_09_ExclusionInscripcionesNoConfirmadasOInactivas(): void
    {
        $this->loginAsUser($this->fixtureUserDocente1Id);

        $controller = new DocenteController($this->authService, $this->portalService);
        $request = Request::create('GET', '/docente?seccion=' . $this->fixtureSeccionAId);
        $response = $controller->index($request);
        $body = $response->getBody();

        $hasAnulado = str_contains($body, 'Estudiante, Anulado');
        $hasRechazado = str_contains($body, 'Estudiante, Rechazado');
        $hasInactivo = str_contains($body, 'Estudiante, Inactivo');

        $passed = !$hasAnulado && !$hasRechazado && !$hasInactivo;

        $this->record(
            'T7-09',
            'Exclusión de estados ANULADA, RECHAZADA o estudiante INACTIVO',
            'Ningún estudiante en estado no confirmado o inactivo aparece en la lista',
            sprintf('Anulado: %s, Rechazado: %s, Inactivo: %s', !$hasAnulado ? 'Ausente' : 'EXPUESTO', !$hasRechazado ? 'Ausente' : 'EXPUESTO', !$hasInactivo ? 'Ausente' : 'EXPUESTO'),
            $passed
        );
    }

    /**
     * T7-10: Aislamiento horizontal por sección: parámetro manipulado seccion=X ajena -> HTTP 403 y 0 registros.
     */
    private function testT7_10_AislamientoHorizontalPorSeccion(): void
    {
        $this->loginAsUser($this->fixtureUserDocente1Id);

        $controller = new DocenteController($this->authService, $this->portalService);
        // Docente 1 intenta solicitar la Sección C (asignada a Docente 2)
        $request = Request::create('GET', '/docente?seccion=' . $this->fixtureSeccionCId);
        $response = $controller->index($request);

        $isHttp403 = $response->getStatusCode() === 403;

        // Comprobación física a nivel de Repositorio (SQL Barrier)
        $estudiantesDirectos = $this->portalRepo->findEstudiantesBySeccionDocente($this->fixtureDocente1Id, $this->fixtureSeccionCId);
        $countDirecto = count($estudiantesDirectos);

        $passed = $isHttp403 && $countDirecto === 0;

        $this->record(
            'T7-10',
            'Aislamiento horizontal: sección ajena rechazada con HTTP 403',
            'Controlador devuelve HTTP 403 y consulta SQL directa retorna 0 filas',
            sprintf('HTTP %d en controlador, %d filas retornadas en repositorio', $response->getStatusCode(), $countDirecto),
            $passed
        );
    }

    /**
     * T7-11: Manipulación de id_docente en la petición es ignorada (identidad fija de sesión).
     */
    private function testT7_11_ManipulacionIdDocenteIgnorada(): void
    {
        $this->loginAsUser($this->fixtureUserDocente1Id);

        $controller = new DocenteController($this->authService, $this->portalService);
        // Se envía id_docente del docente 2 en la query string
        $request = Request::create('GET', '/docente?id_docente=' . $this->fixtureDocente2Id);
        $response = $controller->index($request);
        $body = $response->getBody();

        // Debe seguir mostrando a Marta Elena (Docente 1) y NO a Carlos Andrés (Docente 2)
        $showsDocente1 = str_contains($body, 'Marta Elena');
        $showsDocente2 = str_contains($body, 'Carlos Andrés');

        $passed = $showsDocente1 && !$showsDocente2;

        $this->record(
            'T7-11',
            'Parameter Tampering de id_docente es completamente ignorado',
            'Identidad extraída exclusivamente de la sesión auth_user_id',
            sprintf('Docente 1 mostrado: %s, Docente 2 inyectado mostrado: %s', $showsDocente1 ? 'SÍ' : 'NO', $showsDocente2 ? 'SÍ (VULNERABLE)' : 'NO (SEGURO)'),
            $passed
        );
    }

    /**
     * T7-12: Manipulación de id_asignacion es inocua (doble barrera por id_docente).
     */
    private function testT7_12_ManipulacionIdAsignacionRechazada(): void
    {
        // Buscar asignación ajena con docente 1
        $asigAjena = $this->portalRepo->findAsignacionByIdAndDocente($this->fixtureAsignacionCId, $this->fixtureDocente1Id);

        $passed = $asigAjena === null;

        $this->record(
            'T7-12',
            'Consulta de asignación ajena restringida a id_docente en repositorio',
            'findAsignacionByIdAndDocente retorna null para asignación de otro docente',
            sprintf('Retorno de asignación ajena: %s', $asigAjena === null ? 'null (aislado)' : 'Objeto expuesto'),
            $passed
        );
    }

    /**
     * T7-13: Soporte para múltiples asignaciones activas (cambio fluido entre secciones).
     */
    private function testT7_13_SoporteMultiplesAsignaciones(): void
    {
        $this->loginAsUser($this->fixtureUserDocente1Id);

        $controller = new DocenteController($this->authService, $this->portalService);

        // 1. Consulta sin parámetro (por defecto Sección A)
        $reqA = Request::create('GET', '/docente');
        $respA = $controller->index($reqA);
        $bodyA = $respA->getBody();

        // 2. Consulta con parámetro seccion = B
        $reqB = Request::create('GET', '/docente?seccion=' . $this->fixtureSeccionBId);
        $respB = $controller->index($reqB);
        $bodyB = $respB->getBody();

        $showsSeccionB = str_contains($bodyB, 'Sección B') || str_contains($bodyB, '— B');
        $showsEst6InB = str_contains($bodyB, 'Fuentes Marroquín, Daniela');
        $showsSwitcher = str_contains($bodyA, 'Mis Asignaciones Activas:');

        $passed = $showsSeccionB && $showsEst6InB && $showsSwitcher;

        $this->record(
            'T7-13',
            'Soporte multi-asignación y selector de secciones',
            'Docente navega entre Sección A y Sección B visualizando sus nóminas respectivas',
            sprintf('Selector presente: %s, Estudiante Sección B visible en B: %s', $showsSwitcher ? 'SÍ' : 'NO', $showsEst6InB ? 'SÍ' : 'NO'),
            $passed
        );
    }

    /**
     * T7-14: Consistencia exacta entre conteo de estudiantes reportado y nómina desplegada.
     */
    private function testT7_14_ConsistenciaContadorEstudiantes(): void
    {
        $this->loginAsUser($this->fixtureUserDocente1Id);

        $resumen = $this->portalService->obtenerResumenPortal($this->fixtureDocente1Id, $this->fixtureSeccionAId);
        $countRepo = $this->portalRepo->countEstudiantesBySeccionDocente($this->fixtureDocente1Id, $this->fixtureSeccionAId);

        $listaCount = count($resumen['estudiantes']);
        $totalReportado = $resumen['total_estudiantes'];

        $passed = $listaCount === 2 && $totalReportado === 2 && $countRepo === 2;

        $this->record(
            'T7-14',
            'Consistencia matemática en conteo de estudiantes',
            'total_estudiantes coincide con count(estudiantes) y countEstudiantesBySeccionDocente',
            sprintf('Nómina: %d, Total servicio: %d, Count SQL: %d', $listaCount, $totalReportado, $countRepo),
            $passed
        );
    }

    /**
     * T7-15: Prevención estricta de XSS en datos y nombres de estudiantes renderizados.
     */
    private function testT7_15_PrevencionXSSNombresEstudiantes(): void
    {
        $this->loginAsUser($this->fixtureUserDocente1Id);

        $controller = new DocenteController($this->authService, $this->portalService);
        $request = Request::create('GET', '/docente?seccion=' . $this->fixtureSeccionAId);
        $response = $controller->index($request);
        $body = $response->getBody();

        // Estudiante 2 tiene: <script>alert("XSS")</script> & Sofía
        $hasRawScript = str_contains($body, '<script>alert("XSS")</script>');
        $hasRawImg = str_contains($body, '<img src=x onerror=1>');
        $hasEscapedScript = str_contains($body, '&lt;script&gt;') || str_contains($body, '&quot;XSS&quot;');

        $passed = !$hasRawScript && !$hasRawImg && $hasEscapedScript;

        $this->record(
            'T7-15',
            'Prevención contra Cross-Site Scripting (XSS) en nómina',
            'Etiquetas HTML y caracteres peligrosos saneados con htmlspecialchars/e()',
            sprintf('Script crudo: %s, Imagen inyectada: %s, Caracteres escapados: %s', $hasRawScript ? 'EXPUESTO' : 'NO', $hasRawImg ? 'EXPUESTO' : 'NO', $hasEscapedScript ? 'SÍ' : 'NO'),
            $passed
        );
    }

    /**
     * T7-16: Verificación de privacidad de datos de estudiantes (RNF-04).
     */
    private function testT7_16_MinimizacionPrivacidadEstudiantesRNF04(): void
    {
        $this->loginAsUser($this->fixtureUserDocente1Id);

        $controller = new DocenteController($this->authService, $this->portalService);
        $request = Request::create('GET', '/docente?seccion=' . $this->fixtureSeccionAId);
        $response = $controller->index($request);
        $body = $response->getBody();

        // Verificar que NO se expongan datos del encargado ni contraseñas
        $hasTelEncargado = str_contains($body, '55551234');
        $hasEmailEncargado = str_contains($body, 'encargado@test.com');
        $hasPasswordHash = str_contains($body, '$2y$') || str_contains($body, 'clave_hash');

        $passed = !$hasTelEncargado && !$hasEmailEncargado && !$hasPasswordHash;

        $this->record(
            'T7-16',
            'Minimización de datos y privacidad del estudiante (RNF-04)',
            'Sin exposición de datos del encargado, contraseñas ni identificadores sensibles',
            sprintf('Teléfono encargado: %s, Correo encargado: %s, Hashes: %s', $hasTelEncargado ? 'EXPUESTO' : 'OMITIDO', $hasEmailEncargado ? 'EXPUESTO' : 'OMITIDO', $hasPasswordHash ? 'EXPUESTO' : 'OMITIDO'),
            $passed
        );
    }

    /**
     * T7-17: Revocación de acceso para docente desactivado (estado INACTIVO).
     */
    private function testT7_17_RevocacionAccesoDocenteInactivo(): void
    {
        $this->loginAsUser($this->fixtureUserDocenteInactivoId);

        $controller = new DocenteController($this->authService, $this->portalService);
        $request = Request::create('GET', '/docente');
        $response = $controller->index($request);

        $passed = $response->getStatusCode() === 403
            && str_contains($response->getBody(), 'Su cuenta docente no se encuentra activa');

        $this->record(
            'T7-17',
            'Revocación de acceso a docente en estado INACTIVO',
            'HTTP 403 Forbidden cuando docentes.estado = INACTIVO',
            sprintf('HTTP %d (%s)', $response->getStatusCode(), $passed ? 'Bloqueo efectivo' : 'No bloqueado'),
            $passed
        );
    }

    /**
     * T7-18: Aislamiento de asignación inactiva / histórica.
     */
    private function testT7_18_AislamientoAsignacionInactiva(): void
    {
        // La asignación 4 de Docente 1 está en estado INACTIVA
        $activas = $this->portalRepo->findActiveAsignacionesByDocente($this->fixtureDocente1Id);

        $contieneInactiva = false;
        foreach ($activas as $asig) {
            if ($asig->id_asignacion_docente === $this->fixtureAsignacionInactivaId) {
                $contieneInactiva = true;
                break;
            }
        }

        $passed = !$contieneInactiva;

        $this->record(
            'T7-18',
            'Asignaciones históricas o inactivas no accesibles en portal activo',
            'findActiveAsignacionesByDocente excluye asignaciones con estado != ACTIVA',
            sprintf('Asignación inactiva incluida en lista activa: %s', $contieneInactiva ? 'SÍ (FALLO)' : 'NO (CORRECTO)'),
            $passed
        );
    }

    /**
     * T7-19: Integración en tiempo real Fase 6 -> Fase 7 (RF-31).
     */
    private function testT7_19_IntegracionFase6Fase7RF31(): void
    {
        // Encargado para la prueba
        $encId = (int)$this->db->query("SELECT id_encargado FROM encargados ORDER BY id_encargado DESC LIMIT 1")->fetchColumn();

        // Crear un nuevo estudiante e inscribirlo en la Sección A
        $stmtEst = $this->db->prepare('INSERT INTO estudiantes (codigo_estudiante, nombres, apellidos, fecha_nacimiento, estado) VALUES (:c, :n, :a, "2016-05-15", "ACTIVO")');
        $cEstAuto = 'EST-AUTO-' . rand(1000, 9999);
        $stmtEst->execute([':c' => $cEstAuto, ':n' => 'Valeria', ':a' => 'Zamora Fuentes']);
        $eAuto = (int)$this->db->lastInsertId();
        $this->createdEstudianteIds[] = $eAuto;

        $this->db->exec("INSERT INTO estudiantes_encargados (id_estudiante, id_encargado, parentesco, es_principal) VALUES ($eAuto, $encId, 'MADRE', 1)");

        $stmtIns = $this->db->prepare('INSERT INTO inscripciones (id_estudiante, id_seccion, id_encargado, codigo_confirmacion, estado, fecha_confirmacion) VALUES (:est, :sec, :enc, :cod, "CONFIRMADA", NOW())');
        $stmtIns->execute([':est' => $eAuto, ':sec' => $this->fixtureSeccionAId, ':enc' => $encId, ':cod' => 'CONF-AUTO-' . uniqid()]);
        $insAuto = (int)$this->db->lastInsertId();
        $this->createdInscripcionIds[] = $insAuto;

        // Consultar el portal docente inmediatamente
        $this->loginAsUser($this->fixtureUserDocente1Id);
        $controller = new DocenteController($this->authService, $this->portalService);
        $request = Request::create('GET', '/docente?seccion=' . $this->fixtureSeccionAId);
        $response = $controller->index($request);
        $body = $response->getBody();

        $showsAutoStudent = str_contains($body, 'Zamora Fuentes, Valeria') && str_contains($body, $cEstAuto);

        $passed = $showsAutoStudent;

        $this->record(
            'T7-19',
            'Integración Fase 6 -> Fase 7: nueva inscripción confirmada (RF-31)',
            'Inscripción confirmada en línea aparece de inmediato en nómina sin copia manual',
            sprintf('Estudiante recién inscrito detectado en nómina docente: %s', $showsAutoStudent ? 'SÍ (RF-31 OK)' : 'NO'),
            $passed
        );
    }

    /**
     * T7-20: Reflejo inmediato de cambios de estado en inscripciones.
     */
    private function testT7_20_ReflejoCambioEstadoInscripcion(): void
    {
        // Anular la inscripción creada en T7-19
        $lastInsId = end($this->createdInscripcionIds);
        $this->db->exec("UPDATE inscripciones SET estado = 'ANULADA' WHERE id_inscripcion = $lastInsId");

        // Consultar el portal docente nuevamente
        $this->loginAsUser($this->fixtureUserDocente1Id);
        $controller = new DocenteController($this->authService, $this->portalService);
        $request = Request::create('GET', '/docente?seccion=' . $this->fixtureSeccionAId);
        $response = $controller->index($request);
        $body = $response->getBody();

        $stillPresent = str_contains($body, 'Zamora Fuentes, Valeria');

        $passed = !$stillPresent;

        $this->record(
            'T7-20',
            'Reflejo de cambio de estado de inscripción en nómina docente',
            'Inscripción que cambia a ANULADA desaparece inmediatamente del portal',
            sprintf('Inscripción anulada ausente en portal: %s', !$stillPresent ? 'CORRECTO (removida)' : 'FALLO (sigue visible)'),
            $passed
        );
    }

    /**
     * T7-21: Flujo HTTP real mediante servidor PHP embebido y cURL.
     */
    private function testT7_21_FlujoHttpReal(): void
    {
        echo "\n>>> Iniciando servidor web PHP embebido para prueba de flujo real T7-21...\n";

        $port = 8092;
        $phpBin = PHP_BINARY;
        $docRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $cmd = sprintf('"%s" -S 127.0.0.1:%d -t "%s"', $phpBin, $port, $docRoot);
        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));

        if (!is_resource($process)) {
            $this->record('T7-21', 'Flujo HTTP real docente mediante servidor embebido', 'HTTP 200 y portal renderizado vía HTTP', 'No se pudo iniciar servidor PHP', false);
            return;
        }

        $procStatus = proc_get_status($process);
        $pid = $procStatus['pid'] ?? 0;

        // Esperar disponibilidad del servidor
        $connected = false;
        for ($i = 0; $i < 30; $i++) {
            $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
            if ($fp) {
                fclose($fp);
                $connected = true;
                break;
            }
            usleep(100000); // 100ms
        }

        if (!$connected) {
            $this->terminateProcess($pid, $process, $pipes);
            $this->record('T7-21', 'Flujo HTTP real docente mediante servidor embebido', 'HTTP 200 y portal renderizado vía HTTP', 'El servidor web no respondió a tiempo', false);
            return;
        }

        $cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_doc_' . uniqid() . '.txt';

        $httpRequest = function (string $url, string $method = 'GET', ?array $postData = null, ?string $cookie = null): array {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            if ($cookie !== null) {
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
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

            return ['code' => $code, 'headers' => $headers, 'body' => $body, 'location' => $location];
        };

        try {
            // 1. GET /login para obtener CSRF token y cookie
            $resLoginGet = $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'GET', null, $cookieFile);
            $csrfToken = '';
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resLoginGet['body'], $m)) {
                $csrfToken = $m[1];
            }

            // 2. POST /login con credenciales del docente 1
            $u1Data = $this->userRepo->findById($this->fixtureUserDocente1Id);
            $resLoginPost = $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'POST', [
                '_token' => $csrfToken,
                'nombre_usuario' => $u1Data->nombre_usuario,
                'password' => 'DocenteFase7Pass!',
            ], $cookieFile);

            $loginRedirectsToDocente = ($resLoginPost['code'] === 302 && str_contains($resLoginPost['location'], '/docente'));

            // 3. GET /docente autenticado
            $resDocente = $httpRequest(sprintf('http://127.0.0.1:%d/docente', $port), 'GET', null, $cookieFile);

            $is200 = $resDocente['code'] === 200;
            $hasName = str_contains($resDocente['body'], 'Marta Elena');
            $hasStudents = str_contains($resDocente['body'], 'Nómina Oficial de Estudiantes');

            $passed = $loginRedirectsToDocente && $is200 && $hasName && $hasStudents;

            $this->record(
                'T7-21',
                'Flujo HTTP real completo: Login docente -> Redirección -> Vista /docente',
                'GET /login 200 -> POST /login 302 /docente -> GET /docente 200 con nómina de estudiantes',
                sprintf('Login HTTP %d a %s, /docente HTTP %d, bienvenida %s', $resLoginPost['code'], $resLoginPost['location'], $resDocente['code'], $hasName ? 'OK' : 'ERR'),
                $passed
            );
        } finally {
            $this->terminateProcess($pid, $process, $pipes);
            if (file_exists($cookieFile)) {
                @unlink($cookieFile);
            }
        }
    }

    private function terminateProcess(int $pid, $process, array $pipes): void
    {
        foreach ($pipes as $p) {
            if (is_resource($p)) {
                fclose($p);
            }
        }
        if ($pid > 0) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("taskkill /F /T /PID $pid 2>nul");
            } else {
                posix_kill($pid, 9);
            }
        }
        if (is_resource($process)) {
            proc_terminate($process);
            proc_close($process);
        }
    }

    /**
     * T7-22: Regresión completa de Fase 6 (Inscripciones y Cupos).
     */
    private function testT7_22_RegresionFase6(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 6 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase6.php');
        $output = (string)@shell_exec($cmd);

        $isOk = str_contains($output, 'TODAS LAS PRUEBAS DE FASE 6 SUPERADAS SATISFACTORIAMENTE.')
            && str_contains($output, 'Total ejecutadas : 30')
            && str_contains($output, 'Fallidas (FALLO) : 0');

        $this->record('T7-22', 'Regresión completa de Fase 6 (Inscripciones y Cupos)', '30/30 pruebas de Fase 6 aprobadas', sprintf('%s pruebas de Fase 6 aprobadas', $isOk ? '30/30' : 'Fallo en regresión F6'), $isOk);
    }

    /**
     * T7-23: Regresión completa de Fase 5 (Asignaciones Docentes).
     */
    private function testT7_23_RegresionFase5(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 5 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase5.php');
        $output = (string)@shell_exec($cmd);

        $isOk = str_contains($output, 'TODAS LAS PRUEBAS DE FASE 5 SUPERADAS SATISFACTORIAMENTE.')
            && str_contains($output, 'Total ejecutadas : 26')
            && str_contains($output, 'Fallidas (FALLO) : 0');

        $this->record('T7-23', 'Regresión completa de Fase 5 (Asignaciones Docentes)', '26/26 pruebas de Fase 5 aprobadas', sprintf('%s pruebas de Fase 5 aprobadas', $isOk ? '26/26' : 'Fallo en regresión F5'), $isOk);
    }

    /**
     * T7-24: Regresión completa de Fase 4 (Administración Básica).
     */
    private function testT7_24_RegresionFase4(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 4 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase4.php');
        $output = (string)@shell_exec($cmd);

        $isOk = str_contains($output, 'RESULTADO: TODAS LAS PRUEBAS DE FASE 4 SUPERADAS SATISFACTORIAMENTE.')
            && str_contains($output, 'Total ejecutadas : 27')
            && str_contains($output, 'Fallidas (FALLO) : 0');

        $this->record('T7-24', 'Regresión completa de Fase 4 (Administración Básica)', '27/27 pruebas de Fase 4 aprobadas', sprintf('%s pruebas de Fase 4 aprobadas', $isOk ? '27/27' : 'Fallo en regresión F4'), $isOk);
    }

    /**
     * T7-25: Regresión completa de Fase 3 (Autenticación y Roles).
     */
    private function testT7_25_RegresionFase3(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 3 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase3.php');
        $output = (string)@shell_exec($cmd);

        $isOk = str_contains($output, 'RESULTADO: TODAS LAS PRUEBAS DE FASE 3 SUPERADAS SATISFACTORIAMENTE.')
            && str_contains($output, 'Total ejecutadas : 19')
            && str_contains($output, 'Fallidas (FALLO) : 0');

        $this->record('T7-25', 'Regresión completa de Fase 3 (Autenticación y Roles)', '19/19 pruebas de Fase 3 aprobadas', sprintf('%s pruebas de Fase 3 aprobadas', $isOk ? '19/19' : 'Fallo en regresión F3'), $isOk);
    }

    /**
     * T7-26: Regresión completa de Fase 2 (Arquitectura Base y Enrutamiento).
     */
    private function testT7_26_RegresionFase2(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 2 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests.php');
        $output = (string)@shell_exec($cmd);

        $isOk = str_contains($output, 'TODAS LAS PRUEBAS DE FASE 2 SUPERADAS SATISFACTORIAMENTE.')
            && str_contains($output, 'Total ejecutadas : 16')
            && str_contains($output, 'Fallidas (FALLO) : 0');

        $this->record('T7-26', 'Regresión completa de Fase 2 (Arquitectura Base y Enrutamiento)', '16/16 pruebas de Fase 2 aprobadas', sprintf('%s pruebas de Fase 2 aprobadas', $isOk ? '16/16' : 'Fallo en regresión F2'), $isOk);
    }

    /**
     * T7-27: Regresión completa de Fase 1 (Diseño de Base de Datos Relacional).
     */
    private function testT7_27_RegresionFase1(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 1...\n";
        $root = dirname(__DIR__);
        $mysqlBinary = $this->findMysqlBinary();

        if ($mysqlBinary === null) {
            $this->record(
                'T7-27',
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
        $tempDb = 'escuela_fase7_regresion_temp';

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
            $output = (string) shell_exec($baseCmd . ' ' . $tempDb . ' < "' . $valFile . '" 2>&1');

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
            'T7-27',
            'Regresión completa de Fase 1 (Diseño de Base de Datos)',
            '18/18 pruebas de integridad relacional superadas',
            $obtained,
            $isOk
        );
    }

    /**
     * T7-28: Seguridad HTTP real del Portal Docente.
     * Evalúa con servidor PHP efímero y cURL real los 6 escenarios de autorización y privacidad:
     * - Escenario A: Docente propio -> HTTP 200 con propia asignación y estudiantes.
     * - Escenario B: Manipulación de sección ajena -> HTTP 403, 0 datos de Docente B, 0 estudiantes expuestos.
     * - Escenario C: Parámetro id_docente manipulado -> ignorado, sesión resuelve Docente A.
     * - Escenario D: Administrador -> HTTP 403.
     * - Escenario E: Usuario anónimo -> HTTP 302 -> /login.
     * - Escenario F: Asignación inactiva -> rechazo HTTP 403, cero nómina asociada.
     */
    private function testT7_28_SeguridadHttpReal(): void
    {
        echo "\n>>> Iniciando servidor web PHP embebido para prueba de seguridad HTTP real T7-28...\n";

        $port = 8094;
        $phpBin = PHP_BINARY;
        $docRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $cmd = sprintf('"%s" -S 127.0.0.1:%d -t "%s"', $phpBin, $port, $docRoot);
        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));

        if (!is_resource($process)) {
            $this->record('T7-28', 'Seguridad HTTP real del Portal Docente', 'Prueba de seguridad HTTP real superada', 'No se pudo iniciar servidor PHP embebido', false);
            return;
        }

        $procStatus = proc_get_status($process);
        $pid = $procStatus['pid'] ?? 0;

        // Esperar disponibilidad del servidor
        $connected = false;
        for ($i = 0; $i < 30; $i++) {
            $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
            if ($fp) {
                fclose($fp);
                $connected = true;
                break;
            }
            usleep(100000); // 100ms
        }

        if (!$connected) {
            $this->terminateProcess($pid, $process, $pipes);
            $this->record('T7-28', 'Seguridad HTTP real del Portal Docente', 'Prueba de seguridad HTTP real superada', 'El servidor web no respondió a tiempo', false);
            return;
        }

        $cookieFileDocA = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_docA_' . uniqid() . '.txt';
        $cookieFileAdmin = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_adm_' . uniqid() . '.txt';

        $httpRequest = function (string $url, string $method = 'GET', ?array $postData = null, ?string $cookie = null): array {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            if ($cookie !== null) {
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
            }
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if ($postData !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                }
            }
            $raw = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headers = substr($raw, 0, $headerSize);
            $body = substr($raw, $headerSize);
            curl_close($ch);

            $location = '';
            if (preg_match('/Location:\s*([^\r\n]+)/i', $headers, $m)) {
                $location = trim($m[1]);
            }

            return ['code' => $code, 'headers' => $headers, 'body' => $body, 'location' => $location];
        };

        $loginUser = function (string $username, string $password, string $cookieFile) use ($httpRequest, $port): bool {
            $resGet = $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'GET', null, $cookieFile);
            $token = '';
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resGet['body'], $m)) {
                $token = $m[1];
            }
            if ($token === '') {
                return false;
            }
            $resPost = $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'POST', [
                '_token' => $token,
                'nombre_usuario' => $username,
                'password' => $password,
            ], $cookieFile);

            return $resPost['code'] === 302;
        };

        $tempSecInacId = null;
        $tempAsigInacId = null;
        $tempEstId = null;
        $tempInsId = null;

        try {
            // =========================================================================
            // ESCENARIO A — DOCENTE PROPIO
            // =========================================================================
            $u1Data = $this->userRepo->findById($this->fixtureUserDocente1Id);
            $okLoginDocA = $loginUser($u1Data->nombre_usuario, 'DocenteFase7Pass!', $cookieFileDocA);

            $resA = $httpRequest(sprintf('http://127.0.0.1:%d/docente?seccion=%d', $port, $this->fixtureSeccionAId), 'GET', null, $cookieFileDocA);
            $scenA_code = ($resA['code'] === 200);
            $scenA_showsOwnTeacher = str_contains($resA['body'], 'Marta Elena');
            $scenA_showsOwnSection = str_contains($resA['body'], 'Sección A');
            $scenA_showsOwnStudent = str_contains($resA['body'], 'Alejandro') && str_contains($resA['body'], 'Álvarez Gómez');
            $scenA = $okLoginDocA && $scenA_code && $scenA_showsOwnTeacher && $scenA_showsOwnSection && $scenA_showsOwnStudent;

            // =========================================================================
            // ESCENARIO B — MANIPULACIÓN DE SECCIÓN AJENA
            // =========================================================================
            // Docente A intenta acceder a Sección C (asignada a Docente 2 / Carlos Andrés)
            $resB = $httpRequest(sprintf('http://127.0.0.1:%d/docente?seccion=%d', $port, $this->fixtureSeccionCId), 'GET', null, $cookieFileDocA);
            $scenB_code = ($resB['code'] === 403);
            $scenB_noStudentB = !str_contains($resB['body'], 'Guillermo') && !str_contains($resB['body'], 'Hernández Castillo');
            $scenB_noDocB = !str_contains($resB['body'], 'Carlos Andrés') && !str_contains($resB['body'], 'Pérez Ramos');
            $scenB_noSensitive = !str_contains($resB['body'], '55551234') && !str_contains($resB['body'], 'encargado@test.com');
            $scenB = $scenB_code && $scenB_noStudentB && $scenB_noDocB && $scenB_noSensitive;

            // =========================================================================
            // ESCENARIO C — id_docente MANIPULADO
            // =========================================================================
            // Docente A envía id_docente ajeno en query string
            $resC1 = $httpRequest(sprintf('http://127.0.0.1:%d/docente?seccion=%d&id_docente=%d', $port, $this->fixtureSeccionAId, $this->fixtureDocente2Id), 'GET', null, $cookieFileDocA);
            $resC2 = $httpRequest(sprintf('http://127.0.0.1:%d/docente?id_docente=99999', $port), 'GET', null, $cookieFileDocA);
            $scenC_code1 = ($resC1['code'] === 200);
            $scenC_code2 = ($resC2['code'] === 200);
            $scenC_stillDocA = str_contains($resC1['body'], 'Marta Elena') && str_contains($resC2['body'], 'Marta Elena');
            $scenC_neverDocB = !str_contains($resC1['body'], 'Carlos Andrés') && !str_contains($resC2['body'], 'Carlos Andrés');
            $scenC_hasOwnStudent = str_contains($resC1['body'], 'Alejandro');
            $scenC = $scenC_code1 && $scenC_code2 && $scenC_stillDocA && $scenC_neverDocB && $scenC_hasOwnStudent;

            // =========================================================================
            // ESCENARIO D — ADMINISTRADOR
            // =========================================================================
            $uAdminData = $this->userRepo->findById($this->fixtureUserAdminId);
            $okLoginAdmin = $loginUser($uAdminData->nombre_usuario, 'DocenteFase7Pass!', $cookieFileAdmin);

            $resD = $httpRequest(sprintf('http://127.0.0.1:%d/docente', $port), 'GET', null, $cookieFileAdmin);
            $scenD_code = ($resD['code'] === 403);
            $scenD = $okLoginAdmin && $scenD_code;

            // =========================================================================
            // ESCENARIO E — USUARIO ANÓNIMO
            // =========================================================================
            $resE = $httpRequest(sprintf('http://127.0.0.1:%d/docente', $port), 'GET', null, null);
            $scenE_code = ($resE['code'] === 302);
            $scenE_redirect = str_contains($resE['location'], '/login');
            $scenE = $scenE_code && $scenE_redirect;

            // =========================================================================
            // ESCENARIO F — ASIGNACIÓN INACTIVA / HISTÓRICA
            // =========================================================================
            // Crear una sección inactiva y asignación inactiva específica para Docente 1
            $stmtSec = $this->db->prepare('INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada) VALUES (:gp, "H", "Sección H Histórica Inactiva", 30, 0)');
            $stmtSec->execute([':gp' => $this->fixtureGradoPeriodoId]);
            $tempSecInacId = (int)$this->db->lastInsertId();

            $stmtAsigInac = $this->db->prepare('INSERT INTO asignaciones_docentes (id_docente, id_seccion, id_aula, id_usuario_creador, fecha_inicio, fecha_fin, estado) VALUES (:d, :s, :a, :u, "2024-01-15", "2024-10-31", "INACTIVA")');
            $stmtAsigInac->execute([':d' => $this->fixtureDocente1Id, ':s' => $tempSecInacId, ':a' => $this->fixtureAula1Id, ':u' => $this->fixtureUserAdminId]);
            $tempAsigInacId = (int)$this->db->lastInsertId();

            $encId = (int)$this->db->query("SELECT id_encargado FROM encargados LIMIT 1")->fetchColumn();
            if ($encId === 0) {
                $this->db->exec('INSERT INTO encargados (nombres, apellidos, telefono) VALUES ("Enc", "Prueba", "55550000")');
                $encId = (int)$this->db->lastInsertId();
            }

            $stmtEstTemp = $this->db->prepare('INSERT INTO estudiantes (codigo_estudiante, nombres, apellidos, fecha_nacimiento, estado) VALUES (:c, "Estudiante", "Histórico Inactivo", "2015-05-05", "ACTIVO")');
            $codEstHist = 'EST-F7-HIST-' . uniqid();
            $stmtEstTemp->execute([':c' => $codEstHist]);
            $tempEstId = (int)$this->db->lastInsertId();

            $this->db->exec("INSERT INTO estudiantes_encargados (id_estudiante, id_encargado, parentesco, es_principal) VALUES ($tempEstId, $encId, 'MADRE', 1)");

            $stmtInsTemp = $this->db->prepare('INSERT INTO inscripciones (id_estudiante, id_seccion, id_encargado, codigo_confirmacion, estado, fecha_confirmacion) VALUES (:est, :sec, :enc, :cod, "CONFIRMADA", "2024-02-01 08:00:00")');
            $stmtInsTemp->execute([':est' => $tempEstId, ':sec' => $tempSecInacId, ':enc' => $encId, ':cod' => 'CONF-HIST-' . uniqid()]);
            $tempInsId = (int)$this->db->lastInsertId();

            $resF = $httpRequest(sprintf('http://127.0.0.1:%d/docente?seccion=%d', $port, $tempSecInacId), 'GET', null, $cookieFileDocA);
            $scenF_rejected = ($resF['code'] === 403);
            $scenF_noStudent = !str_contains($resF['body'], 'Histórico Inactivo');
            $scenF = $scenF_rejected && $scenF_noStudent;

            $passed = $scenA && $scenB && $scenC && $scenD && $scenE && $scenF;

            $obtained = sprintf(
                'A(200 propio): %s | B(403 ajena): %s | C(id_docente ignorado): %s | D(admin 403): %s | E(anónimo 302): %s | F(inactiva 403): %s',
                $scenA ? 'OK' : 'FALLO',
                $scenB ? 'OK' : 'FALLO',
                $scenC ? 'OK' : 'FALLO',
                $scenD ? 'OK' : 'FALLO',
                $scenE ? 'OK' : 'FALLO',
                $scenF ? 'OK' : 'FALLO'
            );

            $this->record(
                'T7-28',
                'Seguridad HTTP real del Portal Docente',
                'A:200, B:403 (sin fugas), C:id_docente ignorado, D:admin 403, E:anónimo 302, F:inactiva 403',
                $obtained,
                $passed
            );
        } finally {
            if ($tempInsId !== null) {
                $this->db->exec("DELETE FROM inscripciones WHERE id_inscripcion = $tempInsId");
            }
            if ($tempEstId !== null) {
                $this->db->exec("DELETE FROM estudiantes_encargados WHERE id_estudiante = $tempEstId");
                $this->db->exec("DELETE FROM estudiantes WHERE id_estudiante = $tempEstId");
            }
            if ($tempAsigInacId !== null) {
                $this->db->exec("DELETE FROM asignaciones_docentes WHERE id_asignacion_docente = $tempAsigInacId");
            }
            if ($tempSecInacId !== null) {
                $this->db->exec("DELETE FROM secciones WHERE id_seccion = $tempSecInacId");
            }

            $this->terminateProcess($pid, $process, $pipes);
            if (file_exists($cookieFileDocA)) {
                @unlink($cookieFileDocA);
            }
            if (file_exists($cookieFileAdmin)) {
                @unlink($cookieFileAdmin);
            }
        }
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
        echo "RESUMEN DE PRUEBAS DE FASE 7\n";
        echo "============================================================\n";
        printf("Total ejecutadas : %d\n", $this->total);
        printf("Exitosas (OK)    : %d\n", $this->passed);
        printf("Fallidas (FALLO) : %d\n", $this->failed);
        echo "============================================================\n";

        if ($this->failed === 0) {
            echo "RESULTADO: TODAS LAS PRUEBAS DE FASE 7 SUPERADAS SATISFACTORIAMENTE.\n";
        } else {
            echo "RESULTADO: EXISTEN PRUEBAS FALLIDAS.\n";
            exit(1);
        }
    }
}

$suite = new Phase7TestSuite();
$suite->run();
