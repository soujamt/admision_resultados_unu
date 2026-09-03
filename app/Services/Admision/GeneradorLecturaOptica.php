<?php

namespace App\Services\Admision;

use App\Enums\NivelDeExamen;
use App\Models\AsignacionExamen;
use App\Models\Examen;
use App\Models\Inscripcion;
use App\Models\Vacante;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Fabrica los dos TXT que devuelve el lector optico para poder ensayar la
 * importacion y la resolucion sin esperar a una jornada real.
 *
 * Copia el formato del CEPRE tal cual llega: cabecera, campos separados por
 * «;» con «;» final, fin de linea CRLF y Windows-1252. Escribirlo en la
 * codificacion original es lo unico que prueba de verdad la conversion que
 * hace ImportadorExamenTxt con los apellidos que llevan Ñ o tilde.
 *
 * El padron sale de las inscripciones vigentes del proceso y no de nombres
 * inventados: el importador cruza por numero de documento y la resolucion
 * rechaza al postulante que no cruza, asi que un padron inventado no llegaria
 * mas alla de la primera pantalla. Lo ficticio es la tarjeta optica.
 */
class GeneradorLecturaOptica
{
    private const PREGUNTAS = 100;

    /** @var list<string> */
    private const ALTERNATIVAS = ['A', 'B', 'C', 'D', 'E'];

    /** Primer numero de cartilla; el lector los entrega correlativos por aula. */
    private const CARTILLA_INICIAL = 1000;

    /** Cuantos postulantes por aula cuando la jornada todavia no tiene sorteo. */
    private const POR_AULA = 40;

    private const CABECERA_PADRON = 'DARACOD;COD POSTULANTE;APELLDOS Y NOMBRES;CARRERAS;MODALIDAD;MOD EXTRA;AULA;';

    private const CABECERA_RESPUESTAS = 'DARACOD;PRUEBAS Nota directa;PRUEBAS Nota transformada;PRUEBAS Aciertos;PRUEBAS Errores;PRUEBAS Blancos;PRUEBAS Dobles;RESPUESTAS;PRUEBAS;';

    /** @var list<string> */
    private const APELLIDOS = ['DEL AGUILA', 'REATEGUI', 'PACAYA', 'TANANTA', 'MANIHUARI', 'ISUIZA', 'RENGIFO', 'MURAYARI', 'SALDAÑA', 'PIÑA', 'VILLACREZ', 'ZUMAETA'];

    /** @var list<string> */
    private const NOMBRES = ['ANA LUCIA', 'JOSUE MARTIN', 'KIARA DAYANA', 'BRAYAN DAVID', 'MILAGROS BELEN', 'THIERRY HENRY', 'ROSA ANGELICA', 'JEREMY PAUL'];

    /**
     * @param  int  $ausentes  porcentaje del padron que no rinde y queda como NSP
     * @param  int  $intrusos  filas con documentos ajenos al proceso, para ensayar el rechazo
     * @param  ?int  $limite  cuantos postulantes escribir; null es todo el padron
     */
    public function generar(
        Examen $examen,
        ?string $carpeta = null,
        NivelDeExamen $nivel = NivelDeExamen::Normal,
        int $ausentes = 8,
        int $intrusos = 0,
        ?int $limite = null,
        ?int $semilla = null,
        bool $utf8 = false,
    ): ResumenLecturaSimulada {
        $semilla ??= random_int(1, 999999);
        mt_srand($semilla);

        $examen->loadMissing('proceso');
        $inscripciones = $this->inscripciones($examen);

        if ($inscripciones->isEmpty()) {
            throw new RuntimeException("El proceso {$examen->proceso->codigo_pro} no tiene inscripciones vigentes: impórtalas antes de simular la lectura óptica.");
        }

        $aulas = $this->aulasPorInscripcion($examen);
        $inscripciones = $this->ordenarComoElPadron($inscripciones, $aulas);

        if ($limite !== null && $limite > 0) {
            $inscripciones = $inscripciones->take($limite)->values();
        }

        $vacantes = $this->vacantesHabilitadas($examen);

        /*
         * El lector solo entrega lo que leyo, y entrega lo mismo en los dos
         * archivos: una fila de padron por cada tarjeta. Quien no rinde no
         * figura en ninguno de los dos, y es el resolutor el que lo publica
         * como NSP a partir de su inscripcion.
         */
        $rinden = $this->quienesRinden($inscripciones, max(0, min(100, $ausentes)));
        $filas = $this->filasDelPadron($rinden, $aulas, $vacantes);
        $filas = array_merge($filas, $this->filasIntrusas($rinden, $intrusos, count($filas)));

        $clave = $this->clave();
        $tarjetas = array_map(
            fn (array $fila): string => $this->tarjeta($examen, $fila['cartilla'], $clave, $nivel),
            $filas,
        );

        $carpeta = $carpeta === null ? $this->carpetaPorDefecto($examen) : $this->normalizar($carpeta);
        File::ensureDirectoryExists($carpeta);
        $base = $carpeta.DIRECTORY_SEPARATOR.$examen->proceso->codigo_pro.'_EXA'.$examen->id_exa;

        $this->escribir($base.'_PADRON.txt', self::CABECERA_PADRON, array_column($filas, 'linea'), $utf8);
        $this->escribir($base.'_RESPUESTAS.txt', self::CABECERA_RESPUESTAS, $tarjetas, $utf8);

        return new ResumenLecturaSimulada(
            padron: $base.'_PADRON.txt',
            respuestas: $base.'_RESPUESTAS.txt',
            filasPadron: count($filas),
            filasRespuestas: count($tarjetas),
            ausentes: $inscripciones->count() - $rinden->count(),
            intrusos: $intrusos,
            semilla: $semilla,
            advertencias: $this->advertencias($inscripciones, $aulas, $vacantes, $intrusos),
        );
    }

