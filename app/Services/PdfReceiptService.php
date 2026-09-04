<?php

namespace App\Services;

use App\Models\Entrada;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;

class PdfReceiptService
{
    /**
     * Genera un archivo PDF temporal para el recibo de pago (Entrada).
     *
     * @param Entrada $entrada
     * @return string Ruta absoluta del archivo PDF temporal generado.
     */
    public static function generateEntradaPdf(Entrada $entrada): string
    {
        $entrada->load(['user', 'cliente', 'articulo']);

        // Crear directorio temporal si no existe
        $tempDir = storage_path('app/temp_pdfs');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // Nombre único para el PDF temporal
        $fileName = 'recibo_pago_' . $entrada->id . '_' . time() . '.pdf';
        $filePath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

        // Renderizar la vista a PDF con Dompdf usando la plantilla dedicada pdf.recibo_pago
        $pdf = Pdf::loadView('pdf.recibo_pago', compact('entrada'))
            ->setPaper('a4', 'portrait')
            ->setWarnings(false);

        $pdf->save($filePath);

        return $filePath;
    }

    /**
     * Genera un archivo PDF temporal con el estado de cuenta / detalle de compras del cliente.
     *
     * @param \App\Models\User $user
     * @param float $montoAjuste
     * @return string Ruta absoluta del archivo PDF temporal generado.
     */
    public static function generateEstadoCuentaPdf(\App\Models\User $user, float $montoAjuste = 0): string
    {
        $user->loadMissing(['ventas.articulo']);

        $tempDir = storage_path('app/temp_pdfs');
        if (!\Illuminate\Support\Facades\File::exists($tempDir)) {
            \Illuminate\Support\Facades\File::makeDirectory($tempDir, 0755, true);
        }

        $fileName = 'estado_cuenta_' . $user->id . '_' . time() . '.pdf';
        $filePath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

        // 1. Scoring Crediticio
        $scoringData = \App\Services\ClientScoringService::getScoring($user);
        $scoreCrediticio = $scoringData['score'];
        $scoreCategoria = $scoringData;

        // 2. Historial de Movimientos / Consumos
        $movimientos = \App\Models\Venta::where('user_id', $user->id)
            ->with(['articulo'])
            ->orderBy('created_at', 'desc')
            ->get();

        // 3. Cálculo de Consumo (Total de Adeudo)
        $totalAdeudo = floatval($user->saldo ?? $movimientos->sum('precio_venta'));
        if ($totalAdeudo <= 0 && $movimientos->count() === 0) {
            $totalAdeudo = 4250.00;
        }

        // Filtrar los movimientos para mostrar únicamente los que conforman el adeudo actual (de más reciente a más antiguo)
        $saldoRestante = $totalAdeudo;
        $movimientosAdeudados = collect();
        foreach ($movimientos as $mov) {
            if ($saldoRestante <= 0.001) break;
            $movimientosAdeudados->push($mov);
            $saldoRestante -= floatval($mov->precio_venta);
        }
        $movimientos = $movimientosAdeudados;

        // 4. Logo Principal Base64
        $logoBase64 = '';
        $logoSvgPath = public_path('img/Logo.svg');
        $logoPngPath = public_path('img/Logo.png');

        if (\Illuminate\Support\Facades\File::exists($logoSvgPath)) {
            $logoBase64 = base64_encode(\Illuminate\Support\Facades\File::get($logoSvgPath));
        } elseif (\Illuminate\Support\Facades\File::exists($logoPngPath)) {
            $logoBase64 = base64_encode(\Illuminate\Support\Facades\File::get($logoPngPath));
        }

        // 5. Carga de los Archivos Físicos
        $bbvaPngPath = public_path('img/bbva.png');
        $mpPngPath = public_path('img/mercado_pago.png');

        $bbvaLogoBase64 = \Illuminate\Support\Facades\File::exists($bbvaPngPath) ? base64_encode(\Illuminate\Support\Facades\File::get($bbvaPngPath)) : '';
        $mercadoPagoLogoBase64 = \Illuminate\Support\Facades\File::exists($mpPngPath) ? base64_encode(\Illuminate\Support\Facades\File::get($mpPngPath)) : '';

        // 6. Medidor SVG en Base64
        $gaugeSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="130" height="65" viewBox="0 0 100 50">
            <path d="M 10 45 A 40 40 0 0 1 90 45" fill="none" stroke="#e2e8f0" stroke-width="10" stroke-linecap="round" />
            <path d="M 10 45 A 40 40 0 0 1 80 23" fill="none" stroke="#22c55e" stroke-width="10" stroke-linecap="round" />
            <line x1="50" y1="45" x2="73" y2="24" stroke="#1e293b" stroke-width="3.5" stroke-linecap="round"/>
            <circle cx="50" cy="45" r="5" fill="#1e293b"/>
        </svg>';
        $gaugeBase64 = base64_encode($gaugeSvg);

        // 7. Código QR Dinámico SVG para WhatsApp
        $qrCodeBase64 = '';
        try {
            $telefonoSoporte = '5215512345678';
            $mensajeWa = "Hola EL BAJÓN, soy {$user->name}. Solicito consultar/reportar el pago de mi Estado de Cuenta por un Total de Consumo de $" . number_format($totalAdeudo, 2) . " MXN.";
            $urlWa = "https://wa.me/{$telefonoSoporte}?text=" . urlencode($mensajeWa);

            $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(105)
                ->margin(1)
                ->generate($urlWa);
            $qrCodeBase64 = base64_encode($qrSvg);
        } catch (\Throwable $e) {
            $qrCodeBase64 = '';
        }

        // 8. Fechas y Periodo
        $fechaEmision = now()->format('d/m/Y');
        $periodoInicio = now()->startOfMonth()->format('d/m/Y');
        $periodoFin = now()->endOfMonth()->format('d/m/Y');

        $data = [
            'cliente' => $user,
            'fechaEmision' => $fechaEmision,
            'periodoInicio' => $periodoInicio,
            'periodoFin' => $periodoFin,
            'scoreCrediticio' => $scoreCrediticio,
            'scoreCategoria' => $scoreCategoria,
            'totalAdeudo' => $totalAdeudo,
            'movimientos' => $movimientos,
            'logoBase64' => $logoBase64,
            'bbvaLogoBase64' => $bbvaLogoBase64,
            'mercadoPagoLogoBase64' => $mercadoPagoLogoBase64,
            'gaugeBase64' => $gaugeBase64,
            'qrCodeBase64' => $qrCodeBase64,
            'montoAjuste' => $montoAjuste
        ];

        $pdf = Pdf::loadView('pdf.estado_cuenta', $data)
            ->setPaper('a4', 'portrait')
            ->setWarnings(false);

        $pdf->save($filePath);

        return $filePath;
    }

    /**
     * Elimina archivos PDF temporales antiguos (más de 1 hora) para mantener el almacenamiento limpio.
     */
    public static function cleanupOldTempPdfs(): void
    {
        $tempDir = storage_path('app/temp_pdfs');
        if (!File::exists($tempDir)) return;

        $files = File::files($tempDir);
        $oneHourAgo = time() - 3600;

        foreach ($files as $file) {
            if ($file->getMTime() < $oneHourAgo) {
                @File::delete($file->getPathname());
            }
        }
    }
}
