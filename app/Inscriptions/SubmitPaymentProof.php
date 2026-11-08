<?php

namespace App\Inscriptions;

use App\Models\Inscription;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * O comprovante de pagamento, na web e no app (spec
 * 2026-11-08-app-mobile-design, AM17 e AM19). Só para inscrição aprovada; a
 * gravação é condicional ao status ainda ser "aprovado", para não perder a
 * corrida com o cron de expiração (commits de 15/07/2026).
 */
final class SubmitPaymentProof
{
    public const NOT_APPROVED = 'not_approved';

    public const DEADLINE_PASSED = 'deadline_passed';

    public const STORAGE_FAILED = 'storage_failed';

    public const MESSAGES = [
        self::NOT_APPROVED => 'Sua inscrição não está em status de aprovação para envio de comprovante.',
        self::DEADLINE_PASSED => 'O prazo para envio do comprovante terminou e sua inscrição voltou para a fila de espera.',
        self::STORAGE_FAILED => 'Não foi possível salvar o comprovante. Tente novamente.',
    ];

    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return ['payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120'];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'payment_proof.required' => 'Selecione o arquivo do comprovante.',
            'payment_proof.mimes' => 'O comprovante deve ser uma imagem (JPG, PNG) ou PDF.',
            'payment_proof.max' => 'O arquivo deve ter no máximo 5MB.',
        ];
    }

    /**
     * Por que esta inscrição não pode receber comprovante agora, ou null.
     * Conferido antes da validação do arquivo (a ordem da web).
     */
    public function refusal(Inscription $inscription): ?string
    {
        if ($inscription->isApproved()) {
            return null;
        }

        return $inscription->isWaitlisted() && $inscription->payment_expired_at
            ? self::DEADLINE_PASSED
            : self::NOT_APPROVED;
    }

    /**
     * Grava o comprovante. Null quando aceito; senão, o motivo da recusa.
     */
    public function handle(Inscription $inscription, UploadedFile $file): ?string
    {
        if ($refusal = $this->refusal($inscription)) {
            return $refusal;
        }

        $previousProof = $inscription->payment_proof;

        $path = $file->store('payment_proofs', 'public');

        if ($path === false) {
            return self::STORAGE_FAILED;
        }

        // Aceita o comprovante apenas enquanto o status ainda é "aprovado"
        // (evita corrida com o cron de expiração)
        $accepted = Inscription::whereKey($inscription->id)
            ->where('status', 'aprovado')
            ->update(['payment_proof' => $path, 'updated_at' => now()]);

        if (! $accepted) {
            Storage::disk('public')->delete($path);

            return self::DEADLINE_PASSED;
        }

        // Remove o comprovante anterior substituído (evita arquivo órfão)
        if ($previousProof && $previousProof !== $path) {
            Storage::disk('public')->delete($previousProof);
        }

        $inscription->payment_proof = $path;

        return null;
    }
}
