<?php

namespace App\Providers;

use App\Services\AdminPeriodFilter;
use App\Services\WhatsAppService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WhatsAppService::class);
        $this->app->singleton(AdminPeriodFilter::class);
    }

    public function boot(): void
    {
        Route::pattern('id', '[0-9]+');

        // Usar notificação customizada para reset de senha
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return url(route('password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ], false));
        });

        // Compartilhar AdminPeriodFilter com o layout principal quando estiver em rotas /admin
        View::composer('layouts.app', function ($view) {
            if (request()->is('admin') || request()->is('admin/*')) {
                $view->with('adminPeriodFilter', app(AdminPeriodFilter::class));
            }
        });
    }
}

