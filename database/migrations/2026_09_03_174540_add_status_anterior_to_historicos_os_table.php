<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona `status_anterior` em historicos_os para rastreabilidade completa
 * das transições (Spec 8 — Req 2.1–2.4).
 *
 * Mantém a coluna `status` existente (alias de status_novo) para
 * compatibilidade; o Service passou a preencher ambas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historicos_os', function (Blueprint $table) {
            // Status de onde veio a transição (null = criação inicial da OS)
            $table->string('status_anterior')->nullable()->after('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::table('historicos_os', function (Blueprint $table) {
            $table->dropColumn('status_anterior');
        });
    }
};
