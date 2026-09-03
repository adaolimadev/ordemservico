<?php

namespace App\Services;

use App\Application\OrdemServico\DTO\AlterarStatusOrdemServicoDTO;
use App\Application\OrdemServico\DTO\CancelarOrdemServicoDTO;
use App\Application\OrdemServico\DTO\CriarOrdemServicoDTO;
use App\Enums\StatusOSEnum;
use App\Exceptions\Domain\OrdemServicoJaCanceladaException;
use App\Exceptions\Domain\OrdemServicoJaConcluidaException;
use App\Exceptions\Domain\TransicaoStatusInvalidaException;
use App\Models\OrdemServico;
use Illuminate\Support\Facades\DB;

class OrdemServicoService
{
    /*
    |--------------------------------------------------------------------------
    | Criação
    |--------------------------------------------------------------------------
    */

    public function criarOrdemServico(CriarOrdemServicoDTO $dto): OrdemServico
    {
        return DB::transaction(function () use ($dto) {
            $os = OrdemServico::create([
                'numero'        => $this->gerarNumeroOS(),
                'cliente_id'    => $dto->clienteId,
                'usuario_id'    => $dto->usuarioId,
                'descricao'     => $dto->descricao,
                'prioridade'    => $dto->prioridade,
                'status'        => StatusOSEnum::ABERTA,
                'data_abertura' => now(),
            ]);

            $os->itens()->createMany(
                array_map(
                    fn ($id) => ['equipamento_id' => $id],
                    $dto->equipamentoIds,
                )
            );

            $os->historicos()->create([
                'usuario_id' => $dto->usuarioId,
                'status'     => StatusOSEnum::ABERTA,
            ]);

            return $os->load(['cliente', 'itens.equipamento', 'historicos']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Máquina de estados
    |--------------------------------------------------------------------------
    */

    /**
     * Altera o status de uma OS respeitando as RN06–RN09.
     *
     * @throws TransicaoStatusInvalidaException
     */
    public function alterarStatus(OrdemServico $os, AlterarStatusOrdemServicoDTO $dto): OrdemServico
    {
        $statusAtual = $os->status;
        $novoStatus  = $dto->novoStatus;

        // No-op idempotente: mesmo status, sem gravar histórico
        if ($statusAtual === $novoStatus) {
            // Ainda atualiza diagnóstico se fornecido
            if ($dto->diagnostico !== null) {
                $os->update(['diagnostico' => $dto->diagnostico]);
            }

            return $os->load(['cliente', 'responsavel', 'itens.equipamento', 'historicos']);
        }

        // Valida a transição pela máquina de estados
        if (! $statusAtual->podeTransitarPara($novoStatus)) {
            throw new TransicaoStatusInvalidaException($statusAtual, $novoStatus);
        }

        return DB::transaction(function () use ($os, $dto, $novoStatus) {
            $update = ['status' => $novoStatus];

            if ($novoStatus->ehTerminal()) {
                $update['data_fechamento'] = now();
            }

            if ($dto->diagnostico !== null) {
                $update['diagnostico'] = $dto->diagnostico;
            }

            $os->update($update);

            $os->historicos()->create([
                'usuario_id' => $dto->usuarioId,
                'status'     => $novoStatus,
            ]);

            return $os->load(['cliente', 'responsavel', 'itens.equipamento', 'historicos']);
        });
    }

    /**
     * Cancela uma OS.
     *
     * @throws OrdemServicoJaConcluidaException
     * @throws OrdemServicoJaCanceladaException
     * @throws TransicaoStatusInvalidaException
     */
    public function cancelar(OrdemServico $os, CancelarOrdemServicoDTO $dto): OrdemServico
    {
        if ($os->status === StatusOSEnum::CONCLUIDA) {
            throw new OrdemServicoJaConcluidaException();
        }

        if ($os->status === StatusOSEnum::CANCELADA) {
            throw new OrdemServicoJaCanceladaException();
        }

        return $this->alterarStatus($os, new AlterarStatusOrdemServicoDTO(
            novoStatus: StatusOSEnum::CANCELADA,
            usuarioId:  $dto->usuarioId,
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Utilitários privados
    |--------------------------------------------------------------------------
    */

    private function gerarNumeroOS(): string
    {
        $data   = now()->format('Ymd');
        $ultimo = OrdemServico::whereDate('created_at', now()->toDateString())->count() + 1;

        return 'OS-' . $data . '-' . str_pad($ultimo, 4, '0', STR_PAD_LEFT);
    }
}