    /**
     * Carpeta del proceso dentro del disco privado, al lado de las fotos.
     */
    public function carpetaPorDefecto(Examen $examen): string
    {
        $examen->loadMissing('proceso');

        return $this->normalizar(Storage::disk('local')->path($examen->proceso->carpeta().'/lecturas'));
    }

    /**
     * @return Collection<int, Inscripcion>
     */
    private function inscripciones(Examen $examen): Collection
    {
        return Inscripcion::query()
            ->delProceso($examen->id_pro)
            ->vigente()
            ->with(['postulante', 'modalidad'])
            ->get()
            ->filter(fn (Inscripcion $inscripcion): bool => $inscripcion->postulante !== null)
            ->values();
    }

    /**
     * Aula y asiento con los que el sorteo dejo a cada inscripcion en esta
     * jornada. Vacio si todavia no se sortearon las aulas.
     *
     * @return Collection<int, array{aula: string, asiento: int}>
     */
    private function aulasPorInscripcion(Examen $examen): Collection
    {
        return AsignacionExamen::query()
            ->whereHas('aulaExamen', fn (Builder $consulta) => $consulta->where('id_exa', $examen->id_exa))
            ->with('aulaExamen.aula')
            ->get()
            ->mapWithKeys(fn (AsignacionExamen $asignacion): array => [
                $asignacion->id_ins => [
                    'aula' => $this->numeroDeAula($asignacion->aulaExamen?->aula?->codigo_aul),
                    'asiento' => (int) $asignacion->asiento_ase,
                ],
            ]);
    }

    /**
     * El lector devuelve el padron agrupado por aula y en el orden en que se
     * recogen las tarjetas dentro de ella.
     *
     * @param  Collection<int, Inscripcion>  $inscripciones
     * @param  Collection<int, array{aula: string, asiento: int}>  $aulas
     * @return Collection<int, Inscripcion>
     */
    private function ordenarComoElPadron(Collection $inscripciones, Collection $aulas): Collection
    {
        return $inscripciones->sortBy(function (Inscripcion $inscripcion) use ($aulas): string {
            $asignacion = $aulas->get($inscripcion->id_ins);

            return ($asignacion['aula'] ?? 'ZZZ')
                .str_pad((string) ($asignacion['asiento'] ?? 0), 4, '0', STR_PAD_LEFT)
                .$this->nombre($inscripcion);
        })->values();
    }

    /**
     * @return Collection<string, Vacante>
     */
    private function vacantesHabilitadas(Examen $examen): Collection
    {
        return $examen->proceso->vacantes()
            ->habilitada()
            ->get()
            ->keyBy(fn (Vacante $vacante): string => $this->clavePostulacion($vacante->id_mod, $vacante->id_car, $vacante->id_sed));
    }

    /**
     * Una fila por postulante. Los codigos de carrera y modalidad se escriben
     * con el «codigo_externo» de la oferta y no con la abreviatura del CEPRE
     * (INGAA, OR): es lo que compara ResolverResultadosService y con la
     * abreviatura la resolucion se detiene antes de adjudicar nada.
     *
     * @param  Collection<int, Inscripcion>  $inscripciones
     * @param  Collection<int, array{aula: string, asiento: int}>  $aulas
     * @param  Collection<string, Vacante>  $vacantes
     * @return list<array{cartilla: string, linea: string}>
     */
    private function filasDelPadron(Collection $inscripciones, Collection $aulas, Collection $vacantes): array
    {
        $filas = [];

        foreach ($inscripciones as $indice => $inscripcion) {
            $vacante = $vacantes->get($this->clavePostulacion($inscripcion->id_mod, $inscripcion->id_car, $inscripcion->id_sed));
            $cartilla = $this->cartilla($indice);

            $filas[] = [
                'cartilla' => $cartilla,
                'linea' => $this->linea([
                    $cartilla,
                    $inscripcion->postulante->numero_documento_pos,
                    $this->nombre($inscripcion),
                    (string) ($vacante?->codigo_externo_vac ?? ''),
                    (string) ($inscripcion->modalidad?->codigo_externo_mod ?? ''),
                    '   ',
                    $aulas->get($inscripcion->id_ins)['aula'] ?? $this->aulaPorBloque($indice),
                ]),
            ];
        }

        return $filas;
    }

