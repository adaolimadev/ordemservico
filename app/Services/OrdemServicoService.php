<?php

namespace App\Services;

use App\Models\OrdemServico;
use App\Enums\StatusOSEnum;
use Illuminate\Support\Facades\DB;

class OrdemServicoService
{
    public function criarOrdemServico(array $dados)
    {
        return DB::transaction(function () use ($dados) {
            // 1. Cria a OS principal
            $os = OrdemServico::create([
                'numero' => $this->gerarNumeroOS(),
                'cliente_id' => $dados['cliente_id'],
                'usuario_id' => $dados['usuario_id'], // Quem abriu
                'descricao' => $dados['descricao'],
                'prioridade' => $dados['prioridade'],
                'status' => StatusOSEnum::ABERTA,
            ]);

            // 2. Adiciona os itens (equipamentos)
            $itens = array_map(function ($equipamentoId) {
                return ['equipamento_id' => $equipamentoId];
            }, $dados['equipamentos']);
            
            $os->itens()->createMany($itens);

            // 3. Registra o histórico inicial
            $os->historicos()->create([
                'usuario_id' => $dados['usuario_id'],
                'status' => StatusOSEnum::ABERTA,
            ]);

            return $os->load(['cliente', 'itens.equipamento', 'historicos']);
        });
    }

    private function gerarNumeroOS(): string
    {
        $data = now()->format('Ymd');
        $ultimo = OrdemServico::whereDate('created_at', now()->toDateString())->count() + 1;
        return "OS-{$data}-" . str_pad($ultimo, 4, '0', STR_PAD_LEFT);
    }

    public function cancelar(OrdemServico $os, int $usuarioId)
    {
        // 1. Valida a regra de negócio: Não pode cancelar se já estiver concluída
        if ($os->status === StatusOSEnum::CONCLUIDA) {
            throw new \Exception('Uma Ordem de Serviço concluída não pode ser cancelada.');
        }

        // Evita processamento desnecessário se já estiver cancelada
        if ($os->status === StatusOSEnum::CANCELADA) {
            throw new \Exception('Esta Ordem de Serviço já encontra-se cancelada.');
        }

        return DB::transaction(function () use ($os, $usuarioId) {
            // 2. Atualiza a OS principal
            $os->update([
                'status' => StatusOSEnum::CANCELADA,
                'data_fechamento' => now(), // Registra o momento em que foi encerrada
            ]);

            // 3. Grava o evento no histórico
            $os->historicos()->create([
                'usuario_id' => $usuarioId,
                'status' => StatusOSEnum::CANCELADA,
            ]);

            return $os;
        });
    }

    public function atualizar(OrdemServico $os, array $dados)
{
    $statusAtual = $os->status;
    // Transforma a string recebida no Form Request em um Enum real
    $novoStatus = StatusOSEnum::from($dados['status']);

    // 1. Regras de Bloqueio (Estados Terminais)
    if ($statusAtual === StatusOSEnum::CONCLUIDA) {
        throw new \Exception('Uma Ordem de Serviço concluída não pode ser alterada.');
    }
    
    if ($statusAtual === StatusOSEnum::CANCELADA) {
        throw new \Exception('Uma Ordem de Serviço cancelada não pode ser alterada.');
    }

    return DB::transaction(function () use ($os, $dados, $novoStatus, $statusAtual) {
        // 2. Prepara os dados de atualização
        $updateData = [
            'diagnostico' => $dados['diagnostico'] ?? $os->diagnostico,
        ];

        // 3. Se o status mudou, atualiza na OS e grava no histórico
        if ($statusAtual !== $novoStatus) {
            $updateData['status'] = $novoStatus;
            
            // Se o novo status for CONCLUIDA, marca a data de fechamento
            if ($novoStatus === StatusOSEnum::CONCLUIDA) {
                $updateData['data_fechamento'] = now();
            }

            $os->historicos()->create([
                'usuario_id' => $dados['usuario_id'],
                'status' => $novoStatus,
            ]);
        }

        // 4. Salva a OS
        $os->update($updateData);

        return $os->load(['cliente', 'responsavel', 'itens.equipamento', 'historicos']);
    });
}
}