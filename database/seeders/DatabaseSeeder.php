<?php

namespace Database\Seeders;

use App\Enums\PerfilEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PerfilUsuarioSeeder::class,
            TipoEquipamentoSeeder::class,
        ]);

        $adminPerfilId = DB::table('perfis_usuarios')
            ->where('descricao', 'ADMINISTRADOR')
            ->value('id');

        User::firstOrCreate(
            ['email' => 'admin@sistema.com.br'],
            [
                'name'             => 'Admin do Sistema',
                'password'         => Hash::make('password123'),
                'cargo'            => 'Administrador Geral',
                'situacao'         => true,
                'perfil'           => PerfilEnum::ADMINISTRADOR,
                'perfil_acesso_id' => $adminPerfilId,
            ]
        );
    }
}
