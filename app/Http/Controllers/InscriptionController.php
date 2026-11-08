<?php

namespace App\Http\Controllers;

use App\Inscriptions\CancelInscription;
use App\Inscriptions\InscriptionRules;
use App\Inscriptions\RegisterInscription;
use App\Inscriptions\SubmitPaymentProof;
use App\Inscriptions\SubmitSocialRequest;
use App\Models\Event;
use App\Models\Inscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * O fluxo público da web. As regras moram em app/Inscriptions, divididas com
 * a API do app (spec 2026-11-08-app-mobile-design, AM17): aqui só se traduz
 * o resultado em redirect.
 */
class InscriptionController extends Controller
{
    /**
     * Formulário público de inscrição.
     */
    public function create()
    {
        $events = Event::where('status', 'published')
            ->whereDate('date', '>=', now())
            ->orderBy('date')
            ->get(['id', 'title', 'description', 'city', 'date', 'capacity', 'confirmed_count', 'status']);

        return view('inscriptions.create', compact('events'));
    }

    /**
     * Armazena nova inscrição.
     */
    public function store(Request $request, RegisterInscription $register)
    {
        $validator = Validator::make($request->all(), InscriptionRules::rules(), InscriptionRules::messages());

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $event = Event::findOrFail($request->event_id);

        [$result, $inscription] = $register->handle($event, $request->only([
            'full_name', 'cpf', 'birth_date', 'city_neighborhood', 'whatsapp', 'email', 'instagram', 'motivation',
        ]));

        return match ($result) {
            RegisterInscription::DUPLICATE => redirect()
                ->route('inscricao.status', $inscription->token)
                ->with('info', RegisterInscription::MESSAGES[RegisterInscription::DUPLICATE]),
            RegisterInscription::CREATED => redirect()
                ->route('inscricao.status', $inscription->token)
                ->with('success', 'Inscrição realizada com sucesso!'),
            default => redirect()->back()->with('error', RegisterInscription::MESSAGES[$result]),
        };
    }

    /**
     * Página de status da inscrição (acessível via token único).
     */
    public function status(string $token)
    {
        $inscription = Inscription::where('token', $token)
            ->with('event')
            ->firstOrFail();

        return view('inscriptions.status', compact('inscription'));
    }

    /**
     * Upload de comprovante de pagamento (só se aprovado).
     */
    public function uploadPaymentProof(Request $request, string $token, SubmitPaymentProof $proof)
    {
        $inscription = Inscription::where('token', $token)->firstOrFail();

        if ($refusal = $proof->refusal($inscription)) {
            return redirect()
                ->route('inscricao.status', $token)
                ->with('error', SubmitPaymentProof::MESSAGES[$refusal]);
        }

        $request->validate(SubmitPaymentProof::rules(), SubmitPaymentProof::messages());

        if ($refusal = $proof->handle($inscription, $request->file('payment_proof'))) {
            return redirect()
                ->route('inscricao.status', $token)
                ->with('error', SubmitPaymentProof::MESSAGES[$refusal]);
        }

        return redirect()->route('inscricao.upload-sucesso', $token);
    }

    /**
     * Página de confirmação após upload de comprovante.
     */
    public function uploadSuccess(string $token)
    {
        $inscription = Inscription::where('token', $token)
            ->with('event')
            ->firstOrFail();

        if (! $inscription->payment_proof) {
            return redirect()->route('inscricao.status', $token);
        }

        return view('inscriptions.upload-success', compact('inscription'));
    }

    /**
     * Submeter solicitação de contribuição social.
     */
    public function submitSocialRequest(Request $request, string $token, SubmitSocialRequest $socialRequest)
    {
        $inscription = Inscription::where('token', $token)->with('event')->firstOrFail();

        if ($refusal = $socialRequest->refusal($inscription)) {
            return redirect()
                ->route('inscricao.status', $token)
                ->with('error', SubmitSocialRequest::MESSAGES[$refusal]);
        }

        $request->validate(SubmitSocialRequest::rules(), SubmitSocialRequest::messages());

        $socialRequest->handle($inscription, $request->input('reason'), (float) $request->input('amount'));

        return redirect()
            ->route('inscricao.status', $token)
            ->with('success', 'Solicitação enviada! A equipe vai analisar e logo retornaremos.');
    }

    /**
     * Cancelar inscrição pelo participante.
     */
    public function cancel(Request $request, string $token, CancelInscription $cancel)
    {
        $inscription = Inscription::where('token', $token)->firstOrFail();

        if ($refusal = $cancel->handle($inscription)) {
            return redirect()
                ->route('inscricao.status', $token)
                ->with('error', CancelInscription::MESSAGES[$refusal]);
        }

        return redirect()
            ->route('inscricao.status', $token)
            ->with('success', 'Sua inscrição foi cancelada com sucesso.');
    }
}
