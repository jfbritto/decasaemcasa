<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ParticipantResource;
use App\Inscriptions\InscriptionRules;
use App\Inscriptions\MailDomainChecker;
use App\Models\Inscription;
use App\Models\Participant;
use App\Participants\LoginCodes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Login do app por código no e-mail (spec 2026-11-08-app-mobile-design, AM3 a
 * AM8).
 */
class AuthController extends Controller
{
    public const INVALID_CODE = 'Código inválido ou expirado. Confira os números ou peça um novo código.';

    public const EMAIL_MESSAGES = [
        'email.required' => 'Informe seu e-mail.',
        'email.string' => 'Informe um e-mail válido.',
        'email.email' => 'Informe um e-mail válido.',
        // Só ASCII: um e-mail com acento poderia se passar por outro
        // ("maria@gmáil.com" por "maria@gmail.com").
        'email.regex' => 'Digite o e-mail sem acentos nem espaços.',
    ];

    /**
     * POST /api/v1/auth/code: envia o código. A resposta é sempre a mesma,
     * exista ou não conta ou inscrição com o e-mail (AM3).
     */
    public function requestCode(Request $request, LoginCodes $codes, MailDomainChecker $domains): JsonResponse
    {
        $this->trimEmail($request);

        $data = $request->validate([
            'email' => ['bail', 'required', 'string', 'regex:'.Participant::ASCII_EMAIL, 'email', 'max:255', function ($attribute, $value, $fail) use ($codes, $domains) {
                if ($codes->isReviewAccount((string) $value)) {
                    return;
                }
                $suggestion = InscriptionRules::emailTypoSuggestion($value);
                if ($suggestion) {
                    $fail("Verifique seu e-mail. Você quis dizer {$suggestion}?");

                    return;
                }
                if (! $domains->acceptsMail($value)) {
                    $fail('O domínio deste e-mail não recebe mensagens. Verifique se digitou corretamente.');
                }
            }],
        ], self::EMAIL_MESSAGES);

        $codes->issue($data['email']);

        return response()->json([
            'message' => 'Enviamos um código de 6 números para o seu e-mail.',
        ], 202);
    }

    /**
     * POST /api/v1/auth/verify: confere o código e devolve o token do
     * aparelho. Cria a conta na primeira vez (AM4).
     */
    public function verify(Request $request, LoginCodes $codes): JsonResponse
    {
        $this->trimEmail($request);

        $data = $request->validate([
            'email' => ['bail', 'required', 'string', 'regex:'.Participant::ASCII_EMAIL, 'email', 'max:255'],
            'code' => ['required', 'string', 'max:32'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ], [
            ...self::EMAIL_MESSAGES,
            'code.required' => 'Informe o código que chegou no seu e-mail.',
        ]);

        if (! $codes->verify($data['email'], $data['code'])) {
            return response()->json([
                'message' => self::INVALID_CODE,
                'errors' => ['code' => [self::INVALID_CODE]],
            ], 422);
        }

        $participant = Participant::firstOrCreate(['email' => Participant::normalizeEmail($data['email'])]);

        if (! $participant->name) {
            $participant->name = Inscription::ownedBy($participant)->latest('id')->value('full_name');
        }
        $participant->last_login_at = now();
        $participant->save();

        $token = $participant->createToken($data['device_name'] ?? 'app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'participant' => new ParticipantResource($participant),
        ]);
    }

    /**
     * GET /api/v1/me
     */
    public function me(Request $request): ParticipantResource
    {
        return new ParticipantResource($request->user());
    }

    /**
     * POST /api/v1/auth/logout: revoga o token deste aparelho e apaga o
     * token de push que o app mandar (AM8).
     */
    public function logout(Request $request): Response
    {
        /** @var Participant $participant */
        $participant = $request->user();

        $pushToken = $request->input('push_token');
        if (is_string($pushToken) && $pushToken !== '') {
            $participant->pushTokens()->where('token', $pushToken)->delete();
        }

        $participant->currentAccessToken()?->delete();

        return response()->noContent();
    }

    /**
     * O projeto não tem o TrimStrings global: o e-mail digitado no celular
     * costuma vir com um espaço do teclado.
     */
    private function trimEmail(Request $request): void
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => trim($request->input('email'))]);
        }
    }
}
