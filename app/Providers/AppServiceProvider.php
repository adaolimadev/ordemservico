<?php

namespace App\Providers;

use App\Enums\PerfilEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /**
         * Gate de autorização: somente usuários com perfil ADMINISTRADOR
         * podem gerenciar (criar, listar, editar, ativar/desativar, alterar perfil)
         * outros usuários do sistema.
         */
        Gate::define('gerenciar-usuarios', function (User $user): bool {
            return $user->perfil === PerfilEnum::ADMINISTRADOR;
        });

        /**
         * Previne lazy loading fora de produção para detectar N+1 queries
         * durante desenvolvimento e testes (Spec 7 — Req 3.3).
         */
        Model::preventLazyLoading(! app()->isProduction());
    }
}
