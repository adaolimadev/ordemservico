<?php

namespace App\Services;

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

    public function criarOrdemServico(array $dados): OrdemServico
    {
        return DB::transaction(function () use ($dados) {
            // 1. Cria a OS principal
            $os = OrdemServico::create([
                'numero'        => $this->gerarNumeroOS(),
                'cliente_id'    => $dados['cliente_id'],
                'usuario_id'    => $dados['usuario_id'],
                'descricao'     => $dados['descricao'],
                'prioridade'    => $dados['prioridade'],
                'status'        => StatusOSEnum::ABERTA,
                // Preenchido explicitamente para não depender do default do banco (Spec 2 — Req 5)
                'data_abertura' => now(),
            ]);

            // 2. Adiciona os itens (equipamentos)
            $os->itens()->createMany(
                array_map(
                    fn ($id) => ['equipamento_id' => $id],
                    $dados['equipamentos'],
                )
            );

            // 3. Registra o histórico inicial
            $os->historicos()->create([
                'usuario_id' => $dados['usuario_id'],
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
     * Altera o status de uma OS respeitando a máquina de estados (RN06–RN09).
     *
     * - Idempotente: mesmo status → no-op sem gravar histórico.
     * - Valida a transição via StatusOSEnum::podeTransitarPara.
     * - Preenche data_fechamento quando o destino é terminal.
     *
     * @throws TransicaoStatusInvalidaException
     * @throws OrdemServicoJaConcluidaException
     * @throws OrdemServicoJaCanceladaException
     */
    public function alterarStatus(OrdemServico $os, StatusOSEnum $novoStatus, int $usuarioId): OrdemServico
    {
        $statusAtual = $os->status;

        // No-op idempotente: mesmo status, sem gravar histórico
        if ($statusAtual === $novoStatus) {
            return $os;
        }

        // Valida a transição pela máquina de estados
        if (! $statusAtual->podeTransitarPara($novoStatus)) {
            throw new TransicaoStatusInvalidaException($statusAtual, $novoStatus);
        }

        return DB::transaction(function () use ($os, $novoStatus, $usuarioId) {
            $update = ['status' => $novoStatus];

            // Estados terminais: registra o fechamento
            if ($novoStatus->ehTerminal()) {
                $update['data_fechamento'] = now();
            }

            $os->update($update);

            $os->historicos()->create([
                'usuario_id' => $usuarioId,
                'status'     => $novoStatus,
            ]);

            return $os->load(['cliente', 'responsavel', 'itens.equipamento', 'historicos']);
        });
    }

    /**
     * Cancela uma OS.
     * Atalho semântico sobre alterarStatus — mantém regras de negócio centralizadas.
     *
     * @throws OrdemServicoJaConcluidaException
     * @throws OrdemServicoJaCanceladaException
     * @throws TransicaoStatusInvalidaException
     */
    public function cancelar(OrdemServico $os, int $usuarioId): OrdemServico
    {
        // Exceções semânticas antes da validação genérica para mensagens melhores
        if ($os->status === StatusOSEnum::CONCLUIDA) {
            throw new OrdemServicoJaConcluidaException();
        }

        if ($os->status === StatusOSEnum::CANCELADA) {
            throw new OrdemServicoJaCanceladaException();
        }

        return $this->alterarStatus($os, StatusOSEnum::CANCELADA, $usuarioId);
    }

    /**
     * Atualiza diagnóstico e/ou status de uma OS.
     * Delegado à máquina de estados quando o status muda.
     *
     * @throws TransicaoStatusInvalidaException
     * @throws OrdemServicoJaConcluidaException
     * @throws OrdemServicoJaCanceladaException
     */
    public function atualizar(OrdemServico $os, array $dados): OrdemServico
    {
        $novoStatus = StatusOSEnum::from($dados['status']);

        // Delega a transição para o método dedicado
        $os = $this->alterarStatus($os, $novoStatus, $dados['usuario_id']);

        // Atualiza diagnóstico se fornecido
        if (array_key_exists('diagnostico', $dados) && $dados['diagnostico'] !== null) {
            $os->update(['diagnostico' => $dados['diagnostico']]);
        }

        return $os->load(['cliente', 'responsavel', 'itens.equipamento', 'historicos']);
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
