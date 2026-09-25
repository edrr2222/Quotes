<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('pdf._estilos')
</head>
<body>
    <h1>ESPECIFICACIÓN Y COTIZACIÓN DE MATERIALES</h1>
    <p class="subtitulo">{{ $proyecto->nombre }} @if($proyecto->municipio) — {{ $proyecto->municipio }}@if($proyecto->departamento), {{ $proyecto->departamento }}@endif @endif</p>

    @include('pdf._especificacion-seccion')

    @php
        $categorias = ['obra_civil' => '1. Obra civil', 'hidraulico' => '2. Sistema hidráulico', 'electrico' => '3. Sistema eléctrico'];
    @endphp

    @foreach($categorias as $clave => $titulo)
        <h2>{{ $titulo }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th class="center">Und.</th>
                    <th class="center">Cant.</th>
                    <th class="right">Vr. Unitario</th>
                    <th class="right">Vr. Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($resumen['materiales']['items'][$clave] as $item)
                <tr>
                    <td>{{ $item['descripcion'] }}</td>
                    <td class="center">{{ $item['unidad'] }}</td>
                    <td class="center">{{ rtrim(rtrim(number_format($item['cantidad'], 3, ',', '.'), '0'), ',') }}</td>
                    <td class="right">${{ number_format($item['valor_unitario'], 0, ',', '.') }}</td>
                    <td class="right">${{ number_format($item['valor_total'], 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="center">Sin ítems registrados</td></tr>
                @endforelse
                <tr class="fila-subtotal">
                    <td colspan="4" class="right">Subtotal</td>
                    <td class="right">${{ number_format($resumen['materiales']['subtotales'][$clave], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach

    <table style="margin-top: 14px;">
        <tr><td>Obra civil</td><td class="right">${{ number_format($resumen['materiales']['subtotales']['obra_civil'], 0, ',', '.') }}</td></tr>
        <tr><td>Sistema hidráulico</td><td class="right">${{ number_format($resumen['materiales']['subtotales']['hidraulico'], 0, ',', '.') }}</td></tr>
        <tr><td>Sistema eléctrico</td><td class="right">${{ number_format($resumen['materiales']['subtotales']['electrico'], 0, ',', '.') }}</td></tr>
        <tr class="fila-total"><td>TOTAL MATERIALES</td><td class="right">${{ number_format($resumen['materiales']['total_materiales'], 0, ',', '.') }}</td></tr>
    </table>

    <p class="nota">Este total corresponde únicamente a materiales y equipos; no incluye mano de obra, transporte, AIU ni IVA.</p>
</body>
</html>
