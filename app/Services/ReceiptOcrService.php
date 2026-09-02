<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ReceiptOcrService
{
    /**
     * Extrae texto de la imagen dada usando Tesseract OCR / Google Vision y estructura los datos bancarios.
     *
     * @param string $imagePath Ruta absoluta del archivo de imagen o PDF
     * @return array
     */
    public static function processImage(string $imagePath): array
    {
        $rawText = self::runTesseract($imagePath);

        // Si Tesseract no devolvió suficiente texto, intentar con Google Vision si está disponible
        if (strlen(trim($rawText)) < 15) {
            $rawText = self::runGoogleVisionFallback($imagePath) ?: $rawText;
        }

        return self::parseReceiptText($rawText);
    }

    /**
     * Ejecuta Tesseract OCR mediante CLI.
     */
    protected static function runTesseract(string $imagePath): string
    {
        if (!file_exists($imagePath)) {
            return '';
        }

        try {
            $process = new Process(['tesseract', $imagePath, 'stdout', '--oem', '1']);
            $process->setTimeout(15);
            $process->run();

            if ($process->isSuccessful()) {
                return $process->getOutput();
            }

            Log::warning("Tesseract process error: " . $process->getErrorOutput());
        } catch (\Throwable $e) {
            Log::error("Excepción al ejecutar Tesseract OCR: " . $e->getMessage());
        }

        return '';
    }

    /**
     * Fallback usando Google Vision API si las credenciales existen.
     */
    protected static function runGoogleVisionFallback(string $imagePath): string
    {
        try {
            if (class_exists('\Google\Cloud\Vision\V1\ImageAnnotatorClient') &&
                env('GOOGLE_APPLICATION_CREDENTIALS') &&
                file_exists(base_path(env('GOOGLE_APPLICATION_CREDENTIALS')))) {

                $imageAnnotator = new \Google\Cloud\Vision\V1\ImageAnnotatorClient();
                $imageContent = file_get_contents($imagePath);
                $response = $imageAnnotator->textDetection($imageContent);
                $texts = $response->getTextAnnotations();
                $imageAnnotator->close();

                if (count($texts) > 0) {
                    return $texts[0]->getDescription();
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Google Vision fallback not available: " . $e->getMessage());
        }

        return '';
    }

    /**
     * Parsea el texto extraído buscando datos clave de comprobantes bancarios mexicanos (SPEI, BBVA, Banamex, etc.).
     */
    public static function parseReceiptText(string $text): array
    {
        $normalizedText = mb_strtolower($text, 'UTF-8');

        // 1. Detectar Banco
        $banco = null;
        $bancosMap = [
            'bbva' => 'BBVA',
            'banamex' => 'Citibanamex',
            'citibanamex' => 'Citibanamex',
            'santander' => 'Santander',
            'banorte' => 'Banorte',
            'stp' => 'STP',
            'mercado pago' => 'Mercado Pago',
            'mercadopago' => 'Mercado Pago',
            'nu' => 'Nu México',
            'stori' => 'Stori',
            'hsbc' => 'HSBC',
            'scotiabank' => 'Scotiabank',
            'banco azteca' => 'Banco Azteca',
            'azteca' => 'Banco Azteca',
            'banregio' => 'Banregio',
            'inbursa' => 'Inbursa',
            'spin' => 'Spin by OXXO',
        ];

        foreach ($bancosMap as $key => $name) {
            if (str_contains($normalizedText, $key)) {
                $banco = $name;
                break;
            }
        }

        // 2. Detectar Clave de Rastreo / Folio / Referencia
        $claveRastreo = null;
        $patternsClave = [
            '/(?:clave\s*de\s*rastreo|folio\s*(?:de\s*operaci[oó]n)?|referencia|n[uú]m\.?\s*de\s*operaci[oó]n|autorizaci[oó]n)[:\s]*([a-z0-9]{6,35})/i',
            '/\b([0-9]{20,30})\b/', // Típica clave SPEI de 20-30 dígitos
            '/\b([A-Z0-9]{10,25})\b/'
        ];

        foreach ($patternsClave as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $candidate = trim($matches[1]);
                if (strlen($candidate) >= 6 && !in_array(strtolower($candidate), ['transferencia', 'exitoso', 'comprobante'])) {
                    $claveRastreo = strtoupper($candidate);
                    break;
                }
            }
        }

        // 3. Detectar Monto / Importe
        $montoExtraido = null;
        $patternsMonto = [
            '/(?:\$|mxn|importe|monto|monto\s*enviado|total|pagado)[:\s]*\$?\s*([\d,]+\.\d{2})/i',
            '/\$\s*([\d,]+\.\d{2})/',
            '/([\d,]+\.\d{2})\s*(?:mxn|pesos)/i'
        ];

        foreach ($patternsMonto as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $valStr = str_replace(',', '', $matches[1]);
                $valFloat = (float) $valStr;
                if ($valFloat > 0) {
                    $montoExtraido = $valFloat;
                    break;
                }
            }
        }

        // 4. Detectar Fecha
        $fechaTransferencia = null;
        if (preg_match('/(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})/', $text, $matches)) {
            $fechaTransferencia = $matches[1];
        } elseif (preg_match('/(\d{1,2}\s+(?:de\s+)?(?:ene|feb|mar|abr|may|jun|jul|ago|sep|oct|nov|dic|enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)\w*\s+(?:de\s+)?\d{2,4})/i', $text, $matches)) {
            $fechaTransferencia = $matches[1];
        }

        // 5. Detectar CLABE / Cuenta
        $clabeCuenta = null;
        if (preg_match('/(\d{18})/', $text, $matches)) {
            $clabeCuenta = $matches[1];
        } elseif (preg_match('/(?:\*{2,12})\d{4}/', $text, $matches)) {
            $clabeCuenta = $matches[0];
        }

        // 6. Validar si el texto parece ser un comprobante bancario
        $keywordsValidas = ['bbva', 'mercado pago', 'mercadopago', 'transferencia', 'spei', 'pago', 'exitoso', 'autorización', 'autorizacion', 'folio', 'importe', 'monto', 'clabe', 'santander', 'banorte', 'stp', 'comprobante', 'recibo'];
        $foundCount = 0;
        foreach ($keywordsValidas as $kw) {
            if (str_contains($normalizedText, $kw)) {
                $foundCount++;
            }
        }
        $isValidReceipt = ($foundCount >= 1 || $montoExtraido !== null || $claveRastreo !== null);

        return [
            'success' => true,
            'is_valid_receipt' => $isValidReceipt,
            'banco' => $banco,
            'clave_rastreo' => $claveRastreo,
            'fecha_transferencia' => $fechaTransferencia,
            'clabe_cuenta' => $clabeCuenta,
            'monto_extraido' => $montoExtraido,
            'raw_text' => trim($text),
        ];
    }
}
