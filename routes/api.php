<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EquipamentoController;
use App\Http\Controllers\Api\OrdemServicoController;
use App\Http\Controllers\Api\TipoEquipamentoController;
use App\Http\Controllers\Api\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas da API — v1
|--------------------------------------------------------------------------
*/

// ── Rotas públicas (sem autenticação) ────────────────────────────────────
Route::prefix('v1/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// ── Rotas protegidas ─────────────────────────────────────────────────────
Route::prefix('v1')
    ->middleware(['auth:sanctum', 'usuario.ativo'])
    ->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me',      [AuthController::class, 'me']);
        });

        // Clientes
        Route::apiResource('clientes', ClienteController::class);

        // Equipamentos
        Route::apiResource('equipamentos', EquipamentoController::class);

        // Tipos de Equipamento (somente leitura por ora)
        Route::get('tipos-equipamentos', [TipoEquipamentoController::class, 'index']);

        // Dashboard
        Route::get('dashboard/indicadores', [DashboardController::class, 'indicadores']);

        // Ordens de Serviço
        // Ações dedicadas — registradas ANTES do apiResource para evitar conflito de binding
        Route::patch('ordens-servico/{ordemServico}/status',   [OrdemServicoController::class, 'alterarStatus']);
        Route::post ('ordens-servico/{ordemServico}/concluir', [OrdemServicoController::class, 'concluir']);
        Route::post ('ordens-servico/{ordemServico}/cancelar', [OrdemServicoController::class, 'cancelar']);

        // CRUD básico — sem update nem destroy (inativação/cancelamento via rotas dedicadas)
        Route::apiResource('ordens-servico', OrdemServicoController::class)
            ->parameters(['ordens-servico' => 'ordemServico'])
            ->except(['update', 'destroy']);

        // ── Usuários (exclusivo para ADMINISTRADOR) ───────────────────────
        Route::middleware('can:gerenciar-usuarios')->group(function () {
            Route::patch('usuarios/{usuario}/situacao', [UsuarioController::class, 'alterarSituacao']);
            Route::patch('usuarios/{usuario}/perfil',   [UsuarioController::class, 'alterarPerfil']);
            Route::apiResource('usuarios', UsuarioController::class);
        });
    });
