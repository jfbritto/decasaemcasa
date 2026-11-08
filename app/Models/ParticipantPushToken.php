<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um aparelho que recebe push (spec 2026-11-08-app-mobile-design, AM20).
 * O token é único no sistema: registrado por outra conta, muda de dono.
 */
class ParticipantPushToken extends Model
{
    protected $fillable = [
        'participant_id',
        'token',
        'platform',
        'device_name',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
