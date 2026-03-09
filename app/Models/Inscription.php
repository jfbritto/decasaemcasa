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
        'confirmed_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'terms_accepted' => 'boolean',
        'approved_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'contribution_amount' => 'decimal:2',
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
        $this->save();
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
