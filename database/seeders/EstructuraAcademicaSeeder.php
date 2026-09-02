<?php

namespace Database\Seeders;

use App\Enums\EstadoRegistro;
use App\Models\Area;
use App\Models\Carrera;
use App\Models\Facultad;
use App\Models\Sede;
use App\Models\Ubigeo;
use Illuminate\Database\Seeder;

/**
 * Facultades (Art. 1), areas academicas (Art. 4), sedes y carreras.
 *
 * Es la unica parte del catalogo que se escribe a mano: sale del reglamento y
 * no de un archivo externo, y solo cambia cuando el Consejo Universitario crea
 * o cierra una carrera.
 */
class EstructuraAcademicaSeeder extends Seeder
{
    public function run(): void
    {
        $this->sembrarSedes();

        $facultades = $this->sembrarFacultades();
        $areas = $this->sembrarAreas();

        foreach ($this->carreras() as $carrera) {
            Carrera::updateOrCreate(
                ['codigo_car' => $carrera['codigo']],
                [
                    'id_fac' => $facultades[$carrera['facultad']],
                    'id_are' => $areas[$carrera['area']],
                    'nombre_car' => $carrera['nombre'],
                    'nombre_corto_car' => $carrera['corto'],
                    'puntaje_minimo_car' => $carrera['minimo'],
                    'estado_car' => EstadoRegistro::Habilitado,
                ],
            );
        }
    }

    /**
     * El ubigeo de la sede queda nulo si el padron aun no esta importado: la
     * clave foranea lo exige, y las sedes se siembran antes que el catalogo.
     */
    private function sembrarSedes(): void
    {
        $sedes = [
            ['codigo_sed' => 'CORONEL_PORTILLO', 'nombre_sed' => 'Sede Coronel Portillo - Callería', 'codigo_ubi' => '250101', 'es_filial_sed' => false],
            ['codigo_sed' => 'AGUAYTIA', 'nombre_sed' => 'Filial Aguaytía', 'codigo_ubi' => '250302', 'es_filial_sed' => true],
            ['codigo_sed' => 'ATALAYA', 'nombre_sed' => 'Filial Atalaya', 'codigo_ubi' => '250201', 'es_filial_sed' => true],
        ];

        foreach ($sedes as $sede) {
            if (! Ubigeo::where('codigo_ubi', $sede['codigo_ubi'])->exists()) {
                $sede['codigo_ubi'] = null;
            }

            Sede::updateOrCreate(
                ['codigo_sed' => $sede['codigo_sed']],
                $sede + ['estado_sed' => EstadoRegistro::Habilitado],
            );
        }
    }

    /**
     * @return array<string, int>
     */
    private function sembrarFacultades(): array
    {
        $facultades = [
            'AGROPECUARIAS' => 'Facultad de Ciencias Agropecuarias',
            'FORESTALES' => 'Facultad de Ciencias Forestales y Ambientales',
            'SALUD' => 'Facultad de Ciencias de la Salud',
            'MEDICINA' => 'Facultad de Medicina Humana',
            'ECONOMICAS' => 'Facultad de Ciencias Económicas, Administrativas y Contables',
            'SISTEMAS_CIVIL' => 'Facultad de Ingeniería de Sistemas e Ingeniería Civil',
            'DERECHO' => 'Facultad de Derecho y Ciencias Políticas',
            'EDUCACION' => 'Facultad de Educación y Ciencias Sociales',
        ];

        $ids = [];

        foreach ($facultades as $codigo => $nombre) {
            $ids[$codigo] = Facultad::updateOrCreate(
                ['codigo_fac' => $codigo],
                ['nombre_fac' => $nombre, 'estado_fac' => EstadoRegistro::Habilitado],
            )->id_fac;
        }

        return $ids;
    }

    /**
     * @return array<int, int>
     */
    private function sembrarAreas(): array
    {
        $areas = [
            1 => 'Ciencias Agrarias y del Ambiente',
            2 => 'Ciencias de la Salud',
            3 => 'Negocios',
            4 => 'Ingeniería',
            5 => 'Ciencias Sociales',
        ];

        $ids = [];

        foreach ($areas as $numero => $nombre) {
            $ids[$numero] = Area::updateOrCreate(
                ['numero_are' => $numero],
                ['nombre_are' => $nombre, 'estado_are' => EstadoRegistro::Habilitado],
            )->id_are;
        }

        return $ids;
    }

