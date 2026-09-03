{{-- Las filas combinadas reproducen las divisiones asimétricas del formato:
     foto alta a la izquierda, huella arriba a la derecha y firma corrida bajo
     las dos columnas derechas. --}}
<table class="tarjeta">
    <tr class="fila-codigo">
        <td class="celda-numero">{{ $tarjeta['numero'] }}</td>
        <td class="celda-barras">
            <div style="padding: 1px 3px !important;">
                <img class="barras" src="{{ $tarjeta['barras'] }}" alt="Código de barras del documento">
            </div>
        </td>
        <td class="celda-huella" rowspan="3">
            <div class="rotulo">HUELLA<br>DACTILAR</div>
        </td>
    </tr>
    <tr class="fila-documento">
        <td class="celda-documento">{{ $tarjeta['documento'] }}</td>
        <td class="celda-datos" rowspan="2">
            <div class="nombre">{{ $tarjeta['nombre'] }}</div>
            <div class="procedencia">{{ $tarjeta['procedencia'] }}</div>
        </td>
    </tr>
    <tr class="fila-foto">
        <td class="celda-foto" rowspan="2">
            @if ($tarjeta['foto'] !== null)
                <img class="foto" src="{{ $tarjeta['foto'] }}" alt="Fotografía del postulante">
            @else
                <span class="sin-foto">SIN FOTO</span>
            @endif
        </td>
    </tr>
    <tr class="fila-firma">
        <td class="celda-firma" colspan="2">
            <span class="rotulo-firma">FIRMA</span>
        </td>
    </tr>
</table>
