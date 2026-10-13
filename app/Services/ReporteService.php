<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DocenteRepository;
use App\Repositories\DocenteRepositoryInterface;
use App\Repositories\GradoRepository;
use App\Repositories\GradoRepositoryInterface;
use App\Repositories\PeriodoRepository;
use App\Repositories\PeriodoRepositoryInterface;
use App\Repositories\ReporteRepository;
use App\Repositories\ReporteRepositoryInterface;
use App\Repositories\SeccionRepository;
use App\Repositories\SeccionRepositoryInterface;
use InvalidArgumentException;

/**
 * Servicio de negocio para la generación y validación de reportes administrativos.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 12 — RF-29 y RF-30).
 */
class ReporteService
{
    private ReporteRepositoryInterface $reporteRepo;
    private DocenteRepositoryInterface $docenteRepo;
    private PeriodoRepositoryInterface $periodoRepo;
    private GradoRepositoryInterface $gradoRepo;
    private SeccionRepositoryInterface $seccionRepo;

    public function __construct(
        ?ReporteRepositoryInterface $reporteRepo = null,
        ?DocenteRepositoryInterface $docenteRepo = null,
        ?PeriodoRepositoryInterface $periodoRepo = null,
        ?GradoRepositoryInterface $gradoRepo = null,
        ?SeccionRepositoryInterface $seccionRepo = null
    ) {
        $this->reporteRepo = $reporteRepo ?? new ReporteRepository();
        $this->docenteRepo = $docenteRepo ?? new DocenteRepository();
        $this->periodoRepo = $periodoRepo ?? new PeriodoRepository();
        $this->gradoRepo = $gradoRepo ?? new GradoRepository();
        $this->seccionRepo = $seccionRepo ?? new SeccionRepository();
    }

    /**
     * Generar reporte de asistencia docente con validación estricta de filtros (RF-29).
     *
     * @param array<string, mixed> $params Parámetros: id_docente, id_periodo_escolar, fecha_inicio, fecha_fin, tipo, orden
     * @return array{filtros: array<string, mixed>, marcajes: array<int, array<string, mixed>>, resumen: array<string, int>}
     */
    public function generarReporteAsistencia(array $params): array
    {
        $filtros = [];

        // 1. Validación de Docente
        if (!empty($params['id_docente'])) {
            $idDocente = (int) $params['id_docente'];
            if ($idDocente <= 0 || $this->docenteRepo->findById($idDocente) === null) {
                throw new InvalidArgumentException('El docente seleccionado no existe en el sistema.');
            }
            $filtros['id_docente'] = $idDocente;
        }

        // 2. Validación de Período Escolar
        $periodo = null;
        if (!empty($params['id_periodo_escolar'])) {
            $idPeriodo = (int) $params['id_periodo_escolar'];
            if ($idPeriodo <= 0) {
                throw new InvalidArgumentException('El identificador del período escolar es inválido.');
            }
            $periodo = $this->periodoRepo->findById($idPeriodo);
            if ($periodo === null) {
                throw new InvalidArgumentException('El período escolar seleccionado no existe en el sistema.');
            }
            $filtros['id_periodo_escolar'] = $idPeriodo;
        }

        // 3. Validación de Fechas
        $fechaInicio = !empty($params['fecha_inicio']) ? trim((string) $params['fecha_inicio']) : null;
        $fechaFin = !empty($params['fecha_fin']) ? trim((string) $params['fecha_fin']) : null;

        // Si se indicó un período escolar y no se especificaron fechas, aplicar el rango del período
        if ($periodo !== null) {
            if ($fechaInicio === null || $fechaInicio === '') {
                $fechaInicio = $periodo->fecha_inicio;
            }
            if ($fechaFin === null || $fechaFin === '') {
                $fechaFin = $periodo->fecha_fin;
            }
        }

        if ($fechaInicio !== null && $fechaInicio !== '') {
            $this->validarFormatoFecha($fechaInicio, 'fecha de inicio');
            $filtros['fecha_inicio'] = $fechaInicio;
        }

        if ($fechaFin !== null && $fechaFin !== '') {
            $this->validarFormatoFecha($fechaFin, 'fecha de fin');
            $filtros['fecha_fin'] = $fechaFin;
        }

        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
            if ($filtros['fecha_fin'] < $filtros['fecha_inicio']) {
                throw new InvalidArgumentException('La fecha final no puede ser anterior a la fecha inicial.');
            }
        }

        // 4. Validación de Tipo de Marcaje (ENTRADA / SALIDA)
        if (!empty($params['tipo'])) {
            $tipo = strtoupper(trim((string) $params['tipo']));
            if (!in_array($tipo, ['ENTRADA', 'SALIDA'], true)) {
                throw new InvalidArgumentException('El tipo de marcaje debe ser ENTRADA o SALIDA.');
            }
            $filtros['tipo'] = $tipo;
        }

        // 5. Ordenamiento seguro
        if (!empty($params['orden'])) {
            $orden = strtoupper(trim((string) $params['orden']));
            if (in_array($orden, ['ASC', 'DESC'], true)) {
                $filtros['orden'] = $orden;
            }
        }

        // Consulta al repositorio
        $marcajes = $this->reporteRepo->findMarcajesAsistencia($filtros);

        // Cálculo de métricas agregadas
        $totalMarcajes = count($marcajes);
        $totalEntradas = 0;
        $totalSalidas = 0;
        $docentesSet = [];

