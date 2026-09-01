<?php

use App\Enums\EstadoRegistro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_usuario', function (Blueprint $table) {
            $table->id('id_usu');
            $table->foreignId('id_rol')->constrained('tbl_rol', 'id_rol');
            $table->string('nombre_usu', 150);
            $table->string('usuario_usu', 100)->unique();
            $table->string('correo_usu', 150)->unique();
            $table->string('clave_usu');
            $table->timestamp('clave_cambiada_en_usu')->nullable();
            $table->unsignedTinyInteger('estado_usu')->default(EstadoRegistro::Habilitado->value);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('tbl_usuario');
    }
};
