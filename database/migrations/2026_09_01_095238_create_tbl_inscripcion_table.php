<?php

use App\Enums\EstadoInscripcion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La ficha de inscripcion de un postulante a un proceso. El Art. 28 le da
 * caracter de declaracion jurada, por eso lo que se declaro en una
 * convocatoria queda congelado aqui aunque los datos de la persona cambien.
 *
 * `foto_ins` guarda la ruta relativa dentro del disco privado `local`, con la
 * forma `procesos/{codigo}/fotos/{documento}.jpg`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_inscripcion', function (Blueprint $table) {
            $table->id('id_ins');
            $table->foreignId('id_pro')->constrained('tbl_proceso', 'id_pro')->cascadeOnDelete();
            $table->foreignId('id_pos')->constrained('tbl_postulante', 'id_pos');
            $table->foreignId('id_mod')->constrained('tbl_modalidad', 'id_mod');
            $table->foreignId('id_car')->constrained('tbl_carrera', 'id_car');
            $table->foreignId('id_sed')->constrained('tbl_sede', 'id_sed');
            $table->string('codigo_ins', 20)->nullable();
            $table->foreignId('id_pai')->nullable()->constrained('tbl_pais', 'id_pai');
            $table->char('codigo_colegio_ins', 7)->nullable();
            $table->string('nombre_colegio_ins', 200)->nullable();
            $table->unsignedTinyInteger('tipo_colegio_ins')->nullable();
            $table->unsignedSmallInteger('anio_graduacion_ins')->nullable();
            $table->unsignedTinyInteger('veces_unu_ins')->default(0);
            $table->unsignedTinyInteger('veces_otros_ins')->default(0);
            $table->string('foto_ins', 255)->nullable();
            $table->string('observacion_ins', 255)->nullable();
            $table->date('fecha_ins')->nullable();
            $table->unsignedTinyInteger('estado_ins')->default(EstadoInscripcion::Inscrito->value);
            $table->timestamps();
            $table->softDeletes();

            /* Art. 34: una sola modalidad de admision por convocatoria. */
            $table->unique(['id_pro', 'id_pos']);
            $table->unique(['id_pro', 'codigo_ins']);
            $table->index(['id_pro', 'id_mod', 'id_car']);

            $table->foreign('codigo_colegio_ins')->references('codigo_modular_col')->on('tbl_colegio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_inscripcion');
    }
};
