<?php

namespace App\Services;

use App\Application\OrdemServico\DTO\AlterarStatusOrdemServicoDTO;
use App\Application\OrdemServico\DTO\CancelarOrdemServicoDTO;
use App\Application\OrdemServico\DTO\ConcluirOrdemServicoDTO;
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
                array_map(fn ($id) => ['equipamento_id' => $id], $dto->equipamentoIds)
            );

            // status_anterior = null indica criação (sem transição prévia)
            $os->historicos()->create([
                'usuario_id'      => $dto->usuarioId,
                'status_anterior' => null,
                'status'          => StatusOSEnum::ABERTA,
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
     * Altera o status da OS respeitando as RN06–RN09.
     * Rejeita CONCLUIDA e CANCELADA — use concluir() e cancelar() para isso.
     *
     * @throws TransicaoStatusInvalidaException
     */
    public function alterarStatus(OrdemServico $os, AlterarStatusOrdemServicoDTO $dto): OrdemServico
    {
        $statusAtual = $os->status;
        $novoStatus  = $dto->novoStatus;

        // No-op idempotente: mesmo status, atualiza diagnóstico se fornecido
        if ($statusAtual === $novoStatus) {
            if ($dto->diagnostico !== null) {
                $os->update(['diagnostico' => $dto->diagnostico]);
            }

            return $os->load(['cliente', 'responsavel', 'itens.equipamento', 'historicos']);
        }

        if (! $statusAtual->podeTransitarPara($novoStatus)) {
            throw new TransicaoStatusInvalidaException($statusAtual, $novoStatus);
        }

        return DB::transaction(function () use ($os, $dto, $novoStatus, $statusAtual) {
            $update = ['status' => $novoStatus];

            if ($dto->diagnostico !== null) {
                $update['diagnostico'] = $dto->diagnostico;
            }

            $os->update($update);

            // Grava par status_anterior → status para rastreabilidade completa
            $os->historicos()->create([
                'usuario_id'      => $dto->usuarioId,
                'status_anterior' => $statusAtual,
                'status'          => $novoStatus,
            ]);

            return $os->load(['cliente', 'responsavel', 'itens.equipamento', 'historicos']);
        });
    }

    /**
     * Conclui a OS com diagnóstico final obrigatório.
     * Transição válida apenas a partir de EM_EXECUCAO.
     *
     * @throws TransicaoStatusInvalidaException
     */
    public function concluir(OrdemServico $os, ConcluirOrdemServicoDTO $dto): OrdemServico
    {
        $statusAtual = $os->status;

        if ($statusAtual === StatusOSEnum::CONCLUIDA) {
            throw new OrdemServicoJaConcluidaException();
        }

        if (! $statusAtual->podeTransitarPara(StatusOSEnum::CONCLUIDA)) {
            throw new TransicaoStatusInvalidaException($statusAtual, StatusOSEnum::CONCLUIDA);
        }

        return DB::transaction(function () use ($os, $dto, $statusAtual) {
            $os->update([
                'status'          => StatusOSEnum::CONCLUIDA,
                'diagnostico'     => $dto->diagnostico,
                'data_fechamento' => now(),
            ]);

            $os->historicos()->create([
                'usuario_id'      => $dto->usuarioId,
                'status_anterior' => $statusAtual,
                'status'          => StatusOSEnum::CONCLUIDA,
            ]);

            return $os->load(['cliente', 'responsavel', 'itens.equipamento', 'historicos']);
        });
    }

    /**
     * Cancela a OS registrando motivo obrigatório.
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

        if (! $os->status->podeTransitarPara(StatusOSEnum::CANCELADA)) {
            throw new TransicaoStatusInvalidaException($os->status, StatusOSEnum::CANCELADA);
        }

        return DB::transaction(function () use ($os, $dto) {
            $statusAntes = $os->status;

            $os->update([
                'status'          => StatusOSEnum::CANCELADA,
                'data_fechamento' => now(),
            ]);

            $os->historicos()->create([
                'usuario_id'      => $dto->usuarioId,
                'status_anterior' => $statusAntes,
                'status'          => StatusOSEnum::CANCELADA,
                'motivo'          => $dto->motivo,
            ]);

            return $os->load(['cliente', 'responsavel', 'itens.equipamento', 'historicos']);
        });
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