    /**
     * Filas con documentos que no pertenecen al proceso, para comprobar que el
     * importador las observa y que la resolucion se niega a seguir con ellas.
     *
     * @param  Collection<int, Inscripcion>  $inscripciones
     * @return list<array{cartilla: string, linea: string}>
     */
    private function filasIntrusas(Collection $inscripciones, int $intrusos, int $desde): array
    {
        if ($intrusos < 1) {
            return [];
        }

        $ocupados = $inscripciones
            ->map(fn (Inscripcion $inscripcion): string => $inscripcion->postulante->numero_documento_pos)
            ->flip();
        $filas = [];

        for ($numero = 0; $numero < $intrusos; $numero++) {
            do {
                $documento = (string) mt_rand(70000000, 79999999);
            } while ($ocupados->has($documento));

            $ocupados[$documento] = true;
            $cartilla = $this->cartilla($desde + $numero);

            $filas[] = [
                'cartilla' => $cartilla,
                'linea' => $this->linea([
                    $cartilla,
                    $documento,
                    $this->nombreInventado(),
                    '',
                    '',
                    '   ',
                    $this->aulaPorBloque($desde + $numero),
                ]),
            ];
        }

        return $filas;
    }

    /**
     * Quienes rinden, conservando el orden del padron. El resto no se escribe
     * en ningun archivo: no rindieron, asi que el lector nunca los vio, y el
     * Art. 76 los publica como NSP desde su inscripcion.
     *
     * @param  Collection<int, Inscripcion>  $inscripciones
     * @return Collection<int, Inscripcion>
     */
    private function quienesRinden(Collection $inscripciones, int $porcentaje): Collection
    {
        $cuantos = (int) round($inscripciones->count() * $porcentaje / 100);

        if ($cuantos < 1) {
            return $inscripciones;
        }

        $indices = range(0, $inscripciones->count() - 1);
        shuffle($indices);
        $ausentes = array_fill_keys(array_slice($indices, 0, $cuantos), true);

        return $inscripciones
            ->reject(fn (Inscripcion $inscripcion, int $indice): bool => isset($ausentes[$indice]))
            ->values();
    }

    /**
     * @return list<string>
     */
    private function clave(): array
    {
        return array_map(
            fn (): string => self::ALTERNATIVAS[mt_rand(0, count(self::ALTERNATIVAS) - 1)],
            range(1, self::PREGUNTAS),
        );
    }

    /**
     * Una tarjeta optica leida: el conteo del lector, las 100 marcas y la
     * cadena que el CEPRE repite al final de la fila con «.» por cada blanco.
     *
     * @param  list<string>  $clave
     */
    private function tarjeta(Examen $examen, string $cartilla, array $clave, NivelDeExamen $nivel): string
    {
        $aciertos = $this->aciertos($nivel);
        $restantes = self::PREGUNTAS - $aciertos;
        $blancos = mt_rand(1, 100) <= 25 ? mt_rand(1, min($restantes, 40)) : 0;
        $disponibles = $restantes - $blancos;
        $dobles = $disponibles > 0 && mt_rand(1, 100) <= 8 ? mt_rand(1, min($disponibles, 3)) : 0;
        $errores = $restantes - $blancos - $dobles;

        $posiciones = range(0, self::PREGUNTAS - 1);
        shuffle($posiciones);
        $marcas = [];

        foreach ($posiciones as $orden => $pregunta) {
            $marcas[$pregunta] = match (true) {
                $orden < $aciertos => $clave[$pregunta],
                $orden < $aciertos + $blancos => ' ',
                $orden < $aciertos + $blancos + $dobles => '?',
                default => $this->alternativaDistinta($clave[$pregunta]),
            };
        }

        ksort($marcas);
        $directa = ($aciertos * (float) $examen->puntaje_acierto_exa)
            + (($errores + $dobles) * (float) $examen->puntaje_error_exa)
            + ($blancos * (float) $examen->puntaje_blanco_exa);

        return $this->linea([
            $cartilla,
            $this->nota($directa),
            $this->nota($directa * 20 / self::PREGUNTAS),
            (string) $aciertos,
            (string) $errores,
            (string) $blancos,
            (string) $dobles,
            ...array_values($marcas),
            str_replace(' ', '.', implode('', $marcas)),
        ]);
    }

