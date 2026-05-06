<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Inscription;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InscriptionController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'required|exists:events,id',
            'full_name' => 'required|string|max:255',
            'cpf' => ['required', 'string', 'max:14', function ($attribute, $value, $fail) {
                $cpf = preg_replace('/\D/', '', $value);
                if (strlen($cpf) !== 11) {
                    $fail('O CPF deve conter 11 dígitos.');

                    return;
                }
                if (! $this->validateCpf($cpf)) {
                    $fail('O CPF informado é inválido.');
                }
            }],
            'birth_date' => 'required|date|before:today',
            'city_neighborhood' => 'required|string|max:255',
            'whatsapp' => 'required|string|max:20',
            'email' => ['required', 'email', 'max:255', function ($attribute, $value, $fail) {
                $suggestion = $this->detectEmailTypo($value);
                if ($suggestion) {
                    $fail("Verifique seu e-mail. Você quis dizer {$suggestion}?");

                    return;
                }
                if (! $this->emailDomainAcceptsMail($value)) {
                    $fail('O domínio deste e-mail não recebe mensagens. Verifique se digitou corretamente.');
                }
            }],
            'instagram' => 'nullable|string|max:255',
            'motivation' => 'required|string|min:10',
            'terms_accepted' => 'required|accepted',
        ], [
            'event_id.required' => 'Selecione o encontro que deseja participar.',
            'event_id.exists' => 'O encontro selecionado não existe.',
            'full_name.required' => 'Informe seu nome completo.',
            'cpf.required' => 'Informe seu CPF.',
            'birth_date.required' => 'Informe sua data de nascimento.',
            'birth_date.before' => 'A data de nascimento deve ser anterior a hoje.',
            'city_neighborhood.required' => 'Informe seu bairro e cidade.',
            'whatsapp.required' => 'Informe seu WhatsApp.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'motivation.required' => 'Conte-nos sua história.',
            'motivation.min' => 'Sua história deve ter pelo menos 10 caracteres.',
            'terms_accepted.required' => 'Você deve aceitar os termos.',
            'terms_accepted.accepted' => 'Você deve aceitar os termos.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Verificar se o evento está publicado
        $event = Event::findOrFail($request->event_id);
        if (! $event->isPublished()) {
            return redirect()->back()->with('error', 'Este encontro não está disponível para inscrições.');
        }

        // Verificar se o evento está esgotado
        if ($event->isFull()) {
            return redirect()->back()->with('error', 'As vagas para este encontro estão esgotadas.');
        }

        // Verificar inscrição duplicada por CPF + evento
        $cpfClean = preg_replace('/\D/', '', $request->cpf);
        $existingInscription = Inscription::where('event_id', $request->event_id)
            ->where(function ($query) use ($cpfClean, $request) {
                $query->whereRaw("REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?", [$cpfClean])
                    ->orWhere('email', $request->email);
            })
            ->first();

        if ($existingInscription) {
            return redirect()
                ->route('inscricao.status', $existingInscription->token)
                ->with('info', 'Você já possui uma inscrição para este encontro. Aqui está o status dela.');
        }

        // Criar inscrição
        $inscription = Inscription::create([
            'event_id' => $request->event_id,
            'full_name' => $request->full_name,
            'cpf' => $request->cpf,
            'birth_date' => $request->birth_date,
            'city_neighborhood' => $request->city_neighborhood,
            'whatsapp' => $request->whatsapp,
            'email' => $request->email,
            'instagram' => $request->instagram,
            'motivation' => $request->motivation,
            'terms_accepted' => true,
            'status' => 'pendente',
        ]);

        // Notificar participante
        $this->notificationService->notifyInscriptionReceived($inscription);

        return redirect()
            ->route('inscricao.status', $inscription->token)
            ->with('success', 'Inscrição realizada com sucesso!');
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
    public function uploadPaymentProof(Request $request, string $token)
    {
        $inscription = Inscription::where('token', $token)->firstOrFail();

        if (! $inscription->isApproved()) {
            return redirect()
                ->route('inscricao.status', $token)
                ->with('error', 'Sua inscrição não está em status de aprovação para envio de comprovante.');
        }

        $request->validate([
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'payment_proof.required' => 'Selecione o arquivo do comprovante.',
            'payment_proof.mimes' => 'O comprovante deve ser uma imagem (JPG, PNG) ou PDF.',
            'payment_proof.max' => 'O arquivo deve ter no máximo 5MB.',
        ]);

        // Salvar arquivo
        $path = $request->file('payment_proof')->store('payment_proofs', 'public');
        $inscription->payment_proof = $path;
        $inscription->save();

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
    public function submitSocialRequest(Request $request, string $token)
    {
        $inscription = Inscription::where('token', $token)->with('event')->firstOrFail();

        if (! $inscription->isApproved()) {
            return redirect()
                ->route('inscricao.status', $token)
                ->with('error', 'Solicitação disponível apenas para inscrições aprovadas.');
        }

        if ($inscription->event && $inscription->event->isFull()) {
            return redirect()
                ->route('inscricao.status', $token)
                ->with('error', 'Vagas esgotadas para este encontro.');
        }

        if ($inscription->isSocialRequestPending() || $inscription->isSocialRequestApproved()) {
            return redirect()
                ->route('inscricao.status', $token)
                ->with('error', 'Você já possui uma solicitação de contribuição social registrada.');
        }

        $request->validate([
            'reason' => 'required|string|min:20|max:1000',
            'amount' => 'required|numeric|min:0|max:99999.99',
        ], [
            'reason.required' => 'Conte brevemente sua situação.',
            'reason.min' => 'Sua justificativa deve ter pelo menos 20 caracteres.',
            'reason.max' => 'Sua justificativa não pode passar de 1000 caracteres.',
            'amount.required' => 'Informe o valor que você consegue contribuir.',
            'amount.numeric' => 'Informe um valor numérico.',
            'amount.min' => 'O valor não pode ser negativo.',
        ]);

        $inscription->submitSocialRequest($request->input('reason'), (float) $request->input('amount'));

        try {
            $this->notificationService->notifySocialRequestSubmitted($inscription);
        } catch (\Throwable $e) {
            Log::warning('Falha ao notificar solicitação social: '.$e->getMessage());
        }

        return redirect()
            ->route('inscricao.status', $token)
            ->with('success', 'Solicitação enviada! A equipe vai analisar e logo retornaremos.');
    }

    /**
     * Cancelar inscrição pelo participante.
     */
    public function cancel(Request $request, string $token)
    {
        $inscription = Inscription::where('token', $token)->firstOrFail();

        // Só pode cancelar se não estiver já cancelada ou rejeitada
        if ($inscription->isCancelled() || $inscription->isRejected()) {
            return redirect()
                ->route('inscricao.status', $token)
                ->with('error', 'Esta inscrição já está cancelada.');
        }

        $inscription->cancel();

        // Notificar
        $this->notificationService->notifyInscriptionCancelled($inscription);

        return redirect()
            ->route('inscricao.status', $token)
            ->with('success', 'Sua inscrição foi cancelada com sucesso.');
    }

    /**
     * Detecta typos comuns no domínio do e-mail e retorna a sugestão de correção.
     * Retorna null se não houver typo detectado.
     */
    private function detectEmailTypo(?string $email): ?string
    {
        if (! $email || ! str_contains($email, '@')) {
            return null;
        }

        [$local, $domain] = explode('@', strtolower(trim($email)), 2);
        if (! $local || ! $domain) {
            return null;
        }

        $typoMap = [
            'gmail.com.br' => 'gmail.com',
            'gmail.con' => 'gmail.com',
            'gmail.cm' => 'gmail.com',
            'gmial.com' => 'gmail.com',
            'gmal.com' => 'gmail.com',
            'gmaill.com' => 'gmail.com',
            'gnail.com' => 'gmail.com',
            'gemail.com' => 'gmail.com',
            'hotmail.com.br' => 'hotmail.com',
            'hotmail.con' => 'hotmail.com',
            'hotmial.com' => 'hotmail.com',
            'hotmal.com' => 'hotmail.com',
            'hotmai.com' => 'hotmail.com',
            'hormail.com' => 'hotmail.com',
            'yahoo.con' => 'yahoo.com.br',
            'yhoo.com' => 'yahoo.com',
            'yhaoo.com' => 'yahoo.com',
            'outlook.con' => 'outlook.com',
            'outlok.com' => 'outlook.com',
            'outloook.com' => 'outlook.com',
            'icloud.con' => 'icloud.com',
            'iclound.com' => 'icloud.com',
        ];

        if (isset($typoMap[$domain])) {
            return $local.'@'.$typoMap[$domain];
        }

        // Heurística: terminação .con (no lugar de .com)
        if (str_ends_with($domain, '.con')) {
            return $local.'@'.substr($domain, 0, -4).'.com';
        }

        // Heurística: terminação .cm (no lugar de .com)
        if (str_ends_with($domain, '.cm') && ! str_ends_with($domain, '.com')) {
            return $local.'@'.substr($domain, 0, -3).'.com';
        }

        return null;
    }

    /**
     * Verifica se o domínio do e-mail aceita mensagens.
     * Detecta NULL MX (RFC 7505) e domínios sem registros DNS.
     * Em caso de erro de DNS retorna true (fail-open) para não barrar usuários legítimos.
     */
    private function emailDomainAcceptsMail(?string $email): bool
    {
        if (! $email || ! str_contains($email, '@')) {
            return true;
        }

        [, $domain] = explode('@', strtolower(trim($email)), 2);
        if (! $domain) {
            return true;
        }

        try {
            $mxRecords = @dns_get_record($domain, DNS_MX);

            if ($mxRecords === false) {
                return true;
            }

            if (! empty($mxRecords)) {
                // Detecta NULL MX (RFC 7505): único registro com target "." e prioridade 0
                if (count($mxRecords) === 1
                    && ($mxRecords[0]['target'] ?? '') === ''
                    && (int) ($mxRecords[0]['pri'] ?? -1) === 0) {
                    return false;
                }

                return true;
            }

            // Sem MX: e-mail ainda pode ser entregue se houver registro A (fallback)
            $aRecords = @dns_get_record($domain, DNS_A);

            return ! empty($aRecords);
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * Validação de CPF (dígitos verificadores).
     */
    private function validateCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) !== 11) {
            return false;
        }

        // CPFs com todos dígitos iguais são inválidos
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Validar primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        if ((int) $cpf[9] !== $digit1) {
            return false;
        }

        // Validar segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        return (int) $cpf[10] === $digit2;
    }
}
