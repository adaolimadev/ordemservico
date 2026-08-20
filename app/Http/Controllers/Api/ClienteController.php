<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Http\Resources\ClienteResource;

class ClienteController extends Controller
{
    public function index()
    {
        // Retorna todos os clientes paginados (15 por página)
        return ClienteResource::collection(Cliente::paginate(15));
    }

    public function store(StoreClienteRequest $request)
    {
        $cliente = Cliente::create($request->validated());
        return new ClienteResource($cliente);
    }

    public function show(Cliente $cliente)
    {
        return new ClienteResource($cliente);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        $cliente->update($request->validated());
        return new ClienteResource($cliente);
    }

    // O escopo pede "ativar/desativar", então em vez de deletar do banco, inativamos.
    public function destroy(Cliente $cliente)
    {
        $cliente->update(['situacao' => false]);
        return response()->json(['message' => 'Cliente desativado com sucesso.']);
    }
}