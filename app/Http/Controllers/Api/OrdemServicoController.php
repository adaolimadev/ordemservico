<?php

namespace App\Http\Controllers\Api;

use App\Application\OrdemServico\DTO\CancelarOrdemServicoDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrdemServicoRequest;
use App\Http\Requests\UpdateOrdemServicoRequest;
use App\Http\Resources\OrdemServicoResource;
use App\Models\OrdemServico;
use App\Services\OrdemServicoService;
use Illuminate\Http\JsonResponse;
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
        // toDto() já inclui Auth::id() como usuarioId (nunca vem do payload)
        $os = $this->osService->criarOrdemServico($request->toDto());

        return new OrdemServicoResource($os);
    }

    public function show(OrdemServico $ordens_servico): OrdemServicoResource
    {
        $ordens_servico->load(['cliente', 'responsavel', 'itens.equipamento', 'historicos']);

        return new OrdemServicoResource($ordens_servico);
    }

    /**
     * Cancela a OS. Exceções de domínio tratadas pelo handler central.
     */
    public function cancelar(OrdemServico $ordens_servico): JsonResponse
    {
        $this->osService->cancelar(
            $ordens_servico,
            new CancelarOrdemServicoDTO(usuarioId: Auth::id()),
        );

        return response()->json(['message' => 'Ordem de Serviço cancelada com sucesso.']);
    }

    /**
     * Atualiza status/diagnóstico da OS. Exceções de domínio tratadas pelo handler central.
     */
    public function update(UpdateOrdemServicoRequest $request, OrdemServico $ordens_servico): OrdemServicoResource
    {
        // toDto() converte status para Enum e carrega Auth::id() como usuarioId
        $os = $this->osService->alterarStatus($ordens_servico, $request->toDto());

        return new OrdemServicoResource($os);
    }
}
