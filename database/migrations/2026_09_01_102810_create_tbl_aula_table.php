<?php

use App\Enums\EstadoRegistro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aulas donde se rinde el examen de admision.
 *
 * Por ahora es solo el catalogo de locales de cada sede. Mas adelante se
 * habilitan por proceso y sobre esa capacidad se hace el sorteo que ubica a
 * cada postulante en su aula y carpeta (Art. 10), asi que la capacidad y el
 * orden ya estan aqui para que ese modulo no obligue a remodelar la tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_aula', function (Blueprint $table) {
            $table->id('id_aul');
            $table->foreignId('id_sed')->constrained('tbl_sede', 'id_sed');
            $table->string('codigo_aul', 20);
            $table->string('nombre_aul', 100);
            $table->string('pabellon_aul', 80)->nullable();
            $table->unsignedSmallInteger('capacidad_aul')->default(0);
            $table->unsignedSmallInteger('orden_aul')->default(0);
            $table->unsignedTinyInteger('estado_aul')->default(EstadoRegistro::Habilitado->value);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id_sed', 'codigo_aul']);
            $table->index(['id_sed', 'orden_aul']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_aula');
    }
};
