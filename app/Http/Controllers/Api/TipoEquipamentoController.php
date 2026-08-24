<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoEquipamento;
use App\Models\Equipamento;
use App\Http\Requests\StoreTipoEquipamentoRequest;
use App\Http\Requests\UpdateTipoEquipamentoRequest;
use App\Http\Resources\TipoEquipamentoResource;

class TipoEquipamentoController extends Controller
{
    public function index()
    {
        return TipoEquipamentoResource::collection(TipoEquipamento::all());
    }

    public function store(StoreTipoEquipamentoRequest $request)
    {
        $tipo = TipoEquipamento::create($request->validated());
        return new TipoEquipamentoResource($tipo);
    }

    public function show(TipoEquipamento $tipoEquipamento)
    {
        return new TipoEquipamentoResource($tipoEquipamento);
    }

    public function update(UpdateTipoEquipamentoRequest $request, TipoEquipamento $tipoEquipamento)
    {
        $tipoEquipamento->update($request->validated());
        return new TipoEquipamentoResource($tipoEquipamento);
    }

    public function destroy(TipoEquipamento $tipoEquipamento)
    {
        // Proteção: Verifica se existe algum equipamento usando este tipo
        $emUso = Equipamento::where('tipo_equipamento_id', $tipoEquipamento->id)->exists();

        if ($emUso) {
            return response()->json([
                'message' => 'Não é possível excluir este tipo pois existem equipamentos vinculados a ele.'
            ], 422);
        }

        $tipoEquipamento->delete();
        return response()->json(['message' => 'Tipo de equipamento excluído com sucesso.']);
    }
}