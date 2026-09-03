<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El padron oficial de resultados tiene que publicar como NSP al inscrito que
 * no se presento (Art. 76), y ese postulante nunca recibio tarjeta optica: el
 * lector no lo vio. La cartilla en nulo es justamente esa distincion, y sirve
 * para separar lo que entrego el lector de lo que completo la resolucion.
 *
 * El unico (id_exa, codigo_cartilla_exp) sigue valiendo: MySQL admite varios
 * nulos en un indice unico, asi que no limita cuantos no presentados hay.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_examen_postulante', function (Blueprint $table): void {
            $table->string('codigo_cartilla_exp', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_examen_postulante', function (Blueprint $table): void {
            $table->string('codigo_cartilla_exp', 30)->nullable(false)->change();
        });
    }
};
