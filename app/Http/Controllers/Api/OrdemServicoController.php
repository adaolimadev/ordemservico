<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrdemServico;
use App\Http\Requests\StoreOrdemServicoRequest;
use App\Http\Resources\OrdemServicoResource;
use App\Services\OrdemServicoService;

class OrdemServicoController extends Controller
{
    public function __construct(private OrdemServicoService $osService) {}

    public function index()
    {
        $os = OrdemServico::with(['cliente', 'responsavel'])->paginate(15);
        return OrdemServicoResource::collection($os);
    }

    public function store(StoreOrdemServicoRequest $request)
    {
        // Passa os dados validados para o Service fazer a mágica no banco
        $os = $this->osService->criarOrdemServico($request->validated());
        
        return new OrdemServicoResource($os);
    }

    public function show(OrdemServico $ordens_servico) // O Laravel infere o nome da variável pelo route name
    {
        $ordens_servico->load(['cliente', 'responsavel', 'itens.equipamento', 'historicos']);
        return new OrdemServicoResource($ordens_servico);
    }
    
    
}