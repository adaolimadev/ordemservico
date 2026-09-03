<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrdemServicoRequest;
use App\Http\Requests\UpdateOrdemServicoRequest;
use App\Http\Resources\OrdemServicoResource;
use App\Models\OrdemServico;
use App\Services\OrdemServicoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class OrdemServicoController extends Controller
{
    public function __construct(private readonly OrdemServicoService $osService) {}

    public function index(): AnonymousResourceCollection
    {
        $os = OrdemServico::with(['cliente', 'responsavel'])->paginate(15);

        return OrdemServicoResource::collection($os);
    }

    public function store(StoreOrdemServicoRequest $request): OrdemServicoResource
    {
        // usuario_id vem do usuário autenticado, nunca do payload (Spec 1 — Req 4)
        $dados = array_merge($request->validated(), [
            'usuario_id' => Auth::id(),
        ]);

        $os = $this->osService->criarOrdemServico($dados);

        return new OrdemServicoResource($os);
    }

    public function show(OrdemServico $ordens_servico): OrdemServicoResource
    {
        $ordens_servico->load(['cliente', 'responsavel', 'itens.equipamento', 'historicos']);

        return new OrdemServicoResource($ordens_servico);
    }

    /**
     * Cancela a OS identificada por {ordens_servico}.
     * Exceções de domínio são tratadas pelo handler central (bootstrap/app.php).
     */
    public function cancelar(Request $request, OrdemServico $ordens_servico): JsonResponse
    {
        $this->osService->cancelar($ordens_servico, Auth::id());

        return response()->json(['message' => 'Ordem de Serviço cancelada com sucesso.']);
    }

    /**
     * Atualiza status e/ou diagnóstico da OS.
     * Exceções de domínio são tratadas pelo handler central (bootstrap/app.php).
     */
    public function update(UpdateOrdemServicoRequest $request, OrdemServico $ordens_servico): OrdemServicoResource
    {
        $dados = array_merge($request->validated(), [
            'usuario_id' => Auth::id(),
        ]);

        $os = $this->osService->atualizar($ordens_servico, $dados);

        return new OrdemServicoResource($os);
    }
}
