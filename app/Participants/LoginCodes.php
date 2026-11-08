<?php

namespace App\Participants;

use App\Mail\ParticipantLoginCodeMail;
use App\Models\Participant;
use App\Models\ParticipantLoginCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Emite e confere o código de 6 dígitos do login do app (spec
 * 2026-11-08-app-mobile-design, AM3, AM4 e AM6).
 *
 * - Só o HMAC do código é gravado (com a APP_KEY): o banco vazado não
 *   entrega códigos vivos.
 * - Um código vivo por e-mail: emitir de novo apaga os anteriores.
 * - Vale login_code_ttl_minutes; morre na login_code_max_attempts-ésima
 *   tentativa errada; usado, não vale de novo (a marca de uso é um UPDATE
 *   condicional, então dois verify ao mesmo tempo não entram os dois).
 * - Conta de revisão das lojas: nenhum e-mail sai e o código fixo vale.
 */
final class LoginCodes
{
    /**
     * Gera o código e o envia por e-mail. Nunca diz ao chamador se o e-mail
     * tem inscrição ou conta (AM3): a resposta da API é sempre a mesma.
     */
    public function issue(string $email): void
    {
        $email = Participant::normalizeEmail($email);

        // Defesa em profundidade: o controller já recusa e-mail com acento.
        if (! Participant::isAsciiEmail($email) || $this->isReviewAccount($email)) {
            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        ParticipantLoginCode::where('email', $email)->delete();
        ParticipantLoginCode::create([
            'email' => $email,
            'code_hash' => $this->hash($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes($this->ttlMinutes()),
        ]);

        try {
            Mail::to($email)->send(new ParticipantLoginCodeMail($code, $this->ttlMinutes()));
        } catch (\Throwable $e) {
            // Nunca o código nem o e-mail no log: só o motivo.
            Log::error('Falha ao enviar o código de acesso do app: '.$e->getMessage());
        }
    }

    /**
     * Confere o código. Verdadeiro uma vez só por código emitido.
     */
    public function verify(string $email, string $code): bool
    {
        $email = Participant::normalizeEmail($email);
        $code = trim($code);

        if (! Participant::isAsciiEmail($email)) {
            return false;
        }

        if ($this->isReviewAccount($email)) {
            return hash_equals((string) config('participant_app.review_code'), $code);
        }

        $login = ParticipantLoginCode::where('email', $email)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $login) {
            return false;
        }

        // Cada comparação gasta uma tentativa ANTES de comparar, num UPDATE
        // condicional: pedidos em paralelo nunca comparam mais que
        // max_attempts códigos (a tentativa certa também conta).
        $slot = ParticipantLoginCode::whereKey($login->id)
            ->whereNull('used_at')
            ->where('attempts', '<', $this->maxAttempts())
            ->increment('attempts');

        if ($slot !== 1 || ! hash_equals($login->code_hash, $this->hash($code))) {
            return false;
        }

        $claimed = ParticipantLoginCode::whereKey($login->id)
            ->whereNull('used_at')
            ->update(['used_at' => now(), 'updated_at' => now()]);

        return $claimed === 1;
    }

    /**
     * O código fixo não tem contador de tentativas nem validade: a conta de
     * revisão só existe com um código de pelo menos REVIEW_CODE_MIN_LENGTH
     * caracteres (curto, ela fica desligada) e com o limite por e-mail do
     * verify (RouteServiceProvider).
     */
    public const REVIEW_CODE_MIN_LENGTH = 12;

    public function isReviewAccount(string $email): bool
    {
        $reviewEmail = config('participant_app.review_email');
        $reviewCode = config('participant_app.review_code');

        if (! is_string($reviewEmail) || trim($reviewEmail) === '' || ! is_string($reviewCode)
            || mb_strlen(trim($reviewCode)) < self::REVIEW_CODE_MIN_LENGTH) {
            return false;
        }

        return Participant::normalizeEmail($reviewEmail) === Participant::normalizeEmail($email);
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    private function ttlMinutes(): int
    {
        return max(1, (int) config('participant_app.login_code_ttl_minutes', 10));
    }

    private function maxAttempts(): int
    {
        return max(1, (int) config('participant_app.login_code_max_attempts', 5));
    }
}
