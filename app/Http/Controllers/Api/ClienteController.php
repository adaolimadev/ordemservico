<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClienteController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ClienteResource::collection(Cliente::paginate(15));
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

    // O escopo pede "ativar/desativar" — em vez de deletar, inativamos (Spec 6 terá endpoint dedicado)
    public function destroy(Cliente $cliente): JsonResponse
    {
        $cliente->update(['situacao' => false]);

        return response()->json(['message' => 'Cliente desativado com sucesso.']);
    }
}