    /**
     * Campana de Box-Muller alrededor del promedio del nivel: sin ella todos
     * los puntajes salen parecidos y la adjudicacion no se parece en nada a la
     * de una jornada real.
     */
    private function aciertos(NivelDeExamen $nivel): int
    {
        $uno = max(mt_rand() / mt_getrandmax(), 1e-9);
        $dos = mt_rand() / mt_getrandmax();
        $normal = sqrt(-2 * log($uno)) * cos(2 * M_PI * $dos);

        return (int) max(5, min(95, round($nivel->promedioDeAciertos() + ($normal * $nivel->desviacion()))));
    }

    private function alternativaDistinta(string $correcta): string
    {
        $otras = array_values(array_diff(self::ALTERNATIVAS, [$correcta]));

        return $otras[mt_rand(0, count($otras) - 1)];
    }

    /**
     * @param  Collection<int, Inscripcion>  $inscripciones
     * @param  Collection<int, array{aula: string, asiento: int}>  $aulas
     * @param  Collection<string, Vacante>  $vacantes
     * @return list<string>
     */
    private function advertencias(Collection $inscripciones, Collection $aulas, Collection $vacantes, int $intrusos): array
    {
        $advertencias = [];
        $sinVacante = $inscripciones->reject(
            fn (Inscripcion $inscripcion): bool => $vacantes->has($this->clavePostulacion($inscripcion->id_mod, $inscripcion->id_car, $inscripcion->id_sed)),
        )->count();

        if ($sinVacante > 0) {
            $advertencias[] = "{$sinVacante} inscripción(es) no tienen vacante habilitada: la resolución se detendrá hasta que el cuadro las cubra.";
        }

        if ($aulas->isEmpty()) {
            $advertencias[] = 'La jornada no tiene aulas sorteadas: el padrón se reparte en bloques de '.self::POR_AULA.'.';
        }

        if ($intrusos > 0) {
            $advertencias[] = "{$intrusos} fila(s) llevan documentos ajenos al proceso: el padrón se importará con observaciones y la resolución no podrá correr.";
        }

        return $advertencias;
    }

    private function nombre(Inscripcion $inscripcion): string
    {
        $postulante = $inscripcion->postulante;

        return mb_strtoupper(implode(' ', array_filter([
            $postulante->primer_apellido_pos,
            $postulante->segundo_apellido_pos,
            $postulante->nombres_pos,
        ])));
    }

    private function nombreInventado(): string
    {
        return self::APELLIDOS[mt_rand(0, count(self::APELLIDOS) - 1)]
            .' '.self::APELLIDOS[mt_rand(0, count(self::APELLIDOS) - 1)]
            .' '.self::NOMBRES[mt_rand(0, count(self::NOMBRES) - 1)];
    }

    /**
     * El lector rellena la cartilla a seis caracteres con espacios a la
     * derecha: «3894  ».
     */
    private function cartilla(int $indice): string
    {
        return str_pad((string) (self::CARTILLA_INICIAL + $indice), 6);
    }

    private function numeroDeAula(?string $codigo): string
    {
        $digitos = preg_replace('/\D/', '', (string) $codigo);

        return $digitos === '' ? '' : str_pad($digitos, 3, '0', STR_PAD_LEFT);
    }

    private function aulaPorBloque(int $indice): string
    {
        return str_pad((string) (intdiv($indice, self::POR_AULA) + 1), 3, '0', STR_PAD_LEFT);
    }

    private function clavePostulacion(int $modalidad, int $carrera, int $sede): string
    {
        return $modalidad.'-'.$carrera.'-'.$sede;
    }

    private function nota(float $valor): string
    {
        return number_format($valor, 5, ',', '');
    }

    /**
     * @param  list<string>  $campos
     */
    private function linea(array $campos): string
    {
        return implode(';', $campos).';';
    }

    /**
     * @param  list<string>  $lineas
     */
    private function escribir(string $ruta, string $cabecera, array $lineas, bool $utf8): void
    {
        $contenido = $cabecera."\r\n".implode("\r\n", $lineas)."\r\n";

        File::put($ruta, $utf8 ? $contenido : mb_convert_encoding($contenido, 'Windows-1252', 'UTF-8'));
    }

    /**
     * Flysystem devuelve la ruta con barras normales aunque el resto sea de
     * Windows, y una ruta a medias no se puede pegar en el explorador.
     */
    private function normalizar(string $ruta): string
    {
        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $ruta), DIRECTORY_SEPARATOR);
    }
}
