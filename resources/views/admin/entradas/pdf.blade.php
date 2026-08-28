<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago #{{ $entrada->id }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 20px;
            font-size: 13px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .logo-title {
            font-size: 26px;
            font-weight: 900;
            color: #0f172a;
            margin: 0;
            letter-spacing: 1px;
        }
        .subtitle {
            font-size: 12px;
            color: #475569;
            font-weight: bold;
            margin-top: 3px;
        }
        .folio-badge {
            background-color: #e2e8f0;
            color: #0f172a;
            padding: 5px 12px;
            font-weight: bold;
            font-size: 12px;
            border-radius: 12px;
            display: inline-block;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
        }
        .info-label {
            font-size: 10px;
            font-weight: bold;
            color: #94a3b8;
            text-transform: uppercase;
        }
        .info-value {
            font-size: 13px;
            font-weight: bold;
            color: #1e293b;
            margin-top: 2px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .items-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #cbd5e1;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }
        .total-wrapper {
            width: 100%;
            margin-bottom: 30px;
        }
        .total-box {
            float: right;
            width: 240px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px 15px;
            text-align: right;
        }
        .total-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }
        .total-amount {
            font-size: 22px;
            font-weight: 900;
            color: #059669;
            margin-top: 4px;
        }
        .footer {
            clear: both;
            margin-top: 40px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="vertical-align: top;">
                <div class="logo-title">EL BAJÓN</div>
                <div class="subtitle">Comprobante de Pago Saldado / Abono</div>
            </td>
            <td style="text-align: right; vertical-align: top;">
                <div class="folio-badge">Folio: #{{ $entrada->id }}</div>
                <div style="font-size: 11px; color: #0f172a; font-weight: bold; margin-top: 6px;">
                    Fecha: {{ $entrada->created_at ? $entrada->created_at->format('d/m/Y h:i A') : now()->format('d/m/Y h:i A') }}
                </div>
            </td>
        </tr>
    </table>

    @php
        $clienteObj = $entrada->cliente ?? $entrada->user;
    @endphp
    <div class="info-box">
        <table class="info-table">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div class="info-label">Cliente</div>
                    <div class="info-value">{{ $clienteObj->name ?? 'Cliente General' }}</div>
                    <div style="font-size: 11px; color: #64748b;">{{ $clienteObj->email ?? '' }}</div>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <div class="info-label">Teléfono</div>
                    <div class="info-value">{{ $clienteObj->telefono ?? 'Sin teléfono' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Concepto de Pago</th>
                <th style="text-align: center;">Tipo</th>
                <th style="text-align: right;">Monto Pagado</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ $entrada->articulo->nombre ?? 'Pago Registrado' }}</strong>
                    @if($entrada->descripcion)
                        <br><span style="font-size: 11px; color: #64748b;">{{ $entrada->descripcion }}</span>
                    @endif
                </td>
                <td style="text-align: center; color: #059669; font-weight: bold;">
                    Abono / Pago
                </td>
                <td style="text-align: right; font-weight: bold; font-size: 14px;">
                    ${{ number_format($entrada->precio_venta, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="total-wrapper">
        <div class="total-box">
            <div class="total-title">Monto Recibido:</div>
            <div class="total-amount">${{ number_format($entrada->precio_venta, 2) }}</div>
        </div>
    </div>

    <div class="footer">
        <p style="margin: 0; font-weight: bold;">¡Gracias por su pago!</p>
        <p style="margin: 3px 0 0 0;">El Bajón</p>
    </div>
</body>
</html>
