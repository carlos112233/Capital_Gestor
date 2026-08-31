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
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $fileName = 'estado_cuenta_' . $user->id . '_' . time() . '.pdf';
        $filePath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

        $pdf = Pdf::loadView('pdf.estado_cuenta', compact('user', 'montoAjuste'))
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
