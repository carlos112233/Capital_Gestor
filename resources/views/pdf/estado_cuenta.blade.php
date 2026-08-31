<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Cuenta / Detalle de Compras - {{ $user->name }} - El Bajón</title>
    <style>
        @page {
            margin: 15mm;
        }
        html, body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
            width: 100%;
            font-size: 12px;
            line-height: 1.4;
            background-color: #ffffff;
        }
        table {
            border-collapse: collapse;
        }
        .outer-card {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 16px;
            background-color: #ffffff;
            box-sizing: border-box;
        }
        .header-box {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }
        .brand {
            font-size: 22px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .subtitle {
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            margin-top: 2px;
        }
        .folio-badge {
            background-color: #fee2e2;
            color: #991b1b;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 16px;
            display: inline-block;
        }
        .date {
            font-size: 10px;
            font-weight: 700;
            color: #334155;
            margin-top: 4px;
        }
        .client-box {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }
        .label {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            color: #94a3b8;
        }
        .value {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            margin-top: 2px;
        }
        .details-table {
            width: 100%;
            margin-bottom: 16px;
        }
        .details-table th {
            background-color: #f1f5f9;
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 8px 10px;
            border-bottom: 1px solid #cbd5e1;
        }
        .details-table td {
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .total-box {
            width: 260px;
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 10px 14px;
            margin-left: auto;
            margin-bottom: 16px;
        }
        .total-amount {
            font-size: 18px;
            font-weight: 900;
            color: #dc2626;
        }
        .payment-info {
            width: 100%;
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #64748b;
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
            margin-top: 12px;
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

    <table width="100%" cellpadding="0" cellspacing="0" style="width: 100%;">
        <tr>
            <td class="outer-card">
                <!-- Encabezado -->
                <table class="header-box" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="left" valign="middle" width="60%">
                            <div class="brand">EL BAJÓN</div>
                            <div class="subtitle">Estado de Cuenta / Detalle de Compras</div>
                        </td>
                        <td align="right" valign="middle" width="40%">
                            <div class="folio-badge">Saldo Pendiente</div>
                            <div class="date">Fecha de Emisión: {{ now()->format('d/m/Y h:i A') }}</div>
                        </td>
                    </tr>
                </table>

                <!-- Información del Cliente -->
                <table class="client-box" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="50%" align="left" valign="top">
                            <div class="label">Cliente</div>
                            <div class="value">{{ $user->name }}</div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 1px;">{{ $user->email ?? '' }}</div>
                        </td>
                        <td width="50%" align="left" valign="top">
                            <div class="label">Teléfono</div>
                            <div class="value">{{ $user->telefono ?? 'Sin teléfono' }}</div>
                        </td>
                    </tr>
                </table>

                <!-- Desglose de Cortes -->
                @if($saldoAnterior > 0)
                <table class="client-box" width="100%" cellpadding="0" cellspacing="0" style="background-color: #fff7ed; border-color: #ffedd5;">
                    <tr>
                        <td width="50%" align="left">
                            <div class="label" style="color: #c2410c;">Corte Anterior (Vencido)</div>
                            <div class="value" style="color: #9a3412;">${{ number_format($saldoAnterior, 2) }}</div>
                        </td>
                        <td width="50%" align="left">
                            <div class="label" style="color: #ea580c;">Corte Actual (Quincena)</div>
                            <div class="value" style="color: #c2410c;">${{ number_format($saldoActual, 2) }}</div>
                        </td>
                    </tr>
                </table>
                @endif

                <!-- Detalle de Compras -->
                <table class="details-table" width="100%" cellpadding="0" cellspacing="0">
                    <thead>
                        <tr>
                            <th align="left" width="45%">Producto / Artículo</th>
                            <th align="center" width="15%">Cantidad</th>
                            <th align="right" width="20%">Precio Unit.</th>
                            <th align="right" width="20%">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ventasPorCubrir as $venta)
                            <tr>
                                <td align="left" valign="middle">
                                    <strong style="font-size: 12px; color: #0f172a;">{{ $venta->articulo->nombre ?? 'Artículo' }}</strong>
                                    @if($venta->descripcion)
                                        <div style="font-size: 10px; color: #64748b; margin-top: 1px;">{{ $venta->descripcion }}</div>
                                    @endif
                                </td>
                                <td align="center" valign="middle">
                                    <span style="font-weight: 700; color: #334155;">{{ $venta->cantidad }}x</span>
                                </td>
                                <td align="right" valign="middle" style="color: #475569;">
                                    ${{ number_format($venta->precio_venta, 2) }}
                                </td>
                                <td align="right" valign="middle" style="font-weight: 800; color: #0f172a;">
                                    ${{ number_format($venta->total_venta, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" align="center" style="padding: 16px; color: #64748b;">
                                    No hay detalle de compras pendientes registrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Resumen Total -->
                <table class="total-box" align="right" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="left" valign="middle"><strong style="font-size: 12px; color: #991b1b;">Saldo a Cubrir:</strong></td>
                        <td align="right" valign="middle" class="total-amount">${{ number_format($saldo, 2) }}</td>
                    </tr>
                </table>
                <div style="clear: both;"></div>

                <!-- Datos de Pago -->
                <div class="payment-info">
                    <strong style="font-size: 11px; color: #166534; text-transform: uppercase;">💳 Datos para Pago:</strong>
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 6px;">
                        <tr>
                            <td width="50%" valign="top" style="font-size: 11px;">
                                <strong style="color: #15803d;">BBVA:</strong><br>
                                Cuenta: <strong>158 086 7512</strong><br>
                                CLABE: <strong>012 650 01580867512 5</strong>
                            </td>
                            <td width="50%" valign="top" style="font-size: 11px;">
                                <strong style="color: #15803d;">Mercado Pago:</strong><br>
                                CLABE: <strong>722969010384935035</strong>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Pie de página -->
                <div class="footer">
                    <p style="margin: 0; font-weight: 600;">Favor de enviar su comprobante de pago a este número de WhatsApp.</p>
                    <p style="margin: 2px 0 0 0; color: #0f172a; font-weight: 800;">El Bajón</p>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
