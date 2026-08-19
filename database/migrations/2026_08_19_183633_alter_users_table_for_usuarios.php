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
        // Aproveitando a tabela padrão do Laravel para autenticação
        Schema::table('users', function (Blueprint $table) {
            $table->string('cargo')->nullable();
            $table->boolean('ativo')->default(true);
            $table->foreignId('perfil_acesso_id')->nullable()->constrained('perfis_usuarios');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