    /**
     * `minimo` es la nota final minima del Art. 81: 50 en todas las carreras
     * salvo Psicologia (55) y Medicina Humana (60). Se deja en null cuando
     * rige el minimo general, que se configura en la jornada de examen.
     *
     * @return list<array{codigo: string, nombre: string, corto: string, facultad: string, area: int, minimo: ?float}>
     */
    private function carreras(): array
    {
        return [
            ['codigo' => 'AGRONOMIA', 'nombre' => 'Agronomía', 'corto' => 'Agronomía', 'facultad' => 'AGROPECUARIAS', 'area' => 1, 'minimo' => null],
            ['codigo' => 'ING_AGROINDUSTRIAL', 'nombre' => 'Ingeniería Agroindustrial', 'corto' => 'Ing. Agroindustrial', 'facultad' => 'AGROPECUARIAS', 'area' => 1, 'minimo' => null],
            ['codigo' => 'ING_FORESTAL', 'nombre' => 'Ingeniería Forestal', 'corto' => 'Ing. Forestal', 'facultad' => 'FORESTALES', 'area' => 1, 'minimo' => null],
            ['codigo' => 'ING_AMBIENTAL', 'nombre' => 'Ingeniería Ambiental', 'corto' => 'Ing. Ambiental', 'facultad' => 'FORESTALES', 'area' => 1, 'minimo' => null],
            ['codigo' => 'PSICOLOGIA', 'nombre' => 'Psicología', 'corto' => 'Psicología', 'facultad' => 'SALUD', 'area' => 2, 'minimo' => 55],
            ['codigo' => 'ENFERMERIA', 'nombre' => 'Enfermería', 'corto' => 'Enfermería', 'facultad' => 'SALUD', 'area' => 2, 'minimo' => null],
            ['codigo' => 'MEDICINA_HUMANA', 'nombre' => 'Medicina Humana', 'corto' => 'Medicina Humana', 'facultad' => 'MEDICINA', 'area' => 2, 'minimo' => 60],
            ['codigo' => 'ADMINISTRACION', 'nombre' => 'Administración', 'corto' => 'Administración', 'facultad' => 'ECONOMICAS', 'area' => 3, 'minimo' => null],
            ['codigo' => 'CONTABILIDAD', 'nombre' => 'Contabilidad', 'corto' => 'Contabilidad', 'facultad' => 'ECONOMICAS', 'area' => 3, 'minimo' => null],
            ['codigo' => 'ECONOMIA', 'nombre' => 'Economía y Negocios Internacionales', 'corto' => 'Economía y Neg. Int.', 'facultad' => 'ECONOMICAS', 'area' => 3, 'minimo' => null],
            ['codigo' => 'ING_SISTEMAS', 'nombre' => 'Ingeniería de Sistemas', 'corto' => 'Ing. de Sistemas', 'facultad' => 'SISTEMAS_CIVIL', 'area' => 4, 'minimo' => null],
            ['codigo' => 'ING_CIVIL', 'nombre' => 'Ingeniería Civil', 'corto' => 'Ing. Civil', 'facultad' => 'SISTEMAS_CIVIL', 'area' => 4, 'minimo' => null],
            ['codigo' => 'DERECHO', 'nombre' => 'Derecho', 'corto' => 'Derecho', 'facultad' => 'DERECHO', 'area' => 5, 'minimo' => null],
            ['codigo' => 'EDU_INICIAL', 'nombre' => 'Educación Inicial', 'corto' => 'Educación Inicial', 'facultad' => 'EDUCACION', 'area' => 5, 'minimo' => null],
            ['codigo' => 'EDU_PRIMARIA', 'nombre' => 'Educación Primaria', 'corto' => 'Educación Primaria', 'facultad' => 'EDUCACION', 'area' => 5, 'minimo' => null],
            ['codigo' => 'EDU_SEC_INGLES', 'nombre' => 'Educación Secundaria: Especialidad Idioma Inglés', 'corto' => 'Edu. Sec. Inglés', 'facultad' => 'EDUCACION', 'area' => 5, 'minimo' => null],
            ['codigo' => 'EDU_SEC_LENGUA', 'nombre' => 'Educación Secundaria: Especialidad Lengua y Literatura', 'corto' => 'Edu. Sec. Lengua y Literatura', 'facultad' => 'EDUCACION', 'area' => 5, 'minimo' => null],
            ['codigo' => 'EDU_SEC_MATEMATICA', 'nombre' => 'Educación Secundaria: Especialidad Matemática, Física e Informática', 'corto' => 'Edu. Sec. Matemática', 'facultad' => 'EDUCACION', 'area' => 5, 'minimo' => null],
            ['codigo' => 'EDU_SEC_CIENCIAS', 'nombre' => 'Educación Secundaria: Especialidad Ciencias Naturales y Medio Ambiente', 'corto' => 'Edu. Sec. Ciencias Naturales', 'facultad' => 'EDUCACION', 'area' => 5, 'minimo' => null],
            ['codigo' => 'EDU_SEC_SOCIALES', 'nombre' => 'Educación Secundaria: Especialidad Ciencias Sociales y Educación Intercultural', 'corto' => 'Edu. Sec. Ciencias Sociales', 'facultad' => 'EDUCACION', 'area' => 5, 'minimo' => null],
            ['codigo' => 'CIENCIAS_COMUNICACION', 'nombre' => 'Ciencias de la Comunicación', 'corto' => 'Ciencias de la Comunicación', 'facultad' => 'EDUCACION', 'area' => 5, 'minimo' => null],
        ];
    }
}
