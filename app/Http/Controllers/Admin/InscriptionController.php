<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Inscription;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InscriptionController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Lista de inscrições com filtros por encontro, cidade e status.
     */
    public function index(Request $request)
    {
        $query = Inscription::with('event');

        // Ordenação
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['full_name', 'created_at', 'status', 'email'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Filtro por encontro
        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        // Filtro por status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por comprovante
        if ($request->filled('comprovante')) {
            if ($request->comprovante === 'enviado') {
                $query->whereNotNull('payment_proof');
            } elseif ($request->comprovante === 'pendente') {
                $query->whereNull('payment_proof');
            }
        }

        // Busca por nome
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('cpf', 'like', "%{$search}%");
            });
        }

        $inscriptions = $query->paginate(20)->appends($request->query());

        // Meses em português para labels
        $meses = [
            'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
        ];

        // Encontros para o filtro
        $events = Event::orderBy('date', 'desc')->get()->map(fn ($e) => [
            'id' => $e->id,
            'label' => ($e->city ?? $e->title).' - '.$meses[$e->date->month - 1].'/'.$e->date->year,
        ]);

        // Contadores sensíveis ao filtro de encontro
        $countsQuery = Inscription::query();

        if ($request->filled('event_id')) {
            $countsQuery->where('event_id', $request->event_id);
        }

        $counts = [
            'total' => (clone $countsQuery)->count(),
            'pendente' => (clone $countsQuery)->where('status', 'pendente')->count(),
            'aprovado' => (clone $countsQuery)->where('status', 'aprovado')->count(),
            'confirmado' => (clone $countsQuery)->where('status', 'confirmado')->count(),
            'fila_de_espera' => (clone $countsQuery)->where('status', 'fila_de_espera')->count(),
            'rejeitado' => (clone $countsQuery)->where('status', 'rejeitado')->count(),
            'cancelado' => (clone $countsQuery)->where('status', 'cancelado')->count(),
        ];

        return view('admin.inscriptions.index', compact('inscriptions', 'events', 'counts'));
    }

    /**
     * Detalhes de uma inscrição.
     */
    public function show(Inscription $inscription)
    {
        $inscription->load('event');

        $activityLogs = ActivityLog::where('subject_type', Inscription::class)
            ->where('subject_id', $inscription->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.inscriptions.show', compact('inscription', 'activityLogs'));
    }

    /**
     * Aprovar inscrição.
     */
    public function approve(Inscription $inscription)
    {
        if (! $inscription->isPending() && ! $inscription->isWaitlisted()) {
            return redirect()
                ->back()
                ->with('error', 'Apenas inscrições pendentes ou em fila de espera podem ser aprovadas.');
        }

        try {
            $inscription->approve();
        } catch (\Throwable $e) {
            Log::error('Falha ao aprovar inscrição: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()->back()->with('error', 'Erro ao aprovar inscrição. Verifique os logs.');
        }

        try {
            ActivityLog::log('aprovar_inscricao', "Aprovou inscrição de {$inscription->full_name}", $inscription);
        } catch (\Throwable $e) {
            Log::warning('Falha ao registrar log de atividade: '.$e->getMessage());
        }

        try {
            $this->notificationService->notifyInscriptionApproved($inscription);
        } catch (\Throwable $e) {
            Log::error('Falha ao notificar aprovação: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }

        return redirect()
            ->back()
            ->with('success', "Inscrição de {$inscription->full_name} aprovada com sucesso! Link de pagamento enviado.");
    }

    /**
     * Mover para fila de espera.
     */
    public function waitlist(Inscription $inscription)
    {
        if (! $inscription->isPending()) {
            return redirect()
                ->back()
                ->with('error', 'Apenas inscrições pendentes podem ser movidas para a fila de espera.');
        }

        try {
            $inscription->waitlist();
        } catch (\Throwable $e) {
            Log::error('Falha ao mover para fila: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()->back()->with('error', 'Erro ao mover para fila de espera. Verifique os logs.');
        }

        try {
            ActivityLog::log('fila_espera_inscricao', "Moveu {$inscription->full_name} para fila de espera", $inscription);
        } catch (\Throwable $e) {
            Log::warning('Falha ao registrar log de atividade: '.$e->getMessage());
        }

        try {
            $this->notificationService->notifyInscriptionWaitlisted($inscription);
        } catch (\Throwable $e) {
            Log::error('Falha ao notificar fila de espera: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }

        return redirect()
            ->back()
            ->with('success', "{$inscription->full_name} movido(a) para a fila de espera.");
    }

    /**
     * Confirmar pagamento.
     */
    public function confirm(Request $request, Inscription $inscription)
    {
        if (! $inscription->isApproved()) {
            return redirect()
                ->back()
                ->with('error', 'Apenas inscrições aprovadas podem ter o pagamento confirmado.');
        }

        // Salvar valor da contribuição (se informado)
        if ($request->filled('contribution_amount')) {
            $inscription->contribution_amount = $request->contribution_amount;
            $inscription->save();
        }

        try {
            $inscription->confirm();
        } catch (\Throwable $e) {
            Log::error('Falha ao confirmar inscrição: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()->back()->with('error', 'Erro ao confirmar pagamento. Verifique os logs.');
        }

        try {
            ActivityLog::log('confirmar_inscricao', "Confirmou pagamento de {$inscription->full_name}", $inscription);
        } catch (\Throwable $e) {
            Log::warning('Falha ao registrar log de atividade: '.$e->getMessage());
        }

        try {
            $this->notificationService->notifyInscriptionConfirmed($inscription);
        } catch (\Throwable $e) {
            Log::error('Falha ao notificar confirmação: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }

        return redirect()
            ->back()
            ->with('success', "Pagamento de {$inscription->full_name} confirmado! Endereço enviado.");
    }

    /**
     * Enviar lembrete de comprovante.
     */
    public function sendReminder(Inscription $inscription)
    {
        if (! $inscription->isApproved() || $inscription->payment_proof) {
            return redirect()
                ->back()
                ->with('error', 'O lembrete só pode ser enviado para inscrições aprovadas sem comprovante.');
        }

        try {
            $event = $inscription->event;
            $statusUrl = route('inscricao.status', $inscription->token);

            // Email
            $this->notificationService->sendEmail(
                $inscription->email,
                'Lembrete: Envie seu comprovante - De Casa em Casa',
                "Lembrete de comprovante para {$inscription->full_name}",
                null,
                'payment_reminder',
                ['inscription_id' => $inscription->id],
                'emails.payment-reminder',
                ['inscription' => $inscription, 'event' => $event, 'statusUrl' => $statusUrl]
            );

            // WhatsApp
            $wa = "Olá {$inscription->full_name}! Lembramos que sua participação no encontro *De Casa em Casa* em *{$event->city}* foi aprovada. ";
            $wa .= "Para garantir sua vaga, envie o comprovante de pagamento pelo link: {$statusUrl}";

            $this->notificationService->sendWhatsApp(
                $inscription->whatsapp,
                $wa,
                null,
                'payment_reminder',
                ['inscription_id' => $inscription->id]
            );
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar lembrete: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()->back()->with('error', 'Erro ao enviar lembrete. Verifique os logs.');
        }

        return redirect()
            ->back()
            ->with('success', "Lembrete enviado para {$inscription->full_name}!");
    }

    /**
     * Rejeitar inscrição.
     */
    public function reject(Inscription $inscription)
    {
        if (! $inscription->isPending() && ! $inscription->isWaitlisted()) {
            return redirect()
                ->back()
                ->with('error', 'Apenas inscrições pendentes ou em fila de espera podem ser rejeitadas.');
        }

        try {
            $inscription->reject();
        } catch (\Throwable $e) {
            Log::error('Falha ao rejeitar inscrição: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return redirect()->back()->with('error', 'Erro ao rejeitar inscrição. Verifique os logs.');
        }

        try {
            ActivityLog::log('rejeitar_inscricao', "Rejeitou inscrição de {$inscription->full_name}", $inscription);
        } catch (\Throwable $e) {
            Log::warning('Falha ao registrar log de atividade: '.$e->getMessage());
        }

        try {
            $this->notificationService->notifyInscriptionRejected($inscription);
        } catch (\Throwable $e) {
            Log::error('Falha ao notificar rejeição: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }

        return redirect()
            ->back()
            ->with('success', "Inscrição de {$inscription->full_name} foi rejeitada.");
    }

    /**
     * Ações em lote.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:aprovar,rejeitar,fila_espera',
            'inscription_ids' => 'required|array|min:1',
            'inscription_ids.*' => 'exists:inscriptions,id',
        ]);

        $inscriptions = Inscription::whereIn('id', $request->inscription_ids)->get();
        $count = 0;

        foreach ($inscriptions as $inscription) {
            try {
                switch ($request->action) {
                    case 'aprovar':
                        if ($inscription->isPending() || $inscription->isWaitlisted()) {
                            $inscription->approve();
                            $count++;
                            try {
                                $this->notificationService->notifyInscriptionApproved($inscription);
                            } catch (\Throwable $e) {
                                Log::error("Falha ao notificar aprovação da inscrição #{$inscription->id}: ".$e->getMessage());
                            }
                        }
                        break;
                    case 'rejeitar':
                        if ($inscription->isPending() || $inscription->isWaitlisted()) {
                            $inscription->reject();
                            $count++;
                            try {
                                $this->notificationService->notifyInscriptionRejected($inscription);
                            } catch (\Throwable $e) {
                                Log::error("Falha ao notificar rejeição da inscrição #{$inscription->id}: ".$e->getMessage());
                            }
                        }
                        break;
                    case 'fila_espera':
                        if ($inscription->isPending()) {
                            $inscription->waitlist();
                            $count++;
                            try {
                                $this->notificationService->notifyInscriptionWaitlisted($inscription);
                            } catch (\Throwable $e) {
                                Log::error("Falha ao notificar fila de espera da inscrição #{$inscription->id}: ".$e->getMessage());
                            }
                        }
                        break;
                }
            } catch (\Throwable $e) {
                Log::error("Falha na ação em lote para inscrição #{$inscription->id}: ".$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }
        }

        $actionLabel = match ($request->action) {
            'aprovar' => 'aprovadas',
            'rejeitar' => 'rejeitadas',
            'fila_espera' => 'movidas para fila de espera',
        };

        try {
            ActivityLog::log('bulk_action', "Ação em lote: {$count} inscrições {$actionLabel}", null, [
                'action' => $request->action,
                'count' => $count,
                'ids' => $request->inscription_ids,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Falha ao registrar log de atividade: '.$e->getMessage());
        }

        return redirect()
            ->back()
            ->with('success', "{$count} inscrições {$actionLabel} com sucesso.");
    }

    /**
     * Exportar inscrições em CSV.
     */
    public function exportCsv(Request $request)
    {
        $query = Inscription::with('event')
            ->orderBy('created_at', 'desc');

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('cpf', 'like', "%{$search}%");
            });
        }

        $inscriptions = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="inscricoes_'.date('Y-m-d_His').'.csv"',
        ];

        $callback = function () use ($inscriptions) {
            $file = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'Nome Completo', 'CPF', 'Data Nascimento', 'Bairro/Cidade',
                'WhatsApp', 'Email', 'Instagram', 'Encontro', 'Data Encontro',
                'Status', 'Comprovante', 'Contribuição (R$)', 'Motivação', 'Data Inscrição',
            ], ';');

            foreach ($inscriptions as $inscription) {
                fputcsv($file, [
                    $inscription->full_name,
                    $inscription->formatted_cpf,
                    $inscription->birth_date->format('d/m/Y'),
                    $inscription->city_neighborhood,
                    $inscription->whatsapp,
                    $inscription->email,
                    $inscription->instagram ?? '',
                    $inscription->event->city ?? $inscription->event->title,
                    $inscription->event->date->format('d/m/Y'),
                    $inscription->status_label,
                    $inscription->payment_proof ? 'Sim' : 'Não',
                    $inscription->contribution_amount ? number_format($inscription->contribution_amount, 2, ',', '.') : '',
                    $inscription->motivation,
                    $inscription->created_at->format('d/m/Y H:i'),
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Atualizar notas do admin.
     */
    public function updateNotes(Request $request, Inscription $inscription)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $inscription->admin_notes = $request->admin_notes;
        $inscription->save();

        return redirect()
            ->back()
            ->with('success', 'Notas atualizadas.');
    }

    /**
     * Atualizar valor da contribuição.
     */
    public function updateContribution(Request $request, Inscription $inscription)
    {
        $request->validate([
            'contribution_amount' => 'nullable|numeric|min:0',
        ]);

        $inscription->contribution_amount = $request->contribution_amount;
        $inscription->save();

        return redirect()
            ->back()
            ->with('success', 'Valor da contribuição atualizado.');
    }
}
