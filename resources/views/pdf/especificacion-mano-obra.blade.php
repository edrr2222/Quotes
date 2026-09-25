<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('pdf._estilos')
</head>
<body>
    <h1>ESPECIFICACIÓN Y COTIZACIÓN DE MANO DE OBRA</h1>
    <p class="subtitulo">{{ $proyecto->nombre }} @if($proyecto->municipio) — {{ $proyecto->municipio }}@if($proyecto->departamento), {{ $proyecto->departamento }}@endif @endif</p>

    @include('pdf._especificacion-seccion')

    <h2>Cuadrilla y costos de mano de obra</h2>
    <table>
        <thead>
            <tr>
                <th>Cargo</th>
                <th class="center">No. personas</th>
                <th class="right">Jornal básico</th>
                <th class="center">Jornales</th>
                <th class="right">ARL / día</th>
                <th class="right">ARL total</th>
                <th class="right">Costo total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($resumen['mano_obra']['items'] as $item)
            <tr>
                <td>{{ $item['cargo'] }}</td>
                <td class="center">{{ $item['numero_personas'] }}</td>
                <td class="right">${{ number_format($item['jornal_basico'], 0, ',', '.') }}</td>
                <td class="center">{{ $item['jornales'] }}</td>
                <td class="right">${{ number_format($item['arl_dia'], 0, ',', '.') }}</td>
                <td class="right">${{ number_format($item['arl_total_por_persona'], 0, ',', '.') }}</td>
                <td class="right">${{ number_format($item['costo_total'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr class="fila-subtotal">
                <td colspan="6" class="right">TOTAL MANO DE OBRA</td>
                <td class="right">${{ number_format($resumen['mano_obra']['total_mano_obra'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="6" class="right">Imprevistos ({{ (int) ($cotizacion->imprevistos_mano_obra_pct * 100) }}%)</td>
                <td class="right">${{ number_format($resumen['mano_obra']['imprevistos'], 0, ',', '.') }}</td>
            </tr>
            <tr class="fila-total">
                <td colspan="6" class="right">TOTAL CON IMPREVISTOS</td>
                <td class="right">${{ number_format($resumen['mano_obra']['total_con_imprevistos'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <p class="nota">El rubro de Imprevistos cubre eventualidades propias de la mano de obra (días adicionales, ajustes de rendimiento, horas extra). El ARL corresponde a la afiliación obligatoria a la Administradora de Riesgos Laborales.</p>
</body>
</html>
