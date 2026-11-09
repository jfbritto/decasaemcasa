<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EventResource;
use App\Inscriptions\InscriptionView;
use App\Models\Event;
use App\Models\Inscription;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Os encontros abertos, sem login (spec 2026-11-08-app-mobile-design, AM10 e
 * AM27): os mesmos do formulário da web (publicados, de hoje em diante, por
 * data). O endereço nunca sai daqui.
 */
class EventController extends Controller
{
    public function index(): JsonResponse
    {
        $events = $this->open()->orderBy('date')->get();

        return response()->json(['data' => EventResource::collection($events)]);
    }

    /**
     * Com o token do participante (rota pública que lê o sanctum), diz se
     * ele já tem inscrição neste encontro.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $event = $this->open()->findOrFail($id);
        $participant = $request->user('sanctum');

        $mine = $participant instanceof Participant
            ? Inscription::ownedBy($participant)->where('event_id', $event->id)->with('event')->latest('id')->first()
            : null;

        return response()->json([
            ...(new EventResource($event))->resolve($request),
            'my_inscription' => $mine ? ['id' => $mine->id, 'state' => InscriptionView::state($mine)] : null,
        ]);
    }

    private function open(): Builder
    {
        return Event::query()
            ->where('status', 'published')
            ->whereDate('date', '>=', now());
    }
}
