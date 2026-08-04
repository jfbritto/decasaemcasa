<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Inscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'token',
        'full_name',
        'cpf',
        'birth_date',
        'city_neighborhood',
        'whatsapp',
        'email',
        'instagram',
        'motivation',
        'terms_accepted',
        'status',
        'payment_proof',
        'contribution_amount',
        'admin_notes',
        'cancelled_by',
        'approved_at',
        'payment_deadline_at',
        'confirmed_at',
        'social_request_status',
        'social_request_reason',
        'social_request_amount',
        'social_request_admin_message',
        'social_request_submitted_at',
        'social_request_reviewed_at',
        'social_request_reviewed_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'terms_accepted' => 'boolean',
        'approved_at' => 'datetime',
        'payment_deadline_at' => 'datetime',
        'payment_deadline_minutes' => 'integer',
        'payment_expired_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'contribution_amount' => 'decimal:2',
        'social_request_amount' => 'decimal:2',
        'social_request_submitted_at' => 'datetime',
        'social_request_reviewed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($inscription) {
            if (empty($inscription->token)) {
                $inscription->token = Str::random(64);
            }
        });
    }

    // Relationships

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function socialRequestReviewer()
    {
        return $this->belongsTo(User::class, 'social_request_reviewed_by');
    }

    // Status checks

    public function isPending(): bool
    {
        return $this->status === 'pendente';
    }

    public function isApproved(): bool
    {
        return $this->status === 'aprovado';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmado';
    }

    public function isWaitlisted(): bool
    {
        return $this->status === 'fila_de_espera';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejeitado';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelado';
    }

    // Status transitions

    public function approve(): void
    {
        $this->status = 'aprovado';
        $this->approved_at = now();
        $this->startPaymentDeadline();
        $this->save();
    }

    public function startPaymentDeadline(): void
    {
        $minutes = $this->paymentDeadlineWindowMinutes();

        $this->payment_deadline_at = now()->addMinutes($minutes);
        $this->payment_deadline_minutes = $minutes;
        // Prazo novo invalida qualquer expiração anterior
        $this->payment_expired_at = null;
    }

    /**
     * Janela de pagamento conforme a proximidade do evento, em faixas
     * "horasAteEvento:janelaEmMinutos" (config payment_deadline_tiers).
     * Uma faixa vale quando falta MAIS que o número de horas indicado;
     * sem faixa aplicável (ou sem evento), vale o prazo base.
     */
    public function paymentDeadlineWindowMinutes(): int
    {
        $base = (int) config('inscriptions.payment_deadline_minutes', 60);
        $eventDate = $this->event?->date;

        if (! $eventDate) {
            return $base;
        }

        $tiers = collect(explode(',', (string) config('inscriptions.payment_deadline_tiers', '')))
            ->map(function ($tier) {
                $parts = array_map('intval', explode(':', trim($tier)));

                return count($parts) === 2 ? $parts : null;
            })
            // Teto de 65535: a janela é persistida em smallint unsigned; valores
            // acima (ex.: typo de minutos como segundos) estourariam o insert
            ->filter(fn ($pair) => $pair !== null && $pair[0] > 0 && $pair[1] > 0 && $pair[1] <= 65535)
            ->sortByDesc(fn ($pair) => $pair[0]);

        foreach ($tiers as [$hours, $minutes]) {
            if ($eventDate->greaterThan(now()->addHours($hours))) {
                return $minutes;
            }
        }

        return $base;
    }

    public function waitlist(): void
    {
        $this->status = 'fila_de_espera';
        $this->save();
    }

    public function confirm(): void
    {
        $this->status = 'confirmado';
        $this->confirmed_at = now();
        $this->save();

        if ($this->event) {
            $this->event->increment('confirmed_count');
        }
    }

    public function reject(): void
    {
        $this->status = 'rejeitado';
        $this->save();
    }

    public function cancel(string $cancelledBy = 'participant'): void
    {
        $previousStatus = $this->status;
        $this->status = 'cancelado';
        $this->cancelled_by = $cancelledBy;
        $this->save();

        if ($previousStatus === 'confirmado' && $this->event) {
            $this->event->decrement('confirmed_count');
        }
    }

    public function isCancelledByAdmin(): bool
    {
        return $this->isCancelled() && $this->cancelled_by === 'admin';
    }

    public function isCancelledByParticipant(): bool
    {
        return $this->isCancelled() && $this->cancelled_by !== 'admin';
    }

    public function revertToPending(): void
    {
        $this->status = 'pendente';
        $this->cancelled_by = null;
        $this->save();
    }

    public function revertRejectionToPending(): void
    {
        $this->status = 'pendente';
        $this->save();
    }

    /**
     * Expira a inscrição para a fila de espera de forma atômica.
     * Retorna false se o comprovante chegou, o status mudou ou o prazo foi
     * reiniciado (ex.: reenvio de aprovação) entre a busca e o update.
     */
    public function expireToWaitlist(): bool
    {
        $updated = static::whereKey($this->id)
            ->where('status', 'aprovado')
            ->whereNull('payment_proof')
            // Re-checa o prazo: um reenvio pode tê-lo reiniciado entre a
            // busca de candidatos do cron e este update
            ->where('payment_deadline_at', '<=', now())
            ->where(function ($query) {
                // Pausa da solicitação social também no UPDATE: fecha a janela entre a
                // busca de candidatos do cron e este update
                $query->whereNull('social_request_status')
                    ->orWhere('social_request_status', '!=', 'pendente');
            })
            ->update([
                'status' => 'fila_de_espera',
                'payment_expired_at' => now(),
                'updated_at' => now(),
            ]);

        if ($updated) {
            $this->refresh();
        }

        return (bool) $updated;
    }

    // Social Request

    public function hasSocialRequest(): bool
    {
        return $this->social_request_status !== null;
    }

    public function isSocialRequestPending(): bool
    {
        return $this->social_request_status === 'pendente';
    }

    public function isSocialRequestApproved(): bool
    {
        return $this->social_request_status === 'aprovado';
    }

    public function isSocialRequestRejected(): bool
    {
        return $this->social_request_status === 'rejeitado';
    }

    public function submitSocialRequest(string $reason, float $amount): void
    {
        $this->social_request_status = 'pendente';
        $this->social_request_reason = $reason;
        $this->social_request_amount = $amount;
        $this->social_request_admin_message = null;
        $this->social_request_submitted_at = now();
        $this->social_request_reviewed_at = null;
        $this->social_request_reviewed_by = null;
        $this->save();
    }

    public function approveSocialRequest(?float $amount = null, ?string $message = null, ?int $userId = null): void
    {
        $this->social_request_status = 'aprovado';
        if ($amount !== null) {
            $this->social_request_amount = $amount;
        }
        $this->social_request_admin_message = $message;
        $this->social_request_reviewed_at = now();
        $this->social_request_reviewed_by = $userId;

        if ($this->isApproved()) {
            $this->startPaymentDeadline();
        }

        $this->save();
    }

    public function rejectSocialRequest(?string $message = null, ?int $userId = null): void
    {
        $this->social_request_status = 'rejeitado';
        $this->social_request_admin_message = $message;
        $this->social_request_reviewed_at = now();
        $this->social_request_reviewed_by = $userId;

        if ($this->isApproved()) {
            $this->startPaymentDeadline();
        }

        $this->save();
    }

    // Helpers

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pendente' => 'Pendente',
            'aprovado' => 'Aprovado',
            'confirmado' => 'Confirmado',
            'fila_de_espera' => 'Fila de Espera',
            'rejeitado' => 'Rejeitado',
            'cancelado' => 'Cancelado',
            default => $this->status,
        };
    }

    public function getPaymentDeadlineLabelAttribute(): string
    {
        // Janela gravada quando o prazo foi iniciado; prazos legados caem no default
        $minutes = (int) ($this->payment_deadline_minutes
            ?? config('inscriptions.payment_deadline_minutes', 60));

        if ($minutes % 60 === 0) {
            $hours = intdiv($minutes, 60);

            return $hours === 1 ? '1 hora' : "{$hours} horas";
        }

        return "{$minutes} minutos";
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pendente' => 'yellow',
            'aprovado' => 'blue',
            'confirmado' => 'green',
            'fila_de_espera' => 'orange',
            'rejeitado' => 'red',
            'cancelado' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Formata o CPF para exibição: 000.000.000-00
     */
    public function getFormattedCpfAttribute(): string
    {
        $cpf = preg_replace('/\D/', '', $this->cpf);

        if (strlen($cpf) === 11) {
            return substr($cpf, 0, 3).'.'.substr($cpf, 3, 3).'.'.substr($cpf, 6, 3).'-'.substr($cpf, 9, 2);
        }

        return $this->cpf;
    }
}
