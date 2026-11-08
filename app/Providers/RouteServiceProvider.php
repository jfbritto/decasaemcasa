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
        RateLimiter::for('app-auth-code', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(5)->by('app-code-ip:'.$request->ip()),
                Limit::perMinutes(10, 3)->by('app-code-email:'.sha1($email)),
            ];
        });

        RateLimiter::for('app-auth-verify', function (Request $request) {
            return Limit::perMinute(10)->by('app-verify-ip:'.$request->ip());
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
