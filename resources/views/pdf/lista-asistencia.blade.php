<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <style>
            /* El margen inferior reserva el sitio del pie: al ir en
               position:fixed no ocupa lugar en el flujo. */
            @page { margin: 8mm 8mm 32mm; }
            * { box-sizing: border-box; }
            body { color: #111; font-family: Arial, Helvetica, sans-serif; font-size: 8px; margin: 0; }

            .cabecera { min-height: 24mm; position: relative; text-align: center; }
            .isologo { height: 18mm; left: 2mm; position: absolute; top: 0; width: auto; }
            .institucion { font-size: 12px; font-weight: bold; line-height: 1.2; margin-bottom: 1px; }
            .linea-cabecera { font-size: 10px; font-weight: bold; line-height: 1.3; }
            .titulo { font-size: 12px; font-weight: bold; margin: 6px 0 4px; text-align: center; text-transform: uppercase; }
            .fecha { font-size: 9px; margin-bottom: 4px; text-align: right; }

            .resumen { border-collapse: collapse; margin-bottom: 5px; table-layout: fixed; width: 100%; }
            .resumen th { background: #4472C4; border: 0.7px solid #2F5597; color: #fff; font-size: 8.5px; font-weight: bold; padding: 3px; text-align: center; }
            .resumen td { border: 0.7px solid #2F5597; font-size: 8.5px; padding: 3px; text-align: center; }

            .rejilla { border-collapse: separate; border-spacing: 3px 2px; table-layout: fixed; width: 100%; }
            .rejilla > tr > td { padding: 0; vertical-align: top; width: 50%; }
            .rejilla > tbody > tr { page-break-inside: avoid; }

            .tarjeta { border-collapse: collapse; table-layout: fixed; width: 100%; }
            .tarjeta td { border: 0.7px solid #222; padding: 2px; vertical-align: top; }
            .fila-codigo { height: 7.5mm; }
            .fila-documento { height: 6mm; }
            .fila-foto { height: 7mm; }
            .fila-firma { height: 16mm; }

            /* Columna izquierda: numero, documento y foto, uno bajo el otro. */
            .celda-numero { font-size: 12px; font-weight: bold; text-align: center; vertical-align: middle !important; width: 22%; }
            .celda-documento { font-size: 9px; font-weight: bold; letter-spacing: .3px; text-align: center; vertical-align: middle !important; }
            .celda-foto { text-align: center; vertical-align: middle !important; }
            .foto { max-height: 22mm; max-width: 95%; width: auto; }
            .sin-foto { color: #999; font-size: 6.5px; }

            /* Columna central: barras arriba, datos al medio, firma abajo. */
            .celda-barras { padding: 1px 3px !important; text-align: center !important; vertical-align: middle !important; width: 60%; }
            .barras { height: 6mm; width: 50%; }
            .celda-datos { padding: 3px 4px !important; }
            .nombre { font-size: 8.5px; font-weight: bold; line-height: 1.15; }
            .procedencia { color: #0563C1; font-size: 7.2px; line-height: 1.15; padding-top: 2px; }
            .celda-firma { padding: 2px 4px !important; vertical-align: bottom !important; }
            .rotulo-firma { font-size: 8px; font-weight: bold; }

            .celda-huella { padding-bottom: 2px !important; text-align: end; vertical-align: bottom !important; width: 18%; }
            .rotulo { color: #777; font-size: 6.5px; font-weight: normal; line-height: 1.1; }

            /* Observaciones y pie van juntos en un solo bloque fijo al fondo:
               así cierran todas las hojas por igual y quedan pegados entre sí,
               sin flotar a media página cuando la última lleva pocas tarjetas.
               La caja va en flujo normal dentro del bloque: una tabla con
               position:fixed propia DomPDF la colapsa a la altura del rótulo. */
            .pie { bottom: -29mm; left: 0; position: fixed; right: 0; }
            .observaciones { border: 0.7px solid #222; border-collapse: collapse; table-layout: fixed; width: 100%; }
            .observaciones td { padding: 1.5mm 3mm 1mm; }
            .rotulo-observaciones { font-size: 9px; font-weight: bold; margin-bottom: 1mm; }
            .renglon { border-bottom: 0.6px solid #222; height: 4.5mm; }

            .pie-linea { border-top: 0.7px solid #222; color: #333; font-size: 7.5px; margin-top: 2mm; padding-top: 1.2mm; }
            .pie-tabla { table-layout: fixed; width: 100%; }
            .pie-tabla td { padding: 0; }
            .pie-izq { text-align: left; }
            .pie-centro { text-align: center; }
            .pie-der { text-align: right; }
            .pagina::after { content: counter(page); }
        </style>
    </head>
    <body>
        @foreach ($paginas as $filas)
            <section @if (! $loop->first) style="page-break-before: always;" @endif>
                <header class="cabecera">
                    @include('pdf.partials.cabecera-institucional', [
                        'modalidad' => $modalidadCabecera,
                        'codigoProceso' => $codigoProceso,
                    ])
                </header>

                <div class="titulo">Lista de asistencia de postulantes</div>

                <div class="fecha">
                    {{ mb_convert_case($ubicacion, MB_CASE_TITLE, 'UTF-8') }},
                    {{ $fecha->translatedFormat('d \d\e F \d\e Y') }}
                </div>

                <table class="resumen">
                    <tr>
                        <th>N° Pabellón</th>
                        <th>N° Aula</th>
                        <th>N° Postulantes</th>
                    </tr>
                    <tr>
                        <td>{{ $aulaExamen->aula->numeroDePabellon() ?? '—' }}</td>
                        <td>{{ $aulaExamen->aula->numeroDeAula() }}</td>
                        <td>{{ $total }}</td>
                    </tr>
                </table>

                <table class="rejilla">
                    @foreach ($filas as $fila)
                        <tr>
                            @foreach (['izquierda', 'derecha'] as $lado)
                                <td>
                                    @if ($fila[$lado] !== null)
                                        @include('pdf.partials.tarjeta-asistencia', ['tarjeta' => $fila[$lado]])
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </table>
            </section>
        @endforeach

        {{-- Bloque fijo del fondo: DomPDF lo repite en cada hoja. El total de
             páginas sale del número de secciones porque cada una es una hoja;
             DomPDF no expone un contador de páginas totales en CSS. --}}
        <footer class="pie">
            <table class="observaciones">
                <tr>
                    <td>
                        <div class="rotulo-observaciones">OBSERVACIONES</div>
                        <div class="renglon"></div>
                        <div class="renglon"></div>
                        <div class="renglon"></div>
                    </td>
                </tr>
            </table>

            <div class="pie-linea">
                <table class="pie-tabla">
                    <tr>
                        <td class="pie-izq">
                            {{ $aulaExamen->aula->sede->abreviatura() }} =&gt;
                            {{ mb_strtoupper($aulaExamen->aula->sede->nombre_sed) }}
                        </td>
                        <td class="pie-centro">
                            Página <span class="pagina"></span> de {{ $paginas->count() }}
                        </td>
                        <td class="pie-der">{{ $codigoFormato }}</td>
                    </tr>
                </table>
            </div>
        </footer>
    </body>
</html>
