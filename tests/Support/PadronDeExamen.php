<?php

namespace Tests\Support;

use App\Models\Examen;
use App\Models\ExamenPostulante;
use App\Models\ExamenRespuesta;
use App\Models\Inscripcion;
use App\Models\Postulante;
use App\Models\Vacante;

/**
 * Inscribe a un postulante en una vacante y le deja la lectura optica hecha,
 * que es el punto de partida de cualquier prueba sobre resultados. Sin
 * respuesta el postulante queda como NSP, igual que en el padron real.
 */
class PadronDeExamen
{
    public static function postulante(Examen $examen, Vacante $vacante, int $numero, ?int $aciertos): ExamenPostulante
    {
        $inscripcion = Inscripcion::factory()->create([
            'id_pro' => $examen->id_pro,
            'id_mod' => $vacante->id_mod,
            'id_car' => $vacante->id_car,
            'id_sed' => $vacante->id_sed,
            'id_pos' => Postulante::factory()->create([
                'numero_documento_pos' => str_pad((string) $numero, 8, '0', STR_PAD_LEFT),
            ]),
        ]);
        $postulante = ExamenPostulante::factory()->create([
            'id_exa' => $examen->id_exa,
            'id_ins' => $inscripcion->id_ins,
            'codigo_cartilla_exp' => 'C-'.$numero,
            'documento_exp' => $inscripcion->postulante->numero_documento_pos,
            'codigo_carrera_exp' => (string) $vacante->codigo_externo_vac,
            'codigo_modalidad_exp' => (string) $inscripcion->modalidad->codigo_externo_mod,
        ]);

        if ($aciertos !== null) {
            ExamenRespuesta::factory()->create([
                'id_exp' => $postulante->id_exp,
                'aciertos_exr' => $aciertos,
                'errores_exr' => 100 - $aciertos,
                'blancos_exr' => 0,
                'dobles_exr' => 0,
            ]);
        }

        return $postulante;
    }
}
