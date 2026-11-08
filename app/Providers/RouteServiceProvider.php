<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/client/events';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            // A chave leva o tipo: o id 1 da equipe e o id 1 de um
            // participante do app não dividem a mesma cota.
            $user = $request->user();

            return Limit::perMinute(60)->by($user ? class_basename($user).':'.$user->getKey() : $request->ip());
        });

        // Login do app (spec 2026-11-08-app-mobile-design, AM5): pedir código,
        // 5 por minuto por IP e 3 a cada 10 minutos por e-mail; conferir, 10
        // por minuto por IP (o código também morre na 5ª tentativa errada).
        // O e-mail da chave: aparado e minúsculo; um array (email[]) vira
        // vazio em vez de quebrar com 500.
        $emailKey = fn (Request $request) => sha1(is_string($request->input('email')) ? mb_strtolower(trim($request->input('email'))) : '');

        RateLimiter::for('app-auth-code', function (Request $request) use ($emailKey) {
            return [
                Limit::perMinute(5)->by('app-code-ip:'.$request->ip()),
                Limit::perMinutes(10, 3)->by('app-code-email:'.$emailKey($request)),
            ];
        });

        // Também por e-mail: tentativas espalhadas por vários IPs contra a
        // mesma conta (inclusive a de revisão, que tem código fixo) param aqui.
        RateLimiter::for('app-auth-verify', function (Request $request) use ($emailKey) {
            return [
                Limit::perMinute(10)->by('app-verify-ip:'.$request->ip()),
                Limit::perMinutes(10, 10)->by('app-verify-email:'.$emailKey($request)),
            ];
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
