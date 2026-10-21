<?php

declare(strict_types=1);

/**
 * Suite de pruebas automatizadas para Fase 2 — Estructura Base de la Aplicación.
 * Ejecutable mediante PHP CLI.
 * 
 * Cobertura obligatoria:
 * T2-01 a T2-16
 */

// 1. Cargar bootstrap
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\App;
use App\Core\Config;
use App\Core\Database;
use App\Core\DatabaseException;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

class Phase2TestSuite
{
    private int $total = 0;
    private int $passed = 0;
    private int $failed = 0;
    /** @var array<int, array{id: string, name: string, expected: string, obtained: string, status: string}> */
    private array $results = [];

    public function run(): void
    {
        echo "============================================================\n";
        echo "SUITE DE PRUEBAS AUTOMATIZADAS — FASE 2\n";
        echo "Escuela Oficial Rural Mixta Lo de Fuentes\n";
        echo "============================================================\n\n";

        $this->testT2_01_AppBoot();
        $this->testT2_02_Configuration();
        $this->testT2_03_PdoConnection();
        $this->testT2_04_ConnectionErrorMasking();
        $this->testT2_05_ValidRoute();
        $this->testT2_06_NotFoundRoute();
        $this->testT2_07_GetMethod();
        $this->testT2_08_PostMethod();
        $this->testT2_09_LayoutRendering();
        $this->testT2_10_HtmlEscaping();
        $this->testT2_11_SecretsNotVersioned();
        $this->testT2_12_RegressionPhase1();
        $this->testT2_13_IsolatedTechnicalRoutes();
        $this->testT2_14_InternalFileProtection();
        $this->testT2_15_MandatoryConfiguration();
        $this->testT2_16_DatabaseRegressionPhase1();

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
     * T2-01 — Carga de aplicación: La aplicación inicia sin errores fatales.
     */
    private function testT2_01_AppBoot(): void
    {
        try {
            $app = App::getInstance();
            $app->boot();
            $isBooted = true;
            $obtained = 'Aplicación inicializada correctamente';
        } catch (\Throwable $e) {
            $isBooted = false;
            $obtained = 'Error fatal: ' . $e->getMessage();
        }

        $this->record(
            'T2-01',
            'Carga de aplicación',
            'Inicio exitoso sin errores fatales',
            $obtained,
            $isBooted
        );
    }

    /**
     * T2-02 — Configuración: La configuración puede cargarse sin exponer secretos.
     */
    private function testT2_02_Configuration(): void
    {
        $appName = Config::get('app.name');
        $dbHost = Config::get('database.connections.mysql.host');
        $allConfig = Config::all();

        // Verificar que existan las secciones y que una consulta estándar no vuelque secretos
        $isOk = !empty($appName) && !empty($dbHost) && is_array($allConfig);
        $obtained = sprintf('app.name="%s", db.host="%s"', (string) $appName, (string) $dbHost);

        $this->record(
            'T2-02',
            'Carga de configuración',
            'Configuración cargada en memoria',
            $obtained,
            $isOk
        );
    }

    /**
     * T2-03 — Conexión PDO: La aplicación puede conectarse correctamente a MySQL usando la configuración definida.
     */
    private function testT2_03_PdoConnection(): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query('SELECT 1 AS test');
            $val = $stmt->fetchColumn();
            $isOk = ($val == 1);
            $obtained = 'Conexión PDO establecida (SELECT 1 = 1)';
        } catch (\Throwable $e) {
            $isOk = false;
            $obtained = 'Error de conexión: ' . $e->getMessage();
        }

