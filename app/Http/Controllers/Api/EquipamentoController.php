<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Equipamento\EquipamentoIndexRequest;
use App\Http\Requests\StoreEquipamentoRequest;
use App\Http\Requests\UpdateEquipamentoRequest;
use App\Http\Resources\EquipamentoResource;
use App\Models\Equipamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EquipamentoController extends Controller
{
    public function index(EquipamentoIndexRequest $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 15);

        $equipamentos = Equipamento::query()
            ->with(['cliente:id,nome_razao_social', 'tipoEquipamento:id,descricao'])
            ->when(
                $request->filled('cliente_id'),
                fn ($q) => $q->where('cliente_id', $request->input('cliente_id'))
            )
            ->when(
                $request->has('situacao'),
                fn ($q) => $q->where('situacao', $request->boolean('situacao'))
            )
            ->when(
                $request->filled('numero_serie'),
                fn ($q) => $q->where('numero_serie', 'like', '%' . $request->input('numero_serie') . '%')
            )
            ->paginate($perPage);

        return EquipamentoResource::collection($equipamentos);
    }

    public function store(StoreEquipamentoRequest $request): EquipamentoResource
    {
        $dto         = $request->toDto();
        $equipamento = Equipamento::create([
            'cliente_id'          => $dto->clienteId,
            'tipo_equipamento_id'  => $dto->tipoEquipamentoId,
            'numero_serie'        => $dto->numeroSerie,
            'marca'               => $dto->marca,
            'descricao'           => $dto->descricao,
            'situacao'            => $dto->situacao,
        ]);
        $equipamento->load(['cliente', 'tipoEquipamento']);

        return new EquipamentoResource($equipamento);
    }

    public function show(Equipamento $equipamento): EquipamentoResource
    {
        $equipamento->load(['cliente', 'tipoEquipamento']);

        return new EquipamentoResource($equipamento);
    }

    public function update(UpdateEquipamentoRequest $request, Equipamento $equipamento): EquipamentoResource
    {
        $dto = $request->toDto();
        $equipamento->update([
            'cliente_id'          => $dto->clienteId,
            'tipo_equipamento_id'  => $dto->tipoEquipamentoId,
            'numero_serie'        => $dto->numeroSerie,
            'marca'               => $dto->marca,
            'descricao'           => $dto->descricao,
            'situacao'            => $dto->situacao ?? $equipamento->situacao,
        ]);
        $equipamento->load(['cliente', 'tipoEquipamento']);

        return new EquipamentoResource($equipamento);
    }

    public function destroy(Equipamento $equipamento): JsonResponse
    {
        $equipamento->update(['situacao' => false]);

        return response()->json(['message' => 'Equipamento desativado com sucesso.']);
    }
}
