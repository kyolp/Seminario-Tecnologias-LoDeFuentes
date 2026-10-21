<?php

declare(strict_types=1);

/**
 * Suite de pruebas automatizadas para Fase 3 — Autenticación y Roles.
 * Ejecutable mediante PHP CLI.
 * 
 * Cobertura obligatoria:
 * T3-01 a T3-19
 */

// 1. Cargar bootstrap
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once __DIR__ . '/run_tests.php'; // Para reutilizar Phase2TestSuite en T3-17

use App\Core\App;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\UserRepository;
use App\Services\AuthService;

class Phase3TestSuite
{
    private int $total = 0;
    private int $passed = 0;
    private int $failed = 0;
    /** @var array<int, array{id: string, name: string, expected: string, obtained: string, status: string}> */
    private array $results = [];

    private AuthService $auth;
    private UserRepository $userRepo;
    private int $createdAdminId = 0;
    private int $createdInacId = 0;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->auth = new AuthService($this->userRepo);
    }

    public function run(): void
    {
        echo "============================================================\n";
        echo "SUITE DE PRUEBAS AUTOMATIZADAS — FASE 3\n";
        echo "Autenticación y Roles — Escuela Oficial Rural Mixta Lo de Fuentes\n";
        echo "============================================================\n\n";

        $this->ensureDevUsers();

        try {
            $this->testT3_01_LoginValidoAdministrador();
        $this->testT3_02_LoginValidoDocente();
        $this->testT3_03_UsuarioInexistente();
        $this->testT3_04_PasswordIncorrecta();
        $this->testT3_05_UsuarioInactivo();
        $this->testT3_06_RegeneracionSesion();
        $this->testT3_07_RutaPrivadaSinAutenticacion();
        $this->testT3_08_AdministradorAccedeAdmin();
        $this->testT3_09_DocenteAccedeDocente();
        $this->testT3_10_DocenteIntentaAdmin();
        $this->testT3_11_AdministradorRutaRestringidaPolitica();
        $this->testT3_12_Logout();
        $this->testT3_13_ManipulacionManualRol();
        $this->testT3_14_CsrfLoginLogout();
        $this->testT3_15_UsuarioDesactivadoConSesionPrevia();
        $this->testT3_16_PasswordNoExpuesta();
        $this->testT3_17_RegresionFase2();
        $this->testT3_18_RegresionFase1();
        $this->testT3_19_RegeneracionRealSesionHttp();
        } finally {
            $this->cleanupDevUsers();
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

    /**
     * Asegurar que las cuentas de prueba con contraseñas documentadas existan en la BD de desarrollo.
     */
    private function ensureDevUsers(): void
    {
        $pdo = Database::getConnection();

        // Admin: Admin.2026!
        $adminHash = password_hash('Admin.2026!', PASSWORD_BCRYPT);
        $adminExists = (int)$pdo->query("SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'admin.demo'")->fetchColumn();
        if ($adminExists === 0) {
            $adminRolId = (int)$pdo->query("SELECT id_rol FROM roles WHERE nombre IN ('ADMINISTRACION', 'ADMINISTRADOR') LIMIT 1")->fetchColumn();
            $pdo->prepare('INSERT INTO usuarios (id_rol, nombre_usuario, correo, clave_hash, nombres, apellidos, estado) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$adminRolId, 'admin.demo', 'admin.f3@escuela.local', $adminHash, 'Admin', 'Temporal', 'ACTIVO']);
            $this->createdAdminId = (int)$pdo->lastInsertId();
        } else {
            $pdo->prepare('UPDATE usuarios SET clave_hash = ?, estado = "ACTIVO" WHERE nombre_usuario = ?')
                ->execute([$adminHash, 'admin.demo']);
        }

        // Docente: Docente.2026!
        $docenteHash = password_hash('Docente.2026!', PASSWORD_BCRYPT);
        $docExists = (int)$pdo->query("SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'carlos.mendez'")->fetchColumn();
        if ($docExists === 0) {
            $oldDocId = (int)$pdo->query("SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'docente.demo'")->fetchColumn();
            if ($oldDocId > 0) {
                $pdo->exec("UPDATE usuarios SET nombre_usuario = 'carlos.mendez', correo = 'carlos.mendez@escuela.com', nombres = 'Carlos', apellidos = 'Méndez' WHERE id_usuario = {$oldDocId}");
            } else {
                $docenteRolId = (int)$pdo->query("SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'")->fetchColumn();
                $pdo->prepare('INSERT INTO usuarios (id_rol, nombre_usuario, correo, clave_hash, nombres, apellidos, estado) VALUES (?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$docenteRolId, 'carlos.mendez', 'carlos.mendez@escuela.com', $docenteHash, 'Carlos', 'Méndez', 'ACTIVO']);
                $uId = (int)$pdo->lastInsertId();
                $pdo->exec("INSERT INTO docentes (id_usuario, codigo_docente, fecha_ingreso, estado) VALUES ($uId, 'DOC-DEMO-001', '2026-01-01', 'ACTIVO')");
            }
        } else {
            $pdo->prepare('UPDATE usuarios SET clave_hash = ?, correo = "carlos.mendez@escuela.com", nombres = "Carlos", apellidos = "Méndez", estado = "ACTIVO" WHERE nombre_usuario = ?')
                ->execute([$docenteHash, 'carlos.mendez']);
        }

        // Inactivo: Inactivo.2026!
        $inactivoHash = password_hash('Inactivo.2026!', PASSWORD_BCRYPT);
        $inacExists = (int)$pdo->query("SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'luis.ramirez'")->fetchColumn();
        if ($inacExists === 0) {
            $oldInacId = (int)$pdo->query("SELECT id_usuario FROM usuarios WHERE nombre_usuario = 'inactivo.demo'")->fetchColumn();
            if ($oldInacId > 0) {
                $pdo->exec("UPDATE usuarios SET nombre_usuario = 'luis.ramirez', correo = 'luis.ramirez@escuela.com', nombres = 'Luis', apellidos = 'Ramírez' WHERE id_usuario = {$oldInacId}");
            } else {
                $docenteRolId = (int)$pdo->query("SELECT id_rol FROM roles WHERE nombre = 'DOCENTE'")->fetchColumn();
                $pdo->prepare('INSERT INTO usuarios (id_rol, nombre_usuario, correo, clave_hash, nombres, apellidos, estado) VALUES (?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$docenteRolId, 'luis.ramirez', 'luis.ramirez@escuela.com', $inactivoHash, 'Luis', 'Ramírez', 'INACTIVO']);
            }
        } else {
            $pdo->prepare('UPDATE usuarios SET clave_hash = ?, correo = "luis.ramirez@escuela.com", nombres = "Luis", apellidos = "Ramírez", estado = "INACTIVO" WHERE nombre_usuario = ?')
                ->execute([$inactivoHash, 'luis.ramirez']);
        }
    }

    /**
     * T3-01 — Login válido ADMINISTRADOR: Autenticación exitosa, sesión creada y rol correcto.
     */
    private function testT3_01_LoginValidoAdministrador(): void
    {
        Session::destroy();
        $token = Csrf::getToken();

        $app = App::getInstance();
        $request = Request::create('POST', '/login', [], [
            'nombre_usuario' => 'admin.demo',
            'password' => 'Admin.2026!',
            '_token' => $token,
        ]);

        $response = $app->handle($request);

        $isRedirect = ($response->getStatusCode() === 302);
        $targetLocation = (string) $response->getHeader('Location');
        $locationOk = str_contains($targetLocation, '/administracion');

        $user = $this->auth->user();
        $authOk = ($user !== null) && ($user->nombre_usuario === 'admin.demo') && $user->isAdmin();

        $isOk = $isRedirect && $locationOk && $authOk;
        $obtained = $isOk
            ? sprintf('Status 302 -> %s, usuario autenticado: %s (Rol: %s)', $targetLocation, $user->nombre_usuario, $user->rol_nombre)
            : sprintf('Fallo: Status %d, Location: %s', $response->getStatusCode(), $targetLocation);

        $this->record('T3-01', 'Login válido ADMINISTRADOR', 'Autenticación exitosa, sesión creada y rol ADMINISTRADOR', $obtained, $isOk);
    }

    /**
     * T3-02 — Login válido DOCENTE: Autenticación exitosa, sesión creada y rol DOCENTE.
     */
    private function testT3_02_LoginValidoDocente(): void
    {
        Session::destroy();
        $token = Csrf::getToken();

        $app = App::getInstance();
        $request = Request::create('POST', '/login', [], [
            'nombre_usuario' => 'carlos.mendez',
            'password' => 'Docente.2026!',
            '_token' => $token,
        ]);

        $response = $app->handle($request);

        $isRedirect = ($response->getStatusCode() === 302);
        $targetLocation = (string) $response->getHeader('Location');
        $locationOk = str_contains($targetLocation, '/docente');

        $user = $this->auth->user();
        $authOk = ($user !== null) && ($user->nombre_usuario === 'carlos.mendez') && $user->isDocente();

        $isOk = $isRedirect && $locationOk && $authOk;
        $obtained = $isOk
            ? sprintf('Status 302 -> %s, usuario autenticado: %s (Rol: %s)', $targetLocation, $user->nombre_usuario, $user->rol_nombre)
            : sprintf('Fallo: Status %d, Location: %s', $response->getStatusCode(), $targetLocation);

        $this->record('T3-02', 'Login válido DOCENTE', 'Autenticación exitosa, sesión creada y rol DOCENTE', $obtained, $isOk);
    }

    /**
     * T3-03 — Usuario inexistente: Acceso rechazado con mensaje genérico sin filtrar info.
     */
    private function testT3_03_UsuarioInexistente(): void
    {
        Session::destroy();
        $token = Csrf::getToken();

        $app = App::getInstance();
        $request = Request::create('POST', '/login', [], [
            'nombre_usuario' => 'usuario_fantasma_xyz_999',
            'password' => 'Password123!',
            '_token' => $token,
        ]);

        $response = $app->handle($request);

        $rejected = ($response->getStatusCode() === 401);
        $hasGenericMessage = str_contains($response->getContent(), 'Credenciales inválidas.');
        $notAuth = !$this->auth->check();

        $isOk = $rejected && $hasGenericMessage && $notAuth;
        $obtained = $isOk
            ? 'Status 401 con mensaje genérico "Credenciales inválidas." sin sesión creada'
            : sprintf('Fallo: Status %d', $response->getStatusCode());

        $this->record('T3-03', 'Usuario inexistente', 'Status 401 con mensaje genérico "Credenciales inválidas."', $obtained, $isOk);
    }

    /**
     * T3-04 — Contraseña incorrecta: Acceso rechazado con mensaje genérico sin revelar datos.
     */
    private function testT3_04_PasswordIncorrecta(): void
    {
        Session::destroy();
        $token = Csrf::getToken();

        $app = App::getInstance();
        $request = Request::create('POST', '/login', [], [
            'nombre_usuario' => 'admin.demo',
            'password' => 'PasswordTotalmenteErronea!',
            '_token' => $token,
        ]);

        $response = $app->handle($request);

        $rejected = ($response->getStatusCode() === 401);
        $hasGenericMessage = str_contains($response->getContent(), 'Credenciales inválidas.');
        $notAuth = !$this->auth->check();

        $isOk = $rejected && $hasGenericMessage && $notAuth;
        $obtained = $isOk
            ? 'Status 401 con mensaje genérico "Credenciales inválidas." sin revelar existencia de usuario'
            : sprintf('Fallo: Status %d', $response->getStatusCode());

        $this->record('T3-04', 'Contraseña incorrecta', 'Status 401 con mensaje genérico "Credenciales inválidas."', $obtained, $isOk);
    }

    /**
     * T3-05 — Usuario inactivo: Acceso rechazado para cuentas en estado INACTIVO o BLOQUEADO.
     */
    private function testT3_05_UsuarioInactivo(): void
    {
        Session::destroy();
        $token = Csrf::getToken();

        $app = App::getInstance();
        $request = Request::create('POST', '/login', [], [
            'nombre_usuario' => 'inactivo.demo',
            'password' => 'Inactivo.2026!',
            '_token' => $token,
        ]);

        $response = $app->handle($request);

        $rejected = ($response->getStatusCode() === 401);
        $hasGenericMessage = str_contains($response->getContent(), 'Credenciales inválidas.');
        $notAuth = !$this->auth->check();

        $isOk = $rejected && $hasGenericMessage && $notAuth;
        $obtained = $isOk
            ? 'Status 401: Usuario inactivo rechazado sin iniciar sesión'
            : sprintf('Fallo: Status %d', $response->getStatusCode());

        $this->record('T3-05', 'Usuario inactivo', 'Acceso rechazado y sesión no autorizada para usuario inactivo', $obtained, $isOk);
    }

    /**
     * T3-06 — Regeneración de sesión: El ID de sesión cambia tras la autenticación correcta.
     */
    private function testT3_06_RegeneracionSesion(): void
    {
        Session::destroy();
        Session::start();
        $idBefore = Session::id();
        $token = Csrf::getToken();

        $app = App::getInstance();
        $request = Request::create('POST', '/login', [], [
            'nombre_usuario' => 'admin.demo',
            'password' => 'Admin.2026!',
            '_token' => $token,
        ]);

        $app->handle($request);
        $idAfter = Session::id();

        $idChanged = ($idBefore !== $idAfter) && !empty($idAfter);

        $this->record(
            'T3-06',
            'Regeneración de sesión',
            'ID de sesión antes != ID de sesión después',
            sprintf('Antes: "%s" | Después: "%s" (Cambió: %s)', $idBefore, $idAfter, $idChanged ? 'SÍ' : 'NO'),
            $idChanged
        );
    }

    /**
     * T3-07 — Ruta privada sin autenticación: Redirección al login con status 302.
     */
    private function testT3_07_RutaPrivadaSinAutenticacion(): void
    {
        Session::destroy();

        $app = App::getInstance();

        $reqAdmin = Request::create('GET', '/administracion');
        $respAdmin = $app->handle($reqAdmin);

        $reqDocente = Request::create('GET', '/docente');
        $respDocente = $app->handle($reqDocente);

        $adminRedirect = ($respAdmin->getStatusCode() === 302) && str_contains($respAdmin->getHeader('Location'), '/login');
        $docenteRedirect = ($respDocente->getStatusCode() === 302) && str_contains($respDocente->getHeader('Location'), '/login');

        $isOk = $adminRedirect && $docenteRedirect;
        $obtained = $isOk
            ? 'Rutas /administracion y /docente redirigen a /login con HTTP 302 sin sesión previa'
            : sprintf('Fallo: Admin status=%d, Docente status=%d', $respAdmin->getStatusCode(), $respDocente->getStatusCode());

        $this->record('T3-07', 'Ruta privada sin autenticación', 'Redirección controlada HTTP 302 a /login', $obtained, $isOk);
    }

    /**
     * T3-08 — ADMINISTRADOR accede a ruta administrativa: Acceso autorizado con vista correcta.
     */
    private function testT3_08_AdministradorAccedeAdmin(): void
    {
        $this->auth->attempt('admin.demo', 'Admin.2026!');

        $app = App::getInstance();
        $request = Request::create('GET', '/administracion');
        $response = $app->handle($request);

        $statusOk = ($response->getStatusCode() === 200);
        $contentOk = str_contains($response->getContent(), 'Área administrativa — sesión válida')
            && str_contains($response->getContent(), 'admin.demo');

        $isOk = $statusOk && $contentOk;
        $obtained = $isOk
            ? 'Status 200: Vista "Área administrativa — sesión válida" renderizada correctamente'
            : sprintf('Fallo: Status %d', $response->getStatusCode());

        $this->record('T3-08', 'ADMINISTRADOR accede a ruta administrativa', 'HTTP 200 con contenido de área administrativa', $obtained, $isOk);
    }

    /**
     * T3-09 — DOCENTE accede a ruta docente: Acceso autorizado con vista correcta.
     */
    private function testT3_09_DocenteAccedeDocente(): void
    {
        $this->auth->attempt('carlos.mendez', 'Docente.2026!');

        $app = App::getInstance();
        $request = Request::create('GET', '/docente');
        $response = $app->handle($request);

        $statusOk = ($response->getStatusCode() === 200);
        $contentOk = str_contains($response->getContent(), 'Portal docente — sesión válida')
            && str_contains($response->getContent(), 'carlos.mendez');

        $isOk = $statusOk && $contentOk;
        $obtained = $isOk
            ? 'Status 200: Vista "Portal docente — sesión válida" renderizada correctamente'
            : sprintf('Fallo: Status %d', $response->getStatusCode());

        $this->record('T3-09', 'DOCENTE accede a ruta docente', 'HTTP 200 con contenido de portal docente', $obtained, $isOk);
    }

    /**
     * T3-10 — DOCENTE intenta ruta administrativa: Rechazado con HTTP 403 Forbidden.
     */
    private function testT3_10_DocenteIntentaAdmin(): void
    {
        $this->auth->attempt('carlos.mendez', 'Docente.2026!');

        $app = App::getInstance();
        $request = Request::create('GET', '/administracion');
        $response = $app->handle($request);

        $status403 = ($response->getStatusCode() === 403);
        $deniedView = str_contains($response->getContent(), '403')
            && str_contains($response->getContent(), 'Acceso Denegado')
            && !str_contains($response->getContent(), 'Área administrativa — sesión válida');

        $isOk = $status403 && $deniedView;
        $obtained = $isOk
            ? 'HTTP 403 Forbidden emitido con vista controlada "Acceso Denegado"'
            : sprintf('Fallo: Status %d', $response->getStatusCode());

        $this->record('T3-10', 'DOCENTE intenta ruta administrativa', 'HTTP 403 con vista controlada sin acceso a datos administrativos', $obtained, $isOk);
    }

    /**
     * T3-11 — ADMINISTRADOR ante ruta restringida según política:
     * Política institucional: El portal docente requiere estrictamente rol DOCENTE (privilegio mínimo).
     */
    private function testT3_11_AdministradorRutaRestringidaPolitica(): void
    {
        $this->auth->attempt('admin.demo', 'Admin.2026!');

        $app = App::getInstance();
        $request = Request::create('GET', '/docente');
        $response = $app->handle($request);

        // La política de mínimo privilegio restringe /docente exclusivamente a DOCENTE
        $status403 = ($response->getStatusCode() === 403);
        $contentDenied = str_contains($response->getContent(), 'Acceso Denegado')
            && !str_contains($response->getContent(), 'Portal docente — sesión válida');

        $isOk = $status403 && $contentDenied;
        $obtained = $isOk
            ? 'HTTP 403 emitido conforme a la política de mínimo privilegio (área docente reservada a docentes)'
            : sprintf('Fallo: Status %d', $response->getStatusCode());

        $this->record('T3-11', 'ADMINISTRADOR ante ruta docente (política)', 'HTTP 403 bajo política estricta de privilegio mínimo por rol', $obtained, $isOk);
    }

    /**
     * T3-12 — Logout: Sesión destruida e imposibilidad de acceder a rutas privadas posteriores.
     */
    private function testT3_12_Logout(): void
    {
        $this->auth->attempt('admin.demo', 'Admin.2026!');
        $token = Csrf::getToken();

        $app = App::getInstance();
        $logoutReq = Request::create('POST', '/logout', [], ['_token' => $token]);
        $logoutResp = $app->handle($logoutReq);

        $logoutRedirect = ($logoutResp->getStatusCode() === 302);
        $notAuth = !$this->auth->check();

        // Intento de reingreso a ruta privada
        $subsequentReq = Request::create('GET', '/administracion');
        $subsequentResp = $app->handle($subsequentReq);
        $subsequentBlocked = ($subsequentResp->getStatusCode() === 302) && str_contains($subsequentResp->getHeader('Location'), '/login');

        $isOk = $logoutRedirect && $notAuth && $subsequentBlocked;
        $obtained = $isOk
            ? 'Logout ejecutó redirección 302, destruyó sesión y bloqueó acceso posterior a /administracion'
            : 'Fallo al invalidar sesión en logout';

        $this->record('T3-12', 'Cierre de sesión (Logout)', 'Sesión destruida y rutas privadas inaccesibles tras logout', $obtained, $isOk);
    }

    /**
     * T3-13 — Manipulación manual de rol: Intentar alterar cookies o parámetros no eleva privilegios.
     */
    private function testT3_13_ManipulacionManualRol(): void
    {
        $this->auth->attempt('carlos.mendez', 'Docente.2026!');

        // Intento 1: Manipular la variable de sesión directamente en memoria
        Session::set('auth_role', 'ADMINISTRADOR');

        // Intento 2: Inyectar parámetros en solicitud
        $app = App::getInstance();
        $request = Request::create('GET', '/administracion?role=ADMINISTRADOR&is_admin=1', [
            'auth_role' => 'ADMINISTRADOR',
        ]);

        $response = $app->handle($request);

        // El backend consulta UserRepository::findById() en tiempo real y detecta que el usuario es DOCENTE
        $isOk = ($response->getStatusCode() === 403);
        $obtained = $isOk
            ? 'HTTP 403: El backend valida el rol contra base de datos en tiempo real; manipulación client-side rechazada'
            : sprintf('Fallo: Status %d (Vulnerabilidad de elevación de privilegios detectada)', $response->getStatusCode());

        $this->record('T3-13', 'Manipulación manual de rol', 'HTTP 403: Verificación autoritativa en BD impide elevación de privilegios', $obtained, $isOk);
    }

    /**
     * T3-14 — CSRF login/logout: Token obligatorio para solicitudes mutantes POST.
     */
    private function testT3_14_CsrfLoginLogout(): void
    {
        Session::destroy();
        $app = App::getInstance();

        // 1. POST sin token
        $reqNoToken = Request::create('POST', '/login', [], [
            'nombre_usuario' => 'admin.demo',
            'password' => 'Admin.2026!',
        ]);
        $respNoToken = $app->handle($reqNoToken);
        $blockedNoToken = ($respNoToken->getStatusCode() === 403);

        // 2. POST con token falsificado
        $reqBadToken = Request::create('POST', '/login', [], [
            'nombre_usuario' => 'admin.demo',
            'password' => 'Admin.2026!',
            '_token' => 'token_malicioso_ficticio_123',
        ]);
        $respBadToken = $app->handle($reqBadToken);
        $blockedBadToken = ($respBadToken->getStatusCode() === 403);

        // 3. POST con token genuino
        $genuineToken = Csrf::getToken();
        $reqValid = Request::create('POST', '/login', [], [
            'nombre_usuario' => 'admin.demo',
            'password' => 'Admin.2026!',
            '_token' => $genuineToken,
        ]);
        $respValid = $app->handle($reqValid);
        $allowedValid = ($respValid->getStatusCode() === 302);

        $isOk = $blockedNoToken && $blockedBadToken && $allowedValid;
        $obtained = $isOk
            ? 'POST sin token: 403 | POST con token inválido: 403 | POST con token válido: 302'
            : sprintf('Fallo: noToken=%d, badToken=%d, validToken=%d', $respNoToken->getStatusCode(), $respBadToken->getStatusCode(), $respValid->getStatusCode());

        $this->record('T3-14', 'Protección CSRF en login/logout', 'Bloqueo 403 sin token o con token inválido; permitido con token válido', $obtained, $isOk);
    }

    /**
     * T3-15 — Usuario desactivado con sesión previa: No mantiene privilegios tras ser desactivado en BD.
     */
    private function testT3_15_UsuarioDesactivadoConSesionPrevia(): void
    {
        // 1. Iniciar sesión como docente activo
        $this->auth->attempt('carlos.mendez', 'Docente.2026!');
        $user = $this->auth->user();

        $app = App::getInstance();
        $reqInitial = Request::create('GET', '/docente');
        $respInitial = $app->handle($reqInitial);
        $initOk = ($respInitial->getStatusCode() === 200);

        // 2. Simular desactivación en base de datos mientras la sesión sigue abierta
        $this->userRepo->updateStatus($user->id_usuario, 'INACTIVO');

        // 3. Intentar acceder nuevamente a la ruta protegida con la misma sesión
        $reqAfter = Request::create('GET', '/docente');
        $respAfter = $app->handle($reqAfter);

        // Al consultar $auth->user(), detecta estado INACTIVO, revoca la sesión y redirige a /login
        $blockedAfter = ($respAfter->getStatusCode() === 302) && str_contains($respAfter->getHeader('Location'), '/login');
        $sessionDestroyed = !$this->auth->check();

        // 4. Restaurar estado ACTIVO para no afectar pruebas subsiguientes
        $this->userRepo->updateStatus($user->id_usuario, 'ACTIVO');

        $isOk = $initOk && $blockedAfter && $sessionDestroyed;
        $obtained = $isOk
            ? 'Usuario desactivado en BD fue bloqueado en la siguiente petición y su sesión fue revocada (HTTP 302 -> /login)'
            : sprintf('Fallo: init=%d, after=%d, sessionRevoked=%s', $respInitial->getStatusCode(), $respAfter->getStatusCode(), $sessionDestroyed ? 'SÍ' : 'NO');

        $this->record('T3-15', 'Usuario desactivado con sesión previa', 'Revocación inmediata de sesión ante desactivación en base de datos', $obtained, $isOk);
    }

    /**
     * T3-16 — Contraseña no expuesta: Verificar que vistas, logs y respuestas no contengan contraseñas o hashes.
     */
    private function testT3_16_PasswordNoExpuesta(): void
    {
        $app = App::getInstance();

        // Probar vista login
        $respLogin = $app->handle(Request::create('GET', '/login'));
        $loginContent = $respLogin->getContent();

        // Probar vista admin
        $this->auth->attempt('admin.demo', 'Admin.2026!');
        $respAdmin = $app->handle(Request::create('GET', '/administracion'));
        $adminContent = $respAdmin->getContent();

        // Probar vista docente
        $this->auth->attempt('carlos.mendez', 'Docente.2026!');
        $respDocente = $app->handle(Request::create('GET', '/docente'));
        $docenteContent = $respDocente->getContent();

        // Probar vista 403
        $resp403 = $app->handle(Request::create('GET', '/administracion'));
        $content403 = $resp403->getContent();

        // Verificar logs
        $logPath = dirname(__DIR__) . '/storage/logs/app.log';
        $logContent = file_exists($logPath) ? (string) file_get_contents($logPath) : '';

        $sensitiveStrings = [
            'Admin.2026!',
            'Docente.2026!',
            'Inactivo.2026!',
            '$2y$10$',
        ];

        $allClean = true;
        foreach ($sensitiveStrings as $secret) {
            if (str_contains($loginContent, $secret)
                || str_contains($adminContent, $secret)
                || str_contains($docenteContent, $secret)
                || str_contains($content403, $secret)
                || str_contains($logContent, $secret)) {
                $allClean = false;
                break;
            }
        }

        $this->record(
            'T3-16',
            'Confidencialidad de contraseñas y hashes',
            'Cero contraseñas o hashes en vistas, logs o respuestas HTTP',
            $allClean ? 'Ningún secreto ni hash BCrypt presente en vistas, logs o respuestas' : 'Fallo: Secreto o hash expuesto',
            $allClean
        );
    }

    /**
     * T3-17 — Regresión Fase 2: Ejecutar la suite completa de Fase 2 (16/16 OK).
     */
    private function testT3_17_RegresionFase2(): void
    {
        ob_start();
        $suiteF2 = new Phase2TestSuite();
        try {
            $suiteF2->run();
            $out = ob_get_clean();
            $hasSuccess = str_contains($out, '16') && str_contains($out, 'TODAS LAS PRUEBAS DE FASE 2 SUPERADAS SATISFACTORIAMENTE');
            $has0Fail = str_contains($out, 'Fallidas (FALLO) : 0');
            $isOk = $hasSuccess && $has0Fail;
            $obtained = $isOk ? '16/16 pruebas de Fase 2 ejecutadas con 16 OK y 0 fallos' : 'Fallo en regresión de Fase 2';
        } catch (\Throwable $e) {
            ob_end_clean();
            $isOk = false;
            $obtained = 'Excepción en regresión Fase 2: ' . $e->getMessage();
        }

        $this->record('T3-17', 'Regresión completa de Fase 2', '16/16 pruebas de Fase 2 superadas satisfactoriamente', $obtained, $isOk);
    }

    /**
     * T3-18 — Regresión Fase 1: Ejecutar validación funcional de base de datos en BD temporal (18/18 OK).
     */
    private function testT3_18_RegresionFase1(): void
    {
        $root = dirname(__DIR__);
        $mysqlBinary = $this->findMysqlBinary();

        if ($mysqlBinary === null) {
            $this->record('T3-18', 'Regresión completa de Fase 1', '18/18 pruebas superadas en BD temporal', 'Fallo: mysql.exe no encontrado', false);
            return;
        }

        $dbHost = (string) Config::get('database.connections.mysql.host', '127.0.0.1');
        $dbPort = (int) Config::get('database.connections.mysql.port', 3307);
        $dbUser = (string) Config::get('database.connections.mysql.username', 'root');
        $dbPass = (string) Config::get('database.connections.mysql.password', '');
        $tempDb = 'escuela_fase3_regresion_fase1_temp';

        $schemaFile = str_replace('/', '\\', $root . '/database/schema.sql');
        $seedFile = file_exists($root . '/database/tests/seed_fase1.sql') ? str_replace('/', '\\', $root . '/database/tests/seed_fase1.sql') : str_replace('/', '\\', $root . '/database/seed.sql');
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
            // 1. Crear base de datos temporal
            shell_exec($baseCmd . ' -e "DROP DATABASE IF EXISTS ' . $tempDb . ';" 2>&1');
            shell_exec($baseCmd . ' -e "CREATE DATABASE ' . $tempDb . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1');

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
                $obtained = 'Fallo en validación de Fase 1: ' . substr(strip_tags($output), -300);
            }
        } catch (\Throwable $e) {
            $isOk = false;
            $obtained = 'Excepción durante regresión: ' . $e->getMessage();
        } finally {
            // 6. Eliminar base de datos temporal
            shell_exec($baseCmd . ' -e "DROP DATABASE IF EXISTS ' . $tempDb . ';" 2>&1');
        }

        $this->record('T3-18', 'Regresión completa de Fase 1', '18/18 pruebas automáticas ejecutadas exitosamente (18 OK, 0 fallos)', $obtained, $isOk);
    }

    /**
     * T3-19 — Regeneración real de sesión mediante HTTP:
     * Comprobación en cliente HTTP (cURL) de emisión de Set-Cookie con un identificador
     * nuevo y diferente tras el inicio de sesión exitoso (mitigación contra Session Fixation).
     */
    private function testT3_19_RegeneracionRealSesionHttp(): void
    {
        $port = $this->findFreePort(8091, 8099);
        $phpBin = PHP_BINARY;
        $docRoot = dirname(__DIR__) . '/public';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $cmd = sprintf('"%s" -S 127.0.0.1:%d -t "%s"', $phpBin, $port, $docRoot);
        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));

        if (!is_resource($process)) {
            $this->record('T3-19', 'Regeneración real de sesión mediante HTTP', 'Cookie regenerada (cookie_before != cookie_after) con redirección 302', 'No se pudo iniciar servidor temporal PHP', false);
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
            $this->record('T3-19', 'Regeneración real de sesión mediante HTTP', 'Cookie regenerada (cookie_before != cookie_after) con redirección 302', 'El servidor web temporal no respondió a tiempo en el puerto ' . $port, false);
            return;
        }

        $cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_test_' . uniqid() . '.txt';
        $isOk = false;
        $obtained = '';

        try {
            // 1. GET /login para capturar cookie inicial y token CSRF
            $ch = curl_init("http://127.0.0.1:$port/login");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            $response = curl_exec($ch);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headers = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);
            curl_close($ch);

            $cookieBefore = null;
            if (preg_match('/Set-Cookie:\s*([^;=]+)=([^;]+)/i', $headers, $m)) {
                $cookieName = $m[1];
                $cookieBefore = $m[2];
            } else {
                $cookieName = 'PHPSESSID';
            }

            $token = null;
            if (preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/', $body, $m)) {
                $token = $m[1];
            }

            if (empty($token)) {
                throw new \Exception('No se pudo extraer el token CSRF del formulario /login');
            }

            // 2. POST /login con credenciales válidas enviando cookie previa
            $postFields = http_build_query([
                '_token' => $token,
                'nombre_usuario' => 'admin.demo',
                'password' => 'Admin.2026!'
            ]);

            $ch = curl_init("http://127.0.0.1:$port/login");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // No seguir redirección

            $postResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $postHeaderSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $postHeaders = substr($postResponse, 0, $postHeaderSize);
            curl_close($ch);

            $cookieAfter = null;
            if (preg_match_all('/Set-Cookie:\s*([^;=]+)=([^;]+)/i', $postHeaders, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    if ($match[1] === $cookieName || stripos($match[1], 'sess') !== false) {
                        $cookieAfter = $match[2];
                    }
                }
            }

            $location = '';
            if (preg_match('/Location:\s*([^\r\n]+)/i', $postHeaders, $m)) {
                $location = trim($m[1]);
            }

            $statusRedirect = ($httpCode === 302 && str_contains($location, '/administracion'));
            $cookieChanged = (!empty($cookieBefore) && !empty($cookieAfter) && $cookieBefore !== $cookieAfter);

            $isOk = $statusRedirect && $cookieChanged;
            $obtained = sprintf(
                'Status %d -> %s | Cookie antes: "%s" | Cookie después: "%s" (Cambió: %s)',
                $httpCode,
                $location,
                $cookieBefore ?? 'N/D',
                $cookieAfter ?? 'N/D',
                $cookieChanged ? 'SÍ' : 'NO'
            );
        } catch (\Throwable $e) {
            $isOk = false;
            $obtained = 'Excepción en prueba HTTP real: ' . $e->getMessage();
        } finally {
            $this->terminateProcess($pid, $process, $pipes);
            if (file_exists($cookieFile)) {
                @unlink($cookieFile);
            }
        }

        $this->record(
            'T3-19',
            'Regeneración real de sesión mediante HTTP',
            'Cookie de sesión regenerada vía HTTP real tras login exitoso (cookie_before != cookie_after) con redirección 302',
            $obtained,
            $isOk
        );
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

    private function cleanupDevUsers(): void
    {
        $pdo = Database::getConnection();
        if ($this->createdAdminId > 0) {
            $pdo->exec("DELETE FROM usuarios WHERE id_usuario = {$this->createdAdminId}");
        }
        if ($this->createdInacId > 0) {
            $pdo->exec("DELETE FROM usuarios WHERE id_usuario = {$this->createdInacId}");
        }
        $pdo->prepare('UPDATE usuarios SET clave_hash = "$2y$10$9zCQrOhppuS5iHV8FMda8ui6xqxmRV1DbN/ZdmpJSP7Es4BoiCul6" WHERE nombre_usuario = "carlos.mendez"')->execute();
    }

    private function printSummary(): void
    {
        echo "\n============================================================\n";
        echo "RESUMEN DE PRUEBAS DE FASE 3\n";
        echo "============================================================\n";
        printf("Total ejecutadas : %d\n", $this->total);
        printf("Exitosas (OK)    : %d\n", $this->passed);
        printf("Fallidas (FALLO) : %d\n", $this->failed);
        echo "============================================================\n";

        if ($this->failed === 0) {
            echo "RESULTADO: TODAS LAS PRUEBAS DE FASE 3 SUPERADAS SATISFACTORIAMENTE.\n";
        } else {
            echo "RESULTADO: EXISTEN PRUEBAS FALLIDAS.\n";
            exit(1);
        }
    }
}

$suite = new Phase3TestSuite();
$suite->run();
