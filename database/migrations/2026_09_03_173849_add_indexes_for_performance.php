<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona índices nas colunas de filtro/ordenação mais utilizadas (Spec 7 — Req 4).
 * FKs já são indexadas automaticamente pelo constrained(); aqui cobrimos
 * os campos de busca que não são FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->index('status');
            $table->index('prioridade');
            $table->index('data_abertura');
        });

        Schema::table('equipamentos', function (Blueprint $table) {
            $table->index('situacao');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->index('situacao');
        });

        Schema::table('historicos_os', function (Blueprint $table) {
            $table->index('ordem_servico_id');
        });

        Schema::table('ordem_servico_itens', function (Blueprint $table) {
            $table->index('equipamento_id');
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['prioridade']);
            $table->dropIndex(['data_abertura']);
        });

        Schema::table('equipamentos', function (Blueprint $table) {
            $table->dropIndex(['situacao']);
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['situacao']);
        });

        Schema::table('historicos_os', function (Blueprint $table) {
            $table->dropIndex(['ordem_servico_id']);
        });

        Schema::table('ordem_servico_itens', function (Blueprint $table) {
            $table->dropIndex(['equipamento_id']);
        });
    }
};
