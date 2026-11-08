<?php

namespace App\Http\Middleware;

use App\Models\Participant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Só o participante usa a API do app (spec 2026-11-08-app-mobile-design,
 * AM7): uma sessão da equipe (o auth:sanctum também aceita a sessão web de
 * um domínio stateful) ou um token de outro tipo dá 403.
 */
class EnsureParticipant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof Participant) {
            return response()->json([
                'message' => 'Esta área é do app do participante.',
                'code' => 'not_participant',
            ], 403);
        }

        return $next($request);
    }
}
