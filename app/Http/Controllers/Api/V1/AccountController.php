<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * DELETE /api/v1/account: "Excluir conta" do app, exigência da Apple e do
 * Google (spec 2026-11-08-app-mobile-design, AM8). Apaga a conta, os tokens
 * de acesso e de push e os códigos. As inscrições ficam com a equipe.
 */
class AccountController extends Controller
{
    public function destroy(Request $request): Response
    {
        /** @var Participant $participant */
        $participant = $request->user();

        $participant->deleteAccount();

        return response()->noContent();
    }
}
