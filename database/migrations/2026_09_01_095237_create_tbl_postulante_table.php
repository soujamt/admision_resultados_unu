<?php

use App\Enums\EstadoRegistro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La persona, no la postulacion: aqui vive lo que la identifica y no depende
 * del proceso. Lo que se declara para una convocatoria concreta (carrera,
 * modalidad, colegio, foto) va en tbl_inscripcion.
 *
 * El ubigeo y el colegio se referencian por su codigo oficial y no por el id
 * autoincremental, porque ese codigo es la llave con la que llegan y salen los
 * datos hacia el MINEDU.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_postulante', function (Blueprint $table) {
            $table->id('id_pos');
            $table->unsignedTinyInteger('tipo_documento_pos');
            $table->string('numero_documento_pos', 20);
            $table->boolean('solo_un_apellido_pos')->default(false);
            $table->string('primer_apellido_pos', 80);
            $table->string('segundo_apellido_pos', 80)->nullable();
            $table->string('nombres_pos', 120);
            $table->string('apellido_casada_pos', 80)->nullable();
            $table->string('estado_civil_pos', 15);
            $table->char('sexo_pos', 1);
            $table->date('fecha_nacimiento_pos');
            $table->foreignId('id_pai')->constrained('tbl_pais', 'id_pai');
            $table->foreignId('id_nac')->constrained('tbl_nacionalidad', 'id_nac');
            $table->char('ubigeo_nacimiento_pos', 6)->nullable();
            $table->boolean('condicion_discapacidad_pos')->default(false);
            $table->foreignId('id_tdi')->nullable()->constrained('tbl_tipo_discapacidad', 'id_tdi');
            $table->string('celular_pos', 15)->nullable();
            $table->string('telefono_pos', 15)->nullable();
            $table->string('correo_pos', 150)->nullable();
            $table->char('ubigeo_direccion_pos', 6)->nullable();
            $table->string('direccion_pos', 200)->nullable();
            $table->string('lengua_materna_pos', 60)->nullable();
            $table->foreignId('id_ide')->nullable()->constrained('tbl_identidad_etnica', 'id_ide');
            $table->foreignId('id_lna')->nullable()->constrained('tbl_lengua_nativa', 'id_lna');
            $table->foreignId('id_lex')->nullable()->constrained('tbl_lengua_extranjera', 'id_lex');
            $table->unsignedTinyInteger('estado_pos')->default(EstadoRegistro::Habilitado->value);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tipo_documento_pos', 'numero_documento_pos']);
            $table->index(['primer_apellido_pos', 'segundo_apellido_pos', 'nombres_pos'], 'tbl_postulante_nombres_index');

            $table->foreign('ubigeo_nacimiento_pos')->references('codigo_ubi')->on('tbl_ubigeo');
            $table->foreign('ubigeo_direccion_pos')->references('codigo_ubi')->on('tbl_ubigeo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_postulante');
    }
};
