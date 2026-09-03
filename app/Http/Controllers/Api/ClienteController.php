<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\ClienteIndexRequest;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClienteController extends Controller
{
    public function index(ClienteIndexRequest $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 15);

        $clientes = Cliente::query()
            ->when(
                $request->has('situacao'),
                fn ($q) => $q->where('situacao', $request->boolean('situacao'))
            )
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(function ($q) use ($request) {
                    $q->where('nome_razao_social', 'like', '%' . $request->input('search') . '%')
                      ->orWhere('cpf_cnpj', 'like', '%' . $request->input('search') . '%');
                })
            )
            ->paginate($perPage);

        return ClienteResource::collection($clientes);
    }

    public function store(StoreClienteRequest $request): ClienteResource
    {
        $dto     = $request->toDto();
        $cliente = Cliente::create([
            'tipo_pessoa'       => $dto->tipoPessoa,
            'nome_razao_social' => $dto->nomeRazaoSocial,
            'cpf_cnpj'          => $dto->cpfCnpj,
            'email'             => $dto->email,
            'telefone'          => $dto->telefone,
            'endereco'          => $dto->endereco,
            'situacao'          => $dto->situacao,
        ]);

        return new ClienteResource($cliente);
    }

    public function show(Cliente $cliente): ClienteResource
    {
        return new ClienteResource($cliente);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): ClienteResource
    {
        $dto = $request->toDto();
        $cliente->update([
            'tipo_pessoa'       => $dto->tipoPessoa,
            'nome_razao_social' => $dto->nomeRazaoSocial,
            'cpf_cnpj'          => $dto->cpfCnpj,
            'email'             => $dto->email,
            'telefone'          => $dto->telefone,
            'endereco'          => $dto->endereco,
            'situacao'          => $dto->situacao ?? $cliente->situacao,
        ]);

        return new ClienteResource($cliente);
    }

    public function destroy(Cliente $cliente): JsonResponse
    {
        $cliente->update(['situacao' => false]);

        return response()->json(['message' => 'Cliente desativado com sucesso.']);
    }
}
