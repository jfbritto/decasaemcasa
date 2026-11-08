<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * O código de 6 dígitos do login do app (spec 2026-11-08-app-mobile-design,
 * AM3 e AM4). Só o hash é gravado; quem emite e confere é o
 * App\Participants\LoginCodes.
 */
class ParticipantLoginCode extends Model
{
    protected $fillable = [
        'email',
        'code_hash',
        'attempts',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    protected $hidden = [
        'code_hash',
    ];
}
