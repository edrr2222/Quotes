<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('pdf._estilos')
</head>
<body>
    <h1>ESPECIFICACIÓN DEL TRABAJO</h1>
    <p class="subtitulo">{{ $proyecto->nombre }} @if($proyecto->municipio) — {{ $proyecto->municipio }}@if($proyecto->departamento), {{ $proyecto->departamento }}@endif @endif</p>

    <table class="ficha">
        <tr><td>Cliente</td><td>{{ $proyecto->cliente ?? '[Nombre del cliente]' }}</td></tr>
        <tr><td>Ubicación de la obra</td><td>{{ $proyecto->municipio }}@if($proyecto->departamento), {{ $proyecto->departamento }}@endif</td></tr>
        @if($proyecto->descripcion)
        <tr><td>Descripción general</td><td>{{ $proyecto->descripcion }}</td></tr>
        @endif
    </table>

    @include('pdf._especificacion-seccion')

    <p class="nota">Este documento describe el alcance del trabajo, sin valores. La cotización de mano de obra y de materiales se presentan en documentos aparte.</p>
</body>
</html>
