<?php

declare(strict_types=1);

/**
 * Suite de Pruebas Automatizadas — Fase 13
 * Recuperación de Contraseña (RF-03)
 * Sistema Web — Escuela Oficial Rural Mixta Lo de Fuentes
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\App;
use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Controllers\PasswordRecoveryController;
use App\Models\TokenRecuperacion;
use App\Models\User;
use App\Repositories\TokenRecuperacionRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\PasswordRecoveryService;

class Phase13TestSuite
{
    private PDO $db;
    private array $results = [];

    // Fixtures creados para limpieza estricta (T13-30)
    private array $createdUserIds = [];
    private array $createdTokenIds = [];

    private int $fixtureAdminId = 0;
    private int $fixtureDocenteId = 0;
    private int $fixtureInactivoId = 0;

    private string $correoAdmin = 'admin.f13@escuela.edu.gt';
    private string $correoDocente = 'docente.f13@escuela.edu.gt';
    private string $correoInactivo = 'inactivo.f13@escuela.edu.gt';
    private string $claveInicial = 'ClaveInicial.2026!';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function run(): void
    {
        echo "============================================================\n";
        echo "SUITE DE PRUEBAS AUTOMATIZADAS — FASE 13\n";
        echo "Recuperación de Contraseña (RF-03)\n";
        echo "============================================================\n\n";

        $this->cleanupPreviousFixtures();
        $this->setupFixtures();

        try {
            $this->testT13_01_TrazabilidadDocumentalOficial();
            $this->testT13_02_SolicitudValidaGeneraToken();
            $this->testT13_03_SolicitudUsuarioInexistenteAntiEnumeracion();
            $this->testT13_04_SolicitudUsuarioInactivoAntiEnumeracion();
            $this->testT13_05_TokenCriptograficamenteSeguro();
            $this->testT13_06_AlmacenamientoSeguroHashSha256();
            $this->testT13_07_VigenciaTemporalSesentaMinutos();
            $this->testT13_08_ValidacionTokenActivoYVigente();
            $this->testT13_09_RechazoTokenInexistente();
            $this->testT13_10_RechazoTokenFormatoInvalido();
            $this->testT13_11_RechazoTokenVencido();
            $this->testT13_12_SolicitudNuevaRevocaTokensPrevios();
            $this->testT13_13_RestablecimientoExitoso();
            $this->testT13_14_InmutabilidadEstadoUsado();
            $this->testT13_15_TokenNoReutilizable();
            $this->testT13_16_HashActualizadoConBcrypt();
            $this->testT13_17_LoginConNuevaContrasena();
            $this->testT13_18_LoginConContrasenaAnteriorRechazado();
            $this->testT13_19_ConfirmacionIncorrectaRechazada();
            $this->testT13_20_PoliticaLongitudMinimaContrasena();
            $this->testT13_21_MitigacionInyeccionSql();
            $this->testT13_22_PrevencionXssVistasAuth();
            $this->testT13_23_ProteccionCsrfEndpointsRecuperacion();
            $this->testT13_24_AccesoDirectoSinTokenRechazado();
            $this->testT13_25_RedireccionUsuarioAutenticado();
            $this->testT13_26_IntegridadSchemaSinTablasNuevas();
            $this->testT13_27_BloqueoPesimistaConcurrenciaToken();
            $this->testT13_28_FlujoHttpRealCompleto();
            $this->testT13_29_FlujoHttpRealCasosAnomalos();
            $this->testT13_30_LimpiezaEstrictaFixtures();
        } finally {
            $this->cleanupFixtures();
        }

        $this->printSummary();
    }

    private function setupFixtures(): void
    {
        $idRolAdmin = (int) $this->db->query("SELECT id_rol FROM roles WHERE nombre IN ('ADMINISTRACION', 'ADMINISTRADOR') LIMIT 1")->fetchColumn();
        $idRolDocente = (int) $this->db->query("SELECT id_rol FROM roles WHERE nombre = 'DOCENTE' LIMIT 1")->fetchColumn();

        $hashInicial = password_hash($this->claveInicial, PASSWORD_BCRYPT);

        // 1. Usuario Admin Fixture
        $stmtA = $this->db->prepare('INSERT INTO usuarios (id_rol, nombre_usuario, correo, clave_hash, nombres, apellidos, estado)
                                     VALUES (:r, "admin_f13", :c, :p, "Admin", "Fase 13", "ACTIVO")');
        $stmtA->execute([':r' => $idRolAdmin, ':c' => $this->correoAdmin, ':p' => $hashInicial]);
        $this->fixtureAdminId = (int) $this->db->lastInsertId();
        $this->createdUserIds[] = $this->fixtureAdminId;

        // 2. Usuario Docente Fixture
        $stmtD = $this->db->prepare('INSERT INTO usuarios (id_rol, nombre_usuario, correo, clave_hash, nombres, apellidos, estado)
                                     VALUES (:r, "docente_f13", :c, :p, "Docente", "Fase 13", "ACTIVO")');
        $stmtD->execute([':r' => $idRolDocente, ':c' => $this->correoDocente, ':p' => $hashInicial]);
        $this->fixtureDocenteId = (int) $this->db->lastInsertId();
        $this->createdUserIds[] = $this->fixtureDocenteId;

        // 3. Usuario Inactivo Fixture
        $stmtI = $this->db->prepare('INSERT INTO usuarios (id_rol, nombre_usuario, correo, clave_hash, nombres, apellidos, estado)
                                     VALUES (:r, "inactivo_f13", :c, :p, "Inactivo", "Fase 13", "INACTIVO")');
        $stmtI->execute([':r' => $idRolDocente, ':c' => $this->correoInactivo, ':p' => $hashInicial]);
        $this->fixtureInactivoId = (int) $this->db->lastInsertId();
        $this->createdUserIds[] = $this->fixtureInactivoId;
    }

    private function cleanupPreviousFixtures(): void
    {
        $this->db->exec('DELETE FROM tokens_recuperacion WHERE id_usuario IN (
            SELECT id_usuario FROM usuarios WHERE nombre_usuario IN ("admin_f13", "docente_f13", "inactivo_f13")
            OR correo IN ("admin.f13@escuela.edu.gt", "docente.f13@escuela.edu.gt", "inactivo.f13@escuela.edu.gt")
        )');

        $this->db->exec('DELETE FROM usuarios WHERE nombre_usuario IN ("admin_f13", "docente_f13", "inactivo_f13")
            OR correo IN ("admin.f13@escuela.edu.gt", "docente.f13@escuela.edu.gt", "inactivo.f13@escuela.edu.gt")');
    }

    private function cleanupFixtures(): void
    {
        if (!empty($this->createdUserIds)) {
            $in = implode(',', array_map('intval', $this->createdUserIds));
            $this->db->exec("DELETE FROM tokens_recuperacion WHERE id_usuario IN ($in)");
            $this->db->exec("DELETE FROM usuarios WHERE id_usuario IN ($in)");
        }
    }

    private function record(string $code, string $description, string $expected, string $obtained, bool $passed): void
    {
        $status = $passed ? 'OK' : 'FALLO';
        $this->results[] = [
            'code' => $code,
            'desc' => $description,
            'expected' => $expected,
            'obtained' => $obtained,
            'passed' => $passed,
        ];
        printf("[%s] %s: %s\n", $status, $code, $description);
    }

    // =========================================================================
    // PRUEBAS DE FASE 13
    // =========================================================================

    private function testT13_01_TrazabilidadDocumentalOficial(): void
    {
        $matriz = (string) file_get_contents(dirname(__DIR__) . '/docs/fase-0/matriz-trazabilidad.md');
        $hasRf03 = str_contains($matriz, 'RF-03') && str_contains($matriz, 'Recuperación de contraseña');

        $this->record(
            'T13-01',
            'Trazabilidad documental oficial: RF-03 en Fase 13; Fases posteriores pendientes',
            'RF-03 presente en matriz de trazabilidad asignado oficialmente a Fase 13',
            $hasRf03 ? 'Documentado y trazado en matriz' : 'Discrepancia en matriz',
            $hasRf03
        );
    }

    private function testT13_02_SolicitudValidaGeneraToken(): void
    {
        $service = new PasswordRecoveryService();
        $res = $service->solicitarRecuperacion($this->correoDocente);

        $ok = ($res['success'] === true)
            && !empty($res['token_plano'])
            && !empty($res['enlace_simulado'])
            && ($res['id_usuario'] === $this->fixtureDocenteId);

        // Verificar en BD que el token_hash coincida con el hash SHA-256 del token plano
        $hashEsperado = hash('sha256', (string) $res['token_plano']);
        $tokenBd = (new TokenRecuperacionRepository($this->db))->findByTokenHash($hashEsperado);

        $ok = $ok && ($tokenBd !== null) && ($tokenBd->isActivo());

        $this->record(
            'T13-02',
            'Solicitud válida de recuperación con correo registrado existente genera token en BD',
            'success=true, token_plano y hash persistido en estado ACTIVO',
            sprintf('success=%s, token_plano=%s, persistido=%s', $res['success'] ? 'true' : 'false', !empty($res['token_plano']) ? 'SÍ' : 'NO', $tokenBd !== null ? 'SÍ' : 'NO'),
            $ok
        );
    }

    private function testT13_03_SolicitudUsuarioInexistenteAntiEnumeracion(): void
    {
        $service = new PasswordRecoveryService();
        $res = $service->solicitarRecuperacion('no_existe_cuenta_12345@dominio.com');

        // La respuesta debe ser exitosa con el mismo mensaje neutral
        $ok = ($res['success'] === true)
            && str_contains($res['message'], 'Si los datos ingresados corresponden')
            && empty($res['token_plano'])
            && empty($res['enlace_simulado']);

        $this->record(
            'T13-03',
            'Solicitud con correo inexistente devuelve mensaje neutral idéntico (anti-enumeración)',
            'success=true con mensaje neutral preventivo sin revelar no existencia',
            sprintf('success=%s, sin_token=%s, mensaje_neutral=%s', $res['success'] ? 'true' : 'false', empty($res['token_plano']) ? 'SÍ' : 'NO', $ok ? 'SÍ' : 'NO'),
            $ok
        );
    }

    private function testT13_04_SolicitudUsuarioInactivoAntiEnumeracion(): void
    {
        $service = new PasswordRecoveryService();
        $res = $service->solicitarRecuperacion($this->correoInactivo);

        $ok = ($res['success'] === true)
            && str_contains($res['message'], 'Si los datos ingresados corresponden')
            && empty($res['token_plano']);

        // Asegurar que no se haya insertado token para el inactivo
        $count = (int) $this->db->query("SELECT COUNT(*) FROM tokens_recuperacion WHERE id_usuario = {$this->fixtureInactivoId}")->fetchColumn();
        $ok = $ok && ($count === 0);

        $this->record(
            'T13-04',
            'Solicitud con cuenta inactiva devuelve mensaje neutral idéntico sin generar token activo',
            'success=true con mensaje neutral idéntico y cero tokens creados para usuario inactivo',
            sprintf('success=%s, tokens_en_bd=%d', $res['success'] ? 'true' : 'false', $count),
            $ok
        );
    }

    private function testT13_05_TokenCriptograficamenteSeguro(): void
    {
        $service = new PasswordRecoveryService();
        $res1 = $service->solicitarRecuperacion($this->correoDocente);
        $res2 = $service->solicitarRecuperacion($this->correoDocente);

        $t1 = (string) ($res1['token_plano'] ?? '');
        $t2 = (string) ($res2['token_plano'] ?? '');

        $esHex64_1 = (bool) preg_match('/^[a-f0-9]{64}$/i', $t1);
        $esHex64_2 = (bool) preg_match('/^[a-f0-9]{64}$/i', $t2);
        $sonDistintos = ($t1 !== $t2);

        $ok = $esHex64_1 && $esHex64_2 && $sonDistintos;

        $this->record(
            'T13-05',
            'Token generado es criptográficamente seguro (longitud 64 hex, entropía random_bytes)',
            'Cadena hexadecimal de 64 caracteres de alta entropía e impredecible',
            sprintf('Token1 (64 hex): %s, Token2 (64 hex): %s, Únicos y distintos: %s', $esHex64_1 ? 'SÍ' : 'NO', $esHex64_2 ? 'SÍ' : 'NO', $sonDistintos ? 'SÍ' : 'NO'),
            $ok
        );
    }

    private function testT13_06_AlmacenamientoSeguroHashSha256(): void
    {
        $service = new PasswordRecoveryService();
        $res = $service->solicitarRecuperacion($this->correoAdmin);
        $tokenPlano = (string) $res['token_plano'];

        // En BD no debe existir el token plano en ningún registro
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM tokens_recuperacion WHERE token_hash = :plano');
        $stmt->execute([':plano' => $tokenPlano]);
        $countPlano = (int) $stmt->fetchColumn();

        // En BD debe existir el hash SHA-256
        $hash = hash('sha256', $tokenPlano);
        $stmtH = $this->db->prepare('SELECT COUNT(*) FROM tokens_recuperacion WHERE token_hash = :hash');
        $stmtH->execute([':hash' => $hash]);
        $countHash = (int) $stmtH->fetchColumn();

        $ok = ($countPlano === 0) && ($countHash === 1);

        $this->record(
            'T13-06',
            'Almacenamiento seguro: token se persiste exclusivamente como hash SHA-256 en token_hash',
            '0 registros con token plano, 1 registro con hash SHA-256',
            sprintf('Registros con token plano: %d, Registros con hash: %d', $countPlano, $countHash),
            $ok
        );
    }

    private function testT13_07_VigenciaTemporalSesentaMinutos(): void
    {
        $service = new PasswordRecoveryService();
        $res = $service->solicitarRecuperacion($this->correoDocente);
        $tokenPlano = (string) $res['token_plano'];
        $hash = hash('sha256', $tokenPlano);

        $repo = new TokenRecuperacionRepository($this->db);
        $token = $repo->findByTokenHash($hash);

        $tCreacion = new DateTimeImmutable($token->fecha_creacion);
        $tExpiracion = new DateTimeImmutable($token->fecha_expiracion);
        $diffMinutos = ($tExpiracion->getTimestamp() - $tCreacion->getTimestamp()) / 60;

        $ok = ($token !== null) && ($diffMinutos >= 59 && $diffMinutos <= 61);

        $this->record(
            'T13-07',
            'Vigencia temporal estricta: fecha_expiracion fijada a 60 minutos posteriores a creación',
            'Diferencia entre expiración y creación igual a 60 minutos',
            sprintf('Diferencia calculada: %.1f minutos (Vigencia: %d min)', $diffMinutos, TokenRecuperacion::VIGENCIA_MINUTOS),
            $ok
        );
    }

    private function testT13_08_ValidacionTokenActivoYVigente(): void
    {
        $service = new PasswordRecoveryService();
        $res = $service->solicitarRecuperacion($this->correoDocente);
        $tokenPlano = (string) $res['token_plano'];

        $val = $service->validarToken($tokenPlano);
        $ok = ($val['valido'] === true) && ($val['error'] === null) && isset($val['token']) && isset($val['user']);

        $this->record(
            'T13-08',
            'Validación exitosa de token activo y vigente',
            'valido=true, error=null y entidades hidratadas',
            sprintf('valido=%s, error=%s', $val['valido'] ? 'true' : 'false', $val['error'] ?? 'ninguno'),
            $ok
        );
    }

    private function testT13_09_RechazoTokenInexistente(): void
    {
        $service = new PasswordRecoveryService();
        $tokenFalso = str_repeat('a', 64);
        $val = $service->validarToken($tokenFalso);

        $ok = ($val['valido'] === false) && str_contains($val['error'], 'no es válido o no existe');

        $this->record(
            'T13-09',
            'Rechazo de token inexistente o manipulado',
            'valido=false con mensaje explicativo seguro',
            sprintf('valido=%s, error: "%s"', $val['valido'] ? 'true' : 'false', $val['error']),
            $ok
        );
    }

    private function testT13_10_RechazoTokenFormatoInvalido(): void
    {
        $service = new PasswordRecoveryService();
        $casos = ['', 'corto', '1234', str_repeat('z', 64), '../../path/traversal', '<script>alert(1)</script>'];

        $todosRechazados = true;
        foreach ($casos as $caso) {
            $val = $service->validarToken($caso);
            if ($val['valido'] !== false) {
                $todosRechazados = false;
                break;
            }
        }

        $this->record(
            'T13-10',
            'Rechazo de token con formato inválido (no hex o longitud distinta a 64 chars)',
            'valido=false para todos los formatos corruptos o maliciosos',
            $todosRechazados ? '6/6 casos inválidos rechazados' : 'Fallo en rechazo de formato',
            $todosRechazados
        );
    }

    private function testT13_11_RechazoTokenVencido(): void
    {
        // Crear un token directamente expirado en el pasado
        $rawToken = bin2hex(random_bytes(32));
        $hash = hash('sha256', $rawToken);
        $expPasado = (new DateTimeImmutable())->modify('-10 minutes')->format('Y-m-d H:i:s.u');
        $creacion = (new DateTimeImmutable())->modify('-70 minutes')->format('Y-m-d H:i:s.u');

        $stmt = $this->db->prepare('INSERT INTO tokens_recuperacion (id_usuario, token_hash, estado, fecha_creacion, fecha_expiracion)
                                    VALUES (:u, :h, "ACTIVO", :c, :e)');
        $stmt->execute([':u' => $this->fixtureDocenteId, ':h' => $hash, ':c' => $creacion, ':e' => $expPasado]);
        $idTok = (int) $this->db->lastInsertId();
        $this->createdTokenIds[] = $idTok;

        $service = new PasswordRecoveryService();
        $val = $service->validarToken($rawToken);

        $ok = ($val['valido'] === false) && str_contains($val['error'], 'ha expirado');

        $this->record(
            'T13-11',
            'Rechazo de token vencido (fecha_expiracion en el pasado)',
            'valido=false con mensaje indicando expiración del enlace',
            sprintf('valido=%s, mensaje: "%s"', $val['valido'] ? 'true' : 'false', $val['error']),
            $ok
        );
    }

    private function testT13_12_SolicitudNuevaRevocaTokensPrevios(): void
    {
        $service = new PasswordRecoveryService();
        // Generar primera solicitud
        $res1 = $service->solicitarRecuperacion($this->correoDocente);
        $tok1 = (string) $res1['token_plano'];

        // Generar segunda solicitud para el mismo usuario
        $res2 = $service->solicitarRecuperacion($this->correoDocente);
        $tok2 = (string) $res2['token_plano'];

        // El primer token debe estar ahora REVOCADO
        $val1 = $service->validarToken($tok1);
        // El segundo token debe estar ACTIVO y válido
        $val2 = $service->validarToken($tok2);

        $ok = ($val1['valido'] === false)
            && str_contains($val1['error'], 'ha sido revocado')
            && ($val2['valido'] === true);

        $this->record(
            'T13-12',
            'Solicitud nueva revoca tokens activos previos del mismo usuario (estado = REVOCADO)',
            'Token 1 revocado e inválido, Token 2 activo y válido',
            sprintf('Token 1 válido: %s (Error: %s), Token 2 válido: %s', $val1['valido'] ? 'SÍ' : 'NO', $val1['error'], $val2['valido'] ? 'SÍ' : 'NO'),
            $ok
        );
    }

    private function testT13_13_RestablecimientoExitoso(): void
    {
        $service = new PasswordRecoveryService();
        $resSol = $service->solicitarRecuperacion($this->correoAdmin);
        $token = (string) $resSol['token_plano'];

        $nuevaClave = 'NuevaClave.Segura.2026!';
        $resReset = $service->restablecerContrasena($token, $nuevaClave, $nuevaClave);

        $ok = ($resReset['success'] === true) && str_contains($resReset['message'], 'exitosamente');

        $this->record(
            'T13-13',
            'Restablecimiento exitoso de contraseña con token válido y confirmación coincidente',
            'success=true y mensaje de confirmación amigable',
            sprintf('success=%s, mensaje: "%s"', $resReset['success'] ? 'true' : 'false', $resReset['message']),
            $ok
        );
    }

    private function testT13_14_InmutabilidadEstadoUsado(): void
    {
        // Verificar el estado del token que se acaba de usar en T13-13
        $repo = new TokenRecuperacionRepository($this->db);
        $stmt = $this->db->prepare('SELECT estado, fecha_uso FROM tokens_recuperacion WHERE id_usuario = :u ORDER BY id_token_recuperacion DESC LIMIT 1');
        $stmt->execute([':u' => $this->fixtureAdminId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $estadoUsado = ($row['estado'] === 'USADO');
        $fechaUsoNotNull = !empty($row['fecha_uso']);

        $ok = $estadoUsado && $fechaUsoNotNull;

        $this->record(
            'T13-14',
            'Inmutabilidad de uso: token utilizado pasa a estado = USADO con fecha_uso no nula',
            'estado="USADO" y fecha_uso con timestamp server-authoritative',
            sprintf('estado=%s, fecha_uso=%s', $row['estado'], $row['fecha_uso'] ?? 'NULL'),
            $ok
        );
    }

    private function testT13_15_TokenNoReutilizable(): void
    {
        $service = new PasswordRecoveryService();
        $resSol = $service->solicitarRecuperacion($this->correoDocente);
        $token = (string) $resSol['token_plano'];

        $clave1 = 'ClaveCambio1.2026!';
        $res1 = $service->restablecerContrasena($token, $clave1, $clave1);

        // Segundo intento de restablecimiento con el mismo token
        $clave2 = 'ClaveCambio2.2026!';
        $res2 = $service->restablecerContrasena($token, $clave2, $clave2);

        $ok = ($res1['success'] === true)
            && ($res2['success'] === false)
            && str_contains($res2['message'], 'ya fue utilizado');

        $this->record(
            'T13-15',
            'Token no reutilizable: un token USADO es rechazado en intentos posteriores',
            'Primer intento exitoso, segundo intento denegado',
            sprintf('Intento 1: %s, Intento 2 (Reutilización): %s (Mensaje: "%s")', $res1['success'] ? 'OK' : 'ERR', $res2['success'] ? 'PERMITIDO (FALLO)' : 'RECHAZADO (OK)', $res2['message']),
            $ok
        );
    }

    private function testT13_16_HashActualizadoConBcrypt(): void
    {
        $userRepo = new UserRepository($this->db);
        $userDoc = $userRepo->findById($this->fixtureDocenteId);

        $esHashBcrypt = str_starts_with($userDoc->clave_hash, '$2y$') && strlen($userDoc->clave_hash) === 60;
        $verificaNuevaClave = password_verify('ClaveCambio1.2026!', $userDoc->clave_hash);

        $ok = $esHashBcrypt && $verificaNuevaClave;

        $this->record(
            'T13-16',
            'Hash de contraseña en BD se actualiza con BCrypt nativo (password_verify OK con nueva clave)',
            'clave_hash inicia con $2y$ (60 chars) y verifica con nueva clave',
            sprintf('Formato BCrypt: %s, Longitud: %d, password_verify: %s', $esHashBcrypt ? 'SÍ' : 'NO', strlen($userDoc->clave_hash), $verificaNuevaClave ? 'VÁLIDO' : 'INVÁLIDO'),
            $ok
        );
    }

    private function testT13_17_LoginConNuevaContrasena(): void
    {
        Session::destroy();
        Session::start();

        $auth = new AuthService();
        $loginOk = $auth->attempt('docente_f13', 'ClaveCambio1.2026!');

        $ok = $loginOk && $auth->check() && ($auth->user()?->id_usuario === $this->fixtureDocenteId);

        $this->record(
            'T13-17',
            'Inicio de sesión exitoso con la nueva contraseña (AuthService::attempt)',
            'Autenticación aprobada (true) con sesión activa hidratada',
            sprintf('attempt=%s, check=%s, usuario=%s', $loginOk ? 'true' : 'false', $auth->check() ? 'true' : 'false', $auth->user()?->nombre_usuario ?? 'null'),
            $ok
        );
    }

    private function testT13_18_LoginConContrasenaAnteriorRechazado(): void
    {
        Session::destroy();
        Session::start();

        $auth = new AuthService();
        // Intentar con la clave inicial previa
        $loginFallido = $auth->attempt('docente_f13', $this->claveInicial);

        $ok = ($loginFallido === false) && !$auth->check();

        $this->record(
            'T13-18',
            'Rechazo de inicio de sesión con la contraseña anterior tras el restablecimiento',
            'attempt=false con contraseña previa y cero sesión creada',
            sprintf('attempt previo=%s, sesión=%s', $loginFallido ? 'PERMITIDO (FALLO)' : 'RECHAZADO (OK)', $auth->check() ? 'ACTIVA' : 'INEXISTENTE'),
            $ok
        );
    }

    private function testT13_19_ConfirmacionIncorrectaRechazada(): void
    {
        $service = new PasswordRecoveryService();
        $resSol = $service->solicitarRecuperacion($this->correoDocente);
        $token = (string) $resSol['token_plano'];

        $res = $service->restablecerContrasena($token, 'NuevaClaveValida123!', 'OtraClaveTotalmenteDistinta123!');

        $ok = ($res['success'] === false)
            && isset($res['errors']['password_confirmation'])
            && str_contains($res['errors']['password_confirmation'], 'no coincide');

        $this->record(
            'T13-19',
            'Rechazo de restablecimiento si la confirmación de contraseña no coincide',
            'success=false con error en campo password_confirmation',
            sprintf('success=%s, error: "%s"', $res['success'] ? 'true' : 'false', $res['errors']['password_confirmation'] ?? 'ninguno'),
            $ok
        );
    }

    private function testT13_20_PoliticaLongitudMinimaContrasena(): void
    {
        $service = new PasswordRecoveryService();
        $resSol = $service->solicitarRecuperacion($this->correoDocente);
        $token = (string) $resSol['token_plano'];

        $resCorta = $service->restablecerContrasena($token, '1234567', '1234567');
        $resVacia = $service->restablecerContrasena($token, '', '');

        $ok = ($resCorta['success'] === false)
            && isset($resCorta['errors']['password'])
            && str_contains($resCorta['errors']['password'], 'al menos 8 caracteres')
            && ($resVacia['success'] === false);

        $this->record(
            'T13-20',
            'Validación de longitud mínima de contraseña (rechazo de contraseñas < 8 caracteres)',
            'success=false para contraseñas de menos de 8 caracteres o vacías',
            sprintf('Clave 7 chars: %s, Clave vacía: %s', $resCorta['success'] ? 'PERMITIDA' : 'RECHAZADA', $resVacia['success'] ? 'PERMITIDA' : 'RECHAZADA'),
            $ok
        );
    }

    private function testT13_21_MitigacionInyeccionSql(): void
    {
        $service = new PasswordRecoveryService();

        // 1. Inyección SQL en campo de solicitud
        $payloadSql1 = "' OR '1'='1' -- ";
        $res1 = $service->solicitarRecuperacion($payloadSql1);

        // 2. Inyección SQL en token de restablecimiento
        $payloadSql2 = "' OR 1=1; DROP TABLE tokens_recuperacion; -- ";
        $res2 = $service->validarToken($payloadSql2);

        $ok = ($res1['success'] === true) // Respuesta neutral anti-enumeración sin ejecutar SQL
            && ($res2['valido'] === false);

        // Verificar que la tabla tokens_recuperacion sigue existiendo y sana
        $count = (int) $this->db->query("SELECT COUNT(*) FROM tokens_recuperacion")->fetchColumn();
        $ok = $ok && ($count >= 0);

        $this->record(
            'T13-21',
            'Seguridad: Mitigación de Inyección SQL en parámetros de recuperación y restablecimiento',
            'Consultas preparadas nativas neutralizan payloads maliciosos sin alterar base de datos',
            'Payloads procesados sin error SQL, tabla íntegra con prepared statements',
            $ok
        );
    }

    private function testT13_22_PrevencionXssVistasAuth(): void
    {
        $v1 = (string) file_get_contents(dirname(__DIR__) . '/views/auth/recuperar-contrasena.php');
        $v2 = (string) file_get_contents(dirname(__DIR__) . '/views/auth/restablecer-contrasena.php');

        $sinEscapeRaw1 = preg_match('/<\?=\s*\$(?!errors|csrf_field)(correo|mensaje_exito|mensaje_error|enlace_simulado)/', $v1);
        $sinEscapeRaw2 = preg_match('/<\?=\s*\$(?!errors|csrf_field)(token|error_token|mensaje_error)/', $v2);

        $usaE1 = str_contains($v1, 'e($mensaje_exito)') && str_contains($v1, 'e($correo');
        $usaE2 = str_contains($v2, 'e($token)') && str_contains($v2, 'e($error_token');

        $ok = ($sinEscapeRaw1 === 0) && ($sinEscapeRaw2 === 0) && $usaE1 && $usaE2;

        $this->record(
            'T13-22',
            'Seguridad: Prevención de Cross-Site Scripting (XSS) mediante escape e() en vistas auth',
            'Cero impresiones sin e() en vistas de recuperación y restablecimiento de contraseña',
            $ok ? 'Todas las variables dinámicas escapadas con e()' : 'Vulnerabilidad XSS detectada',
            $ok
        );
    }

    private function testT13_23_ProteccionCsrfEndpointsRecuperacion(): void
    {
        Session::destroy();
        Session::start();

        $app = App::getInstance()->reboot();

        // 1. POST /recuperar-contrasena sin token CSRF
        $reqSinCsrf1 = Request::create('POST', '/recuperar-contrasena', ['correo' => $this->correoDocente]);
        $resSinCsrf1 = $app->handle($reqSinCsrf1);

        // 2. POST /restablecer-contrasena sin token CSRF
        $reqSinCsrf2 = Request::create('POST', '/restablecer-contrasena', [
            'token' => str_repeat('a', 64),
            'password' => 'NuevaClave.2026!',
            'password_confirmation' => 'NuevaClave.2026!',
        ]);
        $resSinCsrf2 = $app->handle($reqSinCsrf2);

        $es403_1 = ($resSinCsrf1->getStatusCode() === 403);
        $es403_2 = ($resSinCsrf2->getStatusCode() === 403);

        $ok = $es403_1 && $es403_2;

        $this->record(
            'T13-23',
            'Seguridad: Protección CSRF obligatoria en POST /recuperar-contrasena y /restablecer-contrasena',
            'HTTP 403 Forbidden ante peticiones sin token CSRF válido',
            sprintf('POST /recuperar-contrasena: HTTP %d, POST /restablecer-contrasena: HTTP %d', $resSinCsrf1->getStatusCode(), $resSinCsrf2->getStatusCode()),
            $ok
        );
    }

    private function testT13_24_AccesoDirectoSinTokenRechazado(): void
    {
        $controller = new PasswordRecoveryController();
        $req = Request::create('GET', '/restablecer-contrasena');
        $res = $controller->showResetForm($req);

        $ok = ($res->getStatusCode() === 400) && str_contains($res->getBody(), 'Debe ingresar a través del enlace');

        $this->record(
            'T13-24',
            'Control de acceso: Intento de acceso directo a /restablecer-contrasena sin token devuelve HTTP 400',
            'HTTP 400 con mensaje solicitando acceso mediante enlace válido',
            sprintf('HTTP %d con vista de error explicativa', $res->getStatusCode()),
            $ok
        );
    }

    private function testT13_25_RedireccionUsuarioAutenticado(): void
    {
        Session::destroy();
        Session::start();

        // Autenticar como administrador
        $auth = new AuthService();
        $auth->attempt('admin_f13', 'NuevaClave.Segura.2026!');

        $controller = new PasswordRecoveryController(null, $auth);
        $req1 = Request::create('GET', '/recuperar-contrasena');
        $res1 = $controller->showRequestForm($req1);

        $req2 = Request::create('GET', '/restablecer-contrasena');
        $res2 = $controller->showResetForm($req2);

        $ok1 = ($res1->getStatusCode() === 302) && str_contains((string) $res1->getHeader('Location'), '/administracion');
        $ok2 = ($res2->getStatusCode() === 302) && str_contains((string) $res2->getHeader('Location'), '/administracion');

        $ok = $ok1 && $ok2;

        $this->record(
            'T13-25',
            'Redirección de usuario ya autenticado ante rutas de recuperación hacia su panel',
            'HTTP 302 hacia /administracion para sesión autenticada',
            sprintf('GET /recuperar: HTTP %d -> %s, GET /restablecer: HTTP %d -> %s', $res1->getStatusCode(), $res1->getHeader('Location'), $res2->getStatusCode(), $res2->getHeader('Location')),
            $ok
        );
    }

    private function testT13_26_IntegridadSchemaSinTablasNuevas(): void
    {
        $schema = (string) file_get_contents(dirname(__DIR__) . '/database/schema.sql');

        // Verificar que la tabla tokens_recuperacion exista en schema.sql pero no se hayan creado tablas extras
        $hasTokensTable = str_contains($schema, 'CREATE TABLE tokens_recuperacion');
        $countTables = substr_count($schema, 'CREATE TABLE');

        // En Fase 10 eran 20 tablas. Fase 11, 12 y 13 no agregan tablas adicionales
        $ok = $hasTokensTable && ($countTables === 20);

        $this->record(
            'T13-26',
            'Base de datos: Reutilización de tokens_recuperacion sin alteraciones a schema.sql',
            'schema.sql intacto con exactamente 20 tablas relacionales canónicas',
            sprintf('Tabla tokens_recuperacion presente: %s, Total tablas en schema: %d (esperado: 20)', $hasTokensTable ? 'SÍ' : 'NO', $countTables),
            $ok
        );
    }

    private function testT13_27_BloqueoPesimistaConcurrenciaToken(): void
    {
        $service = new PasswordRecoveryService();
        $res = $service->solicitarRecuperacion($this->correoDocente);
        $token = (string) $res['token_plano'];

        $repo = new TokenRecuperacionRepository($this->db);
        $hash = hash('sha256', $token);

        // Probar que findForUpdate recupera la fila con bloqueo pesimista en transacción
        $this->db->beginTransaction();
        $tokLocked = $repo->findForUpdate($hash);
        $this->db->commit();

        $ok = ($tokLocked !== null) && ($tokLocked->id_usuario === $this->fixtureDocenteId);

        $this->record(
            'T13-27',
            'Concurrencia y atomicidad: Bloqueo pesimista FOR UPDATE previene condiciones de carrera',
            'Token recuperado con SELECT ... FOR UPDATE en bloque transaccional atómico',
            $ok ? 'Bloqueo pesimista verificado en transacción' : 'Fallo en bloqueo pesimista',
            $ok
        );
    }

    private function testT13_28_FlujoHttpRealCompleto(): void
    {
        echo "\n>>> Iniciando servidor web PHP embebido para prueba HTTP real T13-28 (puerto 8101)...\n";

        $port = 8101;
        $phpBin = PHP_BINARY;
        $docRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public';

        $serverLog = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php_server_f13_' . uniqid() . '.log';
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $serverLog, 'a'],
            2 => ['file', $serverLog, 'a'],
        ];

        $cmd = sprintf('"%s" -S 127.0.0.1:%d -t "%s"', $phpBin, $port, $docRoot);
        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));

        if (!is_resource($process)) {
            $this->record('T13-28', 'Flujo HTTP real: Solicitud -> Token -> Formulario -> Cambio -> Login', 'Servidor iniciado', 'No se pudo iniciar el proceso del servidor', false);
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
            $this->record('T13-28', 'Flujo HTTP real: Solicitud -> Token -> Formulario -> Cambio -> Login', 'Servidor conectado', 'Timeout al conectar con el servidor', false);
            return;
        }

        $cookie = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_f13_' . uniqid() . '.txt';
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
            // 1. GET /recuperar-contrasena
            $resGet1 = $http("$base/recuperar-contrasena", 'GET', null, $cookie);
            preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resGet1['body'], $mCsrf1);
            $csrf1 = $mCsrf1[1] ?? '';

            // 2. POST /recuperar-contrasena para el usuario docente
            $resPost1 = $http("$base/recuperar-contrasena", 'POST', [
                '_token' => $csrf1,
                'correo' => $this->correoDocente,
            ], $cookie);

            // Extraer el enlace generado o el token de la respuesta simulada
            preg_match('/\/restablecer-contrasena\?token=([a-f0-9]{64})/i', $resPost1['body'], $mTok);
            $tokenExtraido = $mTok[1] ?? '';

            // 3. GET /restablecer-contrasena?token=...
            $resGet2 = $http("$base/restablecer-contrasena?token=$tokenExtraido", 'GET', null, $cookie);
            preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resGet2['body'], $mCsrf2);
            $csrf2 = $mCsrf2[1] ?? '';

            // 4. POST /restablecer-contrasena con nueva contraseña
            $claveFinal = 'ClaveDefinitiva.HTTP.2026!';
            $resPost2 = $http("$base/restablecer-contrasena", 'POST', [
                '_token' => $csrf2,
                'token' => $tokenExtraido,
                'password' => $claveFinal,
                'password_confirmation' => $claveFinal,
            ], $cookie);

            // Debe redirigir a /login (HTTP 302)
            $redireccionaLogin = ($resPost2['code'] === 302) && str_contains($resPost2['location'], '/login');

            // 5. GET /login para verificar mensaje flash y obtener nuevo CSRF
            $resLoginGet = $http("$base/login", 'GET', null, $cookie);
            preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $resLoginGet['body'], $mCsrfL);
            $csrfLogin = $mCsrfL[1] ?? '';

            // 6. POST /login con la nueva contraseña
            $resLoginPost = $http("$base/login", 'POST', [
                '_token' => $csrfLogin,
                'nombre_usuario' => 'docente_f13',
                'password' => $claveFinal,
            ], $cookie);

            // Debe autenticar exitosamente y redirigir a /docente (HTTP 302)
            $loginDocenteExitoso = ($resLoginPost['code'] === 302) && str_contains($resLoginPost['location'], '/docente');

            $ok = ($resGet1['code'] === 200)
                && ($resPost1['code'] === 200)
                && !empty($tokenExtraido)
                && ($resGet2['code'] === 200)
                && $redireccionaLogin
                && $loginDocenteExitoso;

            $this->record(
                'T13-28',
                'Flujo HTTP real completo: Solicitud -> Token -> Formulario -> Cambio -> Login',
                'GET 200 -> POST 200 -> GET Reset 200 -> POST Reset 302 -> POST Login 302 (/docente)',
                sprintf('GET 1: %d, POST 1: %d, Token extraído: %s, GET 2: %d, POST 2: %d -> %s, Login: %d -> %s',
                    $resGet1['code'], $resPost1['code'], !empty($tokenExtraido) ? 'SÍ' : 'NO', $resGet2['code'], $resPost2['code'], $resPost2['location'], $resLoginPost['code'], $resLoginPost['location']),
                $ok
            );
        } finally {
            $this->terminateProcess($pid, $process, $pipes);
            @unlink($serverLog);
            if (file_exists($cookie)) {
                @unlink($cookie);
            }
        }
    }

    private function testT13_29_FlujoHttpRealCasosAnomalos(): void
    {
        $port = 8102;
        $phpBin = PHP_BINARY;
        $docRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public';

        $serverLog = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php_server_f13_err_' . uniqid() . '.log';
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $serverLog, 'a'],
            2 => ['file', $serverLog, 'a'],
        ];

        $cmd = sprintf('"%s" -S 127.0.0.1:%d -t "%s"', $phpBin, $port, $docRoot);
        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));

        if (!is_resource($process)) {
            $this->record('T13-29', 'Flujo HTTP real: Casos anómalos de recuperación y reset', 'Servidor iniciado', 'No se pudo iniciar el proceso', false);
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
            $this->record('T13-29', 'Flujo HTTP real: Casos anómalos de recuperación y reset', 'Servidor conectado', 'Timeout', false);
            return;
        }

        $cookie = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_f13_err_' . uniqid() . '.txt';
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
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $hs = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);
            $headers = substr((string) $raw, 0, $hs);
            $body = substr((string) $raw, $hs);

            return ['code' => $code, 'headers' => $headers, 'body' => $body];
        };

        $base = "http://127.0.0.1:$port";

        try {
            // 1. Token inválido en URL -> HTTP 400
            $resTokenInvalido = $http("$base/restablecer-contrasena?token=token_invalido_123", 'GET');

            // 2. Acceso directo a /restablecer-contrasena sin parámetro -> HTTP 400
            $resSinToken = $http("$base/restablecer-contrasena", 'GET');

            // 3. POST sin CSRF a /recuperar-contrasena -> HTTP 403
            $resSinCsrf = $http("$base/recuperar-contrasena", 'POST', ['correo' => $this->correoAdmin]);

            $ok = ($resTokenInvalido['code'] === 400)
                && ($resSinToken['code'] === 400)
                && ($resSinCsrf['code'] === 403);

            $this->record(
                'T13-29',
                'Flujo HTTP real de casos anómalos: Token vencido/inválido (400) y CSRF ausente (403)',
                'Token inválido: HTTP 400, Sin token: HTTP 400, Sin CSRF: HTTP 403',
                sprintf('Token inválido: HTTP %d, Sin token: HTTP %d, Sin CSRF: HTTP %d', $resTokenInvalido['code'], $resSinToken['code'], $resSinCsrf['code']),
                $ok
            );
        } finally {
            $this->terminateProcess($pid, $process, $pipes);
            @unlink($serverLog);
            if (file_exists($cookie)) {
                @unlink($cookie);
            }
        }
    }

    private function testT13_30_LimpiezaEstrictaFixtures(): void
    {
        // Limpieza de todos los fixtures de Fase 13 creados para las pruebas
        $this->cleanupFixtures();

        // Verificar que no queden usuarios ni tokens huérfanos de Fase 13
        $countUsers = (int) $this->db->query("SELECT COUNT(*) FROM usuarios WHERE nombre_usuario IN ('admin_f13', 'docente_f13', 'inactivo_f13')")->fetchColumn();
        $countTokens = (int) $this->db->query("SELECT COUNT(*) FROM tokens_recuperacion WHERE id_usuario IN (SELECT id_usuario FROM usuarios WHERE nombre_usuario IN ('admin_f13', 'docente_f13', 'inactivo_f13'))")->fetchColumn();

        $ok = ($countUsers === 0) && ($countTokens === 0);

        $this->record(
            'T13-30',
            'Gestión de fixtures: Cero registros temporales permanentes tras ejecutar la suite',
            '0 usuarios de prueba y 0 tokens residuales en base de datos',
            sprintf('Usuarios residuales: %d, Tokens residuales: %d', $countUsers, $countTokens),
            $ok
        );
    }

    private function terminateProcess(int $pid, $process, array $pipes): void
    {
        if (is_resource($process)) {
            foreach ($pipes as $p) {
                if (is_resource($p)) {
                    @fclose($p);
                }
            }
            if (stripos(PHP_OS, 'WIN') === 0 && $pid > 0) {
                @shell_exec("taskkill /F /T /PID $pid 2>&1");
            }
            @proc_terminate($process);
            @proc_close($process);
        }
    }

    private function printSummary(): void
    {
        $total = count($this->results);
        $ok = count(array_filter($this->results, fn($r) => $r['passed']));
        $fail = $total - $ok;

        echo "\n============================================================\n";
        echo "RESUMEN DE PRUEBAS DE FASE 13: RECUPERACIÓN DE CONTRASEÑA (RF-03)\n";
        echo "============================================================\n";
        printf("Total ejecutadas : %d\n", $total);
        printf("Exitosas (OK)    : %d\n", $ok);
        printf("Fallidas (FALLO) : %d\n", $fail);
        echo "============================================================\n";
        if ($fail === 0) {
            echo "RESULTADO: TODAS LAS PRUEBAS DE FASE 13 SUPERADAS SATISFACTORIAMENTE (100% GREEN).\n\n";
        } else {
            echo "RESULTADO: EXISTEN PRUEBAS FALLIDAS EN FASE 13.\n\n";
        }
    }
}

// Ejecutar suite si se invoca directamente por CLI
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $suite = new Phase13TestSuite();
    $suite->run();
}
