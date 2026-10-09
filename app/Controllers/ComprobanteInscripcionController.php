<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\ComprobanteInscripcionService;

/**
 * Controlador público del comprobante de inscripción en PDF (Fase 11 — RF-13).
 *
 * El cliente solo SOLICITA el comprobante indicando el código público de confirmación (RF-12).
 * El contenido lo determina exclusivamente el servidor. Cualquier otro parámetro enviado por el
 * cliente (ids, nombres, grado, estado, etc.) es ignorado.
 */
class ComprobanteInscripcionController
{
    private ComprobanteInscripcionService $comprobanteService;

    public function __construct(?ComprobanteInscripcionService $comprobanteService = null)
    {
        $this->comprobanteService = $comprobanteService ?? new ComprobanteInscripcionService();
    }

    /**
     * GET /inscripcion/comprobante?codigo=INS-AAAA-XXXXXXXX
     */
    public function descargar(Request $request): Response
    {
        $codigoSolicitado = $request->query('codigo', '');
        $codigoSolicitado = is_string($codigoSolicitado) ? trim($codigoSolicitado) : '';

        $resultado = $this->comprobanteService->generarPorCodigo($codigoSolicitado);

        if ($resultado['success']) {
            $pdf = (string) $resultado['pdf'];

            return new Response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $resultado['filename'] . '"',
                'Content-Length' => (string) strlen($pdf),
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        // Error controlado: nunca se devuelve un PDF falso ni detalles internos.
        $html = View::render('inscripciones.consultar', [
            'title' => 'Comprobante de Inscripción — Escuela Oficial Rural Mixta Lo de Fuentes',
            'codigo' => $codigoSolicitado,
            'resultado' => null,
            'error' => $resultado['message'] ?? 'No fue posible generar el comprobante.',
        ], 'main');

        return new Response($html, (int) $resultado['status'], [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store',
        ]);
    }
}
