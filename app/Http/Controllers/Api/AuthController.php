<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Autentica o usuário e emite um token Sanctum.
     *
     * POST /api/v1/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Credenciais inválidas.',
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Bloqueia login de usuário desativado
        if (! $user->situacao) {
            Auth::logout();

            return response()->json([
                'message' => 'Sua conta está desativada. Entre em contato com o administrador.',
            ], 403);
        }

        // Revoga tokens anteriores para garantir sessão única por login
        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'cargo'    => $user->cargo,
                'perfil'   => $user->perfil?->value,
                'situacao' => $user->situacao,
            ],
        ]);
    }

    /**
     * Revoga o token atual do usuário autenticado.
     *
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    /**
     * Retorna os dados do usuário autenticado.
     *
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json([
            'id'       => $user->id,
            'name'     => $user->name,
            'email'    => $user->email,
            'cargo'    => $user->cargo,
            'perfil'   => $user->perfil?->value,
            'situacao' => $user->situacao,
        ]);
    }
}
