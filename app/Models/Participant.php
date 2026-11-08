<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

/**
 * A conta do app do participante (spec 2026-11-08-app-mobile-design, AM1).
 * Separada dos users, que são só a equipe. A chave é o e-mail: as inscrições
 * do participante são as que têm o mesmo e-mail (AM2).
 */
class Participant extends Model
{
    use HasApiTokens;

    protected $fillable = [
        'email',
        'name',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
    ];

    /**
     * O e-mail como chave: aparado e minúsculo, sempre.
     */
    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = self::normalizeEmail($value);
    }

    public function pushTokens(): HasMany
    {
        return $this->hasMany(ParticipantPushToken::class);
    }

    /**
     * As inscrições deste participante (mesmo e-mail).
     */
    public function inscriptions(): Builder
    {
        return Inscription::ownedBy($this);
    }

    public static function forEmail(string $email): ?self
    {
        return static::where('email', self::normalizeEmail($email))->first();
    }

    /**
     * Exclui a conta do app (AM8): o participante, os tokens de acesso, os de
     * push (cascade) e os códigos de login. As inscrições ficam com a equipe.
     */
    public function deleteAccount(): void
    {
        $this->tokens()->delete();
        ParticipantLoginCode::where('email', $this->email)->delete();
        $this->delete();
    }
}
