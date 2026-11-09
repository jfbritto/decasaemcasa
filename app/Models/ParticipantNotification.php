<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um aviso da aba "Avisos" do app (spec 2026-11-08-app-mobile-design, AM41):
 * o par do e-mail que a pessoa recebeu, com o texto curto do push.
 */
class ParticipantNotification extends Model
{
    /** Quantos avisos a aba mostra (os mais recentes). */
    public const LIST_LIMIT = 50;

    protected $fillable = [
        'email',
        'inscription_id',
        'kind',
        'title',
        'body',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    /**
     * Os avisos de um participante: os do e-mail dele (a coluna é binária e o
     * e-mail é gravado aparado e minúsculo).
     */
    public function scopeOwnedBy(Builder $query, Participant $participant): Builder
    {
        return $query->where('email', $participant->email);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
