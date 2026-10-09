<?php

declare(strict_types=1);

namespace App\Services;

use FPDF;
use RuntimeException;

/**
 * Servicio de composición de documentos PDF (Fase 11 — RF-13).
 *
 * Responsabilidad única: transformar un arreglo de datos YA SANEADOS y autoritativos
 * (obtenidos por el backend) en bytes PDF válidos mediante la biblioteca local FPDF.
 *
 * NO accede a la base de datos ni a la petición HTTP. Todo texto dinámico se trata como
 * contenido no confiable: se normaliza a UTF-8 válido, se eliminan caracteres de control y se
 * convierte a Windows-1252 (codificación de las fuentes estándar PDF). FPDF escapa
 * los delimitadores propios del formato PDF, por lo que ningún dato puede inyectar operadores PDF.
 */
class PdfService
{
    public const INSTITUCION = 'Escuela Oficial Rural Mixta Lo de Fuentes';

    public function __construct()
    {
        $fpdf = dirname(__DIR__) . '/Libraries/Fpdf/fpdf.php';
        if (!is_file($fpdf)) {
            throw new RuntimeException('Biblioteca PDF local no disponible.');
        }
        require_once $fpdf;
    }

    /**
     * Compone el comprobante individual de inscripción (RF-13).
     *
     * @param array{
     *     codigo_confirmacion: string,
     *     estudiante_nombre: string,
     *     grado: string,
     *     seccion: string,
     *     periodo: string,
     *     encargado_nombre: string,
     *     parentesco: string,
     *     fecha_inscripcion: string,
     *     fecha_confirmacion: ?string,
     *     estado: string
     * } $datos
     * @param string $emitidoEn Fecha y hora de emisión (servidor), formato 'Y-m-d H:i'.
     * @return string Bytes del documento PDF.
     */
    public function renderComprobanteInscripcion(array $datos, string $emitidoEn): string
    {
        $pdf = new FPDF('P', 'mm', 'A4');
        // Sin compresión: documento de una página, auditable y verificable byte a byte en pruebas.
        $pdf->SetCompression(false);
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->SetTitle('Comprobante de inscripción', true);
        $pdf->SetAuthor(self::INSTITUCION, true);
        $pdf->SetCreator('Sistema Web de Inscripciones y Asistencia', true);
        $pdf->AddPage();

        // Encabezado institucional (sin logotipos ni identidad visual no disponible).
        $pdf->SetFont('Helvetica', 'B', 15);
        $pdf->Cell(0, 8, $this->t(self::INSTITUCION), 0, 1, 'C');
        $pdf->SetFont('Helvetica', 'B', 18);
        $pdf->Ln(2);
        $pdf->Cell(0, 10, $this->t('COMPROBANTE DE INSCRIPCIÓN'), 0, 1, 'C');
        $pdf->SetDrawColor(30, 58, 138);
        $pdf->SetLineWidth(0.6);
        $pdf->Line(20, $pdf->GetY() + 1, 190, $pdf->GetY() + 1);
        $pdf->Ln(8);

        // Código de confirmación (identificador público aprobado en RF-12).
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(0, 5, $this->t('CÓDIGO ÚNICO DE CONFIRMACIÓN'), 0, 1, 'C');
        $pdf->SetTextColor(30, 58, 138);
        $pdf->SetFont('Courier', 'B', 20);
        $pdf->SetDrawColor(148, 163, 184);
        $pdf->SetLineWidth(0.3);
        $pdf->Cell(0, 14, $this->t($datos['codigo_confirmacion']), 1, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(8);

        // Datos de la inscripción.
        $filas = [
            ['Estudiante', $datos['estudiante_nombre']],
            ['Grado', $datos['grado']],
            ['Sección', $datos['seccion']],
            ['Ciclo escolar', $datos['periodo']],
            ['Padre, madre o encargado', trim($datos['encargado_nombre'] . ' (' . $datos['parentesco'] . ')')],
            ['Estado de la inscripción', $datos['estado']],
            ['Fecha de inscripción', $this->fecha($datos['fecha_inscripcion'])],
        ];
        if (!empty($datos['fecha_confirmacion'])) {
            $filas[] = ['Fecha de confirmación', $this->fecha((string) $datos['fecha_confirmacion'])];
        }

        foreach ($filas as [$etiqueta, $valor]) {
            $y = $pdf->GetY();
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor(71, 85, 105);
            $pdf->SetXY(20, $y);
            $pdf->MultiCell(60, 7, $this->t($etiqueta . ':'), 0, 'L');
            $yEtiqueta = $pdf->GetY();

            $pdf->SetFont('Helvetica', '', 11);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->SetXY(80, $y);
            $pdf->MultiCell(110, 7, $this->t($valor), 0, 'L');

            $pdf->SetY(max($yEtiqueta, $pdf->GetY()) + 1.5);
            $pdf->SetDrawColor(226, 232, 240);
            $pdf->SetLineWidth(0.2);
            $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
            $pdf->Ln(2);
        }

        // Nota y fecha de emisión.
        $pdf->Ln(8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->MultiCell(
            0,
            5,
            $this->t(
                'Este comprobante acredita el registro de la inscripción en el sistema de la institución. '
                . 'Conserve el código de confirmación: le permitirá consultar la información de la inscripción '
                . 'en línea en cualquier momento, sin crear una cuenta.'
            ),
            0,
            'L'
        );
        $pdf->Ln(3);
        $pdf->SetFont('Helvetica', 'I', 9);
        $pdf->Cell(0, 5, $this->t('Fecha de emisión: ' . $emitidoEn), 0, 1, 'L');

        return $pdf->Output('S');
    }

    /**
     * Normaliza un texto dinámico para fuentes estándar PDF (Windows-1252).
     * Neutraliza caracteres de control y UTF-8 inválido; los símbolos fuera de Windows-1252 se sustituyen por "?".
     */
    public function t(string $texto): string
    {
        $utf8 = mb_convert_encoding($texto, 'UTF-8', 'UTF-8'); // descarta secuencias UTF-8 inválidas
        $utf8 = (string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $utf8);
        $utf8 = trim((string) preg_replace('/\s+/u', ' ', $utf8));

        return mb_convert_encoding($utf8, 'Windows-1252', 'UTF-8');
    }

    /**
     * Convierte 'YYYY-MM-DD' a 'DD/MM/YYYY'; devuelve el texto original si no coincide el formato.
     */
    private function fecha(string $fecha): string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $fecha, $m) === 1) {
            return $m[3] . '/' . $m[2] . '/' . $m[1];
        }

        return $fecha;
    }
}
