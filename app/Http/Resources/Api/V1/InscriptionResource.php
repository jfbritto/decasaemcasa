<?php

namespace App\Http\Resources\Api\V1;

use App\Inscriptions\InscriptionView;
use App\Models\Inscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Uma inscrição do participante (spec 2026-11-08-app-mobile-design, AM13 a
 * AM15). O estado e o que pode ser feito vêm prontos (InscriptionView); o
 * Pix sai só quando o comprovante pode ser enviado; endereço e horário, só
 * para confirmado.
 *
 * @mixin Inscription
 */
class InscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Inscription $inscription */
        $inscription = $this->resource;
        $event = $inscription->event;
        $canUpload = InscriptionView::canUploadProof($inscription);

        return [
            'id' => $inscription->id,
            'state' => InscriptionView::state($inscription),
            'status' => $inscription->status,
            'full_name' => $inscription->full_name,
            'created_at' => $inscription->created_at?->toIso8601String(),
            'upcoming' => $event !== null && $event->date !== null && $event->date->greaterThanOrEqualTo(now()->startOfDay()),
            'can' => [
                'upload_proof' => $canUpload,
                'request_social' => InscriptionView::canRequestSocial($inscription),
                'cancel' => InscriptionView::canCancel($inscription),
            ],
            'event' => $event ? [
                'id' => $event->id,
                'title' => $event->title,
                'city' => $event->city,
                'description' => $event->description,
                'date' => $event->date?->toIso8601String(),
                'image_url' => EventResource::imageUrl($event),
                'address' => $inscription->isConfirmed() ? $event->full_address : null,
                'arrival_time' => $inscription->isConfirmed() ? $event->arrival_time : null,
            ] : null,
            'payment' => $canUpload ? [
                'deadline_at' => $inscription->payment_deadline_at?->toIso8601String(),
                'deadline_label' => $inscription->payment_deadline_at ? $inscription->payment_deadline_label : null,
                'paused' => InscriptionView::deadlinePaused($inscription),
                'proof_sent' => (bool) $inscription->payment_proof,
                'pix' => config('services.pix.key') ? [
                    'key' => config('services.pix.key'),
                    'holder' => config('services.pix.holder'),
                ] : null,
            ] : null,
            'social_request' => $inscription->hasSocialRequest() ? [
                'status' => $inscription->social_request_status,
                'reason' => $inscription->social_request_reason,
                'amount' => $inscription->social_request_amount,
                'admin_message' => $inscription->social_request_admin_message,
                'submitted_at' => $inscription->social_request_submitted_at?->toIso8601String(),
            ] : null,
            // A página de status da web, para abrir no navegador.
            'status_url' => route('inscricao.status', $inscription->token),
        ];
    }
}
