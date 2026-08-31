<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Cuenta - {{ $user->name }} - El Bajón</title>
    <style>
        @page {
            margin: 10mm 12mm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        tr {
            page-break-inside: avoid;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .brand {
            font-size: 22px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .subtitle {
            font-size: 11px;
            font-weight: bold;
            color: #475569;
            margin-top: 2px;
        }
        .status-badge {
            background-color: #fee2e2;
            color: #991b1b;
            font-size: 11px;
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 12px;
            display: inline-block;
        }
        .date {
            font-size: 10px;
            font-weight: bold;
            color: #334155;
            margin-top: 4px;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 12px;
        }
        .label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #94a3b8;
        }
        .value {
            font-size: 12px;
            font-weight: bold;
            color: #1e293b;
            margin-top: 2px;
        }
        .cortes-box {
            background-color: #fff7ed;
            border: 1px solid #ffedd5;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 12px;
        }
        .details-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .details-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 8px;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }
        .details-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11px;
        }
        .total-wrapper {
            width: 100%;
            margin-bottom: 15px;
        }
        .total-box {
            float: right;
            width: 220px;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 8px 12px;
            text-align: right;
        }
        .total-amount {
            font-size: 16px;
            font-weight: 900;
            color: #dc2626;
            margin-top: 2px;
        }
        .payment-info {
            clear: both;
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 15px;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #64748b;
            border-top: 1px solid #f1f5f9;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    @php
        $saldo = $user->saldo_pendiente - $montoAjuste;
        $saldoAnterior = $user->saldo_corte_anterior;
        $saldoActual = $user->saldo_corte_actual;

        $ventasCliente = $user->ventas ? $user->ventas->filter(function ($venta) {
            return $venta->articulo && $venta->articulo->nombre !== 'Pago saldado';
        }) : collect();

        $ventasPorCubrir = collect();
        if ($saldo > 0 && $ventasCliente->isNotEmpty()) {
            $acumulado = 0.0;
            foreach ($ventasCliente->reverse() as $venta) {
                if ($acumulado >= $saldo - 0.01) {
                    break;
                }
                $ventasPorCubrir->prepend($venta);
                $acumulado += (float) $venta->total_venta;
            }
        }
    @endphp

    <!-- Encabezado -->
    <table class="header-table">
        <tr>
            <td style="vertical-align: top;">
                <div class="brand">EL BAJÓN</div>
                <div class="subtitle">Estado de Cuenta / Detalle de Compras</div>
            </td>
            <td style="text-align: right; vertical-align: top;">
                <div class="status-badge">Saldo Pendiente</div>
                <div class="date">Fecha de Emisión: {{ now()->format('d/m/Y h:i A') }}</div>
            </td>
        </tr>
    </table>

    <!-- Información del Cliente -->
    <div class="info-box">
        <table>
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div class="label">Cliente</div>
                    <div class="value">{{ $user->name }}</div>
                    <div style="font-size: 10px; color: #64748b;">{{ $user->email ?? '' }}</div>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <div class="label">Teléfono</div>
                    <div class="value">{{ $user->telefono ?? 'Sin teléfono' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Desglose de Cortes -->
    @if($saldoAnterior > 0)
    <div class="cortes-box">
        <table>
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div class="label" style="color: #c2410c;">Corte Anterior (Vencido)</div>
                    <div class="value" style="color: #9a3412;">${{ number_format($saldoAnterior, 2) }}</div>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <div class="label" style="color: #ea580c;">Corte Actual (Quincena)</div>
                    <div class="value" style="color: #c2410c;">${{ number_format($saldoActual, 2) }}</div>
                </td>
            </tr>
        </table>
    </div>
    @endif

    <!-- Detalle de Compras -->
    <table class="details-table">
        <thead>
            <tr>
                <th style="width: 45%;">Producto / Artículo</th>
                <th style="width: 15%; text-align: center;">Cantidad</th>
                <th style="width: 20%; text-align: right;">Precio Unit.</th>
                <th style="width: 20%; text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ventasPorCubrir as $venta)
                <tr>
                    <td style="vertical-align: middle;">
                        <strong>{{ $venta->articulo->nombre ?? 'Artículo' }}</strong>
                        @if($venta->descripcion)
                            <br><span style="font-size: 9px; color: #64748b;">{{ $venta->descripcion }}</span>
                        @endif
                    </td>
                    <td style="text-align: center; vertical-align: middle; font-weight: bold; color: #334155;">
                        {{ $venta->cantidad }}x
                    </td>
                    <td style="text-align: right; vertical-align: middle; color: #475569;">
                        ${{ number_format($venta->precio_venta, 2) }}
                    </td>
                    <td style="text-align: right; vertical-align: middle; font-weight: bold; color: #0f172a;">
                        ${{ number_format($venta->total_venta, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 12px; color: #64748b;">
                        No hay detalle de compras pendientes registrado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Resumen Total -->
    <div class="total-wrapper">
        <div class="total-box">
            <div style="font-size: 11px; font-weight: bold; color: #991b1b;">Saldo a Cubrir:</div>
            <div class="total-amount">${{ number_format($saldo, 2) }}</div>
        </div>
    </div>

    <!-- Datos de Pago -->
    <div class="payment-info">
        <strong style="font-size: 10px; color: #166534; text-transform: uppercase;">💳 Datos para Pago:</strong>
        <table style="margin-top: 4px;">
            <tr>
                <td style="width: 50%; vertical-align: top; font-size: 10px;">
                    <strong style="color: #15803d;">BBVA:</strong><br>
                    Cuenta: <strong>158 086 7512</strong><br>
                    CLABE: <strong>012 650 01580867512 5</strong>
                </td>
                <td style="width: 50%; vertical-align: top; font-size: 10px;">
                    <strong style="color: #15803d;">Mercado Pago:</strong><br>
                    CLABE: <strong>722969010384935035</strong>
                </td>
            </tr>
        </table>
    </div>

    <!-- Pie de página -->
    <div class="footer">
        <p style="margin: 0; font-weight: bold;">Favor de enviar su comprobante de pago a este número de WhatsApp.</p>
        <p style="margin: 2px 0 0 0; color: #0f172a; font-weight: bold;">El Bajón</p>
    </div>
</body>
</html>
