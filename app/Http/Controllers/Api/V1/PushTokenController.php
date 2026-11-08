<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\ParticipantPushToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * O token de push deste aparelho (spec 2026-11-08-app-mobile-design, AM20).
 * Único no sistema: registrado por outra conta no mesmo aparelho, muda de
 * dono (quem entra por último recebe os avisos).
 */
class PushTokenController extends Controller
{
    public const TOKEN_PATTERN = '/^Expo(nent)?PushToken\[[^\]]+\]\z/';

    public function store(Request $request): Response
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255', 'regex:'.self::TOKEN_PATTERN],
            'platform' => ['required', 'string', Rule::in(['ios', 'android'])],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        /** @var Participant $participant */
        $participant = $request->user();

        ParticipantPushToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'participant_id' => $participant->id,
                'platform' => $data['platform'],
                'device_name' => $data['device_name'] ?? null,
                'last_seen_at' => now(),
            ],
        );

        return response()->noContent();
    }

    public function destroy(Request $request, string $token): Response
    {
        /** @var Participant $participant */
        $participant = $request->user();

        $participant->pushTokens()->where('token', $token)->delete();

        return response()->noContent();
    }
}
