<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <style>
            @page { margin: 8mm 8mm 10mm; }
            * { box-sizing: border-box; }
            body { color: #111; font-family: Arial, Helvetica, sans-serif; font-size: 8px; margin: 0; }

            .cabecera { min-height: 22mm; position: relative; text-align: center; }
            .isologo { height: 18mm; left: 2mm; position: absolute; top: 0; width: auto; }
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

            .pie { bottom: -6mm; color: #555; font-size: 7px; left: 0; position: fixed; right: 0; text-align: center; }
            .pagina::after { content: counter(page); }
        </style>
    </head>
    <body>
        @foreach ($paginas as $filas)
            <section @if (! $loop->first) style="page-break-before: always;" @endif>
                <header class="cabecera">
                    <img class="isologo" src="{{ public_path('img/isologo-unu.png') }}" alt="Isologo de la Universidad Nacional de Ucayali">
                    <div class="linea-cabecera">{{ $tituloProceso }}</div>
                    <div class="linea-cabecera">{{ mb_strtoupper($modalidadCabecera) }}</div>
                    <div class="linea-cabecera">{{ $ubicacion }}</div>
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

        <footer class="pie">
            {{ $aulaExamen->examen->proceso->codigo_pro }} ·
            {{ $aulaExamen->aula->etiqueta() }} ·
            {{ $total }} postulante(s) · Página <span class="pagina"></span>
        </footer>
    </body>
</html>
