{{-- Se incluye en los 3 documentos: especificacion.blade.php la usa sola;
     especificacion-mano-obra y especificacion-materiales la anteponen a su tabla de costos. --}}
<h2>Especificación del trabajo a realizar</h2>

@if($especificacion->isEmpty())
    <p class="nota">No se han registrado ítems de especificación para esta cotización.</p>
@else
    @php $agrupado = $especificacion->groupBy(fn ($i) => $i->categoria ?: 'General'); @endphp
    @foreach($agrupado as $categoria => $items)
        <p class="bold" style="margin-top: 10px; margin-bottom: 4px;">{{ $categoria }}</p>
        <table>
            <tbody>
                @foreach($items as $item)
                <tr><td>{{ $item->descripcion }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
@endif
