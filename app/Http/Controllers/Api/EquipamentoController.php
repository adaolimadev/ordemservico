<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipamento;
use App\Http\Requests\StoreEquipamentoRequest;
use App\Http\Requests\UpdateEquipamentoRequest;
use App\Http\Resources\EquipamentoResource;

class EquipamentoController extends Controller
{
    public function index()
    {
        // Carrega cliente e tipo_equipamento junto para otimizar o banco
        $equipamentos = Equipamento::with(['cliente', 'tipoEquipamento'])->paginate(15);
        return EquipamentoResource::collection($equipamentos);
    }

    public function store(StoreEquipamentoRequest $request)
    {
        $equipamento = Equipamento::create($request->validated());
        $equipamento->load(['cliente', 'tipoEquipamento']); // Carrega para a resposta
        
        return new EquipamentoResource($equipamento);
    }

    public function show(Equipamento $equipamento)
    {
        $equipamento->load(['cliente', 'tipoEquipamento']);
        return new EquipamentoResource($equipamento);
    }

    public function update(UpdateEquipamentoRequest $request, Equipamento $equipamento)
    {
        $equipamento->update($request->validated());
        $equipamento->load(['cliente', 'tipoEquipamento']);
        
        return new EquipamentoResource($equipamento);
    }

    public function destroy(Equipamento $equipamento)
    {
        $equipamento->update(['situacao' => false]);
        return response()->json(['message' => 'Equipamento desativado com sucesso.']);
    }
}