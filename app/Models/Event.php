<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'slug',
        'image',
        'date',
        'city',
        'address',
        'arrival_time',
        'capacity',
        'confirmed_count',
        'status',
    ];

    protected $casts = [
        'date' => 'datetime',
        'capacity' => 'integer',
        'confirmed_count' => 'integer',
    ];

    /**
     * Campos que NUNCA devem aparecer em serialização JSON/array.
     * O endereço é informação sensível — só acessível para confirmados.
     */
    protected $hidden = [
        'address',
    ];

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isAvailable(): bool
    {
        return $this->isPublished() && $this->hasAvailableSpots();
    }

    public function hasAvailableSpots(): bool
    {
        if ($this->capacity <= 0) {
            return true; // Sem limite definido
        }

        return $this->confirmed_count < $this->capacity;
    }

    public function isFull(): bool
    {
        return $this->capacity > 0 && $this->confirmed_count >= $this->capacity;
    }

    public function getAvailableSpotsAttribute(): int
    {
        if ($this->capacity <= 0) {
            return 999; // Sem limite
        }

        return max(0, $this->capacity - $this->confirmed_count);
    }

    // Relationships

    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    /**
     * Recalcula o contador de confirmados com base nas inscrições.
     */
    public function recalculateConfirmedCount(): void
    {
        $this->confirmed_count = $this->inscriptions()
            ->where('status', 'confirmado')
            ->count();
        $this->save();
    }

    /**
     * Scope para incluir o endereço (deve ser usado apenas em contextos autorizados).
     */
    public function scopeWithAddress($query)
    {
        return $query;
    }

    /**
     * Acessar o endereço diretamente (para uso interno/admin).
     */
    public function getFullAddressAttribute(): ?string
    {
        return $this->attributes['address'] ?? null;
    }
}
