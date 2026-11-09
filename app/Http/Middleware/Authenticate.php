<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        // A API do app responde 401 em JSON, nunca redireciona ao login da equipe.
        return $request->expectsJson() || $request->is('api/*') ? null : route('login');
    }
}

