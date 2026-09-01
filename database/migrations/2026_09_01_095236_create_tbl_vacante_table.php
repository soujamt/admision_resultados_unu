<?php

use App\Enums\EstadoRegistro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuadro de vacantes del Art. 15: cuantas plazas ofrece cada carrera en una
 * sede, por modalidad y proceso.
 *
 * `codigo_externo_vac` es el codigo de carrera que exige el formato de
 * inscripcion (2555, 2556...). Se renumera en cada proceso y ademas cambia
 * segun la modalidad, asi que corresponde a esta fila y no a tbl_carrera.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_vacante', function (Blueprint $table) {
            $table->id('id_vac');
            $table->foreignId('id_pro')->constrained('tbl_proceso', 'id_pro')->cascadeOnDelete();
            $table->foreignId('id_mod')->constrained('tbl_modalidad', 'id_mod');
            $table->foreignId('id_car')->constrained('tbl_carrera', 'id_car');
            $table->foreignId('id_sed')->constrained('tbl_sede', 'id_sed');
            $table->unsignedSmallInteger('cantidad_vac')->default(0);
            $table->unsignedInteger('codigo_externo_vac')->nullable();
            $table->unsignedTinyInteger('estado_vac')->default(EstadoRegistro::Habilitado->value);
            $table->timestamps();

            $table->unique(['id_pro', 'id_mod', 'id_car', 'id_sed']);
            $table->unique(['id_pro', 'codigo_externo_vac']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_vacante');
    }
};
