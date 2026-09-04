<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <style>
            /* El margen inferior es el que reserva el sitio del pie: al ir en
               position:fixed no ocupa lugar en el flujo, y con un margen corto
               las ultimas filas del listado le quedaban encima. */
            @page { margin: 8mm 10mm 24mm; }
            * { box-sizing: border-box; }
            body { color: #111; font-family: Arial, Helvetica, sans-serif; font-size: 7px; margin: 0; }
            .cabecera { min-height: 29mm; position: relative; text-align: center; }
            .isologo { height: 24mm; left: 8mm; position: absolute; top: 0; width: auto; }
            .institucion { font-size: 14px; font-weight: bold; line-height: 1.15; margin-bottom: 2px; }
            .linea { font-size: 9px; font-weight: bold; line-height: 1.2; }
            .titulo { font-size: 11px; font-weight: bold; margin: 4px 0 2px; text-align: center; text-transform: uppercase; }
            .fecha { margin-bottom: 3px; text-align: right; }
            table.listado { border-collapse: collapse; table-layout: fixed; width: 100%; }
            .listado thead { display: table-header-group; }
            .listado th { border-bottom: 1px solid #222; border-top: 1px solid #222; font-size: 6.8px; font-weight: normal; line-height: 1.1; padding: 4px 2px; text-align: center; text-transform: uppercase; }
            .listado td { font-size: 6.8px; line-height: 1.1; padding: 2px; vertical-align: middle; }
            .numero { text-align: center; width: 4%; }
            .orden { text-align: center; width: 8%; }
            .documento { text-align: center; width: 10%; }
            .postulante { width: 31%; }
            .carrera { width: 29%; }
            .puntaje { text-align: right; width: 9%; }
            .estado { text-align: center; width: 9%; }
            .pie { border-top: 1px solid #555; bottom: -20mm; color: #555; font-size: 7px; height: 16mm; left: 0; padding-top: 1.5mm; position: fixed; right: 0; }
            .leyenda { line-height: 1.25; }
            .paginacion { left: 0; position: absolute; right: 0; text-align: center; top: 1mm; }
            .pagina::after { content: counter(page); }
        </style>
    </head>
    <body>
        @foreach ($secciones as $seccion)
            <section class="seccion" @if (! $loop->last) style="page-break-after: always;" @endif>
                <header class="cabecera">
                    <img class="isologo" src="{{ public_path('img/isologo-unu.png') }}" alt="Isologo de la Universidad Nacional de Ucayali">
                    <div class="institucion">UNIVERSIDAD NACIONAL DE UCAYALI</div>
                    <div class="linea">VICERRECTORADO ACADÉMICO</div>
                    <div class="linea">DIRECCIÓN DE ADMISIÓN</div>
                    <div class="linea">{{ $examen->proceso->tituloConvocatoria() }}</div>
                    <div class="linea">{{ mb_strtoupper($seccion['modalidades'] ?: $examen->nombre_exa) }}</div>
                    <div class="linea">{{ $seccion['ubicacion'] }}</div>
                </header>

                <div class="titulo">{{ $tituloListado }}</div>
                <div class="fecha">{{ mb_convert_case($seccion['ubicacion'], MB_CASE_TITLE, 'UTF-8') }}, {{ ($examen->fecha_exa ?? now())->translatedFormat('d \d\e F \d\e Y') }}</div>

                <table class="listado">
                    <thead>
                        <tr>
                            <th class="numero">N°</th>
                            <th class="orden">Orden general</th>
                            <th class="documento">Código examen</th>
                            <th class="postulante">Apellidos y nombres</th>
                            <th class="carrera">Carrera profesional</th>
                            <th class="puntaje">Puntaje total</th>
                            <th class="estado">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($seccion['resultados'] as $indice => $resultado)
                            <tr>
                                <td class="numero">{{ $esPorCarrera ? ($resultado->orden_carrera_res ?? '—') : $indice + 1 }}</td>
                                <td class="orden">{{ $resultado->orden_general_res ?? '—' }}</td>
                                <td class="documento">{{ $resultado->postulante->documento_exp }}</td>
                                <td class="postulante">{{ $resultado->postulante->nombre_exp }}</td>
                                <td class="carrera">{{ $resultado->postulante->inscripcion->sede->abreviatura() }} - {{ $resultado->postulante->inscripcion->carrera->nombre_car }}</td>
                                <td class="puntaje">{{ $resultado->puntaje_res === null ? '—' : number_format((float) $resultado->puntaje_res, 4) }}</td>
                                <td class="estado">{{ mb_strtoupper($resultado->estado_res->etiqueta()) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endforeach

        <footer class="pie">
            <div class="leyenda">
                <div>RGA =&gt; REGLAMENTO GENERAL DE ADMISIÓN DE PREGRADO</div>
                <div>NSP =&gt; NO SE PRESENTÓ</div>
                <div>SCP-C =&gt; SEDE CORONEL PORTILLO - CALLERÍA</div>
            </div>
            <div class="paginacion">Página <span class="pagina"></span></div>
        </footer>
    </body>
</html>
