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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->char('tipo_pessoa', 1)->default('F'); // F ou J
            $table->string('nome_razao_social');
            $table->string('cpf_cnpj')->unique();
            $table->string('email');
            $table->string('telefone');
            $table->string('endereco'); // Novo campo adicionado
            $table->boolean('situacao')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
