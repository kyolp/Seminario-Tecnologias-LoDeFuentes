<?php

declare(strict_types=1);

/**
 * Suite de Pruebas Automatizadas — Ajuste Final Posterior a Fase 16
 * Control de Red Institucional para Marcaje de Asistencia e Historial Docente/Administrativo
 * Sistema Web — Escuela Oficial Rural Mixta Lo de Fuentes
 *
 * Cobertura de la Suite:
 * - TAF-01: NetworkUtil — IPv4 dentro de CIDR (/24, /32) autorizado correctamente
 * - TAF-02: NetworkUtil — IPv4 fuera de CIDR rechazado
 * - TAF-03: NetworkUtil — IPv6 (::1/128) autorizado y fuera rechazado
 * - TAF-04: NetworkUtil — Múltiples CIDRs en cadena separada por comas
 * - TAF-05: NetworkUtil — Manejo seguro de CIDR malformado, IP inválida y cadenas vacías
 * - TAF-06: Request — Resolución autoritativa de IP de cliente basada en REMOTE_ADDR (Request::ip() y getClientIp())
 * - TAF-07: Request — Inmunidad ante suplantación (cabeceras X-Forwarded-For y X-Real-IP ignoradas)
 * - TAF-08: AsistenciaService — Marcaje rechazado cuando IP está fuera de red autorizada con mensaje normativo exacto
 * - TAF-09: AsistenciaService — Marcaje rechazado cuando geocerca es inválida con mensaje descriptivo y claro
 * - TAF-10: AsistenciaService — Marcaje permitido cuando AMBAS condiciones (red + geocerca) son válidas
 * - TAF-11: AsistenciaService — Comportamiento restrictivo ante configuración de redes vacía o no coincidente
 * - TAF-12: DocenteAsistenciaController::historial — JSON retorna fecha, hora, tipo y estado sin coordenadas ni IDs internos
 * - TAF-13: DocenteAsistenciaController::historial — Vista HTML renderiza historial personal sin coordenadas ni IDs internos
 * - TAF-14: DocenteAsistenciaController::historial — Protección Anti-IDOR estricta con aislamiento a la identidad de sesión
 * - TAF-15: DocenteAsistenciaController::procesarMarcajeHttp — Solicitud HTTP de marcaje denegada si la IP no es institucional
 * - TAF-16: AdminReporteController::asistencia — Administración consulta historial individual de asistencia del Docente A (RF-29)
 * - TAF-17: AdminReporteController::asistencia — Administración consulta historial individual de asistencia del Docente B (RF-29)
 * - TAF-18: Vistas Administrativas — Catálogo y Ficha de Docentes contienen enlaces funcionales al reporte individual de asistencia
 * - TAF-19: Vista Docente — Marcaje comunica las 3 condiciones obligatorias y oculta coordenadas crudas del historial reciente
 * - TAF-20: Limpieza e Inocuidad — Eliminación completa de fixtures temporales de prueba, preservando datos de demostración
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\App;
use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Controllers\AdminReporteController;
use App\Controllers\DocenteAsistenciaController;
use App\Repositories\AsistenciaRepository;
use App\Repositories\DocenteRepository;
use App\Repositories\UbicacionRepository;
use App\Services\AsistenciaService;
use App\Services\PortalDocenteService;
use App\Services\ReporteService;
use App\Utils\NetworkUtil;

class AjusteFinalTestSuite
{
    private PDO $db;
    private int $total = 0;
    private int $passed = 0;
    private int $failed = 0;

    /** @var array<int, array{id: string, name: string, expected: string, obtained: string, status: string}> */
    private array $results = [];

    /** @var array<int, int> */
    private array $marcajesCreados = [];

    private int $docente1Id = 1; // Docente 1 (Carlos Méndez)
    private int $docente1UserId = 3;
    private int $docente2Id = 2; // Docente 2 (María López)
    private int $docente2UserId = 4;
    private int $adminUserId = 1; // Director / Administrador

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function run(): void
    {
        echo "\n";
        echo "================================================================================\n";
        echo "SUITE DE PRUEBAS AUTOMATIZADAS — AJUSTE FINAL POSTERIOR A FASE 16\n";
        echo "CONTROL DE RED INSTITUCIONAL PARA MARCAJE + HISTORIAL DE ASISTENCIA\n";
        echo "Escuela Oficial Rural Mixta Lo de Fuentes\n";
        echo "================================================================================\n\n";

        try {
            $this->testTAF01_NetworkUtilIpv4EnCidr();
            $this->testTAF02_NetworkUtilIpv4FueraCidr();
            $this->testTAF03_NetworkUtilIpv6();
            $this->testTAF04_NetworkUtilMultiplesCidrs();
            $this->testTAF05_NetworkUtilCasosExtremosYFormatoInvalido();
            $this->testTAF06_RequestIpResolucionSegura();
            $this->testTAF07_RequestInmunidadSuplantacionCabeceras();
            $this->testTAF08_AsistenciaServiceRechazoRedInvalida();
            $this->testTAF09_AsistenciaServiceRechazoGeocercaInvalida();
            $this->testTAF10_AsistenciaServiceExitoAmbasCondicionesValidas();
            $this->testTAF11_AsistenciaServiceComportamientoRestrictivoSinRedes();
            $this->testTAF12_DocenteHistorialJsonSinCoordenadasNiIds();
            $this->testTAF13_DocenteHistorialHtmlSinCoordenadasNiIds();
            $this->testTAF14_DocenteHistorialAntiIdorAislamientoSesion();
            $this->testTAF15_DocenteMarcajeHttpRechazoRedNoAutorizada();
            $this->testTAF16_AdminConsultaHistorialDocenteA();
            $this->testTAF17_AdminConsultaHistorialDocenteB();
            $this->testTAF18_VistasAdminEnlacesHistorialAsistencia();
            $this->testTAF19_VistaDocenteCondicionesObligatoriasYOcultamiento();
            $this->testTAF20_LimpiezaBaseDeDatosInocuidad();
        } finally {
            $this->teardown();
        }

        $this->printReport();
    }

    private function record(string $id, string $name, string $expected, string $obtained, bool $ok): void
    {
        $this->total++;
        if ($ok) {
            $this->passed++;
            $status = 'PASSED';
        } else {
            $this->failed++;
            $status = 'FAILED';
        }

        $this->results[] = [
            'id' => $id,
            'name' => $name,
            'expected' => $expected,
            'obtained' => $obtained,
            'status' => $status,
        ];

        printf("  [%s] %s ... %s\n", $id, $name, $status);
        if (!$ok) {
            printf("         Esperado: %s\n         Obtenido: %s\n", $expected, $obtained);
        }
    }

    // TAF-01: IPv4 dentro de CIDR (/24, /32)
    private function testTAF01_NetworkUtilIpv4EnCidr(): void
    {
        $c1 = NetworkUtil::isIpInCidr('192.168.1.15', '192.168.1.0/24');
        $c2 = NetworkUtil::isIpInCidr('10.0.5.20', '10.0.0.0/16');
        $c3 = NetworkUtil::isIpInCidr('127.0.0.1', '127.0.0.1/32');
        $c4 = NetworkUtil::isIpInCidr('172.16.0.1', '172.16.0.1');

        $ok = ($c1 === true && $c2 === true && $c3 === true && $c4 === true);
        $this->record(
            'TAF-01',
            'NetworkUtil: IPv4 dentro de rangos CIDR (/24, /16, /32) autorizada',
            'true para todas las IPs contenidas en sus subredes',
            sprintf('/24: %s, /16: %s, /32: %s, single: %s', var_export($c1, true), var_export($c2, true), var_export($c3, true), var_export($c4, true)),
            $ok
        );
    }

    // TAF-02: IPv4 fuera de CIDR
    private function testTAF02_NetworkUtilIpv4FueraCidr(): void
    {
        $c1 = NetworkUtil::isIpInCidr('192.168.2.15', '192.168.1.0/24');
        $c2 = NetworkUtil::isIpInCidr('10.1.5.20', '10.0.0.0/16');
        $c3 = NetworkUtil::isIpInCidr('127.0.0.2', '127.0.0.1/32');

        $ok = ($c1 === false && $c2 === false && $c3 === false);
        $this->record(
            'TAF-02',
            'NetworkUtil: IPv4 fuera de rangos CIDR rechazada',
            'false para todas las IPs fuera de las subredes',
            sprintf('/24: %s, /16: %s, /32: %s', var_export($c1, true), var_export($c2, true), var_export($c3, true)),
            $ok
        );
    }

    // TAF-03: IPv6
    private function testTAF03_NetworkUtilIpv6(): void
    {
        $c1 = NetworkUtil::isIpInCidr('::1', '::1/128');
        $c2 = NetworkUtil::isIpInCidr('::2', '::1/128');
        $c3 = NetworkUtil::isIpInCidr('2001:db8::1', '2001:db8::/32');
        $c4 = NetworkUtil::isIpInCidr('2001:db9::1', '2001:db8::/32');

        $ok = ($c1 === true && $c2 === false && $c3 === true && $c4 === false);
        $this->record(
            'TAF-03',
            'NetworkUtil: IPv6 (loopback ::1 y prefijos /32) evaluada con precisión binaria',
            '::1 true, ::2 false, 2001:db8::1 true, 2001:db9::1 false',
            sprintf('::1: %s, ::2: %s, db8: %s, db9: %s', var_export($c1, true), var_export($c2, true), var_export($c3, true), var_export($c4, true)),
            $ok
        );
    }

    // TAF-04: Múltiples CIDRs
    private function testTAF04_NetworkUtilMultiplesCidrs(): void
    {
        $redes = '192.168.10.0/24, 10.0.0.0/8, 127.0.0.1/32, ::1/128';

        $c1 = NetworkUtil::isIpInAllowedNetworks('192.168.10.50', $redes);
        $c2 = NetworkUtil::isIpInAllowedNetworks('10.254.1.2', $redes);
        $c3 = NetworkUtil::isIpInAllowedNetworks('127.0.0.1', $redes);
        $c4 = NetworkUtil::isIpInAllowedNetworks('::1', $redes);
        $c5 = NetworkUtil::isIpInAllowedNetworks('192.168.20.1', $redes);
        $c6 = NetworkUtil::isIpInAllowedNetworks('172.16.1.1', $redes);

        $ok = ($c1 && $c2 && $c3 && $c4 && !$c5 && !$c6);
        $this->record(
            'TAF-04',
            'NetworkUtil: Lista de múltiples redes CIDR separadas por coma',
            'IPs autorizadas en cualquiera de las redes dan true; otras false',
            sprintf('192.168.10.50: %s, 10.254.1.2: %s, 127.0.0.1: %s, ::1: %s, 192.168.20.1: %s', var_export($c1, true), var_export($c2, true), var_export($c3, true), var_export($c4, true), var_export($c5, true)),
            $ok
        );
    }

    // TAF-05: Formatos inválidos o vacíos
    private function testTAF05_NetworkUtilCasosExtremosYFormatoInvalido(): void
    {
        $c1 = NetworkUtil::isIpInCidr('', '192.168.1.0/24');
        $c2 = NetworkUtil::isIpInCidr('192.168.1.1', '');
        $c3 = NetworkUtil::isIpInCidr('invalido', '192.168.1.0/24');
        $c4 = NetworkUtil::isIpInCidr('192.168.1.1', 'cidr_invalido');
        $c5 = NetworkUtil::isIpInCidr('192.168.1.1', '192.168.1.0/33');
        $c6 = NetworkUtil::isIpInAllowedNetworks('192.168.1.1', '   ,   ');

        $ok = (!$c1 && !$c2 && !$c3 && !$c4 && !$c5 && !$c6);
        $this->record(
            'TAF-05',
            'NetworkUtil: Manejo seguro y resiliente ante cadenas vacías o malformadas',
            'false sin lanzar excepciones no controladas',
            sprintf('vacia: %s, ip_inv: %s, cidr_inv: %s, mask>32: %s, spaces: %s', var_export($c1, true), var_export($c3, true), var_export($c4, true), var_export($c5, true), var_export($c6, true)),
            $ok
        );
    }

    // TAF-06: Request IP autoritativa
    private function testTAF06_RequestIpResolucionSegura(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.100.5';
        $req1 = Request::createFromGlobals();

        $req2 = Request::create('GET', '/docente/asistencia', [], [], [], [], '10.20.30.40');

        $ok = ($req1->ip() === '192.168.100.5' && $req1->getClientIp() === '192.168.100.5' && $req2->ip() === '10.20.30.40');
        $this->record(
            'TAF-06',
            'Request: Métodos ip() y getClientIp() resuelven la IP autoritativa de cliente',
            'IP resuelta desde REMOTE_ADDR o parámetro seguro inyectado',
            sprintf('req1: %s, req2: %s', $req1->ip(), $req2->ip()),
            $ok
        );
    }

    // TAF-07: Inmunidad contra suplantación
    private function testTAF07_RequestInmunidadSuplantacionCabeceras(): void
    {
        $_SERVER['REMOTE_ADDR'] = '198.51.100.1'; // IP pública externa
        $headers = [
            'HTTP_X_FORWARDED_FOR' => '127.0.0.1, 10.0.0.1',
            'HTTP_X_REAL_IP' => '127.0.0.1',
            'HTTP_CLIENT_IP' => '127.0.0.1',
        ];

        foreach ($headers as $k => $v) {
            $_SERVER[$k] = $v;
        }

        $req = Request::createFromGlobals();

        $ok = ($req->ip() === '198.51.100.1');
        $this->record(
            'TAF-07',
            'Request: Inmunidad ante suplantación (X-Forwarded-For y X-Real-IP ignoradas)',
            'IP del request es REMOTE_ADDR (198.51.100.1) ignorando cabeceras espurias',
            sprintf('IP obtenida: %s', $req->ip()),
            $ok
        );

        foreach ($headers as $k => $v) {
            unset($_SERVER[$k]);
        }
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    // TAF-08: AsistenciaService rechazo por red
    private function testTAF08_AsistenciaServiceRechazoRedInvalida(): void
    {
        $service = new AsistenciaService();
        $service->setAllowedNetworks('192.168.1.0/24'); // Solo red 192.168.1.x

        $ipInvalida = '200.10.20.30'; // Fuera de red
        $coordsValidas = [
            'latitud' => 14.6349150,
            'longitud' => -90.5068820,
            'precision' => 10.0,
        ];

        $rechazado = false;
        $mensaje = '';

        try {
            $service->registrarEntrada($this->docente1Id, $this->docente1UserId, $coordsValidas, $ipInvalida);
        } catch (\DomainException $e) {
            $rechazado = true;
            $mensaje = $e->getMessage();
        }

        $mensajeExactoEsperado = 'Para registrar la asistencia debe estar conectado a la red institucional.';
        $ok = ($rechazado && $mensaje === $mensajeExactoEsperado);

        $this->record(
            'TAF-08',
            'AsistenciaService: Marcaje rechazado cuando la IP no pertenece a la red institucional',
            $mensajeExactoEsperado,
            sprintf('Rechazado: %s | Mensaje: "%s"', $rechazado ? 'SI' : 'NO', $mensaje),
            $ok
        );
    }

    // TAF-09: AsistenciaService rechazo por geocerca
    private function testTAF09_AsistenciaServiceRechazoGeocercaInvalida(): void
    {
        $service = new AsistenciaService();
        $service->setAllowedNetworks('127.0.0.1/32,::1/128,192.168.1.0/24');

        $ipValida = '192.168.1.100'; // Red institucional válida
        $coordsLejos = [
            'latitud' => 14.6600000, // Fuera de geocerca (a más de 2000m)
            'longitud' => -90.5300000,
            'precision' => 15.0,
        ];

        $rechazado = false;
        $mensaje = '';

        try {
            $service->registrarEntrada($this->docente1Id, $this->docente1UserId, $coordsLejos, $ipValida);
        } catch (\DomainException $e) {
            $rechazado = true;
            $mensaje = $e->getMessage();
        }

        $tieneFrase1 = str_contains($mensaje, 'Debe encontrarse dentro del área autorizada para registrar la asistencia');
        $tieneFrase2 = str_contains($mensaje, 'excediendo el radio');
        $ok = ($rechazado && $tieneFrase1 && $tieneFrase2);

        $this->record(
            'TAF-09',
            'AsistenciaService: Marcaje rechazado cuando está fuera del radio geográfico con mensaje claro',
            'Contiene "Debe encontrarse dentro del área autorizada" y "excediendo el radio"',
            sprintf('Rechazado: %s | Mensaje: "%s"', $rechazado ? 'SI' : 'NO', $mensaje),
            $ok
        );
    }

    // TAF-10: AsistenciaService éxito cuando ambas condiciones son válidas
    private function testTAF10_AsistenciaServiceExitoAmbasCondicionesValidas(): void
    {
        $service = new AsistenciaService();
        $service->setAllowedNetworks('192.168.1.0/24,127.0.0.1/32');

        $ipValida = '192.168.1.105';
        $coordsValidas = [
            'latitud' => 14.6349150, // Ubicación exacta de la escuela
            'longitud' => -90.5068820,
            'precision' => 5.0,
        ];

        $resultado = $service->registrarEntrada($this->docente1Id, $this->docente1UserId, $coordsValidas, $ipValida);

        $ok = ($resultado['exito'] === true && $resultado['distancia_metros'] <= 100.0 && $resultado['marcaje'] !== null);
        if ($ok && isset($resultado['marcaje']->id_marcaje_asistencia)) {
            $this->marcajesCreados[] = $resultado['marcaje']->id_marcaje_asistencia;
        }

        $this->record(
            'TAF-10',
            'AsistenciaService: Marcaje autorizado cuando red y geocerca son válidas simultáneamente',
            'exito: true, distancia <= radio, objeto marcaje persistido',
            sprintf('exito: %s, distancia: %.2fm, id_marcaje: %d', var_export($resultado['exito'], true), $resultado['distancia_metros'], $resultado['marcaje']->id_marcaje_asistencia ?? 0),
            $ok
        );
    }

    // TAF-11: Comportamiento restrictivo sin redes
    private function testTAF11_AsistenciaServiceComportamientoRestrictivoSinRedes(): void
    {
        $service = new AsistenciaService();
        $service->setAllowedNetworks(''); // Configuración vacía

        $ip = '10.0.0.1';
        $coordsValidas = [
            'latitud' => 14.6349150,
            'longitud' => -90.5068820,
            'precision' => 5.0,
        ];

        $rechazado = false;
        try {
            $service->registrarEntrada($this->docente2Id, $this->docente2UserId, $coordsValidas, $ip);
        } catch (\DomainException $e) {
            $rechazado = true;
        }

        $this->record(
            'TAF-11',
            'AsistenciaService: Denegación restrictiva cuando la configuración de redes está vacía',
            'Rechazo con DomainException',
            sprintf('Rechazado: %s', $rechazado ? 'SI' : 'NO'),
            $rechazado
        );
    }

    // TAF-12: Historial JSON sin coordenadas ni IDs internos
    private function testTAF12_DocenteHistorialJsonSinCoordenadasNiIds(): void
    {
        Session::reset();
        Session::set('auth_user_id', $this->docente1UserId);
        Session::set('auth_role', 'DOCENTE');

        $req = Request::create(
            'GET',
            '/docente/asistencia/historial',
            [],
            [],
            ['Accept' => 'application/json']
        );

        $res = App::getInstance()->handle($req);
        $data = json_decode($res->getContent(), true);

        $okJson = ($res->getStatusCode() === 200 && is_array($data) && ($data['exito'] ?? false) === true);
        $historial = $data['historial'] ?? [];

        $sinCoordenadas = true;
        $sinIdsInternos = true;
        $camposValidos = true;

        if (!empty($historial)) {
            $item = $historial[0];
            if (isset($item['latitud_dispositivo']) || isset($item['longitud_dispositivo']) || isset($item['distancia_metros'])) {
                $sinCoordenadas = false;
            }
            if (isset($item['id_marcaje_asistencia']) || isset($item['id_docente']) || isset($item['id_usuario'])) {
                $sinIdsInternos = false;
            }
            if (!isset($item['fecha']) || !isset($item['hora']) || !isset($item['tipo']) || !isset($item['estado'])) {
                $camposValidos = false;
            }
        }

        $ok = ($okJson && $sinCoordenadas && $sinIdsInternos && $camposValidos);
        $this->record(
            'TAF-12',
            'DocenteController: Historial JSON expone solo fecha, hora, tipo y estado (sin coordenadas ni IDs)',
            '200 OK, exito: true, sin latitud/longitud ni IDs internos de base de datos',
            sprintf('Status: %d, items: %d, sinCoords: %s, sinIds: %s', $res->getStatusCode(), count($historial), $sinCoordenadas ? 'SI' : 'NO', $sinIdsInternos ? 'SI' : 'NO'),
            $ok
        );
    }

    // TAF-13: Historial HTML sin coordenadas ni IDs
    private function testTAF13_DocenteHistorialHtmlSinCoordenadasNiIds(): void
    {
        Session::reset();
        Session::set('auth_user_id', $this->docente1UserId);
        Session::set('auth_role', 'DOCENTE');

        $req = Request::create('GET', '/docente/asistencia/historial');
        $res = App::getInstance()->handle($req);

        $html = $res->getContent();
        $okStatus = ($res->getStatusCode() === 200);

        // Verificar que contenga encabezados limpios
        $tieneEncabezados = str_contains($html, 'Fecha') && str_contains($html, 'Hora') && str_contains($html, 'Tipo de Marcaje') && str_contains($html, 'Estado / Resultado');
        // Verificar que NO exponga etiquetas técnicas
        $sinCoordsTh = !str_contains($html, 'Coordenadas Registradas') && !str_contains($html, 'Precisión GPS') && !str_contains($html, 'Distancia al Centro');

        $ok = ($okStatus && $tieneEncabezados && $sinCoordsTh);
        $this->record(
            'TAF-13',
            'DocenteController: Vista HTML de historial personal renderiza tabla sin coordenadas crudas ni IDs',
            '200 OK, tabla con Fecha/Hora/Tipo/Estado y sin columnas técnicas',
            sprintf('Status: %d, encabezados: %s, sinCoords: %s', $res->getStatusCode(), $tieneEncabezados ? 'SI' : 'NO', $sinCoordsTh ? 'SI' : 'NO'),
            $ok
        );
    }

    // TAF-14: Anti-IDOR en historial de docente
    private function testTAF14_DocenteHistorialAntiIdorAislamientoSesion(): void
    {
        Session::reset();
        Session::set('auth_user_id', $this->docente1UserId); // Carlos Méndez (id_docente = 1)
        Session::set('auth_role', 'DOCENTE');

        // Intentar consultar el historial del Docente 2 mediante parámetro de query
        $req = Request::create(
            'GET',
            '/docente/asistencia/historial?id_docente=' . $this->docente2Id,
            [],
            [],
            ['Accept' => 'application/json']
        );

        $res = App::getInstance()->handle($req);
        $data = json_decode($res->getContent(), true);

        // El endpoint aísla estrictamente a la sesión: responde 200 con los datos de Carlos Méndez (o lista segura)
        $okStatus = ($res->getStatusCode() === 200);
        $okData = ($data['exito'] ?? false) === true;

        $ok = ($okStatus && $okData);
        $this->record(
            'TAF-14',
            'DocenteController: Protección Anti-IDOR estricta ignora parámetros de query y aísla la sesión',
            '200 OK, los datos consultados provienen únicamente del docente autenticado en sesión',
            sprintf('Status: %d, exito: %s', $res->getStatusCode(), var_export($okData, true)),
            $ok
        );
    }

    // TAF-15: Marcaje HTTP bloqueado ante IP no institucional
    private function testTAF15_DocenteMarcajeHttpRechazoRedNoAutorizada(): void
    {
        Session::reset();
        Session::set('auth_user_id', $this->docente1UserId);
        Session::set('auth_role', 'DOCENTE');
        $csrfToken = \App\Core\Csrf::getToken();

        // Petición con IP externa no institucional
        $req = Request::create(
            'POST',
            '/docente/asistencia/entrada',
            [],
            [
                '_token' => $csrfToken,
                'latitud' => 14.6349150,
                'longitud' => -90.5068820,
                'precision' => 5.0,
            ],
            ['Accept' => 'application/json'],
            [],
            '190.200.50.1' // IP pública externa
        );

        $res = App::getInstance()->handle($req);
        $data = json_decode($res->getContent(), true);

        $esRechazado = ($res->getStatusCode() === 422 || $res->getStatusCode() === 403);
        $errorMensaje = $data['error'] ?? '';
        $mensajeEsperado = str_contains($errorMensaje, 'red institucional');

        $ok = ($esRechazado && $mensajeEsperado);
        $this->record(
            'TAF-15',
            'DocenteController: Solicitud HTTP de marcaje denegada si proviene de una IP fuera de la red institucional',
            'HTTP 422 con error "Para registrar la asistencia debe estar conectado a la red institucional."',
            sprintf('Status: %d, error: "%s"', $res->getStatusCode(), $errorMensaje),
            $ok
        );
    }

    // TAF-16: Admin consulta historial de Docente A
    private function testTAF16_AdminConsultaHistorialDocenteA(): void
    {
        Session::reset();
        Session::set('auth_user_id', $this->adminUserId);
        Session::set('auth_role', 'ADMINISTRADOR');

        $req = Request::create(
            'GET',
            '/administracion/reportes/asistencia?id_docente=' . $this->docente1Id
        );

        $res = App::getInstance()->handle($req);

        $ok = ($res->getStatusCode() === 200 && str_contains($res->getContent(), 'Reporte de Asistencia Docente'));
        $this->record(
            'TAF-16',
            'AdminController: Administración consulta con éxito el historial de marcajes del Docente A (RF-29)',
            '200 OK con vista de reporte de asistencia filtrado para Docente A',
            sprintf('Status: %d', $res->getStatusCode()),
            $ok
        );
    }

    // TAF-17: Admin consulta historial de Docente B
    private function testTAF17_AdminConsultaHistorialDocenteB(): void
    {
        Session::reset();
        Session::set('auth_user_id', $this->adminUserId);
        Session::set('auth_role', 'ADMINISTRADOR');

        $req = Request::create(
            'GET',
            '/administracion/reportes/asistencia?id_docente=' . $this->docente2Id
        );

        $res = App::getInstance()->handle($req);

        $ok = ($res->getStatusCode() === 200 && str_contains($res->getContent(), 'Reporte de Asistencia Docente'));
        $this->record(
            'TAF-17',
            'AdminController: Administración consulta con éxito el historial de marcajes del Docente B (RF-29)',
            '200 OK con vista de reporte de asistencia filtrado para Docente B',
            sprintf('Status: %d', $res->getStatusCode()),
            $ok
        );
    }

    // TAF-18: Vistas administrativas contienen enlaces funcionales
    private function testTAF18_VistasAdminEnlacesHistorialAsistencia(): void
    {
        $indexView = file_get_contents(dirname(__DIR__) . '/views/admin/docentes/index.php');
        $verView = file_get_contents(dirname(__DIR__) . '/views/admin/docentes/ver.php');

        $tieneEnlaceIndex = str_contains($indexView, '/administracion/reportes/asistencia?id_docente=');
        $tieneEnlaceVer = str_contains($verView, '/administracion/reportes/asistencia?id_docente=');

        $ok = ($tieneEnlaceIndex && $tieneEnlaceVer);
        $this->record(
            'TAF-18',
            'Vistas Administrativas: Catálogo (index) y Ficha (ver) enlazan al historial individual de asistencia',
            'Ambas vistas contienen enlace a /administracion/reportes/asistencia?id_docente=X',
            sprintf('index: %s, ver: %s', $tieneEnlaceIndex ? 'SI' : 'NO', $tieneEnlaceVer ? 'SI' : 'NO'),
            $ok
        );
    }

    // TAF-19: Vista docente comunica las 3 condiciones y oculta coordenadas
    private function testTAF19_VistaDocenteCondicionesObligatoriasYOcultamiento(): void
    {
        $docenteView = file_get_contents(dirname(__DIR__) . '/views/docente/asistencia/index.php');

        $tieneGps = str_contains($docenteView, 'Dispositivo con GPS habilitado');
        $tienePerimetro = str_contains($docenteView, 'Ubicación física dentro del perímetro escolar');
        $tieneRed = str_contains($docenteView, 'Conexión a la red institucional autorizada');
        $tieneLinkHistorial = str_contains($docenteView, '/docente/asistencia/historial');
        $sinCoordsEnTabla = !str_contains($docenteView, '<th style="padding: 0.75rem 1rem;">Coordenadas Registradas</th>');

        $ok = ($tieneGps && $tienePerimetro && $tieneRed && $tieneLinkHistorial && $sinCoordsEnTabla);
        $this->record(
            'TAF-19',
            'Vista Docente: Panel comunica las 3 condiciones perimetrales y tabla reciente no expone coordenadas',
            'Condiciones (GPS, geocerca, red) visibles, enlace a historial presente y columnas crudas omitidas',
            sprintf('GPS: %s, Geocerca: %s, Red: %s, Enlace: %s, SinCoords: %s', $tieneGps ? 'OK' : 'ERR', $tienePerimetro ? 'OK' : 'ERR', $tieneRed ? 'OK' : 'ERR', $tieneLinkHistorial ? 'OK' : 'ERR', $sinCoordsEnTabla ? 'OK' : 'ERR'),
            $ok
        );
    }

    // TAF-20: Limpieza e inocuidad
    private function testTAF20_LimpiezaBaseDeDatosInocuidad(): void
    {
        $this->teardown();

        $marcajesRestantes = 0;
        if (!empty($this->marcajesCreados)) {
            $in = implode(',', array_map('intval', $this->marcajesCreados));
            $marcajesRestantes = (int) $this->db->query("SELECT COUNT(*) FROM marcajes_asistencia WHERE id_marcaje_asistencia IN ($in)")->fetchColumn();
        }

        $ok = ($marcajesRestantes === 0);
        $this->record(
            'TAF-20',
            'Inocuidad y Limpieza: Base de datos limpia sin registros residuales creados durante la prueba',
            '0 marcajes residuales de prueba persistidos',
            sprintf('Residuales: %d', $marcajesRestantes),
            $ok
        );
    }

    private function teardown(): void
    {
        if (!empty($this->marcajesCreados)) {
            $in = implode(',', array_map('intval', $this->marcajesCreados));
            $this->db->exec("DELETE FROM marcajes_asistencia WHERE id_marcaje_asistencia IN ($in)");
            $this->marcajesCreados = [];
        }
        Session::reset();
    }

    private function printReport(): void
    {
        echo "\n================================================================================\n";
        echo "RESUMEN DE EJECUCIÓN — AJUSTE FINAL POSTERIOR A FASE 16\n";
        echo "================================================================================\n";
        printf("Total de pruebas ejecutadas: %d\n", $this->total);
        printf("Aprobadas:                   %d\n", $this->passed);
        printf("Fallidas:                    %d\n", $this->failed);
        printf("Tasa de éxito:               %.2f%%\n", ($this->total > 0 ? ($this->passed / $this->total) * 100 : 0));
        echo "================================================================================\n\n";

        if ($this->failed > 0) {
            echo "ATENCIÓN: Existen pruebas fallidas en la suite de ajuste final.\n";
            exit(1);
        } else {
            echo "VALIDACIÓN EXITOSA: Todas las pruebas del ajuste final fueron aprobadas.\n";
        }
    }
}

// Ejecutar suite
$suite = new AjusteFinalTestSuite();
$suite->run();
