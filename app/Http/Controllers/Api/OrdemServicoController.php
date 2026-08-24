<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrdemServico;
use App\Http\Requests\StoreOrdemServicoRequest;
use App\Http\Requests\UpdateOrdemServicoRequest;
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

    public function cancelar(Request $request, OrdemServico $ordens_servico)
    {
        $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:users,id']
        ]);

        try {
            $this->osService->cancelar($ordens_servico, $request->usuario_id);
            
            return response()->json([
                'message' => 'Ordem de Serviço cancelada com sucesso.'
            ]);
        } catch (\Exception $e) {
            // Captura as exceções disparadas pelo nosso Service
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function update(UpdateOrdemServicoRequest $request, OrdemServico $ordens_servico)
    {
        try {
            $os = $this->osService->atualizar($ordens_servico, $request->validated());
            return new OrdemServicoResource($os);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
    
    
}