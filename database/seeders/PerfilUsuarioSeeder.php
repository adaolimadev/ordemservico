<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerfilUsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $perfis = [
            ['descricao' => 'Administrador', 'created_at' => now(), 'updated_at' => now()],
            ['descricao' => 'Atendente', 'created_at' => now(), 'updated_at' => now()],
            ['descricao' => 'Técnico', 'created_at' => now(), 'updated_at' => now()],
            ['descricao' => 'Gestor', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('perfis_usuarios')->insert($perfis);
    }
}