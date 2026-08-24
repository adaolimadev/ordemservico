<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\EquipamentoController;
use App\Http\Controllers\Api\OrdemServicoController;

/*
|--------------------------------------------------------------------------
| Rotas da API
|--------------------------------------------------------------------------
*/

// Rota padrão criada pelo Laravel para retornar o usuário logado via Sanctum
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Agrupando as rotas da nossa aplicação (com prefixo v1 para versionamento)
Route::prefix('v1')->group(function () {
    
    // O apiResource cria automaticamente as rotas: index, store, show, update e destroy
    Route::apiResource('clientes', ClienteController::class);
    Route::apiResource('equipamentos', EquipamentoController::class);
    Route::apiResource('ordens-servico', OrdemServicoController::class);
    

});