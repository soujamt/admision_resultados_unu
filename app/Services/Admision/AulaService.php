<?php

namespace App\Services\Admision;

use App\Models\Aula;

/**
 * Aulas de cada sede. Todavia no hay nada colgando de ellas —la asignacion por
 * proceso y el sorteo llegan despues—, asi que se pueden borrar sin ataduras.
 *
 * @extends ServicioDeCatalogo<Aula>
 */
class AulaService extends ServicioDeCatalogo
{
    public function modelo(): string
    {
        return Aula::class;
    }
}
