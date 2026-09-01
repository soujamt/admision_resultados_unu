<?php

use App\Enums\EstadoRegistro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Carreras profesionales del Art. 1. El area (Art. 4) decide con que examen
 * se evalua al postulante, por eso vive aqui y no en la vacante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_carrera', function (Blueprint $table) {
            $table->id('id_car');
            $table->foreignId('id_fac')->constrained('tbl_facultad', 'id_fac');
            $table->foreignId('id_are')->constrained('tbl_area', 'id_are');
            $table->string('codigo_car', 30)->unique();
            $table->string('nombre_car', 180)->unique();
            $table->string('nombre_corto_car', 80);
            $table->unsignedTinyInteger('estado_car')->default(EstadoRegistro::Habilitado->value);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_carrera');
    }
};
