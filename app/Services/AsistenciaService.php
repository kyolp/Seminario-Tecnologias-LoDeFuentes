<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ConfiguracionUbicacion;
use App\Models\MarcajeAsistencia;
use App\Repositories\AsistenciaRepository;
use App\Repositories\AsistenciaRepositoryInterface;
use App\Repositories\DocenteRepository;
use App\Repositories\DocenteRepositoryInterface;
use App\Repositories\UbicacionRepository;
use App\Repositories\UbicacionRepositoryInterface;
use DomainException;
use InvalidArgumentException;

/**
 * Servicio de lógica de negocio para el control de asistencia y geolocalización.
 * Escuela Oficial Rural Mixta Lo de Fuentes (Fase 8 — RF-19, RF-20, RF-21, RF-22, RF-23).
 *
 * Implementa las decisiones técnicas:
 * - DT-49: Cálculo de distancia autoritativo exclusivamente en backend.
 * - DT-50: Fórmula geodésica Haversine con radio terrestre R = 6,371,000 metros.
 * - DT-51: Fecha y hora autoritativas de servidor.
 * - DT-52: Inmutabilidad de marcajes y restricción dentro_radio = 1.
 * - DT-54: Identidad docente resuelta desde sesión.
 */
class AsistenciaService
{
    public const RADIO_TIERRA_METROS = 6371000.0;

    private AsistenciaRepositoryInterface $asistenciaRepo;
    private UbicacionRepositoryInterface $ubicacionRepo;
    private DocenteRepositoryInterface $docenteRepo;

    public function __construct(
        ?AsistenciaRepositoryInterface $asistenciaRepo = null,
        ?UbicacionRepositoryInterface $ubicacionRepo = null,
        ?DocenteRepositoryInterface $docenteRepo = null
    ) {
        $this->asistenciaRepo = $asistenciaRepo ?? new AsistenciaRepository();
        $this->ubicacionRepo = $ubicacionRepo ?? new UbicacionRepository();
        $this->docenteRepo = $docenteRepo ?? new DocenteRepository();
    }

    /**
     * Calcular la distancia geodésica en metros entre dos coordenadas mediante la fórmula de Haversine (DT-50).
     */
    public function calcularDistanciaHaversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        $sinHalfDeltaLat = sin($deltaLat / 2.0);
        $sinHalfDeltaLon = sin($deltaLon / 2.0);

        $a = ($sinHalfDeltaLat * $sinHalfDeltaLat)
            + cos($lat1Rad) * cos($lat2Rad) * ($sinHalfDeltaLon * $sinHalfDeltaLon);

        // Evitar errores de dominio matemático por imprecisión de punto flotante
        $a = max(0.0, min(1.0, $a));

        $c = 2.0 * atan2(sqrt($a), sqrt(1.0 - $a));

