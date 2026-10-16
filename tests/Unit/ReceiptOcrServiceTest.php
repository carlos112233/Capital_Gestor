<?php

declare(strict_types=1);

use App\Services\ReceiptOcrService;

test('ReceiptOcrService parsea exitosamente el comprobante digital de Ricardo', function () {
    $rawText = "Estado: Transferencia exitosa (Transferencia a terceros)
Monto transferido: $325.00
Destino: Carlos Ramon M •7512
Origen: Cuenta de Ahorro •7520
Folio de operación: 0034987013
Fecha: 16 oct 2026, 09:02 h.
Concepto: comida";

    $parsed = ReceiptOcrService::parseReceiptText($rawText);

    expect($parsed['is_valid_receipt'])->toBeTrue()
        ->and($parsed['estado'])->toBe('Exitosa')
        ->and($parsed['tipo'])->toBe('Transferencia a terceros')
        ->and($parsed['monto_extraido'])->toEqual(325.00)
        ->and($parsed['folio'])->toBe('0034987013')
        ->and($parsed['clave_rastreo'])->toBe('0034987013')
        ->and($parsed['destino'])->toContain('Carlos Ramon M')
        ->and($parsed['cuenta_destino'])->toBe('7512')
        ->and($parsed['origen'])->toContain('Cuenta de Ahorro')
        ->and($parsed['cuenta_origen'])->toBe('7520')
        ->and($parsed['fecha_transferencia'])->toBe('16 oct 2026, 09:02 h')
        ->and(str_starts_with($parsed['fecha_iso'], '2026-10-16'))->toBeTrue()
        ->and($parsed['concepto'])->toBe('comida')
        ->and(ReceiptOcrService::compareAmounts(325.00, $parsed['monto_extraido']))->toBe('exacta');
});

test('ReceiptOcrService reconoce comprobante tradicional SPEI BBVA', function () {
    $rawText = "BBVA BANCOMER
COMPROBANTE DE PAGO SPEI
CLAVE DE RASTREO: 202609024001234567890
FECHA: 02/09/2026
IMPORTE: $ 1,250.50 MXN
CONCEPTO: Pago de pedido";

    $parsed = ReceiptOcrService::parseReceiptText($rawText);

    expect($parsed['is_valid_receipt'])->toBeTrue()
        ->and($parsed['banco'])->toBe('BBVA')
        ->and($parsed['clave_rastreo'])->toBe('202609024001234567890')
        ->and($parsed['monto_extraido'])->toEqual(1250.50)
        ->and($parsed['fecha_transferencia'])->toBe('02/09/2026');
});

test('ReceiptOcrService rechaza texto que no corresponde a un comprobante bancario', function () {
    $rawText = "Menu del dia:
Tacos al pastor 5 ordenes
Refresco de cola 2 piezas
Total de tacos: 5";

    $parsed = ReceiptOcrService::parseReceiptText($rawText);

    expect($parsed['is_valid_receipt'])->toBeFalse();
});

test('ReceiptOcrService detecta discrepancias de montos', function () {
    expect(ReceiptOcrService::compareAmounts(350.00, 325.00))->toBe('difiere')
        ->and(ReceiptOcrService::compareAmounts(325.00, 325.00))->toBe('exacta')
        ->and(ReceiptOcrService::compareAmounts(null, 325.00))->toBe('sin_monto_declarado')
        ->and(ReceiptOcrService::compareAmounts(325.00, null))->toBe('sin_monto_detectado');
});
