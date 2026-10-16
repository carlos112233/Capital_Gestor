<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class ReceiptOcrService
{
    /** Resultado del OCR a partir del cual se detiene la búsqueda de mejores pasadas. */
    private const STRONG_SCORE = 10;

    /** Frases de éxito de la operación (se evalúan sobre texto normalizado: minúsculas, sin acentos). */
    private const SUCCESS_PATTERNS = [
        'Transferencia exitosa' => '/transferencia\s+(?:exitosa|realizada|enviada|completada|aplicada)/',
        'Envío exitoso'         => '/envio\s+(?:exitoso|realizado|completado)|\benviaste\b/',
        'Pago realizado'        => '/pago\s+(?:realizado|exitoso|aplicado|completado|recibido)/',
        'Operación exitosa'     => '/operacion\s+(?:exitosa|realizada|aplicada|completada)/',
        'Éxito'                 => '/\bexito(?:s[oa])?\b/',
    ];

    /** Etiquetas de folio / referencia de la operación. */
    private const FOLIO_LABELS = [
        'folio de operacion', 'folio', 'clave de rastreo', 'rastreo', 'referencia', 'autorizacion',
        'numero de operacion', 'num. de operacion', 'no. de operacion', 'id de transaccion',
        'codigo de confirmacion',
    ];

    /** Etiquetas de importe. */
    private const AMOUNT_LABELS = [
        'monto transferido', 'monto enviado', 'monto pagado', 'importe transferido', 'importe',
        'monto', 'total pagado', 'total', 'cantidad',
    ];

    /** Palabras genéricas de comprobantes bancarios / fintech. */
    private const GENERIC_KEYWORDS = [
        'transferencia', 'spei', 'comprobante', 'recibo', 'clabe', 'beneficiario', 'destino', 'origen',
        'concepto', 'cuenta de ahorro', 'cuenta de cheques', 'cuenta origen', 'cuenta destino',
        'tarjeta', 'deposito', 'pago', 'terceros',
    ];

    /** Bancos y fintechs (se buscan con límites de palabra para evitar falsos positivos tipo "nu" en "numero"). */
    private const BANKS = [
        'bbva' => 'BBVA', 'banamex' => 'Citibanamex', 'citibanamex' => 'Citibanamex', 'santander' => 'Santander',
        'banorte' => 'Banorte', 'stp' => 'STP', 'mercado pago' => 'Mercado Pago', 'mercadopago' => 'Mercado Pago',
        'nu mexico' => 'Nu México', 'nubank' => 'Nu México', 'nu' => 'Nu México', 'stori' => 'Stori', 'hsbc' => 'HSBC',
        'scotiabank' => 'Scotiabank', 'banco azteca' => 'Banco Azteca', 'azteca' => 'Banco Azteca',
        'banregio' => 'Banregio', 'inbursa' => 'Inbursa', 'spin' => 'Spin by OXXO', 'klar' => 'Klar',
        'uala' => 'Ualá', 'hey banco' => 'Hey Banco', 'openbank' => 'Openbank', 'revolut' => 'Revolut',
        'albo' => 'Albo', 'bancoppel' => 'BanCoppel', 'afirme' => 'Afirme', 'banbajio' => 'BanBajío',
        'bajio' => 'BanBajío', 'mifel' => 'Mifel', 'multiva' => 'Multiva', 'vepormas' => 'Ve por Más',
        've por mas' => 'Ve por Más', 'banco del bienestar' => 'Banco del Bienestar', 'paypal' => 'PayPal',
        'didi card' => 'DiDi Card', 'cuenca' => 'Cuenca', 'plata card' => 'Plata Card',
    ];

    private const MONTHS = [
        'ene' => 1, 'feb' => 2, 'mar' => 3, 'abr' => 4, 'may' => 5, 'jun' => 6,
        'jul' => 7, 'ago' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dic' => 12,
    ];

    /**
     * Extrae texto de la imagen (Tesseract con preprocesado, Google Vision como respaldo)
     * y estructura los datos del comprobante.
     *
     * @param string $imagePath Ruta absoluta del archivo de imagen
     */
    public static function processImage(string $imagePath): array
    {
        $rawText = self::extractText($imagePath);

        // Si Tesseract no devolvió suficiente texto, intentar con Google Vision si está disponible
        if (strlen(trim($rawText)) < 15) {
            $rawText = self::runGoogleVisionFallback($imagePath) ?: $rawText;
        }

        return self::parseReceiptText($rawText);
    }

    /**
     * Compara el monto declarado por el cliente contra el detectado en la imagen.
     *
     * @return string exacta | difiere | sin_monto_detectado | sin_monto_declarado
     */
    public static function compareAmounts(?float $declarado, ?float $detectado): string
    {
        if ($detectado === null) {
            return 'sin_monto_detectado';
        }
        if ($declarado === null || $declarado <= 0) {
            return 'sin_monto_declarado';
        }

        return abs($declarado - $detectado) < 0.01 ? 'exacta' : 'difiere';
    }

    // ------------------------------------------------------------------
    // Lectura de texto (Tesseract + preprocesado)
    // ------------------------------------------------------------------

    /**
     * Ejecuta varias pasadas de Tesseract sobre variantes de la imagen y devuelve el mejor texto.
     *
     * Las capturas de apps bancarias modernas suelen ser de modo oscuro, de baja resolución o con
     * tipografías finas: Tesseract sobre la imagen cruda devuelve texto vacío o basura. Por eso se
     * escala, se pasa a grises, se aumenta el contraste y, si la captura es oscura, se invierte.
     */
    protected static function extractText(string $imagePath): string
    {
        if (!file_exists($imagePath)) {
            return '';
        }

        $dark = self::isDarkImage($imagePath);
        $plans = $dark
            ? [[true, 6], [false, 6], [true, 11], [false, 4]]
            : [[false, 6], [true, 6], [false, 11], [false, 4]];

        $best = '';
        $bestScore = -1;

        foreach ($plans as [$negate, $psm]) {
            $prepared = self::preprocess($imagePath, $negate);
            $target = $prepared ?? $imagePath;

            $text = self::runTesseract($target, $psm);

            if ($prepared !== null) {
                @unlink($prepared);
            }

            if (trim($text) === '') {
                continue;
            }

            $parsed = self::parseReceiptText($text);
            $score = $parsed['score'] + ($parsed['monto_extraido'] !== null ? 1 : 0);

            if ($score > $bestScore || ($score === $bestScore && strlen($text) > strlen($best))) {
                $best = $text;
                $bestScore = $score;
            }

            if ($parsed['is_valid_receipt'] && $parsed['monto_extraido'] !== null
                && $parsed['clave_rastreo'] !== null && $bestScore >= self::STRONG_SCORE) {
                break;
            }
        }

        if ($best === '') {
            // Último intento: imagen cruda con segmentación automática
            $best = self::runTesseract($imagePath, 3);
        }

        return $best;
    }

    /**
     * Ejecuta Tesseract OCR mediante CLI.
     */
    protected static function runTesseract(string $imagePath, int $psm = 6): string
    {
        if (!file_exists($imagePath)) {
            return '';
        }

        try {
            $process = new Process([
                'tesseract', $imagePath, 'stdout',
                '-l', self::tesseractLanguages(),
                '--oem', '1',
                '--psm', (string) $psm,
            ]);
            $process->setTimeout(20);
            $process->run();

            if ($process->isSuccessful()) {
                return $process->getOutput();
            }

            Log::warning('Tesseract process error: ' . $process->getErrorOutput());
        } catch (\Throwable $e) {
            Log::error('Excepción al ejecutar Tesseract OCR: ' . $e->getMessage());
        }

        return '';
    }

    /**
     * Idiomas de Tesseract disponibles (spa+eng si existe el paquete de español).
     */
    protected static function tesseractLanguages(): string
    {
        static $langs = null;

        if ($langs !== null) {
            return $langs;
        }

        $langs = 'eng';

        try {
            $process = new Process(['tesseract', '--list-langs']);
            $process->setTimeout(5);
            $process->run();
            if ($process->isSuccessful() && preg_match('/^spa$/m', $process->getOutput())) {
                $langs = 'spa+eng';
            }
        } catch (\Throwable $e) {
            // Se mantiene 'eng'
        }

        return $langs;
    }

    /**
     * Determina si la captura es predominantemente oscura (luminancia media < 50%).
     */
    protected static function isDarkImage(string $imagePath): bool
    {
        if (!function_exists('imagecreatefromstring')) {
            return false;
        }

        $data = @file_get_contents($imagePath);
        $img = $data !== false ? @imagecreatefromstring($data) : false;
        if (!$img) {
            return false;
        }

        $small = @imagescale($img, 48, 48, IMG_BILINEAR_FIXED);
        imagedestroy($img);
        if (!$small) {
            return false;
        }

        $sum = 0.0;
        $count = 0;
        for ($x = 0; $x < 48; $x++) {
            for ($y = 0; $y < 48; $y++) {
                $rgb = imagecolorat($small, $x, $y);
                $sum += (0.299 * (($rgb >> 16) & 0xFF) + 0.587 * (($rgb >> 8) & 0xFF) + 0.114 * ($rgb & 0xFF)) / 255;
                $count++;
            }
        }
        imagedestroy($small);

        return $count > 0 && ($sum / $count) < 0.5;
    }

    /**
     * Genera una copia PNG en escala de grises, ampliada y con mayor contraste (opcionalmente invertida).
     *
     * @return string|null Ruta del archivo temporal o null si GD no está disponible
     */
    protected static function preprocess(string $imagePath, bool $negate): ?string
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $data = @file_get_contents($imagePath);
        $img = $data !== false ? @imagecreatefromstring($data) : false;
        if (!$img) {
            return null;
        }

        $w = imagesx($img);
        $h = imagesy($img);

        // Ampliar capturas pequeñas (Tesseract rinde mejor con letra de ~30px de alto)
        if ($w < 1600 && ($w * 2) * ($h * 2) <= 24_000_000) {
            $scaled = @imagescale($img, $w * 2, -1, IMG_BICUBIC);
            if ($scaled) {
                imagedestroy($img);
                $img = $scaled;
            }
        }

        imagefilter($img, IMG_FILTER_GRAYSCALE);
        if ($negate) {
            imagefilter($img, IMG_FILTER_NEGATE);
        }
        imagefilter($img, IMG_FILTER_CONTRAST, -30);

        $tmpBase = tempnam(sys_get_temp_dir(), 'ocr_');
        if ($tmpBase === false) {
            imagedestroy($img);
            return null;
        }
        $tmp = $tmpBase . '.png';
        @unlink($tmpBase);

        $ok = imagepng($img, $tmp);
        imagedestroy($img);

        return $ok ? $tmp : null;
    }

    /**
     * Fallback usando Google Vision API si las credenciales existen.
     */
    protected static function runGoogleVisionFallback(string $imagePath): string
    {
        try {
            if (class_exists('\Google\Cloud\Vision\V1\ImageAnnotatorClient') &&
                env('GOOGLE_APPLICATION_CREDENTIALS') &&
                file_exists(base_path((string) env('GOOGLE_APPLICATION_CREDENTIALS')))) {

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
            Log::warning('Google Vision fallback not available: ' . $e->getMessage());
        }

        return '';
    }

    // ------------------------------------------------------------------
    // Parser semántico
    // ------------------------------------------------------------------

    /**
     * Parsea el texto extraído y estructura los datos de comprobantes bancarios y de fintechs
     * (SPEI, transferencias a terceros, envíos y pagos realizados desde apps móviles).
     */
    public static function parseReceiptText(string $text): array
    {
        $flat = preg_replace('/\s+/', ' ', Str::ascii(mb_strtolower($text, 'UTF-8'))) ?? '';

        // 1. Señales semánticas
        $exitoLabel = null;
        foreach (self::SUCCESS_PATTERNS as $label => $pattern) {
            if (preg_match($pattern, $flat)) {
                $exitoLabel = $label;
                break;
            }
        }

        $folioLabel = self::firstContained($flat, self::FOLIO_LABELS);
        $importeLabel = self::firstContained($flat, self::AMOUNT_LABELS);

        $genericHits = [];
        foreach (self::GENERIC_KEYWORDS as $kw) {
            if (str_contains($flat, $kw)) {
                $genericHits[] = $kw;
            }
        }

        // 2. Banco / fintech
        $banco = null;
        foreach (self::BANKS as $key => $name) {
            if (preg_match('/\b' . preg_quote($key, '/') . '\b/', $flat)) {
                $banco = $name;
                break;
            }
        }

        // 3. Tipo de operación
        $tipo = null;
        foreach ([
            'transferencia a terceros' => 'Transferencia a terceros',
            'spei'                     => 'Transferencia SPEI',
            'transferencia'            => 'Transferencia',
            'envio'                    => 'Envío de dinero',
            'pago de servicio'         => 'Pago de servicio',
            'pago'                     => 'Pago',
        ] as $needle => $label) {
            if (str_contains($flat, $needle)) {
                $tipo = $label;
                break;
            }
        }

        // 4. Datos estructurados
        $clave = self::extractFolio($text);
        $monto = self::extractAmount($text);
        [$fechaRaw, $fechaIso] = self::extractDate($text);

        $destinoRaw = self::extractLabeled(
            $text,
            ['destino', 'cuenta\s+destino', 'cuenta\s+de\s+destino', 'beneficiario', 'destinatario', 'enviado\s+a', 'transferido\s+a', 'para(?=\s*:)'],
            '/^(origen|folio|fecha|concepto|monto|importe|estado|referencia|clave)\b/iu'
        );
        $origenRaw = self::extractLabeled(
            $text,
            ['origen', 'cuenta\s+origen', 'cuenta\s+de\s+origen', 'ordenante', 'remitente', 'desde'],
            '/^(destino|folio|fecha|concepto|monto|importe|estado|referencia|clave)\b/iu'
        );
        $concepto = self::extractLabeled(
            $text,
            ['concepto(?:\s+de\s+pago)?', 'motivo', 'descripci[oó]n'],
            '/^(origen|destino|folio|fecha|monto|importe|estado|referencia|clave)\b/iu'
        );

        [$destino, $cuentaDestino] = self::splitAccount($destinoRaw);
        [$origen, $cuentaOrigen] = self::splitAccount($origenRaw);

        // 5. CLABE / cuenta
        $clabeCuenta = null;
        if (preg_match('/(?<!\d)(\d{18})(?!\d)/', $text, $m)) {
            $clabeCuenta = $m[1];
        } elseif ($cuentaDestino !== null) {
            $clabeCuenta = '•••' . $cuentaDestino;
        } elseif (preg_match('/[\*•·]{2,12}\s*\d{4}/u', $text, $m)) {
            $clabeCuenta = $m[0];
        }

        // 6. Puntaje y validación
        $score = 0;
        $score += $exitoLabel !== null ? 3 : 0;
        $score += $folioLabel !== null ? 2 : 0;
        $score += $importeLabel !== null ? 2 : 0;
        $score += $monto !== null ? 2 : 0;
        $score += $clave !== null ? 2 : 0;
        $score += $banco !== null ? 1 : 0;
        $score += $fechaRaw !== null ? 1 : 0;
        $score += min(3, count($genericHits));

        $isValidReceipt = $score >= 4
            && ($exitoLabel !== null || $clave !== null || $banco !== null || count($genericHits) >= 1);

        $palabrasClave = array_values(array_filter([
            $exitoLabel,
            $folioLabel !== null ? ucfirst($folioLabel) : null,
            $importeLabel !== null ? ucfirst($importeLabel) : null,
        ]));

        return [
            'success'             => true,
            'is_valid_receipt'    => $isValidReceipt,
            'score'               => $score,
            'estado'              => $exitoLabel !== null ? 'Exitosa' : null,
            'tipo'                => $tipo,
            'banco'               => $banco,
            'clave_rastreo'       => $clave,
            'folio'               => $clave,
            'fecha_transferencia' => $fechaRaw,
            'fecha_iso'           => $fechaIso,
            'clabe_cuenta'        => $clabeCuenta,
            'monto_extraido'      => $monto,
            'destino'             => $destino,
            'cuenta_destino'      => $cuentaDestino,
            'origen'              => $origen,
            'cuenta_origen'       => $cuentaOrigen,
            'concepto'            => $concepto,
            'palabras_clave'      => $palabrasClave,
            'raw_text'            => trim($text),
        ];
    }

    /**
     * Primer elemento de $needles contenido en $haystack (ya normalizado), o null.
     *
     * @param string[] $needles
     */
    private static function firstContained(string $haystack, array $needles): ?string
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return $needle;
            }
        }

        return null;
    }

    /**
     * Folio / clave de rastreo / referencia de la operación.
     */
    private static function extractFolio(string $text): ?string
    {
        $patterns = [
            // Etiqueta + valor (también cuando el valor está en la línea siguiente)
            '/(?:clave\s+de\s+rastreo|folio(?:\s+de\s+(?:la\s+)?operaci[oó]n)?|n[uú]m(?:ero|\.)?\s+de\s+(?:operaci[oó]n|referencia|autorizaci[oó]n)|no\.?\s+de\s+(?:operaci[oó]n|referencia)|referencia|autorizaci[oó]n|id\s+de\s+(?:transacci[oó]n|operaci[oó]n)|c[oó]digo\s+de\s+confirmaci[oó]n)\s*[:#\-]?\s*([A-Za-z0-9]{6,35})/iu',
            // Clave SPEI típica de 20-30 dígitos
            '/(?<!\d)(\d{20,30})(?!\d)/',
            // Cadena larga con mayoría de dígitos (folios sin etiqueta legible)
            '/\b([A-Z0-9]{10,25})\b/',
        ];

        foreach ($patterns as $i => $pattern) {
            if (!preg_match_all($pattern, $text, $matches)) {
                continue;
            }
            foreach ($matches[1] as $candidate) {
                $candidate = trim($candidate);
                $digits = preg_match_all('/\d/', $candidate);

                // Un folio real contiene dígitos; descarta palabras ("transferencia", "exitoso"...)
                if ($digits < 4 || ($i === 2 && $digits / strlen($candidate) < 0.6)) {
                    continue;
                }

                return strtoupper($candidate);
            }
        }

        return null;
    }

    /**
     * Importe de la operación (prioriza las etiquetas "monto transferido", "importe", "total"...).
     */
    private static function extractAmount(string $text): ?float
    {
        $number = '([0-9]{1,3}(?:,[0-9]{3})+(?:\.[0-9]{1,2})?|[0-9]+(?:\.[0-9]{1,2})?)';
        $patterns = [
            '/(?:monto\s+(?:transferido|enviado|pagado|de\s+la\s+transferencia)|importe(?:\s+transferido)?|total(?:\s+pagado)?|cantidad|monto)\s*[:\-]?\s*(?:mxn\s*)?[\$S]?\s*' . $number . '/iu',
            '/\$\s*' . $number . '/u',
            '/\bmxn\s*' . $number . '/iu',
            '/' . $number . '\s*(?:mxn|pesos)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (!preg_match_all($pattern, $text, $matches)) {
                continue;
            }
            foreach ($matches[1] as $candidate) {
                $value = (float) str_replace(',', '', $candidate);
                // Evita confundir folios/cuentas con importes
                if ($value > 0 && $value < 10_000_000 && strlen(preg_replace('/\D/', '', $candidate)) <= 9) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Fecha de la operación: [texto para mostrar, ISO-8601 o null].
     *
     * @return array{0: ?string, 1: ?string}
     */
    private static function extractDate(string $text): array
    {
        // "16 oct 2026, 09:02 h" / "16 de octubre de 2026 a las 09:02"
        if (preg_match(
            '/(\d{1,2})\s*(?:de\s+)?(ene|feb|mar|abr|may|jun|jul|ago|sept|sep|oct|nov|dic)[a-z]*\.?,?\s*(?:de\s+)?(\d{4})(?:[,\s]+(?:a\s+las\s+)?(\d{1,2}:\d{2})(?::\d{2})?)?/iu',
            $text,
            $m
        )) {
            $day = (int) $m[1];
            $mon = strtolower(substr($m[2], 0, 3));
            $year = (int) $m[3];
            $time = $m[4] ?? null;

            $display = sprintf('%d %s %d', $day, $mon, $year) . ($time ? ', ' . $time . ' h' : '');
            $iso = null;
            try {
                [$hh, $mm] = $time ? array_map('intval', explode(':', $time)) : [0, 0];
                $iso = Carbon::create($year, self::MONTHS[$mon], $day, $hh, $mm)->toIso8601String();
            } catch (\Throwable $e) {
                // Fecha inválida: se conserva solo el texto
            }

            return [$display, $iso];
        }

        // 02/09/2026, 02-09-26, 02.09.2026
        if (preg_match('/(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2,4})/', $text, $m)) {
            $iso = null;
            try {
                $year = (int) $m[3] < 100 ? 2000 + (int) $m[3] : (int) $m[3];
                $iso = Carbon::create($year, (int) $m[2], (int) $m[1])->startOfDay()->toIso8601String();
            } catch (\Throwable $e) {
                // Se conserva solo el texto
            }

            return [$m[0], $iso];
        }

        return [null, null];
    }

    /**
     * Valor que sigue a una etiqueta (misma línea o siguiente).
     *
     * @param string[] $labels Fragmentos de regex de etiquetas
     */
    private static function extractLabeled(string $text, array $labels, string $rejectPattern): ?string
    {
        $pattern = '/\b(?:' . implode('|', $labels) . ')\b\s*[:\-]?\s*([^\n\r]{2,80})/iu';

        if (!preg_match_all($pattern, $text, $matches)) {
            return null;
        }

        foreach ($matches[1] as $value) {
            $value = trim($value, " \t:-");
            if ($value === '' || preg_match($rejectPattern, $value)) {
                continue;
            }

            return $value;
        }

        return null;
    }

    /**
     * Separa "Carlos Ramon M •7512" en nombre y últimos 4 dígitos de cuenta.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private static function splitAccount(?string $value): array
    {
        if ($value === null) {
            return [null, null];
        }

        $value = trim($value);

        // Solo dígitos: es una cuenta/CLABE, no un nombre
        if (preg_match('/^[\d\s]{8,}$/', $value)) {
            return [null, substr(preg_replace('/\D/', '', $value), -4)];
        }

        if (preg_match('/^(.*?)\s*[^\p{L}\p{N}\s]{0,3}\s*(\d{4})\s*$/u', $value, $m) && trim($m[1]) !== '') {
            return [trim($m[1], " \t-:•*·."), $m[2]];
        }

        return [trim($value, " \t-:•*·."), null];
    }
}
