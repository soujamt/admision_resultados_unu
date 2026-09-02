<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hayAsignacionesSinInscripcion = DB::table('tbl_asignacion_examen as asignacion')
            ->join('tbl_examen_postulante as postulante', 'postulante.id_exp', '=', 'asignacion.id_exp')
            ->whereNull('postulante.id_ins')
            ->exists();

        if ($hayAsignacionesSinInscripcion) {
            throw new LogicException('No se puede migrar una asignación cuyo postulante de examen no está vinculado a una inscripción.');
        }

        Schema::table('tbl_asignacion_examen', function (Blueprint $table) {
            $table->foreignId('id_ins')->nullable()->after('id_ase');
        });

        DB::statement(<<<'SQL'
            UPDATE tbl_asignacion_examen
            SET id_ins = (
                SELECT tbl_examen_postulante.id_ins
                FROM tbl_examen_postulante
                WHERE tbl_examen_postulante.id_exp = tbl_asignacion_examen.id_exp
            )
        SQL);

        Schema::table('tbl_asignacion_examen', function (Blueprint $table) {
            $table->foreignId('id_ins')->nullable(false)->change();
            $table->foreign('id_ins')->references('id_ins')->on('tbl_inscripcion')->cascadeOnDelete();
        });

        Schema::table('tbl_asignacion_examen', function (Blueprint $table) {
            $table->dropForeign(['id_exp']);
            $table->dropUnique(['id_exp']);
            $table->dropColumn('id_exp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hayAsignacionesSinPostulanteDeExamen = DB::table('tbl_asignacion_examen as asignacion')
            ->join('tbl_examen_aula as aula_examen', 'aula_examen.id_eau', '=', 'asignacion.id_eau')
            ->leftJoin('tbl_examen_postulante as postulante', function ($union): void {
                $union->on('postulante.id_ins', '=', 'asignacion.id_ins')
                    ->on('postulante.id_exa', '=', 'aula_examen.id_exa');
            })
            ->whereNull('postulante.id_exp')
            ->exists();

        if ($hayAsignacionesSinPostulanteDeExamen) {
            throw new LogicException('No se puede revertir la migración sin perder asignaciones que aún no tienen padrón TXT.');
        }

        Schema::table('tbl_asignacion_examen', function (Blueprint $table) {
            $table->foreignId('id_exp')->nullable()->after('id_ase');
        });

        DB::statement(<<<'SQL'
            UPDATE tbl_asignacion_examen
            SET id_exp = (
                SELECT tbl_examen_postulante.id_exp
                FROM tbl_examen_postulante
                INNER JOIN tbl_examen_aula
                    ON tbl_examen_aula.id_exa = tbl_examen_postulante.id_exa
                WHERE tbl_examen_postulante.id_ins = tbl_asignacion_examen.id_ins
                    AND tbl_examen_aula.id_eau = tbl_asignacion_examen.id_eau
            )
        SQL);

        Schema::table('tbl_asignacion_examen', function (Blueprint $table) {
            $table->foreignId('id_exp')->nullable(false)->change();
            $table->foreign('id_exp')->references('id_exp')->on('tbl_examen_postulante')->cascadeOnDelete();
            $table->unique('id_exp');
        });

        Schema::table('tbl_asignacion_examen', function (Blueprint $table) {
            $table->dropForeign(['id_ins']);
            $table->dropColumn('id_ins');
        });
    }
};
