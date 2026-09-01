<?php

use App\Enums\EstadoRegistro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modalidades de admision del Art. 5.
 *
 * `codigo_externo_mod` es el numero con el que la modalidad viaja en el
 * formato que se reporta (2 = Exoneracion CEPREUNU, 8 = Reserva CEPREUNU);
 * queda nulo mientras la modalidad no se reporte por ese canal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_modalidad', function (Blueprint $table) {
            $table->id('id_mod');
            $table->string('codigo_mod', 40)->unique();
            $table->string('nombre_mod', 150);
            $table->string('grupo_mod', 20);
            $table->unsignedSmallInteger('codigo_externo_mod')->nullable();
            $table->string('articulo_mod', 20)->nullable();
            $table->unsignedTinyInteger('estado_mod')->default(EstadoRegistro::Habilitado->value);
            $table->timestamps();
            $table->softDeletes();

            $table->index('grupo_mod');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_modalidad');
    }
};
