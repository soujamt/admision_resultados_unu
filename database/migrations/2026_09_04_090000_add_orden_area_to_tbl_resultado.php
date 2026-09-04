<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Orden de merito dentro del area academica del Art. 4, que agrupa varias
 * carreras. Se guarda junto a los otros dos ordenes en vez de calcularlo al
 * exportar, para que la regla de empate del Art. 85 viva en un solo sitio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_resultado', function (Blueprint $table): void {
            $table->unsignedInteger('orden_area_res')->nullable()->after('orden_carrera_res');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_resultado', function (Blueprint $table): void {
            $table->dropColumn('orden_area_res');
        });
    }
};
