<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <style>
            @page {
                margin: 8mm 12mm 10mm;
            }

            * {
                box-sizing: border-box;
            }

            body {
                color: #111;
                font-family: DejaVu Sans, sans-serif;
                font-size: 8px;
                margin: 0;
            }

            .cabecera {
                min-height: 30mm;
                position: relative;
                text-align: center;
            }

            .isologo {
                height: 25mm;
                left: 8mm;
                position: absolute;
                top: 0;
                width: auto;
            }

            .institucion {
                font-size: 14px;
                font-weight: bold;
                line-height: 1.15;
                margin-bottom: 2px;
            }

            .linea-cabecera {
                font-size: 9.5px;
                font-weight: bold;
                line-height: 1.2;
            }

            .titulo {
                font-size: 11px;
                font-weight: bold;
                margin: 4px 0;
                text-align: center;
                text-transform: uppercase;
            }

            .fecha {
                font-size: 8px;
                margin-bottom: 3px;
                text-align: right;
            }

            .datos-aula {
                border-bottom: 1px solid #222;
                border-top: 1px solid #222;
                margin-bottom: 4px;
                padding: 3px 0;
                width: 100%;
            }

            .datos-aula td {
                border: 0;
                padding: 1px 5px;
            }

            .listado {
                border-collapse: collapse;
                table-layout: fixed;
                width: 100%;
            }

            .listado thead {
                display: table-header-group;
            }

            .listado th {
                border-bottom: 1px solid #222;
                border-top: 1px solid #222;
                font-size: 7.3px;
                line-height: 1.1;
                padding: 4px 3px;
                text-align: center;
                text-transform: uppercase;
                vertical-align: middle;
            }

            .listado td {
                border-bottom: 0.35px solid #aaa;
                font-size: 7.3px;
                height: 11px;
                line-height: 1.1;
                padding: 2.2px 3px;
                vertical-align: middle;
            }

            .listado .col-numero {
                width: 5%;
            }

            .listado .col-documento {
                width: 12%;
            }

            .listado .col-postulante {
                width: 70%;
            }

            .listado .col-asiento {
                width: 13%;
            }

            .centro {
                text-align: center;
            }

            .pie {
                bottom: -9mm;
                color: #555;
                font-size: 7px;
                left: 0;
                position: fixed;
                right: 0;
                text-align: center;
            }

            .pagina::after {
                content: counter(page);
            }
        </style>
    </head>
    <body>
        <header class="cabecera">
            <img class="isologo" src="{{ public_path('img/isologo-unu.png') }}" alt="Isologo de la Universidad Nacional de Ucayali">

            <div class="institucion">UNIVERSIDAD NACIONAL DE UCAYALI</div>
            <div class="linea-cabecera">VICERRECTORADO ACADÉMICO</div>
            <div class="linea-cabecera">DIRECCIÓN DE ADMISIÓN</div>
            <div class="linea-cabecera">{{ $aulaExamen->examen->proceso->tituloConvocatoria() }}</div>
            <div class="linea-cabecera">{{ mb_strtoupper($modalidadCabecera) }}</div>
            <div class="linea-cabecera">{{ $aulaExamen->aula->sede->ubicacionCabecera() }}</div>
        </header>

        <div class="titulo">Padrón de postulantes por aula</div>

        <div class="fecha">
            {{ mb_convert_case($aulaExamen->aula->sede->ubicacionCabecera(), MB_CASE_TITLE, 'UTF-8') }},
            {{ ($aulaExamen->examen->fecha_exa ?? now())->translatedFormat('d \d\e F \d\e Y') }}
        </div>

        <table class="datos-aula">
            <tr>
                <td><strong>Área:</strong> {{ $aulaExamen->area->etiqueta() }}</td>
                <td><strong>Sede:</strong> {{ $aulaExamen->aula->sede->abreviatura() }} - {{ $aulaExamen->aula->sede->nombre_sed }}</td>
            </tr>
            <tr>
                <td><strong>Aula:</strong> {{ $aulaExamen->aula->etiqueta() }}</td>
                <td><strong>Postulantes:</strong> {{ $asignaciones->count() }} de {{ $aulaExamen->capacidad_eau }}</td>
            </tr>
        </table>

        <table class="listado">
            <thead>
                <tr>
                    <th class="col-numero">N°</th>
                    <th class="col-documento">Documento</th>
                    <th class="col-postulante">Apellidos y nombres</th>
                    <th class="col-asiento">Asiento</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($asignaciones as $indice => $asignacion)
                    @php($inscripcion = $asignacion->inscripcion)
                    <tr>
                        <td class="centro col-numero">{{ $indice + 1 }}</td>
                        <td class="centro col-documento">{{ $inscripcion->postulante->numero_documento_pos }}</td>
                        <td class="col-postulante">{{ $inscripcion->postulante->nombreCompleto() }}</td>
                        <td class="centro col-asiento">{{ $asignacion->asiento_ase }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="centro">Todavía no se ha ejecutado el sorteo para esta aula.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <footer class="pie">
            {{ $aulaExamen->examen->proceso->codigo_pro }} · {{ $aulaExamen->aula->etiqueta() }} · Página <span class="pagina"></span>
        </footer>
    </body>
</html>
