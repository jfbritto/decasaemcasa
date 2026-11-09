<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\Participant;
use App\Models\ParticipantNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * A aba "Avisos" do app (spec 2026-11-08-app-mobile-design, AM41 a AM43):
 * os avisos do e-mail do participante, do mais novo para o mais antigo, e o
 * "lido". Aviso de outro e-mail dá 404 (AM12).
 */
class NotificationController extends Controller
{
    /**
     * GET /api/v1/notifications: os LIST_LIMIT mais recentes e quantos não
     * foram lidos (o número da aba).
     */
    public function index(Request $request): JsonResponse
    {
        $participant = $this->participant($request);

        $notifications = ParticipantNotification::ownedBy($participant)
            ->latest('id')
            ->limit(ParticipantNotification::LIST_LIMIT)
            ->get();

        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'unread_count' => ParticipantNotification::ownedBy($participant)->unread()->count(),
        ]);
    }

    /**
     * POST /api/v1/notifications/{id}/read
     */
    public function markRead(Request $request, int $id): Response
    {
        $notification = ParticipantNotification::ownedBy($this->participant($request))->findOrFail($id);

        if (! $notification->read_at) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return response()->noContent();
    }

    /**
     * POST /api/v1/notifications/read-all
     */
    public function markAllRead(Request $request): Response
    {
        ParticipantNotification::ownedBy($this->participant($request))
            ->unread()
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return response()->noContent();
    }

    private function participant(Request $request): Participant
    {
        return $request->user();
    }
}
