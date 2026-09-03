<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Desabilita transacao automatica para DDL de rename no PostgreSQL
    protected $connection = null;

    public function up(): void
    {
        DB::statement('ALTER TABLE users RENAME COLUMN ativo TO situacao');
        Schema::table('users', function (Blueprint $table) {
            $table->string('perfil')->default('ATENDENTE');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('perfil');
        });
        DB::statement('ALTER TABLE users RENAME COLUMN situacao TO ativo');
    }
};