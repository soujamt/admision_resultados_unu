<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <style>
            /* Los márgenes reservan el sitio de la cabecera y del pie: ambos van
               fijos y no ocupan lugar en el flujo, así que sin margen el listado
               les pasaría por encima al saltar de hoja. */
            @page { margin: 46mm 12mm 14mm; }
            * { box-sizing: border-box; }
            body { color: #111; font-family: Arial, Helvetica, sans-serif; font-size: 8px; margin: 0; }
            /* Fija para repetirse en todas las hojas, no solo en la primera. */
            .cabecera { left: 0; position: fixed; right: 0; text-align: center; top: -38mm; }
            .isologo { height: 23mm; left: 0; position: absolute; top: 0; width: auto; }
            .institucion { font-size: 14px; font-weight: bold; line-height: 1.15; margin-bottom: 2px; }
            .linea-cabecera { font-size: 9.5px; font-weight: bold; line-height: 1.25; }
            .titulo { font-size: 11px; font-weight: bold; margin: 9px 0 4px; text-align: center; text-transform: uppercase; }
            /* La linea cuelga de la fecha: un div vacio con solo borde superior
               DomPDF lo colapsa, y este elemento si tiene caja propia. */
            .fecha { border-bottom: 0.8px solid #222; font-size: 8.5px; margin-bottom: 10px; padding-bottom: 3px; text-align: right; }
            .listado { border-collapse: collapse; table-layout: fixed; width: 100%; }
            .listado thead { display: table-header-group; }
            .listado th { background: #D9D9D9; border: 0.6px solid #222; font-size: 7.4px; font-weight: bold; line-height: 1.1; padding: 5px; text-align: center; text-transform: uppercase; vertical-align: middle; }
            .listado td { border: 0.6px solid #222; font-size: 7.4px; height: 13px; line-height: 1.1; padding: 2.5px 3px; vertical-align: middle; }
            /* La columna del correlativo va sombreada de arriba abajo. */
            .col-numero { background: #D9D9D9; width: 4%; }
            .col-codigo { width: 10%; }
            .col-postulante { width: 30%; }
            .col-carrera { width: 32%; }
            .col-pabellon { width: 8%; }
            .col-aula { width: 8%; }
            .col-carpeta { width: 8%; }
            .centro { text-align: center; }
            .pie { bottom: -9mm; color: #555; font-size: 7px; left: 0; position: fixed; right: 0; text-align: center; }
            .pagina::after { content: counter(page); }
        </style>
    </head>
    <body>
        <header class="cabecera">
            @include('pdf.partials.cabecera-institucional', [
                'modalidad' => $modalidadCabecera,
                'codigoProceso' => $examen->proceso->codigo_pro,
            ])

            <div class="titulo">Padrón general de postulantes</div>

            <div class="fecha">
                {{ mb_convert_case($ubicacion, MB_CASE_TITLE, 'UTF-8') }},
                {{ ($examen->fecha_exa ?? now())->translatedFormat('d \d\e F \d\e Y') }}
            </div>
        </header>

        <table class="listado">
            <thead>
                <tr>
                    <th class="col-numero">N°</th>
                    <th class="col-codigo">Código</th>
                    <th class="col-postulante">Apellidos y nombres</th>
                    <th class="col-carrera">Carrera profesional</th>
                    <th class="col-pabellon">Pabellón</th>
                    <th class="col-aula">Aula</th>
                    <th class="col-carpeta">Carpeta</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($asignaciones as $indice => $asignacion)
                    <tr>
                        <td class="centro col-numero">{{ $indice + 1 }}</td>
                        <td class="centro col-codigo">{{ $asignacion->inscripcion->postulante->numero_documento_pos }}</td>
                        <td class="col-postulante">{{ $asignacion->inscripcion->postulante->nombreCompleto() }}</td>
                        <td class="centro col-carrera">
                            {{ $asignacion->inscripcion->sede->abreviatura() }} -
                            {{ mb_strtoupper($asignacion->inscripcion->carrera->nombre_corto_car) }}
                        </td>
                        <td class="centro col-pabellon">{{ $asignacion->aulaExamen->aula->numeroDePabellon() ?? '—' }}</td>
                        <td class="centro col-aula">{{ $asignacion->aulaExamen->aula->numeroDeAula() }}</td>
                        <td class="centro col-carpeta">{{ $asignacion->asiento_ase }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="centro">Todavía no se ha ejecutado el sorteo de aulas para esta jornada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <footer class="pie">
            {{ $examen->proceso->codigo_pro }} · {{ $asignaciones->count() }} postulante(s) · Página <span class="pagina"></span>
        </footer>
    </body>
</html>
