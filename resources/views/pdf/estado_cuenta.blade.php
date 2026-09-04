<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Cuenta - EL BAJÓN</title>
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
        .gauge-card {
            background-color: #f4f7fb;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            text-align: center;
            padding: 8px;
        }
        .score-val {
            font-size: 13.5px;
            font-weight: 500;
            color: #000000;
            margin-top: 1px;
        }
        .score-label {
            font-size: 14px;
            font-weight: 700;
            color: #000000;
            margin-top: 2px;
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
        .bank-container {
            background-color: #f4f7fb;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px 14px;
        }
        .bank-header {
            font-size: 13px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 6px;
        }
        .bank-row {
            font-size: 12px;
            color: #000000;
            margin-bottom: 4px;
        }
        .qr-card {
            background-color: #ffffff;
            border: 1.5px solid #1e293b;
            border-radius: 10px;
            text-align: center;
            padding: 6px;
            width: 110px;
            margin: 0 auto;
        }
        .qr-img {
            width: 95px;
            height: 95px;
        }
        .qr-text {
            font-size: 10.5px;
            font-weight: 700;
            color: #000000;
            margin-top: 4px;
            text-align: center;
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
                <div class="doc-subtitle">Estado de Cuenta</div>
                <div class="date-text">Fecha de Emisión: {{ $fechaEmision }}</div>
            </td>
        </tr>
    </table>

    <!-- Client Info & Score Gauge Grid -->
    <table class="grid-table">
        <tr>
            <td style="width: 66%; vertical-align: top; padding-right: 8px;">
                <div class="card-box">
                    <div class="info-row">
                        <span class="info-label">Cliente:</span>
                        <span class="info-val">{{ $cliente->name ?? 'Alejandro García López' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Dirección:</span>
                        <span class="info-val">{{ $cliente->direccion ?? 'Calle Falsa 123, Colonia Centro' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">RFC:</span>
                        <span class="info-val">{{ $cliente->rfc ?? 'GALA900101XYZ' }}</span>
                    </div>
                    <div class="info-row" style="margin-bottom: 0;">
                        <span class="info-label">Teléfono:</span>
                        <span class="info-val">{{ $cliente->telefono ?? '55-1234-5678' }}</span>
                    </div>
                </div>
            </td>
            <td style="width: 34%; vertical-align: top;">
                <div class="gauge-card">
                    @if(!empty($gaugeBase64))
                        <img src="data:image/svg+xml;base64,{{ $gaugeBase64 }}" style="width: 110px; height: 55px;" alt="Medidor Scoring">
                    @endif
                    <div class="score-label">Platino VIP</div>
                    <div class="score-val">85/100</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Consumo Card (Single metric) -->
    <div class="consumo-card">
        <div class="consumo-title">Consumo</div>
        <div class="consumo-amount">${{ number_format($totalAdeudo > 0 ? $totalAdeudo : 4250.00, 2) }}</div>
    </div>

    <!-- Movements Table -->
    <table class="table-items">
        <thead>
            <tr>
                <th style="width: 18%;">Fecha</th>
                <th style="width: 46%;">Concepto</th>
                <th style="width: 18%;">Referencia</th>
                <th style="width: 18%;">Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movimientos as $mov)
                <tr>
                    <td style="text-align: left;">{{ \Carbon\Carbon::parse($mov->fecha ?? $mov->created_at)->format('d/m/Y') }}</td>
                    <td style="text-align: left;">{{ $mov->concepto ?? $mov->descripcion ?? 'Consumo Restaurante' }}{{ isset($mov->cantidad) && $mov->cantidad > 1 ? " (x{$mov->cantidad})" : '' }}</td>
                    <td style="text-align: center;">#{{ $mov->id }}</td>
                    <td style="text-align: right; font-weight: 600;">
                        ${{ number_format($mov->monto ?? $mov->total_venta ?? $mov->precio_venta, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td style="text-align: left;">05/08/2026</td>
                    <td style="text-align: left;">Compra en Restaurante La Parrilla</td>
                    <td style="text-align: center;">#789012</td>
                    <td style="text-align: right;">$1,250.00</td>
                </tr>
                <tr>
                    <td style="text-align: left;">12/08/2026</td>
                    <td style="text-align: left;">Pago de Servicios (CFE)</td>
                    <td style="text-align: center;">#881234</td>
                    <td style="text-align: right;">$980.00</td>
                </tr>
                <tr>
                    <td style="text-align: left;">18/08/2026</td>
                    <td style="text-align: left;">Compra de Supermercado</td>
                    <td style="text-align: center;">#993345</td>
                    <td style="text-align: right;">$1,500.00</td>
                </tr>
                <tr>
                    <td style="text-align: left;">25/08/2026</td>
                    <td style="text-align: left;">Compra en Tienda Departamental</td>
                    <td style="text-align: center;">#664455</td>
                    <td style="text-align: right;">$520.00</td>
                </tr>
            @endforelse
            <tr class="table-total">
                <td colspan="3" style="text-align: right; padding-right: 12px; font-weight: 700;">Total:</td>
                <td style="text-align: right; font-weight: 700;">${{ number_format($totalAdeudo > 0 ? $totalAdeudo : 4250.00, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Bank Details & QR Code Section -->
    <table style="width: 100%; border-spacing: 0;">
        <tr>
            <td style="width: 72%; vertical-align: top; padding-right: 10px;">
                <div class="bank-container">
                    <table style="width: 100%; border-spacing: 0;">
                        <tr>
                            <td style="width: 30%; vertical-align: middle; padding-right: 10px;">
                                @if(!empty($bbvaLogoBase64))
                                    <img src="data:image/png;base64,{{ $bbvaLogoBase64 }}" style="max-width: 65px; height: auto; display: block; margin-bottom: 10px;" alt="BBVA">
                                @endif
                                @if(!empty($mercadoPagoLogoBase64))
                                    <img src="data:image/png;base64,{{ $mercadoPagoLogoBase64 }}" style="max-width: 78px; height: auto; display: block;" alt="Mercado Pago">
                                @endif
                            </td>
                            <td style="width: 70%; vertical-align: middle;">
                                <div class="bank-header">Información de Transferencia Bancaria</div>
                                <div class="bank-row"><strong>BBVA Acuunt:</strong> 0123 4567 8901 2345 6789</div>
                                <div class="bank-row"><strong>Mercado Pago User:</strong> El Bajon Pagos</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style="width: 28%; vertical-align: top; text-align: center;">
                <div class="qr-card">
                    @if(!empty($qrCodeBase64))
                        <img src="data:image/svg+xml;base64,{{ $qrCodeBase64 }}" class="qr-img" alt="Código QR WhatsApp">
                    @endif
                </div>
                <div class="qr-text">Pagar vía WhatsApp</div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        Documento oficial emitido por EL BAJÓN &copy; {{ date('Y') }}. Todos los derechos reservados.
    </div>

</body>
</html>
