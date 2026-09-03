<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona coluna `motivo` em historicos_os para registrar o motivo
     * de cancelamento ou outras observações do operador (Spec 5 — Req 3.1).
     */
    public function up(): void
    {
        Schema::table('historicos_os', function (Blueprint $table) {
            $table->text('motivo')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('historicos_os', function (Blueprint $table) {
            $table->dropColumn('motivo');
        });
    }
};
