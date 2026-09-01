<?php

use App\Enums\EstadoRegistro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un proceso es una convocatoria concreta: 2027-I, 2027-II, 2027-III. Todo lo
 * que se configura y se registra (vacantes, inscripciones, fotos) cuelga de
 * aqui, de modo que dos convocatorias nunca comparten datos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_proceso', function (Blueprint $table) {
            $table->id('id_pro');
            $table->string('codigo_pro', 20)->unique();
            $table->string('nombre_pro', 150);
            $table->unsignedSmallInteger('anio_pro');
            $table->unsignedTinyInteger('convocatoria_pro');
            $table->date('fecha_inicio_inscripcion_pro')->nullable();
            $table->date('fecha_fin_inscripcion_pro')->nullable();
            $table->date('fecha_examen_pro')->nullable();
            $table->string('resolucion_pro', 100)->nullable();
            $table->unsignedTinyInteger('estado_pro')->default(EstadoRegistro::Habilitado->value);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['anio_pro', 'convocatoria_pro']);
        });

        /*
         * Que modalidades se abren en el proceso y con que codigo de lugar de
         * inscripcion viajan en el archivo que se reporta. El codigo cambia de
         * un proceso a otro, por eso no puede vivir en tbl_modalidad.
         */
        Schema::create('tbl_proceso_modalidad', function (Blueprint $table) {
            $table->id('id_prm');
            $table->foreignId('id_pro')->constrained('tbl_proceso', 'id_pro')->cascadeOnDelete();
            $table->foreignId('id_mod')->constrained('tbl_modalidad', 'id_mod');
            $table->unsignedInteger('codigo_lugar_prm')->nullable();
            $table->string('nombre_lugar_prm', 120)->nullable();
            $table->unsignedTinyInteger('estado_prm')->default(EstadoRegistro::Habilitado->value);
            $table->timestamps();

            $table->unique(['id_pro', 'id_mod']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_proceso_modalidad');
        Schema::dropIfExists('tbl_proceso');
    }
};
