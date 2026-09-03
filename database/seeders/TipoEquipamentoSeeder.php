<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoEquipamentoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['descricao' => 'Celular', 'created_at' => now(), 'updated_at' => now()],
            ['descricao' => 'Tablet', 'created_at' => now(), 'updated_at' => now()],
            ['descricao' => 'Notebook', 'created_at' => now(), 'updated_at' => now()],
            ['descricao' => 'Desktop', 'created_at' => now(), 'updated_at' => now()],
            ['descricao' => 'Monitor', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('tipos_equipamentos')->insertOrIgnore($tipos);
    }
}