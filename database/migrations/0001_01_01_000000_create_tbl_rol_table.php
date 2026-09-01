<?php

use App\Enums\EstadoRegistro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_rol', function (Blueprint $table) {
            $table->id('id_rol');
            $table->string('nombre_rol', 60)->unique();
            $table->string('descripcion_rol', 255)->nullable();
            $table->json('permisos_rol')->nullable();
            $table->unsignedTinyInteger('estado_rol')->default(EstadoRegistro::Habilitado->value);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_rol');
    }
};
