<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\InscriptionResource;
use App\Inscriptions\CancelInscription;
use App\Inscriptions\InscriptionRules;
use App\Inscriptions\RegisterInscription;
use App\Inscriptions\SubmitPaymentProof;
use App\Inscriptions\SubmitSocialRequest;
use App\Models\Event;
use App\Models\Inscription;
use App\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * As inscrições do participante (spec 2026-11-08-app-mobile-design, AM11 a
 * AM19). As regras são as mesmas da web (app/Inscriptions). Inscrição de
 * outro e-mail dá 404, nunca 403 (AM12).
 */
class InscriptionController extends Controller
{
    public const DUPLICATE_OF_OTHER_EMAIL = 'Já existe uma inscrição com este CPF para este encontro. Se ela é sua, entre no app com o e-mail usado nela.';

    public const CONFIRMED_CANNOT_CANCEL = 'Sua participação já está confirmada. Se não puder ir, fale com a equipe pelo WhatsApp para liberar seu lugar na sala.';

    /**
     * GET /api/v1/inscriptions: as próximas primeiro (pela data do
     * encontro), depois as anteriores (da mais recente para a mais antiga).
     */
    public function index(Request $request): JsonResponse
    {
        $inscriptions = Inscription::ownedBy($this->participant($request))
            ->with('event')
            ->get();

        $today = now()->startOfDay();
        [$upcoming, $past] = $inscriptions->partition(
            fn (Inscription $i) => $i->event !== null && $i->event->date !== null && $i->event->date->greaterThanOrEqualTo($today)
        );

        $ordered = $upcoming->sortBy(fn (Inscription $i) => $i->event->date->getTimestamp())
            ->concat($past->sortByDesc(fn (Inscription $i) => $i->event?->date?->getTimestamp() ?? $i->created_at->getTimestamp()))
            ->values();

        return response()->json(['data' => InscriptionResource::collection($ordered)]);
    }

    public function show(Request $request, int $id): InscriptionResource
    {
        return new InscriptionResource($this->find($request, $id));
    }

    /**
     * POST /api/v1/inscriptions: o e-mail é o da conta (AM16).
     */
    public function store(Request $request, RegisterInscription $register): JsonResponse
    {
        $data = $request->validate(InscriptionRules::rules(withEmail: false), InscriptionRules::messages());
        $participant = $this->participant($request);

        $event = Event::find($data['event_id']);
        if (! $event) {
            return $this->refusal(RegisterInscription::MESSAGES[RegisterInscription::UNAVAILABLE], RegisterInscription::UNAVAILABLE);
        }

        [$result, $inscription] = $register->handle($event, [...$data, 'email' => $participant->email]);

        if ($result === RegisterInscription::DUPLICATE) {
            // A duplicada pode ser de outra pessoa (o mesmo CPF digitado por
            // alguém): o id só sai quando a inscrição é da conta.
            if (Inscription::ownedBy($participant)->whereKey($inscription->id)->exists()) {
                return response()->json([
                    'message' => 'Você já tem uma inscrição para este encontro.',
                    'code' => 'already_registered',
                    'inscription_id' => $inscription->id,
                ], 409);
            }

            return response()->json([
                'message' => self::DUPLICATE_OF_OTHER_EMAIL,
                'code' => 'already_registered_other_email',
            ], 409);
        }

        if ($result !== RegisterInscription::CREATED) {
            return $this->refusal(RegisterInscription::MESSAGES[$result], $result);
        }

        if (! $participant->name) {
            $participant->forceFill(['name' => $inscription->full_name])->save();
        }

        return (new InscriptionResource($inscription->load('event')))->response()->setStatusCode(201);
    }

    /**
     * POST /api/v1/inscriptions/{id}/payment-proof (multipart, AM19).
     */
    public function uploadPaymentProof(Request $request, int $id, SubmitPaymentProof $proof): InscriptionResource|JsonResponse
    {
        $inscription = $this->find($request, $id);

        if ($refusal = $proof->refusal($inscription)) {
            return $this->refusal(SubmitPaymentProof::MESSAGES[$refusal], $refusal);
        }

        $request->validate(SubmitPaymentProof::rules(), SubmitPaymentProof::messages());

        if ($refusal = $proof->handle($inscription, $request->file('payment_proof'))) {
            return $this->refusal(SubmitPaymentProof::MESSAGES[$refusal], $refusal);
        }

        return new InscriptionResource($inscription->refresh()->load('event'));
    }

    /**
     * POST /api/v1/inscriptions/{id}/social-request
     */
    public function submitSocialRequest(Request $request, int $id, SubmitSocialRequest $socialRequest): InscriptionResource|JsonResponse
    {
        $inscription = $this->find($request, $id);

        if ($refusal = $socialRequest->refusal($inscription)) {
            return $this->refusal(SubmitSocialRequest::MESSAGES[$refusal], $refusal);
        }

        $data = $request->validate(SubmitSocialRequest::rules(), SubmitSocialRequest::messages());

        $socialRequest->handle($inscription, $data['reason'], (float) $data['amount']);

        return new InscriptionResource($inscription->refresh()->load('event'));
    }

    /**
     * POST /api/v1/inscriptions/{id}/cancel: confirmado não cancela pelo app.
     */
    public function cancel(Request $request, int $id, CancelInscription $cancel): InscriptionResource|JsonResponse
    {
        $inscription = $this->find($request, $id);

        if ($inscription->isConfirmed()) {
            return $this->refusal(self::CONFIRMED_CANNOT_CANCEL, 'confirmed');
        }

        if ($refusal = $cancel->handle($inscription)) {
            return $this->refusal(CancelInscription::MESSAGES[$refusal], $refusal);
        }

        return new InscriptionResource($inscription->refresh()->load('event'));
    }

    private function participant(Request $request): Participant
    {
        return $request->user();
    }

    private function find(Request $request, int $id): Inscription
    {
        return Inscription::ownedBy($this->participant($request))->with('event')->findOrFail($id);
    }

    private function refusal(string $message, string $code): JsonResponse
    {
        return response()->json(['message' => $message, 'code' => $code], 422);
    }
}
