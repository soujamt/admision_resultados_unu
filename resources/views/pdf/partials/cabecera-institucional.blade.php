{{-- Cabecera oficial que comparten el padrón general y la lista de asistencia.
     Se repite en cada hoja, así que va dentro de un bloque fijo o de la sección
     de cada página, según cómo pagine el documento que la use. --}}
<img class="isologo" src="{{ public_path('img/isologo-unu.png') }}" alt="Isologo de la Universidad Nacional de Ucayali">

<div class="institucion">UNIVERSIDAD NACIONAL DE UCAYALI</div>
<div class="linea-cabecera">VICERRECTORADO ACADÉMICO</div>
<div class="linea-cabecera">DIRECCIÓN DE ADMISIÓN</div>
<div class="linea-cabecera">COMISIÓN CENTRAL DE ADMISIÓN</div>
<div class="linea-cabecera">MODALIDAD DE ADMISIÓN POR {{ mb_strtoupper($modalidad) }}</div>
<div class="linea-cabecera">{{ $codigoProceso }}</div>
