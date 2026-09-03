<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrdemServico\AlterarStatusOrdemServicoRequest;
use App\Http\Requests\OrdemServico\CancelarOrdemServicoRequest;
use App\Http\Requests\OrdemServico\ConcluirOrdemServicoRequest;
use App\Http\Requests\OrdemServico\OrdemServicoIndexRequest;
use App\Http\Requests\StoreOrdemServicoRequest;
use App\Http\Resources\OrdemServicoResource;
use App\Models\OrdemServico;
use App\Services\OrdemServicoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrdemServicoController extends Controller
{
    public function __construct(private readonly OrdemServicoService $osService) {}

    /**
     * GET /ordens-servico
     * Listagem com filtros, ordenação e paginação configurável (Spec 7).
     */
    public function index(OrdemServicoIndexRequest $request): AnonymousResourceCollection
    {
        $sort    = $request->input('sort', '-data_abertura');
        $coluna  = ltrim($sort, '-');
        $direcao = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $perPage = (int) $request->input('per_page', 15);

        $os = OrdemServico::query()
            ->with(['cliente:id,nome_razao_social', 'responsavel:id,name'])
            ->filtrar($request->validated())
            ->orderBy($coluna, $direcao)
            ->paginate($perPage);

        return OrdemServicoResource::collection($os);
    }

    public function store(StoreOrdemServicoRequest $request): OrdemServicoResource
    {
        $os = $this->osService->criarOrdemServico($request->toDto());

        return new OrdemServicoResource($os);
    }

    /**
     * GET /ordens-servico/{id}
     * Eager loading completo para evitar N+1 (Spec 7 — Req 3.1).
     */
    public function show(OrdemServico $ordemServico): OrdemServicoResource
    {
        $ordemServico->load([
            'cliente',
            'responsavel',
            'itens.equipamento.tipoEquipamento',
            'historicos.usuario',
        ]);

        return new OrdemServicoResource($ordemServico);
    }

    /**
     * PATCH /ordens-servico/{id}/status
     * Transições de fluxo (exclui CONCLUIDA e CANCELADA).
     */
    public function alterarStatus(AlterarStatusOrdemServicoRequest $request, OrdemServico $ordemServico): OrdemServicoResource
    {
        $os = $this->osService->alterarStatus($ordemServico, $request->toDto());

        return new OrdemServicoResource($os);
    }

    /**
     * POST /ordens-servico/{id}/concluir
     */
    public function concluir(ConcluirOrdemServicoRequest $request, OrdemServico $ordemServico): OrdemServicoResource
    {
        $os = $this->osService->concluir($ordemServico, $request->toDto());

        return new OrdemServicoResource($os);
    }

    /**
     * POST /ordens-servico/{id}/cancelar
     */
    public function cancelar(CancelarOrdemServicoRequest $request, OrdemServico $ordemServico): JsonResponse
    {
        $this->osService->cancelar($ordemServico, $request->toDto());

        return response()->json(['message' => 'Ordem de Serviço cancelada com sucesso.']);
    }
}
