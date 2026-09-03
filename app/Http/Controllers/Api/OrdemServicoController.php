<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrdemServico;
use App\Http\Requests\StoreOrdemServicoRequest;
use App\Http\Requests\UpdateOrdemServicoRequest;
use App\Http\Resources\OrdemServicoResource;
use App\Services\OrdemServicoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        // usuario_id vem do usuário autenticado, nunca do payload (Spec 1 — Req 4)
        $dados = array_merge($request->validated(), [
            'usuario_id' => Auth::id(),
        ]);

        $os = $this->osService->criarOrdemServico($dados);

        return new OrdemServicoResource($os);
    }

    public function show(OrdemServico $ordens_servico)
    {
        $ordens_servico->load(['cliente', 'responsavel', 'itens.equipamento', 'historicos']);
        return new OrdemServicoResource($ordens_servico);
    }

    public function cancelar(Request $request, OrdemServico $ordens_servico)
    {
        try {
            // usuario_id vem do usuário autenticado (Spec 1 — Req 4)
            $this->osService->cancelar($ordens_servico, Auth::id());

            return response()->json([
                'message' => 'Ordem de Serviço cancelada com sucesso.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function update(UpdateOrdemServicoRequest $request, OrdemServico $ordens_servico)
    {
        try {
            // usuario_id vem do usuário autenticado (Spec 1 — Req 4)
            $dados = array_merge($request->validated(), [
                'usuario_id' => Auth::id(),
            ]);

            $os = $this->osService->atualizar($ordens_servico, $dados);
            return new OrdemServicoResource($os);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}