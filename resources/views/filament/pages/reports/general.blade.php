<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Informe General de Ventas</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
        }
        th { 
            background-color: #f4f4f4; 
        }
        .total { 
            font-weight: bold; 
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .summary {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Informe General de Ventas</h1>
        <p>Período: {{ $fecha_inicio->format('d/m/Y') }} - {{ $fecha_fin->format('d/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Área</th>
                <th>Total Ventas</th>
                <th>Cantidad Operaciones</th>
                <th>Firmas Pendientes</th>
            </tr>
        </thead>
        <tbody>
            @php
                $ventasPorCliente = $ventas->groupBy('client_id');
            @endphp
            
            @foreach($ventasPorCliente as $clientId => $ventasCliente)
                <tr>
                    <td>{{ $ventasCliente->first()->client->nombre_completo }}</td>
                    <td>{{ $ventasCliente->first()->client->area }}</td>
                    <td>${{ number_format($ventasCliente->sum('precio'), 2) }}</td>
                    <td>{{ $ventasCliente->count() }}</td>
                    <td>{{ $ventasCliente->where('firma_validada', false)->count() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <h3>Resumen General</h3>
        <p><strong>Total ventas del período:</strong> ${{ number_format($ventas->sum('precio'), 2) }}</p>
        <p><strong>Total de operaciones:</strong> {{ $ventas->count() }}</p>
        <p><strong>Firmas pendientes:</strong> {{ $ventas->where('firma_validada', false)->count() }}</p>
        <p><strong>Clientes únicos:</strong> {{ $ventasPorCliente->count() }}</p>
    </div>
</body>
</html>