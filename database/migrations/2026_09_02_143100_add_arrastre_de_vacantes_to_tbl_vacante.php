<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las plazas que los Arts. 17, 18 y 19 suman al cuadro de vacantes se guardan
 * aparte de `cantidad_vac`: esa cifra la aprueba el Consejo Universitario por
 * el Art. 15 y tiene que seguir siendo legible despues del arrastre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_vacante', function (Blueprint $table): void {
            $table->unsignedSmallInteger('cantidad_arrastre_vac')->default(0)->after('cantidad_vac');
            $table->string('motivo_arrastre_vac', 255)->nullable()->after('cantidad_arrastre_vac');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_vacante', function (Blueprint $table): void {
            $table->dropColumn(['cantidad_arrastre_vac', 'motivo_arrastre_vac']);
        });
    }
};
