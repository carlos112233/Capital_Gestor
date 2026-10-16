<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Comprobante;
use App\Services\ReceiptOcrService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ReprocesarComprobantesOcr extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comprobantes:reprocesar-ocr {--id= : ID específico de comprobante a reprocesar} {--all : Reprocesar todos los comprobantes, incluso aprobados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocesa el OCR y la semántica de los comprobantes bancarios pendientes o especificados';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $id = $this->option('id');
        $all = (bool) $this->option('all');

        $query = Comprobante::query()->with('user');

        if ($id) {
            $query->where('id', $id);
        } elseif (!$all) {
            $query->whereIn('status', ['procesando_pago', 'pendiente']);
        }

        $comprobantes = $query->orderBy('id', 'desc')->get();

        if ($comprobantes->isEmpty()) {
            $this->info('No se encontraron comprobantes para reprocesar.');
            return self::SUCCESS;
        }

        $this->info("Reprocesando {$comprobantes->count()} comprobante(s)...");

        $rows = [];

        foreach ($comprobantes as $comp) {
            $fullPath = storage_path('app/public/' . $comp->imagen);
            $parsed = null;

            if (file_exists($fullPath)) {
                $parsed = ReceiptOcrService::processImage($fullPath);
            } else {
                // Si la imagen no está físicamente (ej. entorno local sincronizado solo con BD),
                // intentar reprocesar el texto crudo previo si existe en datos_ocr_json
                $prevOcr = is_array($comp->datos_ocr_json)
                    ? $comp->datos_ocr_json
                    : (json_decode((string)$comp->datos_ocr_json, true) ?: []);

                if (!empty($prevOcr['raw_text'])) {
                    $parsed = ReceiptOcrService::parseReceiptText((string)$prevOcr['raw_text']);
                }
            }

            if ($parsed === null) {
                $this->warn("Comprobante #{$comp->id}: No se encontró imagen física en {$fullPath} ni texto crudo previo.");
                continue;
            }

            $montoDeclarado = (float) $comp->monto;
            $montoExtraido = $parsed['monto_extraido'] !== null ? (float) $parsed['monto_extraido'] : null;
            $coincidenciaMonto = ReceiptOcrService::compareAmounts($montoDeclarado, $montoExtraido);

            $parsed['coincidencia_monto'] = $coincidenciaMonto;
            $parsed['monto_declarado'] = $montoDeclarado;

            // Reconstruir notas limpiando falsas alarmas anteriores
            $notasBase = trim(preg_replace('/\[(🤖|⚠️|✅)[^\]]+\]/u', '', $comp->notas ?? ''));
            $notasAdicionales = "";

            if (!$parsed['is_valid_receipt']) {
                $notasAdicionales .= "\n[⚠️ ALERTA OCR/IA: La imagen no contiene palabras clave claras de un comprobante bancario.]";
            } else {
                $bancoDetectado = $parsed['banco'] ?? $parsed['tipo'] ?? 'Comprobante digital';
                $folioDetectado = $parsed['folio'] ?? $parsed['clave_rastreo'] ?? null;
                $destinoDetectado = $parsed['destino'] ?? null;
                $fechaDetectada = $parsed['fecha_transferencia'] ?? null;

                $detalles = ["🏦 {$bancoDetectado}"];
                if ($folioDetectado) {
                    $detalles[] = "Folio: {$folioDetectado}";
                }
                if ($destinoDetectado) {
                    $detalles[] = "Destino: {$destinoDetectado}";
                }
                if ($fechaDetectada) {
                    $detalles[] = "Fecha: {$fechaDetectada}";
                }
                if ($montoExtraido !== null) {
                    $detalles[] = "Monto: $" . number_format($montoExtraido, 2);
                }

                $notasAdicionales .= "\n[🤖 OCR/IA: " . implode(' | ', $detalles) . "]";

                if ($coincidenciaMonto === 'exacta') {
                    $notasAdicionales .= "\n[✅ Verificado por IA: El monto ingresado ($" . number_format($montoDeclarado, 2) . ") coincide exactamente con la imagen.]";
                } elseif ($coincidenciaMonto === 'difiere') {
                    $diferencia = abs($montoDeclarado - $montoExtraido);
                    $notasAdicionales .= "\n[⚠️ ALERTA IA: El monto reportado ($" . number_format($montoDeclarado, 2) . ") difiere del detectado en imagen ($" . number_format($montoExtraido, 2) . "). Discrepancia: $" . number_format($diferencia, 2) . ".]";
                }
            }

            $notasFinales = $notasBase ? $notasBase . $notasAdicionales : ltrim($notasAdicionales);

            $comp->update([
                'banco'               => $parsed['banco'] ?? $parsed['tipo'] ?? $comp->banco,
                'clave_rastreo'       => $parsed['clave_rastreo'] ?? $parsed['folio'] ?? $comp->clave_rastreo,
                'fecha_transferencia' => $parsed['fecha_transferencia'] ?? $comp->fecha_transferencia,
                'clabe_cuenta'        => $parsed['clabe_cuenta'] ?? $comp->clabe_cuenta,
                'monto_extraido'      => $parsed['monto_extraido'] ?? $comp->monto_extraido,
                'datos_ocr_json'      => $parsed,
                'notas'               => $notasFinales,
            ]);

            $rows[] = [
                'ID' => $comp->id,
                'Cliente' => $comp->user->name ?? 'N/D',
                'Válido' => $parsed['is_valid_receipt'] ? '✅ SÍ' : '❌ NO',
                'Monto Decl' => '$' . number_format($montoDeclarado, 2),
                'Monto Detect' => $montoExtraido !== null ? '$' . number_format($montoExtraido, 2) : 'N/D',
                'Coincide' => $coincidenciaMonto,
                'Banco/Tipo' => $parsed['banco'] ?? $parsed['tipo'] ?? 'N/D',
                'Folio' => $parsed['folio'] ?? 'N/D',
            ];
        }

        if (!empty($rows)) {
            $this->table(['ID', 'Cliente', 'Válido', 'Monto Decl', 'Monto Detect', 'Coincide', 'Banco/Tipo', 'Folio'], $rows);
        }

        $this->info('Reprocesamiento finalizado con éxito.');

        return self::SUCCESS;
    }
}

