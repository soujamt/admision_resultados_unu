<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogos que la universidad no administra: los codigos vienen fijados por
 * el formato de inscripcion que se reporta al MINEDU/SUNEDU, asi que la
 * columna `codigo_*` es la llave natural con la que se importan y exportan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_pais', function (Blueprint $table) {
            $table->id('id_pai');
            $table->unsignedSmallInteger('codigo_pai')->unique();
            $table->string('nombre_pai', 120);
            $table->timestamps();
        });

        Schema::create('tbl_nacionalidad', function (Blueprint $table) {
            $table->id('id_nac');
            $table->unsignedSmallInteger('codigo_nac')->unique();
            $table->string('nombre_nac', 120);
            $table->timestamps();
        });

        Schema::create('tbl_ubigeo', function (Blueprint $table) {
            $table->id('id_ubi');
            $table->char('codigo_ubi', 6)->unique();
            $table->string('departamento_ubi', 80);
            $table->string('provincia_ubi', 80);
            $table->string('distrito_ubi', 80);
            $table->timestamps();

            $table->index(['departamento_ubi', 'provincia_ubi']);
        });

        Schema::create('tbl_colegio', function (Blueprint $table) {
            $table->id('id_col');
            $table->char('codigo_modular_col', 7)->unique();
            $table->string('nombre_col', 200);
            $table->timestamps();

            $table->index('nombre_col');
        });

        Schema::create('tbl_lengua_nativa', function (Blueprint $table) {
            $table->id('id_lna');
            $table->unsignedSmallInteger('codigo_lna')->unique();
            $table->string('nombre_lna', 120);
            $table->timestamps();
        });

        Schema::create('tbl_lengua_extranjera', function (Blueprint $table) {
            $table->id('id_lex');
            $table->unsignedSmallInteger('codigo_lex')->unique();
            $table->string('nombre_lex', 120);
            $table->timestamps();
        });

        /*
         * El formato no numera las identidades etnicas: el codigo que se
         * reporta es el mismo texto de la descripcion.
         */
        Schema::create('tbl_identidad_etnica', function (Blueprint $table) {
            $table->id('id_ide');
            $table->string('codigo_ide', 120)->unique();
            $table->string('nombre_ide', 120);
            $table->timestamps();
        });

        Schema::create('tbl_tipo_discapacidad', function (Blueprint $table) {
            $table->id('id_tdi');
            $table->unsignedSmallInteger('codigo_tdi')->unique();
            $table->string('nombre_tdi', 120);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_tipo_discapacidad');
        Schema::dropIfExists('tbl_identidad_etnica');
        Schema::dropIfExists('tbl_lengua_extranjera');
        Schema::dropIfExists('tbl_lengua_nativa');
        Schema::dropIfExists('tbl_colegio');
        Schema::dropIfExists('tbl_ubigeo');
        Schema::dropIfExists('tbl_nacionalidad');
        Schema::dropIfExists('tbl_pais');
    }
};
