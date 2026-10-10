<?php

declare(strict_types=1);

/**
 * Suite de pruebas automatizadas para Fase 9 — Gestión de Permisos Docentes.
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Cobertura requerida:
 * T9-01 a T9-30: Pruebas unitarias, funcionales, de seguridad, validación y aislamiento de permisos docentes.
 * T9-31: Concurrencia real multiproceso en resolución de solicitudes (SELECT ... FOR UPDATE) con 5 iteraciones reales.
 * T9-32: Atomicidad transaccional y rollback completo ante fallo (DT-57).
 * T9-33: Flujo HTTP real completo del Docente (GET formulario, POST con archivo, listado y aislamiento horizontal A, B, C).
 * T9-34: Flujo HTTP real completo del Administrador (GET bandeja, filtros, POST resolución, historial y re-resolución denegada).
 * T9-35: Verificación de seguridad de almacenamiento: Denegación de acceso directo web a storage/permisos/.
 * T9-36: Control estricto de alcance de Fase 10: Cero notificaciones creadas en tabla notificaciones (RF-26 PENDIENTE).
 * T9-37: Regresión completa de Fase 8 (Asistencia Docente y Geolocalización: 40/40).
 * T9-38: Regresión completa de Fase 7 (Portal Docente: 28/28).
 * T9-39: Regresión completa de Fase 6 (Inscripciones y Cupos: 30/30).
 * T9-40: Regresión completa de Fase 5 (Asignaciones Docentes: 26/26).
 * T9-41: Regresión completa de Fase 4 (Administración Básica: 27/27).
 * T9-42: Regresión completa de Fase 3 (Autenticación y Roles: 19/19).
 * T9-43: Regresión completa de Fase 2 (Arquitectura Base: 16/16).
 * T9-44: Regresión completa de Fase 1 (Base de Datos: 18/18).
 * T9-45: Nombres físicos aleatorios independientes por carga (DT-63).
 * T9-46: Rechazo administrativo con observación opcional (RF-27).
 * Total: 46 pruebas automatizadas.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\AdminPermisoController;
use App\Controllers\DocentePermisoController;
use App\Core\App;
use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\DocumentoPermiso;
use App\Models\HistorialSolicitudPermiso;
use App\Models\SolicitudPermiso;
use App\Models\User;
use App\Repositories\DocenteRepository;
use App\Repositories\PermisoRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\PermisoService;
use App\Services\PortalDocenteService;

class Phase9TestSuite
{
    private PDO $db;
    private UserRepository $userRepo;
    private DocenteRepository $docenteRepo;
    private PermisoRepository $permisoRepo;

    private AuthService $authService;
    private PermisoService $permisoService;
    private PortalDocenteService $portalDocenteService;

    private int $total = 0;
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];

    // Fixtures
    private ?int $fixtureUserAdminId = null;
    private ?int $fixtureUserAdmin2Id = null;
    private ?int $fixtureUserDocente1Id = null;
    private ?int $fixtureDocente1Id = null;
    private ?int $fixtureUserDocente2Id = null;
    private ?int $fixtureDocente2Id = null;
    private ?int $fixtureUserDocenteInactivoId = null;
    private ?int $fixtureDocenteInactivoId = null;

    private array $createdSolicitudIds = [];
    private array $createdDocumentoIds = [];
    private array $createdFilePaths = [];
    private int $initialNotificacionesCount = 0;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->userRepo = new UserRepository($this->db);
        $this->docenteRepo = new DocenteRepository($this->db);
        $this->permisoRepo = new PermisoRepository($this->db);

        $this->authService = new AuthService($this->userRepo);
        $this->permisoService = new PermisoService($this->permisoRepo, $this->docenteRepo);
        $this->portalDocenteService = new PortalDocenteService();
    }

    public function run(): void
    {
        echo "============================================================\n";
        echo "INICIANDO SUITE DE PRUEBAS DE FASE 9: PERMISOS DOCENTES\n";
        echo "============================================================\n\n";

        try {
            $this->cleanPreviousArtifacts();
            $this->setupFixtures();

            // Bloque 1: Modelo Relacional, Registro y Validaciones de Solicitud (RF-24, DT-56, DT-61)
            $this->testT9_01_EstructuraRelacionalMySQL();
            $this->testT9_02_RegistroSolicitudValidaSinDocumento();
            $this->testT9_03_CreacionAtomicaHistorialInicial();
            $this->testT9_04_ValidacionFechaSolicitada();
            $this->testT9_05_ValidacionTipoMotivo();
            $this->testT9_06_ValidacionDescripcion();
            $this->testT9_07_ProteccionManipulacionDocenteCliente();
            $this->testT9_08_ProteccionEstadoInicialCliente();

            // Bloque 2: Carga, Validación y Seguridad de Archivos de Soporte (RF-25, DT-58, DT-59, DT-60)
            $this->testT9_09_CargaDocumentoPdfValido();
            $this->testT9_10_CargaDocumentoImagenValida();
            $this->testT9_11_RechazoScriptRenombrado();
            $this->testT9_12_RechazoArchivoExcede5MB();
            $this->testT9_13_AlmacenamientoSeguroYNombreHasheado();
            $this->testT9_14_InaccesibilidadDirectaHttpStorage();
            $this->testT9_15_DescargaAutorizadaDocentePropietario();
            $this->testT9_16_AislamientoHorizontalDescargaDocentes();
            $this->testT9_17_DescargaAutorizadaAdministrador();

            // Bloque 3: Consultas, Filtros y Aislamiento Horizontal (RF-27, RF-28, DT-61)
            $this->testT9_18_ListadoPermisosAislamientoDocente();
            $this->testT9_19_DetallePermisoAislamientoDocente();
            $this->testT9_20_BandejaAdminPermisosFiltros();

            // Bloque 4: Resolución Administrativa, Transiciones y Auditoría (RF-27, RNF-06, DT-56, DT-57)
            $this->testT9_21_AprobacionAdminExitosa();
            $this->testT9_22_RechazoAdminExitoso();
            $this->testT9_23_RegistroInmutableTransicionHistorial();
            $this->testT9_24_InmutabilidadEstadoTerminal();
            $this->testT9_25_RestriccionRolResolucion();

            // Bloque 5: Seguridad de Aplicación, Sanitización y Restricciones (RNF-06, DT-56, DT-61)
            $this->testT9_26_ProteccionCsrfFormularios();
            $this->testT9_27_SanitizacionXssVistas();
            $this->testT9_28_InmutabilidadMetodosRepositorioHistorial();
            $this->testT9_29_RechazoDocenteInactivoOAnonimo();
            $this->testT9_30_IntegridadReferencialMySQL();

            // Bloque 6: Concurrencia Real, Transacciones y Flujos HTTP Reales (DT-57, DT-62)
            $this->testT9_31_ConcurrenciaRealResolucion();
            $this->testT9_32_AtomicidadTransaccionalRollback();
            $this->testT9_33_FlujoHttpRealDocente();
            $this->testT9_34_FlujoHttpRealAdmin();
            $this->testT9_35_SeguridadAlmacenamientoHttpDirecto();
            $this->testT9_36_ControlAlcanceNotificacionesFase10();

            // Bloque 7: Regresiones Completas de Fases 1 a 8
            $this->testT9_37_RegresionFase8();
            $this->testT9_38_RegresionFase7();
            $this->testT9_39_RegresionFase6();
            $this->testT9_40_RegresionFase5();
            $this->testT9_41_RegresionFase4();
            $this->testT9_42_RegresionFase3();
            $this->testT9_43_RegresionFase2();
            $this->testT9_44_RegresionFase1();

            // Bloque 8: Pruebas complementarias F9-04 y F9-05 (DT-63, RF-27)
            $this->testT9_45_NombresFisicosAleatoriosPorCarga();
            $this->testT9_46_RechazoAdminConObservacionOpcional();

        } finally {
            $this->cleanup();
        }

        $this->printSummary();
    }

    private function setupFixtures(): void
    {
        $this->db->exec("DELETE FROM solicitudes_permiso WHERE id_docente IN (SELECT id_docente FROM docentes WHERE id_usuario IN (SELECT id_usuario FROM usuarios WHERE nombre_usuario IN ('elena.garcia', 'roberto.alvarado', 'luis.ramirez')))");
        $this->db->exec("DELETE FROM docentes WHERE id_usuario IN (SELECT id_usuario FROM usuarios WHERE nombre_usuario IN ('elena.garcia', 'roberto.alvarado', 'luis.ramirez'))");
        $this->db->exec("DELETE FROM usuarios WHERE nombre_usuario IN ('elena.garcia', 'roberto.alvarado', 'luis.ramirez')");

        $stmtRol = $this->db->query("SELECT id_rol FROM roles WHERE nombre IN ('ADMINISTRACION', 'ADMINISTRADOR') LIMIT 1");
        $rolAdminId = (int) $stmtRol->fetchColumn();

        $stmtRolDoc = $this->db->query("SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'");
        $rolDocId = (int) $stmtRolDoc->fetchColumn();

        $this->initialNotificacionesCount = (int) $this->db->query("SELECT COUNT(*) FROM notificaciones")->fetchColumn();

        $passHash = password_hash('PassFase9Test!', PASSWORD_BCRYPT);

        // 1. Usuario Admin 1
        $uAdmin = 'adm_f9_' . rand(1000, 9999);
        $stmt = $this->db->prepare('INSERT INTO usuarios (id_rol, nombre_usuario, correo, clave_hash, nombres, apellidos, estado) VALUES (:r, :u, :c, :p, :n, :a, "ACTIVO")');
        $stmt->execute([':r' => $rolAdminId, ':u' => $uAdmin, ':c' => $uAdmin . '@escuela.test', ':p' => $passHash, ':n' => 'Admin 1', ':a' => 'Fase 9']);
        $this->fixtureUserAdminId = (int) $this->db->lastInsertId();

        // 2. Usuario Admin 2 (para concurrencia)
        $uAdmin2 = 'adm2_f9_' . rand(1000, 9999);
        $stmt->execute([':r' => $rolAdminId, ':u' => $uAdmin2, ':c' => $uAdmin2 . '@escuela.test', ':p' => $passHash, ':n' => 'Admin 2', ':a' => 'Concurrente']);
        $this->fixtureUserAdmin2Id = (int) $this->db->lastInsertId();

        // 3. Docente 1
        $uDoc1 = 'elena.garcia';
        $stmt->execute([':r' => $rolDocId, ':u' => $uDoc1, ':c' => 'elena.garcia@escuela.com', ':p' => $passHash, ':n' => 'Elena', ':a' => 'García Santos']);
        $this->fixtureUserDocente1Id = (int) $this->db->lastInsertId();

        $stmtDoc = $this->db->prepare('INSERT INTO docentes (id_usuario, codigo_docente, fecha_ingreso, estado) VALUES (:u, :cod, "2024-01-15", "ACTIVO")');
        $cDoc1 = 'DOC-F9-01-' . rand(100, 999);
        $stmtDoc->execute([':u' => $this->fixtureUserDocente1Id, ':cod' => $cDoc1]);
        $this->fixtureDocente1Id = (int) $this->db->lastInsertId();

        // 4. Docente 2 (para pruebas de aislamiento)
        $uDoc2 = 'roberto.alvarado';
        $stmt->execute([':r' => $rolDocId, ':u' => $uDoc2, ':c' => 'roberto.alvarado@escuela.com', ':p' => $passHash, ':n' => 'Roberto', ':a' => 'Alvarado']);
        $this->fixtureUserDocente2Id = (int) $this->db->lastInsertId();

        $cDoc2 = 'DOC-F9-02-' . rand(100, 999);
        $stmtDoc->execute([':u' => $this->fixtureUserDocente2Id, ':cod' => $cDoc2]);
        $this->fixtureDocente2Id = (int) $this->db->lastInsertId();

        // 5. Docente Inactivo
        $uDocInac = 'luis.ramirez';
        $stmt->execute([':r' => $rolDocId, ':u' => $uDocInac, ':c' => 'luis.ramirez@escuela.com', ':p' => $passHash, ':n' => 'Luis', ':a' => 'Ramírez']);
        $this->fixtureUserDocenteInactivoId = (int) $this->db->lastInsertId();

        $cDocInac = 'DOC-F9-INAC-' . rand(100, 999);
        $stmtDocInac = $this->db->prepare('INSERT INTO docentes (id_usuario, codigo_docente, fecha_ingreso, estado) VALUES (:u, :cod, "2024-01-15", "INACTIVO")');
        $stmtDocInac->execute([':u' => $this->fixtureUserDocenteInactivoId, ':cod' => $cDocInac]);
        $this->fixtureDocenteInactivoId = (int) $this->db->lastInsertId();
    }

    private function cleanPreviousArtifacts(): void
    {
        Session::reset();
    }

    private function cleanup(): void
    {
        Session::reset();

        try {
            // Eliminar archivos físicos creados durante pruebas
            foreach ($this->createdFilePaths as $path) {
                if (file_exists($path)) {
                    @unlink($path);
                }
            }

            // Eliminar solicitudes de prueba (cascade borra documentos e historial)
            if (!empty($this->createdSolicitudIds)) {
                $in = implode(',', array_map('intval', $this->createdSolicitudIds));
                $this->db->exec("DELETE FROM historial_solicitudes_permiso WHERE id_solicitud_permiso IN ($in)");
                $this->db->exec("DELETE FROM documentos_permiso WHERE id_solicitud_permiso IN ($in)");
                $this->db->exec("DELETE FROM solicitudes_permiso WHERE id_solicitud_permiso IN ($in)");
            }

            if ($this->fixtureDocente1Id && $this->fixtureDocente2Id) {
                $docIds = implode(',', array_filter([$this->fixtureDocente1Id, $this->fixtureDocente2Id, $this->fixtureDocenteInactivoId]));
                $stmt = $this->db->query("SELECT id_solicitud_permiso FROM solicitudes_permiso WHERE id_docente IN ($docIds)");
                $solIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($solIds)) {
                    $in = implode(',', array_map('intval', $solIds));
                    $this->db->exec("DELETE FROM historial_solicitudes_permiso WHERE id_solicitud_permiso IN ($in)");
                    $this->db->exec("DELETE FROM documentos_permiso WHERE id_solicitud_permiso IN ($in)");
                    $this->db->exec("DELETE FROM solicitudes_permiso WHERE id_solicitud_permiso IN ($in)");
                }
            }

            // Eliminar docentes fixtures
            $docIn = implode(',', array_filter([$this->fixtureDocente1Id, $this->fixtureDocente2Id, $this->fixtureDocenteInactivoId]));
            if (!empty($docIn)) {
                $this->db->exec("DELETE FROM docentes WHERE id_docente IN ($docIn)");
            }

            // Eliminar usuarios fixtures
            $userIn = implode(',', array_filter([$this->fixtureUserAdminId, $this->fixtureUserAdmin2Id, $this->fixtureUserDocente1Id, $this->fixtureUserDocente2Id, $this->fixtureUserDocenteInactivoId]));
            if (!empty($userIn)) {
                $this->db->exec("DELETE FROM usuarios WHERE id_usuario IN ($userIn)");
            }
        } catch (\Throwable $e) {
            // Ignorar errores de cleanup
        }
    }

    private function record(string $id, string $desc, string $expected, string $obtained, bool $passed): void
    {
        $this->total++;
        if ($passed) {
            $this->passed++;
            $status = '[OK]';
        } else {
            $this->failed++;
            $status = '[FALLO]';
        }

        $this->results[] = compact('id', 'desc', 'expected', 'obtained', 'passed', 'status');

        printf("%s %s: %s\n", $status, $id, $desc);
        if (!$passed) {
            printf("   Esperado: %s\n", $expected);
            printf("   Obtenido: %s\n", $obtained);
        }
    }

    private function loginAsUser(int $userId): void
    {
        Session::reset();
        Session::start();
        $user = $this->userRepo->findById($userId);
        if ($user !== null) {
            Session::set('auth_user_id', $user->id_usuario);
            Session::set('auth_role', $user->rol_nombre);
            Session::set('auth_username', $user->nombre_usuario);
            Session::set('user_id', $user->id_usuario);
            Session::set('user_role', $user->rol_nombre);
            Session::set('user_nombre', $user->nombre_usuario);
        }
    }

    // =========================================================================
    // BLOQUE 1: MODELO RELACIONAL Y REGISTRO DE SOLICITUD (RF-24, DT-56, DT-61)
    // =========================================================================

    private function testT9_01_EstructuraRelacionalMySQL(): void
    {
        $tablas = ['solicitudes_permiso', 'historial_solicitudes_permiso', 'documentos_permiso'];
        $encontradas = 0;
        foreach ($tablas as $t) {
            $stmt = $this->db->query("SHOW TABLES LIKE '$t'");
            if ($stmt->fetchColumn() !== false) {
                $encontradas++;
            }
        }

        // Verificar columnas clave de solicitudes_permiso
        $cols = $this->db->query("SHOW COLUMNS FROM solicitudes_permiso")->fetchAll(PDO::FETCH_COLUMN);
        $hasColumns = in_array('id_solicitud_permiso', $cols, true)
            && in_array('id_docente', $cols, true)
            && in_array('fecha_solicitada', $cols, true)
            && in_array('tipo_motivo', $cols, true)
            && in_array('descripcion', $cols, true)
            && in_array('estado', $cols, true);

        // Verificar check constraint de estado ('PENDIENTE', 'APROBADO', 'RECHAZADO')
        $stmtCreate = $this->db->query("SHOW CREATE TABLE solicitudes_permiso")->fetch(PDO::FETCH_ASSOC);
        $createSql = $stmtCreate['Create Table'] ?? '';
        $hasCheck = str_contains($createSql, 'PENDIENTE') && str_contains($createSql, 'APROBADO') && str_contains($createSql, 'RECHAZADO');

        $isOk = ($encontradas === 3 && $hasColumns && $hasCheck);

        $this->record(
            'T9-01',
            'Estructura relacional de permisos en MySQL y restricciones canónicas (DT-56)',
            '3 tablas creadas con columnas correctas y check constraint de estado (PENDIENTE, APROBADO, RECHAZADO)',
            sprintf('Tablas: %d/3, Columnas: %s, Check: %s', $encontradas, $hasColumns ? 'OK' : 'ERR', $hasCheck ? 'OK' : 'ERR'),
            $isOk
        );
    }

    private function testT9_02_RegistroSolicitudValidaSinDocumento(): void
    {
        $fecha = date('Y-m-d', strtotime('+3 days'));
        $datos = [
            'fecha_solicitada' => $fecha,
            'tipo_motivo' => 'Cita Médica del IGSS',
            'descripcion' => 'Consulta programada en la clínica periférica para control rutinario.',
        ];

        $solicitud = $this->permisoService->crearSolicitud(
            $this->fixtureDocente1Id,
            $this->fixtureUserDocente1Id,
            $datos,
            null
        );

        $this->createdSolicitudIds[] = $solicitud->id_solicitud_permiso;

        $dbRow = $this->permisoRepo->findById($solicitud->id_solicitud_permiso);

        $isOk = ($solicitud->id_solicitud_permiso > 0)
            && ($solicitud->id_docente === $this->fixtureDocente1Id)
            && ($solicitud->fecha_solicitada === $fecha)
            && ($solicitud->tipo_motivo === 'Cita Médica del IGSS')
            && ($solicitud->isPendiente())
            && ($dbRow !== null && $dbRow->estado === 'PENDIENTE');

        $this->record(
            'T9-02',
            'Registro de solicitud de permiso válida sin documento (RF-24, DT-56)',
            'Solicitud creada exitosamente con estado PENDIENTE y persistida en BD',
            sprintf('ID: %d, Docente: %d, Estado: %s', $solicitud->id_solicitud_permiso, $solicitud->id_docente, $solicitud->estado),
            $isOk
        );
    }

    private function testT9_03_CreacionAtomicaHistorialInicial(): void
    {
        $ultimoId = end($this->createdSolicitudIds);
        $historial = $this->permisoRepo->findHistorialBySolicitudId($ultimoId);

        $isOk = (count($historial) === 1)
            && ($historial[0]->estado_anterior === null)
            && ($historial[0]->estado_nuevo === 'PENDIENTE')
            && ($historial[0]->id_usuario_actor === $this->fixtureUserDocente1Id)
            && !empty($historial[0]->fecha_cambio);

        $this->record(
            'T9-03',
            'Creación atómica del historial de auditoría al registrar solicitud (RNF-06, DT-56)',
            '1 registro en historial_solicitudes_permiso (estado_anterior: null, estado_nuevo: PENDIENTE)',
            sprintf('Historial: %d registros, Nuevo: %s, Anterior: %s', count($historial), $historial[0]->estado_nuevo ?? 'none', var_export($historial[0]->estado_anterior ?? null, true)),
            $isOk
        );
    }

    private function testT9_04_ValidacionFechaSolicitada(): void
    {
        $casos = [
            ['fecha' => '', 'error' => 'La fecha solicitada es obligatoria'],
            ['fecha' => 'no-es-fecha', 'error' => 'formato de fecha inválido'],
            ['fecha' => '2020-02-31', 'error' => 'formato de fecha inválido'],
        ];

        $superados = 0;
        foreach ($casos as $caso) {
            try {
                $this->permisoService->crearSolicitud(
                    $this->fixtureDocente1Id,
                    $this->fixtureUserDocente1Id,
                    ['fecha_solicitada' => $caso['fecha'], 'tipo_motivo' => 'Motivo', 'descripcion' => 'Desc'],
                    null
                );
            } catch (\InvalidArgumentException $e) {
                if (stripos($e->getMessage(), 'fecha') !== false) {
                    $superados++;
                }
            }
        }

        $isOk = ($superados === count($casos));

        $this->record(
            'T9-04',
            'Validación de fecha solicitada obligatoria y formato válido (RF-24)',
            '3/3 casos inválidos rechazados con InvalidArgumentException controlada',
            sprintf('%d/%d casos rechazados correctamente', $superados, count($casos)),
            $isOk
        );
    }

    private function testT9_05_ValidacionTipoMotivo(): void
    {
        $casos = [
            ['motivo' => '', 'error' => 'obligatorio'],
            ['motivo' => '   ', 'error' => 'obligatorio'],
            ['motivo' => str_repeat('A', 101), 'error' => '100 caracteres'],
        ];

        $superados = 0;
        foreach ($casos as $caso) {
            try {
                $this->permisoService->crearSolicitud(
                    $this->fixtureDocente1Id,
                    $this->fixtureUserDocente1Id,
                    ['fecha_solicitada' => date('Y-m-d'), 'tipo_motivo' => $caso['motivo'], 'descripcion' => 'Desc válida'],
                    null
                );
            } catch (\InvalidArgumentException $e) {
                if (stripos($e->getMessage(), 'motivo') !== false) {
                    $superados++;
                }
            }
        }

        $isOk = ($superados === count($casos));

        $this->record(
            'T9-05',
            'Validación de tipo_motivo obligatorio y longitud máxima (RF-24)',
            '3/3 casos inválidos rechazados con InvalidArgumentException controlada',
            sprintf('%d/%d casos rechazados correctamente', $superados, count($casos)),
            $isOk
        );
    }

    private function testT9_06_ValidacionDescripcion(): void
    {
        $casos = [
            ['desc' => '', 'error' => 'obligatoria'],
            ['desc' => '   ', 'error' => 'obligatoria'],
            ['desc' => str_repeat('X', 1001), 'error' => '1000 caracteres'],
        ];

        $superados = 0;
        foreach ($casos as $caso) {
            try {
                $this->permisoService->crearSolicitud(
                    $this->fixtureDocente1Id,
                    $this->fixtureUserDocente1Id,
                    ['fecha_solicitada' => date('Y-m-d'), 'tipo_motivo' => 'Motivo', 'descripcion' => $caso['desc']],
                    null
                );
            } catch (\InvalidArgumentException $e) {
                if (stripos($e->getMessage(), 'descripción') !== false) {
                    $superados++;
                }
            }
        }

        $isOk = ($superados === count($casos));

        $this->record(
            'T9-06',
            'Validación de descripcion obligatoria y longitud máxima (RF-24)',
            '3/3 casos inválidos rechazados con InvalidArgumentException controlada',
            sprintf('%d/%d casos rechazados correctamente', $superados, count($casos)),
            $isOk
        );
    }

    private function testT9_07_ProteccionManipulacionDocenteCliente(): void
    {
        // El cliente intenta enviar id_docente=9999 en el payload POST
        $this->loginAsUser($this->fixtureUserDocente1Id);
        $controller = new DocentePermisoController($this->authService, $this->permisoService, $this->portalDocenteService);

        $request = new Request(
            'POST',
            '/docente/permisos/crear',
            [],
            [
                '_token' => csrf_token(),
                'id_docente' => 9999, // Intentando suplantar docente
                'fecha_solicitada' => date('Y-m-d', strtotime('+5 days')),
                'tipo_motivo' => 'Asunto Personal',
                'descripcion' => 'Trámite personal justificado.',
            ],
            ['accept' => 'application/json', 'x-requested-with' => 'xmlhttprequest']
        );

        $response = $controller->store($request);
        $body = json_decode($response->getContent(), true);

        $solicitudId = (int) ($body['solicitud']['id_solicitud_permiso'] ?? 0);
        if ($solicitudId > 0) {
            $this->createdSolicitudIds[] = $solicitudId;
        }

        $solicitudBd = $this->permisoRepo->findById($solicitudId);

        $isOk = ($response->getStatusCode() === 200)
            && ($solicitudBd !== null)
            && ($solicitudBd->id_docente === $this->fixtureDocente1Id) // Se usó el del docente autenticado, ignorando 9999
            && ($solicitudBd->id_docente !== 9999);

        $this->record(
            'T9-07',
            'Protección contra manipulación de id_docente desde el cliente (DT-61)',
            'Parámetro id_docente ignorado y resuelto autoritativamente desde la sesión del usuario',
            sprintf('HTTP %d, ID en BD: %d (esperado: %d)', $response->getStatusCode(), $solicitudBd?->id_docente ?? 0, $this->fixtureDocente1Id),
            $isOk
        );
    }

    private function testT9_08_ProteccionEstadoInicialCliente(): void
    {
        // El cliente intenta forzar estado='APROBADO' en el payload
        $this->loginAsUser($this->fixtureUserDocente1Id);
        $controller = new DocentePermisoController($this->authService, $this->permisoService, $this->portalDocenteService);

        $request = new Request(
            'POST',
            '/docente/permisos/crear',
            [],
            [
                '_token' => csrf_token(),
                'estado' => 'APROBADO', // Intento de mass assignment
                'fecha_solicitada' => date('Y-m-d', strtotime('+6 days')),
                'tipo_motivo' => 'Calamidad Doméstica',
                'descripcion' => 'Inundación en residencia familiar.',
            ],
            ['accept' => 'application/json', 'x-requested-with' => 'xmlhttprequest']
        );

        $response = $controller->store($request);
        $body = json_decode($response->getContent(), true);

        $solicitudId = (int) ($body['solicitud']['id_solicitud_permiso'] ?? 0);
        if ($solicitudId > 0) {
            $this->createdSolicitudIds[] = $solicitudId;
        }

        $solicitudBd = $this->permisoRepo->findById($solicitudId);

        $isOk = ($response->getStatusCode() === 200)
            && ($solicitudBd !== null)
            && ($solicitudBd->estado === 'PENDIENTE')
            && ($solicitudBd->estado !== 'APROBADO');

        $this->record(
            'T9-08',
            'Protección de estado inicial contra mass assignment (DT-56)',
            'Estado "APROBADO" inyectado ignorado; backend fuerza PENDIENTE en base de datos',
            sprintf('HTTP %d, Estado en BD: %s', $response->getStatusCode(), $solicitudBd?->estado ?? 'null'),
            $isOk
        );
    }

    // =========================================================================
    // BLOQUE 2: CARGA, VALIDACIÓN Y SEGURIDAD DE ARCHIVOS (RF-25, DT-58, DT-59, DT-60)
    // =========================================================================

    private function testT9_09_CargaDocumentoPdfValido(): void
    {
        $tmpPdf = tempnam(sys_get_temp_dir(), 'test_pdf_') . '.pdf';
        // Generar archivo PDF sintético con magic bytes válidos (%PDF-1.4)
        $content = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 3 3]>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000102 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n162\n%%EOF";
        file_put_contents($tmpPdf, $content);

        $archivo = [
            'name' => 'constancia_medica_igss.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $tmpPdf,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmpPdf),
        ];

        $solicitud = $this->permisoService->crearSolicitud(
            $this->fixtureDocente1Id,
            $this->fixtureUserDocente1Id,
            [
                'fecha_solicitada' => date('Y-m-d', strtotime('+7 days')),
                'tipo_motivo' => 'Cita Médica Especialista',
                'descripcion' => 'Adjunto comprobante emitido por el hospital.',
            ],
            $archivo
        );

        $this->createdSolicitudIds[] = $solicitud->id_solicitud_permiso;
        if ($solicitud->documento) {
            $this->createdDocumentoIds[] = $solicitud->documento->id_documento_permiso;
            $this->createdFilePaths[] = dirname(__DIR__) . '/' . $solicitud->documento->ruta_almacenamiento;
        }

        @unlink($tmpPdf);

        $docBd = $this->permisoRepo->findDocumentoBySolicitudId($solicitud->id_solicitud_permiso);
        $rutaFisica = dirname(__DIR__) . '/' . ($docBd?->ruta_almacenamiento ?? '');
        $existeEnDisco = file_exists($rutaFisica);

        $isOk = ($docBd !== null)
            && ($docBd->nombre_original === 'constancia_medica_igss.pdf')
            && ($docBd->tipo_mime === 'application/pdf')
            && $existeEnDisco;

        $this->record(
            'T9-09',
            'Carga y validación exitosa de documento PDF con magic bytes reales (RF-25, DT-60)',
            'Documento PDF persistido en BD y almacenado físicamente con MIME application/pdf',
            sprintf('ID Doc: %d, MIME: %s, Existe en disco: %s', $docBd?->id_documento_permiso ?? 0, $docBd?->tipo_mime ?? 'null', $existeEnDisco ? 'SÍ' : 'NO'),
            $isOk
        );
    }

    private function testT9_10_CargaDocumentoImagenValida(): void
    {
        $tmpPng = tempnam(sys_get_temp_dir(), 'test_img_') . '.png';
        // PNG minimal de 1x1 píxel con magic bytes legítimos
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        file_put_contents($tmpPng, $pngBytes);

        $archivo = [
            'name' => 'foto_receta.png',
            'type' => 'image/png',
            'tmp_name' => $tmpPng,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmpPng),
        ];

        $solicitud = $this->permisoService->crearSolicitud(
            $this->fixtureDocente1Id,
            $this->fixtureUserDocente1Id,
            [
                'fecha_solicitada' => date('Y-m-d', strtotime('+8 days')),
                'tipo_motivo' => 'Exámenes Médicos',
                'descripcion' => 'Fotografía de la orden de laboratorios.',
            ],
            $archivo
        );

        $this->createdSolicitudIds[] = $solicitud->id_solicitud_permiso;
        if ($solicitud->documento) {
            $this->createdDocumentoIds[] = $solicitud->documento->id_documento_permiso;
            $this->createdFilePaths[] = dirname(__DIR__) . '/' . $solicitud->documento->ruta_almacenamiento;
        }

        @unlink($tmpPng);

        $docBd = $this->permisoRepo->findDocumentoBySolicitudId($solicitud->id_solicitud_permiso);

        $isOk = ($docBd !== null)
            && ($docBd->tipo_mime === 'image/png')
            && ($docBd->nombre_original === 'foto_receta.png');

        $this->record(
            'T9-10',
            'Carga y validación exitosa de documento de imagen PNG (RF-25, DT-60)',
            'Imagen PNG aceptada y catalogada con MIME image/png tras inspección de contenido',
            sprintf('ID Doc: %d, MIME: %s, Nombre: %s', $docBd?->id_documento_permiso ?? 0, $docBd?->tipo_mime ?? 'null', $docBd?->nombre_original ?? 'null'),
            $isOk
        );
    }

    private function testT9_11_RechazoScriptRenombrado(): void
    {
        $tmpMalicioso = tempnam(sys_get_temp_dir(), 'test_sh_') . '.pdf';
        // Archivo que tiene extensión .pdf pero contenido PHP malicioso
        file_put_contents($tmpMalicioso, "<?php phpinfo(); ?>");

        $archivo = [
            'name' => 'payload_camuflado.pdf',
            'type' => 'application/pdf', // El cliente mintió en el Content-Type
            'tmp_name' => $tmpMalicioso,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmpMalicioso),
        ];

        $rechazado = false;
        $errorMsg = '';
        try {
            $this->permisoService->crearSolicitud(
                $this->fixtureDocente1Id,
                $this->fixtureUserDocente1Id,
                [
                    'fecha_solicitada' => date('Y-m-d', strtotime('+9 days')),
                    'tipo_motivo' => 'Motivo X',
                    'descripcion' => 'Ataque de prueba.',
                ],
                $archivo
            );
        } catch (\InvalidArgumentException $e) {
            $rechazado = true;
            $errorMsg = $e->getMessage();
        } finally {
            @unlink($tmpMalicioso);
        }

        $isOk = $rechazado && (stripos($errorMsg, 'no permitido') !== false || stripos($errorMsg, 'formato') !== false);

        $this->record(
            'T9-11',
            'Rechazo de archivo ejecutable/PHP camuflado con extensión permitida (DT-60)',
            'Archivo PHP disfrazado de .pdf rechazado mediante finfo en el servidor',
            sprintf('Rechazado: %s, Mensaje: "%s"', $rechazado ? 'SÍ' : 'NO', $errorMsg),
            $isOk
        );
    }

    private function testT9_12_RechazoArchivoExcede5MB(): void
    {
        $tmpGrande = tempnam(sys_get_temp_dir(), 'test_big_') . '.pdf';
        // Crear un archivo ficticio que supere los 5MB (5 * 1024 * 1024 + 1 bytes)
        $fp = fopen($tmpGrande, 'wb');
        fseek($fp, (5 * 1024 * 1024) + 10);
        fwrite($fp, "\0");
        fclose($fp);

        $archivo = [
            'name' => 'archivo_gigante.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $tmpGrande,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmpGrande),
        ];

        $rechazado = false;
        $errorMsg = '';
        try {
            $this->permisoService->crearSolicitud(
                $this->fixtureDocente1Id,
                $this->fixtureUserDocente1Id,
                [
                    'fecha_solicitada' => date('Y-m-d', strtotime('+10 days')),
                    'tipo_motivo' => 'Motivo Tamaño',
                    'descripcion' => 'Prueba de límite de tamaño.',
                ],
                $archivo
            );
        } catch (\InvalidArgumentException $e) {
            $rechazado = true;
            $errorMsg = $e->getMessage();
        } finally {
            @unlink($tmpGrande);
        }

        $isOk = $rechazado && stripos($errorMsg, '5 MB') !== false;

        $this->record(
            'T9-12',
            'Rechazo de documento que excede el límite de 5MB (DT-60)',
            'Archivo > 5MB rechazado con mensaje de tamaño excedido',
            sprintf('Rechazado: %s, Mensaje: "%s"', $rechazado ? 'SÍ' : 'NO', $errorMsg),
            $isOk
        );
    }

    private function testT9_13_AlmacenamientoSeguroYNombreHasheado(): void
    {
        $ultimoDocId = end($this->createdDocumentoIds);
        $doc = $this->permisoRepo->findDocumentoById($ultimoDocId);

        $ruta = $doc?->ruta_almacenamiento ?? '';
        // Debe comenzar con storage/permisos/
        $enStorage = str_starts_with($ruta, 'storage/permisos/');
        // No debe contener el nombre original del cliente sin hashear
        $nombreFisico = basename($ruta);
        $tieneHash = preg_match('/^permiso_[a-f0-9]{32,64}\./', $nombreFisico) === 1;
        // No debe contener secuencias de directory traversal
        $sinTraversal = !str_contains($ruta, '..') && !str_contains($ruta, "\0");

        $isOk = ($doc !== null) && $enStorage && $tieneHash && $sinTraversal;

        $this->record(
            'T9-13',
            'Almacenamiento privado con nombre único hasheado/aleatorio y sin path traversal (DT-58, DT-63)',
            'Ruta en storage/permisos/ con prefijo seguro y sin secuencias de escape',
            sprintf('Ruta: "%s", En storage: %s, Tiene hash: %s', $ruta, $enStorage ? 'SÍ' : 'NO', $tieneHash ? 'SÍ' : 'NO'),
            $isOk
        );
    }

    private function testT9_14_InaccesibilidadDirectaHttpStorage(): void
    {
        // La raíz del servidor web es /public. La carpeta /storage se encuentra fuera de /public.
        $storageDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'permisos';
        $publicDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public';

        $isOutsidePublic = !str_starts_with(realpath($storageDir), realpath($publicDir));

        $this->record(
            'T9-14',
            'Inaccesibilidad directa vía HTTP de archivos subidos en storage/ (DT-58)',
            'storage/permisos/ reside fuera del document root (public/), impidiendo acceso web directo',
            sprintf('Storage: %s | Public: %s | Aislado: %s', $storageDir, $publicDir, $isOutsidePublic ? 'SÍ' : 'NO'),
            $isOutsidePublic
        );
    }

    private function testT9_15_DescargaAutorizadaDocentePropietario(): void
    {
        $this->loginAsUser($this->fixtureUserDocente1Id);
        $controller = new DocentePermisoController($this->authService, $this->permisoService, $this->portalDocenteService);

        $ultimoDocId = end($this->createdDocumentoIds);

        $request = new Request(
            'GET',
            '/docente/permisos/documento',
            ['id' => $ultimoDocId]
        );

        $response = $controller->descargarDocumento($request);
        $headers = $response->getHeaders();

        $contentDisposition = $headers['content-disposition'] ?? $headers['Content-Disposition'] ?? '';
        $contentTypeOptions = $headers['x-content-type-options'] ?? $headers['X-Content-Type-Options'] ?? '';

        $isOk = ($response->getStatusCode() === 200)
            && str_contains($contentDisposition, 'attachment')
            && ($contentTypeOptions === 'nosniff')
            && !empty($response->getContent());

        $this->record(
            'T9-15',
            'Descarga autorizada de soporte por el docente propietario con headers seguros (DT-59)',
            'HTTP 200 con Content-Disposition: attachment y X-Content-Type-Options: nosniff',
            sprintf('HTTP %d, Disposition: "%s", NoSniff: "%s"', $response->getStatusCode(), $contentDisposition, $contentTypeOptions),
            $isOk
        );
    }

    private function testT9_16_AislamientoHorizontalDescargaDocentes(): void
    {
        // Iniciar sesión como Docente 2 e intentar descargar el archivo de Docente 1
        $this->loginAsUser($this->fixtureUserDocente2Id);
        $controller = new DocentePermisoController($this->authService, $this->permisoService, $this->portalDocenteService);

        $ultimoDocId = end($this->createdDocumentoIds); // Pertenece a Docente 1

        $request = new Request(
            'GET',
            '/docente/permisos/documento',
            ['id' => $ultimoDocId]
        );

        $response = $controller->descargarDocumento($request);

        $isOk = ($response->getStatusCode() === 403);

        $this->record(
            'T9-16',
            'Aislamiento horizontal: Docente ajeno no puede descargar documento de otro docente (DT-61)',
            'HTTP 403 Acceso Denegado al intentar descargar documento ajeno',
            sprintf('HTTP %d (esperado: 403)', $response->getStatusCode()),
            $isOk
        );
    }

    private function testT9_17_DescargaAutorizadaAdministrador(): void
    {
        // Administrador puede descargar cualquier archivo para revisión institucional
        $this->loginAsUser($this->fixtureUserAdminId);
        $controller = new AdminPermisoController($this->authService, $this->permisoService, $this->docenteRepo);

        $ultimoDocId = end($this->createdDocumentoIds);

        $request = new Request(
            'GET',
            '/administracion/permisos/documento',
            ['id' => $ultimoDocId]
        );

        $response = $controller->descargarDocumento($request);
        $headers = $response->getHeaders();

        $contentDisposition = $headers['content-disposition'] ?? $headers['Content-Disposition'] ?? '';
        $contentTypeOptions = $headers['x-content-type-options'] ?? $headers['X-Content-Type-Options'] ?? '';

        $isOk = ($response->getStatusCode() === 200)
            && str_contains($contentDisposition, 'attachment')
            && ($contentTypeOptions === 'nosniff');

        $this->record(
            'T9-17',
            'Descarga autorizada de documento de soporte para Administrador (RF-27, DT-59)',
            'HTTP 200 con headers de seguridad permitida a administradores para auditoría',
            sprintf('HTTP %d, Content-Type: %s', $response->getStatusCode(), $headers['Content-Type'] ?? 'none'),
            $isOk
        );
    }

    // =========================================================================
    // BLOQUE 3: CONSULTAS, BANDEJA Y AISLAMIENTO HORIZONTAL (RF-27, RF-28, DT-61)
    // =========================================================================

    private function testT9_18_ListadoPermisosAislamientoDocente(): void
    {
        // Registrar una solicitud para Docente 2
        $solDoc2 = $this->permisoService->crearSolicitud(
            $this->fixtureDocente2Id,
            $this->fixtureUserDocente2Id,
            [
                'fecha_solicitada' => date('Y-m-d', strtotime('+12 days')),
                'tipo_motivo' => 'Permiso Docente 2',
                'descripcion' => 'Solicitud propia de Docente 2.',
            ],
            null
        );
        $this->createdSolicitudIds[] = $solDoc2->id_solicitud_permiso;

        // Consultar solicitudes de Docente 1
        $solicitudesDoc1 = $this->permisoService->obtenerSolicitudesDocente($this->fixtureDocente1Id);

        // Verificar que NINGUNA solicitud pertenezca a Docente 2
        $todasPertenecenDoc1 = true;
        foreach ($solicitudesDoc1 as $s) {
            if ($s->id_docente !== $this->fixtureDocente1Id) {
                $todasPertenecenDoc1 = false;
                break;
            }
        }

        $tieneDoc2 = false;
        foreach ($solicitudesDoc1 as $s) {
            if ($s->id_solicitud_permiso === $solDoc2->id_solicitud_permiso) {
                $tieneDoc2 = true;
                break;
            }
        }

        $isOk = $todasPertenecenDoc1 && !$tieneDoc2 && (count($solicitudesDoc1) > 0);

        $this->record(
            'T9-18',
            'Listado personal de permisos con aislamiento horizontal estricto (RF-28, DT-61)',
            'El docente solo visualiza sus propias solicitudes; cero registros de otros docentes',
            sprintf('Total Doc1: %d, Contiene Doc2: %s, Todo propio: %s', count($solicitudesDoc1), $tieneDoc2 ? 'SÍ' : 'NO', $todasPertenecenDoc1 ? 'SÍ' : 'NO'),
            $isOk
        );
    }

    private function testT9_19_DetallePermisoAislamientoDocente(): void
    {
        // Docente 1 intenta ver el detalle de la solicitud de Docente 2
        $solDoc2Id = end($this->createdSolicitudIds);

        $denegado = false;
        try {
            $this->permisoService->obtenerDetalleSolicitudDocente($solDoc2Id, $this->fixtureDocente1Id);
        } catch (\DomainException $e) {
            $denegado = true;
        }

        $this->record(
            'T9-19',
            'Detalle de permiso: Denegación de acceso a solicitud ajena (RF-28, DT-61)',
            'DomainException (Acceso no autorizado) al intentar consultar solicitud de otro docente',
            sprintf('Denegado: %s', $denegado ? 'SÍ' : 'NO'),
            $denegado
        );
    }

    private function testT9_20_BandejaAdminPermisosFiltros(): void
    {
        // Administrador consulta con filtro por estado 'PENDIENTE'
        $pendientes = $this->permisoService->obtenerSolicitudesAdmin(['estado' => 'PENDIENTE']);
        $todosPendientes = true;
        foreach ($pendientes as $s) {
            if ($s->estado !== 'PENDIENTE') {
                $todosPendientes = false;
                break;
            }
        }

        // Filtro por docente
        $porDocente = $this->permisoService->obtenerSolicitudesAdmin(['id_docente' => $this->fixtureDocente1Id]);
        $todosDoc1 = true;
        foreach ($porDocente as $s) {
            if ($s->id_docente !== $this->fixtureDocente1Id) {
                $todosDoc1 = false;
                break;
            }
        }

        $isOk = $todosPendientes && $todosDoc1 && (count($pendientes) > 0) && (count($porDocente) > 0);

        $this->record(
            'T9-20',
            'Bandeja administrativa de permisos con soporte de filtros por estado y docente (RF-27)',
            'Filtros por estado=PENDIENTE y por id_docente aplicados con precisión en MySQL',
            sprintf('Pendientes: %d (100%% válidos: %s), Docente 1: %d (100%% válidos: %s)', count($pendientes), $todosPendientes ? 'SÍ' : 'NO', count($porDocente), $todosDoc1 ? 'SÍ' : 'NO'),
            $isOk
        );
    }

    // =========================================================================
    // BLOQUE 4: RESOLUCIÓN ADMINISTRATIVA Y AUDITORÍA (RF-27, RNF-06, DT-56, DT-57)
    // =========================================================================

    private function testT9_21_AprobacionAdminExitosa(): void
    {
        // Tomar la primera solicitud creada de Docente 1
        $solId = $this->createdSolicitudIds[0];

        $solicitud = $this->permisoService->aprobarSolicitud(
            $solId,
            $this->fixtureUserAdminId,
            'Aprobado conforme a documentación presentada.'
        );

        $dbRow = $this->permisoRepo->findById($solId);

        $isOk = ($solicitud->isAprobado())
            && ($dbRow !== null && $dbRow->estado === 'APROBADO')
            && !empty($dbRow->fecha_actualizacion);

        $this->record(
            'T9-21',
            'Aprobación administrativa de solicitud de permiso (RF-27, DT-56)',
            'Solicitud transiciona atómicamente a APROBADO con timestamp de actualización',
            sprintf('Estado: %s, ID: %d, Fecha act: %s', $dbRow?->estado ?? 'null', $solId, $dbRow?->fecha_actualizacion ?? 'null'),
            $isOk
        );
    }

    private function testT9_22_RechazoAdminExitoso(): void
    {
        // Tomar la segunda solicitud creada
        $solId = $this->createdSolicitudIds[1];

        $solicitud = $this->permisoService->rechazarSolicitud(
            $solId,
            $this->fixtureUserAdminId,
            'No procede por falta de cobertura pedagógica en esa fecha.'
        );

        $dbRow = $this->permisoRepo->findById($solId);

        $isOk = ($solicitud->isRechazado())
            && ($dbRow !== null && $dbRow->estado === 'RECHAZADO')
            && !empty($dbRow->fecha_actualizacion);

        $this->record(
            'T9-22',
            'Rechazo administrativo de solicitud de permiso (RF-27, DT-56)',
            'Solicitud transiciona atómicamente a RECHAZADO con justificación institucional',
            sprintf('Estado: %s, ID: %d, Fecha act: %s', $dbRow?->estado ?? 'null', $solId, $dbRow?->fecha_actualizacion ?? 'null'),
            $isOk
        );
    }

    private function testT9_23_RegistroInmutableTransicionHistorial(): void
    {
        // Verificar historial de la solicitud aprobada (T9-21)
        $solId = $this->createdSolicitudIds[0];
        $historial = $this->permisoRepo->findHistorialBySolicitudId($solId);

        $tiene2Registros = (count($historial) === 2);
        // Registro 0: PENDIENTE
        $reg0 = $historial[0];
        // Registro 1: APROBADO
        $reg1 = $historial[1];

        $isOk = $tiene2Registros
            && ($reg1->estado_anterior === 'PENDIENTE')
            && ($reg1->estado_nuevo === 'APROBADO')
            && ($reg1->id_usuario_actor === $this->fixtureUserAdminId)
            && str_contains($reg1->comentario ?? '', 'Aprobado conforme');

        $this->record(
            'T9-23',
            'Registro inmutable de transición en historial_solicitudes_permiso (RNF-06, DT-56, DT-57)',
            '2 entradas cronológicas inmutables (null -> PENDIENTE y PENDIENTE -> APROBADO con actor Admin)',
            sprintf('Total historial: %d, Último: %s -> %s, Comentario: "%s"', count($historial), $reg1->estado_anterior ?? 'none', $reg1->estado_nuevo ?? 'none', $reg1->comentario ?? ''),
            $isOk
        );
    }

    private function testT9_24_InmutabilidadEstadoTerminal(): void
    {
        // Intentar volver a rechazar o aprobar la solicitud ya aprobada en T9-21
        $solId = $this->createdSolicitudIds[0];

        $rechazoFallido = false;
        try {
            $this->permisoService->rechazarSolicitud($solId, $this->fixtureUserAdminId, 'Intento de modificar terminal');
        } catch (\DomainException $e) {
            $rechazoFallido = true;
        }

        $aprobacionFallida = false;
        try {
            $this->permisoService->aprobarSolicitud($solId, $this->fixtureUserAdminId, 'Segundo visto bueno');
        } catch (\DomainException $e) {
            $aprobacionFallida = true;
        }

        $solFinal = $this->permisoRepo->findById($solId);
        $sigueAprobada = ($solFinal !== null && $solFinal->estado === 'APROBADO');

        $isOk = $rechazoFallido && $aprobacionFallida && $sigueAprobada;

        $this->record(
            'T9-24',
            'Inmutabilidad de estado terminal (APROBADO/RECHAZADO no admite modificaciones - DT-56)',
            'DomainException al intentar alterar una solicitud ya resuelta; estado se mantiene inalterado',
            sprintf('Rechazo denegado: %s, Re-aprobación denegada: %s, Estado persistido: %s', $rechazoFallido ? 'SÍ' : 'NO', $aprobacionFallida ? 'SÍ' : 'NO', $solFinal?->estado ?? 'null'),
            $isOk
        );
    }

    private function testT9_25_RestriccionRolResolucion(): void
    {
        // Un docente autenticado intenta aprobar una solicitud pendiente
        $this->loginAsUser($this->fixtureUserDocente1Id);
        $controller = new AdminPermisoController($this->authService, $this->permisoService, $this->docenteRepo);

        // Crear una nueva solicitud pendiente
        $nuevaSol = $this->permisoService->crearSolicitud(
            $this->fixtureDocente1Id,
            $this->fixtureUserDocente1Id,
            ['fecha_solicitada' => date('Y-m-d', strtotime('+15 days')), 'tipo_motivo' => 'Motivo', 'descripcion' => 'Desc'],
            null
        );
        $this->createdSolicitudIds[] = $nuevaSol->id_solicitud_permiso;

        $request = new Request(
            'POST',
            '/administracion/permisos/aprobar',
            [],
            [
                '_token' => csrf_token(),
                'id_solicitud_permiso' => $nuevaSol->id_solicitud_permiso,
                'comentario' => 'Auto-aprobación',
            ],
            ['accept' => 'application/json', 'x-requested-with' => 'xmlhttprequest']
        );

        $response = $controller->aprobar($request);

        $solBd = $this->permisoRepo->findById($nuevaSol->id_solicitud_permiso);

        $isOk = ($response->getStatusCode() === 403)
            && ($solBd !== null && $solBd->estado === 'PENDIENTE');

        $this->record(
            'T9-25',
            'Restricción de rol: Docente no puede resolver permisos en administración (RF-27)',
            'HTTP 403 Acceso Denegado y solicitud permanece PENDIENTE',
            sprintf('HTTP %d, Estado en BD: %s', $response->getStatusCode(), $solBd?->estado ?? 'null'),
            $isOk
        );
    }

    // =========================================================================
    // BLOQUE 5: SEGURIDAD, CSRF, XSS E INMUTABILIDAD (RNF-06, DT-56)
    // =========================================================================

    private function testT9_26_ProteccionCsrfFormularios(): void
    {
        $app = App::getInstance();
        $this->loginAsUser($this->fixtureUserDocente1Id);

        // POST sin token CSRF
        $requestNoCsrf = new Request(
            'POST',
            '/docente/permisos/crear',
            [],
            [
                'fecha_solicitada' => date('Y-m-d', strtotime('+20 days')),
                'tipo_motivo' => 'Sin CSRF',
                'descripcion' => 'Intento sin token.',
            ]
        );

        $responseNoCsrf = $app->handle($requestNoCsrf);

        // POST con token manipulado
        $requestBadCsrf = new Request(
            'POST',
            '/docente/permisos/crear',
            [],
            [
                '_token' => 'token_invalido_malicioso_123',
                'fecha_solicitada' => date('Y-m-d', strtotime('+20 days')),
                'tipo_motivo' => 'Bad CSRF',
                'descripcion' => 'Intento con token falso.',
            ]
        );

        $responseBadCsrf = $app->handle($requestBadCsrf);

        $isOk = ($responseNoCsrf->getStatusCode() === 403) && ($responseBadCsrf->getStatusCode() === 403);

        $this->record(
            'T9-26',
            'Protección CSRF estricta en endpoints POST de permisos (RF-24, RF-27)',
            'HTTP 403 denegado ante peticiones sin token o con token CSRF inválido',
            sprintf('Sin token: HTTP %d, Token manipulado: HTTP %d', $responseNoCsrf->getStatusCode(), $responseBadCsrf->getStatusCode()),
            $isOk
        );
    }

    private function testT9_27_SanitizacionXssVistas(): void
    {
        // Registrar una solicitud con caracteres potencialmente peligrosos para XSS
        $xssMotivo = 'Motivo <script>alert("XSS")</script>';
        $xssDesc = 'Descripción con <img src=x onerror=alert(1)> y "comillas".';

        $solicitud = $this->permisoService->crearSolicitud(
            $this->fixtureDocente1Id,
            $this->fixtureUserDocente1Id,
            [
                'fecha_solicitada' => date('Y-m-d', strtotime('+21 days')),
                'tipo_motivo' => $xssMotivo,
                'descripcion' => $xssDesc,
            ],
            null
        );
        $this->createdSolicitudIds[] = $solicitud->id_solicitud_permiso;

        $this->loginAsUser($this->fixtureUserDocente1Id);
        $controller = new DocentePermisoController($this->authService, $this->permisoService, $this->portalDocenteService);

        // Renderizar vista show
        $request = new Request('GET', '/docente/permisos/ver', ['id' => $solicitud->id_solicitud_permiso]);
        $response = $controller->show($request);
        $html = $response->getContent();

        $sinScriptSinEscape = !str_contains($html, '<script>alert("XSS")</script>');
        $sinImgSinEscape = !str_contains($html, '<img src=x onerror=alert(1)>');
        $tieneEntidadesHtml = str_contains($html, '&lt;script&gt;') || str_contains($html, '&lt;img');

        $isOk = $sinScriptSinEscape && $sinImgSinEscape && $tieneEntidadesHtml;

        $this->record(
            'T9-27',
            'Sanitización estricta contra inyección XSS en vistas docentes y administrativas',
            'Etiquetas HTML y JavaScript escapadas mediante e() en el renderizado',
            sprintf('Sin script crudo: %s, Sin img crudo: %s, Entidades presentes: %s', $sinScriptSinEscape ? 'SÍ' : 'NO', $sinImgSinEscape ? 'SÍ' : 'NO', $tieneEntidadesHtml ? 'SÍ' : 'NO'),
            $isOk
        );
    }

    private function testT9_28_InmutabilidadMetodosRepositorioHistorial(): void
    {
        $reflection = new ReflectionClass(PermisoRepository::class);
        $methods = array_map(fn($m) => $m->getName(), $reflection->getMethods());

        // Verificar que NO existan métodos para actualizar o eliminar historial
        $hasUpdateHistorial = in_array('updateHistorial', $methods, true) || in_array('modificarHistorial', $methods, true);
        $hasDeleteHistorial = in_array('deleteHistorial', $methods, true) || in_array('eliminarHistorial', $methods, true);

        // Verificar que el repositorio expone inserción de historial mediante crearHistorial
        $hasInsertHistorial = in_array('crearHistorial', $methods, true);

        $isOk = !$hasUpdateHistorial && !$hasDeleteHistorial && $hasInsertHistorial;

        $this->record(
            'T9-28',
            'Inmutabilidad arquitectónica: Cero endpoints de modificación o borrado de historial (RNF-06, DT-56)',
            'PermisoRepository expone solo inserción de auditoría; carece de updateHistorial y deleteHistorial',
            sprintf('Insert: %s, Update prohibido: %s, Delete prohibido: %s', $hasInsertHistorial ? 'OK' : 'ERR', !$hasUpdateHistorial ? 'OK' : 'ERR', !$hasDeleteHistorial ? 'OK' : 'ERR'),
            $isOk
        );
    }

    private function testT9_29_RechazoDocenteInactivoOAnonimo(): void
    {
        // 1. Docente Inactivo
        $this->loginAsUser($this->fixtureUserDocenteInactivoId);
        $controller = new DocentePermisoController($this->authService, $this->permisoService, $this->portalDocenteService);

        $resInactivo = $controller->createForm(new Request('GET', '/docente/permisos/crear'));

        // 2. Anónimo
        Session::reset();
        $controllerAnon = new DocentePermisoController(new AuthService($this->userRepo), $this->permisoService, $this->portalDocenteService);
        $resAnon = $controllerAnon->index(new Request('GET', '/docente/permisos'));

        $isOk = ($resInactivo->getStatusCode() === 403)
            && ($resAnon->getStatusCode() === 302); // Redirige a /login

        $this->record(
            'T9-29',
            'Rechazo de registro para docentes inactivos o usuarios no autenticados (RF-24)',
            'Docente inactivo recibe HTTP 403; usuario anónimo es redirigido a /login (HTTP 302)',
            sprintf('Inactivo: HTTP %d, Anónimo: HTTP %d (Location: %s)', $resInactivo->getStatusCode(), $resAnon->getStatusCode(), $resAnon->getHeader('Location') ?? 'none'),
            $isOk
        );
    }

    private function testT9_30_IntegridadReferencialMySQL(): void
    {
        // Comprobar que no es posible insertar una solicitud con id_docente inexistente
        $falloFk = false;
        try {
            $stmt = $this->db->prepare('INSERT INTO solicitudes_permiso (id_docente, fecha_solicitada, tipo_motivo, descripcion, estado) VALUES (999999, "2026-10-15", "FK Test", "Prueba", "PENDIENTE")');
            $stmt->execute();
        } catch (\PDOException $e) {
            $falloFk = true;
        }

        $this->record(
            'T9-30',
            'Integridad referencial estricta en MySQL para solicitudes_permiso',
            'PDOException por violación de Foreign Key constraint (id_docente)',
            sprintf('Violación de FK capturada: %s', $falloFk ? 'SÍ' : 'NO'),
            $falloFk
        );
    }

    // =========================================================================
    // BLOQUE 6: CONCURRENCIA REAL, ATOMICIDAD Y FLUJOS HTTP (DT-57, DT-62)
    // =========================================================================

    private function testT9_31_ConcurrenciaRealResolucion(): void
    {
        echo "\n>>> Ejecutando prueba de concurrencia real multiproceso T9-31 (5 iteraciones reales)...\n";

        // Script auxiliar independiente para proceso competidor
        $scriptWorker = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'worker_permiso_concurrente_' . uniqid() . '.php';
        $workerCode = <<< 'PHP'
<?php
require_once %s;
use App\Core\Database;
use App\Repositories\PermisoRepository;
use App\Repositories\DocenteRepository;
use App\Services\PermisoService;

$solId = (int) $argv[1];
$adminId = (int) $argv[2];
$decision = $argv[3]; // 'aprobar' o 'rechazar'

try {
    $db = Database::getConnection();
    $repo = new PermisoRepository($db);
    $dRepo = new DocenteRepository($db);
    $service = new PermisoService($repo, $dRepo);

    if ($decision === 'aprobar') {
        $service->aprobarSolicitud($solId, $adminId, 'Aprobación concurrente');
    } else {
        $service->rechazarSolicitud($solId, $adminId, 'Rechazo concurrente');
    }
    echo json_encode(['exito' => true, 'mensaje' => 'Resolución completada', 'decision' => $decision]);
} catch (\Throwable $e) {
    echo json_encode(['exito' => false, 'error' => $e->getMessage(), 'decision' => $decision]);
}
PHP;

        $bootstrapPath = var_export(dirname(__DIR__) . '/app/bootstrap.php', true);
        file_put_contents($scriptWorker, sprintf($workerCode, $bootstrapPath));

        $filasTabla = [];
        $todasConsistentes = true;

        for ($iter = 1; $iter <= 5; $iter++) {
            // Crear solicitud pendiente para cada iteración
            $sol = $this->permisoService->crearSolicitud(
                $this->fixtureDocente1Id,
                $this->fixtureUserDocente1Id,
                [
                    'fecha_solicitada' => date('Y-m-d', strtotime('+' . (25 + $iter) . ' days')),
                    'tipo_motivo' => 'Carrera Concurrente Iter ' . $iter,
                    'descripcion' => 'Prueba de concurrencia iteración ' . $iter,
                ],
                null
            );
            $solId = $sol->id_solicitud_permiso;
            $this->createdSolicitudIds[] = $solId;

            $cmd1 = sprintf('"%s" "%s" %d %d aprobar', PHP_BINARY, $scriptWorker, $solId, $this->fixtureUserAdminId);
            $cmd2 = sprintf('"%s" "%s" %d %d rechazar', PHP_BINARY, $scriptWorker, $solId, $this->fixtureUserAdmin2Id);

            // Lanzar ambos procesos simultáneamente con pipes no bloqueantes
            $p1 = proc_open($cmd1, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes1);
            $p2 = proc_open($cmd2, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes2);

            $out1 = stream_get_contents($pipes1[1]);
            $out2 = stream_get_contents($pipes2[1]);

            fclose($pipes1[1]);
            fclose($pipes1[2]);
            fclose($pipes2[1]);
            fclose($pipes2[2]);

            proc_close($p1);
            proc_close($p2);

            $res1 = json_decode(trim($out1), true);
            $res2 = json_decode(trim($out2), true);

            $exito1 = $res1['exito'] ?? false;
            $exito2 = $res2['exito'] ?? false;

            // Determinar ganador y perdedor
            $exactamenteUnGanador = ($exito1 xor $exito2);
            $ganador = $exito1 ? 'Proceso A (aprobar)' : ($exito2 ? 'Proceso B (rechazar)' : 'NINGUNO');
            $perdedor = $exito1 ? 'Proceso B (rechazar)' : ($exito2 ? 'Proceso A (aprobar)' : 'NINGUNO');

            $solBd = $this->permisoRepo->findById($solId);
            $historial = $this->permisoRepo->findHistorialBySolicitudId($solId);

            // Historial debe tener exactamente 2 entradas (null->PENDIENTE y la resolución)
            $historialTerminalCount = count($historial) - 1; // cantidad de decisiones terminales
            $historialValido = (count($historial) === 2) && ($historialTerminalCount === 1);

            // Ninguna excepción SQL expuesta
            $sinSqlError = !str_contains($out1, 'SQLSTATE') && !str_contains($out1, 'PDOException')
                && !str_contains($out2, 'SQLSTATE') && !str_contains($out2, 'PDOException');

            $consistente = $exactamenteUnGanador && $historialValido && ($solBd->isAprobado() || $solBd->isRechazado()) && $sinSqlError;

            if (!$consistente) {
                $todasConsistentes = false;
            }

            $filasTabla[] = [
                'iteracion' => $iter,
                'ganador' => $ganador,
                'perdedor' => $perdedor,
                'estado_final' => $solBd?->estado ?? 'null',
                'historial_terminal' => $historialTerminalCount,
                'consistente' => $consistente ? 'SÍ' : 'NO',
            ];
        }

        @unlink($scriptWorker);

        // Imprimir tabla markdown de las 5 iteraciones
        echo "\n| Iteración | Ganador | Perdedor | Estado final | Historial terminal | Consistente |\n";
        echo "|---|---|---|---|---:|---|\n";
        foreach ($filasTabla as $fila) {
            printf(
                "| %d | %s | %s | %s | %d | %s |\n",
                $fila['iteracion'],
                $fila['ganador'],
                $fila['perdedor'],
                $fila['estado_final'],
                $fila['historial_terminal'],
                $fila['consistente']
            );
        }
        echo "\n";

        $this->record(
            'T9-31',
            'Concurrencia real multiproceso en resolución con bloqueo pesimista: 5 iteraciones (DT-62)',
            '5/5 iteraciones con exactamente 1 ganador, 1 perdedor, 1 estado terminal y 0 excepciones SQL',
            sprintf('5 iteraciones evaluadas. Todas consistentes: %s (100%% consistencia en MySQL)', $todasConsistentes ? 'SÍ' : 'NO'),
            $todasConsistentes
        );
    }

    private function testT9_32_AtomicidadTransaccionalRollback(): void
    {
        // Simular un fallo durante la transacción inyectando un error forzado
        $solId = end($this->createdSolicitudIds);

        // Verificar conteo antes
        $conteoAntes = (int) $this->db->query("SELECT COUNT(*) FROM historial_solicitudes_permiso WHERE id_solicitud_permiso = $solId")->fetchColumn();

        // Creamos un repositorio mock que falle en la inserción de historial
        $mockRepo = new class(Database::getConnection()) extends PermisoRepository {
            public function crearHistorial(
                int $idSolicitud,
                int $idUsuarioActor,
                ?string $estadoAnterior,
                string $estadoNuevo,
                ?string $comentario = null
            ): HistorialSolicitudPermiso {
                throw new \RuntimeException('Fallo simulado en historial para verificar rollback');
            }
        };

        $servicioConMock = new PermisoService($mockRepo, $this->docenteRepo);

        // Crear una nueva solicitud pendiente para esta prueba
        $nuevaSol = $this->permisoService->crearSolicitud(
            $this->fixtureDocente1Id,
            $this->fixtureUserDocente1Id,
            ['fecha_solicitada' => date('Y-m-d', strtotime('+35 days')), 'tipo_motivo' => 'Rollback Test', 'descripcion' => 'Desc'],
            null
        );
        $this->createdSolicitudIds[] = $nuevaSol->id_solicitud_permiso;

        $falloCapturado = false;
        try {
            $servicioConMock->aprobarSolicitud($nuevaSol->id_solicitud_permiso, $this->fixtureUserAdminId, 'Debe fallar');
        } catch (\RuntimeException $e) {
            $falloCapturado = true;
        }

        // Verificar que la solicitud NO cambió de estado en la base de datos (se hizo rollback)
        $solDespues = $this->permisoRepo->findById($nuevaSol->id_solicitud_permiso);
        $siguePendiente = ($solDespues !== null && $solDespues->estado === 'PENDIENTE');

        $isOk = $falloCapturado && $siguePendiente;

        $this->record(
            'T9-32',
            'Atomicidad transaccional y rollback ante fallo en historial (DT-57)',
            'Excepción durante la resolución ejecuta rollback completo; solicitud se preserva en PENDIENTE',
            sprintf('Excepción capturada: %s, Estado tras rollback: %s', $falloCapturado ? 'SÍ' : 'NO', $solDespues?->estado ?? 'null'),
            $isOk
        );
    }

    private function testT9_33_FlujoHttpRealDocente(): void
    {
        echo "\n>>> Iniciando servidor web PHP embebido para pruebas HTTP reales T9-33 y T9-34...\n";

        $port = 8097;
        $phpBin = PHP_BINARY;
        $docRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public';

        $serverLog = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php_server_f9_' . uniqid() . '.log';
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $serverLog, 'a'],
            2 => ['file', $serverLog, 'a'],
        ];

        $cmd = sprintf('"%s" -S 127.0.0.1:%d -t "%s"', $phpBin, $port, $docRoot);
        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));

        if (!is_resource($process)) {
            $this->record('T9-33', 'Flujo HTTP real completo del Docente', 'Servidor iniciado', 'No se pudo iniciar servidor en puerto ' . $port, false);
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
            @unlink($serverLog);
            $this->record('T9-33', 'Flujo HTTP real completo del Docente', 'Servidor conectado', 'Timeout esperando servidor HTTP', false);
            return;
        }

        $cookieDoc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_doc_f9_' . uniqid() . '.txt';

        $httpRequest = function (string $url, string $method = 'GET', $postData = null, ?string $cookie = null, array $headers = []): array {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            if ($cookie !== null) {
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
            }
            if (!empty($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if (is_array($postData)) {
                    $hasFile = false;
                    foreach ($postData as $v) {
                        if ($v instanceof CURLFile) {
                            $hasFile = true;
                            break;
                        }
                    }
                    if ($hasFile) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                    } else {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                    }
                }
            }
            $raw = curl_exec($ch);
            if ($raw === false) {
                $err = (string) curl_error($ch);
                curl_close($ch);
                return ['code' => 0, 'headers' => '', 'body' => $err, 'location' => ''];
            }
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $respHeaders = substr($raw, 0, $headerSize);
            $body = substr($raw, $headerSize);
            curl_close($ch);

            $location = '';
            if (preg_match('/Location:\s*([^\r\n]+)/i', $respHeaders, $m)) {
                $location = trim($m[1]);
            }

            return ['code' => $code, 'headers' => $respHeaders, 'body' => $body, 'location' => $location];
        };

        try {
            // 1. Login Docente
            $resLoginGet = $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'GET', null, $cookieDoc);
            $csrfToken = '';
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resLoginGet['body'], $m)) {
                $csrfToken = $m[1];
            }

            $uDoc = $this->userRepo->findById($this->fixtureUserDocente1Id);
            $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'POST', [
                '_token' => $csrfToken,
                'nombre_usuario' => $uDoc->nombre_usuario,
                'password' => 'PassFase9Test!',
            ], $cookieDoc);

            // 2. GET /docente/permisos/crear
            $resForm = $httpRequest(sprintf('http://127.0.0.1:%d/docente/permisos/crear', $port), 'GET', null, $cookieDoc);
            $tokenForm = '';
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resForm['body'], $m)) {
                $tokenForm = $m[1];
            }

            // 3. POST /docente/permisos/crear con archivo adjunto
            $tmpFile = tempnam(sys_get_temp_dir(), 'curl_f9_') . '.pdf';
            file_put_contents($tmpFile, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 3 3]>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000102 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n162\n%%EOF");

            $cfile = new CURLFile($tmpFile, 'application/pdf', 'constancia_curl.pdf');
            $postData = [
                '_token' => $tokenForm,
                'fecha_solicitada' => date('Y-m-d', strtotime('+40 days')),
                'tipo_motivo' => 'Trámite cURL Real',
                'descripcion' => 'Solicitud enviada mediante flujo HTTP real con multipart/form-data.',
                'soporte' => $cfile,
            ];

            $resPost = $httpRequest(sprintf('http://127.0.0.1:%d/docente/permisos/crear', $port), 'POST', $postData, $cookieDoc);
            @unlink($tmpFile);

            $esRedireccion = ($resPost['code'] === 302) && str_contains($resPost['location'], '/docente/permisos');

            // 4. GET /docente/permisos (verificar renderizado en tabla)
            $resIndex = $httpRequest(sprintf('http://127.0.0.1:%d/docente/permisos', $port), 'GET', null, $cookieDoc);
            $tieneSolicitud = str_contains($resIndex['body'], 'Trámite cURL Real');

            // =================================================================
            // F9-02 — Escenario A: Docente 1 solicita detalle de permiso de Docente 2
            // =================================================================
            $solDoc2 = $this->permisoService->crearSolicitud(
                $this->fixtureDocente2Id,
                $this->fixtureUserDocente2Id,
                [
                    'fecha_solicitada' => date('Y-m-d', strtotime('+42 days')),
                    'tipo_motivo' => 'Trámite Confidencial Docente 2',
                    'descripcion' => 'DetallePrivadoDocente2_NoDebeFiltrarse',
                ],
                null
            );
            $this->createdSolicitudIds[] = $solDoc2->id_solicitud_permiso;

            $resVerDoc2 = $httpRequest(sprintf('http://127.0.0.1:%d/docente/permisos/ver?id=%d', $port, $solDoc2->id_solicitud_permiso), 'GET', null, $cookieDoc);
            $escenarioA_403 = ($resVerDoc2['code'] === 403);
            $escenarioA_sinFuga = !str_contains($resVerDoc2['body'], 'DetallePrivadoDocente2_NoDebeFiltrarse')
                && !str_contains($resVerDoc2['body'], 'Trámite Confidencial Docente 2');

            // =================================================================
            // F9-02 — Escenario B: Docente 1 intenta descargar documento de Docente 2
            // =================================================================
            $tmpDoc2 = tempnam(sys_get_temp_dir(), 'curl_doc2_') . '.pdf';
            file_put_contents($tmpDoc2, "%PDF-1.4\nContenidoPrivadoDocente2\n%%EOF");
            $cfile2 = [
                'name' => 'archivo_privado_doc2.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $tmpDoc2,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($tmpDoc2),
            ];
            $solDoc2ConArchivo = $this->permisoService->crearSolicitud(
                $this->fixtureDocente2Id,
                $this->fixtureUserDocente2Id,
                [
                    'fecha_solicitada' => date('Y-m-d', strtotime('+43 days')),
                    'tipo_motivo' => 'Permiso Con Soporte Doc2',
                    'descripcion' => 'Desc soporte',
                ],
                $cfile2
            );
            @unlink($tmpDoc2);
            $idDoc2Archivo = $solDoc2ConArchivo->documento->id_documento_permiso;
            $this->createdSolicitudIds[] = $solDoc2ConArchivo->id_solicitud_permiso;
            $this->createdDocumentoIds[] = $idDoc2Archivo;
            $this->createdFilePaths[] = dirname(__DIR__) . '/' . $solDoc2ConArchivo->documento->ruta_almacenamiento;

            $resDescargaAjena = $httpRequest(sprintf('http://127.0.0.1:%d/docente/permisos/documento?id=%d', $port, $idDoc2Archivo), 'GET', null, $cookieDoc);
            $escenarioB_403 = ($resDescargaAjena['code'] === 403);
            $escenarioB_ceroBytes = !str_contains($resDescargaAjena['body'], 'ContenidoPrivadoDocente2');

            // =================================================================
            // F9-02 — Escenario C: Manipulación de parámetro id_docente en POST
            // =================================================================
            $resFormTamper = $httpRequest(sprintf('http://127.0.0.1:%d/docente/permisos/crear', $port), 'GET', null, $cookieDoc);
            $tokenFormTamper = '';
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resFormTamper['body'], $m)) {
                $tokenFormTamper = $m[1];
            }

            $resPostTamper = $httpRequest(sprintf('http://127.0.0.1:%d/docente/permisos/crear', $port), 'POST', [
                '_token' => $tokenFormTamper,
                'id_docente' => $this->fixtureDocente2Id, // Intento de inyectar Docente 2
                'fecha_solicitada' => date('Y-m-d', strtotime('+44 days')),
                'tipo_motivo' => 'Prueba Tampering id_docente HTTP Real',
                'descripcion' => 'Parámetro id_docente manipulado en POST cURL.',
            ], $cookieDoc);

            $stmtTamper = $this->db->query("SELECT id_solicitud_permiso, id_docente FROM solicitudes_permiso WHERE tipo_motivo = 'Prueba Tampering id_docente HTTP Real' ORDER BY id_solicitud_permiso DESC LIMIT 1");
            $rowTamper = $stmtTamper->fetch(PDO::FETCH_ASSOC);
            $idTamperSol = (int) ($rowTamper['id_solicitud_permiso'] ?? 0);
            if ($idTamperSol > 0) {
                $this->createdSolicitudIds[] = $idTamperSol;
            }
            $escenarioC_ok = ($resPostTamper['code'] === 302)
                && ($rowTamper !== false)
                && ((int) $rowTamper['id_docente'] === $this->fixtureDocente1Id) // Propietario forzado al usuario de la sesión
                && ((int) $rowTamper['id_docente'] !== $this->fixtureDocente2Id);

            $isOk = ($resForm['code'] === 200) && $esRedireccion && ($resIndex['code'] === 200) && $tieneSolicitud
                && $escenarioA_403 && $escenarioA_sinFuga
                && $escenarioB_403 && $escenarioB_ceroBytes
                && $escenarioC_ok;

            $this->record(
                'T9-33',
                'Flujo HTTP real completo del Docente con aislamiento horizontal y anti-tampering (RF-24, RF-25, RF-28, DT-61)',
                'GET form 200 -> POST multipart 302 -> GET listado 200 + Escenarios A (403 sin fuga), B (403 cero bytes) y C (id_docente ignorado)',
                sprintf(
                    'Form: %d, POST: %d, Index: %d, Esc A: %d (sin fuga: %s), Esc B: %d (0 bytes: %s), Esc C: 302 (id_docente BD: %d)',
                    $resForm['code'],
                    $resPost['code'],
                    $resIndex['code'],
                    $resVerDoc2['code'],
                    $escenarioA_sinFuga ? 'SÍ' : 'NO',
                    $resDescargaAjena['code'],
                    $escenarioB_ceroBytes ? 'SÍ' : 'NO',
                    $rowTamper['id_docente'] ?? 0
                ),
                $isOk
            );

            // =================================================================
            // T9-34: Flujo HTTP real del Administrador (dentro del mismo servidor activo)
            // =================================================================
            $this->testT9_34_FlujoHttpRealAdminConServidor($port, $httpRequest);

            // =================================================================
            // T9-35: Verificación de seguridad de almacenamiento en el servidor activo
            // =================================================================
            $resDirect = $httpRequest(sprintf('http://127.0.0.1:%d/storage/permisos/test.pdf', $port), 'GET');
            $codeDirect = $resDirect['code'];
            $isOkDirect = ($codeDirect === 404);

            $this->record(
                'T9-35',
                'Verificación de seguridad: Acceso directo HTTP a storage/permisos/ devuelve HTTP 404 (DT-58)',
                'Petición GET directa a /storage/permisos/... rechazada con HTTP 404 por aislamiento de webroot',
                sprintf('Código HTTP obtenido: %d (esperado: 404)', $codeDirect),
                $isOkDirect
            );

        } finally {
            $this->terminateProcess($pid, $process, $pipes);
            if (file_exists($cookieDoc)) {
                @unlink($cookieDoc);
            }
        }
    }

    private function testT9_34_FlujoHttpRealAdminConServidor(int $port, callable $httpRequest): void
    {
        $cookieAdmin = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_adm_f9_' . uniqid() . '.txt';

        try {
            // 1. Login Admin
            $resLoginGet = $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'GET', null, $cookieAdmin);
            $csrfToken = '';
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resLoginGet['body'], $m)) {
                $csrfToken = $m[1];
            }

            $uAdmin = $this->userRepo->findById($this->fixtureUserAdminId);
            $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'POST', [
                '_token' => $csrfToken,
                'nombre_usuario' => $uAdmin->nombre_usuario,
                'password' => 'PassFase9Test!',
            ], $cookieAdmin);

            // 2. GET /administracion/permisos
            $resBandeja = $httpRequest(sprintf('http://127.0.0.1:%d/administracion/permisos', $port), 'GET', null, $cookieAdmin);

            // Buscar la solicitud pendiente creada en T9-33
            $stmt = $this->db->query("SELECT id_solicitud_permiso FROM solicitudes_permiso WHERE tipo_motivo = 'Trámite cURL Real' ORDER BY id_solicitud_permiso DESC LIMIT 1");
            $solId = (int) $stmt->fetchColumn();
            if ($solId > 0) {
                $this->createdSolicitudIds[] = $solId;
            }

            // 3. GET /administracion/permisos/ver?id=...
            $resVer = $httpRequest(sprintf('http://127.0.0.1:%d/administracion/permisos/ver?id=%d', $port, $solId), 'GET', null, $cookieAdmin);
            $tokenAdmin = '';
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resVer['body'], $m)) {
                $tokenAdmin = $m[1];
            }

            // 4. POST /administracion/permisos/aprobar
            $resAprobar = $httpRequest(sprintf('http://127.0.0.1:%d/administracion/permisos/aprobar', $port), 'POST', [
                '_token' => $tokenAdmin,
                'id_solicitud_permiso' => $solId,
                'comentario' => 'Aprobado formalmente en prueba HTTP real',
            ], $cookieAdmin);

            $esRedireccion = ($resAprobar['code'] === 302);

            // 5. Verificar que en BD y en vista el estado es APROBADO
            $solBd = $this->permisoRepo->findById($solId);
            $resVerAprobado = $httpRequest(sprintf('http://127.0.0.1:%d/administracion/permisos/ver?id=%d', $port, $solId), 'GET', null, $cookieAdmin);
            $renderAprobado = str_contains($resVerAprobado['body'], 'ESTADO: APROBADO') || str_contains($resVerAprobado['body'], 'APROBADO');

            // =================================================================
            // F9-03 — Intento de segunda resolución incompatible (RECHAZAR ya estando APROBADO)
            // =================================================================
            $tokenRechazar = $tokenAdmin;
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resVerAprobado['body'], $mToken)) {
                $tokenRechazar = $mToken[1];
            }

            $resReRechazar = $httpRequest(sprintf('http://127.0.0.1:%d/administracion/permisos/rechazar', $port), 'POST', [
                '_token' => $tokenRechazar,
                'id_solicitud_permiso' => $solId,
                'comentario' => 'Segundo intento incompatible de rechazo sobre solicitud ya resuelta',
            ], $cookieAdmin);

            $codigoSegundo = $resReRechazar['code'];
            $locSegundo = $resReRechazar['location'];
            $redireccionControlada = ($codigoSegundo === 302) && str_contains($locSegundo, 'error=');

            // Consultar el detalle tras el intento de modificación inválida
            $resDetalleTrasSegundo = $httpRequest(sprintf('http://127.0.0.1:%d%s', $port, $locSegundo !== '' ? $locSegundo : ('/administracion/permisos/ver?id=' . $solId)), 'GET', null, $cookieAdmin);

            // Validar que no hay trazas de SQL ni excepciones PHP expuestas
            $sinFugaSql = !str_contains($resReRechazar['body'], 'SQLSTATE')
                && !str_contains($resReRechazar['body'], 'PDOException')
                && !str_contains($resDetalleTrasSegundo['body'], 'SQLSTATE')
                && !str_contains($resDetalleTrasSegundo['body'], 'PDOException')
                && !str_contains($resDetalleTrasSegundo['body'], 'Fatal error');

            // Validar inalterabilidad en base de datos
            $solBdFinal = $this->permisoRepo->findById($solId);
            $estadoInalterado = ($solBdFinal !== null && $solBdFinal->estado === 'APROBADO');

            // Validar conteo de historial terminal (exactamente 1 resolución terminal en BD)
            $historialFinal = $this->permisoRepo->findHistorialBySolicitudId($solId);
            $conteoTerminal = 0;
            foreach ($historialFinal as $h) {
                if (in_array($h->estado_nuevo, ['APROBADO', 'RECHAZADO'], true)) {
                    $conteoTerminal++;
                }
            }
            $unSoloHistorialTerminal = ($conteoTerminal === 1);

            $isOk = ($resBandeja['code'] === 200) && ($resVer['code'] === 200) && $esRedireccion && ($solBd?->isAprobado()) && $renderAprobado
                && $redireccionControlada && $sinFugaSql && $estadoInalterado && $unSoloHistorialTerminal;

            $this->record(
                'T9-34',
                'Flujo HTTP real Administrador: Bandeja, vista, resolución y re-resolución denegada (RF-27, RF-28, DT-56, DT-62)',
                'GET bandeja 200 -> GET detalle 200 -> POST aprobar 302 -> POST rechazar 302 con error controlado (cero SQLSTATE, BD inalterada)',
                sprintf(
                    'Bandeja: %d, Aprobación: %d, Re-resolución: %d (Loc: %s), BD Final: %s, Historial terminal: %d, Fuga SQL: %s',
                    $resBandeja['code'],
                    $resAprobar['code'],
                    $codigoSegundo,
                    substr($locSegundo, 0, 45) . '...',
                    $solBdFinal?->estado ?? 'null',
                    $conteoTerminal,
                    !$sinFugaSql ? 'SÍ' : 'NO'
                ),
                $isOk
            );
        } finally {
            if (file_exists($cookieAdmin)) {
                @unlink($cookieAdmin);
            }
        }
    }

    private function testT9_34_FlujoHttpRealAdmin(): void
    {
        // Ejecutado dentro de testT9_33_FlujoHttpRealDocente
    }

    private function testT9_35_SeguridadAlmacenamientoHttpDirecto(): void
    {
        // Ejecutado dentro de testT9_33_FlujoHttpRealDocente
    }

    private function testT9_36_ControlAlcanceNotificacionesFase10(): void
    {
        // Verificar que durante toda la Fase 9 la tabla notificaciones NO ha sido modificada funcionalmente
        // y que RF-26 se mantiene estrictamente en alcance de Fase 10
        $countFinal = (int) $this->db->query("SELECT COUNT(*) FROM notificaciones")->fetchColumn();
        $nuevasNotificaciones = $countFinal - $this->initialNotificacionesCount;

        // Comprobación adicional: ninguna solicitud de Fase 9 tiene fila en notificaciones
        $countSolicitudesFase9 = 0;
        if (!empty($this->createdSolicitudIds)) {
            $in = implode(',', array_map('intval', $this->createdSolicitudIds));
            $countSolicitudesFase9 = (int) $this->db->query("SELECT COUNT(*) FROM notificaciones WHERE id_solicitud_permiso IN ($in)")->fetchColumn();
        }

        $isOk = ($nuevasNotificaciones === 0) && ($countSolicitudesFase9 === 0);

        $this->record(
            'T9-36',
            'Control estricto de alcance de Fase 10: Cero notificaciones creadas (RF-26 PENDIENTE FASE 10)',
            'Cero nuevas notificaciones generadas en Fase 9 y cero referencias a solicitudes creadas',
            sprintf('Nuevas notificaciones: %d (esperado: 0), Vinculadas a solicitudes Fase 9: %d (esperado: 0)', $nuevasNotificaciones, $countSolicitudesFase9),
            $isOk
        );
    }

    // =========================================================================
    // BLOQUE 7: REGRESIONES COMPLETAS DE FASES 1 A 8
    // =========================================================================

    private function testT9_37_RegresionFase8(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 8 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase8.php');
        $output = (string) @shell_exec($cmd);

        $matched = preg_match('/Total ejecutadas\s*:\s*40\s*Exitosas\s*\(OK\)\s*:\s*40\s*Fallidas\s*\(FALLO\)\s*:\s*0/i', $output);
        $isOk = $matched && !str_contains($output, '[FALLO]');

        $this->record(
            'T9-37',
            'Regresión completa de Fase 8 (Asistencia Docente y Geolocalización)',
            '40/40 pruebas superadas con 0 fallos',
            $isOk ? '40/40 pruebas exitosas de Fase 8' : 'Fallo en regresión de Fase 8: ' . substr($output, -250),
            $isOk
        );
    }

    private function testT9_38_RegresionFase7(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 7 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase7.php');
        $output = (string) @shell_exec($cmd);

        $matched = preg_match('/Total ejecutadas\s*:\s*28\s*Exitosas\s*\(OK\)\s*:\s*28\s*Fallidas\s*\(FALLO\)\s*:\s*0/i', $output);
        $isOk = $matched && !str_contains($output, '[FALLO]');

        $this->record(
            'T9-38',
            'Regresión completa de Fase 7 (Portal Docente)',
            '28/28 pruebas superadas con 0 fallos',
            $isOk ? '28/28 pruebas exitosas de Fase 7' : 'Fallo en regresión de Fase 7: ' . substr($output, -250),
            $isOk
        );
    }

    private function testT9_39_RegresionFase6(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 6 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase6.php');
        $output = (string) @shell_exec($cmd);

        $matched = preg_match('/Total ejecutadas\s*:\s*30\s*Exitosas\s*\(OK\)\s*:\s*30\s*Fallidas\s*\(FALLO\)\s*:\s*0/i', $output);
        $isOk = $matched && !str_contains($output, '[FALLO]');

        $this->record(
            'T9-39',
            'Regresión completa de Fase 6 (Inscripciones y Cupos)',
            '30/30 pruebas superadas con 0 fallos',
            $isOk ? '30/30 pruebas exitosas de Fase 6' : 'Fallo en regresión de Fase 6: ' . substr($output, -250),
            $isOk
        );
    }

    private function testT9_40_RegresionFase5(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 5 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase5.php');
        $output = (string) @shell_exec($cmd);

        $matched = preg_match('/Total ejecutadas\s*:\s*26\s*Exitosas\s*\(OK\)\s*:\s*26\s*Fallidas\s*\(FALLO\)\s*:\s*0/i', $output);
        $isOk = $matched && !str_contains($output, '[FALLO]');

        $this->record(
            'T9-40',
            'Regresión completa de Fase 5 (Asignaciones Docentes)',
            '26/26 pruebas superadas con 0 fallos',
            $isOk ? '26/26 pruebas exitosas de Fase 5' : 'Fallo en regresión de Fase 5: ' . substr($output, -250),
            $isOk
        );
    }

    private function testT9_41_RegresionFase4(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 4 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase4.php');
        $output = (string) @shell_exec($cmd);

        $matched = preg_match('/Total ejecutadas\s*:\s*27\s*Exitosas\s*\(OK\)\s*:\s*27\s*Fallidas\s*\(FALLO\)\s*:\s*0/i', $output);
        $isOk = $matched && !str_contains($output, '[FALLO]');

        $this->record(
            'T9-41',
            'Regresión completa de Fase 4 (Administración Básica)',
            '27/27 pruebas superadas con 0 fallos',
            $isOk ? '27/27 pruebas exitosas de Fase 4' : 'Fallo en regresión de Fase 4: ' . substr($output, -250),
            $isOk
        );
    }

    private function testT9_42_RegresionFase3(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 3 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase3.php');
        $output = (string) @shell_exec($cmd);

        $matched = preg_match('/Total ejecutadas\s*:\s*19\s*Exitosas\s*\(OK\)\s*:\s*19\s*Fallidas\s*\(FALLO\)\s*:\s*0/i', $output);
        $isOk = $matched && !str_contains($output, '[FALLO]');

        $this->record(
            'T9-42',
            'Regresión completa de Fase 3 (Autenticación y Roles)',
            '19/19 pruebas superadas con 0 fallos',
            $isOk ? '19/19 pruebas exitosas de Fase 3' : 'Fallo en regresión de Fase 3: ' . substr($output, -250),
            $isOk
        );
    }

    private function testT9_43_RegresionFase2(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 2 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests.php');
        $output = (string) @shell_exec($cmd);

        $isOk = str_contains($output, 'TODAS LAS PRUEBAS DE FASE 2 SUPERADAS SATISFACTORIAMENTE.')
            && str_contains($output, 'Total ejecutadas : 16')
            && str_contains($output, 'Fallidas (FALLO) : 0');

        $this->record(
            'T9-43',
            'Regresión completa de Fase 2 (Arquitectura Base y Enrutamiento)',
            '16/16 pruebas de Fase 2 superadas con 0 fallos',
            $isOk ? '16/16 pruebas exitosas de Fase 2' : 'Fallo en regresión de Fase 2: ' . substr($output, -250),
            $isOk
        );
    }

    private function testT9_44_RegresionFase1(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 1 en base de datos aislada...\n";
        $root = dirname(__DIR__);
        $mysqlBinary = $this->findMysqlBinary();

        if ($mysqlBinary === null) {
            $this->record(
                'T9-44',
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
        $tempDb = 'escuela_fase9_regresion_temp';

        $schemaFile = str_replace('/', '\\', $root . '/database/schema.sql');
        $seedFile = str_replace('/', '\\', $root . '/database/seed.sql');
        $valFile = str_replace('/', '\\', $root . '/database/tests/validacion.sql');

        $baseCmd = sprintf(
            '"%s" -h %s -P %d -u %s',
            $mysqlBinary,
            $dbHost,
            $dbPort,
            $dbUser
        );
        if ($dbPass !== '') {
            $baseCmd .= ' -p"' . str_replace('"', '\"', $dbPass) . '"';
        }

        $isOk = false;
        $obtained = '';

        try {
            shell_exec($baseCmd . ' -e "DROP DATABASE IF EXISTS ' . $tempDb . ';" 2>&1');
            shell_exec($baseCmd . ' -e "CREATE DATABASE ' . $tempDb . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1');
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
            'T9-44',
            'Regresión completa de Fase 1 (Diseño de Base de Datos)',
            '18/18 pruebas de integridad relacional superadas',
            $obtained,
            $isOk
        );
    }

    // =========================================================================
    // BLOQUE 8: PRUEBAS COMPLEMENTARIAS F9-04 Y F9-05 (DT-63, RF-27)
    // =========================================================================

    private function testT9_45_NombresFisicosAleatoriosPorCarga(): void
    {
        $bytes = "%PDF-1.4\nIdenticalContentForTestingRandomBytes32\n%%EOF";
        $tmp1 = tempnam(sys_get_temp_dir(), 'rand_a_') . '.pdf';
        $tmp2 = tempnam(sys_get_temp_dir(), 'rand_b_') . '.pdf';
        file_put_contents($tmp1, $bytes);
        file_put_contents($tmp2, $bytes);

        $sol1 = $this->permisoService->crearSolicitud(
            $this->fixtureDocente1Id,
            $this->fixtureUserDocente1Id,
            [
                'fecha_solicitada' => date('Y-m-d', strtotime('+50 days')),
                'tipo_motivo' => 'Carga Archivo Aleatorio A',
                'descripcion' => 'Prueba de nombres físicos aleatorios A',
            ],
            [
                'name' => 'constancia_identica.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $tmp1,
                'error' => UPLOAD_ERR_OK,
                'size' => strlen($bytes),
            ]
        );
        $sol2 = $this->permisoService->crearSolicitud(
            $this->fixtureDocente1Id,
            $this->fixtureUserDocente1Id,
            [
                'fecha_solicitada' => date('Y-m-d', strtotime('+51 days')),
                'tipo_motivo' => 'Carga Archivo Aleatorio B',
                'descripcion' => 'Prueba de nombres físicos aleatorios B',
            ],
            [
                'name' => 'constancia_identica.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $tmp2,
                'error' => UPLOAD_ERR_OK,
                'size' => strlen($bytes),
            ]
        );

        @unlink($tmp1);
        @unlink($tmp2);

        $this->createdSolicitudIds[] = $sol1->id_solicitud_permiso;
        $this->createdSolicitudIds[] = $sol2->id_solicitud_permiso;

        $doc1 = $this->permisoRepo->findDocumentoBySolicitudId($sol1->id_solicitud_permiso);
        $doc2 = $this->permisoRepo->findDocumentoBySolicitudId($sol2->id_solicitud_permiso);

        if ($doc1) {
            $this->createdDocumentoIds[] = $doc1->id_documento_permiso;
            $this->createdFilePaths[] = dirname(__DIR__) . '/' . $doc1->ruta_almacenamiento;
        }
        if ($doc2) {
            $this->createdDocumentoIds[] = $doc2->id_documento_permiso;
            $this->createdFilePaths[] = dirname(__DIR__) . '/' . $doc2->ruta_almacenamiento;
        }

        $nombre1 = basename($doc1?->ruta_almacenamiento ?? '');
        $nombre2 = basename($doc2?->ruta_almacenamiento ?? '');

        // 1. nombre_almacenado A != nombre_almacenado B
        $distintos = ($nombre1 !== '') && ($nombre2 !== '') && ($nombre1 !== $nombre2);

        // 2. Ninguno utiliza el nombre original del cliente como ruta
        $ningunoOriginal = !str_contains($nombre1, 'constancia_identica')
            && !str_contains($nombre2, 'constancia_identica');

        // 3. Ambos residen en storage/permisos/ fuera del webroot
        $rutaRelativa1 = $doc1?->ruta_almacenamiento ?? '';
        $rutaRelativa2 = $doc2?->ruta_almacenamiento ?? '';
        $enStorage = str_starts_with($rutaRelativa1, 'storage/permisos/')
            && str_starts_with($rutaRelativa2, 'storage/permisos/');

        $publicDir = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public');
        $rutaFisica1 = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . $rutaRelativa1);
        $rutaFisica2 = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . $rutaRelativa2);

        $fueraPublic = ($publicDir !== false)
            && ($rutaFisica1 !== false && !str_starts_with($rutaFisica1, $publicDir))
            && ($rutaFisica2 !== false && !str_starts_with($rutaFisica2, $publicDir));

        $isOk = $distintos && $ningunoOriginal && $enStorage && $fueraPublic;

        $this->record(
            'T9-45',
            'Nombres físicos aleatorios independientes por carga (F9-05, DT-63)',
            'Archivos idénticos reciben nombres físicos distintos con random_bytes(32) en storage/permisos/ fuera de public/',
            sprintf('Doc 1: %s | Doc 2: %s | Distintos: %s | Ninguno original: %s | Fuera de public: %s', $nombre1, $nombre2, $distintos ? 'SÍ' : 'NO', $ningunoOriginal ? 'SÍ' : 'NO', $fueraPublic ? 'SÍ' : 'NO'),
            $isOk
        );
    }

    private function testT9_46_RechazoAdminConObservacionOpcional(): void
    {
        // Crear solicitud pendiente para rechazo sin observación
        $sol = $this->permisoService->crearSolicitud(
            $this->fixtureDocente1Id,
            $this->fixtureUserDocente1Id,
            [
                'fecha_solicitada' => date('Y-m-d', strtotime('+55 days')),
                'tipo_motivo' => 'Asunto Sin Justificación Admin',
                'descripcion' => 'Solicitud pendiente para validar rechazo administrativo con observacion opcional.',
            ],
            null
        );
        $this->createdSolicitudIds[] = $sol->id_solicitud_permiso;

        // Rechazar con comentario NULL (sin observación institucional obligatoria)
        $solicitudRechazada = $this->permisoService->rechazarSolicitud($sol->id_solicitud_permiso, $this->fixtureUserAdminId, null);

        $solBd = $this->permisoRepo->findById($sol->id_solicitud_permiso);
        $historial = $this->permisoRepo->findHistorialBySolicitudId($sol->id_solicitud_permiso);
        $ultimoHistorial = end($historial);

        // Rechazo exitoso, estado = RECHAZADO, observacion en historial persiste NULL
        $isOk = ($solicitudRechazada->isRechazado())
            && ($solBd !== null && $solBd->estado === 'RECHAZADO')
            && ($ultimoHistorial !== false)
            && ($ultimoHistorial->estado_nuevo === 'RECHAZADO')
            && ($ultimoHistorial->comentario === null);

        $this->record(
            'T9-46',
            'Rechazo administrativo con observación opcional (null/vacía) sin restricción artificial (F9-04, RF-27)',
            'Solicitud transiciona a RECHAZADO y observación en historial persiste NULL sin error ni longitud mínima',
            sprintf('Estado: %s, Historial estado: %s, Observación persistida: %s', $solBd?->estado ?? 'null', $ultimoHistorial ? $ultimoHistorial->estado_nuevo : 'none', var_export($ultimoHistorial ? $ultimoHistorial->comentario : 'none', true)),
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

    private function printSummary(): void
    {
        echo "\n============================================================\n";
        echo "RESUMEN DE PRUEBAS DE FASE 9: GESTIÓN DE PERMISOS DOCENTES\n";
        echo "============================================================\n";
        printf("Total ejecutadas : %d\n", $this->total);
        printf("Exitosas (OK)    : %d\n", $this->passed);
        printf("Fallidas (FALLO) : %d\n", $this->failed);
        echo "============================================================\n";

        if ($this->failed === 0) {
            echo "RESULTADO: TODAS LAS PRUEBAS DE FASE 9 SUPERADAS SATISFACTORIAMENTE (100% GREEN).\n";
        } else {
            echo "RESULTADO: EXISTEN PRUEBAS FALLIDAS.\n";
            exit(1);
        }
    }
}

$suite = new Phase9TestSuite();
$suite->run();