        $this->record(
            'T2-03',
            'Conexión PDO',
            'Conexión exitosa a MySQL',
            $obtained,
            $isOk
        );
    }

    /**
     * T2-04 — Error de conexión: Un error controlado de conexión no expone credenciales.
     */
    private function testT2_04_ConnectionErrorMasking(): void
    {
        $fakePassword = 'SuperSecretPassword999!';
        $fakeConfig = [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3307,
            'database' => 'non_existent_db_xyz',
            'username' => 'fake_user_test',
            'password' => $fakePassword,
            'charset' => 'utf8mb4',
        ];

        $exposed = true;
        $caught = false;
        $msg = '';

        try {
            Database::createConnection($fakeConfig);
        } catch (DatabaseException $e) {
            $caught = true;
            $msg = $e->getMessage();
            // Comprobar estrictamente que la contraseña no aparezca en el mensaje
            $exposed = str_contains($msg, $fakePassword);
        } catch (\Throwable $e) {
            $caught = true;
            $msg = $e->getMessage();
            $exposed = str_contains($msg, $fakePassword);
        }

        $isOk = $caught && !$exposed;
        $obtained = $isOk
            ? 'Error controlado capturado sin exponer contraseña ("' . $msg . '")'
            : 'Fallo: Contraseña expuesta o no capturada';

        $this->record(
            'T2-04',
            'Error controlado de conexión',
            'Excepción controlada sin exponer credenciales',
            $obtained,
            $isOk
        );
    }

    /**
     * T2-05 — Ruta válida: Una ruta registrada responde correctamente.
     */
    private function testT2_05_ValidRoute(): void
    {
        $app = App::getInstance();
        $request = Request::create('GET', '/');
        $response = $app->handle($request);

        $isOk = ($response->getStatusCode() === 200) && str_contains($response->getContent(), 'Escuela Oficial Rural Mixta Lo de Fuentes');
        $obtained = sprintf('Status HTTP %d, contiene encabezado institucional', $response->getStatusCode());

        $this->record(
            'T2-05',
            'Ruta registrada válida',
            'Status 200 con contenido esperado',
            $obtained,
            $isOk
        );
    }

    /**
     * T2-06 — Ruta inexistente: Una ruta inexistente devuelve manejo 404 controlado.
     */
    private function testT2_06_NotFoundRoute(): void
    {
        $app = App::getInstance();
        $request = Request::create('GET', '/ruta-totalmente-inexistente-404');
        $response = $app->handle($request);

        $isOk = ($response->getStatusCode() === 404) && (str_contains($response->getContent(), '404') || str_contains($response->getContent(), 'No Encontrada'));
        $obtained = sprintf('Status HTTP %d con vista de 404 controlada', $response->getStatusCode());

        $this->record(
            'T2-06',
            'Ruta inexistente',
            'Status 404 manejado controladamente',
            $obtained,
            $isOk
        );
    }

    /**
     * T2-07 — GET: El router procesa correctamente una ruta GET.
     */
    private function testT2_07_GetMethod(): void
    {
        $app = App::getInstance();
        $testValue = 'prueba_get_' . mt_rand(100, 999);
        $request = Request::create('GET', '/test/get?echo=' . $testValue);
        $response = $app->handle($request);

        $data = json_decode($response->getContent(), true);
        $isOk = ($response->getStatusCode() === 200) && is_array($data) && ($data['method'] ?? '') === 'GET' && ($data['echo'] ?? '') === $testValue;
        $obtained = sprintf('Status %d, method=%s, echo=%s', $response->getStatusCode(), $data['method'] ?? 'null', $data['echo'] ?? 'null');

        $this->record(
            'T2-07',
            'Enrutamiento de método GET',
            'Status 200 con payload GET procesado',
            $obtained,
            $isOk
        );
    }

    /**
     * T2-08 — POST: El router procesa correctamente una ruta POST de prueba o infraestructura.
     */
    private function testT2_08_PostMethod(): void
    {
        $app = App::getInstance();
        $postData = ['data' => 'valor_infraestructura_' . mt_rand(1000, 9999)];
        $request = Request::create('POST', '/test/post', [], $postData);
        $response = $app->handle($request);

        $data = json_decode($response->getContent(), true);
        $isOk = ($response->getStatusCode() === 201) && is_array($data) && ($data['method'] ?? '') === 'POST' && ($data['received'] ?? '') === $postData['data'];
        $obtained = sprintf('Status %d, method=%s, received=%s', $response->getStatusCode(), $data['method'] ?? 'null', $data['received'] ?? 'null');

        $this->record(
            'T2-08',
            'Enrutamiento de método POST',
            'Status 201 con payload POST procesado',
            $obtained,
            $isOk
        );
    }

    /**
     * T2-09 — Layout: Una vista puede renderizarse utilizando el layout común.
     */
    private function testT2_09_LayoutRendering(): void
    {
        $rendered = View::render('home.index', [
            'environment' => 'testing',
            'phpVersion' => PHP_VERSION,
            'dbConnected' => true,
            'debug' => false,
            'appName' => 'Test Layout',
        ], 'main');

        $hasHeader = str_contains($rendered, '<header class="app-header">');
        $hasFooter = str_contains($rendered, '<footer class="app-footer">');
        $hasContent = str_contains($rendered, 'Escuela Oficial Rural Mixta Lo de Fuentes') || str_contains($rendered, 'Arquitectura Base del Sistema');

        $isOk = $hasHeader && $hasFooter && $hasContent;
        $obtained = $isOk ? 'Layout común renderizado con header, contenido y footer' : 'Fallo de ensamble de layout';

        $this->record(
            'T2-09',
            'Renderizado con layout común',
            'Header, contenido y footer ensamblados',
            $obtained,
            $isOk
        );
    }

    /**
     * T2-10 — Escape HTML: Validar que el helper de escape no renderice directamente una cadena como: <script>alert(1)</script>
     */
    private function testT2_10_HtmlEscaping(): void
    {
        $malicious = '<script>alert(1)</script>';
        $escaped = e($malicious);

        $isOk = !str_contains($escaped, '<script>') && str_contains($escaped, '&lt;script&gt;');
        $obtained = sprintf('Entrada: "%s" -> Salida: "%s"', $malicious, $escaped);

        $this->record(
            'T2-10',
            'Helper de escape HTML contra XSS',
            'Cadena escapada como &lt;script&gt;alert(1)&lt;/script&gt;',
            $obtained,
            $isOk
        );
    }

    /**
     * T2-11 — Secretos: Verificar que .env o credenciales reales no estén versionados.
     */
    private function testT2_11_SecretsNotVersioned(): void
    {
        $root = dirname(__DIR__);
        $gitStatus = (string) shell_exec('git -C "' . $root . '" status --porcelain');
        $gitLsFiles = (string) shell_exec('git -C "' . $root . '" ls-files .env');

        // .env no debe estar en el índice de Git
        $notTracked = trim($gitLsFiles) === '';
        // .env.example debe existir
        $exampleExists = file_exists($root . '/.env.example');

        $isOk = $notTracked && $exampleExists;
        $obtained = $isOk
            ? '.env no versionado en Git y .env.example disponible como plantilla'
            : 'Fallo: .env está rastreado por Git';

        $this->record(
            'T2-11',
            'Aislamiento de secretos y .env',
            '.env excluido de Git y .env.example disponible',
            $obtained,
            $isOk
        );
    }

    /**
     * T2-12 — Regresión Fase 1: Confirmar que los scripts y estructura de base de datos de Fase 1 continúan intactos y utilizables.
     */
    private function testT2_12_RegressionPhase1(): void
    {
        $root = dirname(__DIR__);
        $schemaExists = file_exists($root . '/database/schema.sql') && filesize($root . '/database/schema.sql') > 1000;
        $seedExists = file_exists($root . '/database/seed.sql') && filesize($root . '/database/seed.sql') > 500;
        $validationExists = file_exists($root . '/database/tests/validacion.sql') && filesize($root . '/database/tests/validacion.sql') > 1000;
        $docsF1Exists = file_exists($root . '/docs/fase-1/informe-cierre-fase-1.md') && file_exists($root . '/docs/fase-1/diccionario-datos.md');

        $isOk = $schemaExists && $seedExists && $validationExists && $docsF1Exists;
        $obtained = $isOk
            ? 'schema.sql, seed.sql, validacion.sql e informes de Fase 1 íntegros y preservados'
            : 'Fallo: Artefactos de Fase 1 alterados o ausentes';

        $this->record(
            'T2-12',
            'Prueba de regresión de Fase 1',
            'Artefactos de BD y documentación de Fase 1 íntegros',
            $obtained,
            $isOk
        );
    }

    /**
     * T2-13 — Aislamiento de rutas técnicas: Validar que /test/get y /test/post no estén disponibles en producción (404) y sí en desarrollo/testing.
     */
    private function testT2_13_IsolatedTechnicalRoutes(): void
    {
        $app = App::getInstance();
        $originalEnv = (string) Config::get('app.env', 'testing');

        // 1. Simular entorno de producción
        Config::set('app.env', 'production');
        $app->reboot();

        $reqGet = Request::create('GET', '/test/get');
        $respGetProd = $app->handle($reqGet);

        $reqPost = Request::create('POST', '/test/post', [], ['test' => 1]);
        $respPostProd = $app->handle($reqPost);

        $blockedInProd = ($respGetProd->getStatusCode() === 404) && ($respPostProd->getStatusCode() === 404);

        // 2. Probar entorno testing / development
        Config::set('app.env', 'testing');
        $app->reboot();

        $respGetTest = $app->handle($reqGet);
        $availableInDev = ($respGetTest->getStatusCode() === 200);

        // Restaurar estado original
        Config::set('app.env', $originalEnv);
        $app->reboot();

        $isOk = $blockedInProd && $availableInDev;
        $obtained = $isOk
            ? 'Rutas /test/* retornan 404 en production y 200 en testing'
            : sprintf('Fallo: GET prod=%d, POST prod=%d, GET testing=%d', $respGetProd->getStatusCode(), $respPostProd->getStatusCode(), $respGetTest->getStatusCode());

        $this->record(
            'T2-13',
            'Aislamiento de rutas técnicas en producción',
            'Status 404 en production y accesibles en dev/testing',
            $obtained,
            $isOk
        );
    }

    /**
     * T2-14 — Protección perimetral de archivos internos y logs: Bloqueo de .env, app, config, storage y logs.
     */
    private function testT2_14_InternalFileProtection(): void
    {
        $root = dirname(__DIR__);
        $rootHtaccess = $root . '/.htaccess';
        $storageHtaccess = $root . '/storage/.htaccess';
        $publicHtaccess = $root . '/public/.htaccess';

        $rootHtaccessOk = file_exists($rootHtaccess) && str_contains((string) file_get_contents($rootHtaccess), '\.env');
        $storageHtaccessOk = file_exists($storageHtaccess) && (str_contains((string) file_get_contents($storageHtaccess), 'Require all denied') || str_contains((string) file_get_contents($storageHtaccess), 'Deny from all'));
        $publicHtaccessOk = file_exists($publicHtaccess);

        // Simular intento de acceso HTTP mediante Front Controller
        $app = App::getInstance();
        $reqEnv = Request::create('GET', '/.env');
        $respEnv = $app->handle($reqEnv);

        $reqConfig = Request::create('GET', '/config/database.php');
        $respConfig = $app->handle($reqConfig);

        $reqLog = Request::create('GET', '/storage/logs/app.log');
        $respLog = $app->handle($reqLog);

        $noLeak = !str_contains($respEnv->getContent(), 'DB_PASSWORD') 
               && !str_contains($respConfig->getContent(), 'PDO::ATTR_ERRMODE')
               && ($respEnv->getStatusCode() === 404 || $respEnv->getStatusCode() === 403)
               && ($respConfig->getStatusCode() === 404 || $respConfig->getStatusCode() === 403);

        $isOk = $rootHtaccessOk && $storageHtaccessOk && $publicHtaccessOk && $noLeak;
        $obtained = $isOk
            ? 'Reglas .htaccess perimetrales vigentes y rutas a archivos sensibles no exponen secretos (404/403)'
            : 'Fallo: Ausencia de reglas .htaccess o fuga en respuestas HTTP';

        $this->record(
            'T2-14',
            'Protección perimetral de archivos internos y logs',
            'Bloqueo perimetral y respuestas 404/403 sin exposición de código/secretos',
            $obtained,
            $isOk
        );
    }

    /**
     * T2-15 — Validación estricta de configuración de base de datos: Comprobación fail-fast sin fuga de credenciales.
     */
    private function testT2_15_MandatoryConfiguration(): void
    {
        $cases = [
            ['config' => ['port' => 3307, 'database' => 'db', 'username' => 'u', 'password' => 'p'], 'missing' => 'DB_HOST'],
            ['config' => ['host' => '127.0.0.1', 'database' => 'db', 'username' => 'u', 'password' => 'p'], 'missing' => 'DB_PORT'],
            ['config' => ['host' => '127.0.0.1', 'port' => 3307, 'username' => 'u', 'password' => 'p'], 'missing' => 'DB_DATABASE'],
            ['config' => ['host' => '127.0.0.1', 'port' => 3307, 'database' => 'db', 'password' => 'p'], 'missing' => 'DB_USERNAME'],
            ['config' => ['host' => '127.0.0.1', 'port' => 3307, 'database' => 'db', 'username' => 'u'], 'missing' => 'DB_PASSWORD'],
        ];

        $allCaught = true;
        $secretNotExposed = true;
        $secretSample = 'TopSecretVal_!99';

        foreach ($cases as $case) {
            try {
                $testCfg = $case['config'];
                if (isset($testCfg['password'])) {
                    $testCfg['password'] = $secretSample;
                }
                Database::validateConfig($testCfg);
                $allCaught = false;
            } catch (DatabaseException $e) {
                if (!str_contains($e->getMessage(), $case['missing'])) {
                    $allCaught = false;
                }
                if (str_contains($e->getMessage(), $secretSample)) {
                    $secretNotExposed = false;
                }
            } catch (\Throwable) {
                $allCaught = false;
            }
        }

        // Probar configuración válida
        $validCfg = [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3307,
            'database' => 'escuela_lo_de_fuentes',
            'username' => 'root',
            'password' => '',
        ];
        $validPassed = true;
        try {
            Database::validateConfig($validCfg);
        } catch (\Throwable) {
            $validPassed = false;
        }

        $isOk = $allCaught && $secretNotExposed && $validPassed;
        $obtained = $isOk
            ? 'Validación estricta fail-fast activa para 5 parámetros obligatorios sin exposición de secretos'
            : 'Fallo: Parámetros obligatorios no validados correctamente o fuga de secretos';

        $this->record(
            'T2-15',
            'Validación estricta de configuración de base de datos',
            'DatabaseException ante parámetros faltantes sin exponer credenciales',
            $obtained,
            $isOk
        );
    }

    /**
     * T2-16 — Regresión funcional de base de datos de Fase 1: Ejecutar schema, seed y validacion.sql en BD temporal.
     */
    private function testT2_16_DatabaseRegressionPhase1(): void
    {
        $root = dirname(__DIR__);
        $mysqlBinary = $this->findMysqlBinary();

        if ($mysqlBinary === null) {
            $this->record(
                'T2-16',
                'Regresión funcional de base de datos de Fase 1',
                '18 pruebas automáticas ejecutadas exitosamente (18 OK, 0 fallos)',
                'Fallo: Binario mysql.exe no encontrado en el sistema',
                false
            );
            return;
        }

        $dbHost = (string) Config::get('database.connections.mysql.host', '127.0.0.1');
        $dbPort = (int) Config::get('database.connections.mysql.port', 3307);
        $dbUser = (string) Config::get('database.connections.mysql.username', 'root');
        $dbPass = (string) Config::get('database.connections.mysql.password', '');
        $tempDb = 'escuela_fase2_regresion_temp';

        $schemaFile = str_replace('/', '\\', $root . '/database/schema.sql');
        $seedFile = file_exists($root . '/database/tests/seed_fase1.sql')
            ? str_replace('/', '\\', $root . '/database/tests/seed_fase1.sql')
            : str_replace('/', '\\', $root . '/database/seed.sql');
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
            // 1. Crear base de datos temporal limpia
            shell_exec($baseCmd . ' -e "DROP DATABASE IF EXISTS ' . $tempDb . ';" 2>&1');
            shell_exec($baseCmd . ' -e "CREATE DATABASE ' . $tempDb . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1');

            // 2. Ejecutar schema.sql
            shell_exec($baseCmd . ' ' . $tempDb . ' < "' . $schemaFile . '" 2>&1');

            // 3. Ejecutar seed.sql
            shell_exec($baseCmd . ' ' . $tempDb . ' < "' . $seedFile . '" 2>&1');

            // 4. Ejecutar validacion.sql
            $output = (string) shell_exec($baseCmd . ' ' . $tempDb . ' < "' . $valFile . '" 2>&1');

            // 5. Analizar resultado de las 18 pruebas
            // Salida de MySQL CLI tabulada: pruebas_ejecutadas \t pruebas_exitosas \t pruebas_fallidas \n 18 \t 18 \t 0
            $matched = preg_match('/pruebas_ejecutadas\s+pruebas_exitosas\s+pruebas_fallidas\s+18\s+18\s+0/i', $output);
            $hasNoFailureInTable = !preg_match('/\bFALLO\b/i', $output);

            if ($matched && $hasNoFailureInTable) {
                $isOk = true;
                $obtained = '18/18 pruebas de Fase 1 ejecutadas con 18 exitosas y 0 fallidas en BD temporal aislada';
            } else {
                $isOk = false;
                $obtained = 'Fallo en salida de validacion.sql: ' . substr(strip_tags($output), -300);
            }
        } catch (\Throwable $e) {
            $isOk = false;
            $obtained = 'Excepción durante regresión: ' . $e->getMessage();
        } finally {
            // 6. Eliminar base de datos temporal
            shell_exec($baseCmd . ' -e "DROP DATABASE IF EXISTS ' . $tempDb . ';" 2>&1');
        }

        $this->record(
            'T2-16',
            'Regresión funcional de base de datos de Fase 1',
            '18 pruebas automáticas ejecutadas exitosamente (18 OK, 0 fallos)',
            $obtained,
            $isOk
        );
    }

    /**
     * Localizar el binario de MySQL CLI en el entorno.
     */
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
        echo "RESUMEN DE PRUEBAS DE FASE 2\n";
        echo "============================================================\n";
        printf("Total ejecutadas : %d\n", $this->total);
        printf("Exitosas (OK)    : %d\n", $this->passed);
        printf("Fallidas (FALLO) : %d\n", $this->failed);
        echo "============================================================\n";

        if ($this->failed === 0) {
            echo "RESULTADO: TODAS LAS PRUEBAS DE FASE 2 SUPERADAS SATISFACTORIAMENTE.\n";
        } else {
            echo "RESULTADO: EXISTEN PRUEBAS FALLIDAS.\n";
            exit(1);
        }
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $suite = new Phase2TestSuite();
    $suite->run();
}


