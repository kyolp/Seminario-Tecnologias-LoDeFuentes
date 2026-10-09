<?php

declare(strict_types=1);

/**
 * Suite de pruebas automatizadas para Fase 11 — Comprobante de Inscripción en PDF (RF-13).
 * Escuela Oficial Rural Mixta Lo de Fuentes.
 *
 * Cobertura:
 *  T11-01        Trazabilidad RF-13 (texto oficial, fase, RF futuros sin implementar).
 *  T11-02..T11-06 Generación, rechazo de inexistentes/inválidos, firma y estructura PDF reales.
 *  T11-07..T11-09 Cabeceras HTTP, PDF no vacío, UTF-8/acentos.
 *  T11-10..T11-14 Autoría del contenido (inscripción, código, estudiante, grado/sección, estado).
 *  T11-15..T11-19 Privacidad y seguridad (IDs, hashes, datos ajenos, manipulación, XSS).
 *  T11-20..T11-23 Nombre de archivo, inmutabilidad, descargas repetidas, sin temporales públicos.
 *  T11-24..T11-26 Error controlado, sin servicios externos, sin persistencia de PDFs en BD.
 *  T11-27        Flujo HTTP real end-to-end (servidor embebido + cURL).
 *  T11-28..T11-29 Regresión funcional RF-11/RF-12/RF-14 y regresión completa de Fase 6.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\ComprobanteInscripcionController;
use App\Core\App;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\InscripcionRepository;
use App\Services\ComprobanteInscripcionService;
use App\Services\InscripcionService;
use App\Services\PdfService;

class Phase11TestSuite
{
    private PDO $db;
    private InscripcionService $inscripcionService;
    private InscripcionRepository $inscripcionRepo;
    private ComprobanteInscripcionService $comprobanteService;

    private int $total = 0;
    private int $passed = 0;
    private int $failed = 0;

    // Fixtures
    private ?int $periodoId = null;
    private ?int $gradoId = null;
    private ?int $gradoPeriodoId = null;
    private ?int $seccionAId = null;
    private ?int $seccionBId = null;

    /** @var array<string, array<string, mixed>> Inscripciones de prueba por alias */
    private array $ins = [];
    private array $createdInscripcionIds = [];
    private array $createdEstudianteIds = [];
    private array $createdEncargadoIds = [];

    private const EST_UNICO = 'Zacarías Quetzal OtroF11';
    private const TEL_I1 = '5555-1111';
    private const MAIL_I1 = 'maria.f11@example.invalid';

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->inscripcionRepo = new InscripcionRepository($this->db);
        $this->inscripcionService = new InscripcionService();
        $this->comprobanteService = new ComprobanteInscripcionService();
    }

    public function run(): void
    {
        echo "============================================================\n";
        echo "SUITE DE PRUEBAS AUTOMATIZADAS — FASE 11\n";
        echo "Comprobante de Inscripción en PDF (RF-13)\n";
        echo "============================================================\n\n";

        $this->cleanPreviousArtifacts();

        try {
            $this->setupFixtures();

            $this->testT11_01_Trazabilidad();
            $this->testT11_02_InscripcionValidaGeneraComprobante();
            $this->testT11_03_InscripcionInexistenteNoGeneraPdf();
            $this->testT11_04_CodigoInvalidoNoGeneraPdf();
            $this->testT11_05_FirmaPdf();
            $this->testT11_06_EstructuraPdfValida();
            $this->testT11_07_CabecerasHttp();
            $this->testT11_08_PdfNoVacio();
            $this->testT11_09_Utf8YAcentos();
            $this->testT11_10_DatosDeLaInscripcionSolicitada();
            $this->testT11_11_CodigoConfirmacionCorrecto();
            $this->testT11_12_EstudianteYEncargadoCorrectos();
            $this->testT11_13_GradoSeccionPeriodoCorrectos();
            $this->testT11_14_EstadoYCondicionDeGeneracion();
            $this->testT11_15_SinIdsInternosNiRutasNiSql();
            $this->testT11_16_SinPasswordsNiHashesNiDatosContacto();
            $this->testT11_17_SinFiltracionEntreEstudiantes();
            $this->testT11_18_ManipulacionDeIdentificadorBloqueada();
            $this->testT11_19_XssYContenidoMaliciosoNeutralizado();
            $this->testT11_20_NombreDeArchivoSeguro();
            $this->testT11_21_NoModificaInscripcion();
            $this->testT11_22_DescargasRepetidasSinDuplicados();
            $this->testT11_23_SinArchivosTemporalesPublicos();
            $this->testT11_24_ErrorDeGeneracionControlado();
            $this->testT11_25_GeneracionLocalSinServiciosExternos();
            $this->testT11_26_SinPersistenciaDePdfEnBaseDeDatos();
            $this->testT11_27_FlujoHttpReal();
            $this->testT11_28_RegresionFuncionalRf11Rf12Rf14();
            $this->testT11_29_RegresionFase6();
        } finally {
            $this->cleanup();
        }

        $this->printSummary();
    }

    // =========================================================================
    // Infraestructura de pruebas
    // =========================================================================

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

        printf("[%s] %s: %s | Obtenido: %s\n", $status, $id, $name, $obtained);
        if (!$isOk) {
            printf("       Esperado: %s\n", $expected);
        }
    }

    private function cleanPreviousArtifacts(): void
    {
        $this->db->exec("DELETE FROM inscripciones WHERE codigo_confirmacion LIKE 'INS-F11%'");
        $this->db->exec("DELETE FROM inscripciones WHERE id_estudiante IN (SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante LIKE 'EST-F11%')");
        $this->db->exec("DELETE FROM estudiantes_encargados WHERE id_estudiante IN (SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante LIKE 'EST-F11%')");
        $this->db->exec("DELETE FROM estudiantes WHERE codigo_estudiante LIKE 'EST-F11%'");
        $this->db->exec("DELETE FROM encargados WHERE correo LIKE '%.f11@example.invalid'");
        $this->db->exec("DELETE FROM inscripciones WHERE id_seccion IN (SELECT id_seccion FROM secciones WHERE codigo LIKE 'F11-%')");
        $this->db->exec("DELETE FROM secciones WHERE codigo LIKE 'F11-%'");
        $this->db->exec("DELETE FROM grados_periodos WHERE id_grado IN (SELECT id_grado FROM grados WHERE codigo LIKE 'GRD-F11%')");
        $this->db->exec("DELETE FROM grados WHERE codigo LIKE 'GRD-F11%'");
        $this->db->exec("DELETE FROM periodos_escolares WHERE nombre LIKE 'Ciclo F11%'");
    }

    private function setupFixtures(): void
    {
        $this->db->exec("INSERT INTO periodos_escolares (nombre, fecha_inicio, fecha_fin, estado) VALUES ('Ciclo F11 Comprobantes 2026', '2026-01-15', '2026-10-31', 'PLANIFICADO')");
        $this->periodoId = (int) $this->db->lastInsertId();

        $orden = (int) $this->db->query('SELECT IFNULL(MAX(orden), 0) + 1 FROM grados')->fetchColumn();
        $this->db->exec("INSERT INTO grados (codigo, nombre, orden, activo) VALUES ('GRD-F11-01', 'Primero Primaria F11', {$orden}, 1)");
        $this->gradoId = (int) $this->db->lastInsertId();

        $this->db->exec("INSERT INTO grados_periodos (id_periodo_escolar, id_grado, habilitado) VALUES ({$this->periodoId}, {$this->gradoId}, 1)");
        $this->gradoPeriodoId = (int) $this->db->lastInsertId();

        $this->db->exec("INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada) VALUES ({$this->gradoPeriodoId}, 'F11-A', 'Sección Mañana F11', 30, 1)");
        $this->seccionAId = (int) $this->db->lastInsertId();
        $this->db->exec("INSERT INTO secciones (id_grado_periodo, codigo, nombre, capacidad, habilitada) VALUES ({$this->gradoPeriodoId}, 'F11-B', 'Sección Tarde F11', 30, 1)");
        $this->seccionBId = (int) $this->db->lastInsertId();

        // I1: inscripción creada por el FLUJO REAL RF-11/RF-12 (acentos, ñ, datos de contacto).
        $res = $this->inscripcionService->procesarInscripcionPublica([
            'estudiante_nombres' => 'José Ángel',
            'estudiante_apellidos' => 'Muñoz Pérez',
            'estudiante_fecha_nacimiento' => '2019-05-14',
            'codigo_estudiante' => 'EST-F11-001',
            'encargado_nombres' => 'María Ñandú',
            'encargado_apellidos' => 'Gómez Ruiz',
            'encargado_telefono' => self::TEL_I1,
            'encargado_correo' => self::MAIL_I1,
            'parentesco' => 'MADRE',
            'id_seccion' => $this->seccionAId,
        ]);
        if (!($res['success'] ?? false)) {
            throw new RuntimeException('Fixture I1 no pudo crearse: ' . json_encode($res, JSON_UNESCAPED_UNICODE));
        }
        $this->registrarInscripcionExistente('I1', (int) $res['id_inscripcion']);

        // I2: otro estudiante en OTRA sección (control de fuga entre inscripciones).
        $res2 = $this->inscripcionService->procesarInscripcionPublica([
            'estudiante_nombres' => 'Zacarías Quetzal',
            'estudiante_apellidos' => 'OtroF11',
            'estudiante_fecha_nacimiento' => '2019-02-02',
            'codigo_estudiante' => 'EST-F11-002',
            'encargado_nombres' => 'Pedro Pablo',
            'encargado_apellidos' => 'Distinto Encargado',
            'encargado_telefono' => '5555-2222',
            'encargado_correo' => 'pedro.f11@example.invalid',
            'parentesco' => 'PADRE',
            'id_seccion' => $this->seccionBId,
        ]);
        if (!($res2['success'] ?? false)) {
            throw new RuntimeException('Fixture I2 no pudo crearse: ' . json_encode($res2, JSON_UNESCAPED_UNICODE));
        }
        $this->registrarInscripcionExistente('I2', (int) $res2['id_inscripcion']);

        // I3: contenido hostil (HTML/JS/PDF) en nombres, insertado directamente.
        $this->ins['I3'] = $this->crearInscripcionSql(
            'EST-F11-003',
            '<script>alert("XSS")</script><img src=x onerror=alert(1)>',
            ') >> /OpenAction << /S /JavaScript /JS (app.alert(1)) >> ( \\ \\) Ł 😀',
            'xss.f11@example.invalid',
            '<b>Encargado</b> & "Falso"',
            'TUTOR',
            $this->seccionAId,
            'CONFIRMADA',
            'INS-F11-XSS00001'
        );

        // I4: inscripción ANULADA (conserva código pero ya no es válida).
        $this->ins['I4'] = $this->crearInscripcionSql(
            'EST-F11-004',
            'Anulada',
            'Prueba F11',
            'anulada.f11@example.invalid',
            'Encargado',
            'Anulado',
            $this->seccionAId,
            'ANULADA',
            'INS-F11-ANUL0001'
        );

        // I5: estudiante con nombres muy largos (ajuste de línea) y carácter fuera de Windows-1252.
        $this->ins['I5'] = $this->crearInscripcionSql(
            'EST-F11-005',
            str_repeat('Nombrelargo ', 4),
            str_repeat('ApellidoLargo ', 4) . 'Łukasz',
            'larga.f11@example.invalid',
            'Encargado Largo',
            'Tutor',
            $this->seccionBId,
            'CONFIRMADA',
            'INS-F11-LARGO001'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function registrarInscripcionExistente(string $alias, int $idInscripcion): void
    {
        $this->createdInscripcionIds[] = $idInscripcion;
        $i = $this->inscripcionRepo->findById($idInscripcion);
        $this->createdEstudianteIds[] = $i->id_estudiante;
        $this->createdEncargadoIds[] = $i->id_encargado;
        $this->ins[$alias] = [
            'id_inscripcion' => $i->id_inscripcion,
            'id_estudiante' => $i->id_estudiante,
            'id_encargado' => $i->id_encargado,
            'codigo' => (string) $i->codigo_confirmacion,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function crearInscripcionSql(
        string $codEst,
        string $nombres,
        string $apellidos,
        string $correoEnc,
        string $encNombres,
        string $parentesco,
        int $idSeccion,
        string $estado,
        string $codigoConf
    ): array {
        $stmt = $this->db->prepare('INSERT INTO estudiantes (codigo_estudiante, nombres, apellidos, fecha_nacimiento, estado) VALUES (:c, :n, :a, "2019-03-03", "ACTIVO")');
        $stmt->execute([':c' => $codEst, ':n' => $nombres, ':a' => $apellidos]);
        $idEst = (int) $this->db->lastInsertId();
        $this->createdEstudianteIds[] = $idEst;

        $stmt = $this->db->prepare('INSERT INTO encargados (nombres, apellidos, telefono, correo) VALUES (:n, "Apellido Enc", "5555-9999", :c)');
        $stmt->execute([':n' => $encNombres, ':c' => $correoEnc]);
        $idEnc = (int) $this->db->lastInsertId();
        $this->createdEncargadoIds[] = $idEnc;

        $stmt = $this->db->prepare('INSERT INTO estudiantes_encargados (id_estudiante, id_encargado, parentesco, es_principal) VALUES (:e, :g, :p, 1)');
        $stmt->execute([':e' => $idEst, ':g' => $idEnc, ':p' => $parentesco]);

        $stmt = $this->db->prepare('INSERT INTO inscripciones (id_estudiante, id_seccion, id_encargado, codigo_confirmacion, estado, fecha_confirmacion) VALUES (:e, :s, :g, :c, :st, CURRENT_TIMESTAMP(6))');
        $stmt->execute([':e' => $idEst, ':s' => $idSeccion, ':g' => $idEnc, ':c' => $codigoConf, ':st' => $estado]);
        $idIns = (int) $this->db->lastInsertId();
        $this->createdInscripcionIds[] = $idIns;

        return [
            'id_inscripcion' => $idIns,
            'id_estudiante' => $idEst,
            'id_encargado' => $idEnc,
            'codigo' => $codigoConf,
        ];
    }

    private function cleanup(): void
    {
        try {
            if (!empty($this->createdInscripcionIds)) {
                $in = implode(',', array_map('intval', $this->createdInscripcionIds));
                $this->db->exec("DELETE FROM inscripciones WHERE id_inscripcion IN ($in)");
            }
            if ($this->seccionAId) {
                $this->db->exec("DELETE FROM inscripciones WHERE id_seccion IN ({$this->seccionAId}, {$this->seccionBId})");
            }
            if (!empty($this->createdEstudianteIds)) {
                $in = implode(',', array_map('intval', $this->createdEstudianteIds));
                $this->db->exec("DELETE FROM estudiantes_encargados WHERE id_estudiante IN ($in)");
                $this->db->exec("DELETE FROM estudiantes WHERE id_estudiante IN ($in)");
            }
            if (!empty($this->createdEncargadoIds)) {
                $in = implode(',', array_map('intval', $this->createdEncargadoIds));
                $this->db->exec("DELETE FROM estudiantes_encargados WHERE id_encargado IN ($in)");
                $this->db->exec("DELETE FROM encargados WHERE id_encargado IN ($in)");
            }
            if ($this->gradoPeriodoId) {
                $this->db->exec("DELETE FROM secciones WHERE id_grado_periodo = {$this->gradoPeriodoId}");
                $this->db->exec("DELETE FROM grados_periodos WHERE id_grado_periodo = {$this->gradoPeriodoId}");
            }
            if ($this->gradoId) {
                $this->db->exec("DELETE FROM grados WHERE id_grado = {$this->gradoId}");
            }
            if ($this->periodoId) {
                $this->db->exec("DELETE FROM periodos_escolares WHERE id_periodo_escolar = {$this->periodoId}");
            }
        } catch (\Throwable $e) {
            fwrite(STDERR, 'Aviso cleanup: ' . $e->getMessage() . "\n");
        }
    }

    // ----- utilidades de verificación PDF -----

    /** @return array{pdf: ?string, res: array<string, mixed>} */
    private function generar(string $alias): array
    {
        $res = $this->comprobanteService->generarPorCodigo($this->ins[$alias]['codigo']);

        return ['pdf' => $res['pdf'] ?? null, 'res' => $res];
    }

    private function pdfDe(string $alias): string
    {
        return (string) $this->generar($alias)['pdf'];
    }

    /**
     * Valida la estructura binaria PDF: cabecera, EOF, tabla xref con offsets reales,
     * trailer/Root/Catalog y número de páginas. Devuelve la lista de errores.
     *
     * @return array<int, string>
     */
    private function validarEstructuraPdf(string $pdf): array
    {
        $errores = [];

        if (!str_starts_with($pdf, '%PDF-')) {
            $errores[] = 'sin cabecera %PDF-';
        }
        if (preg_match('/startxref\s+(\d+)\s+%%EOF\s*$/', $pdf, $m) !== 1) {
            return array_merge($errores, ['sin startxref/%%EOF final']);
        }
        $xrefPos = (int) $m[1];
        if (substr($pdf, $xrefPos, 4) !== 'xref') {
            return array_merge($errores, ['startxref no apunta a xref']);
        }
        if (preg_match('/\Gxref\r?\n0 (\d+)\r?\n/', $pdf, $mx, 0, $xrefPos) !== 1) {
            return array_merge($errores, ['tabla xref ilegible']);
        }
        $n = (int) $mx[1];
        $cursor = $xrefPos + strlen($mx[0]);
        for ($i = 0; $i < $n; $i++) {
            $entrada = substr($pdf, $cursor + ($i * 20), 20);
            if (preg_match('/^(\d{10}) (\d{5}) ([nf]) \r?\n$/', $entrada, $me) !== 1) {
                $errores[] = "entrada xref #$i malformada";
                continue;
            }
            if ($me[3] === 'n') {
                $offset = (int) $me[1];
                if (preg_match('/^' . $i . ' 0 obj/', substr($pdf, $offset, 24)) !== 1) {
                    $errores[] = "offset xref #$i no apunta a '$i 0 obj'";
                }
            }
        }
        if (preg_match('/trailer\s*<<(.*?)>>\s*startxref/s', $pdf, $mt) !== 1) {
            $errores[] = 'sin trailer';
        } else {
            if (preg_match('/\/Size (\d+)/', $mt[1], $ms) !== 1 || (int) $ms[1] !== $n) {
                $errores[] = 'trailer /Size no coincide con xref';
            }
            if (preg_match('/\/Root (\d+) 0 R/', $mt[1], $mr) !== 1) {
                $errores[] = 'trailer sin /Root';
            } elseif (preg_match('/^' . $mr[1] . ' 0 obj\s*<<[^>]*\/Type \/Catalog/s', substr($pdf, $this->offsetObjeto($pdf, (int) $mr[1]), 200)) !== 1) {
                $errores[] = '/Root no es /Catalog';
            }
        }
        if (preg_match_all('/\/Type \/Page(?!s)/', $pdf) !== 1) {
            $errores[] = 'el comprobante debe tener exactamente 1 página';
        }

        return $errores;
    }

    private function offsetObjeto(string $pdf, int $numero): int
    {
        return (int) strpos($pdf, "\n$numero 0 obj") + 1;
    }

    /** PDF sin cuerpos de stream (para buscar acciones/estructuras peligrosas fuera del texto). */
    private function pdfSinStreams(string $pdf): string
    {
        return (string) preg_replace('/stream\r?\n.*?endstream/s', 'stream endstream', $pdf);
    }

    /** Texto visible del PDF (literales Tj) convertido de Windows-1252 a UTF-8. */
    private function textoPdf(string $pdf): string
    {
        preg_match_all('/\(((?:\\\\.|[^\\\\()])*)\)\s*Tj/s', $pdf, $m);
        $partes = [];
        foreach ($m[1] as $literal) {
            $literal = (string) preg_replace('/\\\\([()\\\\])/', '$1', $literal);
            $partes[] = mb_convert_encoding($literal, 'UTF-8', 'Windows-1252');
        }

        return implode("\n", $partes);
    }

    /** Contenido normalizado en una sola línea (para búsquedas con textos largos con ajuste de línea). */
    private function textoPlano(string $pdf): string
    {
        return (string) preg_replace('/\s+/u', ' ', $this->textoPdf($pdf));
    }

    private function requestPdf(string $query): Response
    {
        $controller = new ComprobanteInscripcionController();

        return $controller->descargar(new Request('GET', '/inscripcion/comprobante', $query === '' ? [] : $this->parseQuery($query)));
    }

    /** @return array<string, mixed> */
    private function parseQuery(string $q): array
    {
        parse_str($q, $out);

        return $out;
    }

    private function snapshotInscripcion(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM inscripciones WHERE id_inscripcion = :id');
        $stmt->execute([':id' => $id]);

        return (array) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function contarTablas(): array
    {
        $out = [];
        foreach (['inscripciones', 'estudiantes', 'encargados', 'estudiantes_encargados', 'secciones'] as $t) {
            $out[$t] = (int) $this->db->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        }

        return $out;
    }

    // =========================================================================
    // T11-01 .. T11-09
    // =========================================================================

    private function testT11_01_Trazabilidad(): void
    {
        $matriz = (string) file_get_contents(dirname(__DIR__) . '/docs/fase-0/matriz-trazabilidad.md');
        $filas = [];
        foreach (preg_split('/\R/', $matriz) as $linea) {
            if (preg_match('/^\|\s*(RF-\d{2})\s*\|/', $linea, $m) === 1) {
                if (!isset($filas[$m[1]])) {
                    $celdas = array_map('trim', explode('|', trim($linea, " |\t")));
                    $filas[$m[1]] = $celdas;
                }
            }
        }

        $rf13 = $filas['RF-13'] ?? [];
        $nombreOk = isset($rf13[1]) && str_contains($rf13[1], 'Comprobante PDF')
            && str_contains($rf13[1], 'Generar un comprobante de inscripción en PDF');
        $faseOk = ($rf13[2] ?? '') === '11';
        $estadoRf13 = $rf13[5] ?? '';
        $estadoOk = in_array($estadoRf13, ['PENDIENTE', 'IMPLEMENTADO Y VALIDADO'], true);

        $futurosPendientes = true;
        foreach (['RF-29', 'RF-30', 'RF-03'] as $rf) {
            if (($filas[$rf][5] ?? '') !== 'PENDIENTE') {
                $futurosPendientes = false;
            }
        }
        $fasesFuturas = ($filas['RF-29'][2] ?? '') === '12' && ($filas['RF-30'][2] ?? '') === '12' && ($filas['RF-03'][2] ?? '') === '13';

        $this->record(
            'T11-01',
            'Trazabilidad RF-13: nombre/descripcion oficiales, Fase 11; RF-29, RF-30 y RF-03 siguen pendientes (Fases 12 y 13)',
            'RF-13 = "Comprobante PDF" / Fase 11; RF-29, RF-30 (Fase 12) y RF-03 (Fase 13) PENDIENTE',
            sprintf('Nombre: %s | Fase: %s | Estado RF-13: %s | RF-29/RF-30/RF-03 pendientes: %s', $nombreOk ? 'OK' : 'ERR', $rf13[2] ?? '?', $estadoRf13, ($futurosPendientes && $fasesFuturas) ? 'SÍ' : 'NO'),
            $nombreOk && $faseOk && $estadoOk && $futurosPendientes && $fasesFuturas
        );
    }

    private function testT11_02_InscripcionValidaGeneraComprobante(): void
    {
        $g = $this->generar('I1');
        $ok = $g['res']['success'] === true && $g['res']['status'] === 200 && is_string($g['pdf']) && $g['pdf'] !== '';

        $this->record('T11-02', 'Inscripción confirmada válida permite generar el comprobante (RF-13)', 'success=true, HTTP 200, PDF no vacío',
            sprintf('success=%s, status=%s, bytes=%d', var_export($g['res']['success'], true), $g['res']['status'], strlen((string) $g['pdf'])), $ok);
    }

    private function testT11_03_InscripcionInexistenteNoGeneraPdf(): void
    {
        $casos = ['INS-2026-NOEXISTE', 'INS-F11-ZZZZ9999', 'INS-2026-00000000'];
        $fallos = [];
        foreach ($casos as $c) {
            $r = $this->comprobanteService->generarPorCodigo($c);
            $resp = $this->requestPdf('codigo=' . urlencode($c));
            if ($r['success'] !== false || $r['status'] !== 404 || isset($r['pdf'])
                || $resp->getStatusCode() !== 404 || str_contains($resp->getBody(), '%PDF-')
                || stripos((string) $resp->getHeader('Content-Type'), 'application/pdf') !== false) {
                $fallos[] = $c;
            }
        }
        $this->record('T11-03', 'Inscripción inexistente no genera PDF (HTTP 404, sin cuerpo PDF)', '404 controlado, sin bytes PDF, Content-Type no PDF',
            empty($fallos) ? '3/3 códigos inexistentes rechazados con HTTP 404' : 'Fallos: ' . implode(', ', $fallos), empty($fallos));
    }

    private function testT11_04_CodigoInvalidoNoGeneraPdf(): void
    {
        $casos = [
            'vacío' => '',
            'espacios' => '   ',
            'espacio interno' => 'INS 2026 ABCD',
            'path traversal' => '../../etc/passwd',
            'path traversal windows' => '..\\..\\windows\\win.ini',
            'inyección de cabecera' => "INS-2026-X\r\nSet-Cookie: a=b",
            'byte nulo' => "INS-2026-\0ABC",
            'sql' => "' OR '1'='1' --",
            'demasiado largo' => str_repeat('A', 65),
            'muy corto' => 'AB',
            'comodín LIKE' => 'INS-%',
            'unicode' => 'INS-2026-ÁÉÍÓÚÑ',
        ];
        $fallos = [];
        foreach ($casos as $nombre => $c) {
            $r = $this->comprobanteService->generarPorCodigo($c);
            $resp = $this->requestPdf('codigo=' . urlencode($c));
            if ($r['success'] !== false || $r['status'] !== 400 || isset($r['pdf'])
                || $resp->getStatusCode() !== 400 || str_contains($resp->getBody(), '%PDF-')) {
                $fallos[] = $nombre;
            }
        }
        $this->record('T11-04', 'Código inválido (formato/inyección/traversal) no genera PDF (HTTP 400)', '12/12 casos rechazados con HTTP 400 sin PDF',
            empty($fallos) ? sprintf('%d/%d casos rechazados con HTTP 400', count($casos), count($casos)) : 'Fallos: ' . implode(', ', $fallos), empty($fallos));
    }

    private function testT11_05_FirmaPdf(): void
    {
        $pdf = $this->pdfDe('I1');
        $ok = str_starts_with($pdf, '%PDF-') && preg_match('/^%PDF-1\.\d\R/', $pdf) === 1 && preg_match('/%%EOF\s*$/', $pdf) === 1;
        $this->record('T11-05', 'PDF inicia con la firma %PDF- y termina con %%EOF', 'Cabecera %PDF-1.x y marcador %%EOF final',
            sprintf('Cabecera: "%s", EOF final: %s', substr($pdf, 0, 8), preg_match('/%%EOF\s*$/', $pdf) === 1 ? 'SÍ' : 'NO'), $ok);
    }

    private function testT11_06_EstructuraPdfValida(): void
    {
        $pdf = $this->pdfDe('I1');
        $errores = $this->validarEstructuraPdf($pdf);

        $sinStreams = $this->pdfSinStreams($pdf);
        $peligrosos = [];
        foreach (['/JavaScript', '/JS ', '/OpenAction', '/Launch', '/URI', '/EmbeddedFile', '/AA ', '/RichMedia', '/SubmitForm'] as $nombre) {
            if (str_contains($sinStreams, $nombre)) {
                $peligrosos[] = $nombre;
            }
        }
        $ok = empty($errores) && empty($peligrosos);
        $this->record('T11-06', 'Estructura PDF real válida (xref con offsets reales, trailer, Catalog, 1 página) sin acciones activas', 'Cero errores estructurales y cero acciones /JavaScript, /OpenAction, /Launch, /URI',
            $ok ? sprintf('Estructura íntegra (%d bytes), sin acciones activas', strlen($pdf)) : 'Errores: ' . implode('; ', array_merge($errores, $peligrosos)), $ok);
    }

    private function testT11_07_CabecerasHttp(): void
    {
        $resp = $this->requestPdf('codigo=' . urlencode($this->ins['I1']['codigo']));
        // También a través del enrutador real (ruta registrada).
        $app = App::getInstance();
        $respRuta = $app->handleRequest(Request::create('GET', '/inscripcion/comprobante?codigo=' . urlencode($this->ins['I1']['codigo'])));

        $verificar = function (Response $r): array {
            $err = [];
            if ($r->getStatusCode() !== 200) $err[] = 'status ' . $r->getStatusCode();
            if ($r->getHeader('Content-Type') !== 'application/pdf') $err[] = 'Content-Type=' . $r->getHeader('Content-Type');
            if (preg_match('/^attachment; filename="comprobante-inscripcion-[A-Za-z0-9-]+\.pdf"$/', (string) $r->getHeader('Content-Disposition')) !== 1) $err[] = 'Content-Disposition=' . $r->getHeader('Content-Disposition');
            if ((int) $r->getHeader('Content-Length') !== strlen($r->getBody())) $err[] = 'Content-Length no coincide';
            if ($r->getHeader('X-Content-Type-Options') !== 'nosniff') $err[] = 'sin nosniff';
            if (!str_contains((string) $r->getHeader('Cache-Control'), 'no-store')) $err[] = 'sin no-store';
            if (!str_starts_with($r->getBody(), '%PDF-')) $err[] = 'cuerpo sin %PDF-';

            return $err;
        };

        $e1 = $verificar($resp);
        $e2 = $verificar($respRuta);
        $ok = empty($e1) && empty($e2);
        $this->record('T11-07', 'Cabeceras HTTP correctas (Content-Type, Content-Disposition, Content-Length, nosniff, no-store) en controlador y enrutador', 'HTTP 200 application/pdf, attachment con nombre seguro',
            $ok ? sprintf('Disposition: %s | Length: %s', $resp->getHeader('Content-Disposition'), $resp->getHeader('Content-Length')) : 'Errores: ' . implode('; ', array_merge($e1, $e2)), $ok);
    }

    private function testT11_08_PdfNoVacio(): void
    {
        $pdf = $this->pdfDe('I1');
        $tieneTexto = preg_match('/BT\s.*?\(.+?\)\s*Tj\s+ET/s', $pdf) === 1;
        $ok = strlen($pdf) > 1500 && $tieneTexto && substr_count($pdf, ' Tj') >= 10;
        $this->record('T11-08', 'PDF no vacío: contiene página con bloques de texto', 'Más de 1500 bytes y al menos 10 operaciones de texto',
            sprintf('%d bytes, %d operaciones de texto', strlen($pdf), substr_count($pdf, ' Tj')), $ok);
    }

    private function testT11_09_Utf8YAcentos(): void
    {
        $pdf = $this->pdfDe('I1');
        $t = $this->textoPlano($pdf);
        $esperados = ['José Ángel Muñoz Pérez', 'María Ñandú Gómez Ruiz', 'COMPROBANTE DE INSCRIPCIÓN', 'CÓDIGO ÚNICO DE CONFIRMACIÓN', 'Sección Mañana F11'];
        $faltan = array_filter($esperados, fn($x) => !str_contains($t, $x));

        // Caracteres fuera de Windows-1252 no rompen la generación (se sustituyen por "?").
        $pdf5 = $this->pdfDe('I5');
        $t5 = $this->textoPlano($pdf5);
        $okLargo = empty($this->validarEstructuraPdf($pdf5)) && str_contains($t5, '?ukasz');

        $this->record('T11-09', 'Caracteres UTF-8/acentos/ñ preservados; caracteres fuera de Windows-1252 y nombres largos no rompen el PDF', 'Acentos y ñ legibles; Ł -> "?"; estructura válida con nombres largos',
            sprintf('Faltantes: %s | Nombres largos/Ł: %s', empty($faltan) ? 'ninguno' : implode(' | ', $faltan), $okLargo ? 'OK' : 'ERR'), empty($faltan) && $okLargo);
    }

    // =========================================================================
    // T11-10 .. T11-14: autoridad del contenido
    // =========================================================================

    private function testT11_10_DatosDeLaInscripcionSolicitada(): void
    {
        $t = $this->textoPlano($this->pdfDe('I1'));
        $propios = str_contains($t, 'José Ángel Muñoz Pérez') && str_contains($t, $this->ins['I1']['codigo']);
        $ajenos = str_contains($t, 'Zacarías') || str_contains($t, 'OtroF11') || str_contains($t, $this->ins['I2']['codigo']) || str_contains($t, 'Pedro Pablo');
        $this->record('T11-10', 'Los datos del PDF pertenecen únicamente a la inscripción solicitada', 'Datos de I1 presentes; ningún dato de I2',
            sprintf('Propios: %s | Datos ajenos presentes: %s', $propios ? 'SÍ' : 'NO', $ajenos ? 'SÍ' : 'NO'), $propios && !$ajenos);
    }

    private function testT11_11_CodigoConfirmacionCorrecto(): void
    {
        $cod1 = $this->ins['I1']['codigo'];
        $t = $this->textoPdf($this->pdfDe('I1'));
        $formato = preg_match('/^INS-\d{4}-[0-9A-F]{8}$/', $cod1) === 1;
        $lineas = array_map('trim', explode("\n", $t));
        $ok = in_array($cod1, $lineas, true) && $formato;
        $this->record('T11-11', 'Código de confirmación del PDF coincide exactamente con el de la BD (RF-12)', 'Línea de código == código persistido, formato INS-AAAA-XXXXXXXX',
            sprintf('BD: %s | En PDF: %s | Formato: %s', $cod1, in_array($cod1, $lineas, true) ? 'idéntico' : 'distinto', $formato ? 'OK' : 'ERR'), $ok);
    }

    private function testT11_12_EstudianteYEncargadoCorrectos(): void
    {
        $t = $this->textoPlano($this->pdfDe('I1'));
        $ok = str_contains($t, 'Estudiante: José Ángel Muñoz Pérez')
            && str_contains($t, 'Padre, madre o encargado: María Ñandú Gómez Ruiz (MADRE)');
        $this->record('T11-12', 'Estudiante, encargado y parentesco correctos (obtenidos de BD)', 'Estudiante "José Ángel Muñoz Pérez"; encargado "María Ñandú Gómez Ruiz (MADRE)"',
            $ok ? 'Estudiante y encargado coinciden con la BD' : 'Texto: ' . substr($t, 0, 300), $ok);
    }

    private function testT11_13_GradoSeccionPeriodoCorrectos(): void
    {
        $tA = $this->textoPlano($this->pdfDe('I1'));
        $tB = $this->textoPlano($this->pdfDe('I2'));
        $okA = str_contains($tA, 'Grado: Primero Primaria F11') && str_contains($tA, 'Sección: F11-A (Sección Mañana F11)') && str_contains($tA, 'Ciclo escolar: Ciclo F11 Comprobantes 2026');
        $okB = str_contains($tB, 'Sección: F11-B (Sección Tarde F11)') && !str_contains($tB, 'F11-A');
        $this->record('T11-13', 'Grado, sección y ciclo escolar correctos para cada inscripción', 'I1 -> F11-A; I2 -> F11-B; mismo grado y ciclo',
            sprintf('I1: %s | I2: %s', $okA ? 'OK' : 'ERR', $okB ? 'OK' : 'ERR'), $okA && $okB);
    }

    private function testT11_14_EstadoYCondicionDeGeneracion(): void
    {
        $t = $this->textoPlano($this->pdfDe('I1'));
        $estadoPdf = str_contains($t, 'Estado de la inscripción: CONFIRMADA') && str_contains($t, 'Fecha de inscripción: ') && str_contains($t, 'Fecha de confirmación: ');

        // ANULADA conserva código pero NO genera comprobante (HTTP 409).
        $anulada = $this->comprobanteService->generarPorCodigo($this->ins['I4']['codigo']);
        $respAnulada = $this->requestPdf('codigo=' . urlencode($this->ins['I4']['codigo']));
        $bloqueaAnulada = $anulada['success'] === false && $anulada['status'] === 409 && !isset($anulada['pdf'])
            && $respAnulada->getStatusCode() === 409 && !str_contains($respAnulada->getBody(), '%PDF-');

        // Cambio de estado real: CONFIRMADA -> ANULADA bloquea; se restaura.
        $id = (int) $this->ins['I2']['id_inscripcion'];
        $antes = $this->comprobanteService->generarPorCodigo($this->ins['I2']['codigo']);
        $this->db->exec("UPDATE inscripciones SET estado = 'ANULADA' WHERE id_inscripcion = {$id}");
        $durante = $this->comprobanteService->generarPorCodigo($this->ins['I2']['codigo']);
        $this->db->exec("UPDATE inscripciones SET estado = 'CONFIRMADA' WHERE id_inscripcion = {$id}");
        $despues = $this->comprobanteService->generarPorCodigo($this->ins['I2']['codigo']);
        $transicion = $antes['success'] && !$durante['success'] && $durante['status'] === 409 && $despues['success'];

        // PENDIENTE/RECHAZADA no tienen código (constraint chk_inscripciones_confirmacion): no es posible solicitarlas.
        $sinCodigo = (int) $this->db->query("SELECT COUNT(*) FROM inscripciones WHERE estado IN ('PENDIENTE','RECHAZADA') AND codigo_confirmacion IS NOT NULL")->fetchColumn() === 0;

        $ok = $estadoPdf && $bloqueaAnulada && $transicion && $sinCodigo;
        $this->record('T11-14', 'Estado correcto en el PDF; solo CONFIRMADA genera comprobante (ANULADA -> 409; PENDIENTE/RECHAZADA sin código)', 'CONFIRMADA genera; ANULADA bloquea con 409; transición reversible',
            sprintf('Estado en PDF: %s | ANULADA bloqueada: %s | Transición CONFIRMADA->ANULADA->CONFIRMADA: %s | Sin código en PENDIENTE/RECHAZADA: %s', $estadoPdf ? 'OK' : 'ERR', $bloqueaAnulada ? 'SÍ' : 'NO', $transicion ? 'OK' : 'ERR', $sinCodigo ? 'SÍ' : 'NO'), $ok);
    }

    // =========================================================================
    // T11-15 .. T11-19: privacidad y seguridad
    // =========================================================================

    private function testT11_15_SinIdsInternosNiRutasNiSql(): void
    {
        $pdf = $this->pdfDe('I1');
        $t = $this->textoPdf($pdf);
        $hallazgos = [];

        foreach (['id_inscripcion', 'id_estudiante', 'id_encargado', 'id_seccion', 'id_usuario', 'ID interno'] as $x) {
            if (stripos($t, $x) !== false) $hallazgos[] = $x;
        }
        // Valores de IDs internos como tokens aislados en el texto visible.
        foreach (['id_inscripcion', 'id_estudiante', 'id_encargado'] as $campo) {
            $valor = (string) $this->ins['I1'][$campo];
            if (preg_match('/(?<![\w-])' . preg_quote($valor, '/') . '(?![\w-])/', $t) === 1 && !preg_match('/^\d{4}$/', $valor)) {
                // Se acepta coincidencia únicamente si es un fragmento de fecha/hora legítimo; se verifica abajo.
                $enLineasEtiquetadas = preg_match('/(?<![\w\/:-])' . preg_quote($valor, '/') . '(?![\w\/:-])/', $t) === 1;
                if ($enLineasEtiquetadas) $hallazgos[] = "valor interno $campo=$valor";
            }
        }
        foreach (['ProyectoFinal', 'C:\\', 'C:/', '/app/', '/storage/', 'SELECT ', ' FROM ', 'INSERT ', 'SQLSTATE', 'PDOException', 'Stack trace', '.php'] as $x) {
            if (stripos($t, $x) !== false) $hallazgos[] = $x;
        }
        // Metadatos del documento: sin rutas del servidor ni identificadores internos.
        $info = $this->pdfSinStreams($pdf);
        foreach (['ProyectoFinal', 'C:\\', 'C:/', 'id_inscripcion', 'SQLSTATE'] as $x) {
            if (stripos($info, $x) !== false) $hallazgos[] = "metadatos:$x";
        }

        $this->record('T11-15', 'El PDF no expone IDs internos, rutas del servidor, SQL ni trazas', 'Cero IDs/rutas/SQL en texto y metadatos',
            empty($hallazgos) ? 'Sin IDs internos, rutas ni SQL (texto y metadatos)' : 'Hallazgos: ' . implode(', ', $hallazgos), empty($hallazgos));
    }

    private function testT11_16_SinPasswordsNiHashesNiDatosContacto(): void
    {
        $pdf = $this->pdfDe('I1');
        $todo = $pdf . "\n" . $this->textoPdf($pdf);
        $hallazgos = [];

        foreach (['$2y$', 'clave_hash', 'password', 'contraseña', 'token', 'bcrypt'] as $x) {
            if (stripos($todo, $x) !== false) $hallazgos[] = $x;
        }
        $hashes = $this->db->query('SELECT clave_hash FROM usuarios LIMIT 5')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($hashes as $h) {
            if ($h !== '' && str_contains($todo, (string) $h)) $hallazgos[] = 'hash de usuario real';
        }
        // Minimización: datos de contacto y fecha de nacimiento del menor no se incluyen.
        foreach ([self::TEL_I1, self::MAIL_I1, '2019-05-14', '14/05/2019', 'EST-F11-001'] as $x) {
            if (str_contains($todo, $x)) $hallazgos[] = "dato no necesario: $x";
        }
        $this->record('T11-16', 'Sin contraseñas/hashes y con mínima exposición (sin teléfono, correo, fecha de nacimiento ni código de estudiante)', 'Cero hashes/credenciales y cero datos de contacto',
            empty($hallazgos) ? 'Sin credenciales ni datos de contacto en el PDF' : 'Hallazgos: ' . implode(', ', $hallazgos), empty($hallazgos));
    }

    private function testT11_17_SinFiltracionEntreEstudiantes(): void
    {
        $t1 = $this->textoPlano($this->pdfDe('I1'));
        $t2 = $this->textoPlano($this->pdfDe('I2'));
        $t5 = $this->textoPlano($this->pdfDe('I5'));
        // I1 e I5 son de secciones distintas; I1 comparte sección con I3 e I4.
        $f1 = str_contains($t1, 'Zacarías') || str_contains($t1, 'Nombrelargo') || str_contains($t1, 'Anulada') || str_contains($t1, 'script');
        $f2 = str_contains($t2, 'José Ángel') || str_contains($t2, 'Muñoz') || str_contains($t2, 'María Ñandú') || str_contains($t2, $this->ins['I1']['codigo']);
        $f5 = str_contains($t5, 'José Ángel') || str_contains($t5, 'Zacarías') || str_contains($t5, 'Anulada');
        $this->record('T11-17', 'Datos de otros estudiantes (misma sección o distinta) no se filtran', 'Cada PDF contiene solo su inscripción',
            sprintf('Fuga I1: %s | Fuga I2: %s | Fuga I5: %s', $f1 ? 'SÍ' : 'NO', $f2 ? 'SÍ' : 'NO', $f5 ? 'SÍ' : 'NO'), !$f1 && !$f2 && !$f5);
    }

    private function testT11_18_ManipulacionDeIdentificadorBloqueada(): void
    {
        $cod1 = $this->ins['I1']['codigo'];
        $fallos = [];

        // a) Parámetros extra (ids internos de otra inscripción) son ignorados: sigue siendo el PDF de I1.
        $r = $this->requestPdf(http_build_query([
            'codigo' => $cod1,
            'id_inscripcion' => $this->ins['I2']['id_inscripcion'],
            'id_estudiante' => $this->ins['I2']['id_estudiante'],
            'estudiante_nombre' => 'FALSIFICADO',
            'estado' => 'ANULADA',
            'grado' => 'Sexto Falso',
        ]));
        $t = $this->textoPlano($r->getBody());
        if (!($r->getStatusCode() === 200 && str_contains($t, 'José Ángel Muñoz Pérez') && !str_contains($t, 'FALSIFICADO') && !str_contains($t, 'Sexto Falso') && !str_contains($t, 'Zacarías'))) {
            $fallos[] = 'parámetros extra no ignorados';
        }

        // b) Solo ID interno, sin código: no existe acceso por ID.
        foreach ([
            'id=' . $this->ins['I1']['id_inscripcion'],
            'id_inscripcion=' . $this->ins['I1']['id_inscripcion'],
            'inscripcion=' . $this->ins['I1']['id_inscripcion'],
            'codigo=' . $this->ins['I1']['id_inscripcion'],
            '',
        ] as $q) {
            $rr = $this->requestPdf($q);
            if (str_contains($rr->getBody(), '%PDF-') || !in_array($rr->getStatusCode(), [400, 404], true)) {
                $fallos[] = "acceso por id: '$q'";
            }
        }

        // c) Enumeración trivial: códigos adyacentes/parciales/comodines no devuelven PDF.
        foreach (['INS-', 'INS-' . date('Y') . '-', substr($cod1, 0, 12), 'INS-' . date('Y') . '-00000000', 'INS-%', '%'] as $q) {
            $rr = $this->requestPdf('codigo=' . urlencode($q));
            if (str_contains($rr->getBody(), '%PDF-')) {
                $fallos[] = "enumeración: '$q'";
            }
        }

        // d) Parámetro como arreglo no rompe ni devuelve PDF.
        $ra = $this->requestPdf('codigo[]=' . urlencode($cod1));
        if (str_contains($ra->getBody(), '%PDF-') || $ra->getStatusCode() !== 400) {
            $fallos[] = 'codigo[] devolvió PDF o estado inesperado';
        }

        // e) Los mensajes de error de inexistente/no confirmada no revelan datos del estudiante.
        $rn = $this->requestPdf('codigo=' . urlencode($this->ins['I4']['codigo']));
        if (str_contains($rn->getBody(), 'Prueba F11') || str_contains($rn->getBody(), 'Anulada Prueba')) {
            $fallos[] = 'error 409 filtra datos del estudiante';
        }

        $this->record('T11-18', 'Manipulación del identificador bloqueada (ids ignorados, sin acceso por ID, sin enumeración)', 'Solo el código público determina el contenido; ids/parametros extra ignorados',
            empty($fallos) ? 'Parámetros extra ignorados; acceso por ID, comodines y arreglos rechazados' : 'Fallos: ' . implode('; ', $fallos), empty($fallos));
    }

    private function testT11_19_XssYContenidoMaliciosoNeutralizado(): void
    {
        $g = $this->generar('I3');
        $pdf = (string) $g['pdf'];
        $estructura = $this->validarEstructuraPdf($pdf);

        $sinStreams = $this->pdfSinStreams($pdf);
        $peligrosos = [];
        foreach (['/JavaScript', '/JS ', '/OpenAction', '/Launch', '/URI', '/AA '] as $n) {
            if (str_contains($sinStreams, $n)) $peligrosos[] = $n;
        }

        // El contenido hostil aparece únicamente como TEXTO inerte (literal escapado dentro de un stream).
        $texto = $this->textoPlano($pdf);
        $comoTexto = str_contains($texto, '<script>alert("XSS")</script>') && str_contains($texto, '/OpenAction');
        // No se interpretó HTML: el PDF no contiene el HTML renderizado como estructura.
        $okGen = $g['res']['success'] === true;

        // El HTML de las vistas de error no refleja el código sin escapar.
        $xss = '<script>alert(1)</script>';
        $rv = $this->requestPdf('codigo=' . urlencode($xss));
        $escapado = !str_contains($rv->getBody(), $xss);

        $ok = $okGen && empty($estructura) && empty($peligrosos) && $comoTexto && $escapado;
        $this->record('T11-19', 'HTML/JS/operadores PDF maliciosos en nombres quedan neutralizados como texto inerte; vistas de error escapan el código', 'PDF íntegro sin acciones activas; payload solo como texto; salida HTML escapada',
            sprintf('Generado: %s | Estructura: %s | Acciones activas: %s | Payload como texto inerte: %s | Error HTML escapado: %s', $okGen ? 'SÍ' : 'NO', empty($estructura) ? 'OK' : implode(',', $estructura), empty($peligrosos) ? 'ninguna' : implode(',', $peligrosos), $comoTexto ? 'SÍ' : 'NO', $escapado ? 'SÍ' : 'NO'), $ok);
    }

    // =========================================================================
    // T11-20 .. T11-26
    // =========================================================================

    private function testT11_20_NombreDeArchivoSeguro(): void
    {
        $fallos = [];
        foreach (['I1', 'I2', 'I3', 'I5'] as $alias) {
            $r = $this->requestPdf('codigo=' . urlencode($this->ins[$alias]['codigo']));
            $cd = (string) $r->getHeader('Content-Disposition');
            if (preg_match('/^attachment; filename="comprobante-inscripcion-[A-Za-z0-9-]+\.pdf"$/', $cd) !== 1
                || preg_match('/[\r\n\0\\\\\/:*?<>|]/', explode('filename=', $cd)[1] ?? '') === 1 && false) {
                $fallos[] = "$alias: $cd";
            }
            $nombre = explode('"', $cd)[1] ?? '';
            if (preg_match('/[\x00-\x1F\x7F\/\\\\]|\.\./', $nombre) === 1 || str_contains($cd, "\r") || str_contains($cd, "\n")) {
                $fallos[] = "$alias: caracteres no permitidos";
            }
            // El nombre no incluye nombre del estudiante ni datos personales.
            if (preg_match('/Jos|Mu.oz|Gomez|Zacar/i', $nombre) === 1) {
                $fallos[] = "$alias: nombre con datos personales";
            }
        }
        // Intento de traversal/inyección en el código: se rechaza antes de construir cabeceras.
        foreach (["../x", "A\r\nX-Inyectado: 1", 'a"; filename="evil.php'] as $mal) {
            $r = $this->requestPdf('codigo=' . urlencode($mal));
            if ($r->getHeader('Content-Disposition') !== null || $r->getHeader('X-Inyectado') !== null) {
                $fallos[] = 'cabecera generada con entrada maliciosa';
            }
        }
        $this->record('T11-20', 'Nombre de archivo seguro: sin path traversal, caracteres de control ni inyección en cabeceras ni datos personales', 'attachment; filename="comprobante-inscripcion-[CÓDIGO].pdf" validado',
            empty($fallos) ? 'Nombres de archivo seguros; entradas maliciosas rechazadas sin cabeceras' : 'Fallos: ' . implode('; ', $fallos), empty($fallos));
    }

    private function testT11_21_NoModificaInscripcion(): void
    {
        $antes = $this->snapshotInscripcion((int) $this->ins['I1']['id_inscripcion']);
        $this->pdfDe('I1');
        $this->requestPdf('codigo=' . urlencode($this->ins['I1']['codigo']));
        $despues = $this->snapshotInscripcion((int) $this->ins['I1']['id_inscripcion']);
        $ok = $antes === $despues && !empty($antes);
        $this->record('T11-21', 'Generar el PDF no modifica la inscripción (ni siquiera fecha_actualizacion)', 'Fila de inscripciones idéntica antes y después',
            $ok ? 'Fila idéntica (' . count($antes) . ' columnas comparadas)' : 'La fila cambió', $ok);
    }

    private function testT11_22_DescargasRepetidasSinDuplicados(): void
    {
        $antes = $this->contarTablas();
        $notifAntes = (int) $this->db->query('SELECT COUNT(*) FROM notificaciones')->fetchColumn();
        $firmas = [];
        for ($i = 0; $i < 6; $i++) {
            $r = $this->requestPdf('codigo=' . urlencode($this->ins['I1']['codigo']));
            $firmas[] = ($r->getStatusCode() === 200 && str_starts_with($r->getBody(), '%PDF-')) ? 'ok' : 'err';
        }
        $despues = $this->contarTablas();
        $notifDespues = (int) $this->db->query('SELECT COUNT(*) FROM notificaciones')->fetchColumn();
        $ok = $antes === $despues && $notifAntes === $notifDespues && count(array_unique($firmas)) === 1 && $firmas[0] === 'ok';
        $this->record('T11-22', 'Descargas repetidas (6) no crean registros duplicados en ninguna tabla', 'Conteos idénticos antes/después; 6/6 descargas válidas',
            sprintf('Conteos iguales: %s | Descargas válidas: %d/6', $antes === $despues ? 'SÍ' : 'NO', count(array_filter($firmas, fn($f) => $f === 'ok'))), $ok);
    }

    private function testT11_23_SinArchivosTemporalesPublicos(): void
    {
        $listar = function (string $dir): array {
            $out = [];
            if (!is_dir($dir)) return $out;
            try {
                $dirIt = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
                $it = new RecursiveIteratorIterator($dirIt, RecursiveIteratorIterator::SELF_FIRST);
                foreach ($it as $f) {
                    try {
                        if ($f->isFile() && preg_match('/\.pdf$/i', $f->getFilename())) {
                            $out[] = $f->getPathname();
                        }
                    } catch (\Throwable $e) {
                        // Ignorar archivos/directorios no accesibles
                    }
                }
            } catch (\Throwable $e) {
                // Ignorar excepciones de permisos en directorios temporales del sistema
            }
            sort($out);

            return $out;
        };
        $raiz = dirname(__DIR__);
        $dirs = [$raiz . '/public', $raiz . '/storage', sys_get_temp_dir()];
        $antes = array_map($listar, $dirs);

        for ($i = 0; $i < 3; $i++) {
            $this->requestPdf('codigo=' . urlencode($this->ins['I1']['codigo']));
            $this->pdfDe('I2');
        }

        $despues = array_map($listar, $dirs);
        $ok = $antes === $despues;
        $this->record('T11-23', 'No se generan archivos .pdf temporales ni permanentes (public/, storage/ ni directorio temporal)', 'Mismo conjunto de archivos .pdf antes y después (generación 100% en memoria)',
            $ok ? 'Sin archivos PDF nuevos en public/, storage/ ni temp' : 'Aparecieron archivos PDF nuevos', $ok);
    }

    private function testT11_24_ErrorDeGeneracionControlado(): void
    {
        // 1) El generador lanza una excepción con información sensible.
        $fallido = new class extends PdfService {
            public function renderComprobanteInscripcion(array $datos, string $emitidoEn): string
            {
                throw new RuntimeException('SQLSTATE[HY000] secreto C:\\ruta\\interna\\fpdf.php password=abc123');
            }
        };
        $svc = new ComprobanteInscripcionService(null, $fallido);
        $r = $svc->generarPorCodigo($this->ins['I1']['codigo']);
        $ctrl = new ComprobanteInscripcionController($svc);
        $resp = $ctrl->descargar(new Request('GET', '/inscripcion/comprobante', ['codigo' => $this->ins['I1']['codigo']]));
        $cuerpo = $resp->getBody();

        $controlada = $r['success'] === false && $r['status'] === 500 && !isset($r['pdf'])
            && $resp->getStatusCode() === 500
            && !str_contains($cuerpo, '%PDF-')
            && stripos((string) $resp->getHeader('Content-Type'), 'text/html') === 0
            && !str_contains($cuerpo, 'SQLSTATE') && !str_contains($cuerpo, 'secreto') && !str_contains($cuerpo, 'abc123')
            && !str_contains($cuerpo, 'Stack trace') && !str_contains($cuerpo, 'fpdf.php')
            && $resp->getHeader('Content-Disposition') === null;

        // 2) El generador devuelve bytes que no son un PDF válido: se rechaza (no se entrega un PDF falso).
        $corrupto = new class extends PdfService {
            public function renderComprobanteInscripcion(array $datos, string $emitidoEn): string
            {
                return '<html>No soy un PDF</html>';
            }
        };
        $r2 = (new ComprobanteInscripcionService(null, $corrupto))->generarPorCodigo($this->ins['I1']['codigo']);
        $rechazaFalso = $r2['success'] === false && $r2['status'] === 500 && !isset($r2['pdf']);

        $this->record('T11-24', 'Error de generación manejado de forma controlada (HTTP 500 genérico, sin trazas ni PDF falso)', 'Excepción -> 500 sin detalles; salida no PDF -> rechazada',
            sprintf('Excepción controlada: %s | Documento inválido rechazado: %s', $controlada ? 'SÍ' : 'NO', $rechazaFalso ? 'SÍ' : 'NO'), $controlada && $rechazaFalso);
    }

    private function testT11_25_GeneracionLocalSinServiciosExternos(): void
    {
        $raiz = dirname(__DIR__);
        $fuentes = [
            $raiz . '/app/Services/PdfService.php',
            $raiz . '/app/Services/ComprobanteInscripcionService.php',
            $raiz . '/app/Controllers/ComprobanteInscripcionController.php',
        ];
        $externos = [];
        foreach ($fuentes as $f) {
            $c = (string) file_get_contents($f);
            foreach (['curl_', 'file_get_contents(\'http', 'file_get_contents("http', 'fsockopen', 'http://', 'https://', 'wkhtmltopdf', 'shell_exec', 'exec(', 'proc_open', 'system('] as $x) {
                if (stripos($c, $x) !== false) $externos[] = basename($f) . ':' . $x;
            }
        }
        $fpdf = $raiz . '/app/Libraries/Fpdf/fpdf.php';
        $version = is_file($fpdf) && preg_match("/const VERSION = '([\d.]+)'/", (string) file_get_contents($fpdf), $m) === 1 ? $m[1] : '?';
        $licencia = is_file($raiz . '/app/Libraries/Fpdf/license.txt');
        $fuentesPdf = is_file($raiz . '/app/Libraries/Fpdf/font/helvetica.php') && is_file($raiz . '/app/Libraries/Fpdf/font/courierb.php');

        $ok = empty($externos) && $version === '1.86' && $licencia && $fuentesPdf;
        $this->record('T11-25', 'Generación 100% local: biblioteca FPDF 1.86 incluida con licencia, sin servicios web externos ni procesos externos', 'FPDF 1.86 local; cero llamadas HTTP/exec en el código de la fase',
            sprintf('FPDF %s | Licencia: %s | Fuentes: %s | Referencias externas: %s', $version, $licencia ? 'SÍ' : 'NO', $fuentesPdf ? 'SÍ' : 'NO', empty($externos) ? 'ninguna' : implode(',', $externos)), $ok);
    }

    private function testT11_26_SinPersistenciaDePdfEnBaseDeDatos(): void
    {
        $dbName = (string) $this->db->query('SELECT DATABASE()')->fetchColumn();

        $tablasPdf = $this->db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :d AND (table_name LIKE '%pdf%' OR table_name LIKE '%comprobante%')");
        $tablasPdf->execute([':d' => $dbName]);
        $nTablas = (int) $tablasPdf->fetchColumn();

        $colsPdf = $this->db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = :d AND table_name = 'inscripciones' AND (column_name LIKE '%pdf%' OR column_name LIKE '%comprobante%' OR data_type IN ('blob','longblob','mediumblob'))");
        $colsPdf->execute([':d' => $dbName]);
        $nCols = (int) $colsPdf->fetchColumn();

        $stmt = $this->db->prepare("SELECT column_name FROM information_schema.columns WHERE table_schema = :d AND table_name = 'inscripciones' ORDER BY ordinal_position");
        $stmt->execute([':d' => $dbName]);
        $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $esperadas = ['id_inscripcion', 'id_estudiante', 'id_encargado', 'id_seccion', 'codigo_confirmacion', 'estado', 'fecha_inscripcion', 'fecha_confirmacion', 'estudiante_vigente', 'seccion_vigente', 'fecha_actualizacion'];
        $intacta = $columnas === $esperadas;

        $schema = (string) file_get_contents(dirname(__DIR__) . '/database/schema.sql');
        $schemaLimpio = !preg_match('/CREATE TABLE\s+\w*(pdf|comprobante)\w*/i', $schema);

        $ok = $nTablas === 0 && $nCols === 0 && $intacta && $schemaLimpio;
        $this->record('T11-26', 'El PDF no se persiste: sin tablas/columnas/BLOB de comprobantes y tabla inscripciones sin cambios', 'Cero tablas pdf/comprobante, 11 columnas originales en inscripciones',
            sprintf('Tablas pdf: %d | Columnas pdf/BLOB: %d | Columnas inscripciones intactas: %s | schema.sql sin tabla de PDFs: %s', $nTablas, $nCols, $intacta ? 'SÍ' : 'NO', $schemaLimpio ? 'SÍ' : 'NO'), $ok);
    }

    // =========================================================================
    // T11-27: flujo HTTP real end-to-end
    // =========================================================================

    private function testT11_27_FlujoHttpReal(): void
    {
        echo "\n>>> Iniciando servidor web PHP embebido para prueba HTTP real T11-27...\n";

        $port = 8099;
        $docRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public';
        $serverLog = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php_server_f11_' . uniqid() . '.log';
        $descriptors = [0 => ['pipe', 'r'], 1 => ['file', $serverLog, 'a'], 2 => ['file', $serverLog, 'a']];
        $cmd = sprintf('"%s" -S 127.0.0.1:%d -t "%s"', PHP_BINARY, $port, $docRoot);
        $process = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));

        if (!is_resource($process)) {
            $this->record('T11-27', 'Flujo HTTP real end-to-end del comprobante PDF', 'Servidor iniciado', 'No se pudo iniciar el servidor', false);
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
            $this->record('T11-27', 'Flujo HTTP real end-to-end del comprobante PDF', 'Servidor conectado', 'Timeout esperando el servidor HTTP', false);
            return;
        }

        $cookie = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_f11_' . uniqid() . '.txt';
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
                return ['code' => 0, 'headers' => '', 'body' => $err, 'location' => '', 'contentType' => ''];
            }
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $hs = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $ct = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);
            $headers = substr((string) $raw, 0, $hs);
            $body = substr((string) $raw, $hs);
            $loc = preg_match('/^Location:\s*([^\r\n]+)/mi', $headers, $m) === 1 ? trim($m[1]) : '';

            return ['code' => $code, 'headers' => $headers, 'body' => $body, 'location' => $loc, 'contentType' => $ct];
        };

        $base = "http://127.0.0.1:$port";
        $detalle = [];
        $ok = false;
        $idsCreados = [];

        try {
            // 1. GET formulario -> token CSRF real
            $form = $http("$base/inscripcion", 'GET', null, $cookie);
            $token = preg_match('/name=["\']_token["\']\s+value=["\']([^"\']+)["\']/i', $form['body'], $m) === 1 ? $m[1] : '';
            $detalle[] = 'Form:' . $form['code'];

            // 2. POST inscripción real (RF-11) -> 302 /inscripcion/exito (RF-12 código)
            $post = $http("$base/inscripcion", 'POST', [
                '_token' => $token,
                'estudiante_nombres' => 'Ángela Lucía',
                'estudiante_apellidos' => 'Peñate Http',
                'estudiante_fecha_nacimiento' => '2019-08-21',
                'codigo_estudiante' => 'EST-F11-HTTP',
                'encargado_nombres' => 'Rubén Éder',
                'encargado_apellidos' => 'Peñate',
                'encargado_telefono' => '5555-7777',
                'encargado_correo' => 'ruben.f11@example.invalid',
                'parentesco' => 'PADRE',
                'id_seccion' => $this->seccionAId,
            ], $cookie);
            $detalle[] = 'POST:' . $post['code'];
            $okPost = $post['code'] === 302 && str_contains($post['location'], '/inscripcion/exito');

            $toBaseUrl = function (string $u) use ($base): string {
                $path = parse_url($u, PHP_URL_PATH) ?? '';
                if (preg_match('#(?:/ProyectoFinal(?:/public)?)?(/inscripcion.*)$#i', $path, $m) === 1) {
                    $path = $m[1];
                }
                $query = parse_url($u, PHP_URL_QUERY);
                return $base . ($path === '' ? '/' : $path) . ($query !== null && $query !== '' ? '?' . $query : '');
            };

            // 3. GET exito: contiene el enlace al comprobante
            $exito = $http($toBaseUrl($post['location']), 'GET', null, $cookie);
            $detalle[] = 'Éxito:' . $exito['code'];
            $linkOk = preg_match('#href="([^"]*inscripcion/comprobante\?codigo=([A-Za-z0-9-]+))"#', $exito['body'], $ml) === 1;
            $codigoHttp = $linkOk ? $ml[2] : '';
            $enlace = $linkOk ? html_entity_decode($ml[1]) : '';

            // Rastreo directo en BD para garantizar limpieza en caso de fallo parcial
            $stmtH = $this->db->prepare('SELECT id_estudiante FROM estudiantes WHERE codigo_estudiante = "EST-F11-HTTP"');
            $stmtH->execute();
            $estH = $stmtH->fetchColumn();
            if ($estH) {
                $idEstH = (int) $estH;
                $this->createdEstudianteIds[] = $idEstH;
                $stmtI = $this->db->prepare('SELECT id_inscripcion, id_encargado FROM inscripciones WHERE id_estudiante = :e');
                $stmtI->execute([':e' => $idEstH]);
                $insH = $stmtI->fetch(PDO::FETCH_ASSOC);
                if ($insH) {
                    $this->createdInscripcionIds[] = (int) $insH['id_inscripcion'];
                    $this->createdEncargadoIds[] = (int) $insH['id_encargado'];
                    if ($codigoHttp === '') {
                        $codigoHttp = (string) $this->db->query("SELECT codigo_confirmacion FROM inscripciones WHERE id_inscripcion = {$insH['id_inscripcion']}")->fetchColumn();
                    }
                }
            }

            // 4. GET comprobante (HTTP real)
            $urlPdf = $enlace !== '' ? $toBaseUrl($enlace) : "$base/inscripcion/comprobante?codigo=" . urlencode($codigoHttp);
            $pdfRes = $http($urlPdf, 'GET', null, $cookie);
            $detalle[] = 'PDF:' . $pdfRes['code'] . ' ' . $pdfRes['contentType'];
            $body = $pdfRes['body'];
            $okPdf = $pdfRes['code'] === 200
                && stripos($pdfRes['contentType'], 'application/pdf') === 0
                && preg_match('/^Content-Disposition:\s*attachment; filename="comprobante-inscripcion-' . preg_quote($codigoHttp, '/') . '\.pdf"/mi', $pdfRes['headers']) === 1
                && str_starts_with($body, '%PDF-')
                && empty($this->validarEstructuraPdf($body))
                && !preg_match('/(Warning|Notice|Fatal error|Deprecated|Stack trace):/', $this->pdfSinStreams($body));
            $texto = $this->textoPlano($body);
            $okContenido = str_contains($texto, 'Ángela Lucía Peñate Http') && str_contains($texto, $codigoHttp)
                && str_contains($texto, 'Rubén Éder Peñate (PADRE)') && str_contains($texto, 'Sección: F11-A')
                && !str_contains($body, '5555-7777') && !str_contains($body, 'ruben.f11@example.invalid');

            // 5. Consulta pública (RF-14) muestra el botón y la descarga es idéntica en estructura
            $cons = $http("$base/inscripcion/consultar?codigo=" . urlencode($codigoHttp), 'GET', null, $cookie);
            $okCons = $cons['code'] === 200 && str_contains($cons['body'], 'inscripcion/comprobante?codigo=' . $codigoHttp);

            // 6. Casos de error reales: inexistente (404) y código inválido (400), sin PDF
            $r404 = $http("$base/inscripcion/comprobante?codigo=INS-2026-NOEXISTE", 'GET', null, $cookie);
            $r400 = $http("$base/inscripcion/comprobante?codigo=" . urlencode('../../etc/passwd'), 'GET', null, $cookie);
            $rSin = $http("$base/inscripcion/comprobante", 'GET', null, $cookie);
            $rAnu = $http("$base/inscripcion/comprobante?codigo=" . urlencode($this->ins['I4']['codigo']), 'GET', null, $cookie);
            $okErr = $r404['code'] === 404 && $r400['code'] === 400 && $rSin['code'] === 400 && $rAnu['code'] === 409
                && !str_contains($r404['body'], '%PDF-') && !str_contains($r400['body'], '%PDF-') && !str_contains($rAnu['body'], '%PDF-')
                && stripos($r404['contentType'], 'text/html') === 0;
            $detalle[] = sprintf('Err:%d/%d/%d/%d', $r404['code'], $r400['code'], $rSin['code'], $rAnu['code']);

            $ok = $form['code'] === 200 && $token !== '' && $okPost && $exito['code'] === 200 && $linkOk && $okPdf && $okContenido && $okCons && $okErr;
            $detalle[] = sprintf('PostRedir:%s Enlace:%s Pdf:%s Contenido:%s Consulta:%s Errores:%s',
                $okPost ? 'OK' : 'ERR', $linkOk ? 'OK' : 'ERR', $okPdf ? 'OK' : 'ERR', $okContenido ? 'OK' : 'ERR', $okCons ? 'OK' : 'ERR', $okErr ? 'OK' : 'ERR');
        } finally {
            $this->terminateProcess($pid, $process, $pipes);
            @unlink($serverLog);
            if (file_exists($cookie)) {
                @unlink($cookie);
            }
        }

        $this->record('T11-27', 'Flujo HTTP real end-to-end: inscripción -> código -> enlace -> descarga PDF (200, application/pdf, %PDF-) y errores 404/400/409', 'Solicitud HTTP real -> backend -> consulta -> PDF válido con cabeceras correctas',
            implode(' | ', $detalle), $ok);
    }

    // =========================================================================
    // T11-28 .. T11-29: regresiones
    // =========================================================================

    private function testT11_28_RegresionFuncionalRf11Rf12Rf14(): void
    {
        // RF-11: inscripción por formulario (fixture I1 creada por el servicio real) persistida y confirmada.
        $i = $this->inscripcionRepo->findById((int) $this->ins['I1']['id_inscripcion']);
        $rf11 = $i !== null && $i->isConfirmada();
        // RF-12: código único con formato impredecible.
        $rf12 = preg_match('/^INS-\d{4}-[0-9A-F]{8}$/', $this->ins['I1']['codigo']) === 1
            && $this->ins['I1']['codigo'] !== $this->ins['I2']['codigo']
            && $this->inscripcionRepo->existsCodigo($this->ins['I1']['codigo']);
        // RF-14: consulta pública por código sin IDs internos.
        $c = $this->inscripcionService->consultarInscripcionPorCodigo($this->ins['I1']['codigo']);
        $d = $c['data'] ?? [];
        $sinIds = !array_key_exists('id_inscripcion', $d) && !array_key_exists('id_estudiante', $d) && !array_key_exists('id_encargado', $d) && !array_key_exists('encargado_telefono', $d);
        $rf14 = ($c['success'] ?? false) === true && ($d['estudiante_nombre'] ?? '') === 'José Ángel Muñoz Pérez' && $sinIds;
        $inex = $this->inscripcionService->consultarInscripcionPorCodigo('INS-2026-NOEXISTE');
        $rf14b = ($inex['success'] ?? true) === false;

        $this->record('T11-28', 'RF-11, RF-12 y RF-14 continúan funcionando tras integrar RF-13', 'Inscripción confirmada, código único, consulta por código sin IDs',
            sprintf('RF-11: %s | RF-12: %s | RF-14: %s/%s', $rf11 ? 'OK' : 'ERR', $rf12 ? 'OK' : 'ERR', $rf14 ? 'OK' : 'ERR', $rf14b ? 'OK' : 'ERR'), $rf11 && $rf12 && $rf14 && $rf14b);
    }

    private function testT11_29_RegresionFase6(): void
    {
        echo "\n>>> Ejecutando regresión completa de Fase 6 en proceso aislado...\n";
        $cmd = sprintf('"%s" "%s"', PHP_BINARY, dirname(__DIR__) . '/tests/run_tests_fase6.php');
        $output = (string) @shell_exec($cmd);

        $ok = preg_match('/Total ejecutadas\s*:\s*30\s*Exitosas\s*\(OK\)\s*:\s*30\s*Fallidas\s*\(FALLO\)\s*:\s*0/i', $output) === 1 && !str_contains($output, '[FALLO]');
        $this->record('T11-29', 'Regresión completa de Fase 6 (Inscripciones y Cupos: 30/30) en proceso aislado', '30/30 pruebas superadas con 0 fallos',
            $ok ? '30/30 pruebas exitosas de Fase 6' : 'Fallo en regresión de Fase 6: ' . substr($output, -300), $ok);
    }

    // =========================================================================

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
        echo "RESUMEN DE PRUEBAS DE FASE 11: COMPROBANTE DE INSCRIPCIÓN EN PDF (RF-13)\n";
        echo "============================================================\n";
        printf("Total ejecutadas : %d\n", $this->total);
        printf("Exitosas (OK)    : %d\n", $this->passed);
        printf("Fallidas (FALLO) : %d\n", $this->failed);
        echo "============================================================\n";

        if ($this->failed === 0) {
            echo "RESULTADO: TODAS LAS PRUEBAS DE FASE 11 SUPERADAS SATISFACTORIAMENTE (100% GREEN).\n";
        } else {
            echo "RESULTADO: EXISTEN PRUEBAS FALLIDAS.\n";
            exit(1);
        }
    }
}

$suite = new Phase11TestSuite();
$suite->run();
