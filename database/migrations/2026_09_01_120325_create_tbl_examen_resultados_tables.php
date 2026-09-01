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
        Schema::create('tbl_examen', function (Blueprint $table) {
            $table->id('id_exa');
            $table->foreignId('id_pro')->constrained('tbl_proceso', 'id_pro')->cascadeOnDelete();
            $table->string('nombre_exa', 120);
            $table->date('fecha_exa')->nullable();
            $table->decimal('puntaje_acierto_exa', 6, 3)->default(1);
            $table->decimal('puntaje_error_exa', 6, 3)->default(-0.010);
            $table->decimal('puntaje_blanco_exa', 6, 3)->default(0.100);
            $table->timestamp('resuelto_en_exa')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['id_pro', 'nombre_exa']);
        });

        Schema::create('tbl_examen_importacion', function (Blueprint $table) {
            $table->id('id_exi');
            $table->foreignId('id_exa')->constrained('tbl_examen', 'id_exa')->cascadeOnDelete();
            $table->string('tipo_exi', 20);
            $table->string('archivo_exi', 255);
            $table->string('hash_exi', 64);
            $table->unsignedInteger('filas_exi')->default(0);
            $table->json('errores_exi')->nullable();
            $table->foreignId('id_usu')->nullable()->constrained('tbl_usuario', 'id_usu')->nullOnDelete();
            $table->timestamps();
            $table->unique(['id_exa', 'tipo_exi', 'hash_exi']);
        });

        Schema::create('tbl_examen_postulante', function (Blueprint $table) {
            $table->id('id_exp');
            $table->foreignId('id_exa')->constrained('tbl_examen', 'id_exa')->cascadeOnDelete();
            $table->foreignId('id_ins')->nullable()->constrained('tbl_inscripcion', 'id_ins')->nullOnDelete();
            $table->string('codigo_cartilla_exp', 30);
            $table->string('documento_exp', 20);
            $table->string('nombre_exp', 200);
            $table->string('codigo_carrera_exp', 30)->nullable();
            $table->string('codigo_modalidad_exp', 30)->nullable();
            $table->string('aula_origen_exp', 30)->nullable();
            $table->timestamps();
            $table->unique(['id_exa', 'codigo_cartilla_exp']);
            $table->unique(['id_exa', 'id_ins']);
            $table->index(['id_exa', 'documento_exp']);
        });

        Schema::create('tbl_examen_respuesta', function (Blueprint $table) {
            $table->id('id_exr');
            $table->foreignId('id_exp')->constrained('tbl_examen_postulante', 'id_exp')->cascadeOnDelete();
            $table->decimal('nota_directa_exr', 8, 4)->nullable();
            $table->decimal('nota_transformada_exr', 8, 4)->nullable();
            $table->unsignedSmallInteger('aciertos_exr')->default(0);
            $table->unsignedSmallInteger('errores_exr')->default(0);
            $table->unsignedSmallInteger('blancos_exr')->default(0);
            $table->unsignedSmallInteger('dobles_exr')->default(0);
            $table->json('respuestas_exr')->nullable();
            $table->timestamps();
            $table->unique('id_exp');
        });

        Schema::create('tbl_resultado', function (Blueprint $table) {
            $table->id('id_res');
            $table->foreignId('id_exa')->constrained('tbl_examen', 'id_exa')->cascadeOnDelete();
            $table->foreignId('id_exp')->constrained('tbl_examen_postulante', 'id_exp')->cascadeOnDelete();
            $table->foreignId('id_vac')->nullable()->constrained('tbl_vacante', 'id_vac')->nullOnDelete();
            $table->decimal('puntaje_res', 8, 4)->nullable();
            $table->unsignedInteger('orden_general_res')->nullable();
            $table->unsignedInteger('orden_carrera_res')->nullable();
            $table->string('estado_res', 20)->default('pendiente');
            $table->timestamps();
            $table->unique(['id_exa', 'id_exp']);
            $table->index(['id_exa', 'estado_res', 'orden_general_res']);
        });

        Schema::create('tbl_examen_aula', function (Blueprint $table) {
            $table->id('id_eau');
            $table->foreignId('id_exa')->constrained('tbl_examen', 'id_exa')->cascadeOnDelete();
            $table->foreignId('id_aul')->constrained('tbl_aula', 'id_aul');
            $table->foreignId('id_are')->constrained('tbl_area', 'id_are');
            $table->unsignedSmallInteger('capacidad_eau');
            $table->timestamps();
            $table->unique(['id_exa', 'id_aul']);
            $table->index(['id_exa', 'id_are']);
        });

        Schema::create('tbl_asignacion_examen', function (Blueprint $table) {
            $table->id('id_ase');
            $table->foreignId('id_exp')->constrained('tbl_examen_postulante', 'id_exp')->cascadeOnDelete();
            $table->foreignId('id_eau')->constrained('tbl_examen_aula', 'id_eau')->cascadeOnDelete();
            $table->unsignedSmallInteger('asiento_ase');
            $table->timestamps();
            $table->unique('id_exp');
            $table->unique(['id_eau', 'asiento_ase']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_asignacion_examen');
        Schema::dropIfExists('tbl_examen_aula');
        Schema::dropIfExists('tbl_resultado');
        Schema::dropIfExists('tbl_examen_respuesta');
        Schema::dropIfExists('tbl_examen_postulante');
        Schema::dropIfExists('tbl_examen_importacion');
        Schema::dropIfExists('tbl_examen');
    }
};
