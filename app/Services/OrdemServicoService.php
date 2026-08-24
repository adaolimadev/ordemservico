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
}