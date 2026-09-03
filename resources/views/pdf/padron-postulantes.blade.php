<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <style>
            @page { margin: 8mm 12mm 10mm; }
            * { box-sizing: border-box; }
            body { color: #111; font-family: "Times New Roman", Times, serif; font-size: 9px; margin: 0; }
            .cabecera { min-height: 30mm; position: relative; text-align: center; }
            .isologo { height: 25mm; left: 8mm; position: absolute; top: 0; width: auto; }
            .institucion { font-size: 16px; font-weight: bold; line-height: 1.15; margin-bottom: 2px; }
            .linea-cabecera { font-size: 11px; font-weight: bold; line-height: 1.25; }
            .titulo { font-size: 12px; font-weight: bold; margin: 10px 0 14px; text-align: center; text-transform: uppercase; }
            .listado { border-collapse: collapse; table-layout: fixed; width: 100%; }
            .listado thead { display: table-header-group; }
            .listado th { border: 0.6px solid #222; font-size: 8.6px; font-weight: bold; line-height: 1.1; padding: 4px 3px; text-align: center; text-transform: uppercase; vertical-align: middle; }
            .listado td { border: 0.6px solid #222; font-size: 8.6px; height: 13px; line-height: 1.1; padding: 2.5px 3px; vertical-align: middle; }
            .col-numero { width: 6%; }
            .col-postulante { width: 54%; }
            .col-pabellon { width: 18%; }
            .col-aula { width: 12%; }
            .col-carpeta { width: 10%; }
            .centro { text-align: center; }
            .pie { bottom: -9mm; color: #555; font-size: 7px; left: 0; position: fixed; right: 0; text-align: center; }
            .pagina::after { content: counter(page); }
        </style>
    </head>
    <body>
        <header class="cabecera">
            <img class="isologo" src="{{ public_path('img/isologo-unu.png') }}" alt="Isologo de la Universidad Nacional de Ucayali">

            <div class="institucion">UNIVERSIDAD NACIONAL DE UCAYALI</div>
            <div class="linea-cabecera">VICERRECTORADO ACADÉMICO</div>
            <div class="linea-cabecera">DIRECCIÓN DE ADMISIÓN</div>
            <div class="linea-cabecera">{{ $examen->proceso->tituloConvocatoria() }}</div>
            <div class="linea-cabecera">{{ mb_strtoupper($modalidadCabecera) }}</div>
            <div class="linea-cabecera">{{ $ubicacion }}</div>
        </header>

        <div class="titulo">Padrón general de postulantes</div>

        <table class="listado">
            <thead>
                <tr>
                    <th class="col-numero">N°</th>
                    <th class="col-postulante">Apellidos y nombres</th>
                    <th class="col-pabellon">Pabellón</th>
                    <th class="col-aula">Aula</th>
                    <th class="col-carpeta">Carpeta</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($asignaciones as $indice => $asignacion)
                    <tr>
                        <td class="centro col-numero">{{ $indice + 1 }}</td>
                        <td class="col-postulante">{{ $asignacion->inscripcion->postulante->nombreCompleto() }}</td>
                        <td class="centro col-pabellon">{{ $asignacion->aulaExamen->aula->pabellon_aul ?? '—' }}</td>
                        <td class="centro col-aula">{{ $asignacion->aulaExamen->aula->nombre_aul }}</td>
                        <td class="centro col-carpeta">{{ $asignacion->asiento_ase }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="centro">Todavía no se ha ejecutado el sorteo de aulas para esta jornada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <footer class="pie">
            {{ $examen->proceso->codigo_pro }} · {{ $asignaciones->count() }} postulante(s) · Página <span class="pagina"></span>
        </footer>
    </body>
</html>