        return self::RADIO_TIERRA_METROS * $c;
    }

    /**
     * Comprobar si una distancia está comprendida dentro del radio permitido (<= radio).
     */
    public function isDentroRadio(float $distanciaMetros, float $radioMetros): bool
    {
        return $distanciaMetros <= $radioMetros;
    }

    /**
     * Registrar marcaje de entrada docente.
     *
     * @param int $idDocente Identificador resuelto desde la sesión autenticada.
     * @param int $idUsuario Identificador del usuario en sesión.
     * @param array<string, mixed> $datosCliente Coordenadas enviadas por el navegador.
     * @return array{
     *     exito: bool,
     *     mensaje: string,
     *     marcaje: MarcajeAsistencia,
     *     distancia_metros: float,
     *     radio_metros: float
     * }
     */
    public function registrarEntrada(int $idDocente, int $idUsuario, array $datosCliente): array
    {
        return $this->procesarMarcaje($idDocente, $idUsuario, 'ENTRADA', $datosCliente);
    }

    /**
     * Registrar marcaje de salida docente.
     *
     * @param int $idDocente Identificador resuelto desde la sesión autenticada.
     * @param int $idUsuario Identificador del usuario en sesión.
     * @param array<string, mixed> $datosCliente Coordenadas enviadas por el navegador.
     * @return array{
     *     exito: bool,
     *     mensaje: string,
     *     marcaje: MarcajeAsistencia,
     *     distancia_metros: float,
     *     radio_metros: float
     * }
     */
    public function registrarSalida(int $idDocente, int $idUsuario, array $datosCliente): array
    {
        return $this->procesarMarcaje($idDocente, $idUsuario, 'SALIDA', $datosCliente);
    }

    /**
     * Procesar y validar un marcaje docente (ENTRADA o SALIDA) con validación geográfica autoritativa.
     */
    private function procesarMarcaje(
        int $idDocente,
        int $idUsuario,
        string $tipo,
        array $datosCliente
    ): array {
        $tipo = strtoupper(trim($tipo));
        if (!in_array($tipo, ['ENTRADA', 'SALIDA'], true)) {
            throw new InvalidArgumentException('Tipo de marcaje no reconocido. Solo se permite ENTRADA o SALIDA.');
        }

        // 1. Verificar estado activo del docente
        $docente = $this->docenteRepo->findById($idDocente);
        if ($docente === null || !$docente->isActivo()) {
            throw new DomainException('No tiene permisos para registrar marcajes de asistencia: su cuenta docente no se encuentra activa.');
        }

        // 2. Obtener la configuración institucional activa
        $config = $this->ubicacionRepo->findConfiguracionActiva();
        if ($config === null) {
            throw new DomainException('No existe una ubicación institucional activa configurada en el sistema.');
        }

        // 3. Sanitizar y validar coordenadas recibidas del cliente
        $coordenadas = $this->validarCoordenadasCliente($datosCliente);

        // 4. Calcular distancia de forma autoritativa exclusivamente en backend (DT-49, DT-50)
        // Se ignoran deliberadamente campos espurios del cliente como 'dentro_radio' o 'distancia'
        $distanciaCalculada = $this->calcularDistanciaHaversine(
            $config->latitud,
            $config->longitud,
            $coordenadas['latitud'],
            $coordenadas['longitud']
        );

        // 5. Validar regla de radio institucional (<= radio_metros)
        if (!$this->isDentroRadio($distanciaCalculada, $config->radio_metros)) {
            $distanciaRedondeada = round($distanciaCalculada, 2);
            $radioRedondeado = round($config->radio_metros, 2);
            throw new DomainException(
                sprintf(
                    'Marcaje denegado: Su ubicación actual se encuentra a %.2f metros de la institución, excediendo el radio autorizado de %.2f metros.',
                    $distanciaRedondeada,
                    $radioRedondeado
                )
            );
        }

        // 6. Validar secuencia lógica de marcajes para prevenir duplicados y secuencias inválidas
        $ultimoMarcaje = $this->asistenciaRepo->findUltimoMarcajeDocente($idDocente);
        $this->validarSecuenciaMarcaje($tipo, $ultimoMarcaje);

        // 7. Persistir marcaje inmutable en base de datos
        $marcaje = $this->asistenciaRepo->crearMarcaje(
            $idDocente,
            $idUsuario,
            $config->id_configuracion_ubicacion,
            $tipo,
            $coordenadas['latitud'],
            $coordenadas['longitud'],
            $coordenadas['precision'],
            $distanciaCalculada
        );

        return [
            'exito' => true,
            'mensaje' => sprintf('Marcaje de %s registrado exitosamente a %.1f m del centro escolar.', strtolower($tipo), $distanciaCalculada),
            'marcaje' => $marcaje,
            'distancia_metros' => $distanciaCalculada,
            'radio_metros' => $config->radio_metros,
        ];
    }

    /**
     * Validar la secuencia lógica de marcaje (ENTRADA -> SALIDA -> ENTRADA).
     */
    private function validarSecuenciaMarcaje(string $tipo, ?MarcajeAsistencia $ultimoMarcaje): void
    {
        if ($tipo === 'ENTRADA') {
            if ($ultimoMarcaje !== null && $ultimoMarcaje->isEntrada()) {
                throw new DomainException('Secuencia inválida: Ya cuenta con un marcaje de entrada registrado pendiente de salida.');
            }
        } elseif ($tipo === 'SALIDA') {
            if ($ultimoMarcaje === null) {
                throw new DomainException('Secuencia inválida: No puede registrar una salida sin haber registrado previamente una entrada.');
            }
            if ($ultimoMarcaje->isSalida()) {
                throw new DomainException('Secuencia inválida: Su último marcaje ya fue una salida. Debe marcar entrada antes de una nueva salida.');
            }
        }
    }

    /**
     * Validar rigurosamente las coordenadas emitidas por el dispositivo.
     *
     * @param array<string, mixed> $datos
     * @return array{latitud: float, longitud: float, precision: ?float}
     */
    private function validarCoordenadasCliente(array $datos): array
    {
        if (!isset($datos['latitud']) || !is_numeric($datos['latitud'])) {
            throw new InvalidArgumentException('Las coordenadas geográficas recibidas son inválidas o incompletas (latitud no numérica).');
        }
        $latitud = (float) $datos['latitud'];
        if ($latitud < -90.0 || $latitud > 90.0 || is_nan($latitud) || is_infinite($latitud)) {
            throw new InvalidArgumentException('La latitud proporcionada está fuera del rango geográfico válido (-90.0 a 90.0).');
        }

        if (!isset($datos['longitud']) || !is_numeric($datos['longitud'])) {
            throw new InvalidArgumentException('Las coordenadas geográficas recibidas son inválidas o incompletas (longitud no numérica).');
        }
        $longitud = (float) $datos['longitud'];
        if ($longitud < -180.0 || $longitud > 180.0 || is_nan($longitud) || is_infinite($longitud)) {
            throw new InvalidArgumentException('La longitud proporcionada está fuera del rango geográfico válido (-180.0 a 180.0).');
        }

        $precision = null;
        if (isset($datos['precision']) && is_numeric($datos['precision'])) {
            $p = (float) $datos['precision'];
            if ($p > 0.0 && !is_nan($p) && !is_infinite($p)) {
                $precision = $p;
            }
        }

        return [
            'latitud' => $latitud,
            'longitud' => $longitud,
            'precision' => $precision,
        ];
    }

    /**
     * Consultar el historial personal de asistencia del docente.
     *
     * @return array<int, MarcajeAsistencia>
     */
    public function obtenerHistorialDocente(int $idDocente, int $limit = 50): array
    {
        return $this->asistenciaRepo->findMarcajesByDocente($idDocente, $limit);
    }

    /**
     * Obtener el estado actual del módulo de asistencia para el docente.
     *
     * @return array{
     *     configuracion: ?ConfiguracionUbicacion,
     *     ultimo_marcaje: ?MarcajeAsistencia,
     *     puede_marcar_entrada: bool,
     *     puede_marcar_salida: bool,
     *     total_marcajes: int
     * }
     */
    public function obtenerEstadoAsistenciaDocente(int $idDocente): array
    {
        $config = $this->ubicacionRepo->findConfiguracionActiva();
        $ultimo = $this->asistenciaRepo->findUltimoMarcajeDocente($idDocente);
        $total = $this->asistenciaRepo->countMarcajesByDocente($idDocente);

        $puedeEntrada = ($config !== null) && ($ultimo === null || $ultimo->isSalida());
        $puedeSalida = ($config !== null) && ($ultimo !== null && $ultimo->isEntrada());

        return [
            'configuracion' => $config,
            'ultimo_marcaje' => $ultimo,
            'puede_marcar_entrada' => $puedeEntrada,
            'puede_marcar_salida' => $puedeSalida,
            'total_marcajes' => $total,
        ];
    }
}
