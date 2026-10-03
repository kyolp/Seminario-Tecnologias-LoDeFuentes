<?php

declare(strict_types=1);

/**
 * Suite de pruebas automatizadas para Fase 8 — Asistencia Docente y Geolocalización.
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Cobertura requerida:
 * T8-01 a T8-28: Pruebas unitarias, funcionales, de seguridad y aislamiento de asistencia/geolocalización.
 * T8-29: Flujo HTTP real completo mediante servidor web PHP embebido y cURL.
 * T8-30: Verificación de seguridad: Parámetros del cliente maliciosos ignorados (cálculo exclusivo en backend).
 * T8-31: Regresión completa de Fase 7 (Portal Docente: 28/28).
 * T8-32: Regresión completa de Fase 6 (Inscripciones y Cupos: 30/30).
 * T8-33: Regresión completa de Fase 5 (Asignaciones Docentes: 26/26).
 * T8-34: Regresión completa de Fase 4 (Administración Básica: 27/27).
 * T8-35: Regresión completa de Fase 3 (Autenticación y Roles: 19/19).
 * T8-36: Regresión completa de Fase 2 (Arquitectura Base: 16/16).
 * T8-37: Regresión completa de Fase 1 (Base de Datos: 18/18).
 * T8-38: Protección CSRF real en marcajes de asistencia (F8-01).
 * T8-39: Manejo de errores de Geolocation API (F8-02).
 * T8-40: Validación estricta de coordenadas recibidas en marcaje (F8-03).
 * Total: 40 pruebas automatizadas.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\AdminUbicacionController;
use App\Controllers\DocenteAsistenciaController;
use App\Core\App;
use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\ConfiguracionUbicacion;
use App\Models\MarcajeAsistencia;
use App\Models\User;
use App\Repositories\AsistenciaRepository;
use App\Repositories\DocenteRepository;
use App\Repositories\UbicacionRepository;
use App\Repositories\UserRepository;
use App\Services\AsistenciaService;
use App\Services\AuthService;
use App\Services\PortalDocenteService;
use App\Services\UbicacionService;

class Phase8TestSuite
{
    private PDO $db;
    private UserRepository $userRepo;
    private DocenteRepository $docenteRepo;
    private UbicacionRepository $ubicacionRepo;
    private AsistenciaRepository $asistenciaRepo;

    private AuthService $authService;
    private UbicacionService $ubicacionService;
    private AsistenciaService $asistenciaService;
    private PortalDocenteService $portalDocenteService;

    private int $total = 0;
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];

    // Fixtures
    private ?int $fixtureUserAdminId = null;
    private ?int $fixtureUserDocente1Id = null;
    private ?int $fixtureDocente1Id = null;
    private ?int $fixtureUserDocente2Id = null;
    private ?int $fixtureDocente2Id = null;
    private ?int $fixtureUserDocenteInactivoId = null;
    private ?int $fixtureDocenteInactivoId = null;

    private ?int $fixtureConfigUbicacionId = null;
    private array $createdConfigIds = [];
    private array $createdMarcajeIds = [];

    // Coordenadas oficiales de prueba (Escuela Lo de Fuentes)
    public const ESCUELA_LAT = 14.6349150;
    public const ESCUELA_LON = -90.5068820;
    public const ESCUELA_RADIO = 100.0; // 100 metros

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->userRepo = new UserRepository($this->db);
        $this->docenteRepo = new DocenteRepository($this->db);
        $this->ubicacionRepo = new UbicacionRepository($this->db);
        $this->asistenciaRepo = new AsistenciaRepository($this->db);

        $this->authService = new AuthService($this->userRepo);
        $this->ubicacionService = new UbicacionService($this->ubicacionRepo, $this->userRepo);
        $this->asistenciaService = new AsistenciaService($this->asistenciaRepo, $this->ubicacionRepo, $this->docenteRepo);
        $this->portalDocenteService = new PortalDocenteService();
    }

    public function run(): void
    {
        echo "============================================================\n";
        echo "INICIANDO SUITE DE PRUEBAS DE FASE 8: ASISTENCIA Y GEOLOCALIZACIÓN\n";
        echo "============================================================\n\n";

        try {
            $this->cleanPreviousArtifacts();
            $this->setupFixtures();

            // Bloque 1: Configuración de Ubicación Institucional (RF-18)
            $this->testT8_01_ConfiguracionUbicacionValida();
            $this->testT8_02_TransaccionalidadNuevaConfiguracionActiva();
            $this->testT8_03_RechazoRadioInvalido();
            $this->testT8_04_RechazoLatitudInvalida();
            $this->testT8_05_RechazoLongitudInvalida();
            $this->testT8_06_RechazoDescripcionInvalida();
            $this->testT8_07_ControlAccesoAdminUbicacion();
            $this->testT8_08_UnicidadCondicionalConfiguracionActiva();

            // Bloque 2: Validación Geográfica y Haversine (RF-19)
            $this->testT8_09_CalculoHaversineMatematico();
            $this->testT8_10_MarcajeDentroRadioAceptado();
            $this->testT8_11_MarcajeFronteraExactaRadio();
            $this->testT8_12_MarcajeFueraRadioRechazado();
            $this->testT8_13_RechazoSinConfiguracionActiva();

            // Bloque 3: Marcajes de Entrada y Salida (RF-20, RF-21, RF-22)
            $this->testT8_14_MarcajeEntradaDocente();
            $this->testT8_15_RechazoDobleEntradaConsecutiva();
            $this->testT8_16_MarcajeSalidaDocente();
            $this->testT8_17_RechazoSalidaSinEntradaPrevia();
            $this->testT8_18_RechazoDobleSalidaConsecutiva();
            $this->testT8_19_SecuenciaMarcajeCompletaAlternada();
            $this->testT8_20_FechaHoraAutoritativaServidor();

            // Bloque 4: Reglas de Seguridad, Roles y Aislamiento (RF-23, RNF-06)
            $this->testT8_21_RechazoDocenteInactivo();
            $this->testT8_22_RechazoAdminMarcajeDocente();
            $this->testT8_23_RechazoAnonimoMarcaje();
            $this->testT8_24_AislamientoHorizontalHistorial();
            $this->testT8_25_HistorialOrdenCronologicoDescendente();
            $this->testT8_26_InmutabilidadMarcajesAsistencia();
            $this->testT8_27_IntegridadConstraintDentroRadio();
            $this->testT8_28_SanitizacionXssVistas();

            // Bloque 5: Pruebas HTTP Reales y Parámetros Maliciosos
            $this->testT8_29_FlujoHttpRealCompleto();
            $this->testT8_30_ParametrosMaliciososClienteIgnorados();

            // Bloque 6: Regresiones de Fases Anteriores
            $this->testT8_31_RegresionFase7();
            $this->testT8_32_RegresionFase6();
            $this->testT8_33_RegresionFase5();
            $this->testT8_34_RegresionFase4();
            $this->testT8_35_RegresionFase3();
            $this->testT8_36_RegresionFase2();
            $this->testT8_37_RegresionFase1();

            // Bloque 7: Observaciones de Corrección Fase 8 (F8-01, F8-02, F8-03)
            $this->testT8_38_ProteccionCsrfReal();
            $this->testT8_39_ManejoErroresGeolocationApi();
            $this->testT8_40_ValidacionEstrictaCoordenadas();

        } finally {
            $this->cleanup();
        }

        $this->printSummary();
    }

    private function setupFixtures(): void
    {
        // 1. Rol ADMINISTRADOR / ADMINISTRACION
        $stmtRol = $this->db->query("SELECT id_rol FROM roles WHERE nombre IN ('ADMINISTRACION', 'ADMINISTRADOR') LIMIT 1");
        $rolAdminId = (int) $stmtRol->fetchColumn();

        // Rol DOCENTE
        $stmtRolDoc = $this->db->query("SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'");
        $rolDocId = (int) $stmtRolDoc->fetchColumn();

        $passHash = password_hash('PassFase8Test!', PASSWORD_BCRYPT);

        // 2. Usuario Administrador Fixture
        $stmt = $this->db->prepare('INSERT INTO usuarios (id_rol, nombre_usuario, correo, clave_hash, nombres, apellidos, estado) VALUES (:r, :u, :c, :p, :n, :a, "ACTIVO")');
        $uAdmin = 'adm_f8_' . rand(1000, 9999);
        $stmt->execute([':r' => $rolAdminId, ':u' => $uAdmin, ':c' => $uAdmin . '@escuela.test', ':p' => $passHash, ':n' => 'Admin', ':a' => 'Fase 8']);
        $this->fixtureUserAdminId = (int) $this->db->lastInsertId();

        // 3. Usuario Docente 1 (Activo)
        $uDoc1 = 'doc1_f8_' . rand(1000, 9999);
        $stmt->execute([':r' => $rolDocId, ':u' => $uDoc1, ':c' => $uDoc1 . '@escuela.test', ':p' => $passHash, ':n' => 'Claudia', ':a' => 'Morales Ruiz']);
        $this->fixtureUserDocente1Id = (int) $this->db->lastInsertId();

        $stmtDoc = $this->db->prepare('INSERT INTO docentes (id_usuario, codigo_docente, fecha_ingreso, estado) VALUES (:u, :cod, "2024-01-15", "ACTIVO")');
        $cDoc1 = 'DOC-F8-01-' . rand(100, 999);
        $stmtDoc->execute([':u' => $this->fixtureUserDocente1Id, ':cod' => $cDoc1]);
        $this->fixtureDocente1Id = (int) $this->db->lastInsertId();

        // 4. Usuario Docente 2 (Activo, para aislamiento)
        $uDoc2 = 'doc2_f8_' . rand(1000, 9999);
        $stmt->execute([':r' => $rolDocId, ':u' => $uDoc2, ':c' => $uDoc2 . '@escuela.test', ':p' => $passHash, ':n' => 'Fernando', ':a' => 'Pérez Ramos']);
        $this->fixtureUserDocente2Id = (int) $this->db->lastInsertId();

        $cDoc2 = 'DOC-F8-02-' . rand(100, 999);
        $stmtDoc->execute([':u' => $this->fixtureUserDocente2Id, ':cod' => $cDoc2]);
        $this->fixtureDocente2Id = (int) $this->db->lastInsertId();

        // 5. Usuario Docente Inactivo
        $uDocInac = 'doc_inac_f8_' . rand(1000, 9999);
        $stmt->execute([':r' => $rolDocId, ':u' => $uDocInac, ':c' => $uDocInac . '@escuela.test', ':p' => $passHash, ':n' => 'Inactivo', ':a' => 'Docente']);
        $this->fixtureUserDocenteInactivoId = (int) $this->db->lastInsertId();

        $cDocInac = 'DOC-F8-INAC-' . rand(100, 999);
        $stmtDocInac = $this->db->prepare('INSERT INTO docentes (id_usuario, codigo_docente, fecha_ingreso, estado) VALUES (:u, :cod, "2024-01-15", "INACTIVO")');
        $stmtDocInac->execute([':u' => $this->fixtureUserDocenteInactivoId, ':cod' => $cDocInac]);
        $this->fixtureDocenteInactivoId = (int) $this->db->lastInsertId();

        // 6. Configuración institucional activa de prueba
        // Desactivar temporalmente cualquier previa
        $this->db->exec('UPDATE configuraciones_ubicacion SET activa = 0, fecha_fin = CURRENT_TIMESTAMP(6) WHERE activa = 1');

        $stmtCfg = $this->db->prepare('INSERT INTO configuraciones_ubicacion (id_usuario_creador, descripcion, latitud, longitud, radio_metros, fecha_inicio, activa) VALUES (:u, :d, :lat, :lon, :rad, CURRENT_TIMESTAMP(6), 1)');
        $stmtCfg->execute([
            ':u' => $this->fixtureUserAdminId,
            ':d' => 'Escuela Oficial Rural Mixta Lo de Fuentes - Sede Central',
            ':lat' => self::ESCUELA_LAT,
            ':lon' => self::ESCUELA_LON,
            ':rad' => self::ESCUELA_RADIO,
        ]);
        $this->fixtureConfigUbicacionId = (int) $this->db->lastInsertId();
        $this->createdConfigIds[] = $this->fixtureConfigUbicacionId;
    }

    private function cleanPreviousArtifacts(): void
    {
        Session::reset();
    }

    private function cleanup(): void
    {
        Session::reset();

        try {
            // Eliminar marcajes de prueba creados
            if (!empty($this->createdMarcajeIds)) {
                $in = implode(',', array_map('intval', $this->createdMarcajeIds));
                $this->db->exec("DELETE FROM marcajes_asistencia WHERE id_marcaje_asistencia IN ($in)");
            }
            if ($this->fixtureDocente1Id && $this->fixtureDocente2Id) {
                $this->db->exec("DELETE FROM marcajes_asistencia WHERE id_docente IN ({$this->fixtureDocente1Id}, {$this->fixtureDocente2Id})");
            }

            // Eliminar configuraciones de prueba
            if (!empty($this->createdConfigIds)) {
                $in = implode(',', array_map('intval', $this->createdConfigIds));
                $this->db->exec("DELETE FROM configuraciones_ubicacion WHERE id_configuracion_ubicacion IN ($in)");
            }

            // Eliminar docentes
            if ($this->fixtureDocente1Id || $this->fixtureDocente2Id || $this->fixtureDocenteInactivoId) {
                $dIds = array_filter([$this->fixtureDocente1Id, $this->fixtureDocente2Id, $this->fixtureDocenteInactivoId]);
                $in = implode(',', $dIds);
                $this->db->exec("DELETE FROM docentes WHERE id_docente IN ($in)");
            }

            // Eliminar usuarios
            if ($this->fixtureUserAdminId || $this->fixtureUserDocente1Id || $this->fixtureUserDocente2Id || $this->fixtureUserDocenteInactivoId) {
                $uIds = array_filter([$this->fixtureUserAdminId, $this->fixtureUserDocente1Id, $this->fixtureUserDocente2Id, $this->fixtureUserDocenteInactivoId]);
                $in = implode(',', $uIds);
                $this->db->exec("DELETE FROM usuarios WHERE id_usuario IN ($in)");
            }
        } catch (\Throwable $e) {
            // Silencioso en cleanup
        }
    }

    private function loginAsUser(int $idUsuario): void
    {
        Session::start();
        Session::set('auth_user_id', $idUsuario);
    }

    private function record(string $code, string $description, string $expected, string $obtained, bool $passed): void
    {
        $this->total++;
        if ($passed) {
            $this->passed++;
            $badge = '[OK]';
        } else {
            $this->failed++;
            $badge = '[FALLO]';
        }

        $this->results[] = [
            'code' => $code,
            'description' => $description,
            'expected' => $expected,
            'obtained' => $obtained,
            'passed' => $passed,
        ];

        printf("%s %s — %s\n", $badge, $code, $description);
        if (!$passed) {
            printf("   Esperado: %s\n", $expected);
            printf("   Obtenido: %s\n", $obtained);
        }
    }

    // =========================================================================
    // BLOQUE 1: CONFIGURACIÓN DE UBICACIÓN INSTITUCIONAL (RF-18)
    // =========================================================================

    /**
     * T8-01: Administrador configura ubicación institucional válida.
     */
    private function testT8_01_ConfiguracionUbicacionValida(): void
    {
        $cfg = $this->ubicacionService->configurarNuevaUbicacion($this->fixtureUserAdminId, [
            'descripcion' => 'Campus Central Nueva Geocerca',
            'latitud' => 14.6349200,
            'longitud' => -90.5068900,
            'radio_metros' => 120.0,
        ]);
        $this->createdConfigIds[] = $cfg->id_configuracion_ubicacion;

        $activa = $this->ubicacionRepo->findConfiguracionActiva();
        $isOk = ($activa !== null && $activa->id_configuracion_ubicacion === $cfg->id_configuracion_ubicacion && $activa->radio_metros === 120.0);

        $this->record(
            'T8-01',
            'Configuración institucional de ubicación válida por administrador (RF-18)',
            'Nueva configuración activa con radio 120.0m y latitud/longitud precisas',
            $activa ? sprintf('ID %d activa, radio %.1fm, desc: %s', $activa->id_configuracion_ubicacion, $activa->radio_metros, $activa->descripcion) : 'Null',
            $isOk
        );
    }

    /**
     * T8-02: Transaccionalidad al crear nueva configuración activa: desactiva la previa.
     */
    private function testT8_02_TransaccionalidadNuevaConfiguracionActiva(): void
    {
        $previa = $this->ubicacionRepo->findConfiguracionActiva();
        $idPrevia = $previa->id_configuracion_ubicacion;

        // Crear una nueva
        $nueva = $this->ubicacionService->configurarNuevaUbicacion($this->fixtureUserAdminId, [
            'descripcion' => 'Reemplazo de Geocerca Activa',
            'latitud' => self::ESCUELA_LAT,
            'longitud' => self::ESCUELA_LON,
            'radio_metros' => self::ESCUELA_RADIO,
        ]);
        $this->createdConfigIds[] = $nueva->id_configuracion_ubicacion;

        // Verificar que la previa ahora tiene activa = 0 y fecha_fin NO null
        $previaActualizada = $this->ubicacionRepo->findById($idPrevia);
        $totalActivas = (int) $this->db->query('SELECT COUNT(*) FROM configuraciones_ubicacion WHERE activa = 1')->fetchColumn();

        $isOk = (!$previaActualizada->isActiva() && $previaActualizada->fecha_fin !== null && $totalActivas === 1 && $nueva->isActiva());

        $this->record(
            'T8-02',
            'Transaccionalidad en activación de geocerca: versión previa pasa a histórico (DT-53)',
            'Previa inactiva con fecha_fin, exactamente 1 configuración activa en el sistema',
            sprintf('Previa activa: %s, fecha_fin: %s, total activas: %d', $previaActualizada->isActiva() ? '1' : '0', $previaActualizada->fecha_fin ?? 'NULL', $totalActivas),
            $isOk
        );
    }

    /**
     * T8-03: Rechazo de configuración con radio <= 0.
     */
    private function testT8_03_RechazoRadioInvalido(): void
    {
        $caught = false;
        try {
            $this->ubicacionService->configurarNuevaUbicacion($this->fixtureUserAdminId, [
                'descripcion' => 'Radio Inválido',
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
                'radio_metros' => 0.0,
            ]);
        } catch (\InvalidArgumentException $e) {
            $caught = true;
        }

        $this->record(
            'T8-03',
            'Rechazo de configuración de ubicación con radio <= 0 metros',
            'InvalidArgumentException arrojada y cero filas guardadas',
            $caught ? 'InvalidArgumentException capturada correctamente' : 'No se arrojó excepción',
            $caught
        );
    }

    /**
     * T8-04: Rechazo de configuración con latitud fuera de rango (-90 a 90).
     */
    private function testT8_04_RechazoLatitudInvalida(): void
    {
        $caught = false;
        try {
            $this->ubicacionService->configurarNuevaUbicacion($this->fixtureUserAdminId, [
                'descripcion' => 'Latitud Inválida',
                'latitud' => 95.5,
                'longitud' => self::ESCUELA_LON,
                'radio_metros' => 100.0,
            ]);
        } catch (\InvalidArgumentException $e) {
            $caught = true;
        }

        $this->record(
            'T8-04',
            'Rechazo de configuración de ubicación con latitud fuera de rango [-90, 90]',
            'InvalidArgumentException arrojada',
            $caught ? 'Excepción capturada con éxito' : 'No arrojó excepción',
            $caught
        );
    }

    /**
     * T8-05: Rechazo de configuración con longitud fuera de rango (-180 a 180).
     */
    private function testT8_05_RechazoLongitudInvalida(): void
    {
        $caught = false;
        try {
            $this->ubicacionService->configurarNuevaUbicacion($this->fixtureUserAdminId, [
                'descripcion' => 'Longitud Inválida',
                'latitud' => self::ESCUELA_LAT,
                'longitud' => -185.0,
                'radio_metros' => 100.0,
            ]);
        } catch (\InvalidArgumentException $e) {
            $caught = true;
        }

        $this->record(
            'T8-05',
            'Rechazo de configuración de ubicación con longitud fuera de rango [-180, 180]',
            'InvalidArgumentException arrojada',
            $caught ? 'Excepción capturada con éxito' : 'No arrojó excepción',
            $caught
        );
    }

    /**
     * T8-06: Rechazo de configuración con descripción vacía o mayor a 150 caracteres.
     */
    private function testT8_06_RechazoDescripcionInvalida(): void
    {
        $caughtVacia = false;
        try {
            $this->ubicacionService->configurarNuevaUbicacion($this->fixtureUserAdminId, [
                'descripcion' => '   ',
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
                'radio_metros' => 100.0,
            ]);
        } catch (\InvalidArgumentException $e) {
            $caughtVacia = true;
        }

        $caughtLarga = false;
        try {
            $this->ubicacionService->configurarNuevaUbicacion($this->fixtureUserAdminId, [
                'descripcion' => str_repeat('A', 151),
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
                'radio_metros' => 100.0,
            ]);
        } catch (\InvalidArgumentException $e) {
            $caughtLarga = true;
        }

        $isOk = ($caughtVacia && $caughtLarga);

        $this->record(
            'T8-06',
            'Rechazo de configuración con descripción vacía o > 150 caracteres',
            'Rechazo con InvalidArgumentException en ambos casos de longitud de descripción',
            sprintf('Vacía: %s, Larga (>150): %s', $caughtVacia ? 'Rechazada' : 'Permitida', $caughtLarga ? 'Rechazada' : 'Permitida'),
            $isOk
        );
    }

    /**
     * T8-07: Control de acceso a configuración de ubicación para roles no administrativos.
     */
    private function testT8_07_ControlAccesoAdminUbicacion(): void
    {
        $controller = new AdminUbicacionController($this->authService, $this->ubicacionService);

        // 1. Usuario DOCENTE intenta acceder
        $this->loginAsUser($this->fixtureUserDocente1Id);
        $reqDoc = new Request('GET', '/administracion/ubicacion');
        $resDoc = $controller->index($reqDoc);

        // 2. Anónimo intenta acceder
        Session::reset();
        $reqAnon = new Request('GET', '/administracion/ubicacion');
        $resAnon = $controller->index($reqAnon);

        $isDocBlocked = ($resDoc->getStatusCode() === 403);
        $isAnonRedirected = ($resAnon->getStatusCode() === 302 && str_contains($resAnon->getHeader('Location') ?? '', '/login'));
        $isOk = ($isDocBlocked && $isAnonRedirected);

        $this->record(
            'T8-07',
            'Control de acceso RBAC a configuración de ubicación institucional',
            'DOCENTE recibe HTTP 403, Anónimo recibe HTTP 302 a /login',
            sprintf('DOCENTE: %d, Anónimo: %d', $resDoc->getStatusCode(), $resAnon->getStatusCode()),
            $isOk
        );
    }

    /**
     * T8-08: Verificación de unicidad condicional a nivel de base de datos.
     */
    private function testT8_08_UnicidadCondicionalConfiguracionActiva(): void
    {
        $violated = false;
        try {
            // Forzar un segundo INSERT directo en SQL con activa = 1 violando uk_configuraciones_ubicacion_activa
            $stmt = $this->db->prepare('INSERT INTO configuraciones_ubicacion (id_usuario_creador, descripcion, latitud, longitud, radio_metros, activa) VALUES (:u, "Duplicada", 14.63, -90.50, 100, 1)');
            $stmt->execute([':u' => $this->fixtureUserAdminId]);
        } catch (\PDOException $e) {
            $violated = true;
        }

        $this->record(
            'T8-08',
            'Unicidad condicional de geocerca activa en BD (uk_configuraciones_ubicacion_activa)',
            'PDOException por violación de clave única en clave_configuracion_activa',
            $violated ? 'Violación de restricción única confirmada por BD' : 'Permitió múltiples configuraciones activas',
            $violated
        );
    }

    // =========================================================================
    // BLOQUE 2: VALIDACIÓN GEOGRÁFICA Y HAVERSINE (RF-19)
    // =========================================================================

    /**
     * T8-09: Verificación matemática de la fórmula de Haversine con coordenadas geodésicas de referencia.
     */
    private function testT8_09_CalculoHaversineMatematico(): void
    {
        // Dos puntos con distancia geodésica conocida con R = 6,371,000 m
        // Punto A: Palacio Nacional de la Cultura, Guatemala (14.6429, -90.5132)
        // Punto B: Obelisco, Guatemala (14.5958, -90.5186)
        // Distancia geodésica de referencia: ~5269.4 metros
        $latA = 14.642900;
        $lonA = -90.513200;
        $latB = 14.595800;
        $lonB = -90.518600;

        $distancia = $this->asistenciaService->calcularDistanciaHaversine($latA, $lonA, $latB, $lonB);

        // Distancia idéntica (mismo punto) debe ser 0.0
        $distCero = $this->asistenciaService->calcularDistanciaHaversine($latA, $lonA, $latA, $lonA);

        $isOk = ($distCero === 0.0 && $distancia >= 5260.0 && $distancia <= 5280.0);

        $this->record(
            'T8-09',
            'Precisión matemática del cálculo geodésico Haversine (DT-50)',
            'Distancia idéntica 0.0m y distancia geodésica conocida entre 5260m y 5280m (~5269m)',
            sprintf('Mismo punto: %.4fm, Punto A-B: %.2fm', $distCero, $distancia),
            $isOk
        );
    }

    /**
     * T8-10: Marcaje docente dentro del radio institucional permitido -> Aceptado.
     */
    private function testT8_10_MarcajeDentroRadioAceptado(): void
    {
        // Coordenadas a ~30 metros del centro escolar
        $latDentro = self::ESCUELA_LAT + 0.000200;
        $lonDentro = self::ESCUELA_LON + 0.000100;

        $res = $this->asistenciaService->registrarEntrada($this->fixtureDocente1Id, $this->fixtureUserDocente1Id, [
            'latitud' => $latDentro,
            'longitud' => $lonDentro,
            'precision' => 15.0,
        ]);
        $this->createdMarcajeIds[] = $res['marcaje']->id_marcaje_asistencia;

        $isOk = ($res['exito'] === true && $res['distancia_metros'] <= self::ESCUELA_RADIO && $res['marcaje']->tipo === 'ENTRADA');

        $this->record(
            'T8-10',
            'Marcaje docente dentro del radio institucional permitido (RF-19, RF-20)',
            'Marcaje aceptado, distancia <= 100m, registro persistido',
            sprintf('Éxito: %s, Distancia: %.2fm (Radio: %.1fm)', $res['exito'] ? 'true' : 'false', $res['distancia_metros'], $res['radio_metros']),
            $isOk
        );
    }

    /**
     * T8-11: Marcaje docente en la frontera exacta del radio (distancia <= radio_metros).
     */
    private function testT8_11_MarcajeFronteraExactaRadio(): void
    {
        // En la implementación de AsistenciaService:
        // public function isDentroRadio(float $distanciaMetros, float $radioMetros): bool { return $distanciaMetros <= $radioMetros; }
        $igual = $this->asistenciaService->isDentroRadio(100.0, 100.0);
        $menor = $this->asistenciaService->isDentroRadio(99.99, 100.0);
        $mayor = $this->asistenciaService->isDentroRadio(100.01, 100.0);

        $isOk = ($igual === true && $menor === true && $mayor === false);

        $this->record(
            'T8-11',
            'Evaluación inclusiva de la frontera de radio institucional (distancia <= radio)',
            'd=100.00m (TRUE), d=99.99m (TRUE), d=100.01m (FALSE)',
            sprintf('100.0m: %s, 99.99m: %s, 100.01m: %s', $igual ? 'OK' : 'ERR', $menor ? 'OK' : 'ERR', !$mayor ? 'OK' : 'ERR'),
            $isOk
        );
    }

    /**
     * T8-12: Marcaje docente fuera del radio institucional permitido -> Rechazado.
     */
    private function testT8_12_MarcajeFueraRadioRechazado(): void
    {
        // Coordenadas a ~1.5 kilómetros de distancia
        $latFuera = self::ESCUELA_LAT + 0.015000;
        $lonFuera = self::ESCUELA_LON + 0.015000;

        $caught = false;
        $msg = '';
        $conteoAntes = (int) $this->db->query('SELECT COUNT(*) FROM marcajes_asistencia')->fetchColumn();

        try {
            $this->asistenciaService->registrarSalida($this->fixtureDocente1Id, $this->fixtureUserDocente1Id, [
                'latitud' => $latFuera,
                'longitud' => $lonFuera,
                'precision' => 10.0,
            ]);
        } catch (\DomainException $e) {
            $caught = true;
            $msg = $e->getMessage();
        }

        $conteoDespues = (int) $this->db->query('SELECT COUNT(*) FROM marcajes_asistencia')->fetchColumn();
        $isOk = ($caught && $conteoAntes === $conteoDespues && str_contains($msg, 'Marcaje denegado') && str_contains($msg, 'excediendo el radio'));

        $this->record(
            'T8-12',
            'Rechazo estricto de marcaje fuera del radio institucional (RF-19)',
            'DomainException con distancia excedida, cero filas persistidas en base de datos',
            sprintf('Excepción: %s, Diferencia de filas: %d, Mensaje: %s', $caught ? 'Sí' : 'No', $conteoDespues - $conteoAntes, substr($msg, 0, 70)),
            $isOk
        );
    }

    /**
     * T8-13: Rechazo de marcaje cuando no existe configuración activa.
     */
    private function testT8_13_RechazoSinConfiguracionActiva(): void
    {
        // Desactivar temporalmente geocercas
        $this->db->exec('UPDATE configuraciones_ubicacion SET activa = 0 WHERE activa = 1');

        $caught = false;
        $msg = '';
        try {
            $this->asistenciaService->registrarSalida($this->fixtureDocente1Id, $this->fixtureUserDocente1Id, [
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
            ]);
        } catch (\DomainException $e) {
            $caught = true;
            $msg = $e->getMessage();
        } finally {
            // Restaurar configuración activa
            $this->db->exec("UPDATE configuraciones_ubicacion SET activa = 1 WHERE id_configuracion_ubicacion = {$this->fixtureConfigUbicacionId}");
        }

        $isOk = ($caught && str_contains($msg, 'No existe una ubicación institucional activa'));

        $this->record(
            'T8-13',
            'Rechazo de marcaje ante ausencia de geocerca institucional activa',
            'DomainException por geocerca inactiva',
            $caught ? 'Excepción: ' . $msg : 'No arrojó excepción',
            $isOk
        );
    }

    // =========================================================================
    // BLOQUE 3: MARCAJES DE ENTRADA Y SALIDA (RF-20, RF-21, RF-22)
    // =========================================================================

    /**
     * T8-14: Docente registra marcaje de ENTRADA.
     */
    private function testT8_14_MarcajeEntradaDocente(): void
    {
        // El docente 2 inicia en limpio sin marcajes
        $res = $this->asistenciaService->registrarEntrada($this->fixtureDocente2Id, $this->fixtureUserDocente2Id, [
            'latitud' => self::ESCUELA_LAT,
            'longitud' => self::ESCUELA_LON,
            'precision' => 5.0,
        ]);
        $this->createdMarcajeIds[] = $res['marcaje']->id_marcaje_asistencia;

        $m = $res['marcaje'];
        $isOk = ($m->tipo === 'ENTRADA' && $m->id_docente === $this->fixtureDocente2Id && $m->dentro_radio === 1 && $m->distancia_metros >= 0.0);

        $this->record(
            'T8-14',
            'Registro exitoso de marcaje de ENTRADA docente (RF-20)',
            'Tipo ENTRADA, dentro_radio=1, persistido con ID válido',
            sprintf('ID %d, Tipo: %s, Docente: %d, Fecha: %s', $m->id_marcaje_asistencia, $m->tipo, $m->id_docente, $m->fecha_hora),
            $isOk
        );
    }

    /**
     * T8-15: Docente intenta registrar ENTRADA doble consecutiva -> Rechazado.
     */
    private function testT8_15_RechazoDobleEntradaConsecutiva(): void
    {
        // Docente 2 ya tiene ENTRADA activa de T8-14
        $caught = false;
        $msg = '';
        try {
            $this->asistenciaService->registrarEntrada($this->fixtureDocente2Id, $this->fixtureUserDocente2Id, [
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
            ]);
        } catch (\DomainException $e) {
            $caught = true;
            $msg = $e->getMessage();
        }

        $isOk = ($caught && str_contains($msg, 'Ya cuenta con un marcaje de entrada registrado pendiente de salida'));

        $this->record(
            'T8-15',
            'Prevención de doble marcaje de ENTRADA consecutivo sin salida previa',
            'DomainException indicando entrada previa pendiente de salida',
            $caught ? 'Excepción: ' . $msg : 'Permitió doble entrada',
            $isOk
        );
    }

    /**
     * T8-16: Docente registra marcaje de SALIDA tras haber registrado entrada.
     */
    private function testT8_16_MarcajeSalidaDocente(): void
    {
        // Docente 2 registra salida tras su entrada
        $res = $this->asistenciaService->registrarSalida($this->fixtureDocente2Id, $this->fixtureUserDocente2Id, [
            'latitud' => self::ESCUELA_LAT,
            'longitud' => self::ESCUELA_LON,
            'precision' => 8.0,
        ]);
        $this->createdMarcajeIds[] = $res['marcaje']->id_marcaje_asistencia;

        $m = $res['marcaje'];
        $isOk = ($m->tipo === 'SALIDA' && $m->id_docente === $this->fixtureDocente2Id && $m->dentro_radio === 1);

        $this->record(
            'T8-16',
            'Registro exitoso de marcaje de SALIDA docente (RF-21)',
            'Tipo SALIDA registrado cerrando la secuencia de entrada',
            sprintf('ID %d, Tipo: %s, Docente: %d, Fecha: %s', $m->id_marcaje_asistencia, $m->tipo, $m->id_docente, $m->fecha_hora),
            $isOk
        );
    }

    /**
     * T8-17: Docente intenta registrar SALIDA sin entrada previa o como primer marcaje.
     */
    private function testT8_17_RechazoSalidaSinEntradaPrevia(): void
    {
        // Crear un docente temporal limpio sin ningún marcaje
        $stmtDoc = $this->db->prepare('INSERT INTO docentes (id_usuario, codigo_docente, fecha_ingreso, estado) VALUES (:u, :cod, "2024-01-15", "ACTIVO")');
        $cDocTemp = 'DOC-F8-TMP-' . rand(100, 999);
        $stmtDoc->execute([':u' => $this->fixtureUserAdminId, ':cod' => $cDocTemp]);
        $tempDocId = (int) $this->db->lastInsertId();

        $caught = false;
        $msg = '';
        try {
            $this->asistenciaService->registrarSalida($tempDocId, $this->fixtureUserAdminId, [
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
            ]);
        } catch (\DomainException $e) {
            $caught = true;
            $msg = $e->getMessage();
        } finally {
            $this->db->exec("DELETE FROM docentes WHERE id_docente = $tempDocId");
        }

        $isOk = ($caught && str_contains($msg, 'No puede registrar una salida sin haber registrado previamente una entrada'));

        $this->record(
            'T8-17',
            'Rechazo de marcaje de SALIDA cuando no existe registro previo de entrada',
            'DomainException impidiendo salida sin entrada',
            $caught ? 'Excepción: ' . $msg : 'Permitió salida huérfana',
            $isOk
        );
    }

    /**
     * T8-18: Docente intenta registrar SALIDA doble consecutiva -> Rechazado.
     */
    private function testT8_18_RechazoDobleSalidaConsecutiva(): void
    {
        // Docente 2 actualmente tiene SALIDA como último marcaje (de T8-16)
        $caught = false;
        $msg = '';
        try {
            $this->asistenciaService->registrarSalida($this->fixtureDocente2Id, $this->fixtureUserDocente2Id, [
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
            ]);
        } catch (\DomainException $e) {
            $caught = true;
            $msg = $e->getMessage();
        }

        $isOk = ($caught && str_contains($msg, 'Su último marcaje ya fue una salida'));

        $this->record(
            'T8-18',
            'Prevención de doble marcaje de SALIDA consecutivo',
            'DomainException impidiendo salida consecutiva',
            $caught ? 'Excepción: ' . $msg : 'Permitió doble salida',
            $isOk
        );
    }

    /**
     * T8-19: Secuencia de marcajes alternada completa: ENTRADA -> SALIDA -> ENTRADA -> SALIDA.
     */
    private function testT8_19_SecuenciaMarcajeCompletaAlternada(): void
    {
        // Docente 1 registra salida para cerrar la entrada de T8-10
        $salida1 = $this->asistenciaService->registrarSalida($this->fixtureDocente1Id, $this->fixtureUserDocente1Id, [
            'latitud' => self::ESCUELA_LAT,
            'longitud' => self::ESCUELA_LON,
        ]);
        $this->createdMarcajeIds[] = $salida1['marcaje']->id_marcaje_asistencia;

        // Docente 1 registra nueva ENTRADA
        $entrada2 = $this->asistenciaService->registrarEntrada($this->fixtureDocente1Id, $this->fixtureUserDocente1Id, [
            'latitud' => self::ESCUELA_LAT,
            'longitud' => self::ESCUELA_LON,
        ]);
        $this->createdMarcajeIds[] = $entrada2['marcaje']->id_marcaje_asistencia;

        // Docente 1 registra nueva SALIDA
        $salida2 = $this->asistenciaService->registrarSalida($this->fixtureDocente1Id, $this->fixtureUserDocente1Id, [
            'latitud' => self::ESCUELA_LAT,
            'longitud' => self::ESCUELA_LON,
        ]);
        $this->createdMarcajeIds[] = $salida2['marcaje']->id_marcaje_asistencia;

        $historial = $this->asistenciaRepo->findMarcajesByDocente($this->fixtureDocente1Id, 4);

        $seq = array_map(fn($m) => $m->tipo, $historial);
        // Historial está en orden cronológico descendente: [SALIDA, ENTRADA, SALIDA, ENTRADA]
        $isOk = ($seq === ['SALIDA', 'ENTRADA', 'SALIDA', 'ENTRADA']);

        $this->record(
            'T8-19',
            'Secuencia alternada de marcajes: ENTRADA -> SALIDA -> ENTRADA -> SALIDA',
            'Historial descendente ordenado exactamente: SALIDA, ENTRADA, SALIDA, ENTRADA',
            'Secuencia obtenida: ' . implode(' -> ', $seq),
            $isOk
        );
    }

    /**
     * T8-20: Fecha y hora autoritativa provista exclusivamente por el servidor (DT-51, RF-22).
     */
    private function testT8_20_FechaHoraAutoritativaServidor(): void
    {
        $fechaServidorInicio = date('Y-m-d H:i:s');

        // Intentar pasar una fecha falsa en los datos del cliente
        $res = $this->asistenciaService->registrarEntrada($this->fixtureDocente1Id, $this->fixtureUserDocente1Id, [
            'latitud' => self::ESCUELA_LAT,
            'longitud' => self::ESCUELA_LON,
            'fecha_hora' => '1999-01-01 00:00:00', // Fecha espuria
            'timestamp' => 123456789,
        ]);
        $this->createdMarcajeIds[] = $res['marcaje']->id_marcaje_asistencia;

        $fechaMarcaje = $res['marcaje']->fecha_hora;
        $yearMarcaje = substr($fechaMarcaje, 0, 4);
        $currentYear = date('Y');

        $isOk = ($yearMarcaje === $currentYear && !str_starts_with($fechaMarcaje, '1999'));

        $this->record(
            'T8-20',
            'Fecha y hora autoritativas de servidor (CURRENT_TIMESTAMP(6)) (DT-51, RF-22)',
            'Año actual de servidor registrado, parámetro fecha_hora espurio de 1999 ignorado',
            sprintf('Fecha registrada: %s (Año esperado: %s)', $fechaMarcaje, $currentYear),
            $isOk
        );
    }

    // =========================================================================
    // BLOQUE 4: REGLAS DE SEGURIDAD, ROLES Y AISLAMIENTO (RF-23, RNF-06)
    // =========================================================================

    /**
     * T8-21: Docente en estado INACTIVO intenta registrar marcaje -> Rechazado.
     */
    private function testT8_21_RechazoDocenteInactivo(): void
    {
        $caught = false;
        $msg = '';
        try {
            $this->asistenciaService->registrarEntrada($this->fixtureDocenteInactivoId, $this->fixtureUserDocenteInactivoId, [
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
            ]);
        } catch (\DomainException $e) {
            $caught = true;
            $msg = $e->getMessage();
        }

        $isOk = ($caught && str_contains($msg, 'cuenta docente no se encuentra activa'));

        $this->record(
            'T8-21',
            'Rechazo de marcaje de asistencia para docente en estado INACTIVO',
            'DomainException denegando acceso por cuenta inactiva',
            $caught ? 'Excepción: ' . $msg : 'Permitió marcaje de docente inactivo',
            $isOk
        );
    }

    /**
     * T8-22: Usuario con rol ADMINISTRADOR intenta registrar marcaje docente -> Rechazado.
     */
    private function testT8_22_RechazoAdminMarcajeDocente(): void
    {
        $controller = new DocenteAsistenciaController($this->authService, $this->asistenciaService, $this->portalDocenteService);
        $this->loginAsUser($this->fixtureUserAdminId);

        $req = new Request('POST', '/docente/asistencia/entrada', [], ['latitud' => self::ESCUELA_LAT, 'longitud' => self::ESCUELA_LON]);
        $res = $controller->marcarEntrada($req);

        $isOk = ($res->getStatusCode() === 403);

        $this->record(
            'T8-22',
            'Rechazo de marcaje de asistencia para rol ADMINISTRADOR (sin rol DOCENTE)',
            'HTTP 403 Acceso Denegado por rol no autorizado',
            sprintf('Código HTTP obtenido: %d', $res->getStatusCode()),
            $isOk
        );
    }

    /**
     * T8-23: Usuario anónimo intenta marcar asistencia -> Redirección a login (HTTP 302).
     */
    private function testT8_23_RechazoAnonimoMarcaje(): void
    {
        $controller = new DocenteAsistenciaController($this->authService, $this->asistenciaService, $this->portalDocenteService);
        Session::reset();

        $req = new Request('POST', '/docente/asistencia/entrada', [], ['latitud' => self::ESCUELA_LAT, 'longitud' => self::ESCUELA_LON]);
        $res = $controller->marcarEntrada($req);

        $isOk = ($res->getStatusCode() === 302 && str_contains($res->getHeader('Location') ?? '', '/login'));

        $this->record(
            'T8-23',
            'Rechazo de marcaje de asistencia para usuario no autenticado',
            'HTTP 302 con redirección a /login',
            sprintf('HTTP %d a %s', $res->getStatusCode(), $res->getHeader('Location') ?? 'N/A'),
            $isOk
        );
    }

    /**
     * T8-24: Aislamiento horizontal estricto en consulta de historial (DT-54, RF-23).
     */
    private function testT8_24_AislamientoHorizontalHistorial(): void
    {
        // Obtener historial del Docente 1 y verificar que ningún registro pertenezca a Docente 2
        $histDoc1 = $this->asistenciaService->obtenerHistorialDocente($this->fixtureDocente1Id, 50);
        $histDoc2 = $this->asistenciaService->obtenerHistorialDocente($this->fixtureDocente2Id, 50);

        $doc1HasDoc2Records = false;
        foreach ($histDoc1 as $m) {
            if ($m->id_docente === $this->fixtureDocente2Id) {
                $doc1HasDoc2Records = true;
                break;
            }
        }

        $doc2HasDoc1Records = false;
        foreach ($histDoc2 as $m) {
            if ($m->id_docente === $this->fixtureDocente1Id) {
                $doc2HasDoc1Records = true;
                break;
            }
        }

        $isOk = (!$doc1HasDoc2Records && !$doc2HasDoc1Records && !empty($histDoc1) && !empty($histDoc2));

        $this->record(
            'T8-24',
            'Aislamiento horizontal estricto de historial entre docentes (DT-54, RF-23)',
            'Docente 1 sólo ve sus registros, Docente 2 sólo ve los suyos, 0 contaminación cruzada',
            sprintf('Docente 1: %d marcajes (contaminación: %s), Docente 2: %d marcajes (contaminación: %s)', count($histDoc1), $doc1HasDoc2Records ? 'SÍ' : 'NO', count($histDoc2), $doc2HasDoc1Records ? 'SÍ' : 'NO'),
            $isOk
        );
    }

    /**
     * T8-25: Historial ordenado cronológicamente descendente (fecha_hora DESC).
     */
    private function testT8_25_HistorialOrdenCronologicoDescendente(): void
    {
        $historial = $this->asistenciaService->obtenerHistorialDocente($this->fixtureDocente1Id, 50);

        $isDescending = true;
        for ($i = 0; $i < count($historial) - 1; $i++) {
            if (strcmp($historial[$i]->fecha_hora, $historial[$i + 1]->fecha_hora) < 0) {
                $isDescending = false;
                break;
            }
        }

        $isOk = ($isDescending && count($historial) >= 2);

        $this->record(
            'T8-25',
            'Orden cronológico descendente estricto en historial de marcajes (fecha_hora DESC)',
            'Cada elemento sucesivo tiene fecha_hora menor o igual al anterior',
            sprintf('Total marcajes evaluados: %d, orden descendente: %s', count($historial), $isDescending ? 'CORRECTO' : 'INCORRECTO'),
            $isOk
        );
    }

    /**
     * T8-26: Inmutabilidad de marcajes de asistencia (DT-52, RNF-06).
     */
    private function testT8_26_InmutabilidadMarcajesAsistencia(): void
    {
        $reflection = new \ReflectionClass(\App\Repositories\AsistenciaRepositoryInterface::class);
        $methods = array_map(fn($m) => $m->getName(), $reflection->getMethods());

        $hasUpdate = in_array('update', $methods, true) || in_array('actualizarMarcaje', $methods, true);
        $hasDelete = in_array('delete', $methods, true) || in_array('eliminarMarcaje', $methods, true);

        $isOk = (!$hasUpdate && !$hasDelete);

        $this->record(
            'T8-26',
            'Inmutabilidad de marcajes: interfaz de repositorio carece de métodos de edición o borrado (DT-52)',
            'AsistenciaRepositoryInterface no define métodos update ni delete',
            sprintf('Métodos disponibles: %s (Update: %s, Delete: %s)', implode(', ', $methods), $hasUpdate ? 'PRESENTE' : 'AUSENTE', $hasDelete ? 'PRESENTE' : 'AUSENTE'),
            $isOk
        );
    }

    /**
     * T8-27: Integridad de base de datos: constraint chk_marcajes_dentro_radio impide dentro_radio = 0.
     */
    private function testT8_27_IntegridadConstraintDentroRadio(): void
    {
        $violated = false;
        try {
            $stmt = $this->db->prepare('INSERT INTO marcajes_asistencia (
                id_docente, id_usuario, id_configuracion_ubicacion, tipo, latitud_dispositivo, longitud_dispositivo, distancia_metros, dentro_radio
            ) VALUES (
                :d, :u, :c, "ENTRADA", 14.63, -90.50, 500.0, 0
            )');
            $stmt->execute([
                ':d' => $this->fixtureDocente1Id,
                ':u' => $this->fixtureUserDocente1Id,
                ':c' => $this->fixtureConfigUbicacionId,
            ]);
        } catch (\PDOException $e) {
            $violated = true;
        }

        $this->record(
            'T8-27',
            'Integridad física de datos: constraint chk_marcajes_dentro_radio (dentro_radio = 1)',
            'PDOException por violación de CHECK constraint chk_marcajes_dentro_radio',
            $violated ? 'Violación de constraint capturada correctamente por BD' : 'Permitió almacenar registro con dentro_radio = 0',
            $violated
        );
    }

    /**
     * T8-28: Sanitización y prevención de XSS en vistas de asistencia docente.
     */
    private function testT8_28_SanitizacionXssVistas(): void
    {
        $viewFile = dirname(__DIR__) . '/views/docente/asistencia/index.php';
        $content = file_get_contents($viewFile);

        // Comprobar que las salidas dinámicas están envueltas en la función de escape seguro e(...)
        $hasRawDocenteOutput = (bool) preg_match('/<\?=\s*\$docente->[a-zA-Z0-9_]+\s*;?\s*\?>/', $content);
        $hasRawConfigOutput = (bool) preg_match('/<\?=\s*\$configuracion->[a-zA-Z0-9_]+\s*;?\s*\?>/', $content);
        $hasRawErrorOutput = (bool) preg_match('/<\?=\s*\$mensajeError\s*;?\s*\?>/', $content);

        $isOk = (!$hasRawDocenteOutput && !$hasRawConfigOutput && !$hasRawErrorOutput);

        $this->record(
            'T8-28',
            'Sanitización y prevención de XSS en vistas de asistencia docente (RF-23)',
            'Todas las expresiones dinámicas envueltas en e() sin interpolación cruda',
            sprintf('Raw Docente: %s, Raw Config: %s, Raw Error: %s', $hasRawDocenteOutput ? 'DETECTADO' : 'CORRECTO', $hasRawConfigOutput ? 'DETECTADO' : 'CORRECTO', $hasRawErrorOutput ? 'DETECTADO' : 'CORRECTO'),
            $isOk
        );
    }

    // =========================================================================
    // BLOQUE 5: PRUEBAS HTTP REALES Y PARÁMETROS MALICIOSOS
    // =========================================================================

    /**
     * T8-29: Flujo HTTP real completo mediante servidor embebido PHP y cURL.
     */
    private function testT8_29_FlujoHttpRealCompleto(): void
    {
        echo "\n>>> Iniciando servidor web PHP embebido para prueba HTTP real T8-29...\n";

        $port = 8095;
        $phpBin = PHP_BINARY;
        $docRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public';

        $serverLog = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php_server_f8_' . uniqid() . '.log';
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $serverLog, 'a'],
            2 => ['file', $serverLog, 'a'],
        ];

        $cmd = sprintf('"%s" -S 127.0.0.1:%d -t "%s"', $phpBin, $port, $docRoot);
        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));

        if (!is_resource($process)) {
            $this->record('T8-29', 'Flujo HTTP real completo de asistencia', 'Servidor iniciado', 'No se pudo iniciar servidor PHP embebido', false);
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
            $this->record('T8-29', 'Flujo HTTP real completo de asistencia', 'Servidor conectado', 'El servidor web no respondió a tiempo', false);
            return;
        }

        $cookieFileDocente = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_asistencia_doc_' . uniqid() . '.txt';
        $cookieFileAdmin = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_asistencia_adm_' . uniqid() . '.txt';

        $httpRequest = function (string $url, string $method = 'GET', ?array $postData = null, ?string $cookie = null, array $headers = []): array {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            if ($cookie !== null) {
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
            }
            if (!empty($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if ($postData !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                }
            }
            $raw = curl_exec($ch);
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
            // 1. GET /login para obtener CSRF token y cookie para Docente 1
            $resLoginGet = $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'GET', null, $cookieFileDocente);
            $csrfTokenLogin = '';
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resLoginGet['body'], $m)) {
                $csrfTokenLogin = $m[1];
            }

            // 1. Login DOCENTE
            $u1 = $this->userRepo->findById($this->fixtureUserDocente1Id);
            $resLoginPost = $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'POST', [
                '_token' => $csrfTokenLogin,
                'nombre_usuario' => $u1->nombre_usuario,
                'password' => 'PassFase8Test!',
            ], $cookieFileDocente);
            $loginOk = ($resLoginPost['code'] === 302 && str_contains($resLoginPost['location'], '/docente'));

            // 2. GET /docente/asistencia (HTTP 200 con formulario y geocerca)
            $resAsistenciaGet = $httpRequest(sprintf('http://127.0.0.1:%d/docente/asistencia', $port), 'GET', null, $cookieFileDocente);
            $get200 = ($resAsistenciaGet['code'] === 200 && str_contains($resAsistenciaGet['body'], 'Control de Asistencia Docente'));

            // 3. Extracción de token CSRF del formulario
            $csrfAsistencia = '';
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resAsistenciaGet['body'], $m)) {
                $csrfAsistencia = $m[1];
            }
            $csrfOk = !empty($csrfAsistencia);

            // Asegurar estado inicial limpio para Docente 1
            $ultimo = $this->asistenciaRepo->findUltimoMarcajeDocente($this->fixtureDocente1Id);
            if ($ultimo !== null && $ultimo->isEntrada()) {
                $httpRequest(sprintf('http://127.0.0.1:%d/docente/asistencia/salida', $port), 'POST', [
                    '_token' => $csrfAsistencia,
                    'latitud' => self::ESCUELA_LAT,
                    'longitud' => self::ESCUELA_LON,
                ], $cookieFileDocente);
            }

            // 4. POST /docente/asistencia/entrada dentro del radio
            $resEntradaAjax = $httpRequest(sprintf('http://127.0.0.1:%d/docente/asistencia/entrada', $port), 'POST', [
                '_token' => $csrfAsistencia,
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
                'precision' => 10.0,
            ], $cookieFileDocente, ['Accept: application/json', 'X-Requested-With: XMLHttpRequest']);
            $entradaJson = json_decode($resEntradaAjax['body'], true);
            $entradaOk = ($resEntradaAjax['code'] === 200 && ($entradaJson['exito'] ?? false) === true);

            // 5. Verificación de persistencia en BD
            $stmtPersist = $this->db->prepare('SELECT * FROM marcajes_asistencia WHERE id_docente = :d ORDER BY id_marcaje_asistencia DESC LIMIT 1');
            $stmtPersist->execute([':d' => $this->fixtureDocente1Id]);
            $rowEntrada = $stmtPersist->fetch(\PDO::FETCH_ASSOC);
            $persistenciaOk = ($rowEntrada !== false
                && $rowEntrada['tipo'] === 'ENTRADA'
                && (int) $rowEntrada['dentro_radio'] === 1
                && (float) $rowEntrada['distancia_metros'] <= self::ESCUELA_RADIO);
            if ($rowEntrada) {
                $this->createdMarcajeIds[] = (int) $rowEntrada['id_marcaje_asistencia'];
            }

            // 6. Intento de doble entrada consecutiva (secuencia inválida) -> 422 JSON
            $resDobleEntrada = $httpRequest(sprintf('http://127.0.0.1:%d/docente/asistencia/entrada', $port), 'POST', [
                '_token' => $csrfAsistencia,
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
            ], $cookieFileDocente, ['Accept: application/json', 'X-Requested-With: XMLHttpRequest']);
            $dobleEntradaRechazada = ($resDobleEntrada['code'] === 422);

            // 7. POST /docente/asistencia/salida dentro del radio -> 200 JSON
            $resSalidaValida = $httpRequest(sprintf('http://127.0.0.1:%d/docente/asistencia/salida', $port), 'POST', [
                '_token' => $csrfAsistencia,
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
            ], $cookieFileDocente, ['Accept: application/json', 'X-Requested-With: XMLHttpRequest']);
            $salidaJson = json_decode($resSalidaValida['body'], true);
            $salidaOk = ($resSalidaValida['code'] === 200 && ($salidaJson['exito'] ?? false) === true);
            if ($salidaOk) {
                $ultimoSalida = $this->asistenciaRepo->findUltimoMarcajeDocente($this->fixtureDocente1Id);
                if ($ultimoSalida !== null) {
                    $this->createdMarcajeIds[] = $ultimoSalida->id_marcaje_asistencia;
                }
            }

            // 8. Consulta GET de historial personal del docente -> HTTP 200 renderizando historial
            $resHistorialGet = $httpRequest(sprintf('http://127.0.0.1:%d/docente/asistencia', $port), 'GET', null, $cookieFileDocente);
            $historialOk = ($resHistorialGet['code'] === 200 && (str_contains($resHistorialGet['body'], 'Historial Reciente') || str_contains($resHistorialGet['body'], 'Mi Historial')));

            // 9. Intento de manipulación de id_docente en payload (spoofing ignorado)
            $countDoc2Antes = (int) $this->db->query("SELECT COUNT(*) FROM marcajes_asistencia WHERE id_docente = {$this->fixtureDocente2Id}")->fetchColumn();
            $resSpoof = $httpRequest(sprintf('http://127.0.0.1:%d/docente/asistencia/entrada', $port), 'POST', [
                '_token' => $csrfAsistencia,
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
                'id_docente' => $this->fixtureDocente2Id, // Intento de spoofing
            ], $cookieFileDocente, ['Accept: application/json', 'X-Requested-With: XMLHttpRequest']);
            $countDoc2Despues = (int) $this->db->query("SELECT COUNT(*) FROM marcajes_asistencia WHERE id_docente = {$this->fixtureDocente2Id}")->fetchColumn();
            $spoofIgnorado = ($countDoc2Despues === $countDoc2Antes); // Docente 2 no fue afectado
            $ultimoDoc1 = $this->asistenciaRepo->findUltimoMarcajeDocente($this->fixtureDocente1Id);
            if ($ultimoDoc1 && $ultimoDoc1->isEntrada()) {
                $this->createdMarcajeIds[] = $ultimoDoc1->id_marcaje_asistencia;
                // Cerrar salida para dejar estado consistente
                $httpRequest(sprintf('http://127.0.0.1:%d/docente/asistencia/salida', $port), 'POST', [
                    '_token' => $csrfAsistencia,
                    'latitud' => self::ESCUELA_LAT,
                    'longitud' => self::ESCUELA_LON,
                ], $cookieFileDocente);
                $ultimoSalida2 = $this->asistenciaRepo->findUltimoMarcajeDocente($this->fixtureDocente1Id);
                if ($ultimoSalida2) {
                    $this->createdMarcajeIds[] = $ultimoSalida2->id_marcaje_asistencia;
                }
            }

            // 10. POST con coordenadas fuera del radio -> 422 JSON
            $countTotalAntesFuera = (int) $this->db->query("SELECT COUNT(*) FROM marcajes_asistencia")->fetchColumn();
            $resFueraRadio = $httpRequest(sprintf('http://127.0.0.1:%d/docente/asistencia/entrada', $port), 'POST', [
                '_token' => $csrfAsistencia,
                'latitud' => self::ESCUELA_LAT + 0.05,
                'longitud' => self::ESCUELA_LON + 0.05,
            ], $cookieFileDocente, ['Accept: application/json', 'X-Requested-With: XMLHttpRequest']);
            $fueraRadioRechazado = ($resFueraRadio['code'] === 422);

            // 11. Comprobación de no persistencia en BD para coordenadas fuera de radio
            $countTotalDespuesFuera = (int) $this->db->query("SELECT COUNT(*) FROM marcajes_asistencia")->fetchColumn();
            $ceroPersistenciaFuera = ($countTotalDespuesFuera === $countTotalAntesFuera);

            // 12. Usuario con rol ADMINISTRADOR intenta acceder a /docente/asistencia -> HTTP 403
            $resLoginAdmGet = $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'GET', null, $cookieFileAdmin);
            $csrfTokenAdm = '';
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resLoginAdmGet['body'], $m)) {
                $csrfTokenAdm = $m[1];
            }
            $uAdm = $this->userRepo->findById($this->fixtureUserAdminId);
            $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'POST', [
                '_token' => $csrfTokenAdm,
                'nombre_usuario' => $uAdm->nombre_usuario,
                'password' => 'PassFase8Test!',
            ], $cookieFileAdmin);
            $resAdminDocente = $httpRequest(sprintf('http://127.0.0.1:%d/docente/asistencia', $port), 'GET', null, $cookieFileAdmin);
            $adminRechazado403 = ($resAdminDocente['code'] === 403);

            // 13. Usuario anónimo intenta acceder -> Redirección HTTP 302 a /login
            $resAnonimo = $httpRequest(sprintf('http://127.0.0.1:%d/docente/asistencia', $port), 'GET', null, null);
            $anonimoRedirigido302 = ($resAnonimo['code'] === 302 && str_contains($resAnonimo['location'], '/login'));

            $passed = ($loginOk && $get200 && $csrfOk && $entradaOk && $persistenciaOk
                && $dobleEntradaRechazada && $salidaOk && $historialOk && $spoofIgnorado
                && $fueraRadioRechazado && $ceroPersistenciaFuera && $adminRechazado403 && $anonimoRedirigido302);

            $this->record(
                'T8-29',
                'Flujo HTTP real completo de asistencia docente mediante cURL y servidor local (13 verificaciones)',
                'Login 302 -> GET 200 -> CSRF OK -> Entrada 200 JSON -> Persistencia BD OK -> Doble Entrada 422 -> Salida 200 JSON -> Historial 200 -> Spoofing ignorado -> Fuera de radio 422 -> 0 persistencia -> Admin 403 -> Anónimo 302',
                sprintf('Login: %s, GET: %s, CSRF: %s, Entrada: %s, Persist: %s, Doble: %s, Salida: %s, Hist: %s, Spoof: %s, Fuera: %s, 0Persist: %s, Admin403: %s, Anon302: %s',
                    $loginOk ? 'OK' : 'ERR', $get200 ? 'OK' : 'ERR', $csrfOk ? 'OK' : 'ERR', $entradaOk ? 'OK' : 'ERR',
                    $persistenciaOk ? 'OK' : 'ERR', $dobleEntradaRechazada ? 'OK' : 'ERR', $salidaOk ? 'OK' : 'ERR',
                    $historialOk ? 'OK' : 'ERR', $spoofIgnorado ? 'OK' : 'ERR', $fueraRadioRechazado ? 'OK' : 'ERR',
                    $ceroPersistenciaFuera ? 'OK' : 'ERR', $adminRechazado403 ? 'OK' : 'ERR', $anonimoRedirigido302 ? 'OK' : 'ERR'
                ),
                $passed
            );
        } finally {
            $this->terminateProcess($pid, $process, $pipes);
            @unlink($serverLog);
            if (file_exists($cookieFileDocente)) {
                @unlink($cookieFileDocente);
            }
            if (file_exists($cookieFileAdmin)) {
                @unlink($cookieFileAdmin);
            }
        }
    }

    /**
     * T8-30: Parámetros del cliente maliciosos ignorados (cálculo autoritativo exclusivo en backend).
     */
    private function testT8_30_ParametrosMaliciososClienteIgnorados(): void
    {
        // El cliente envía coordenadas fuera de radio pero intenta falsificar
        // los campos dentro_radio = 1, distancia_metros = 0.0 y un id_docente ajeno
        $controller = new DocenteAsistenciaController($this->authService, $this->asistenciaService, $this->portalDocenteService);
        $this->loginAsUser($this->fixtureUserDocente1Id);

        // Asegurarse de que el docente pueda registrar entrada
        $ultimo = $this->asistenciaRepo->findUltimoMarcajeDocente($this->fixtureDocente1Id);
        if ($ultimo !== null && $ultimo->isEntrada()) {
            $this->asistenciaService->registrarSalida($this->fixtureDocente1Id, $this->fixtureUserDocente1Id, [
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
            ]);
        }

        $payloadMalicioso = [
            'latitud' => self::ESCUELA_LAT + 0.020000, // Fuera de radio (~2.2 km)
            'longitud' => self::ESCUELA_LON + 0.020000,
            'dentro_radio' => 1,                      // Intento de spoofing
            'distancia_metros' => 0.0,                // Intento de spoofing
            'distancia' => 0.0,                       // Intento de spoofing
            'id_docente' => $this->fixtureDocente2Id, // Intento de spoofing de identidad ajena
        ];

        $req = new Request('POST', '/docente/asistencia/entrada', [], $payloadMalicioso, [
            'accept' => 'application/json',
            'x-requested-with' => 'xmlhttprequest',
        ]);

        $res = $controller->marcarEntrada($req);
        $body = json_decode($res->getContent(), true);

        // Debe ser HTTP 422 rechazado por distancia excedida
        $isOk = ($res->getStatusCode() === 422 && ($body['exito'] ?? false) === false && str_contains($body['error'] ?? '', 'excediendo el radio'));

        $this->record(
            'T8-30',
            'Seguridad ante parámetros maliciosos: backend recalcula y descarta spoofing de cliente (DT-49)',
            'HTTP 422 denegado con recálculo autoritativo de distancia y rechazo de parámetros espurios',
            sprintf('HTTP %d, Mensaje: %s', $res->getStatusCode(), $body['error'] ?? 'Sin error'),
            $isOk
        );
    }

    // =========================================================================
    // BLOQUE 6: REGRESIONES DE FASES ANTERIORES
    // =========================================================================

    /**
     * T8-31: Regresión completa de Fase 7 (Portal Docente).
     */
    private function testT8_31_RegresionFase7(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 7 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase7.php');
        $output = (string) @shell_exec($cmd);

        $matched = preg_match('/Total ejecutadas\s*:\s*28\s*Exitosas\s*\(OK\)\s*:\s*28\s*Fallidas\s*\(FALLO\)\s*:\s*0/i', $output);
        $isOk = $matched && !str_contains($output, '[FALLO]');

        $this->record(
            'T8-31',
            'Regresión completa de Fase 7 (Portal Docente)',
            '28/28 pruebas superadas con 0 fallos',
            $isOk ? '28/28 pruebas exitosas de Fase 7' : 'Fallo en regresión de Fase 7: ' . substr($output, -250),
            $isOk
        );
    }

    /**
     * T8-32: Regresión completa de Fase 6 (Inscripciones y Cupos).
     */
    private function testT8_32_RegresionFase6(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 6 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase6.php');
        $output = (string) @shell_exec($cmd);

        $matched = preg_match('/Total ejecutadas\s*:\s*30\s*Exitosas\s*\(OK\)\s*:\s*30\s*Fallidas\s*\(FALLO\)\s*:\s*0/i', $output);
        $isOk = $matched && !str_contains($output, '[FALLO]');

        $this->record(
            'T8-32',
            'Regresión completa de Fase 6 (Inscripciones y Cupos)',
            '30/30 pruebas superadas con 0 fallos',
            $isOk ? '30/30 pruebas exitosas de Fase 6' : 'Fallo en regresión de Fase 6: ' . substr($output, -250),
            $isOk
        );
    }

    /**
     * T8-33: Regresión completa de Fase 5 (Asignaciones Docentes).
     */
    private function testT8_33_RegresionFase5(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 5 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase5.php');
        $output = (string) @shell_exec($cmd);

        $matched = preg_match('/Total ejecutadas\s*:\s*26\s*Exitosas\s*\(OK\)\s*:\s*26\s*Fallidas\s*\(FALLO\)\s*:\s*0/i', $output);
        $isOk = $matched && !str_contains($output, '[FALLO]');

        $this->record(
            'T8-33',
            'Regresión completa de Fase 5 (Asignaciones Docentes)',
            '26/26 pruebas superadas con 0 fallos',
            $isOk ? '26/26 pruebas exitosas de Fase 5' : 'Fallo en regresión de Fase 5: ' . substr($output, -250),
            $isOk
        );
    }

    /**
     * T8-34: Regresión completa de Fase 4 (Administración Básica).
     */
    private function testT8_34_RegresionFase4(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 4 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase4.php');
        $output = (string) @shell_exec($cmd);

        $matched = preg_match('/Total ejecutadas\s*:\s*27\s*Exitosas\s*\(OK\)\s*:\s*27\s*Fallidas\s*\(FALLO\)\s*:\s*0/i', $output);
        $isOk = $matched && !str_contains($output, '[FALLO]');

        $this->record(
            'T8-34',
            'Regresión completa de Fase 4 (Administración Básica)',
            '27/27 pruebas superadas con 0 fallos',
            $isOk ? '27/27 pruebas exitosas de Fase 4' : 'Fallo en regresión de Fase 4: ' . substr($output, -250),
            $isOk
        );
    }

    /**
     * T8-35: Regresión completa de Fase 3 (Autenticación y Roles).
     */
    private function testT8_35_RegresionFase3(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 3 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase3.php');
        $output = (string) @shell_exec($cmd);

        $matched = preg_match('/Total ejecutadas\s*:\s*19\s*Exitosas\s*\(OK\)\s*:\s*19\s*Fallidas\s*\(FALLO\)\s*:\s*0/i', $output);
        $isOk = $matched && !str_contains($output, '[FALLO]');

        $this->record(
            'T8-35',
            'Regresión completa de Fase 3 (Autenticación y Roles)',
            '19/19 pruebas superadas con 0 fallos',
            $isOk ? '19/19 pruebas exitosas de Fase 3' : 'Fallo en regresión de Fase 3: ' . substr($output, -250),
            $isOk
        );
    }

    /**
     * T8-36: Regresión completa de Fase 2 (Arquitectura Base).
     */
    private function testT8_36_RegresionFase2(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 2 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests.php');
        $output = (string) @shell_exec($cmd);

        $isOk = str_contains($output, 'TODAS LAS PRUEBAS DE FASE 2 SUPERADAS SATISFACTORIAMENTE.')
            && str_contains($output, 'Total ejecutadas : 16')
            && str_contains($output, 'Fallidas (FALLO) : 0');

        $this->record(
            'T8-36',
            'Regresión completa de Fase 2 (Arquitectura Base y Enrutamiento)',
            '16/16 pruebas de Fase 2 superadas con 0 fallos',
            $isOk ? '16/16 pruebas exitosas de Fase 2' : 'Fallo en regresión de Fase 2: ' . substr($output, -250),
            $isOk
        );
    }

    /**
     * T8-37: Regresión completa de Fase 1 (Diseño de Base de Datos).
     */
    private function testT8_37_RegresionFase1(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 1 en base de datos aislada...\n";
        $root = dirname(__DIR__);
        $mysqlBinary = $this->findMysqlBinary();

        if ($mysqlBinary === null) {
            $this->record(
                'T8-37',
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
        $tempDb = 'escuela_fase8_regresion_temp';

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
            'T8-37',
            'Regresión completa de Fase 1 (Diseño de Base de Datos)',
            '18/18 pruebas de integridad relacional superadas',
            $obtained,
            $isOk
        );
    }

    // =========================================================================
    // BLOQUE 7: CORRECCIONES DE REVISIÓN EXTERNA (F8-01, F8-02, F8-03)
    // =========================================================================

    /**
     * T8-38: Protección CSRF real en marcajes de asistencia (F8-01).
     * Servidor web PHP embebido y cURL:
     * Caso A: POST /docente/asistencia/entrada sin token -> 403, 0 persistencia.
     * Caso B: POST /docente/asistencia/entrada con token manipulado -> 403, 0 persistencia.
     * Caso C: POST /docente/asistencia/salida sin token válido -> 403, 0 persistencia.
     * Caso D: POST /docente/asistencia/entrada con token válido -> 200, 1 persistencia confirmada.
     */
    private function testT8_38_ProteccionCsrfReal(): void
    {
        echo "\n>>> Iniciando servidor web PHP embebido para prueba CSRF real T8-38...\n";

        $port = 8096;
        $phpBin = PHP_BINARY;
        $docRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public';

        $serverLog = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php_server_f8_csrf_' . uniqid() . '.log';
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $serverLog, 'a'],
            2 => ['file', $serverLog, 'a'],
        ];

        $cmd = sprintf('"%s" -S 127.0.0.1:%d -t "%s"', $phpBin, $port, $docRoot);
        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));

        if (!is_resource($process)) {
            $this->record('T8-38', 'Protección CSRF real en marcajes de asistencia', 'Servidor iniciado', 'No se pudo iniciar servidor PHP embebido en puerto ' . $port, false);
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
            $this->record('T8-38', 'Protección CSRF real en marcajes de asistencia', 'Servidor conectado', 'El servidor web no respondió a tiempo', false);
            return;
        }

        $cookieDocente = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_csrf_doc_' . uniqid() . '.txt';

        $httpRequest = function (string $url, string $method = 'GET', ?array $postData = null, ?string $cookie = null, array $headers = []): array {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            if ($cookie !== null) {
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
            }
            if (!empty($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if ($postData !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                }
            }
            $raw = curl_exec($ch);
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
            // Autenticar Docente 1 para obtener cookie de sesión válida
            $resLoginGet = $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'GET', null, $cookieDocente);
            $csrfTokenLogin = '';
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resLoginGet['body'], $m)) {
                $csrfTokenLogin = $m[1];
            }
            $u1 = $this->userRepo->findById($this->fixtureUserDocente1Id);
            $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'POST', [
                '_token' => $csrfTokenLogin,
                'nombre_usuario' => $u1->nombre_usuario,
                'password' => 'PassFase8Test!',
            ], $cookieDocente);

            // Asegurarse de que Docente 1 no tenga entrada pendiente para no chocar con la máquina de estados
            $ultimo = $this->asistenciaRepo->findUltimoMarcajeDocente($this->fixtureDocente1Id);
            if ($ultimo !== null && $ultimo->isEntrada()) {
                $this->asistenciaService->registrarSalida($this->fixtureDocente1Id, $this->fixtureUserDocente1Id, [
                    'latitud' => self::ESCUELA_LAT,
                    'longitud' => self::ESCUELA_LON,
                ]);
            }

            $countInicial = (int) $this->db->query("SELECT COUNT(*) FROM marcajes_asistencia WHERE id_docente = {$this->fixtureDocente1Id}")->fetchColumn();

            // CASO A: POST /docente/asistencia/entrada sin token CSRF
            $resCasoA = $httpRequest(sprintf('http://127.0.0.1:%d/docente/asistencia/entrada', $port), 'POST', [
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
            ], $cookieDocente);
            $countCasoA = (int) $this->db->query("SELECT COUNT(*) FROM marcajes_asistencia WHERE id_docente = {$this->fixtureDocente1Id}")->fetchColumn();
            $casoAOk = ($resCasoA['code'] === 403 && $countCasoA === $countInicial);

            // CASO B: POST /docente/asistencia/entrada con token manipulado
            $resCasoB = $httpRequest(sprintf('http://127.0.0.1:%d/docente/asistencia/entrada', $port), 'POST', [
                '_token' => 'token_manipulado_invalido_fase8_xyz',
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
            ], $cookieDocente);
            $countCasoB = (int) $this->db->query("SELECT COUNT(*) FROM marcajes_asistencia WHERE id_docente = {$this->fixtureDocente1Id}")->fetchColumn();
            $casoBOk = ($resCasoB['code'] === 403 && $countCasoB === $countInicial);

            // CASO C: POST /docente/asistencia/salida sin token válido
            $resCasoC = $httpRequest(sprintf('http://127.0.0.1:%d/docente/asistencia/salida', $port), 'POST', [
                '_token' => 'token_falso_salida_123',
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
            ], $cookieDocente);
            $countCasoC = (int) $this->db->query("SELECT COUNT(*) FROM marcajes_asistencia WHERE id_docente = {$this->fixtureDocente1Id}")->fetchColumn();
            $casoCOk = ($resCasoC['code'] === 403 && $countCasoC === $countInicial);

            // CASO D: POST /docente/asistencia/entrada con token CSRF válido
            $resFormGet = $httpRequest(sprintf('http://127.0.0.1:%d/docente/asistencia', $port), 'GET', null, $cookieDocente);
            $csrfValido = '';
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resFormGet['body'], $m)) {
                $csrfValido = $m[1];
            }

            $resCasoD = $httpRequest(sprintf('http://127.0.0.1:%d/docente/asistencia/entrada', $port), 'POST', [
                '_token' => $csrfValido,
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
                'precision' => 10.0,
            ], $cookieDocente, ['Accept: application/json', 'X-Requested-With: XMLHttpRequest']);
            $countCasoD = (int) $this->db->query("SELECT COUNT(*) FROM marcajes_asistencia WHERE id_docente = {$this->fixtureDocente1Id}")->fetchColumn();
            $jsonCasoD = json_decode($resCasoD['body'], true);
            $casoDOk = ($resCasoD['code'] === 200 && ($jsonCasoD['exito'] ?? false) === true && $countCasoD === $countInicial + 1);

            if ($casoDOk) {
                $ultimoPersist = $this->asistenciaRepo->findUltimoMarcajeDocente($this->fixtureDocente1Id);
                if ($ultimoPersist) {
                    $this->createdMarcajeIds[] = $ultimoPersist->id_marcaje_asistencia;
                }
            }

            $allOk = ($casoAOk && $casoBOk && $casoCOk && $casoDOk);

            $this->record(
                'T8-38',
                'Protección CSRF real en marcajes de asistencia (F8-01)',
                'Sin token: 403 (0 persistencia) -> Token manipulado: 403 (0 persistencia) -> Salida sin token: 403 (0 persistencia) -> Token legítimo: 200 (1 persistencia)',
                sprintf('Caso A: %s (HTTP %d, BD: %d), Caso B: %s (HTTP %d, BD: %d), Caso C: %s (HTTP %d, BD: %d), Caso D: %s (HTTP %d, BD: %d)',
                    $casoAOk ? 'OK' : 'ERR', $resCasoA['code'], $countCasoA - $countInicial,
                    $casoBOk ? 'OK' : 'ERR', $resCasoB['code'], $countCasoB - $countInicial,
                    $casoCOk ? 'OK' : 'ERR', $resCasoC['code'], $countCasoC - $countInicial,
                    $casoDOk ? 'OK' : 'ERR', $resCasoD['code'], $countCasoD - $countInicial
                ),
                $allOk
            );
        } finally {
            $this->terminateProcess($pid, $process, $pipes);
            @unlink($serverLog);
            if (file_exists($cookieDocente)) {
                @unlink($cookieDocente);
            }
        }
    }

    /**
     * T8-39: Manejo de errores de Geolocation API (F8-02).
     * Verificación en frontend (harness JS) de:
     * - PERMISSION_DENIED (código 1) -> mensaje claro, formulario no enviado, botones re-habilitados.
     * - POSITION_UNAVAILABLE (código 2) -> mensaje claro, formulario no enviado.
     * - TIMEOUT (código 3) -> mensaje claro, formulario no enviado.
     * - Incompatibilidad de navigator.geolocation -> advertencia clara al usuario.
     * Y verificación en backend de que solicitudes sin coordenadas son rechazadas (HTTP 422, sin fallback simulado).
     */
    private function testT8_39_ManejoErroresGeolocationApi(): void
    {
        // 1. Ejecutar harness JavaScript en Node.js
        $harnessPath = dirname(__DIR__) . '/tests/harness_geolocation.js';
        $nodeCmd = 'node "' . $harnessPath . '"';
        $output = (string) @shell_exec($nodeCmd);
        $json = json_decode(trim($output), true);

        $jsOk = ($json !== null && ($json['ok'] ?? false) === true);
        $caseA = $json['results']['caseA'] ?? false; // PERMISSION_DENIED
        $caseB = $json['results']['caseB'] ?? false; // POSITION_UNAVAILABLE
        $caseC = $json['results']['caseC'] ?? false; // TIMEOUT
        $caseD = $json['results']['caseD'] ?? false; // Incompatibilidad API

        // 2. Verificación en backend: el servidor rechaza peticiones sin coordenadas y no aplica fallback ficticio
        $controller = new DocenteAsistenciaController($this->authService, $this->asistenciaService, $this->portalDocenteService);
        $this->loginAsUser($this->fixtureUserDocente1Id);

        $countInicial = (int) $this->db->query("SELECT COUNT(*) FROM marcajes_asistencia WHERE id_docente = {$this->fixtureDocente1Id}")->fetchColumn();

        // Petición con campos vacíos
        $reqVacio = new Request('POST', '/docente/asistencia/entrada', [], ['latitud' => '', 'longitud' => ''], [
            'accept' => 'application/json',
            'x-requested-with' => 'xmlhttprequest',
        ]);
        $resVacio = $controller->marcarEntrada($reqVacio);
        $bodyVacio = json_decode($resVacio->getContent(), true);

        // Petición completamente sin coordenadas
        $reqAusente = new Request('POST', '/docente/asistencia/entrada', [], [], [
            'accept' => 'application/json',
            'x-requested-with' => 'xmlhttprequest',
        ]);
        $resAusente = $controller->marcarEntrada($reqAusente);
        $bodyAusente = json_decode($resAusente->getContent(), true);

        $countFinal = (int) $this->db->query("SELECT COUNT(*) FROM marcajes_asistencia WHERE id_docente = {$this->fixtureDocente1Id}")->fetchColumn();

        $backendOk = ($resVacio->getStatusCode() === 422
            && ($bodyVacio['exito'] ?? false) === false
            && $resAusente->getStatusCode() === 422
            && ($bodyAusente['exito'] ?? false) === false
            && $countFinal === $countInicial);

        $isOk = ($jsOk && $backendOk);

        $this->record(
            'T8-39',
            'Manejo de errores de Geolocation API en cliente y backend sin fallback ficticio (F8-02)',
            'JS maneja PERMISSION_DENIED, POSITION_UNAVAILABLE, TIMEOUT y sin soporte; backend rechaza coordenadas vacías con HTTP 422 y 0 persistencia',
            sprintf('JS: CaseA=%s, CaseB=%s, CaseC=%s, CaseD=%s | Backend: Vacio=%d, Ausente=%d, Persistencia=%d',
                $caseA ? 'OK' : 'ERR', $caseB ? 'OK' : 'ERR', $caseC ? 'OK' : 'ERR', $caseD ? 'OK' : 'ERR',
                $resVacio->getStatusCode(), $resAusente->getStatusCode(), $countFinal - $countInicial
            ),
            $isOk
        );
    }

    /**
     * T8-40: Validación estricta de coordenadas recibidas en marcaje (F8-03).
     * Casos evaluados:
     * - Latitud > 90, Latitud < -90
     * - Longitud > 180, Longitud < -180
     * - Latitud / Longitud cadenas alfanuméricas no numéricas
     * - Latitud / Longitud cadenas vacías
     * - Latitud / Longitud null
     * - Latitud / Longitud INF / NAN
     * - Coordenadas ausentes
     * Verificación:
     * - Código HTTP 422 en todos los casos
     * - exito: false con mensaje controlado
     * - 0 persistencia en marcajes_asistencia
     * - 0 exposición de trazas internas o errores SQL
     */
    private function testT8_40_ValidacionEstrictaCoordenadas(): void
    {
        $controller = new DocenteAsistenciaController($this->authService, $this->asistenciaService, $this->portalDocenteService);
        $this->loginAsUser($this->fixtureUserDocente1Id);

        // Asegurar que no haya entrada pendiente
        $ultimo = $this->asistenciaRepo->findUltimoMarcajeDocente($this->fixtureDocente1Id);
        if ($ultimo !== null && $ultimo->isEntrada()) {
            $this->asistenciaService->registrarSalida($this->fixtureDocente1Id, $this->fixtureUserDocente1Id, [
                'latitud' => self::ESCUELA_LAT,
                'longitud' => self::ESCUELA_LON,
            ]);
        }

        $casos = [
            'lat > 90' => ['latitud' => 90.000001, 'longitud' => -90.506882],
            'lat < -90' => ['latitud' => -90.000001, 'longitud' => -90.506882],
            'lon > 180' => ['latitud' => 14.634915, 'longitud' => 180.000001],
            'lon < -180' => ['latitud' => 14.634915, 'longitud' => -180.000001],
            'lat string no numérico' => ['latitud' => 'no_valida', 'longitud' => -90.506882],
            'lon string no numérico' => ['latitud' => 14.634915, 'longitud' => 'invalida_lon'],
            'lat cadena vacía' => ['latitud' => '', 'longitud' => -90.506882],
            'lon cadena vacía' => ['latitud' => 14.634915, 'longitud' => ''],
            'lat null' => ['latitud' => null, 'longitud' => -90.506882],
            'lon null' => ['latitud' => 14.634915, 'longitud' => null],
            'lat INF' => ['latitud' => 'INF', 'longitud' => -90.506882],
            'lon NAN' => ['latitud' => 14.634915, 'longitud' => 'NAN'],
            'campos ausentes' => [],
        ];

        $countInicial = (int) $this->db->query("SELECT COUNT(*) FROM marcajes_asistencia WHERE id_docente = {$this->fixtureDocente1Id}")->fetchColumn();

        $fallas = [];
        $totalCasos = count($casos);
        $casosSuperados = 0;

        foreach ($casos as $nombreCaso => $payload) {
            $req = new Request('POST', '/docente/asistencia/entrada', [], $payload, [
                'accept' => 'application/json',
                'x-requested-with' => 'xmlhttprequest',
            ]);
            $res = $controller->marcarEntrada($req);
            $content = $res->getContent();
            $body = json_decode($content, true);

            $es422 = ($res->getStatusCode() === 422);
            $esExitoFalso = ($body !== null && ($body['exito'] ?? null) === false);
            $tieneMensaje = !empty($body['error'] ?? '');
            $sinTraza = !str_contains($content, 'Stack trace')
                && !str_contains($content, 'PDOException')
                && !str_contains($content, 'SQLSTATE');

            if ($es422 && $esExitoFalso && $tieneMensaje && $sinTraza) {
                $casosSuperados++;
            } else {
                $fallas[] = sprintf('%s (HTTP %d, exito=%s, sinTraza=%s)',
                    $nombreCaso,
                    $res->getStatusCode(),
                    var_export($body['exito'] ?? null, true),
                    $sinTraza ? 'OK' : 'ERR'
                );
            }
        }

        $countFinal = (int) $this->db->query("SELECT COUNT(*) FROM marcajes_asistencia WHERE id_docente = {$this->fixtureDocente1Id}")->fetchColumn();
        $persistenciaCero = ($countFinal === $countInicial);

        $isOk = ($casosSuperados === $totalCasos && $persistenciaCero && empty($fallas));

        $this->record(
            'T8-40',
            'Validación estricta de coordenadas recibidas en marcaje (F8-03)',
            '13/13 casos inválidos devuelven HTTP 422 con error controlado, 0 persistencia y 0 trazas expuestas',
            sprintf('%d/%d casos superados, persistencia en BD: %d, fallas: %s',
                $casosSuperados,
                $totalCasos,
                $countFinal - $countInicial,
                empty($fallas) ? 'Ninguna' : implode('; ', $fallas)
            ),
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
        echo "RESUMEN DE PRUEBAS DE FASE 8: ASISTENCIA Y GEOLOCALIZACIÓN\n";
        echo "============================================================\n";
        printf("Total ejecutadas : %d\n", $this->total);
        printf("Exitosas (OK)    : %d\n", $this->passed);
        printf("Fallidas (FALLO) : %d\n", $this->failed);
        echo "============================================================\n";

        if ($this->failed === 0) {
            echo "RESULTADO: TODAS LAS PRUEBAS DE FASE 8 SUPERADAS SATISFACTORIAMENTE (100% GREEN).\n";
        } else {
            echo "RESULTADO: EXISTEN PRUEBAS FALLIDAS.\n";
            exit(1);
        }
    }
}

$suite = new Phase8TestSuite();
$suite->run();
