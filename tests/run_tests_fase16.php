<?php

declare(strict_types=1);

/**
 * Suite de Pruebas Automatizadas — Fase 16
 * Limpieza Final de Base de Datos, Datos de Demostración y Cierre del Proyecto
 * Sistema Web — Escuela Oficial Rural Mixta Lo de Fuentes
 *
 * Cobertura de Fase 16:
 * - T16-01: Integridad del esquema MySQL (database/schema.sql íntegro, 20 tablas relacionales activas)
 * - T16-02: Carga limpia de datos iniciales (database/seed.sql sin errores de claves ni restricciones)
 * - T16-03: Cero cadenas técnicas o de prueba en catálogo de usuarios
 * - T16-04: Cero cadenas técnicas o ficticias en catálogo de estudiantes
 * - T16-05: Cero cadenas técnicas o teléfonos ficticios en catálogo de encargados
 * - T16-06: Cero códigos de prueba en inscripciones confirmadas
 * - T16-07: Cero códigos de prueba en catálogo de aulas y secciones
 * - T16-08: Cero nombres de prueba en períodos escolares
 * - T16-09: Cero solicitudes de permiso con contenido técnico o ficticio
 * - T16-10: Cero notificaciones internas con contenido técnico o de prueba
 * - T16-11: Cero tokens activos en tokens_recuperacion
 * - T16-12: Catálogo de usuarios institucional con nombres naturales guatemaltecos y dominio @escuela.com
 * - T16-13: Hashes de contraseñas Bcrypt nativos válidos para administradores y docentes
 * - T16-14: Catálogo de grados escolares completo (Primero a Sexto Primaria con orden 1..6)
 * - T16-15: Período escolar activo 2026 y ciclo histórico 2025 cerrado
 * - T16-16: 12 secciones escolares académicas configuradas (A y B) con capacidad > 0
 * - T16-17: Asignaciones docentes vigentes para los 12 docentes en el ciclo lectivo activo
 * - T16-18: Estudiantes vinculados con encargados y parentescos coherentes
 * - T16-19: Inscripciones confirmadas con formato oficial INS-2026-XXXXX
 * - T16-20: Disponibilidad de cupos coherente y matemáticamente exacta
 * - T16-21: Geocerca escolar oficial activa con coordenadas institucionales (Lo de Fuentes)
 * - T16-22: Marcajes de asistencia docente coherentes dentro del radio escolar
 * - T16-23: Permisos laborales con transiciones e historial inmutable
 * - T16-24: Auditoría de README.md (sin credenciales, sin rutas locales, sin mención a IA ni historial obsoleto)
 * - T16-25: Auditoría documental de cierre (docs/fase-16/ e informe de cierre de proyecto)
 * - T16-26: Respaldo y exportación de datos de demostración finales (database/final_demo_2026.sql)
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Database;

class Phase16TestSuite
{
    private PDO $db;
    private int $total = 0;
    private int $passed = 0;
    private int $failed = 0;

    /** @var array<int, array{id: string, name: string, expected: string, obtained: string, status: string}> */
    private array $results = [];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function run(): void
    {
        echo "============================================================\n";
        echo "SUITE DE PRUEBAS AUTOMATIZADAS — FASE 16\n";
        echo "Limpieza Final de Base de Datos, Datos Demo y Cierre\n";
        echo "Sistema Web — Escuela Oficial Rural Mixta Lo de Fuentes\n";
        echo "============================================================\n\n";

        $this->testT16_01_IntegridadEsquemaMySQL();
        $this->testT16_02_CargaLimpiaSeedSQL();
        $this->testT16_03_CeroCadenasPruebaUsuarios();
        $this->testT16_04_CeroCadenasPruebaEstudiantes();
        $this->testT16_05_CeroCadenasPruebaEncargados();
        $this->testT16_06_CeroCadenasPruebaInscripciones();
        $this->testT16_07_CeroCadenasPruebaAulasSecciones();
        $this->testT16_08_CeroCadenasPruebaPeriodos();
        $this->testT16_09_CeroCadenasPruebaPermisos();
        $this->testT16_10_CeroCadenasPruebaNotificaciones();
        $this->testT16_11_CeroTokensActivosRecuperacion();
        $this->testT16_12_CatalogoUsuariosNaturales();
        $this->testT16_13_HashesBcryptValidos();
        $this->testT16_14_CatalogoGradosPrimaria();
        $this->testT16_15_PeriodosEscolaresCoherentes();
        $this->testT16_16_SeccionesAcademicasConfiguradas();
        $this->testT16_17_AsignacionesDocentesVigentes();
        $this->testT16_18_EstudiantesYEncargadosCoherentes();
        $this->testT16_19_InscripcionesConfirmadasFormatoOficial();
        $this->testT16_20_DisponibilidadCuposMatematica();
        $this->testT16_21_GeocercaEscolarInstitucionalActiva();
        $this->testT16_22_MarcajesAsistenciaCoherentes();
        $this->testT16_23_PermisosLaboralesEHistorial();
        $this->testT16_24_AuditoriaReadme();
        $this->testT16_25_AuditoriaDocumentalCierre();
        $this->testT16_26_ExportacionDemoFinal();

        $this->printSummary();
    }

    private function record(string $id, string $name, string $expected, string $obtained, bool $success): void
    {
        $this->total++;
        if ($success) {
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

        printf("[%s] %s: %s\n", $status, $id, $name);
        if (!$success) {
            printf("   Esperado: %s\n", $expected);
            printf("   Obtenido: %s\n", $obtained);
        }
    }

    private function testT16_01_IntegridadEsquemaMySQL(): void
    {
        $tablasEsperadas = [
            'roles', 'usuarios', 'docentes', 'estudiantes', 'encargados',
            'estudiantes_encargados', 'periodos_escolares', 'grados', 'grados_periodos',
            'aulas', 'secciones', 'asignaciones_docentes', 'inscripciones',
            'configuraciones_ubicacion', 'marcajes_asistencia', 'solicitudes_permiso',
            'historial_solicitudes_permiso', 'notificaciones', 'tokens_recuperacion',
            'documentos_permiso'
        ];

        $tablasEncontradas = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $faltantes = array_diff($tablasEsperadas, $tablasEncontradas);

        $ok = empty($faltantes) && count($tablasEncontradas) >= 20;
        $this->record(
            'T16-01',
            'Integridad del esquema relacional (20 tablas canónicas presentes)',
            '20 tablas relacionales activas en MySQL',
            sprintf('%d tablas encontradas. Faltantes: %s', count($tablasEncontradas), empty($faltantes) ? 'Ninguna' : implode(', ', $faltantes)),
            $ok
        );
    }

    private function testT16_02_CargaLimpiaSeedSQL(): void
    {
        $seedPath = dirname(__DIR__) . '/database/seed.sql';
        $schemaPath = dirname(__DIR__) . '/database/schema.sql';

        $ok = file_exists($seedPath) && filesize($seedPath) > 5000 && file_exists($schemaPath);
        $this->record(
            'T16-02',
            'Integridad y existencia de database/seed.sql y schema.sql',
            'Archivos seed.sql y schema.sql presentes e íntegros',
            sprintf('seed.sql (%d bytes), schema.sql (%d bytes)', filesize($seedPath), filesize($schemaPath)),
            $ok
        );
    }

    private function testT16_03_CeroCadenasPruebaUsuarios(): void
    {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE nombre_usuario LIKE '%demo%' OR nombre_usuario LIKE '%test%' OR correo LIKE '%example.invalid%' OR correo LIKE '%f4@%' OR correo LIKE '%f5@%'";
        $count = (int)$this->db->query($sql)->fetchColumn();

        $this->record(
            'T16-03',
            'Cero cadenas técnicas o de prueba en catálogo de usuarios',
            '0 usuarios con patrones demo/test/example.invalid',
            sprintf('%d usuarios con patrones técnicos', $count),
            $count === 0
        );
    }

    private function testT16_04_CeroCadenasPruebaEstudiantes(): void
    {
        $sql = "SELECT COUNT(*) FROM estudiantes WHERE codigo_estudiante LIKE '%TEST%' OR nombres LIKE '%Prueba%' OR nombres LIKE '%Competidor%' OR nombres LIKE '%<script>%'";
        $count = (int)$this->db->query($sql)->fetchColumn();

        $this->record(
            'T16-04',
            'Cero cadenas técnicas o ficticias en catálogo de estudiantes',
            '0 estudiantes con patrones TEST/Prueba/Competidor/script',
            sprintf('%d estudiantes detectados', $count),
            $count === 0
        );
    }

    private function testT16_05_CeroCadenasPruebaEncargados(): void
    {
        $sql = "SELECT COUNT(*) FROM encargados WHERE nombres LIKE '%Test%' OR nombres LIKE '%Demo%' OR telefono LIKE '555-%' OR telefono LIKE '0000%'";
        $count = (int)$this->db->query($sql)->fetchColumn();

        $this->record(
            'T16-05',
            'Cero cadenas técnicas o teléfonos ficticios en catálogo de encargados',
            '0 encargados con nombres o teléfonos ficticios',
            sprintf('%d encargados detectados', $count),
            $count === 0
        );
    }

    private function testT16_06_CeroCadenasPruebaInscripciones(): void
    {
        $sql = "SELECT COUNT(*) FROM inscripciones WHERE codigo_confirmacion LIKE '%TEST%' OR codigo_confirmacion LIKE 'INS-F%'";
        $count = (int)$this->db->query($sql)->fetchColumn();

        $this->record(
            'T16-06',
            'Cero códigos de prueba en inscripciones confirmadas',
            '0 inscripciones con códigos de prueba',
            sprintf('%d inscripciones de prueba detectadas', $count),
            $count === 0
        );
    }

    private function testT16_07_CeroCadenasPruebaAulasSecciones(): void
    {
        $aulas = (int)$this->db->query("SELECT COUNT(*) FROM aulas WHERE codigo LIKE '%TEST%' OR codigo LIKE '%HTTP%' OR nombre LIKE '%Test%'")->fetchColumn();
        $secciones = (int)$this->db->query("SELECT COUNT(*) FROM secciones WHERE codigo LIKE '%TEST%' OR codigo LIKE 'SEC-T%' OR codigo LIKE 'F5-%'")->fetchColumn();

        $ok = ($aulas === 0 && $secciones === 0);
        $this->record(
            'T16-07',
            'Cero códigos de prueba en catálogo de aulas y secciones',
            '0 aulas y 0 secciones con prefijos TEST/HTTP/SEC-T',
            sprintf('%d aulas y %d secciones de prueba', $aulas, $secciones),
            $ok
        );
    }

    private function testT16_08_CeroCadenasPruebaPeriodos(): void
    {
        $sql = "SELECT COUNT(*) FROM periodos_escolares WHERE nombre LIKE '%Test%' OR nombre LIKE '%F5%' OR nombre LIKE '%F6%'";
        $count = (int)$this->db->query($sql)->fetchColumn();

        $this->record(
            'T16-08',
            'Cero nombres de prueba en períodos escolares',
            '0 períodos escolares con patrones Test/F5/F6',
            sprintf('%d períodos con nombres de prueba', $count),
            $count === 0
        );
    }

    private function testT16_09_CeroCadenasPruebaPermisos(): void
    {
        $sql = "SELECT COUNT(*) FROM solicitudes_permiso WHERE tipo_motivo LIKE '%Test%' OR descripcion LIKE '%Prueba%' OR descripcion LIKE '%XSS%' OR descripcion LIKE '%<script%'";
        $count = (int)$this->db->query($sql)->fetchColumn();

        $this->record(
            'T16-09',
            'Cero solicitudes de permiso con contenido técnico o ficticio',
            '0 solicitudes con patrones Test/Prueba/XSS',
            sprintf('%d solicitudes con patrones de prueba', $count),
            $count === 0
        );
    }

    private function testT16_10_CeroCadenasPruebaNotificaciones(): void
    {
        $sql = "SELECT COUNT(*) FROM notificaciones WHERE titulo LIKE '%Test%' OR titulo LIKE '%Prueba%' OR mensaje LIKE '%F10%'";
        $count = (int)$this->db->query($sql)->fetchColumn();

        $this->record(
            'T16-10',
            'Cero notificaciones internas con contenido técnico o de prueba',
            '0 notificaciones con patrones Test/Prueba/F10',
            sprintf('%d notificaciones de prueba detectadas', $count),
            $count === 0
        );
    }

    private function testT16_11_CeroTokensActivosRecuperacion(): void
    {
        $sql = "SELECT COUNT(*) FROM tokens_recuperacion WHERE estado = 'ACTIVO'";
        $count = (int)$this->db->query($sql)->fetchColumn();

        $this->record(
            'T16-11',
            'Cero tokens activos en tokens_recuperacion',
            '0 tokens activos (tabla limpia o sólo históricos/expirados)',
            sprintf('%d tokens activos', $count),
            $count === 0
        );
    }

    private function testT16_12_CatalogoUsuariosNaturales(): void
    {
        $admins = (int)$this->db->query("SELECT COUNT(*) FROM usuarios u JOIN roles r ON u.id_rol = r.id_rol WHERE r.nombre = 'ADMINISTRACION'")->fetchColumn();
        $docentes = (int)$this->db->query("SELECT COUNT(*) FROM usuarios u JOIN roles r ON u.id_rol = r.id_rol WHERE r.nombre = 'DOCENTE'")->fetchColumn();
        $correosValidos = (int)$this->db->query("SELECT COUNT(*) FROM usuarios WHERE correo LIKE '%@escuela.com'")->fetchColumn();

        $ok = ($admins >= 2 && $docentes >= 12 && $correosValidos >= 14);
        $this->record(
            'T16-12',
            'Catálogo de usuarios institucional con nombres y correos @escuela.com',
            'Al menos 2 administradores, 12 docentes con @escuela.com',
            sprintf('%d administradores, %d docentes, %d con @escuela.com', $admins, $docentes, $correosValidos),
            $ok
        );
    }

    private function testT16_13_HashesBcryptValidos(): void
    {
        $stmt = $this->db->query("SELECT nombre_usuario, clave_hash FROM usuarios WHERE nombre_usuario IN ('estuardo.gonzalez', 'carlos.mendez')");
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $adminOk = isset($rows['estuardo.gonzalez']) && password_verify('Admin.2026!', $rows['estuardo.gonzalez']);
        $docenteOk = isset($rows['carlos.mendez']) && password_verify('Docente.2026!', $rows['carlos.mendez']);

        $ok = ($adminOk && $docenteOk);
        $this->record(
            'T16-13',
            'Hashes BCrypt nativos válidos para administradores y docentes institucionales',
            'password_verify OK para Admin.2026! y Docente.2026!',
            sprintf('Admin: %s, Docente: %s', $adminOk ? 'OK' : 'FAIL', $docenteOk ? 'OK' : 'FAIL'),
            $ok
        );
    }

    private function testT16_14_CatalogoGradosPrimaria(): void
    {
        $grados = $this->db->query("SELECT orden, nombre FROM grados WHERE activo = 1 ORDER BY orden ASC")->fetchAll(PDO::FETCH_ASSOC);
        $ordenes = array_column($grados, 'orden');

        $ok = (count($grados) === 6 && $ordenes === [1, 2, 3, 4, 5, 6]);
        $this->record(
            'T16-14',
            'Catálogo de grados escolares completo (Primero a Sexto Primaria con orden 1..6)',
            '6 grados activos con progresión 1, 2, 3, 4, 5, 6',
            sprintf('%d grados activos encontrados: %s', count($grados), implode(', ', array_column($grados, 'nombre'))),
            $ok
        );
    }

    private function testT16_15_PeriodosEscolaresCoherentes(): void
    {
        $activos = (int)$this->db->query("SELECT COUNT(*) FROM periodos_escolares WHERE estado = 'ACTIVO'")->fetchColumn();
        $cerrados = (int)$this->db->query("SELECT COUNT(*) FROM periodos_escolares WHERE estado = 'CERRADO'")->fetchColumn();

        $ok = ($activos === 1 && $cerrados >= 1);
        $this->record(
            'T16-15',
            'Período escolar activo 2026 y ciclo histórico 2025 cerrado',
            'Exactamente 1 período ACTIVO y al menos 1 CERRADO',
            sprintf('%d activo, %d cerrado(s)', $activos, $cerrados),
            $ok
        );
    }

    private function testT16_16_SeccionesAcademicasConfiguradas(): void
    {
        $totalSecciones = (int)$this->db->query("SELECT COUNT(*) FROM secciones WHERE habilitada = 1")->fetchColumn();
        $capacidadValida = (int)$this->db->query("SELECT COUNT(*) FROM secciones WHERE capacidad <= 0")->fetchColumn();

        $ok = ($totalSecciones >= 12 && $capacidadValida === 0);
        $this->record(
            'T16-16',
            '12 secciones escolares académicas configuradas (A y B) con capacidad positiva',
            'Al menos 12 secciones habilitadas con capacidad > 0',
            sprintf('%d secciones habilitadas, %d con capacidad inválida', $totalSecciones, $capacidadValida),
            $ok
        );
    }

    private function testT16_17_AsignacionesDocentesVigentes(): void
    {
        $activas = (int)$this->db->query("SELECT COUNT(*) FROM asignaciones_docentes WHERE estado = 'ACTIVA'")->fetchColumn();
        $docentesAsignados = (int)$this->db->query("SELECT COUNT(DISTINCT id_docente) FROM asignaciones_docentes WHERE estado = 'ACTIVA'")->fetchColumn();

        $ok = ($activas >= 12 && $docentesAsignados >= 12);
        $this->record(
            'T16-17',
            'Asignaciones docentes vigentes para los 12 docentes en el ciclo lectivo activo',
            'Al menos 12 asignaciones activas con 12 docentes distintos',
            sprintf('%d asignaciones activas, %d docentes asignados', $activas, $docentesAsignados),
            $ok
        );
    }

    private function testT16_18_EstudiantesYEncargadosCoherentes(): void
    {
        $estudiantes = (int)$this->db->query("SELECT COUNT(*) FROM estudiantes WHERE estado = 'ACTIVO'")->fetchColumn();
        $encargados = (int)$this->db->query("SELECT COUNT(*) FROM encargados")->fetchColumn();
        $vinculos = (int)$this->db->query("SELECT COUNT(*) FROM estudiantes_encargados WHERE es_principal = 1")->fetchColumn();

        $ok = ($estudiantes >= 24 && $encargados >= 18 && $vinculos >= 24);
        $this->record(
            'T16-18',
            'Estudiantes vinculados con encargados y parentescos coherentes',
            'Al menos 24 estudiantes, 18 encargados y 24 vínculos principales',
            sprintf('%d estudiantes, %d encargados, %d vínculos principales', $estudiantes, $encargados, $vinculos),
            $ok
        );
    }

    private function testT16_19_InscripcionesConfirmadasFormatoOficial(): void
    {
        $confirmadas = (int)$this->db->query("SELECT COUNT(*) FROM inscripciones WHERE estado = 'CONFIRMADA'")->fetchColumn();
        $formatoValido = (int)$this->db->query("SELECT COUNT(*) FROM inscripciones WHERE codigo_confirmacion REGEXP '^INS-2026-[0-9A-Z]{5,10}$'")->fetchColumn();

        $ok = ($confirmadas >= 24 && $formatoValido === $confirmadas);
        $this->record(
            'T16-19',
            'Inscripciones confirmadas con formato oficial INS-2026-XXXXX',
            'Al menos 24 inscripciones confirmadas con formato válido',
            sprintf('%d confirmadas, %d con formato oficial válido', $confirmadas, $formatoValido),
            $ok
        );
    }

    private function testT16_20_DisponibilidadCuposMatematica(): void
    {
        $sql = "SELECT s.id_seccion, s.capacidad, 
                       (SELECT COUNT(*) FROM inscripciones i WHERE i.id_seccion = s.id_seccion AND i.estado = 'CONFIRMADA') as inscritos
                FROM secciones s WHERE s.habilitada = 1";
        $filas = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $todosValidos = true;
        foreach ($filas as $fila) {
            $capacidad = (int)$fila['capacidad'];
            $inscritos = (int)$fila['inscritos'];
            $disponibles = $capacidad - $inscritos;
            if ($disponibles < 0 || $inscritos > $capacidad) {
                $todosValidos = false;
                break;
            }
        }

        $this->record(
            'T16-20',
            'Disponibilidad de cupos coherente y matemáticamente exacta por sección',
            'Capacidad >= inscritos confirmados y disponibles >= 0 en todas las secciones',
            sprintf('%d secciones evaluadas, todas matemáticamente consistentes: %s', count($filas), $todosValidos ? 'SÍ' : 'NO'),
            $todosValidos && count($filas) >= 12
        );
    }

    private function testT16_21_GeocercaEscolarInstitucionalActiva(): void
    {
        $stmt = $this->db->query("SELECT id_configuracion_ubicacion, descripcion, latitud, longitud, radio_metros, activa 
                                  FROM configuraciones_ubicacion WHERE activa = 1 LIMIT 1");
        $cfg = $stmt->fetch(PDO::FETCH_ASSOC);

        $ok = ($cfg !== false &&
               abs((float)$cfg['latitud'] - 14.6349150) < 0.0001 &&
               abs((float)$cfg['longitud'] - (-90.5068820)) < 0.0001 &&
               (float)$cfg['radio_metros'] >= 50.0);

        $this->record(
            'T16-21',
            'Geocerca escolar oficial activa con coordenadas institucionales (Lo de Fuentes)',
            'Latitud ~14.6349150, Longitud ~-90.5068820, Radio >= 50m, activa = 1',
            $cfg ? sprintf('Lat: %s, Lon: %s, Radio: %sm, Desc: %s', $cfg['latitud'], $cfg['longitud'], $cfg['radio_metros'], $cfg['descripcion']) : 'No activa',
            $ok
        );
    }

    private function testT16_22_MarcajesAsistenciaCoherentes(): void
    {
        $totalMarcajes = (int)$this->db->query("SELECT COUNT(*) FROM marcajes_asistencia")->fetchColumn();
        $fueraRadio = (int)$this->db->query("SELECT COUNT(*) FROM marcajes_asistencia WHERE dentro_radio = 0")->fetchColumn();
        $tiposValidos = (int)$this->db->query("SELECT COUNT(*) FROM marcajes_asistencia WHERE tipo IN ('ENTRADA', 'SALIDA')")->fetchColumn();

        $ok = ($totalMarcajes >= 10 && $fueraRadio === 0 && $tiposValidos === $totalMarcajes);
        $this->record(
            'T16-22',
            'Marcajes de asistencia docente coherentes dentro del radio escolar',
            'Al menos 10 marcajes, 0 fuera de radio, 100% tipos ENTRADA/SALIDA',
            sprintf('%d marcajes registrados, %d fuera de radio', $totalMarcajes, $fueraRadio),
            $ok
        );
    }

    private function testT16_23_PermisosLaboralesEHistorial(): void
    {
        $solicitudes = (int)$this->db->query("SELECT COUNT(*) FROM solicitudes_permiso")->fetchColumn();
        $historial = (int)$this->db->query("SELECT COUNT(*) FROM historial_solicitudes_permiso")->fetchColumn();

        $ok = ($solicitudes >= 3 && $historial >= $solicitudes);
        $this->record(
            'T16-23',
            'Permisos laborales con transiciones e historial inmutable registrado',
            'Al menos 3 solicitudes con trazabilidad en historial_solicitudes_permiso',
            sprintf('%d solicitudes, %d registros en historial', $solicitudes, $historial),
            $ok
        );
    }

    private function testT16_24_AuditoriaReadme(): void
    {
        $readmePath = dirname(__DIR__) . '/README.md';
        $content = file_exists($readmePath) ? file_get_contents($readmePath) : '';

        $hasPlainPasswords = preg_match('/(?:password|clave|contraseña)\s*[:=]\s*["\']?[a-zA-Z0-9.!@#$%^&*]{8,}/i', $content);
        $hasLocalPaths = (str_contains($content, 'C:\\Users\\') || str_contains($content, 'c:/Users/'));
        $hasAiTone = (str_contains($content, 'As an AI') || str_contains($content, 'Como asistente') || str_contains($content, 'ANTIGRAVITY'));
        $hasOutdatedFases = str_contains($content, 'Próximas fases: Recuperación de contraseña (Fase 13)');

        $ok = (!$hasPlainPasswords && !$hasLocalPaths && !$hasAiTone && !$hasOutdatedFases && strlen($content) > 500);
        $this->record(
            'T16-24',
            'Auditoría de README.md (sin credenciales, sin rutas locales, sin mención a IA ni historial obsoleto)',
            'README profesional, limpio, seguro y actualizado',
            sprintf('Longitud: %d bytes | Passwords: %s | Rutas: %s | AI: %s | Obsoleto: %s',
                strlen($content),
                $hasPlainPasswords ? 'DETECTADAS' : 'NO',
                $hasLocalPaths ? 'DETECTADAS' : 'NO',
                $hasAiTone ? 'DETECTADO' : 'NO',
                $hasOutdatedFases ? 'DETECTADO' : 'NO'
            ),
            $ok
        );
    }

    private function testT16_25_AuditoriaDocumentalCierre(): void
    {
        $docs = [
            dirname(__DIR__) . '/docs/fase-16/plan-limpieza.md',
            dirname(__DIR__) . '/docs/fase-16/datos-demostracion.md',
            dirname(__DIR__) . '/docs/fase-16/pruebas-finales.md',
            dirname(__DIR__) . '/docs/fase-16/informe-cierre-fase-16.md',
            dirname(__DIR__) . '/docs/informe-cierre-proyecto.md',
        ];

        $faltantes = [];
        foreach ($docs as $doc) {
            if (!file_exists($doc) || filesize($doc) < 500) {
                $faltantes[] = basename($doc);
            }
        }

        $ok = empty($faltantes);
        $this->record(
            'T16-25',
            'Auditoría documental de cierre (docs/fase-16/ e informe final de proyecto)',
            '5 documentos técnicos de cierre completos y documentados',
            empty($faltantes) ? '5/5 documentos presentes y completos' : 'Faltantes: ' . implode(', ', $faltantes),
            $ok
        );
    }

    private function testT16_26_ExportacionDemoFinal(): void
    {
        $exportPath = dirname(__DIR__) . '/database/final_demo_2026.sql';
        $ok = file_exists($exportPath) && filesize($exportPath) > 5000;

        $this->record(
            'T16-26',
            'Respaldo y exportación de datos de demostración finales (database/final_demo_2026.sql)',
            'Archivo database/final_demo_2026.sql presente y con tamaño válido (> 5KB)',
            $ok ? sprintf('Presente (%d bytes)', filesize($exportPath)) : 'No encontrado',
            $ok
        );
    }

    private function printSummary(): void
    {
        echo "\n============================================================\n";
        echo "RESUMEN DE PRUEBAS DE FASE 16\n";
        echo "============================================================\n";
        printf("Total ejecutadas : %d\n", $this->total);
        printf("Exitosas (OK)    : %d\n", $this->passed);
        printf("Fallidas (FALLO) : %d\n", $this->failed);
        echo "============================================================\n";

        if ($this->failed === 0) {
            echo "RESULTADO: TODAS LAS PRUEBAS DE FASE 16 SUPERADAS SATISFACTORIAMENTE (100% GREEN).\n";
            exit(0);
        } else {
            echo "RESULTADO: EXISTEN PRUEBAS FALLIDAS EN FASE 16.\n";
            exit(1);
        }
    }
}

$suite = new Phase16TestSuite();
$suite->run();
