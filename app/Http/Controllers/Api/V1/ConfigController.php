<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/config: os textos que o app mostra e que moram no servidor
 * (spec 2026-11-08-app-mobile-design, AM10 e AM18), os mesmos da web.
 */
class ConfigController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'manifesto' => config('inscriptions.manifesto'),
            'terms' => config('inscriptions.terms'),
            'terms_acceptance' => config('inscriptions.terms_acceptance'),
            'reference_amount' => (int) config('inscriptions.reference_amount'),
            'contribution_note' => config('inscriptions.contribution_note'),
            'support' => [
                'whatsapp_number' => config('services.support.whatsapp_number'),
                'whatsapp_link' => config('services.support.whatsapp_link'),
            ],
            'links' => [
                'website' => route('inscricao.create'),
                'privacy' => route('privacidade'),
                'account_deletion' => route('app.excluir-conta'),
            ],
        ]);
    }
}
