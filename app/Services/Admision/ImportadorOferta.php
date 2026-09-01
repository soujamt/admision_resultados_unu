<?php

namespace App\Services\Admision;

use App\Enums\EstadoRegistro;
use App\Models\Carrera;
use App\Models\Modalidad;
use App\Models\Proceso;
use App\Models\ProcesoModalidad;
use App\Models\Sede;
use App\Models\Vacante;
use App\Services\Excel\LectorXlsx;
use Illuminate\Support\Collection;

/**
 * Lee la hoja CARRERAS_PROFESIONALES del formato oficial y arma con ella la
 * oferta del proceso: que modalidades se abren, con que codigo de lugar de
 * inscripcion viajan y que codigo externo le toca a cada carrera.
 *
 * La cantidad de vacantes no viene en el archivo: la aprueban las Escuelas
 * Profesionales (Art. 15) y se configura despues en el sistema, asi que las
 * filas nuevas nacen en cero y una reimportacion nunca pisa la cantidad que ya
 * este cargada.
 */
class ImportadorOferta
{
    private const HOJA = 'CARRERAS_PROFESIONALES';

    /** La cabecera ocupa las tres primeras filas de la hoja. */
    private const PRIMERA_FILA_DE_DATOS = 4;

    /** @var Collection<int, Modalidad> */
    private Collection $modalidades;

    /** @var Collection<int, Sede> */
    private Collection $sedes;

    /** @var array<string, Carrera> */
    private array $carrerasPorNombre = [];

    public function importar(LectorXlsx $lector, Proceso $proceso, Sede $sedePorDefecto): ResultadoImportacion
    {
        $this->cargarCatalogos();

        $resultado = new ResultadoImportacion;

        $modalidad = null;
        $codigoLugar = null;
        $nombreLugar = null;

        foreach ($lector->filasCrudas(self::HOJA) as $numero => $fila) {
            if ($numero < self::PRIMERA_FILA_DE_DATOS) {
                continue;
            }

            /*
             * La hoja solo escribe la modalidad y el lugar en la primera fila
             * de cada bloque; las siguientes los heredan.
             */
            if (filled($fila[0] ?? '')) {
                $modalidad = $this->resolverModalidad($fila[0], $fila[1] ?? '');

                if ($modalidad === null) {
                    $resultado->fallar($numero, 'No hay una modalidad registrada que corresponda a este bloque.', $fila[1] ?? $fila[0]);

                    continue;
                }
            }

            if (filled($fila[2] ?? '')) {
                $codigoLugar = (int) $fila[2];
                $nombreLugar = $fila[3] ?? null;
            }

            $codigoCarrera = $fila[4] ?? '';

            if (blank($codigoCarrera) || ! ctype_digit($codigoCarrera)) {
                continue;
            }

            if ($modalidad === null) {
                $resultado->fallar($numero, 'La carrera no está debajo de ninguna modalidad.', $fila[5] ?? null);

                continue;
            }

            $descripcion = $fila[5] ?? '';
            $sede = $this->resolverSede($descripcion) ?? $sedePorDefecto;
            $carrera = $this->resolverCarrera($descripcion, $sede);

            if ($carrera === null) {
                $resultado->fallar($numero, 'No hay una carrera registrada con ese nombre.', $descripcion);

                continue;
            }

            $this->registrarModalidadDelProceso($proceso, $modalidad, $codigoLugar, $nombreLugar);

            $vacante = Vacante::firstOrNew([
                'id_pro' => $proceso->id_pro,
                'id_mod' => $modalidad->id_mod,
                'id_car' => $carrera->id_car,
                'id_sed' => $sede->id_sed,
            ]);

            $existia = $vacante->exists;

            $vacante->codigo_externo_vac = (int) $codigoCarrera;
            $vacante->estado_vac = EstadoRegistro::Habilitado;
            $vacante->cantidad_vac ??= 0;
            $vacante->save();

            $existia ? $resultado->actualizar() : $resultado->crear();
        }

        return $resultado;
    }

    private function cargarCatalogos(): void
    {
        $this->modalidades = Modalidad::all();
        $this->sedes = Sede::all();

        $this->carrerasPorNombre = Carrera::all()
            ->keyBy(static fn (Carrera $carrera): string => normalizar_texto($carrera->nombre_car))
            ->all();
    }

    private function resolverModalidad(string $codigo, string $descripcion): ?Modalidad
    {
        $porCodigo = $this->modalidades->firstWhere('codigo_externo_mod', (int) $codigo);

        if ($porCodigo !== null) {
            return $porCodigo;
        }

        $buscado = normalizar_texto($descripcion);

        return $this->modalidades->first(
            static fn (Modalidad $modalidad): bool => normalizar_texto($modalidad->nombre_mod) === $buscado,
        );
    }

    /**
     * La descripcion de la carrera viene con la sede delante:
     * "SEDE CORONEL PORTILLO - CALLERIA - AGRONOMIA".
     */
    private function resolverSede(string $descripcion): ?Sede
    {
        $normalizada = normalizar_texto($descripcion);

        return $this->sedes
            ->filter(static fn (Sede $sede): bool => str_starts_with($normalizada, normalizar_texto($sede->nombre_sed)))
            ->sortByDesc(static fn (Sede $sede): int => mb_strlen(normalizar_texto($sede->nombre_sed)))
            ->first();
    }

    private function resolverCarrera(string $descripcion, Sede $sede): ?Carrera
    {
        $normalizada = normalizar_texto($descripcion);
        $prefijo = normalizar_texto($sede->nombre_sed);

        if ($prefijo !== '' && str_starts_with($normalizada, $prefijo)) {
            $normalizada = trim(mb_substr($normalizada, mb_strlen($prefijo)));
        }

        return $this->carrerasPorNombre[$normalizada] ?? null;
    }

    private function registrarModalidadDelProceso(
        Proceso $proceso,
        Modalidad $modalidad,
        ?int $codigoLugar,
        ?string $nombreLugar,
    ): void {
        ProcesoModalidad::updateOrCreate(
            ['id_pro' => $proceso->id_pro, 'id_mod' => $modalidad->id_mod],
            [
                'codigo_lugar_prm' => $codigoLugar,
                'nombre_lugar_prm' => $nombreLugar,
                'estado_prm' => EstadoRegistro::Habilitado,
            ],
        );
    }
}
