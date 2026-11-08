<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Um encontro, como as rotas públicas do app o mostram (spec
 * 2026-11-08-app-mobile-design, AM10): o endereço e o horário de chegada
 * NUNCA saem daqui (só na inscrição confirmada, InscriptionResource).
 *
 * @mixin Event
 */
class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'city' => $this->city,
            'description' => $this->description,
            'date' => $this->date?->toIso8601String(),
            'image_url' => self::imageUrl($this->resource),
            // Sem limite (capacity 0): vagas nulas, nunca o 999 do accessor.
            'capacity' => $this->capacity > 0 ? $this->capacity : null,
            'available_spots' => $this->capacity > 0 ? $this->available_spots : null,
            'is_full' => $this->isFull(),
        ];
    }

    public static function imageUrl(Event $event): ?string
    {
        return $event->image ? asset('storage/'.$event->image) : null;
    }
}
