<?php

use App\Enums\EstadoRegistro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estructura academica sobre la que se arma la oferta: las facultades del
 * Art. 1, las cinco areas del Art. 4 y las sedes donde se dictan las carreras.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_facultad', function (Blueprint $table) {
            $table->id('id_fac');
            $table->string('codigo_fac', 20)->unique();
            $table->string('nombre_fac', 150)->unique();
            $table->unsignedTinyInteger('estado_fac')->default(EstadoRegistro::Habilitado->value);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tbl_area', function (Blueprint $table) {
            $table->id('id_are');
            $table->unsignedTinyInteger('numero_are')->unique();
            $table->string('nombre_are', 120);
            $table->unsignedTinyInteger('estado_are')->default(EstadoRegistro::Habilitado->value);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tbl_sede', function (Blueprint $table) {
            $table->id('id_sed');
            $table->string('codigo_sed', 20)->unique();
            $table->string('nombre_sed', 150);
            $table->char('codigo_ubi', 6)->nullable();
            $table->boolean('es_filial_sed')->default(false);
            $table->unsignedTinyInteger('estado_sed')->default(EstadoRegistro::Habilitado->value);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('codigo_ubi')->references('codigo_ubi')->on('tbl_ubigeo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_sede');
        Schema::dropIfExists('tbl_area');
        Schema::dropIfExists('tbl_facultad');
    }
};
