<?php

declare(strict_types=1);

/**
 * Suite de Pruebas Automatizadas — Fase 12
 * Reportes Institucionales (RF-29 y RF-30)
 * Sistema Web — Escuela Oficial Rural Mixta Lo de Fuentes
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Controllers\AdminReporteController;
use App\Services\ReporteService;
use App\Repositories\ReporteRepository;
use App\Repositories\DocenteRepository;
use App\Repositories\PeriodoRepository;
use App\Repositories\GradoRepository;
use App\Repositories\SeccionRepository;
use App\Repositories\InscripcionRepository;

class Phase12TestSuite
{
    private PDO $db;
    private array $results = [];

    // IDs de Fixtures controlados para limpieza absoluta
    private array $createdUserIds = [];
    private array $createdDocenteIds = [];
    private array $createdMarcajeIds = [];
    private array $createdInscripcionIds = [];
    private array $createdEstudianteIds = [];
    private array $createdEncargadoIds = [];
    private array $createdSeccionIds = [];
    private array $createdGradoPeriodoIds = [];
    private array $createdGradoIds = [];
    private array $createdPeriodoIds = [];
    private array $createdConfigUbicacionIds = [];

    // Referencias a fixtures principales
    private int $fixtureAdminUserId = 0;
    private int $fixtureDocenteUserId = 0;
    private int $fixtureDocente1Id = 0;
    private int $fixtureDocente2Id = 0;
    private int $fixturePeriodo1Id = 0;
    private int $fixturePeriodo2Id = 0;
    private int $fixtureGrado1Id = 0;
    private int $fixtureSeccion1Id = 0; // Capacidad 2, ocupación 2 (LLENO)
    private int $fixtureSeccion2Id = 0; // Capacidad 5, ocupación 1 (DISPONIBLE)

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function run(): void
    {
        echo "============================================================\n";
        echo "SUITE DE PRUEBAS AUTOMATIZADAS — FASE 12\n";
        echo "Reportes Institucionales: Asistencia (RF-29) e Inscripciones/Cupos (RF-30)\n";
        echo "============================================================\n\n";

        $this->cleanPreviousArtifacts();
        $this->setupFixtures();

        try {
            // Bloque 1: Trazabilidad y Arquitectura
            $this->testT12_01_TrazabilidadDocumental();

            // Bloque 2: RF-29 — Reporte de Asistencia Docente
            $this->testT12_02_ReporteAsistenciaDatosReales();
            $this->testT12_03_FiltroDocenteEspecifico();
            $this->testT12_04_FiltroPeriodoEscolarAsistencia();
            $this->testT12_05_FiltroRangoFechasAsistencia();
            $this->testT12_06_ValidacionConsistenciaFechasAsistencia();
            $this->testT12_07_FiltroTipoMarcajeEntradaSalida();
            $this->testT12_08_FiltrosSinCoincidenciasAsistencia();
            $this->testT12_09_InmutabilidadMarcajesDesdeReporte();
            $this->testT12_10_MinimaExposicionPrivacidadAsistencia();

            // Bloque 3: RF-30 — Consulta de Inscripciones y Cupos
            $this->testT12_11_ConsultaGeneralCuposPorSeccion();
            $this->testT12_12_CalculoCuposReutilizaReglaInstitucional();
            $this->testT12_13_SeccionLlenaEstadoLleno();
            $this->testT12_14_SeccionDisponibleCalculoSaldo();
            $this->testT12_15_FiltroPeriodoEscolarInscripciones();
            $this->testT12_16_FiltroGradoInscripciones();
            $this->testT12_17_FiltroSeccionInscripciones();
            $this->testT12_18_FiltroEstadoInscripcion();
            $this->testT12_19_FiltrosSinCoincidenciasInscripciones();
            $this->testT12_20_MinimaExposicionPrivacidadEstudiantes();

            // Bloque 4: Seguridad y Autorización (RBAC, SQLi, XSS)
            $this->testT12_21_MitigacionInyeccionSqlFiltros();
            $this->testT12_22_PrevencionXssVistasReportes();
            $this->testT12_23_ParametrosInvalidosControlados();
            $this->testT12_24_RbacAccesoAnonimoRedirigeLogin();
            $this->testT12_25_RbacRolDocenteDenegado403();

            // Bloque 5: Pruebas HTTP Reales con Servidor Embebido
            $this->testT12_26_FlujoHttpRealReporteAsistencia();
            $this->testT12_27_FlujoHttpRealReporteInscripciones();
            $this->testT12_28_FlujoHttpRealRbacYAnonimo();

            // Bloque 6: Control de Base de Datos y Limpieza
            $this->testT12_29_IntegridadSchemaSinTablasReportes();
            $this->testT12_30_LimpiezaTotalFixturesSinContaminacion();

        } finally {
            $this->cleanup();
        }

        $this->printSummary();
    }

    private function cleanPreviousArtifacts(): void
    {
        Session::reset();

        // Eliminar posibles residuos previos de pruebas de Fase 12 respetando integridad referencial
        $this->db->exec("DELETE FROM marcajes_asistencia WHERE id_docente IN (SELECT id_docente FROM docentes WHERE codigo_docente LIKE 'DOC-F12%')");
        $this->db->exec("DELETE FROM configuraciones_ubicacion WHERE descripcion LIKE 'Geocerca F12%'");
        $this->db->exec("DELETE FROM docentes WHERE codigo_docente LIKE 'DOC-F12%'");

        $inscripciones = $this->db->query("SELECT id_inscripcion, id_estudiante, id_encargado FROM inscripciones WHERE codigo_confirmacion LIKE 'F12-%'")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($inscripciones)) {
            $iIds = implode(',', array_map('intval', array_column($inscripciones, 'id_inscripcion')));
            $eIds = implode(',', array_map('intval', array_column($inscripciones, 'id_estudiante')));
            $encIds = implode(',', array_map('intval', array_column($inscripciones, 'id_encargado')));
            $this->db->exec("DELETE FROM inscripciones WHERE id_inscripcion IN ($iIds)");
            $this->db->exec("DELETE FROM estudiantes_encargados WHERE id_estudiante IN ($eIds)");
            $this->db->exec("DELETE FROM estudiantes WHERE id_estudiante IN ($eIds)");
            $this->db->exec("DELETE FROM encargados WHERE id_encargado IN ($encIds)");
        }

        $secciones = $this->db->query("SELECT id_seccion FROM secciones WHERE codigo LIKE 'F12-%'")->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($secciones)) {
            $sIds = implode(',', array_map('intval', $secciones));
            $this->db->exec("DELETE FROM inscripciones WHERE id_seccion IN ($sIds)");
            $this->db->exec("DELETE FROM secciones WHERE id_seccion IN ($sIds)");
        }

        $gradosPeriodos = $this->db->query("SELECT id_grado_periodo FROM grados_periodos WHERE id_grado IN (SELECT id_grado FROM grados WHERE codigo LIKE 'GRD-F12%')")->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($gradosPeriodos)) {
            $gpIds = implode(',', array_map('intval', $gradosPeriodos));
            $this->db->exec("DELETE FROM secciones WHERE id_grado_periodo IN ($gpIds)");
            $this->db->exec("DELETE FROM grados_periodos WHERE id_grado_periodo IN ($gpIds)");
        }

        $this->db->exec("DELETE FROM grados WHERE codigo LIKE 'GRD-F12%'");
        $this->db->exec("DELETE FROM periodos_escolares WHERE nombre LIKE 'Ciclo F12%'");

        $adminUsers = $this->db->query("SELECT id_usuario FROM usuarios WHERE nombre_usuario LIKE 'adm_f12_%' OR nombre_usuario LIKE 'doc_f12_%'")->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($adminUsers)) {
            $inUsers = implode(',', array_map('intval', $adminUsers));
            $this->db->exec("DELETE FROM usuarios WHERE id_usuario IN ($inUsers)");
        }
    }

    private function setupFixtures(): void
    {
        $stmtRolAdmin = $this->db->query("SELECT id_rol FROM roles WHERE nombre IN ('ADMINISTRACION', 'ADMINISTRADOR') LIMIT 1");
        $rolAdminId = (int) $stmtRolAdmin->fetchColumn();

        $stmtRolDoc = $this->db->query("SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'");
        $rolDocId = (int) $stmtRolDoc->fetchColumn();

        $passHash = password_hash('PassFase12Report!', PASSWORD_BCRYPT);

        // 1. Usuario Administrador de Prueba
        $uAdmin = 'adm_f12_' . rand(1000, 9999);
        $stmtU = $this->db->prepare('INSERT INTO usuarios (id_rol, nombre_usuario, correo, clave_hash, nombres, apellidos, estado) VALUES (:r, :u, :c, :p, "Admin", "Reportes F12", "ACTIVO")');
        $stmtU->execute([':r' => $rolAdminId, ':u' => $uAdmin, ':c' => $uAdmin . '@escuela.test', ':p' => $passHash]);
        $this->fixtureAdminUserId = (int) $this->db->lastInsertId();
        $this->createdUserIds[] = $this->fixtureAdminUserId;

        // 2. Docente 1 (Activo)
        $uDoc1 = 'doc_f12_1_' . rand(1000, 9999);
        $stmtU->execute([':r' => $rolDocId, ':u' => $uDoc1, ':c' => $uDoc1 . '@escuela.test', ':p' => $passHash]);
        $uDoc1Id = (int) $this->db->lastInsertId();
        $this->createdUserIds[] = $uDoc1Id;

        $cDoc1 = 'DOC-F12-01-' . rand(100, 999);
        $stmtDoc = $this->db->prepare('INSERT INTO docentes (id_usuario, codigo_docente, fecha_ingreso, estado) VALUES (:u, :cod, "2024-01-15", "ACTIVO")');
        $stmtDoc->execute([':u' => $uDoc1Id, ':cod' => $cDoc1]);
        $this->fixtureDocente1Id = (int) $this->db->lastInsertId();
        $this->createdDocenteIds[] = $this->fixtureDocente1Id;

        // 3. Docente 2 (Para aislamiento de filtros)
        $uDoc2 = 'doc_f12_2_' . rand(1000, 9999);
        $stmtU->execute([':r' => $rolDocId, ':u' => $uDoc2, ':c' => $uDoc2 . '@escuela.test', ':p' => $passHash]);
        $uDoc2Id = (int) $this->db->lastInsertId();
        $this->fixtureDocenteUserId = $uDoc2Id; // Para prueba de RBAC docente
        $this->createdUserIds[] = $uDoc2Id;

        $cDoc2 = 'DOC-F12-02-' . rand(100, 999);
        $stmtDoc->execute([':u' => $uDoc2Id, ':cod' => $cDoc2]);
        $this->fixtureDocente2Id = (int) $this->db->lastInsertId();
        $this->createdDocenteIds[] = $this->fixtureDocente2Id;

        // 4. Configuración de Geocerca Institucional de Prueba
        $stmtGeo = $this->db->prepare('INSERT INTO configuraciones_ubicacion (id_usuario_creador, descripcion, latitud, longitud, radio_metros, fecha_inicio, activa) VALUES (:u, "Geocerca F12 Institucional", 14.634915, -90.506882, 50.0, CURRENT_TIMESTAMP(6), 1)');
        $stmtGeo->execute([':u' => $this->fixtureAdminUserId]);
        $idConfig = (int) $this->db->lastInsertId();
        $this->createdConfigUbicacionIds[] = $idConfig;

        // 5. Marcajes de Asistencia Docente
        // Docente 1: ENTRADA (2026-03-10 07:30:00) y SALIDA (2026-03-10 13:00:00)
        $stmtM = $this->db->prepare('INSERT INTO marcajes_asistencia (id_docente, id_usuario, id_configuracion_ubicacion, fecha_hora, tipo, latitud_dispositivo, longitud_dispositivo, precision_metros, distancia_metros, dentro_radio) VALUES (:d, :u, :c, :fh, :t, 14.634915, -90.506882, 10.0, 5.0, 1)');
        
        $stmtM->execute([':d' => $this->fixtureDocente1Id, ':u' => $uDoc1Id, ':c' => $idConfig, ':fh' => '2026-03-10 07:30:00', ':t' => 'ENTRADA']);
        $this->createdMarcajeIds[] = (int) $this->db->lastInsertId();

        $stmtM->execute([':d' => $this->fixtureDocente1Id, ':u' => $uDoc1Id, ':c' => $idConfig, ':fh' => '2026-03-10 13:00:00', ':t' => 'SALIDA']);
        $this->createdMarcajeIds[] = (int) $this->db->lastInsertId();

        // Docente 1: ENTRADA (2026-04-15 07:25:00)
        $stmtM->execute([':d' => $this->fixtureDocente1Id, ':u' => $uDoc1Id, ':c' => $idConfig, ':fh' => '2026-04-15 07:25:00', ':t' => 'ENTRADA']);
        $this->createdMarcajeIds[] = (int) $this->db->lastInsertId();

        // Docente 2: ENTRADA (2026-03-10 07:45:00)
        $stmtM->execute([':d' => $this->fixtureDocente2Id, ':u' => $uDoc2Id, ':c' => $idConfig, ':fh' => '2026-03-10 07:45:00', ':t' => 'ENTRADA']);
        $this->createdMarcajeIds[] = (int) $this->db->lastInsertId();

        // 6. Períodos Escolares
        // Período 1: 2026 (Planificado)
        $this->db->exec("INSERT INTO periodos_escolares (nombre, fecha_inicio, fecha_fin, estado) VALUES ('Ciclo F12 2026', '2026-01-01', '2026-10-31', 'PLANIFICADO')");
        $this->fixturePeriodo1Id = (int) $this->db->lastInsertId();
        $this->createdPeriodoIds[] = $this->fixturePeriodo1Id;

        // Período 2: 2025 (Cerrado)
        $this->db->exec("INSERT INTO periodos_escolares (nombre, fecha_inicio, fecha_fin, estado) VALUES ('Ciclo F12 2025', '2025-01-01', '2025-10-31', 'CERRADO')");
        $this->fixturePeriodo2Id = (int) $this->db->lastInsertId();
        $this->createdPeriodoIds[] = $this->fixturePeriodo2Id;

        // 7. Grado
        $this->db->exec("INSERT INTO grados (codigo, nombre, orden, activo) VALUES ('GRD-F12-1', 'Primero Primaria F12', 30, 1)");
        $this->fixtureGrado1Id = (int) $this->db->lastInsertId();
        $this->createdGradoIds[] = $this->fixtureGrado1Id;

        // 8. Grado-Período
        $this->db->exec("INSERT INTO grados_periodos (id_periodo_escolar, id_grado, habilitado) VALUES ({$this->fixturePeriodo1Id}, {$this->fixtureGrado1Id}, 1)");
        $idGp = (int) $this->db->lastInsertId();
        $this->createdGradoPeriodoIds[] = $idGp;

        // 9. Secciones
        // Sección 1: Capacidad = 2 (se llenará con 2 inscripciones)
        $stmtSec = $this->db->prepare('INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada) VALUES (:gp, :c, :n, :cap, 1)');
        $stmtSec->execute([':gp' => $idGp, ':c' => 'F12-A', ':n' => 'Sección Llena F12', ':cap' => 2]);
        $this->fixtureSeccion1Id = (int) $this->db->lastInsertId();
        $this->createdSeccionIds[] = $this->fixtureSeccion1Id;

        // Sección 2: Capacidad = 5 (tendrá 1 inscripción -> 4 disponibles)
        $stmtSec->execute([':gp' => $idGp, ':c' => 'F12-B', ':n' => 'Sección Disponible F12', ':cap' => 5]);
        $this->fixtureSeccion2Id = (int) $this->db->lastInsertId();
        $this->createdSeccionIds[] = $this->fixtureSeccion2Id;

        // 10. Estudiantes y Encargados para Sección 1 (2 Confirmadas -> LLENO)
        $stmtEst = $this->db->prepare('INSERT INTO estudiantes (nombres, apellidos, estado) VALUES (:n, :a, "ACTIVO")');
        $stmtEnc = $this->db->prepare('INSERT INTO encargados (nombres, apellidos, telefono, correo) VALUES (:n, :a, "5555-1234", "enc@f12.test")');
        $stmtEE = $this->db->prepare('INSERT INTO estudiantes_encargados (id_estudiante, id_encargado, parentesco, es_principal) VALUES (:e, :enc, "MADRE", 1)');
        $stmtInsConf = $this->db->prepare('INSERT INTO inscripciones (id_estudiante, id_encargado, id_seccion, codigo_confirmacion, estado, fecha_confirmacion) VALUES (:e, :enc, :s, :c, :est, "2026-02-01 10:00:00")');
        $stmtInsPend = $this->db->prepare('INSERT INTO inscripciones (id_estudiante, id_encargado, id_seccion, codigo_confirmacion, estado, fecha_confirmacion) VALUES (:e, :enc, :s, NULL, "PENDIENTE", NULL)');

        // Estudiante 1 en Sección 1 (CONFIRMADA)
        $stmtEst->execute([':n' => 'Mateo', ':a' => 'Morales Gómez']);
        $e1 = (int) $this->db->lastInsertId();
        $this->createdEstudianteIds[] = $e1;
        $stmtEnc->execute([':n' => 'Sofía', ':a' => 'Gómez']);
        $enc1 = (int) $this->db->lastInsertId();
        $this->createdEncargadoIds[] = $enc1;
        $stmtEE->execute([':e' => $e1, ':enc' => $enc1]);
        $stmtInsConf->execute([':e' => $e1, ':enc' => $enc1, ':s' => $this->fixtureSeccion1Id, ':c' => 'F12-CONF-001', ':est' => 'CONFIRMADA']);
        $this->createdInscripcionIds[] = (int) $this->db->lastInsertId();

        // Estudiante 2 en Sección 1 (PENDIENTE -> cuenta para ocupación según regla Fase 6)
        $stmtEst->execute([':n' => 'Valentina', ':a' => 'López Pérez']);
        $e2 = (int) $this->db->lastInsertId();
        $this->createdEstudianteIds[] = $e2;
        $stmtEnc->execute([':n' => 'Carlos', ':a' => 'López']);
        $enc2 = (int) $this->db->lastInsertId();
        $this->createdEncargadoIds[] = $enc2;
        $stmtEE->execute([':e' => $e2, ':enc' => $enc2]);
        $stmtInsPend->execute([':e' => $e2, ':enc' => $enc2, ':s' => $this->fixtureSeccion1Id]);
        $this->createdInscripcionIds[] = (int) $this->db->lastInsertId();

        // Estudiante 3 en Sección 2 (CONFIRMADA)
        $stmtEst->execute([':n' => 'Santiago', ':a' => 'Castillo Solís']);
        $e3 = (int) $this->db->lastInsertId();
        $this->createdEstudianteIds[] = $e3;
        $stmtEnc->execute([':n' => 'Elena', ':a' => 'Solís']);
        $enc3 = (int) $this->db->lastInsertId();
        $this->createdEncargadoIds[] = $enc3;
        $stmtEE->execute([':e' => $e3, ':enc' => $enc3]);
        $stmtInsConf->execute([':e' => $e3, ':enc' => $enc3, ':s' => $this->fixtureSeccion2Id, ':c' => 'F12-CONF-003', ':est' => 'CONFIRMADA']);
        $this->createdInscripcionIds[] = (int) $this->db->lastInsertId();

        // Estudiante 4 en Sección 2 (ANULADA -> no cuenta para ocupación)
        $stmtEst->execute([':n' => 'Camila', ':a' => 'Ruiz Méndez']);
        $e4 = (int) $this->db->lastInsertId();
        $this->createdEstudianteIds[] = $e4;
        $stmtEnc->execute([':n' => 'Pedro', ':a' => 'Ruiz']);
        $enc4 = (int) $this->db->lastInsertId();
        $this->createdEncargadoIds[] = $enc4;
        $stmtEE->execute([':e' => $e4, ':enc' => $enc4]);
        $stmtInsConf->execute([':e' => $e4, ':enc' => $enc4, ':s' => $this->fixtureSeccion2Id, ':c' => 'F12-ANUL-004', ':est' => 'ANULADA']);
        $this->createdInscripcionIds[] = (int) $this->db->lastInsertId();
    }

    private function cleanup(): void
    {
        Session::reset();

        try {
            if (!empty($this->createdMarcajeIds)) {
                $inM = implode(',', array_map('intval', $this->createdMarcajeIds));
                $this->db->exec("DELETE FROM marcajes_asistencia WHERE id_marcaje_asistencia IN ($inM)");
            }

            if (!empty($this->createdInscripcionIds)) {
                $inI = implode(',', array_map('intval', $this->createdInscripcionIds));
                $this->db->exec("DELETE FROM inscripciones WHERE id_inscripcion IN ($inI)");
            }

            if (!empty($this->createdEstudianteIds) && !empty($this->createdEncargadoIds)) {
                $inE = implode(',', array_map('intval', $this->createdEstudianteIds));
                $inEnc = implode(',', array_map('intval', $this->createdEncargadoIds));
                $this->db->exec("DELETE FROM estudiantes_encargados WHERE id_estudiante IN ($inE)");
                $this->db->exec("DELETE FROM estudiantes WHERE id_estudiante IN ($inE)");
                $this->db->exec("DELETE FROM encargados WHERE id_encargado IN ($inEnc)");
            }

            if (!empty($this->createdSeccionIds)) {
                $inS = implode(',', array_map('intval', $this->createdSeccionIds));
                $this->db->exec("DELETE FROM secciones WHERE id_seccion IN ($inS)");
            }

            if (!empty($this->createdGradoPeriodoIds)) {
                $inGp = implode(',', array_map('intval', $this->createdGradoPeriodoIds));
                $this->db->exec("DELETE FROM grados_periodos WHERE id_grado_periodo IN ($inGp)");
            }

            if (!empty($this->createdGradoIds)) {
                $inG = implode(',', array_map('intval', $this->createdGradoIds));
                $this->db->exec("DELETE FROM grados WHERE id_grado IN ($inG)");
            }

            if (!empty($this->createdPeriodoIds)) {
                $inP = implode(',', array_map('intval', $this->createdPeriodoIds));
                $this->db->exec("DELETE FROM periodos_escolares WHERE id_periodo_escolar IN ($inP)");
            }

            if (!empty($this->createdDocenteIds)) {
                $inD = implode(',', array_map('intval', $this->createdDocenteIds));
                $this->db->exec("DELETE FROM docentes WHERE id_docente IN ($inD)");
            }

            if (!empty($this->createdConfigUbicacionIds)) {
                $inCfg = implode(',', array_map('intval', $this->createdConfigUbicacionIds));
                $this->db->exec("DELETE FROM configuraciones_ubicacion WHERE id_configuracion_ubicacion IN ($inCfg)");
            }

            if (!empty($this->createdUserIds)) {
                $inU = implode(',', array_map('intval', $this->createdUserIds));
                $this->db->exec("DELETE FROM usuarios WHERE id_usuario IN ($inU)");
            }
        } catch (\Throwable $e) {
            // Silencioso en caso de error durante limpieza final
        }
    }

    private function record(string $code, string $description, string $expected, string $obtained, bool $passed): void
    {
        $status = $passed ? 'OK' : 'FALLO';
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

    // =========================================================================
    // BLOQUE 1: Trazabilidad y Arquitectura
    // =========================================================================

    private function testT12_01_TrazabilidadDocumental(): void
    {
        $matriz = (string) file_get_contents(dirname(__DIR__) . '/docs/fase-0/matriz-trazabilidad.md');
        $hasRf29 = str_contains($matriz, 'RF-29');
        $hasRf30 = str_contains($matriz, 'RF-30');
        $hasRf03Pendiente = str_contains($matriz, 'RF-03') && str_contains($matriz, '13');

        $ok = $hasRf29 && $hasRf30 && $hasRf03Pendiente;
        $this->record('T12-01', 'Trazabilidad documental oficial: RF-29 y RF-30 en Fase 12; RF-03 pendiente en Fase 13', 'RF-29 y RF-30 presentes en Fase 12, RF-03 en Fase 13',
            sprintf('RF-29: %s | RF-30: %s | RF-03 Fase 13: %s', $hasRf29 ? 'SÍ' : 'NO', $hasRf30 ? 'SÍ' : 'NO', $hasRf03Pendiente ? 'SÍ' : 'NO'), $ok);
    }

    // =========================================================================
    // BLOQUE 2: RF-29 — Reporte de Asistencia Docente
    // =========================================================================

    private function testT12_02_ReporteAsistenciaDatosReales(): void
    {
        $service = new ReporteService();
        $reporte = $service->generarReporteAsistencia([]);

        $marcajes = $reporte['marcajes'];
        $resumen = $reporte['resumen'];

        // Se deben encontrar al menos los 4 marcajes creados en fixtures
        $ok = count($marcajes) >= 4 && $resumen['total_marcajes'] >= 4 && $resumen['total_entradas'] >= 3 && $resumen['total_salidas'] >= 1;
        $this->record('T12-02', 'RF-29: Consulta básica de marcajes de asistencia por administración con datos reales', 'Al menos 4 marcajes, con desglose de entradas y salidas',
            sprintf('Total: %d | Entradas: %d | Salidas: %d', $resumen['total_marcajes'], $resumen['total_entradas'], $resumen['total_salidas']), $ok);
    }

    private function testT12_03_FiltroDocenteEspecifico(): void
    {
        $service = new ReporteService();
        $reporte = $service->generarReporteAsistencia(['id_docente' => $this->fixtureDocente1Id]);

        $marcajes = $reporte['marcajes'];
        $soloDoc1 = true;
        foreach ($marcajes as $m) {
            if ((int)$m['id_docente'] !== $this->fixtureDocente1Id) {
                $soloDoc1 = false;
                break;
            }
        }

        $ok = count($marcajes) === 3 && $soloDoc1 && $reporte['resumen']['docentes_registrados'] === 1;
        $this->record('T12-03', 'RF-29: Filtro por docente específico aísla exclusivamente sus marcajes', 'Exactamente 3 marcajes del Docente 1',
            sprintf('Marcajes obtenidos: %d | Solo Docente 1: %s', count($marcajes), $soloDoc1 ? 'SÍ' : 'NO'), $ok);
    }

    private function testT12_04_FiltroPeriodoEscolarAsistencia(): void
    {
        $service = new ReporteService();
        // Período 1 abarca 2026-01-01 a 2026-10-31 (todos los fixtures caen aquí)
        $reporte1 = $service->generarReporteAsistencia(['id_periodo_escolar' => $this->fixturePeriodo1Id]);
        // Período 2 abarca 2025-01-01 a 2025-10-31 (ningún fixture cae aquí)
        $reporte2 = $service->generarReporteAsistencia(['id_periodo_escolar' => $this->fixturePeriodo2Id]);

        $ok = count($reporte1['marcajes']) >= 4 && count($reporte2['marcajes']) === 0;
        $this->record('T12-04', 'RF-29: Filtro por período escolar acota automáticamente las fechas al ciclo lectivo', 'Período 2026 >= 4 marcajes, Período 2025 = 0 marcajes',
            sprintf('Período 2026: %d | Período 2025: %d', count($reporte1['marcajes']), count($reporte2['marcajes'])), $ok);
    }

    private function testT12_05_FiltroRangoFechasAsistencia(): void
    {
        $service = new ReporteService();
        // Rango de un solo día: 2026-03-10 (Docente 1 tiene 2 marcajes, Docente 2 tiene 1 -> total 3)
        $reporte = $service->generarReporteAsistencia([
            'fecha_inicio' => '2026-03-10',
            'fecha_fin' => '2026-03-10',
        ]);

        $ok = count($reporte['marcajes']) === 3 && $reporte['resumen']['total_entradas'] === 2 && $reporte['resumen']['total_salidas'] === 1;
        $this->record('T12-05', 'RF-29: Filtro por rango de fechas acota estrictamente la consulta', 'Exactamente 3 marcajes para el día 2026-03-10',
            sprintf('Marcajes obtenidos: %d (Entradas: %d, Salidas: %d)', count($reporte['marcajes']), $reporte['resumen']['total_entradas'], $reporte['resumen']['total_salidas']), $ok);
    }

    private function testT12_06_ValidacionConsistenciaFechasAsistencia(): void
    {
        $service = new ReporteService();
        $excepcionCapturada = false;
        $mensaje = '';

        try {
            $service->generarReporteAsistencia([
                'fecha_inicio' => '2026-05-01',
                'fecha_fin' => '2026-04-01', // Fecha fin < Fecha inicio
            ]);
        } catch (\InvalidArgumentException $e) {
            $excepcionCapturada = true;
            $mensaje = $e->getMessage();
        }

        $ok = $excepcionCapturada && str_contains($mensaje, 'fecha final no puede ser anterior');
        $this->record('T12-06', 'RF-29: Validación de consistencia temporal: rechazo estricto si fecha_fin < fecha_inicio', 'InvalidArgumentException con mensaje explicativo',
            sprintf('Excepción lanzada: %s | Mensaje: %s', $excepcionCapturada ? 'SÍ' : 'NO', $mensaje), $ok);
    }

    private function testT12_07_FiltroTipoMarcajeEntradaSalida(): void
    {
        $service = new ReporteService();
        $repEntradas = $service->generarReporteAsistencia(['id_docente' => $this->fixtureDocente1Id, 'tipo' => 'ENTRADA']);
        $repSalidas = $service->generarReporteAsistencia(['id_docente' => $this->fixtureDocente1Id, 'tipo' => 'SALIDA']);

        $ok = count($repEntradas['marcajes']) === 2 && count($repSalidas['marcajes']) === 1;
        $this->record('T12-07', 'RF-29: Filtro por tipo de marcaje discrimina con precisión entradas y salidas', 'Docente 1: 2 entradas y 1 salida',
            sprintf('Entradas: %d | Salidas: %d', count($repEntradas['marcajes']), count($repSalidas['marcajes'])), $ok);
    }

    private function testT12_08_FiltrosSinCoincidenciasAsistencia(): void
    {
        $service = new ReporteService();
        $reporte = $service->generarReporteAsistencia([
            'fecha_inicio' => '2020-01-01',
            'fecha_fin' => '2020-01-31',
        ]);

        $ok = empty($reporte['marcajes']) && $reporte['resumen']['total_marcajes'] === 0 && is_array($reporte['marcajes']);
        $this->record('T12-08', 'RF-29: Consulta con filtros sin coincidencias devuelve estructura vacía controlada sin error', 'Array vacío y métricas en cero',
            sprintf('Marcajes: %d | Métricas total: %d', count($reporte['marcajes']), $reporte['resumen']['total_marcajes']), $ok);
    }

    private function testT12_09_InmutabilidadMarcajesDesdeReporte(): void
    {
        $countAntes = (int) $this->db->query("SELECT COUNT(*) FROM marcajes_asistencia")->fetchColumn();
        $service = new ReporteService();
        $service->generarReporteAsistencia([]);
        $service->generarReporteAsistencia(['id_docente' => $this->fixtureDocente1Id]);
        $countDespues = (int) $this->db->query("SELECT COUNT(*) FROM marcajes_asistencia")->fetchColumn();

        $ok = $countAntes === $countDespues && $countAntes > 0;
        $this->record('T12-09', 'RF-29: Inmutabilidad estricta: generar reportes no muta registros de asistencia', 'Conteo previo igual a conteo posterior',
            sprintf('Antes: %d | Después: %d', $countAntes, $countDespues), $ok);
    }

    private function testT12_10_MinimaExposicionPrivacidadAsistencia(): void
    {
        $service = new ReporteService();
        $reporte = $service->generarReporteAsistencia(['id_docente' => $this->fixtureDocente1Id]);
        $fila = $reporte['marcajes'][0] ?? [];

        // No debe contener contraseñas, hashes, ni coordenadas directas del dispositivo
        $hasPassword = isset($fila['clave_hash']) || isset($fila['password']);
        $hasLatLong = isset($fila['latitud_dispositivo']) || isset($fila['longitud_dispositivo']);

        $ok = !$hasPassword && !$hasLatLong && isset($fila['distancia_metros']) && isset($fila['dentro_radio']);
        $this->record('T12-10', 'RF-29 / RNF-04: Mínima exposición: reporte no expone coordenadas privadas ni credenciales', 'Sin password, sin lat/lng privadas; solo distancia y validación institucional',
            sprintf('Contraseña expuesta: %s | Coordenadas expuestas: %s', $hasPassword ? 'SÍ' : 'NO', $hasLatLong ? 'SÍ' : 'NO'), $ok);
    }

    // =========================================================================
    // BLOQUE 3: RF-30 — Consulta de Inscripciones y Cupos
    // =========================================================================

    private function testT12_11_ConsultaGeneralCuposPorSeccion(): void
    {
        $service = new ReporteService();
        $rep = $service->generarReporteInscripciones(['id_periodo_escolar' => $this->fixturePeriodo1Id]);

        $secciones = $rep['secciones_cupos'];
        $estudiantes = $rep['estudiantes'];
        $resumen = $rep['resumen'];

        $ok = count($secciones) === 2 && count($estudiantes) === 4 && $resumen['capacidad_total'] === 7;
        $this->record('T12-11', 'RF-30: Consulta general de cupos por sección y listado de inscritos por período', '2 secciones, 4 inscripciones, capacidad total 7',
            sprintf('Secciones: %d | Inscripciones: %d | Capacidad total: %d', count($secciones), count($estudiantes), $resumen['capacidad_total']), $ok);
    }

    private function testT12_12_CalculoCuposReutilizaReglaInstitucional(): void
    {
        $service = new ReporteService();
        $rep = $service->generarReporteInscripciones(['id_seccion' => $this->fixtureSeccion1Id]);
        $sec1 = $rep['secciones_cupos'][0] ?? [];

        // Sección 1 tiene: Capacidad 2, 1 CONFIRMADA + 1 PENDIENTE = 2 Ocupados
        $repoInscripcion = new InscripcionRepository();
        $ocupadosRepo = $repoInscripcion->countOcupacionBySeccion($this->fixtureSeccion1Id);

        $ok = (int)$sec1['cupos_ocupados'] === $ocupadosRepo && (int)$sec1['cupos_ocupados'] === 2;
        $this->record('T12-12', 'RF-30: Cálculo de cupos reutiliza estrictamente la regla institucional de Fase 6', 'Cupos ocupados = 2 coincidiendo con countOcupacionBySeccion',
            sprintf('Ocupados en reporte: %d | Ocupados por InscripcionRepository: %d', (int)($sec1['cupos_ocupados'] ?? -1), $ocupadosRepo), $ok);
    }

    private function testT12_13_SeccionLlenaEstadoLleno(): void
    {
        $service = new ReporteService();
        $rep = $service->generarReporteInscripciones(['id_seccion' => $this->fixtureSeccion1Id]);
        $sec1 = $rep['secciones_cupos'][0] ?? [];

        $ok = ($sec1['estado_cupo'] ?? '') === 'LLENO' && (int)$sec1['cupos_disponibles'] === 0 && (int)$sec1['cupos_ocupados'] === 2;
        $this->record('T12-13', 'RF-30: Sección con capacidad colmada reporta estado LLENO y cero disponibles', 'Estado = LLENO, disponibles = 0',
            sprintf('Estado: %s | Disponibles: %d | Ocupados: %d / %d', $sec1['estado_cupo'] ?? '', (int)($sec1['cupos_disponibles'] ?? -1), (int)($sec1['cupos_ocupados'] ?? -1), (int)($sec1['capacidad'] ?? -1)), $ok);
    }

    private function testT12_14_SeccionDisponibleCalculoSaldo(): void
    {
        $service = new ReporteService();
        $rep = $service->generarReporteInscripciones(['id_seccion' => $this->fixtureSeccion2Id]);
        $sec2 = $rep['secciones_cupos'][0] ?? [];

        // Sección 2: Capacidad 5, 1 CONFIRMADA (la ANULADA no descuenta) -> 1 Ocupado, 4 Disponibles
        $ok = ($sec2['estado_cupo'] ?? '') === 'DISPONIBLE' && (int)$sec2['cupos_disponibles'] === 4 && (int)$sec2['cupos_ocupados'] === 1;
        $this->record('T12-14', 'RF-30: Sección con cupos disponibles reporta estado DISPONIBLE y saldo exacto', 'Estado = DISPONIBLE, 1 ocupado, 4 disponibles (la ANULADA no resta cupo)',
            sprintf('Estado: %s | Disponibles: %d | Ocupados: %d / %d', $sec2['estado_cupo'] ?? '', (int)($sec2['cupos_disponibles'] ?? -1), (int)($sec2['cupos_ocupados'] ?? -1), (int)($sec2['capacidad'] ?? -1)), $ok);
    }

    private function testT12_15_FiltroPeriodoEscolarInscripciones(): void
    {
        $service = new ReporteService();
        $repP1 = $service->generarReporteInscripciones(['id_periodo_escolar' => $this->fixturePeriodo1Id]);
        $repP2 = $service->generarReporteInscripciones(['id_periodo_escolar' => $this->fixturePeriodo2Id]);

        $ok = count($repP1['estudiantes']) === 4 && count($repP2['estudiantes']) === 0;
        $this->record('T12-15', 'RF-30: Filtro de inscripciones por ciclo escolar aísla las inscripciones del ciclo', 'Ciclo 2026: 4 estudiantes, Ciclo 2025: 0 estudiantes',
            sprintf('Ciclo 2026: %d | Ciclo 2025: %d', count($repP1['estudiantes']), count($repP2['estudiantes'])), $ok);
    }

    private function testT12_16_FiltroGradoInscripciones(): void
    {
        $service = new ReporteService();
        $rep = $service->generarReporteInscripciones(['id_grado' => $this->fixtureGrado1Id]);

        $todosGrado1 = true;
        foreach ($rep['estudiantes'] as $est) {
            if ((int)$est['id_grado'] !== $this->fixtureGrado1Id) {
                $todosGrado1 = false;
                break;
            }
        }

        $ok = count($rep['estudiantes']) === 4 && $todosGrado1;
        $this->record('T12-16', 'RF-30: Filtro por grado aísla exclusivamente los estudiantes del grado solicitado', '4 estudiantes, todos del Grado 1',
            sprintf('Estudiantes: %d | Todos Grado 1: %s', count($rep['estudiantes']), $todosGrado1 ? 'SÍ' : 'NO'), $ok);
    }

    private function testT12_17_FiltroSeccionInscripciones(): void
    {
        $service = new ReporteService();
        $repSec1 = $service->generarReporteInscripciones(['id_seccion' => $this->fixtureSeccion1Id]);
        $repSec2 = $service->generarReporteInscripciones(['id_seccion' => $this->fixtureSeccion2Id]);

        $ok = count($repSec1['estudiantes']) === 2 && count($repSec2['estudiantes']) === 2;
        $this->record('T12-17', 'RF-30: Filtro por sección aísla los estudiantes asignados a dicha sección', 'Sección 1: 2 estudiantes, Sección 2: 2 estudiantes',
            sprintf('Sección 1: %d | Sección 2: %d', count($repSec1['estudiantes']), count($repSec2['estudiantes'])), $ok);
    }

    private function testT12_18_FiltroEstadoInscripcion(): void
    {
        $service = new ReporteService();
        $repConf = $service->generarReporteInscripciones(['id_seccion' => $this->fixtureSeccion1Id, 'estado' => 'CONFIRMADA']);
        $repPend = $service->generarReporteInscripciones(['id_seccion' => $this->fixtureSeccion1Id, 'estado' => 'PENDIENTE']);

        $ok = count($repConf['estudiantes']) === 1 && count($repPend['estudiantes']) === 1;
        $this->record('T12-18', 'RF-30: Filtro por estado de inscripción discrimina confirmadas, pendientes y anuladas', 'Sección 1: 1 confirmada y 1 pendiente',
            sprintf('Confirmadas: %d | Pendientes: %d', count($repConf['estudiantes']), count($repPend['estudiantes'])), $ok);
    }

    private function testT12_19_FiltrosSinCoincidenciasInscripciones(): void
    {
        $service = new ReporteService();
        $rep = $service->generarReporteInscripciones(['id_periodo_escolar' => $this->fixturePeriodo2Id]);

        $ok = empty($rep['estudiantes']) && empty($rep['secciones_cupos']) && $rep['resumen']['total_estudiantes_inscritos'] === 0;
        $this->record('T12-19', 'RF-30: Consulta sin coincidencias devuelve estructura vacía controlada sin error', 'Listados vacíos y métricas en cero',
            sprintf('Secciones: %d | Estudiantes: %d', count($rep['secciones_cupos']), count($rep['estudiantes'])), $ok);
    }

    private function testT12_20_MinimaExposicionPrivacidadEstudiantes(): void
    {
        $service = new ReporteService();
        $rep = $service->generarReporteInscripciones(['id_periodo_escolar' => $this->fixturePeriodo1Id]);
        $est1 = $rep['estudiantes'][0] ?? [];

        // No debe exponer teléfonos ni correos de encargados, ni contraseñas
        $hasTelefono = isset($est1['encargado_telefono']) || isset($est1['telefono']);
        $hasCorreo = isset($est1['encargado_correo']) || isset($est1['correo']);
        $hasPassword = isset($est1['clave_hash']);

        $ok = !$hasTelefono && !$hasCorreo && !$hasPassword && isset($est1['codigo_confirmacion']) && isset($est1['estudiante_nombres']);
        $this->record('T12-20', 'RF-30 / RNF-04: Privacidad de menores: no se exponen teléfonos, correos ni datos de contacto', 'Sin teléfono ni correo de encargados en la consulta administrativa',
            sprintf('Teléfono expuesto: %s | Correo expuesto: %s', $hasTelefono ? 'SÍ' : 'NO', $hasCorreo ? 'SÍ' : 'NO'), $ok);
    }

    // =========================================================================
    // BLOQUE 4: Seguridad y Autorización (RBAC, SQLi, XSS)
    // =========================================================================

    private function testT12_21_MitigacionInyeccionSqlFiltros(): void
    {
        $service = new ReporteService();
        $payloadSql = "1' OR '1'='1";
        $excepcionCapturada = false;

        // Intentar inyección SQL en fechas y parámetros
        try {
            $service->generarReporteAsistencia([
                'fecha_inicio' => $payloadSql,
            ]);
        } catch (\InvalidArgumentException) {
            $excepcionCapturada = true;
        }

        $repo = new ReporteRepository();
        // Prueba de inyección en repositorio: la consulta preparada no debe romper ni ejecutar sintaxis SQL
        $res = $repo->findMarcajesAsistencia([
            'tipo' => "ENTRADA' OR '1'='1",
        ]);

        $ok = $excepcionCapturada && empty($res);
        $this->record('T12-21', 'Seguridad: Mitigación de Inyección SQL en parámetros de filtros de reportes', 'Validación previa rechaza payload y consulta preparada neutraliza inyección',
            sprintf('Validación fecha bloqueó inyección: %s | Repositorio preparado devolvió 0: %s', $excepcionCapturada ? 'SÍ' : 'NO', empty($res) ? 'SÍ' : 'NO'), $ok);
    }

    private function testT12_22_PrevencionXssVistasReportes(): void
    {
        // Verificar que las vistas views/admin/reportes/*.php utilicen e() para todos los campos dinámicos
        $v1 = (string) file_get_contents(dirname(__DIR__) . '/views/admin/reportes/asistencia.php');
        $v2 = (string) file_get_contents(dirname(__DIR__) . '/views/admin/reportes/inscripciones.php');

        $sinEscapeV1 = preg_match('/<\?=\s+\$(m|doc|resumen|filtros)\[/', $v1);
        $sinEscapeV2 = preg_match('/<\?=\s+\$(sec|est|resumen|filtros)\[/', $v2);

        $usaEscapeV1 = str_contains($v1, 'e(');
        $usaEscapeV2 = str_contains($v2, 'e(');

        $ok = $sinEscapeV1 === 0 && $sinEscapeV2 === 0 && $usaEscapeV1 && $usaEscapeV2;
        $this->record('T12-22', 'Seguridad: Prevención de Cross-Site Scripting (XSS) mediante escape e() en vistas', 'Cero impresiones sin e() en vistas de reportes',
            sprintf('Sin escape en asistencia: %s | Sin escape en inscripciones: %s', $sinEscapeV1 ? 'DETECTADO' : 'NINGUNO', $sinEscapeV2 ? 'DETECTADO' : 'NINGUNO'), $ok);
    }

    private function testT12_23_ParametrosInvalidosControlados(): void
    {
        $service = new ReporteService();
        $e1 = false;
        $e2 = false;

        try {
            $service->generarReporteAsistencia(['id_docente' => 99999999]); // Docente inexistente
        } catch (\InvalidArgumentException) {
            $e1 = true;
        }

        try {
            $service->generarReporteInscripciones(['estado' => 'ESTADO_INEXISTENTE']); // Estado no permitido
        } catch (\InvalidArgumentException) {
            $e2 = true;
        }

        $ok = $e1 && $e2;
        $this->record('T12-23', 'Seguridad: Parámetros maliciosos o inexistentes rechazados de forma controlada', 'InvalidArgumentException en docente inexistente y estado fuera de whitelist',
            sprintf('Docente inexistente rechazado: %s | Estado inválido rechazado: %s', $e1 ? 'SÍ' : 'NO', $e2 ? 'SÍ' : 'NO'), $ok);
    }

    private function testT12_24_RbacAccesoAnonimoRedirigeLogin(): void
    {
        Session::reset(); // Sin autenticar
        $app = new \App\Core\App();
        $app->boot();

        $req = new Request('GET', '/administracion/reportes');
        $res = $app->handle($req);

        $ok = $res->getStatusCode() === 302 && str_contains($res->getHeader('Location') ?? '', '/login');
        $this->record('T12-24', 'RBAC: Acceso anónimo a /administracion/reportes redirige a /login (HTTP 302)', 'HTTP 302 a /login',
            sprintf('Status: %d | Location: %s', $res->getStatusCode(), $res->getHeader('Location') ?? ''), $ok);
    }

    private function testT12_25_RbacRolDocenteDenegado403(): void
    {
        Session::reset();
        // Simular inicio de sesión de usuario con rol DOCENTE
        $_SESSION['auth_user_id'] = $this->fixtureDocenteUserId;

        $app = new \App\Core\App();
        $app->boot();

        $reqAsistencia = new Request('GET', '/administracion/reportes/asistencia');
        $resAsistencia = $app->handle($reqAsistencia);

        $reqInscripciones = new Request('GET', '/administracion/reportes/inscripciones');
        $resInscripciones = $app->handle($reqInscripciones);

        $ok = $resAsistencia->getStatusCode() === 403 && $resInscripciones->getStatusCode() === 403;
        $this->record('T12-25', 'RBAC: Rol DOCENTE denegado con HTTP 403 Forbidden en reportes administrativos', 'HTTP 403 en asistencia y 403 en inscripciones',
            sprintf('Asistencia status: %d | Inscripciones status: %d', $resAsistencia->getStatusCode(), $resInscripciones->getStatusCode()), $ok);
    }

    // =========================================================================
    // BLOQUE 5: Pruebas HTTP Reales con Servidor Embebido
    // =========================================================================

    private function testT12_26_FlujoHttpRealReporteAsistencia(): void
    {
        echo "\n>>> Iniciando servidor web PHP embebido para pruebas HTTP reales de Fase 12 (puerto 8100)...\n";

        $port = 8100;
        $docRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public';
        $serverLog = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php_server_f12_' . uniqid() . '.log';
        $descriptors = [0 => ['pipe', 'r'], 1 => ['file', $serverLog, 'a'], 2 => ['file', $serverLog, 'a']];
        $cmd = sprintf('"%s" -S 127.0.0.1:%d -t "%s"', PHP_BINARY, $port, $docRoot);
        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));

        if (!is_resource($process)) {
            $this->record('T12-26', 'Flujo HTTP real: Reporte de asistencia con servidor embebido', 'Servidor iniciado', 'No se pudo iniciar el proceso del servidor', false);
            return;
        }
        $pid = (int) (proc_get_status($process)['pid'] ?? 0);

        // Esperar conexión
        $conectado = false;
        for ($i = 0; $i < 40; $i++) {
            $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
            if ($fp) {
                fclose($fp);
                $conectado = true;
                break;
            }
            usleep(100000);
        }

        if (!$conectado) {
            $this->terminateProcess($pid, $process, $pipes);
            @unlink($serverLog);
            $this->record('T12-26', 'Flujo HTTP real: Reporte de asistencia con servidor embebido', 'Servidor conectado', 'Timeout al conectar con el servidor', false);
            return;
        }

        $cookie = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_f12_' . uniqid() . '.txt';
        $http = function (string $url, string $method = 'GET', ?array $post = null, ?string $cookieFile = null): array {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            if ($cookieFile !== null) {
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            }
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post ?? []));
            }
            $raw = curl_exec($ch);
            if ($raw === false) {
                $err = curl_error($ch);
                curl_close($ch);
                return ['code' => 0, 'headers' => '', 'body' => $err, 'location' => ''];
            }
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $hs = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);
            $headers = substr((string) $raw, 0, $hs);
            $body = substr((string) $raw, $hs);
            $loc = preg_match('/^Location:\s*([^\r\n]+)/mi', $headers, $m) === 1 ? trim($m[1]) : '';

            return ['code' => $code, 'headers' => $headers, 'body' => $body, 'location' => $loc];
        };

        $base = "http://127.0.0.1:$port";

        try {
            // 1. Obtener login y token CSRF
            $loginForm = $http("$base/login", 'GET', null, $cookie);
            $csrf = preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $loginForm['body'], $m) === 1 ? $m[1] : '';

            // 2. Iniciar sesión como ADMINISTRADOR
            $stmtU = $this->db->query("SELECT nombre_usuario FROM usuarios WHERE id_usuario = {$this->fixtureAdminUserId}");
            $adminUser = (string) $stmtU->fetchColumn();

            $loginRes = $http("$base/login", 'POST', [
                '_token' => $csrf,
                'nombre_usuario' => $adminUser,
                'password' => 'PassFase12Report!',
            ], $cookie);

            // 3. Consultar Reporte de Asistencia vía HTTP
            $repUrl = "$base/administracion/reportes/asistencia?id_docente={$this->fixtureDocente1Id}&tipo=ENTRADA";
            $res = $http($repUrl, 'GET', null, $cookie);

            $ok = $res['code'] === 200 && str_contains($res['body'], 'Reporte de Asistencia Docente') && str_contains($res['body'], 'ENTRADA') && str_contains($res['body'], 'Geocerca F12 Institucional');
            $this->record('T12-26', 'Flujo HTTP real: Consulta de reporte de asistencia por administrador autenticado', 'HTTP 200 con tabla de asistencia, tipo ENTRADA y datos de geocerca',
                sprintf('Status: %d | Contiene título: %s | Contiene ENTRADA: %s', $res['code'], str_contains($res['body'], 'Reporte de Asistencia Docente') ? 'SÍ' : 'NO', str_contains($res['body'], 'ENTRADA') ? 'SÍ' : 'NO'), $ok);

        } finally {
            $this->terminateProcess($pid, $process, $pipes);
            @unlink($serverLog);
            @unlink($cookie);
        }
    }

    private function testT12_27_FlujoHttpRealReporteInscripciones(): void
    {
        $port = 8100;
        $docRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public';
        $serverLog = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php_server_f12_insc_' . uniqid() . '.log';
        $descriptors = [0 => ['pipe', 'r'], 1 => ['file', $serverLog, 'a'], 2 => ['file', $serverLog, 'a']];
        $cmd = sprintf('"%s" -S 127.0.0.1:%d -t "%s"', PHP_BINARY, $port, $docRoot);
        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));

        if (!is_resource($process)) {
            $this->record('T12-27', 'Flujo HTTP real: Consulta de inscripciones y cupos con servidor embebido', 'Servidor iniciado', 'No se pudo iniciar el proceso', false);
            return;
        }
        $pid = (int) (proc_get_status($process)['pid'] ?? 0);

        $conectado = false;
        for ($i = 0; $i < 40; $i++) {
            $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
            if ($fp) {
                fclose($fp);
                $conectado = true;
                break;
            }
            usleep(100000);
        }

        if (!$conectado) {
            $this->terminateProcess($pid, $process, $pipes);
            @unlink($serverLog);
            $this->record('T12-27', 'Flujo HTTP real: Consulta de inscripciones y cupos con servidor embebido', 'Servidor conectado', 'Timeout de conexión', false);
            return;
        }

        $cookie = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_f12_insc_' . uniqid() . '.txt';
        $http = function (string $url, string $method = 'GET', ?array $post = null, ?string $cookieFile = null): array {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            if ($cookieFile !== null) {
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            }
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post ?? []));
            }
            $raw = curl_exec($ch);
            if ($raw === false) {
                $err = curl_error($ch);
                curl_close($ch);
                return ['code' => 0, 'headers' => '', 'body' => $err, 'location' => ''];
            }
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $hs = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);
            $headers = substr((string) $raw, 0, $hs);
            $body = substr((string) $raw, $hs);
            $loc = preg_match('/^Location:\s*([^\r\n]+)/mi', $headers, $m) === 1 ? trim($m[1]) : '';

            return ['code' => $code, 'headers' => $headers, 'body' => $body, 'location' => $loc];
        };

        $base = "http://127.0.0.1:$port";

        try {
            // Login de administrador
            $loginForm = $http("$base/login", 'GET', null, $cookie);
            $csrf = preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $loginForm['body'], $m) === 1 ? $m[1] : '';

            $stmtU = $this->db->query("SELECT nombre_usuario FROM usuarios WHERE id_usuario = {$this->fixtureAdminUserId}");
            $adminUser = (string) $stmtU->fetchColumn();

            $http("$base/login", 'POST', [
                '_token' => $csrf,
                'nombre_usuario' => $adminUser,
                'password' => 'PassFase12Report!',
            ], $cookie);

            // Consultar Inscripciones y Cupos
            $url = "$base/administracion/reportes/inscripciones?id_periodo_escolar={$this->fixturePeriodo1Id}";
            $res = $http($url, 'GET', null, $cookie);

            $ok = $res['code'] === 200 && str_contains($res['body'], 'Consulta de Inscripciones y Cupos') && str_contains($res['body'], 'LLENO') && str_contains($res['body'], 'DISPONIBLE') && str_contains($res['body'], 'F12-CONF-001');
            $this->record('T12-27', 'Flujo HTTP real: Consulta de inscripciones y cupos por administrador autenticado', 'HTTP 200 con tabla de cupos (LLENO y DISPONIBLE) y listado de inscritos',
                sprintf('Status: %d | Contiene LLENO: %s | Contiene DISPONIBLE: %s | Contiene código: %s', $res['code'], str_contains($res['body'], 'LLENO') ? 'SÍ' : 'NO', str_contains($res['body'], 'DISPONIBLE') ? 'SÍ' : 'NO', str_contains($res['body'], 'F12-CONF-001') ? 'SÍ' : 'NO'), $ok);

        } finally {
            $this->terminateProcess($pid, $process, $pipes);
            @unlink($serverLog);
            @unlink($cookie);
        }
    }

    private function testT12_28_FlujoHttpRealRbacYAnonimo(): void
    {
        $port = 8100;
        $docRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public';
        $serverLog = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php_server_f12_rbac_' . uniqid() . '.log';
        $descriptors = [0 => ['pipe', 'r'], 1 => ['file', $serverLog, 'a'], 2 => ['file', $serverLog, 'a']];
        $cmd = sprintf('"%s" -S 127.0.0.1:%d -t "%s"', PHP_BINARY, $port, $docRoot);
        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));

        if (!is_resource($process)) {
            $this->record('T12-28', 'Flujo HTTP real: Verificación de acceso anónimo y no autorizado', 'Servidor iniciado', 'No se pudo iniciar el proceso', false);
            return;
        }
        $pid = (int) (proc_get_status($process)['pid'] ?? 0);

        $conectado = false;
        for ($i = 0; $i < 40; $i++) {
            $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
            if ($fp) {
                fclose($fp);
                $conectado = true;
                break;
            }
            usleep(100000);
        }

        if (!$conectado) {
            $this->terminateProcess($pid, $process, $pipes);
            @unlink($serverLog);
            $this->record('T12-28', 'Flujo HTTP real: Verificación de acceso anónimo y no autorizado', 'Servidor conectado', 'Timeout de conexión', false);
            return;
        }

        $cookieDoc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_f12_doc_' . uniqid() . '.txt';
        $http = function (string $url, string $method = 'GET', ?array $post = null, ?string $cookieFile = null): array {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            if ($cookieFile !== null) {
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            }
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post ?? []));
            }
            $raw = curl_exec($ch);
            if ($raw === false) {
                $err = curl_error($ch);
                curl_close($ch);
                return ['code' => 0, 'headers' => '', 'body' => $err, 'location' => ''];
            }
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $hs = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);
            $headers = substr((string) $raw, 0, $hs);
            $body = substr((string) $raw, $hs);
            $loc = preg_match('/^Location:\s*([^\r\n]+)/mi', $headers, $m) === 1 ? trim($m[1]) : '';

            return ['code' => $code, 'headers' => $headers, 'body' => $body, 'location' => $loc];
        };

        $base = "http://127.0.0.1:$port";

        try {
            // 1. Acceso anónimo a /administracion/reportes -> HTTP 302 a /login
            $resAnon = $http("$base/administracion/reportes", 'GET');

            // 2. Login con rol DOCENTE
            $loginForm = $http("$base/login", 'GET', null, $cookieDoc);
            $csrf = preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $loginForm['body'], $m) === 1 ? $m[1] : '';

            $stmtU = $this->db->query("SELECT nombre_usuario FROM usuarios WHERE id_usuario = {$this->fixtureDocenteUserId}");
            $docUser = (string) $stmtU->fetchColumn();

            $http("$base/login", 'POST', [
                '_token' => $csrf,
                'nombre_usuario' => $docUser,
                'password' => 'PassFase12Report!',
            ], $cookieDoc);

            // 3. Intento de acceso docente a /administracion/reportes -> HTTP 403
            $resDoc = $http("$base/administracion/reportes", 'GET', null, $cookieDoc);

            $ok = $resAnon['code'] === 302 && $resDoc['code'] === 403;
            $this->record('T12-28', 'Flujo HTTP real: Acceso anónimo redirige a login (302) y docente recibe 403 Forbidden', 'Anónimo HTTP 302, Docente HTTP 403',
                sprintf('Anónimo code: %d (Location: %s) | Docente code: %d', $resAnon['code'], $resAnon['location'], $resDoc['code']), $ok);

        } finally {
            $this->terminateProcess($pid, $process, $pipes);
            @unlink($serverLog);
            @unlink($cookieDoc);
        }
    }

    // =========================================================================
    // BLOQUE 6: Control de Base de Datos y Limpieza
    // =========================================================================

    private function testT12_29_IntegridadSchemaSinTablasReportes(): void
    {
        $schema = (string) file_get_contents(dirname(__DIR__) . '/database/schema.sql');
        $hasReportTables = preg_match('/CREATE TABLE\s+\w*(reporte|estadistica|resumen_cupos)\w*/i', $schema) === 1;

        $dbTables = $this->db->query("SHOW TABLES LIKE '%reporte%'")->fetchAll(PDO::FETCH_COLUMN);

        $ok = !$hasReportTables && empty($dbTables);
        $this->record('T12-29', 'Base de datos: schema.sql intacto sin tablas ni campos redundantes de reportes', 'Cero tablas de reportes o estadísticas en schema.sql y en base de datos',
            sprintf('Tablas de reporte en schema.sql: %s | Tablas en MySQL: %d', $hasReportTables ? 'SÍ' : 'NO', count($dbTables)), $ok);
    }

    private function testT12_30_LimpiezaTotalFixturesSinContaminacion(): void
    {
        // Verificar que el mecanismo de limpieza elimina todos los fixtures creados
        $this->cleanup();

        $marcsRestantes = (int) $this->db->query("SELECT COUNT(*) FROM marcajes_asistencia WHERE latitud_dispositivo = 14.634915 AND longitud_dispositivo = -90.506882 AND id_usuario IN (" . implode(',', array_map('intval', $this->createdUserIds)) . ")")->fetchColumn();
        $inscrRestantes = (int) $this->db->query("SELECT COUNT(*) FROM inscripciones WHERE codigo_confirmacion LIKE 'F12-%'")->fetchColumn();
        $secsRestantes = (int) $this->db->query("SELECT COUNT(*) FROM secciones WHERE codigo LIKE 'F12-%'")->fetchColumn();
        $usersRestantes = (int) $this->db->query("SELECT COUNT(*) FROM usuarios WHERE nombre_usuario LIKE 'adm_f12_%' OR nombre_usuario LIKE 'doc_f12_%'")->fetchColumn();

        $totalRestantes = $marcsRestantes + $inscrRestantes + $secsRestantes + $usersRestantes;

        $ok = $totalRestantes === 0;
        $this->record('T12-30', 'Gestión de pruebas: Fase 12 no deja nuevos fixtures o datos temporales permanentes después de ejecutar su suite', 'Todos los fixtures creados por Fase 12 eliminados al 100%',
            sprintf('Marcajes residuales: %d | Inscripciones residuales: %d | Secciones residuales: %d | Usuarios residuales: %d', $marcsRestantes, $inscrRestantes, $secsRestantes, $usersRestantes), $ok);
    }

    private function terminateProcess(int $pid, mixed $process, array $pipes): void
    {
        foreach ($pipes as $p) {
            if (is_resource($p)) {
                fclose($p);
            }
        }
        if (DIRECTORY_SEPARATOR === '\\') {
            @exec("taskkill /F /T /PID $pid 2>NUL");
        } else {
            @posix_kill($pid, 9);
        }
        if (is_resource($process)) {
            @proc_close($process);
        }
    }

    private function printSummary(): void
    {
        $total = count($this->results);
        $passed = count(array_filter($this->results, fn($r) => $r['status'] === 'OK'));
        $failed = $total - $passed;

        echo "\n============================================================\n";
        echo "RESUMEN DE PRUEBAS DE FASE 12: REPORTES (RF-29 y RF-30)\n";
        echo "============================================================\n";
        printf("Total ejecutadas : %d\n", $total);
        printf("Exitosas (OK)    : %d\n", $passed);
        printf("Fallidas (FALLO) : %d\n", $failed);
        echo "============================================================\n";

        if ($failed === 0) {
            echo "RESULTADO: TODAS LAS PRUEBAS DE FASE 12 SUPERADAS SATISFACTORIAMENTE (100% GREEN).\n\n";
        } else {
            echo "RESULTADO: EXISTEN PRUEBAS FALLIDAS EN FASE 12.\n\n";
        }
    }
}

$suite = new Phase12TestSuite();
$suite->run();
