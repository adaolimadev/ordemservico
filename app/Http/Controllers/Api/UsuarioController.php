<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Usuario\AlterarPerfilRequest;
use App\Http\Requests\Usuario\AlterarSituacaoRequest;
use App\Http\Requests\Usuario\StoreUsuarioRequest;
use App\Http\Requests\Usuario\UpdateUsuarioRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    /**
     * Lista todos os usuários paginados.
     *
     * GET /api/v1/usuarios
     */
    public function index(): AnonymousResourceCollection
    {
        $usuarios = User::orderBy('name')->paginate(15);

        return UsuarioResource::collection($usuarios);
    }

    /**
     * Exibe os detalhes de um usuário.
     *
     * GET /api/v1/usuarios/{usuario}
     */
    public function show(User $usuario): UsuarioResource
    {
        return new UsuarioResource($usuario);
    }

    /**
     * Cria um novo usuário.
     *
     * POST /api/v1/usuarios
     */
    public function store(StoreUsuarioRequest $request): UsuarioResource
    {
        $data = $request->validated();

        $usuario = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'cargo'    => $data['cargo'] ?? null,
            'perfil'   => $data['perfil'],
            'situacao' => $data['situacao'] ?? true,
        ]);

        return new UsuarioResource($usuario);
    }

    /**
     * Atualiza dados editáveis de um usuário (name, email, cargo, password).
     * Perfil e situação possuem endpoints dedicados.
     *
     * PUT/PATCH /api/v1/usuarios/{usuario}
     */
    public function update(UpdateUsuarioRequest $request, User $usuario): UsuarioResource
    {
        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $usuario->update($data);

        return new UsuarioResource($usuario);
    }

    /**
     * Ativa ou desativa um usuário.
     * Um administrador não pode desativar a si mesmo.
     *
     * PATCH /api/v1/usuarios/{usuario}/situacao
     */
    public function alterarSituacao(AlterarSituacaoRequest $request, User $usuario): JsonResponse
    {
        // Proteção contra auto-bloqueio (Requisito 6.4)
        if ($usuario->id === $request->user()->id && ! $request->boolean('situacao')) {
            return response()->json([
                'message' => 'Você não pode desativar a própria conta.',
            ], 422);
        }

        $usuario->update(['situacao' => $request->boolean('situacao')]);

        $acao = $usuario->situacao ? 'ativado' : 'desativado';

        return response()->json([
            'message'  => "Usuário {$acao} com sucesso.",
            'usuario'  => new UsuarioResource($usuario),
        ]);
    }

    /**
     * Altera o perfil de um usuário.
     *
     * PATCH /api/v1/usuarios/{usuario}/perfil
     */
    public function alterarPerfil(AlterarPerfilRequest $request, User $usuario): JsonResponse
    {
        $usuario->update(['perfil' => $request->validated('perfil')]);

        return response()->json([
            'message' => 'Perfil atualizado com sucesso.',
            'usuario' => new UsuarioResource($usuario),
        ]);
    }
}
