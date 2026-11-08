<?php

namespace App\Inscriptions;

use App\Models\Inscription;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * O pedido de contribuição social, na web e no app (spec
 * 2026-11-08-app-mobile-design, AM17). Só para aprovado, com vaga, sem pedido
 * pendente ou aprovado. Enquanto pendente, pausa o prazo do comprovante.
 */
final class SubmitSocialRequest
{
    public const NOT_APPROVED = 'not_approved';

    public const EVENT_FULL = 'event_full';

    public const ALREADY_REQUESTED = 'already_requested';

    public const MESSAGES = [
        self::NOT_APPROVED => 'Solicitação disponível apenas para inscrições aprovadas.',
        self::EVENT_FULL => 'Vagas esgotadas para este encontro.',
        self::ALREADY_REQUESTED => 'Você já possui uma solicitação de contribuição social registrada.',
    ];

    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'reason' => 'required|string|min:20|max:1000',
            'amount' => 'required|numeric|min:0|max:99999.99',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'reason.required' => 'Conte brevemente sua situação.',
            'reason.min' => 'Sua justificativa deve ter pelo menos 20 caracteres.',
            'reason.max' => 'Sua justificativa não pode passar de 1000 caracteres.',
            'amount.required' => 'Informe o valor que você consegue contribuir.',
            'amount.numeric' => 'Informe um valor numérico.',
            'amount.min' => 'O valor não pode ser negativo.',
        ];
    }

    /**
     * Por que esta inscrição não pode pedir a contribuição social, ou null.
     */
    public function refusal(Inscription $inscription): ?string
    {
        if (! $inscription->isApproved()) {
            return self::NOT_APPROVED;
        }

        if ($inscription->event && $inscription->event->isFull()) {
            return self::EVENT_FULL;
        }

        if ($inscription->isSocialRequestPending() || $inscription->isSocialRequestApproved()) {
            return self::ALREADY_REQUESTED;
        }

        return null;
    }

    /**
     * Registra o pedido (já validado). Null quando aceito; senão, o motivo.
     */
    public function handle(Inscription $inscription, string $reason, float $amount): ?string
    {
        if ($refusal = $this->refusal($inscription)) {
            return $refusal;
        }

        $inscription->submitSocialRequest($reason, $amount);

        try {
            $this->notifications->notifySocialRequestSubmitted($inscription);
        } catch (\Throwable $e) {
            Log::warning('Falha ao notificar solicitação social: '.$e->getMessage());
        }

        return null;
    }
}
