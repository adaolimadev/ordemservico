<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusOSEnum;
use App\Http\Controllers\Controller;
use App\Models\OrdemServico;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * GET /api/v1/dashboard/indicadores
     *
     * Retorna contagens por status em uma única query GROUP BY
     * e o total de OS concluídas no mês corrente (Spec 7 — Req 5).
     */
    public function indicadores(): JsonResponse
    {
        // Uma única query agrupa todos os status de uma vez
        $porStatus = OrdemServico::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Garante que todos os status aparecem mesmo que estejam zerados
        $contagens = collect(StatusOSEnum::cases())->mapWithKeys(
            fn (StatusOSEnum $s) => [$s->value => (int) ($porStatus[$s->value] ?? 0)]
        );

        $concluidasMes = OrdemServico::query()
            ->where('status', StatusOSEnum::CONCLUIDA)
            ->whereYear('data_fechamento', now()->year)
            ->whereMonth('data_fechamento', now()->month)
            ->count();

        return response()->json([
            'por_status'        => $contagens,
            'concluidas_no_mes' => $concluidasMes,
        ]);
    }
}
