<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tbl_carrera', function (Blueprint $table): void {
            $table->decimal('puntaje_minimo_car', 5, 2)->nullable()->after('nombre_corto_car');
        });

        Schema::table('tbl_examen', function (Blueprint $table): void {
            $table->decimal('puntaje_minimo_exa', 8, 4)->default(50)->after('puntaje_blanco_exa');
            $table->decimal('umbral_factor_dificultad_exa', 5, 2)->default(40)->after('puntaje_minimo_exa');
            $table->boolean('aplicar_factor_dificultad_exa')->default(true)->after('umbral_factor_dificultad_exa');
        });

        Schema::table('tbl_examen_postulante', function (Blueprint $table): void {
            $table->timestamp('anulado_en_exp')->nullable()->after('aula_origen_exp');
            $table->string('motivo_anulacion_exp', 255)->nullable()->after('anulado_en_exp');
        });

        Schema::table('tbl_resultado', function (Blueprint $table): void {
            $table->decimal('puntaje_directo_res', 8, 4)->nullable()->after('id_vac');
            $table->decimal('factor_dificultad_res', 8, 6)->default(1)->after('puntaje_directo_res');
            $table->decimal('puntaje_minimo_res', 8, 4)->nullable()->after('puntaje_res');
            $table->boolean('repesca_res')->default(false)->after('orden_carrera_res');
            $table->string('motivo_res', 255)->nullable()->after('estado_res');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_resultado', function (Blueprint $table): void {
            $table->dropColumn(['puntaje_directo_res', 'factor_dificultad_res', 'puntaje_minimo_res', 'repesca_res', 'motivo_res']);
        });

        Schema::table('tbl_examen_postulante', function (Blueprint $table): void {
            $table->dropColumn(['anulado_en_exp', 'motivo_anulacion_exp']);
        });

        Schema::table('tbl_examen', function (Blueprint $table): void {
            $table->dropColumn(['puntaje_minimo_exa', 'umbral_factor_dificultad_exa', 'aplicar_factor_dificultad_exa']);
        });

        Schema::table('tbl_carrera', function (Blueprint $table): void {
            $table->dropColumn('puntaje_minimo_car');
        });
    }
};
