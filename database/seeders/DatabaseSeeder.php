<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            UsuarioSeeder::class,
            MaestroGeneralSeeder::class,
            ModalidadSeeder::class,
            EstructuraAcademicaSeeder::class,
        ]);
    }
}