        foreach ($marcajes as $m) {
            if (($m['tipo'] ?? '') === 'ENTRADA') {
                $totalEntradas++;
            } elseif (($m['tipo'] ?? '') === 'SALIDA') {
                $totalSalidas++;
            }
            if (!empty($m['id_docente'])) {
                $docentesSet[(int) $m['id_docente']] = true;
            }
        }

        return [
            'filtros' => $filtros,
            'marcajes' => $marcajes,
            'resumen' => [
                'total_marcajes' => $totalMarcajes,
                'total_entradas' => $totalEntradas,
                'total_salidas' => $totalSalidas,
                'docentes_registrados' => count($docentesSet),
            ],
        ];
    }

    /**
     * Generar reporte y consulta de inscripciones y cupos por sección (RF-30).
     *
     * @param array<string, mixed> $params Parámetros: id_periodo_escolar, id_grado, id_seccion, estado
     * @return array{
     *     filtros: array<string, mixed>,
     *     secciones_cupos: array<int, array<string, mixed>>,
     *     estudiantes: array<int, array<string, mixed>>,
     *     resumen: array<string, int>
     * }
     */
    public function generarReporteInscripciones(array $params): array
    {
        $filtros = [];

        // 1. Validación de Período Escolar
        if (!empty($params['id_periodo_escolar'])) {
            $idPeriodo = (int) $params['id_periodo_escolar'];
            if ($idPeriodo <= 0 || $this->periodoRepo->findById($idPeriodo) === null) {
                throw new InvalidArgumentException('El período escolar seleccionado no existe en el sistema.');
            }
            $filtros['id_periodo_escolar'] = $idPeriodo;
        }

        // 2. Validación de Grado
        if (!empty($params['id_grado'])) {
            $idGrado = (int) $params['id_grado'];
            if ($idGrado <= 0 || $this->gradoRepo->findById($idGrado) === null) {
                throw new InvalidArgumentException('El grado seleccionado no existe en el sistema.');
            }
            $filtros['id_grado'] = $idGrado;
        }

        // 3. Validación de Sección
        if (!empty($params['id_seccion'])) {
            $idSeccion = (int) $params['id_seccion'];
            if ($idSeccion <= 0 || $this->seccionRepo->findById($idSeccion) === null) {
                throw new InvalidArgumentException('La sección seleccionada no existe en el sistema.');
            }
            $filtros['id_seccion'] = $idSeccion;
        }

        // 4. Validación de Estado de Inscripción
        if (!empty($params['estado'])) {
            $estado = strtoupper(trim((string) $params['estado']));
            if (!in_array($estado, ['CONFIRMADA', 'PENDIENTE', 'ANULADA'], true)) {
                throw new InvalidArgumentException('El estado de inscripción seleccionado no es válido.');
            }
            $filtros['estado'] = $estado;
        }

        // Consultas al repositorio
        $seccionesCupos = $this->reporteRepo->findResumenCuposPorSeccion($filtros);
        $estudiantes = $this->reporteRepo->findInscripcionesDetalle($filtros);

        // Métricas de secciones y cupos
        $capacidadTotal = 0;
        $cuposOcupadosTotal = 0;
        $cuposDisponiblesTotal = 0;
        $seccionesLlenas = 0;
        $seccionesDisponibles = 0;

        foreach ($seccionesCupos as $sec) {
            $capacidadTotal += (int) $sec['capacidad'];
            $cuposOcupadosTotal += (int) $sec['cupos_ocupados'];
            $cuposDisponiblesTotal += (int) $sec['cupos_disponibles'];

            if (($sec['estado_cupo'] ?? '') === 'LLENO') {
                $seccionesLlenas++;
            } else {
                $seccionesDisponibles++;
            }
        }

        // Métricas de estudiantes
        $totalConfirmadas = 0;
        $totalPendientes = 0;
        $totalAnuladas = 0;

        foreach ($estudiantes as $est) {
            $st = (string) ($est['inscripcion_estado'] ?? '');
            if ($st === 'CONFIRMADA') {
                $totalConfirmadas++;
            } elseif ($st === 'PENDIENTE') {
                $totalPendientes++;
            } elseif ($st === 'ANULADA') {
                $totalAnuladas++;
            }
        }

        return [
            'filtros' => $filtros,
            'secciones_cupos' => $seccionesCupos,
            'estudiantes' => $estudiantes,
            'resumen' => [
                'total_secciones' => count($seccionesCupos),
                'capacidad_total' => $capacidadTotal,
                'cupos_ocupados_total' => $cuposOcupadosTotal,
                'cupos_disponibles_total' => $cuposDisponiblesTotal,
                'secciones_llenas' => $seccionesLlenas,
                'secciones_disponibles' => $seccionesDisponibles,
                'total_estudiantes_inscritos' => count($estudiantes),
                'total_confirmadas' => $totalConfirmadas,
                'total_pendientes' => $totalPendientes,
                'total_anuladas' => $totalAnuladas,
            ],
        ];
    }

    /**
     * Validar formato de fecha ISO 8601 (YYYY-MM-DD).
     */
    private function validarFormatoFecha(string $fecha, string $nombreCampo): void
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $m)) {
            throw new InvalidArgumentException("El formato de {$nombreCampo} debe ser YYYY-MM-DD.");
        }

        $y = (int) $m[1];
        $mo = (int) $m[2];
        $d = (int) $m[3];

        if (!checkdate($mo, $d, $y)) {
            throw new InvalidArgumentException("La {$nombreCampo} ingresada ({$fecha}) no es una fecha válida en el calendario.");
        }
    }
}
