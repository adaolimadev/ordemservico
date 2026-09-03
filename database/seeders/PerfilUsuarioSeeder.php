<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerfilUsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $perfis = [
            ['descricao' => 'ADMINISTRADOR', 'created_at' => now(), 'updated_at' => now()],
            ['descricao' => 'ATENDENTE',     'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('perfis_usuarios')->insertOrIgnore($perfis);
    }
}
