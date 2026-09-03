<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que o usuário autenticado está ativo (situacao = true).
 * Deve ser executado após o middleware auth:sanctum.
 */
class EnsureUsuarioAtivo
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->situacao) {
            return response()->json([
                'message' => 'Sua conta está desativada. Entre em contato com o administrador.',
            ], 401);
        }

        return $next($request);
    }
}
