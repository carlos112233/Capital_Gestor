<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Pago - EL BAJÓN</title>
    <style>
        @page {
            margin: 20px 25px;
        }
        * {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif !important;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif !important;
            color: #000000;
            margin: 0;
            padding: 0;
            font-size: 12px;
            line-height: 1.3;
            background-color: #ffffff;
        }
        .header-table {
            width: 100%;
            margin-bottom: 12px;
            border-bottom: 1.5px solid #cbd5e1;
            padding-bottom: 8px;
        }
        .logo-box {
            vertical-align: top;
        }
        .logo-img {
            max-height: 50px;
            width: auto;
        }
        .header-right {
            text-align: right;
            vertical-align: top;
        }
        .brand-title {
            font-size: 22px;
            font-weight: 800;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-subtitle {
            font-size: 14px;
            font-weight: 700;
            color: #000000;
            margin-top: 1px;
        }
        .date-text {
            font-size: 11px;
            color: #334155;
            margin-top: 2px;
        }
        .grid-table {
            width: 100%;
            margin-bottom: 14px;
            border-spacing: 0;
        }
        .card-box {
            background-color: #f4f7fb;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px 16px;
        }
        .info-row {
            margin-bottom: 5px;
            font-size: 12px;
        }
        .info-label {
            font-weight: 700;
            color: #000000;
        }
        .info-val {
            color: #000000;
        }
        .consumo-card {
            background-color: #f4f7fb;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            text-align: center;
            padding: 14px 10px;
            margin-bottom: 16px;
        }
        .consumo-title {
            font-size: 15px;
            font-weight: 700;
            color: #000000;
        }
        .consumo-amount {
            font-size: 34px;
            font-weight: 900;
            color: #000000;
            margin-top: 4px;
            letter-spacing: -0.5px;
        }
        .table-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
        }
        .table-items th {
            background-color: #d6e2ee;
            color: #000000;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            text-align: center;
        }
        .table-items td {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            font-size: 12px;
            color: #000000;
            background-color: #ffffff;
        }
        .table-total {
            font-weight: 700;
            font-size: 12px;
            background-color: #ffffff !important;
        }
        .footer {
            margin-top: 16px;
            text-align: center;
            font-size: 10px;
            color: #64748b;
        }
    </style>
</head>
<body>

    @php
        $clienteObj = $entrada->cliente ?? $entrada->user;
    @endphp

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td class="logo-box" style="width: 50%;">
                @if(!empty($logoBase64))
                    <img src="data:image/svg+xml;base64,{{ $logoBase64 }}" class="logo-img" alt="Logo EL BAJÓN">
                @else
                    <div class="brand-title">EL BAJÓN</div>
                @endif
            </td>
            <td class="header-right" style="width: 50%;">
                <div class="brand-title">EL BAJÓN</div>
                <div class="doc-subtitle">Comprobante de Pago</div>
                <div class="date-text">Fecha de Emisión: {{ $entrada->created_at ? $entrada->created_at->format('d/m/Y') : now()->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <!-- Client Info -->
    <table class="grid-table">
        <tr>
            <td style="width: 100%; vertical-align: top;">
                <div class="card-box">
                    <div class="info-row">
                        <span class="info-label">Cliente:</span>
                        <span class="info-val">{{ $clienteObj->name ?? 'Cliente General' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Dirección:</span>
                        <span class="info-val">{{ $clienteObj->direccion ?? 'Calle Falsa 123, Colonia Centro' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">RFC:</span>
                        <span class="info-val">{{ $clienteObj->rfc ?? 'GALA900101XYZ' }}</span>
                    </div>
                    <div class="info-row" style="margin-bottom: 0;">
                        <span class="info-label">Teléfono:</span>
                        <span class="info-val">{{ $clienteObj->telefono ?? 'Sin teléfono' }}</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Consumo Card -->
    <div class="consumo-card">
        <div class="consumo-title">Monto Pagado</div>
        <div class="consumo-amount">${{ number_format($entrada->precio_venta, 2) }}</div>
    </div>

    <!-- Table Items -->
    <table class="table-items">
        <thead>
            <tr>
                <th style="width: 15%;">Fecha</th>
                <th style="width: 50%;">Concepto</th>
                <th style="width: 15%;">Referencia</th>
                <th style="width: 20%; text-align: right;">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">{{ $entrada->created_at ? $entrada->created_at->format('d/m/Y') : now()->format('d/m/Y') }}</td>
                <td>
                    {{ $entrada->articulo->nombre ?? 'Abono / Pago' }}
                    @if($entrada->descripcion)
                        <br><span style="font-size: 10px; color: #475569;">({{ $entrada->descripcion }})</span>
                    @endif
                </td>
                <td style="text-align: center;">#{{ $entrada->id }}</td>
                <td style="text-align: right; font-weight: bold;">${{ number_format($entrada->precio_venta, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="table-total" style="text-align: right;">Total Pagado:</td>
                <td class="table-total" style="text-align: right;">${{ number_format($entrada->precio_venta, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Documento oficial emitido por EL BAJÓN © {{ date('Y') }}. Todos los derechos reservados.
    </div>

</body>
</html>
