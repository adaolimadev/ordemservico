<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roda os seeders de domínios fixos (Perfis e Tipos de Equipamentos)
        $this->call([
            PerfilUsuarioSeeder::class,
            TipoEquipamentoSeeder::class,
        ]);

        // Busca o ID do perfil Administrador
        $adminPerfilId = DB::table('perfis_usuarios')
            ->where('descricao', 'Administrador')
            ->value('id');

        // Cria o usuário padrão
        User::create([
            'name' => 'Admin do Sistema',
            'email' => 'admin@sistema.com.br',
            'password' => Hash::make('password123'), // Mude em produção
            'cargo' => 'Administrador Geral',
            'ativo' => true,
            'perfil_acesso_id' => $adminPerfilId,
        ]);
    }
}