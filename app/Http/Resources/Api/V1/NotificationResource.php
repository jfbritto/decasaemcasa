<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ParticipantNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Um aviso da aba "Avisos" (spec 2026-11-08-app-mobile-design, AM42).
 *
 * @mixin ParticipantNotification
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'title' => $this->title,
            'body' => $this->body,
            'inscription_id' => $this->inscription_id,
            'read' => $this->read_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
