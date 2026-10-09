<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use Throwable;

/**
 * Servicio de comprobante de inscripción en PDF (Fase 11 — RF-13).
 *
 * Orquesta: validación del identificador público → recuperación autoritativa de datos
 * (mecanismo ya aprobado de RF-14: InscripcionService::consultarInscripcionPorCodigo, que
 * excluye IDs internos y datos sensibles) → verificación de la condición de generación →
 * composición del PDF (PdfService).
 *
 * Condición de generación: la inscripción debe existir y estar CONFIRMADA (el código único de
 * RF-12 se asigna a "cada inscripción aceptada"; una inscripción ANULADA conserva código pero
 * ya no es válida y no genera comprobante).
 *
 * Es de solo lectura: nunca modifica inscripciones ni persiste el documento (se genera al vuelo).
 */
final class ComprobanteInscripcionService
{
    public const ESTADO_REQUERIDO = 'CONFIRMADA';

    private InscripcionService $inscripcionService;
    private PdfService $pdfService;

    public function __construct(?InscripcionService $inscripcionService = null, ?PdfService $pdfService = null)
    {
        $this->inscripcionService = $inscripcionService ?? new InscripcionService();
        $this->pdfService = $pdfService ?? new PdfService();
    }

    /**
     * Valida sintácticamente el código público (alfanumérico y guiones, longitud máxima del esquema = 64).
     * Garantiza además que el código es seguro para usarse en el nombre de archivo y en cabeceras HTTP.
     */
    public static function codigoEsValido(string $codigo): bool
    {
        return preg_match('/^[A-Za-z0-9][A-Za-z0-9-]{3,63}$/D', $codigo) === 1;
    }

    /**
     * @return array{
     *     success: bool,
     *     status: int,
     *     message?: string,
     *     pdf?: string,
     *     filename?: string,
     *     codigo?: string
     * }
     */
    public function generarPorCodigo(string $codigo): array
    {
        $codigo = trim($codigo);

        if (!self::codigoEsValido($codigo)) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'El código de confirmación ingresado no tiene un formato válido.',
            ];
        }

        $consulta = $this->inscripcionService->consultarInscripcionPorCodigo($codigo);
        if (!$consulta['success'] || !isset($consulta['data'])) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'No se encontró ninguna inscripción con el código ingresado.',
            ];
        }

        $datos = $consulta['data'];
        if (strtoupper((string) $datos['estado']) !== self::ESTADO_REQUERIDO) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'El comprobante solo está disponible para inscripciones confirmadas.',
            ];
        }

        try {
            $pdf = $this->pdfService->renderComprobanteInscripcion($datos, date('Y-m-d H:i'));
        } catch (Throwable $e) {
            // Nunca exponer detalles internos al cliente; el detalle queda en el log del servidor.
            Logger::error('Fallo al generar comprobante PDF de inscripción', ['exception' => get_class($e)]);

            return [
                'success' => false,
                'status' => 500,
                'message' => 'No fue posible generar el comprobante en este momento. Intente nuevamente más tarde.',
            ];
        }

        if (!str_starts_with($pdf, '%PDF-') || !str_contains(substr($pdf, -16), '%%EOF')) {
            Logger::error('El generador PDF devolvió un documento no válido');

            return [
                'success' => false,
                'status' => 500,
                'message' => 'No fue posible generar el comprobante en este momento. Intente nuevamente más tarde.',
            ];
        }

        $codigoArchivo = (string) $datos['codigo_confirmacion'];

        return [
            'success' => true,
            'status' => 200,
            'pdf' => $pdf,
            'codigo' => $codigoArchivo,
            'filename' => 'comprobante-inscripcion-' . $codigoArchivo . '.pdf',
        ];
    }
}
