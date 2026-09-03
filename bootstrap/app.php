<?php

use App\Exceptions\Domain\DomainException;
use App\Http\Middleware\EnsureUsuarioAtivo;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias para uso nas rotas (ex: middleware('usuario.ativo'))
        $middleware->alias([
            'usuario.ativo' => EnsureUsuarioAtivo::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Garante resposta JSON para todas as rotas da API
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /*
        |----------------------------------------------------------------------
        | Handler centralizado para Exceções de Domínio (Spec 3)
        |----------------------------------------------------------------------
        | Todas as exceções que herdam de DomainException são convertidas em
        | respostas JSON padronizadas com { message, code }.
        |
        | Níveis de log:
        |   - httpStatus >= 500 → Log::error (ex: IntegracaoErpException)
        |   - httpStatus <  500 → Log::warning (violações de regra de negócio)
        |----------------------------------------------------------------------
        */
        $exceptions->render(function (DomainException $e, Request $request): JsonResponse {
            $status = $e->httpStatus();

            if ($status >= 500) {
                Log::error($e->getMessage(), array_merge(
                    ['exception' => $e],
                    $e->context(),
                ));
            } else {
                Log::warning($e->getMessage(), $e->context());
            }

            return response()->json([
                'message' => $e->getMessage(),
                'code'    => $e->errorCode(),
            ], $status);
        });

    })->create();
