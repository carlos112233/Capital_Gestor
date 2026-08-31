<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago #{{ $entrada->id }} - El Bajón</title>
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
            background-color: #e2e8f0;
            color: #0f172a;
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
        .badge-type {
            background-color: #d1fae5;
            color: #065f46;
            font-size: 10px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 10px;
        }
        .total-box {
            width: 230px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            margin-left: auto;
            margin-bottom: 16px;
        }
        .total-amount {
            font-size: 18px;
            font-weight: 900;
            color: #059669;
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
        $clienteObj = $entrada->cliente ?? $entrada->user;
    @endphp
    <table width="100%" cellpadding="0" cellspacing="0" style="width: 100%;">
        <tr>
            <td class="outer-card">
                <!-- Encabezado -->
                <table class="header-box" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="left" valign="middle" width="60%">
                            <div class="brand">EL BAJÓN</div>
                            <div class="subtitle">Comprobante de Pago Saldado / Abono</div>
                        </td>
                        <td align="right" valign="middle" width="40%">
                            <div class="folio-badge">Folio: #{{ $entrada->id }}</div>
                            <div class="date">Fecha: {{ $entrada->created_at ? $entrada->created_at->format('d/m/Y h:i A') : now()->format('d/m/Y h:i A') }}</div>
                        </td>
                    </tr>
                </table>

                <!-- Información del Cliente -->
                <table class="client-box" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="50%" align="left" valign="top">
                            <div class="label">Cliente</div>
                            <div class="value">{{ $clienteObj->name ?? 'Cliente General' }}</div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 1px;">{{ $clienteObj->email ?? '' }}</div>
                        </td>
                        <td width="50%" align="left" valign="top">
                            <div class="label">Teléfono</div>
                            <div class="value">{{ $clienteObj->telefono ?? 'Sin teléfono' }}</div>
                        </td>
                    </tr>
                </table>

                <!-- Detalle de Pago -->
                <table class="details-table" width="100%" cellpadding="0" cellspacing="0">
                    <thead>
                        <tr>
                            <th align="left" width="50%">Concepto de Pago</th>
                            <th align="center" width="25%">Tipo</th>
                            <th align="right" width="25%">Monto Pagado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td align="left" valign="middle">
                                <strong style="font-size: 12px; color: #0f172a;">{{ $entrada->articulo->nombre ?? 'Pago Registrado' }}</strong>
                                @if($entrada->descripcion)
                                    <div style="font-size: 10px; color: #64748b; margin-top: 2px;">{{ $entrada->descripcion }}</div>
                                @endif
                            </td>
                            <td align="center" valign="middle">
                                <span class="badge-type">Abono / Pago</span>
                            </td>
                            <td align="right" valign="middle" style="font-size: 14px; font-weight: 800; color: #0f172a;">
                                ${{ number_format($entrada->precio_venta, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Resumen Total -->
                <table class="total-box" align="right" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="left" valign="middle"><strong style="font-size: 12px;">Monto Recibido:</strong></td>
                        <td align="right" valign="middle" class="total-amount">${{ number_format($entrada->precio_venta, 2) }}</td>
                    </tr>
                </table>
                <div style="clear: both;"></div>

                <!-- Pie de página -->
                <div class="footer">
                    <p style="margin: 0; font-weight: 600;">¡Gracias por su pago!</p>
                    <p style="margin: 2px 0 0 0; color: #0f172a; font-weight: 800;">El Bajón</p>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
