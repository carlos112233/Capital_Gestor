<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago #{{ $entrada->id }} - El Bajón</title>
    <style>
        @page {
            margin: 12mm;
        }
        html, body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
            width: 100%;
            font-size: 13px;
            line-height: 1.4;
            background-color: #ffffff;
        }
        .container {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 20px;
            background: #ffffff;
        }
        .header-table {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 18px;
        }
        .brand {
            font-size: 24px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 1px;
        }
        .subtitle {
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            margin-top: 3px;
        }
        .folio-badge {
            background-color: #e2e8f0;
            color: #0f172a;
            font-size: 12px;
            font-weight: 800;
            padding: 5px 14px;
            border-radius: 20px;
            display: inline-block;
        }
        .date {
            font-size: 11px;
            font-weight: 700;
            color: #334155;
            margin-top: 6px;
        }
        .client-table {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 18px;
        }
        .label {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            color: #94a3b8;
        }
        .value {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            margin-top: 2px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .details-table th {
            background-color: #f1f5f9;
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 10px 12px;
            border-bottom: 1px solid #cbd5e1;
        }
        .details-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .badge-type {
            background-color: #d1fae5;
            color: #065f46;
            font-size: 10px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 12px;
        }
        .total-table {
            width: 250px;
            margin-left: auto;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 18px;
        }
        .total-amount {
            font-size: 22px;
            font-weight: 900;
            color: #059669;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
            border-top: 1px solid #f1f5f9;
            padding-top: 14px;
        }
    </style>
</head>
<body>
    @php
        $clienteObj = $entrada->cliente ?? $entrada->user;
    @endphp
    <div class="container">
        <!-- Encabezado -->
        <table class="header-table" cellspacing="0" cellpadding="0">
            <tr>
                <td valign="middle" align="left">
                    <div class="brand">EL BAJÓN</div>
                    <div class="subtitle">Comprobante de Pago Saldado / Abono</div>
                </td>
                <td valign="middle" align="right">
                    <div class="folio-badge">Folio: #{{ $entrada->id }}</div>
                    <div class="date">Fecha: {{ $entrada->created_at ? $entrada->created_at->format('d/m/Y h:i A') : now()->format('d/m/Y h:i A') }}</div>
                </td>
            </tr>
        </table>

        <!-- Información del Cliente -->
        <table class="client-table" cellspacing="0" cellpadding="0">
            <tr>
                <td width="50%" align="left" valign="top">
                    <div class="label">Cliente</div>
                    <div class="value">{{ $clienteObj->name ?? 'Cliente General' }}</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">{{ $clienteObj->email ?? '' }}</div>
                </td>
                <td width="50%" align="left" valign="top">
                    <div class="label">Teléfono</div>
                    <div class="value">{{ $clienteObj->telefono ?? 'Sin teléfono' }}</div>
                </td>
            </tr>
        </table>

        <!-- Detalle de Pago -->
        <table class="details-table" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th align="left">Concepto de Pago</th>
                    <th align="center">Tipo</th>
                    <th align="right">Monto Pagado</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td align="left">
                        <strong style="font-size: 13px; color: #0f172a;">{{ $entrada->articulo->nombre ?? 'Pago Registrado' }}</strong>
                        @if($entrada->descripcion)
                            <div style="font-size: 11px; color: #64748b; margin-top: 3px;">{{ $entrada->descripcion }}</div>
                        @endif
                    </td>
                    <td align="center">
                        <span class="badge-type">Abono / Pago</span>
                    </td>
                    <td align="right" style="font-size: 15px; font-weight: 800; color: #0f172a;">
                        ${{ number_format($entrada->precio_venta, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Resumen Total -->
        <table class="total-table" cellspacing="0" cellpadding="0">
            <tr>
                <td align="left" valign="middle"><strong style="font-size: 13px;">Monto Recibido:</strong></td>
                <td align="right" valign="middle" class="total-amount">${{ number_format($entrada->precio_venta, 2) }}</td>
            </tr>
        </table>

        <!-- Pie de página -->
        <div class="footer">
            <p style="margin: 0; font-weight: 600;">¡Gracias por su pago!</p>
            <p style="margin: 4px 0 0 0; color: #0f172a; font-weight: 800;">El Bajón</p>
        </div>
    </div>
</body>
</html>
