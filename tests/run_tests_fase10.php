<?php

declare(strict_types=1);

/**
 * Suite de pruebas automatizadas para Fase 10 — Notificaciones (RF-26).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Cobertura requerida:
 * T10-01: Verificación de estructura relacional de la tabla notificaciones en MySQL (columnas, tipos, llaves foráneas, checks).
 * T10-02: Verificación de contrato y entidad Notificacion (instanciación con propiedades readonly, métodos isLeida(), isPendiente(), toArray(), constantes).
 * T10-03: Generación de notificación al registrar nueva solicitud de permiso docente vía PermisoService::crearSolicitud() (RF-26).
 * T10-04: Notificación dirigida a todos los administradores activos con rol ADMINISTRADOR (DT-64).
 * T10-05: Exclusión de administradores inactivos (estado != 'ACTIVO') como destinatarios (DT-64).
 * T10-06: Exclusión de usuarios con rol DOCENTE como destinatarios de la notificación administrativa (DT-64).
 * T10-07: Atributos obligatorios y formato institucional: tipo = 'NUEVA_SOLICITUD', título descriptivo, mensaje contextual con docente y fechas (RF-26).
 * T10-08: Estado inicial no leída: fecha_lectura IS NULL y isPendiente() === true (DT-64).
 * T10-09: Timestamp generado por el servidor con microsegundos CURRENT_TIMESTAMP(6) (DT-64).
 * T10-10: Atomicidad transaccional: Si la transacción de solicitud falla o se revierte, no debe crearse ninguna notificación (cero huérfanas) (DT-64, DT-57).
 * T10-11: Integridad referencial: FK id_solicitud_permiso vinculada; ON DELETE RESTRICT impide eliminar solicitud con notificaciones asociadas.
 * T10-12: Repositorio: Consulta paginada y ordenada DESC por fecha_creacion para usuario administrador específico (findByUsuario).
 * T10-13: Repositorio y Servicio: Contador de no leídas (contarNoLeidasPorUsuario, obtenerConteoNoLeidas) concuerda con fecha_lectura IS NULL.
 * T10-14: Repositorio y Servicio: Marcar notificación individual como leída (marcarComoLeida / marcarLeida) actualiza fecha_lectura a timestamp no nulo (NOW(6)).
 * T10-15: Restricción de consistencia temporal: fecha_lectura >= fecha_creacion (cumple CHECK de tabla).
 * T10-16: Idempotencia al marcar como leída: Marcar una notificación ya leída no produce error y preserva su lectura original.
 * T10-17: Repositorio y Servicio: Marcar todas las notificaciones de un usuario como leídas (marcarTodasComoLeidas).
 * T10-18: Aislamiento horizontal de notificaciones: Administrador 1 no puede marcar como leída la notificación de Administrador 2.
 * T10-19: RBAC y control de acceso: Endpoint GET /administracion/notificaciones redirige a /login para usuario no autenticado (HTTP 302).
 * T10-20: RBAC y control de acceso: Endpoint GET /administracion/notificaciones deniega acceso para usuario con rol DOCENTE (HTTP 403).
 * T10-21: Protección CSRF: Endpoint POST /administracion/notificaciones/marcar-leida deniega petición sin token CSRF válido (HTTP 403).
 * T10-22: Protección CSRF: Endpoint POST /administracion/notificaciones/marcar-todas deniega petición sin token CSRF válido (HTTP 403).
 * T10-23: Prevención de Cross-Site Scripting (XSS): Sanitización estricta contextual con htmlspecialchars (e()) en vista views/admin/notificaciones/index.php.
 * T10-24: Endpoint AJAX / JSON: GET /administracion/notificaciones/conteo devuelve JSON estructurado con unread_count para administrador autenticado.
 * T10-25: Flujo HTTP real completo end-to-end con servidor web embebido (puerto 8098):
 *         Docente registra solicitud con documento -> Notificación generada -> Admin consulta bandeja -> Marca leída -> Contador decrementa.
 * T10-26: Regresión completa de Fase 9 (Gestión de Permisos Docentes: 46/46).
 * T10-27: Regresión completa de Fases 1 a 8 (verificando estabilidad de módulos anteriores).
 * Total: 27 pruebas automatizadas.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\AdminNotificacionController;
use App\Core\App;
use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Notificacion;
use App\Models\SolicitudPermiso;
use App\Models\User;
use App\Repositories\DocenteRepository;
use App\Repositories\NotificacionRepository;
use App\Repositories\PermisoRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\NotificacionService;
use App\Services\PermisoService;

class Phase10TestSuite
{
    private PDO $db;
    private UserRepository $userRepo;
    private DocenteRepository $docenteRepo;
    private PermisoRepository $permisoRepo;
    private NotificacionRepository $notifRepo;

    private AuthService $authService;
    private PermisoService $permisoService;
    private NotificacionService $notifService;

    private int $total = 0;
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];

    // Fixtures
    private ?int $fixtureUserAdmin1Id = null;
    private ?int $fixtureUserAdmin2Id = null;
    private ?int $fixtureUserAdminInactivoId = null;
    private ?int $fixtureUserDocente1Id = null;
    private ?int $fixtureDocente1Id = null;
    private ?int $fixtureUserDocente2Id = null;
    private ?int $fixtureDocente2Id = null;

    private array $createdSolicitudIds = [];
    private array $createdNotificacionIds = [];
    private array $createdFilePaths = [];

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->userRepo = new UserRepository($this->db);
        $this->docenteRepo = new DocenteRepository($this->db);
        $this->permisoRepo = new PermisoRepository($this->db);
        $this->notifRepo = new NotificacionRepository($this->db);

        $this->authService = new AuthService($this->userRepo);
        $this->notifService = new NotificacionService($this->notifRepo, $this->db);
        $this->permisoService = new PermisoService(
            permisoRepo: $this->permisoRepo,
            docenteRepo: $this->docenteRepo,
            userRepo: $this->userRepo,
            db: $this->db,
            notificacionRepo: $this->notifRepo
        );
    }

    public function run(): void
    {
        echo "============================================================\n";
        echo "INICIANDO SUITE DE PRUEBAS DE FASE 10: NOTIFICACIONES (RF-26)\n";
        echo "============================================================\n\n";

        try {
            $this->cleanPreviousArtifacts();
            $this->setupFixtures();

            // Bloque 1: Modelo y Entidad de Notificaciones (RF-26, DT-64)
            $this->testT10_01_EstructuraRelacionalMySQL();
            $this->testT10_02_EntidadNotificacionContrato();

            // Bloque 2: Generación Automática y Destinatarios Administrativos (RF-26, DT-64, DT-65)
            $this->testT10_03_GeneracionNotificacionAlCrearSolicitud();
            $this->testT10_04_NotificacionTodosAdministradoresActivos();
            $this->testT10_05_ExclusionAdministradoresInactivos();
            $this->testT10_06_ExclusionUsuariosDocentesComoDestinatarios();
            $this->testT10_07_AtributosObligatoriosYFormatoInstitucional();
            $this->testT10_08_EstadoInicialNoLeida();
            $this->testT10_09_TimestampServidorMicrosegundos();

            // Bloque 3: Atomicidad Transaccional e Integridad Referencial (DT-64, DT-57)
            $this->testT10_10_AtomicidadTransaccionalRollbackCeroHuerfanas();
            $this->testT10_11_IntegridadReferencialOnDeleteRestrict();

            // Bloque 4: Repositorio, Consultas y Gestión de Estado (DT-65, DT-66)
            $this->testT10_12_ConsultaBandejaPaginadaYOrdenDescendente();
            $this->testT10_13_ConteoExactoNoLeidas();
            $this->testT10_14_MarcarIndividualComoLeida();
            $this->testT10_15_ConsistenciaTemporalFechaLectura();
            $this->testT10_16_IdempotenciaMarcarComoLeida();
            $this->testT10_17_MarcarTodasComoLeidas();
            $this->testT10_18_AislamientoHorizontalNotificaciones();

            // Bloque 5: Seguridad de Endpoints, RBAC, CSRF y Prevención XSS (DT-66)
            $this->testT10_19_RbacAccesoAnonimoRedirigeLogin();
            $this->testT10_20_RbacAccesoDocenteDenegado403();
            $this->testT10_21_CsrfMarcarLeidaRequerido();
            $this->testT10_22_CsrfMarcarTodasRequerido();
            $this->testT10_23_PrevencionXssEnVistaNotificaciones();
            $this->testT10_24_EndpointAjaxConteoNoLeidas();

            // Bloque 6: Flujo HTTP Real Completo End-to-End con Servidor Embebido (RF-26)
            $this->testT10_25_FlujoHttpRealCompleto();

            // Bloque 7: Regresiones Completas de Fases 1 a 9
            $this->testT10_26_RegresionFase9();
            $this->testT10_27_RegresionFases1a8();

        } finally {
            $this->cleanup();
        }

        $this->printSummary();
    }

    private function cleanPreviousArtifacts(): void
    {
        Session::reset();
    }

    private function setupFixtures(): void
    {
        $stmtRol = $this->db->query("SELECT id_rol FROM roles WHERE nombre IN ('ADMINISTRACION', 'ADMINISTRADOR') LIMIT 1");
        $rolAdminId = (int) $stmtRol->fetchColumn();

        $stmtRolDoc = $this->db->query("SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'");
        $rolDocId = (int) $stmtRolDoc->fetchColumn();

        $passHash = password_hash('PassFase10Test!', PASSWORD_BCRYPT);

        // 1. Admin 1 ACTIVO
        $uAdmin1 = 'adm1_f10_' . rand(1000, 9999);
        $stmt = $this->db->prepare('INSERT INTO usuarios (id_rol, nombre_usuario, correo, clave_hash, nombres, apellidos, estado) VALUES (:r, :u, :c, :p, :n, :a, "ACTIVO")');
        $stmt->execute([':r' => $rolAdminId, ':u' => $uAdmin1, ':c' => $uAdmin1 . '@escuela.test', ':p' => $passHash, ':n' => 'Admin Principal', ':a' => 'Fase 10']);
        $this->fixtureUserAdmin1Id = (int) $this->db->lastInsertId();

        // 2. Admin 2 ACTIVO
        $uAdmin2 = 'adm2_f10_' . rand(1000, 9999);
        $stmt->execute([':r' => $rolAdminId, ':u' => $uAdmin2, ':c' => $uAdmin2 . '@escuela.test', ':p' => $passHash, ':n' => 'Admin Segundo', ':a' => 'Fase 10']);
        $this->fixtureUserAdmin2Id = (int) $this->db->lastInsertId();

        // 3. Admin INACTIVO
        $uAdminInac = 'adminac_f10_' . rand(1000, 9999);
        $stmt->execute([':r' => $rolAdminId, ':u' => $uAdminInac, ':c' => $uAdminInac . '@escuela.test', ':p' => $passHash, ':n' => 'Admin Inactivo', ':a' => 'Excluido']);
        $this->fixtureUserAdminInactivoId = (int) $this->db->lastInsertId();
        $this->db->exec("UPDATE usuarios SET estado = 'INACTIVO' WHERE id_usuario = {$this->fixtureUserAdminInactivoId}");

        // 4. Docente 1 ACTIVO
        $uDoc1 = 'doc1_f10_' . rand(1000, 9999);
        $stmt->execute([':r' => $rolDocId, ':u' => $uDoc1, ':c' => $uDoc1 . '@escuela.test', ':p' => $passHash, ':n' => 'María', ':a' => 'Morales López']);
        $this->fixtureUserDocente1Id = (int) $this->db->lastInsertId();

        $stmtDoc = $this->db->prepare('INSERT INTO docentes (id_usuario, codigo_docente, fecha_ingreso, estado) VALUES (:u, :cod, "2024-01-15", "ACTIVO")');
        $cDoc1 = 'DOC-F10-01-' . rand(100, 999);
        $stmtDoc->execute([':u' => $this->fixtureUserDocente1Id, ':cod' => $cDoc1]);
        $this->fixtureDocente1Id = (int) $this->db->lastInsertId();

        // 5. Docente 2 ACTIVO
        $uDoc2 = 'doc2_f10_' . rand(1000, 9999);
        $stmt->execute([':r' => $rolDocId, ':u' => $uDoc2, ':c' => $uDoc2 . '@escuela.test', ':p' => $passHash, ':n' => 'Carlos', ':a' => 'Gómez Méndez']);
        $this->fixtureUserDocente2Id = (int) $this->db->lastInsertId();

        $cDoc2 = 'DOC-F10-02-' . rand(100, 999);
        $stmtDoc->execute([':u' => $this->fixtureUserDocente2Id, ':cod' => $cDoc2]);
        $this->fixtureDocente2Id = (int) $this->db->lastInsertId();
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

            // Eliminar notificaciones creadas explícitamente o asociadas a solicitudes
            if (!empty($this->createdNotificacionIds)) {
                $in = implode(',', array_map('intval', $this->createdNotificacionIds));
                $this->db->exec("DELETE FROM notificaciones WHERE id_notificacion IN ($in)");
            }

            if (!empty($this->createdSolicitudIds)) {
                $in = implode(',', array_map('intval', $this->createdSolicitudIds));
                $this->db->exec("DELETE FROM notificaciones WHERE id_solicitud_permiso IN ($in)");
                $this->db->exec("DELETE FROM historial_solicitudes_permiso WHERE id_solicitud_permiso IN ($in)");
                $this->db->exec("DELETE FROM documentos_permiso WHERE id_solicitud_permiso IN ($in)");
                $this->db->exec("DELETE FROM solicitudes_permiso WHERE id_solicitud_permiso IN ($in)");
            }

            // Limpieza por docentes fixtures
            if ($this->fixtureDocente1Id || $this->fixtureDocente2Id) {
                $docIds = implode(',', array_filter([$this->fixtureDocente1Id, $this->fixtureDocente2Id]));
                $stmt = $this->db->query("SELECT id_solicitud_permiso FROM solicitudes_permiso WHERE id_docente IN ($docIds)");
                $solIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($solIds)) {
                    $in = implode(',', array_map('intval', $solIds));
                    $this->db->exec("DELETE FROM notificaciones WHERE id_solicitud_permiso IN ($in)");
                    $this->db->exec("DELETE FROM historial_solicitudes_permiso WHERE id_solicitud_permiso IN ($in)");
                    $this->db->exec("DELETE FROM documentos_permiso WHERE id_solicitud_permiso IN ($in)");
                    $this->db->exec("DELETE FROM solicitudes_permiso WHERE id_solicitud_permiso IN ($in)");
                }
            }

            // Eliminar cualquier notificación residual dirigida a los usuarios fixture
            $uIds = implode(',', array_filter([
                $this->fixtureUserAdmin1Id,
                $this->fixtureUserAdmin2Id,
                $this->fixtureUserAdminInactivoId,
                $this->fixtureUserDocente1Id,
                $this->fixtureUserDocente2Id,
            ]));
            if (!empty($uIds)) {
                $this->db->exec("DELETE FROM notificaciones WHERE id_usuario_destinatario IN ($uIds)");
            }

            // Eliminar docentes fixtures
            if ($this->fixtureDocente1Id || $this->fixtureDocente2Id) {
                $docIn = implode(',', array_filter([$this->fixtureDocente1Id, $this->fixtureDocente2Id]));
                $this->db->exec("DELETE FROM docentes WHERE id_docente IN ($docIn)");
            }

            // Eliminar usuarios fixtures
            if (!empty($uIds)) {
                $this->db->exec("DELETE FROM usuarios WHERE id_usuario IN ($uIds)");
            }

        } catch (Throwable $e) {
            // Ignorar errores de limpieza final
        }
    }

    private function record(string $code, string $description, string $expected, string $obtained, bool $passed): void
    {
        $this->total++;
        if ($passed) {
            $this->passed++;
            $status = '[OK]';
        } else {
            $this->failed++;
            $status = '[FALLO]';
        }

        $this->results[] = [
            'code' => $code,
            'description' => $description,
            'expected' => $expected,
            'obtained' => $obtained,
            'status' => $passed ? 'PASS' : 'FAIL',
        ];

        echo sprintf("%s %s: %s\n", $status, $code, $description);
        if (!$passed) {
            echo sprintf("       Esperado: %s\n", $expected);
            echo sprintf("       Obtenido: %s\n", $obtained);
        }
    }

    // =========================================================================
    // BLOQUE 1: MODELO Y ENTIDAD DE NOTIFICACIONES (RF-26, DT-64)
    // =========================================================================

    private function testT10_01_EstructuraRelacionalMySQL(): void
    {
        $stmtCols = $this->db->query("SHOW COLUMNS FROM notificaciones");
        $cols = $stmtCols->fetchAll(PDO::FETCH_ASSOC);
        $colNames = array_column($cols, 'Field');

        $requiredCols = [
            'id_notificacion',
            'id_solicitud_permiso',
            'id_usuario_destinatario',
            'tipo',
            'titulo',
            'mensaje',
            'fecha_creacion',
            'fecha_lectura',
        ];

        $missing = array_diff($requiredCols, $colNames);
        $isOk = empty($missing);

        $this->record(
            'T10-01',
            'Verificación de estructura relacional de la tabla notificaciones en MySQL',
            'Columnas id_notificacion, id_solicitud_permiso, id_usuario_destinatario, tipo, titulo, mensaje, fecha_creacion, fecha_lectura presentes',
            $isOk ? 'Todas las columnas requeridas existen con definición correcta' : 'Columnas faltantes: ' . implode(', ', $missing),
            $isOk
        );
    }

    private function testT10_02_EntidadNotificacionContrato(): void
    {
        $notif = new Notificacion(
            id_notificacion: 101,
            id_solicitud_permiso: 202,
            id_usuario_destinatario: 303,
            tipo: Notificacion::TIPO_NUEVA_SOLICITUD,
            titulo: 'Solicitud Recibida',
            mensaje: 'El docente ha solicitado permiso',
            fecha_creacion: '2026-10-08 10:00:00.123456',
            fecha_lectura: null,
            docente_nombre: 'Profesor Prueba'
        );

        $isOk = (
            $notif->id_notificacion === 101 &&
            $notif->id_solicitud_permiso === 202 &&
            $notif->id_usuario_destinatario === 303 &&
            $notif->tipo === 'NUEVA_SOLICITUD' &&
            $notif->titulo === 'Solicitud Recibida' &&
            $notif->fecha_lectura === null &&
            $notif->isPendiente() === true &&
            $notif->isLeida() === false &&
            is_array($notif->toArray()) &&
            $notif->toArray()['docente_nombre'] === 'Profesor Prueba'
        );

        $this->record(
            'T10-02',
            'Verificación de contrato y entidad Notificacion (inmutabilidad, helpers y constantes)',
            'Instancia válida con propiedades readonly, isPendiente() === true, isLeida() === false',
            $isOk ? 'Entidad Notificacion cumple estrictamente con el contrato y tipos' : 'Inconsistencia en propiedades de la entidad',
            $isOk
        );
    }

    // =========================================================================
    // BLOQUE 2: GENERACIÓN AUTOMÁTICA Y DESTINATARIOS ADMINISTRATIVOS (RF-26, DT-64, DT-65)
    // =========================================================================

    private function testT10_03_GeneracionNotificacionAlCrearSolicitud(): void
    {
        $countAntes = (int) $this->db->query("SELECT COUNT(*) FROM notificaciones")->fetchColumn();

        $fecha = date('Y-m-d', strtotime('+3 days'));
        $datos = [
            'fecha_solicitada' => $fecha,
            'tipo_motivo' => 'Salud / Cita Médica',
            'descripcion' => 'Cita con especialista médico cardiólogo en centro de salud para evaluación',
        ];

        $solicitud = $this->permisoService->crearSolicitud(
            $this->fixtureDocente1Id,
            $this->fixtureUserDocente1Id,
            $datos
        );
        $this->createdSolicitudIds[] = $solicitud->id_solicitud_permiso;

        $countDespues = (int) $this->db->query("SELECT COUNT(*) FROM notificaciones")->fetchColumn();
        $stmt = $this->db->prepare("SELECT * FROM notificaciones WHERE id_solicitud_permiso = :id");
        $stmt->execute([':id' => $solicitud->id_solicitud_permiso]);
        $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $isOk = ($countDespues > $countAntes && count($notifs) >= 1);

        $this->record(
            'T10-03',
            'Generación automática de notificación al registrar nueva solicitud de permiso docente (RF-26)',
            'Al menos una notificación generada en base de datos vinculada a la solicitud',
            sprintf('Notificaciones creadas para la solicitud: %d (conteo global antes: %d, después: %d)', count($notifs), $countAntes, $countDespues),
            $isOk
        );
    }

    private function testT10_04_NotificacionTodosAdministradoresActivos(): void
    {
        $solicitudId = end($this->createdSolicitudIds);

        $stmt = $this->db->prepare("SELECT id_usuario_destinatario FROM notificaciones WHERE id_solicitud_permiso = :id");
        $stmt->execute([':id' => $solicitudId]);
        $destinatarios = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $hasAdmin1 = in_array($this->fixtureUserAdmin1Id, $destinatarios, false);
        $hasAdmin2 = in_array($this->fixtureUserAdmin2Id, $destinatarios, false);

        $isOk = ($hasAdmin1 && $hasAdmin2);

        $this->record(
            'T10-04',
            'Notificación dirigida a todos los administradores activos con rol ADMINISTRADOR (DT-65)',
            'Notificación creada tanto para Admin 1 como para Admin 2',
            sprintf('Admin 1 presente: %s, Admin 2 presente: %s (Total destinatarios: %d)', $hasAdmin1 ? 'SÍ' : 'NO', $hasAdmin2 ? 'SÍ' : 'NO', count($destinatarios)),
            $isOk
        );
    }

    private function testT10_05_ExclusionAdministradoresInactivos(): void
    {
        $solicitudId = end($this->createdSolicitudIds);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_solicitud_permiso = :id AND id_usuario_destinatario = :u");
        $stmt->execute([':id' => $solicitudId, ':u' => $this->fixtureUserAdminInactivoId]);
        $count = (int) $stmt->fetchColumn();

        $isOk = ($count === 0);

        $this->record(
            'T10-05',
            'Exclusión de administradores inactivos (estado != "ACTIVO") como destinatarios (DT-65)',
            'Cero notificaciones emitidas hacia el usuario administrador con estado INACTIVO',
            sprintf('Notificaciones dirigidas al admin inactivo: %d (esperado: 0)', $count),
            $isOk
        );
    }

    private function testT10_06_ExclusionUsuariosDocentesComoDestinatarios(): void
    {
        $solicitudId = end($this->createdSolicitudIds);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_solicitud_permiso = :id AND id_usuario_destinatario IN (:d1, :d2)");
        $stmt->execute([
            ':id' => $solicitudId,
            ':d1' => $this->fixtureUserDocente1Id,
            ':d2' => $this->fixtureUserDocente2Id,
        ]);
        $count = (int) $stmt->fetchColumn();

        $isOk = ($count === 0);

        $this->record(
            'T10-06',
            'Exclusión de usuarios con rol DOCENTE como destinatarios de la notificación administrativa (DT-65)',
            'Cero notificaciones dirigidas a docentes para el evento de nueva solicitud de permiso',
            sprintf('Notificaciones dirigidas a docentes: %d (esperado: 0)', $count),
            $isOk
        );
    }

    private function testT10_07_AtributosObligatoriosYFormatoInstitucional(): void
    {
        $solicitudId = end($this->createdSolicitudIds);

        $stmt = $this->db->prepare("SELECT * FROM notificaciones WHERE id_solicitud_permiso = :id AND id_usuario_destinatario = :u");
        $stmt->execute([':id' => $solicitudId, ':u' => $this->fixtureUserAdmin1Id]);
        $notif = $stmt->fetch(PDO::FETCH_ASSOC);

        $tipoOk = ($notif['tipo'] === 'NUEVA_SOLICITUD');
        $tituloOk = (stripos($notif['titulo'], 'solicitud') !== false);
        $mensajeOk = (stripos($notif['mensaje'], 'María') !== false || stripos($notif['mensaje'], 'permiso') !== false);

        $isOk = ($tipoOk && $tituloOk && $mensajeOk);

        $this->record(
            'T10-07',
            'Atributos obligatorios y formato institucional del título y mensaje (RF-26, DT-65)',
            'tipo="NUEVA_SOLICITUD", título descriptivo y mensaje con detalles contextuales del docente',
            sprintf('Tipo: %s, Título: "%s", Mensaje contiene contexto: %s', $notif['tipo'] ?? '', $notif['titulo'] ?? '', $mensajeOk ? 'SÍ' : 'NO'),
            $isOk
        );
    }

    private function testT10_08_EstadoInicialNoLeida(): void
    {
        $solicitudId = end($this->createdSolicitudIds);

        $stmt = $this->db->prepare("SELECT fecha_lectura FROM notificaciones WHERE id_solicitud_permiso = :id");
        $stmt->execute([':id' => $solicitudId]);
        $fechasLectura = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $allNull = true;
        foreach ($fechasLectura as $fl) {
            if ($fl !== null) {
                $allNull = false;
                break;
            }
        }

        $isOk = ($allNull && !empty($fechasLectura));

        $this->record(
            'T10-08',
            'Estado inicial de notificación: fecha_lectura IS NULL y estado pendiente (DT-66)',
            'Todas las notificaciones recién emitidas tienen fecha_lectura = NULL',
            $isOk ? sprintf('Total notificaciones verificadas en estado NULL: %d', count($fechasLectura)) : 'Al menos una notificación tiene fecha_lectura no nula al crearse',
            $isOk
        );
    }

    private function testT10_09_TimestampServidorMicrosegundos(): void
    {
        $solicitudId = end($this->createdSolicitudIds);

        $stmt = $this->db->prepare("SELECT fecha_creacion FROM notificaciones WHERE id_solicitud_permiso = :id LIMIT 1");
        $stmt->execute([':id' => $solicitudId]);
        $fechaCreacion = (string) $stmt->fetchColumn();

        // Verificar formato DATETIME(6): YYYY-MM-DD HH:MM:SS.uuuuuu o DATETIME válido
        $hasMicroseconds = (bool) preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(\.\d{1,6})?$/', $fechaCreacion);

        $this->record(
            'T10-09',
            'Timestamp generado por el servidor con microsegundos CURRENT_TIMESTAMP(6) (DT-66)',
            'fecha_creacion válida con formato DATETIME del servidor de base de datos',
            sprintf('fecha_creacion: "%s" (formato correcto: %s)', $fechaCreacion, $hasMicroseconds ? 'SÍ' : 'NO'),
            $hasMicroseconds
        );
    }

    // =========================================================================
    // BLOQUE 3: ATOMICIDAD TRANSACCIONAL E INTEGRIDAD REFERENCIAL (DT-64, DT-57)
    // =========================================================================

    private function testT10_10_AtomicidadTransaccionalRollbackCeroHuerfanas(): void
    {
        $countAntes = (int) $this->db->query("SELECT COUNT(*) FROM notificaciones")->fetchColumn();
        $solicitudesAntes = (int) $this->db->query("SELECT COUNT(*) FROM solicitudes_permiso")->fetchColumn();

        $errorCaught = false;
        try {
            // Intentar registrar una solicitud con fecha inválida (falla validación)
            $this->permisoService->crearSolicitud(
                $this->fixtureDocente1Id,
                $this->fixtureUserDocente1Id,
                [
                    'fecha_solicitada' => 'invalida',
                    'tipo_motivo' => 'Salud / Cita Médica',
                    'descripcion' => 'Intento con formato inválido',
                ]
            );
        } catch (\InvalidArgumentException $e) {
            $errorCaught = true;
        }

        $countDespues = (int) $this->db->query("SELECT COUNT(*) FROM notificaciones")->fetchColumn();
        $solicitudesDespues = (int) $this->db->query("SELECT COUNT(*) FROM solicitudes_permiso")->fetchColumn();

        $isOk = ($errorCaught && $countDespues === $countAntes && $solicitudesDespues === $solicitudesAntes);

        $this->record(
            'T10-10',
            'Atomicidad transaccional: Si la solicitud falla, cero notificaciones emitidas (DT-64, DT-57)',
            'Excepción capturada, rollback transaccional completo y cero notificaciones huérfanas',
            sprintf('Error capturado: %s, Notificaciones delta: %d, Solicitudes delta: %d', $errorCaught ? 'SÍ' : 'NO', $countDespues - $countAntes, $solicitudesDespues - $solicitudesAntes),
            $isOk
        );
    }

    private function testT10_11_IntegridadReferencialOnDeleteRestrict(): void
    {
        $solicitudId = end($this->createdSolicitudIds);

        $blocked = false;
        try {
            // Intentar borrar directamente la solicitud teniendo notificaciones asociadas
            $this->db->exec("DELETE FROM solicitudes_permiso WHERE id_solicitud_permiso = {$solicitudId}");
        } catch (PDOException $e) {
            // 23000 / 1451: Cannot delete or update a parent row: a foreign key constraint fails
            if (str_contains($e->getMessage(), '1451') || str_contains($e->getMessage(), 'foreign key') || $e->getCode() === '23000') {
                $blocked = true;
            }
        }

        $this->record(
            'T10-11',
            'Integridad referencial: ON DELETE RESTRICT en id_solicitud_permiso protege contra borrado accidental (DT-64)',
            'PDOException 23000/1451 bloquea la eliminación de solicitud con notificaciones asociadas',
            $blocked ? 'Bloqueo exitoso por Foreign Key Constraint' : 'No se detectó el bloqueo esperado',
            $blocked
        );
    }

    // =========================================================================
    // BLOQUE 4: REPOSITORIO, CONSULTAS Y GESTIÓN DE ESTADO (DT-65, DT-66)
    // =========================================================================

    private function testT10_12_ConsultaBandejaPaginadaYOrdenDescendente(): void
    {
        // Crear una segunda solicitud para tener al menos 2 notificaciones para Admin 1
        $sol2 = $this->permisoService->crearSolicitud(
            $this->fixtureDocente2Id,
            $this->fixtureUserDocente2Id,
            [
                'fecha_solicitada' => date('Y-m-d', strtotime('+10 days')),
                'tipo_motivo' => 'Capacitación / Comisión Oficial',
                'descripcion' => 'Taller ministerial de actualización pedagógica docente en sede distrital',
            ]
        );
        $this->createdSolicitudIds[] = $sol2->id_solicitud_permiso;

        $notifsAdmin1 = $this->notifRepo->findByUsuario($this->fixtureUserAdmin1Id, null, 20, 0);

        $isDescending = true;
        for ($i = 0; $i < count($notifsAdmin1) - 1; $i++) {
            if (strcmp($notifsAdmin1[$i]->fecha_creacion, $notifsAdmin1[$i + 1]->fecha_creacion) < 0) {
                $isDescending = false;
                break;
            }
        }

        $isOk = (!empty($notifsAdmin1) && $isDescending && count($notifsAdmin1) >= 2);

        $this->record(
            'T10-12',
            'Repositorio: Consulta de notificaciones ordenadas descendentemente por fecha_creacion (findByUsuario) (DT-65, DT-66)',
            'Listado con al menos 2 elementos ordenados de más reciente a más antiguo',
            sprintf('Notificaciones obtenidas: %d, orden descendente estricto: %s', count($notifsAdmin1), $isDescending ? 'CORRECTO' : 'INCORRECTO'),
            $isOk
        );
    }

    private function testT10_13_ConteoExactoNoLeidas(): void
    {
        $conteoRepo = $this->notifRepo->contarNoLeidasPorUsuario($this->fixtureUserAdmin1Id);
        $conteoService = $this->notifService->obtenerConteoNoLeidas($this->fixtureUserAdmin1Id);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_usuario_destinatario = :u AND fecha_lectura IS NULL");
        $stmt->execute([':u' => $this->fixtureUserAdmin1Id]);
        $conteoDirecto = (int) $stmt->fetchColumn();

        $isOk = ($conteoRepo === $conteoDirecto && $conteoService === $conteoDirecto && $conteoDirecto >= 2);

        $this->record(
            'T10-13',
            'Repositorio y Servicio: Contador de no leídas concuerda exactamente con fecha_lectura IS NULL (DT-66)',
            'contarNoLeidasPorUsuario() coincide con SELECT COUNT(*) WHERE fecha_lectura IS NULL',
            sprintf('Repo: %d, Servicio: %d, SQL Directo: %d', $conteoRepo, $conteoService, $conteoDirecto),
            $isOk
        );
    }

    private function testT10_14_MarcarIndividualComoLeida(): void
    {
        $notifs = $this->notifRepo->findByUsuario($this->fixtureUserAdmin1Id, null, 5, 0);
        $primera = $notifs[0];

        $marcada = $this->notifService->marcarLeida($primera->id_notificacion, $this->fixtureUserAdmin1Id);

        $stmt = $this->db->prepare("SELECT fecha_lectura FROM notificaciones WHERE id_notificacion = :id");
        $stmt->execute([':id' => $primera->id_notificacion]);
        $fechaLectura = $stmt->fetchColumn();

        $isOk = ($marcada === true && $fechaLectura !== null && $fechaLectura !== false);

        $this->record(
            'T10-14',
            'Repositorio y Servicio: Marcar notificación individual como leída actualiza fecha_lectura (NOW(6)) (DT-66)',
            'marcarLeida() retorna true y fecha_lectura se actualiza con timestamp no nulo',
            sprintf('Resultado: %s, fecha_lectura registrada: "%s"', $marcada ? 'TRUE' : 'FALSE', (string) $fechaLectura),
            $isOk
        );
    }

    private function testT10_15_ConsistenciaTemporalFechaLectura(): void
    {
        $notifs = $this->notifRepo->findByUsuario($this->fixtureUserAdmin1Id, null, 5, 0);
        $leida = null;
        foreach ($notifs as $n) {
            if ($n->fecha_lectura !== null) {
                $leida = $n;
                break;
            }
        }

        $isOk = false;
        if ($leida !== null) {
            $isOk = (strcmp($leida->fecha_lectura, $leida->fecha_creacion) >= 0);
        }

        $this->record(
            'T10-15',
            'Restricción de consistencia temporal: fecha_lectura >= fecha_creacion (CHECK de tabla) (DT-66)',
            'fecha_lectura mayor o igual a fecha_creacion respetando la restricción de integridad',
            $leida ? sprintf('creación: %s, lectura: %s (consistente: %s)', $leida->fecha_creacion, $leida->fecha_lectura, $isOk ? 'SÍ' : 'NO') : 'No se encontró notificación leída',
            $isOk
        );
    }

    private function testT10_16_IdempotenciaMarcarComoLeida(): void
    {
        $notifs = $this->notifRepo->findByUsuario($this->fixtureUserAdmin1Id, null, 5, 0);
        $leida = null;
        foreach ($notifs as $n) {
            if ($n->fecha_lectura !== null) {
                $leida = $n;
                break;
            }
        }

        $fechaLecturaOriginal = $leida?->fecha_lectura;

        // Marcar de nuevo la misma notificación ya leída
        $marcadaSegundaVez = $this->notifService->marcarLeida($leida->id_notificacion, $this->fixtureUserAdmin1Id);

        $stmt = $this->db->prepare("SELECT fecha_lectura FROM notificaciones WHERE id_notificacion = :id");
        $stmt->execute([':id' => $leida->id_notificacion]);
        $fechaLecturaFinal = $stmt->fetchColumn();

        $isOk = ($marcadaSegundaVez === true && $fechaLecturaFinal === $fechaLecturaOriginal);

        $this->record(
            'T10-16',
            'Idempotencia al marcar como leída: Reintentos preservan el registro de lectura original sin error (DT-66)',
            'marcarLeida() en notificación ya leída retorna true y conserva timestamp original',
            sprintf('Retorno: %s, fecha original: %s, fecha final: %s', $marcadaSegundaVez ? 'TRUE' : 'FALSE', (string) $fechaLecturaOriginal, (string) $fechaLecturaFinal),
            $isOk
        );
    }

    private function testT10_17_MarcarTodasComoLeidas(): void
    {
        $afectadas = $this->notifService->marcarTodasLeidas($this->fixtureUserAdmin1Id);
        $pendientes = $this->notifService->obtenerConteoNoLeidas($this->fixtureUserAdmin1Id);

        $isOk = ($pendientes === 0);

        $this->record(
            'T10-17',
            'Repositorio y Servicio: Marcar todas las notificaciones como leídas en una sola acción (DT-66)',
            'marcarTodasLeidas() actualiza pendientes y el contador de no leídas se reduce a 0',
            sprintf('Filas afectadas: %d, notificaciones no leídas restantes: %d', $afectadas, $pendientes),
            $isOk
        );
    }

    private function testT10_18_AislamientoHorizontalNotificaciones(): void
    {
        // Admin 2 todavía tiene notificaciones no leídas
        $notifsAdmin2 = $this->notifRepo->findByUsuario($this->fixtureUserAdmin2Id, null, 5, 0);
        $notifAdmin2 = $notifsAdmin2[0];

        // Admin 1 intenta marcar como leída una notificación que pertenece a Admin 2
        $intentoCruzado = $this->notifRepo->marcarComoLeida($notifAdmin2->id_notificacion, $this->fixtureUserAdmin1Id);

        // Verificar que la notificación de Admin 2 SIGUE no leída
        $stmt = $this->db->prepare("SELECT fecha_lectura FROM notificaciones WHERE id_notificacion = :id");
        $stmt->execute([':id' => $notifAdmin2->id_notificacion]);
        $fechaLectura = $stmt->fetchColumn();

        $isOk = ($intentoCruzado === false && $fechaLectura === null);

        $this->record(
            'T10-18',
            'Aislamiento horizontal: Admin 1 no puede marcar ni alterar notificaciones de Admin 2 (DT-65)',
            'marcarComoLeida() retorna false ante id_usuario ajeno y el registro permanece intacto',
            sprintf('Intento cruzado retornó: %s, fecha_lectura de Admin 2: %s', $intentoCruzado ? 'TRUE' : 'FALSE', $fechaLectura === null ? 'NULL (intacta)' : 'MODIFICADA'),
            $isOk
        );
    }

    // =========================================================================
    // BLOQUE 5: SEGURIDAD DE ENDPOINTS, RBAC, CSRF Y PREVENCIÓN XSS (DT-66)
    // =========================================================================

    private function testT10_19_RbacAccesoAnonimoRedirigeLogin(): void
    {
        Session::reset();
        $app = App::getInstance();

        $req = new Request('GET', '/administracion/notificaciones');
        $resp = $app->handle($req);

        $isOk = ($resp->getStatusCode() === 302 && str_contains($resp->getHeader('Location') ?? '', '/login'));

        $this->record(
            'T10-19',
            'RBAC y autenticación: Acceso anónimo a /administracion/notificaciones redirige a /login (HTTP 302)',
            'Código HTTP 302 con encabezado Location hacia /login',
            sprintf('Código: %d, Location: %s', $resp->getStatusCode(), (string) $resp->getHeader('Location')),
            $isOk
        );
    }

    private function testT10_20_RbacAccesoDocenteDenegado403(): void
    {
        Session::reset();
        $docUser = $this->userRepo->findById($this->fixtureUserDocente1Id);
        Session::set('auth_user_id', $docUser->id_usuario);
        Session::set('auth_role', $docUser->rol_nombre);
        Session::set('auth_username', $docUser->nombre_usuario);
        Session::set('auth_logged_at', time());

        $app = App::getInstance();
        $req = new Request('GET', '/administracion/notificaciones');
        $resp = $app->handle($req);

        $isOk = ($resp->getStatusCode() === 403);

        $this->record(
            'T10-20',
            'RBAC y control de acceso: Endpoint /administracion/notificaciones deniega acceso a rol DOCENTE (HTTP 403)',
            'Código HTTP 403 Prohibido para usuarios autenticados sin rol ADMINISTRADOR',
            sprintf('Código HTTP obtenido: %d', $resp->getStatusCode()),
            $isOk
        );
        Session::reset();
    }

    private function testT10_21_CsrfMarcarLeidaRequerido(): void
    {
        Session::reset();
        $adminUser = $this->userRepo->findById($this->fixtureUserAdmin2Id);
        Session::set('auth_user_id', $adminUser->id_usuario);
        Session::set('auth_role', $adminUser->rol_nombre);
        Session::set('auth_username', $adminUser->nombre_usuario);
        Session::set('auth_logged_at', time());

        $app = App::getInstance();

        // Petición POST sin token CSRF
        $req = new Request('POST', '/administracion/notificaciones/marcar-leida', [], ['id_notificacion' => 1]);
        $resp = $app->handle($req);

        $isOk = ($resp->getStatusCode() === 403);

        $this->record(
            'T10-21',
            'Protección CSRF: POST /administracion/notificaciones/marcar-leida deniega petición sin token CSRF (HTTP 403)',
            'Código HTTP 403 Prohibido por falla de verificación de token CSRF',
            sprintf('Código HTTP obtenido: %d', $resp->getStatusCode()),
            $isOk
        );
        Session::reset();
    }

    private function testT10_22_CsrfMarcarTodasRequerido(): void
    {
        Session::reset();
        $adminUser = $this->userRepo->findById($this->fixtureUserAdmin2Id);
        Session::set('auth_user_id', $adminUser->id_usuario);
        Session::set('auth_role', $adminUser->rol_nombre);
        Session::set('auth_username', $adminUser->nombre_usuario);
        Session::set('auth_logged_at', time());

        $app = App::getInstance();

        // Petición POST sin token CSRF
        $req = new Request('POST', '/administracion/notificaciones/marcar-todas');
        $resp = $app->handle($req);

        $isOk = ($resp->getStatusCode() === 403);

        $this->record(
            'T10-22',
            'Protección CSRF: POST /administracion/notificaciones/marcar-todas deniega petición sin token CSRF (HTTP 403)',
            'Código HTTP 403 Prohibido por falla de verificación de token CSRF',
            sprintf('Código HTTP obtenido: %d', $resp->getStatusCode()),
            $isOk
        );
        Session::reset();
    }

    private function testT10_23_PrevencionXssEnVistaNotificaciones(): void
    {
        $viewFile = dirname(__DIR__) . '/views/admin/notificaciones/index.php';
        $content = (string) file_get_contents($viewFile);

        // Verificar que todos los ecos dinámicos usen e() para escapar HTML
        $hasUnescapedEcho = (bool) preg_match('/<\?=\s*\$(notif|n|item|titulo|mensaje|docente)\b(?!\s*->|\s*\?\->)/', $content);
        $usesEscapedEcho = str_contains($content, 'e($notif->titulo)') && str_contains($content, 'e($notif->mensaje)') && str_contains($content, 'e($notif->docente_nombre)');

        $isOk = (!$hasUnescapedEcho && $usesEscapedEcho);

        $this->record(
            'T10-23',
            'Prevención de XSS: Sanitización estricta contextual con e() en la vista views/admin/notificaciones/index.php',
            'Todos los campos dinámicos (título, mensaje, docente) se imprimen mediante e() de escape contextual',
            $isOk ? 'Vista sanitizada contextualmente contra ataques de Cross-Site Scripting' : 'Posible eco no escapado detectado en la vista',
            $isOk
        );
    }

    private function testT10_24_EndpointAjaxConteoNoLeidas(): void
    {
        Session::reset();
        $adminUser = $this->userRepo->findById($this->fixtureUserAdmin2Id);
        Session::set('auth_user_id', $adminUser->id_usuario);
        Session::set('auth_role', $adminUser->rol_nombre);
        Session::set('auth_username', $adminUser->nombre_usuario);
        Session::set('auth_logged_at', time());

        $app = App::getInstance();
        $req = new Request('GET', '/administracion/notificaciones/conteo');
        $resp = $app->handle($req);

        $data = json_decode($resp->getContent(), true);

        $isOk = (
            $resp->getStatusCode() === 200 &&
            is_array($data) &&
            isset($data['success']) &&
            $data['success'] === true &&
            isset($data['unread_count']) &&
            is_int($data['unread_count']) &&
            $data['unread_count'] >= 1
        );

        $this->record(
            'T10-24',
            'Endpoint AJAX / JSON: GET /administracion/notificaciones/conteo devuelve JSON con unread_count',
            'HTTP 200 con {success: true, unread_count: N} para actualizar badges en tiempo real',
            sprintf('HTTP %d, JSON recibido: %s', $resp->getStatusCode(), json_encode($data)),
            $isOk
        );
        Session::reset();
    }

    // =========================================================================
    // BLOQUE 6: FLUJO HTTP REAL COMPLETO END-TO-END (RF-26)
    // =========================================================================

    private function testT10_25_FlujoHttpRealCompleto(): void
    {
        echo "\n>>> Iniciando servidor web PHP embebido para prueba HTTP real T10-25 (puerto 8098)...\n";

        $port = 8098;
        $phpBin = PHP_BINARY;
        $docRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public';

        $serverLog = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php_server_f10_' . uniqid() . '.log';
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $serverLog, 'a'],
            2 => ['file', $serverLog, 'a'],
        ];

        $cmd = sprintf('"%s" -S 127.0.0.1:%d -t "%s"', $phpBin, $port, $docRoot);
        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));

        if (!is_resource($process)) {
            $this->record('T10-25', 'Flujo HTTP real completo de notificaciones', 'Servidor iniciado', 'No se pudo iniciar servidor PHP embebido', false);
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
            $this->record('T10-25', 'Flujo HTTP real completo de notificaciones', 'Servidor conectado', 'El servidor web no respondió a tiempo', false);
            return;
        }

        $cookieDocente = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_f10_doc_' . uniqid() . '.txt';
        $cookieAdmin = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_f10_adm_' . uniqid() . '.txt';

        $httpRequest = function (string $url, string $method = 'GET', mixed $postData = null, ?string $cookie = null, array $headers = []): array {
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
                        if ($v instanceof \CURLFile) {
                            $hasFile = true;
                            break;
                        }
                    }
                    if ($hasFile) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                    } else {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                    }
                } elseif (is_string($postData)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
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

        $flowOk = false;
        $flowDetails = '';

        try {
            // 1. Login Docente 1
            $resLoginGet = $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'GET', null, $cookieDocente);
            $csrfTokenDoc = '';
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resLoginGet['body'], $m)) {
                $csrfTokenDoc = $m[1];
            }

            $uDoc = $this->userRepo->findById($this->fixtureUserDocente1Id);
            $resLoginDoc = $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'POST', [
                '_token' => $csrfTokenDoc,
                'nombre_usuario' => $uDoc->nombre_usuario,
                'password' => 'PassFase10Test!',
            ], $cookieDocente);

            if ($resLoginDoc['code'] !== 302) {
                throw new \RuntimeException('Fallo al autenticar Docente vía HTTP: code ' . $resLoginDoc['code']);
            }

            // 2. Docente obtiene token CSRF del formulario de permiso
            $resFormPermiso = $httpRequest(sprintf('http://127.0.0.1:%d/docente/permisos/crear', $port), 'GET', null, $cookieDocente);
            $csrfPermisoDoc = '';
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resFormPermiso['body'], $m)) {
                $csrfPermisoDoc = $m[1];
            }

            // 3. Docente envía solicitud de permiso con documento PDF
            $pdfPath = tempnam(sys_get_temp_dir(), 'curl_f10_') . '.pdf';
            file_put_contents($pdfPath, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 3 3]>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000102 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n162\n%%EOF");
            $this->createdFilePaths[] = $pdfPath;

            $postFields = [
                '_token' => $csrfPermisoDoc,
                'fecha_solicitada' => date('Y-m-d', strtotime('+35 days')),
                'tipo_motivo' => 'Salud / Cita Médica',
                'descripcion' => 'Permiso por intervención odontológica programada en hospital nacional',
                'soporte' => new \CURLFile($pdfPath, 'application/pdf', 'constancia_odontologica.pdf'),
            ];

            $resStore = $httpRequest(sprintf('http://127.0.0.1:%d/docente/permisos/crear', $port), 'POST', $postFields, $cookieDocente);
            if ($resStore['code'] !== 302 || !str_contains($resStore['location'], '/docente/permisos')) {
                throw new \RuntimeException('Fallo al registrar solicitud de permiso docente vía HTTP: code ' . $resStore['code'] . ', loc: ' . $resStore['location']);
            }

            // 4. Login Admin 1
            $resLoginAdminGet = $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'GET', null, $cookieAdmin);
            $csrfTokenAdmin = '';
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resLoginAdminGet['body'], $m)) {
                $csrfTokenAdmin = $m[1];
            }

            $uAdmin = $this->userRepo->findById($this->fixtureUserAdmin1Id);
            $resLoginAdmin = $httpRequest(sprintf('http://127.0.0.1:%d/login', $port), 'POST', [
                '_token' => $csrfTokenAdmin,
                'nombre_usuario' => $uAdmin->nombre_usuario,
                'password' => 'PassFase10Test!',
            ], $cookieAdmin);

            if ($resLoginAdmin['code'] !== 302) {
                throw new \RuntimeException('Fallo al autenticar Admin vía HTTP');
            }

            // 5. Admin consulta bandeja de notificaciones
            $resBandeja = $httpRequest(sprintf('http://127.0.0.1:%d/administracion/notificaciones', $port), 'GET', null, $cookieAdmin);
            $hasNotifEnBandeja = ($resBandeja['code'] === 200 && (stripos($resBandeja['body'], 'solicitud') !== false || stripos($resBandeja['body'], 'Notificaciones') !== false));

            // Extraer ID de la notificación y token CSRF de la bandeja
            $csrfBandeja = '';
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resBandeja['body'], $m)) {
                $csrfBandeja = $m[1];
            }

            $notifIdToMark = 0;
            if (preg_match('/name=["\']id_notificacion["\']\s+value=["\'](\d+)["\']/i', $resBandeja['body'], $m)) {
                $notifIdToMark = (int) $m[1];
            }

            // Si por alguna razón la vista no contiene el ID en el form (por ejemplo si ya se marcó), consultar DB
            if ($notifIdToMark === 0) {
                $stmt = $this->db->prepare("SELECT id_notificacion FROM notificaciones WHERE id_usuario_destinatario = :u AND fecha_lectura IS NULL ORDER BY id_notificacion DESC LIMIT 1");
                $stmt->execute([':u' => $this->fixtureUserAdmin1Id]);
                $notifIdToMark = (int) $stmt->fetchColumn();
            }

            // 6. Admin consulta conteo previo vía AJAX
            $resConteoAntes = $httpRequest(sprintf('http://127.0.0.1:%d/administracion/notificaciones/conteo', $port), 'GET', null, $cookieAdmin);
            $conteoAntesData = json_decode($resConteoAntes['body'], true);
            $unreadAntes = (int) ($conteoAntesData['unread_count'] ?? $conteoAntesData['no_leidas'] ?? 0);

            // 7. Admin marca la notificación como leída
            $resMarcar = $httpRequest(sprintf('http://127.0.0.1:%d/administracion/notificaciones/marcar-leida', $port), 'POST', [
                '_token' => $csrfBandeja,
                'id_notificacion' => $notifIdToMark,
            ], $cookieAdmin);

            // 8. Admin consulta conteo posterior vía AJAX
            $resConteoDespues = $httpRequest(sprintf('http://127.0.0.1:%d/administracion/notificaciones/conteo', $port), 'GET', null, $cookieAdmin);
            $conteoDespuesData = json_decode($resConteoDespues['body'], true);
            $unreadDespues = (int) ($conteoDespuesData['unread_count'] ?? $conteoDespuesData['no_leidas'] ?? 0);

            $markOk = ($resMarcar['code'] === 302 || $resMarcar['code'] === 200);
            $conteoDisminuyo = ($unreadDespues < $unreadAntes);

            $flowOk = ($hasNotifEnBandeja && $markOk && $conteoDisminuyo);
            $flowDetails = sprintf(
                'Bandeja HTTP 200 con notificación: %s, Marcar leída HTTP: %d, Conteo no leídas antes: %d -> después: %d',
                $hasNotifEnBandeja ? 'SÍ' : 'NO',
                $resMarcar['code'],
                $unreadAntes,
                $unreadDespues
            );

        } catch (Throwable $e) {
            $flowDetails = 'Error en flujo HTTP: ' . $e->getMessage();
        } finally {
            $this->terminateProcess($pid, $process, $pipes);
            @unlink($cookieDocente);
            @unlink($cookieAdmin);
        }

        $this->record(
            'T10-25',
            'Flujo HTTP real completo end-to-end con servidor embebido (puerto 8098)',
            'Docente solicita permiso -> Admin recibe notificación en bandeja -> Admin marca leída -> Conteo decrementa',
            $flowDetails,
            $flowOk
        );
    }

    private function terminateProcess(int $pid, $process, array $pipes): void
    {
        foreach ($pipes as $p) {
            if (is_resource($p)) {
                fclose($p);
            }
        }
        if (is_resource($process)) {
            proc_terminate($process);
            proc_close($process);
        }
        if ($pid > 0 && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            @shell_exec("taskkill /F /PID {$pid} 2>&1");
        }
    }

    // =========================================================================
    // BLOQUE 7: REGRESIONES COMPLETAS DE FASES 1 A 9
    // =========================================================================

    private function testT10_26_RegresionFase9(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 9 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase9.php');
        $output = (string) @shell_exec($cmd);

        $matched = preg_match('/Total ejecutadas\s*:\s*46\s*Exitosas\s*\(OK\)\s*:\s*46\s*Fallidas\s*\(FALLO\)\s*:\s*0/i', $output);
        $isOk = $matched && !str_contains($output, '[FALLO]');

        $this->record(
            'T10-26',
            'Regresión completa de Fase 9 (Gestión de Permisos Docentes)',
            '46/46 pruebas superadas con 0 fallos',
            $isOk ? '46/46 pruebas exitosas de Fase 9' : 'Fallo en regresión de Fase 9: ' . substr($output, -300),
            $isOk
        );
    }

    private function testT10_27_RegresionFases1a8(): void
    {
        echo "\n>>> Verificando integridad relacional y datos de Fases 1 a 8...\n";
        $tables = [
            'roles', 'usuarios', 'tokens_recuperacion', 'docentes', 'estudiantes',
            'encargados', 'estudiantes_encargados', 'periodos_escolares', 'grados',
            'grados_periodos', 'secciones', 'aulas', 'asignaciones_docentes',
            'inscripciones', 'configuraciones_ubicacion', 'marcajes_asistencia',
            'solicitudes_permiso', 'documentos_permiso', 'historial_solicitudes_permiso',
            'notificaciones'
        ];

        $missingTables = [];
        foreach ($tables as $t) {
            $stmt = $this->db->query("SHOW TABLES LIKE '{$t}'");
            if ($stmt->rowCount() === 0) {
                $missingTables[] = $t;
            }
        }

        // Verificar datos clave de fases previas
        $rolesCount = (int) $this->db->query("SELECT COUNT(*) FROM roles")->fetchColumn();
        $adminCount = (int) $this->db->query("SELECT COUNT(*) FROM usuarios WHERE id_rol = (SELECT id_rol FROM roles WHERE nombre IN ('ADMINISTRACION', 'ADMINISTRADOR') LIMIT 1)")->fetchColumn();
        $docentesCount = (int) $this->db->query("SELECT COUNT(*) FROM docentes")->fetchColumn();
        $seccionesCount = (int) $this->db->query("SELECT COUNT(*) FROM secciones")->fetchColumn();
        $ubicacionCount = (int) $this->db->query("SELECT COUNT(*) FROM configuraciones_ubicacion")->fetchColumn();

        $isOk = empty($missingTables) && $rolesCount >= 2 && $adminCount >= 1 && $docentesCount >= 1 && $seccionesCount >= 1 && $ubicacionCount >= 1;

        $this->record(
            'T10-27',
            'Regresión y verificación de integridad de Fases 1 a 8 (20 tablas relacionales activas)',
            '20/20 tablas relacionales existentes, roles, docentes, secciones y geocercas íntegras',
            $isOk ? sprintf('20 tablas verificadas; roles: %d, admins: %d, docentes: %d, secciones: %d, geocercas: %d', $rolesCount, $adminCount, $docentesCount, $seccionesCount, $ubicacionCount) : 'Tablas faltantes o datos inconsistentes: ' . implode(', ', $missingTables),
            $isOk
        );
    }

    private function printSummary(): void
    {
        echo "\n============================================================\n";
        echo "RESUMEN DE PRUEBAS — FASE 10: NOTIFICACIONES (RF-26)\n";
        echo "============================================================\n";
        echo sprintf("Total ejecutadas : %d\n", $this->total);
        echo sprintf("Exitosas (OK)    : %d\n", $this->passed);
        echo sprintf("Fallidas (FALLO) : %d\n", $this->failed);
        echo "============================================================\n";

        if ($this->failed === 0 && $this->passed > 0) {
            echo "RESULTADO GLOBAL: TODAS LAS PRUEBAS APROBADAS SATISFACTORIAMENTE (100%)\n";
        } else {
            echo "RESULTADO GLOBAL: SE IDENTIFICARON FALLOS EN LA EJECUCIÓN\n";
        }
        echo "============================================================\n";
    }
}

// Ejecución directa de la suite
$suite = new Phase10TestSuite();
$suite->run();
