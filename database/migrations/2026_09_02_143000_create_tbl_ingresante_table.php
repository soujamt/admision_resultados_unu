<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Padron oficial de ingresantes del Art. 89, separado de tbl_resultado porque
 * el Art. 72 declara los resultados del examen irrevisables e inmodificables:
 * la renuncia, el expediente incompleto y la falta de matricula cambian quien
 * es ingresante sin poder tocar la nota ni el orden de merito publicados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_ingresante', function (Blueprint $table) {
            $table->id('id_ing');
            $table->foreignId('id_pro')->constrained('tbl_proceso', 'id_pro')->cascadeOnDelete();
            $table->foreignId('id_ins')->constrained('tbl_inscripcion', 'id_ins')->cascadeOnDelete();
            $table->foreignId('id_vac')->constrained('tbl_vacante', 'id_vac');

            /*
             * La jornada de la que salio. Regenerar el padron solo retira a los
             * ingresantes de esa misma jornada, para que un proceso con varias
             * no se pise a si mismo.
             */
            $table->foreignId('id_exa')->nullable()->constrained('tbl_examen', 'id_exa')->nullOnDelete();

            /*
             * El resultado de origen queda en nulo si se vuelve a resolver la
             * jornada; la condicion del ingresante sobrevive a esa regeneracion.
             */
            $table->foreignId('id_res')->nullable()->constrained('tbl_resultado', 'id_res')->nullOnDelete();

            /* A quien reemplaza este ingresante por el Art. 93. */
            $table->foreignId('id_sustituido_ing')->nullable()->constrained('tbl_ingresante', 'id_ing')->nullOnDelete();

            $table->decimal('puntaje_ing', 8, 4)->nullable();
            $table->unsignedInteger('orden_carrera_ing')->nullable();
            $table->string('condicion_ing', 20)->default('vigente');
            $table->string('motivo_ing', 255)->nullable();
            $table->timestamp('condicion_en_ing')->nullable();
            $table->timestamps();

            $table->unique(['id_pro', 'id_ins']);
            $table->index(['id_pro', 'condicion_ing']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_ingresante');
    }
};
