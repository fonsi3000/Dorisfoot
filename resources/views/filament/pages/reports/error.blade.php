<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Error en el Informe</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px;
            text-align: center;
        }
        .error-message {
            color: red;
            padding: 20px;
            margin: 20px;
            border: 1px solid red;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="error-message">
        <h2>Error al Generar el Informe</h2>
        <p>{{ $mensaje }}</p>
    </div>
</body>
</html>