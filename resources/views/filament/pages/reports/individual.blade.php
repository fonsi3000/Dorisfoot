<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Autorización Descuento de Nómina</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px;
            font-size: 12px;
        }
        .header-container {
            margin-bottom: 30px;
            position: relative;
        }
        .logos {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 10px;
        }
        .logo-text {
            font-size: 14px;
            font-weight: bold;
        }
        .document-title-top {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            position: absolute;
            width: 100%;
            top: 0;
            left: 0;
            text-align: center;
        }
        .orange-line {
            border-bottom: 2px solid #ff6b35;
            position: relative;
            margin-top: 40px;
        }
        .header-info {
            position: absolute;
            right: 0;
            top: -40px;
            text-align: right;
            font-size: 12px;
        }
        .header-info p {
            margin: 2px 0;
        }
        .meta-info {
            text-align: right;
            margin: 20px 0;
        }
        .authorization-text {
            margin: 20px 0;
            line-height: 1.5;
            text-align: justify;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
        }
        th, td { 
            border: 1px solid #000; 
            padding: 8px; 
            text-align: left; 
        }
        th { 
            background-color: #f4f4f4; 
        }
        .footer {
            margin-top: 40px;
        }
        .signature-line {
            margin-top: 30px;
            border-top: 1px solid #000;
            width: 200px;
            text-align: center;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header-container">
        {{-- <div class="logos">
            <span class="logo-text">Espumas Medellín</span>
            <span class="logo-text">Espumados del Litoral</span>
        </div> --}}
        <div class="document-title-top">
            AUTORIZACIÓN DESCUENTO DE NÓMINA
        </div>
        {{-- <div class="orange-line">
            <div class="header-info">
                <p>GH.FO.25</p>
                <p>VERSIÓN: 01</p>
                <p>{{ now()->format('d/m/Y') }}</p>
            </div>
        </div> --}}
    </div>

    @php
    $meses = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre'
    ];
    $mes = $meses[now()->month];
    $total = $ventas->sum('precio');
    $cuotaQuincenal = ceil($total / 2);
    $inicioDescuento = now()->addMonth();
    @endphp

    <div class="meta-info">
        <p>Ciudad de Medellín, {{ now()->format('d') }} de {{ $mes }} del {{ now()->format('Y') }}</p>
    </div>

    <p><strong>Señores:</strong><br>
    Espumas Medellín S.A<br>
    Atn. Departamento de Gestión Humana<br>
    Ciudad.</p>

    <div class="authorization-text">
        Autorizo a la empresa <strong>Espumas Medellín S.A</strong> descontar de mi pago de nómina
        siempre y cuando no exceda el límite del 40% del total de descuentos mensuales,
        la suma de <strong>${{ number_format($total, 2) }}</strong>, en cuotas quincenales cada una por valor de
        <strong>$_______</strong>, iniciando en la 1ra ___ o 2da ___ quincena del mes de {{ $meses[$inicioDescuento->month] }}.
        
        <br><br>
        Así mismo autorizo a la empresa en caso de retiro por cualquier motivo; me sea
        descontado de mi liquidación final de prestaciones sociales, salarios, comisiones,
        indemnizaciones, o cualquier otro concepto laboral que me pueda corresponder,
        el saldo adeudado por este concepto.
    </div>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Fecha</th>
                <th>Concepto</th>
                <th>Valor</th>
                <th>Firma</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ventas as $index => $venta)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y h:i:s A') }}</td>
                    <td>{{ $venta->concepto }}</td>
                    <td>${{ number_format($venta->precio, 2) }}</td>
                    <td>{{ $venta->firma_validada ? 'Validada' : 'Pendiente' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Atentamente;</p>
        <div class="">
            <p>Nombre: {{ $cliente->nombre_completo }}</p>
            <p>C.C. {{ $cliente->identificacion }}</p>
            <p>Firma: ______________________________________</p>
        </div>
    </div>
</body>
</html>