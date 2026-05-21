<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ResendFailedNotification;
use App\Models\Event;
use App\Models\Inscription;
use App\Models\Notification;
use App\Services\AdminPeriodFilter;
use App\Services\NotificationResendService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Lista de notificações enviadas com filtros.
     */
    public function index(Request $request, AdminPeriodFilter $periodFilter)
    {
        $baseFilter = function ($q) use ($request, $periodFilter) {
            if ($request->filled('type')) {
                $q->where('type', $request->type);
            }
            if ($request->filled('channel')) {
                $q->where('channel', $request->channel);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('recipient', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            }
            if ($request->filled('event_id')) {
                $ids = Inscription::where('event_id', $request->event_id)->pluck('id')->toArray();
                if (count($ids) > 0) {
                    $q->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.inscription_id")) IN (' . implode(',', $ids) . ')');
                } else {
                    $q->whereRaw('1 = 0');
                }
            }
            if ($request->filled('inscription_status')) {
                $ids = Inscription::where('status', $request->inscription_status)->pluck('id')->toArray();
                if (count($ids) > 0) {
                    $q->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.inscription_id")) IN (' . implode(',', $ids) . ')');
                } else {
                    $q->whereRaw('1 = 0');
                }
            }
            // Período global: filtra por created_at da notificação
            $periodFilter->applyToDate($q, 'created_at');
        };

        $countBase = Notification::where(function ($q) use ($baseFilter) { $baseFilter($q); });
        $counts = [
            'total' => (clone $countBase)->count(),
            'sent' => (clone $countBase)->where('status', 'sent')->count(),
            'failed' => (clone $countBase)->where('status', 'failed')->count(),
            'skipped' => (clone $countBase)->where('status', 'skipped')->count(),
        ];

        $query = Notification::orderBy('created_at', 'desc');
        $baseFilter($query);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $notifications = $query->paginate(25)->appends($request->query());

        $resentKeys = Notification::where('status', 'sent')
            ->get(['recipient', 'channel'])
            ->map(fn ($n) => $n->recipient . '|' . $n->channel)
            ->unique()
            ->values()
            ->toArray();

        $eventsQuery = Event::orderBy('date', 'desc');
        $periodFilter->applyToDate($eventsQuery, 'date');
        $events = $eventsQuery->get(['id', 'title', 'city', 'date']);

        $inscriptionIds = $notifications->pluck('metadata.inscription_id')->filter()->unique()->toArray();
        $inscriptions = Inscription::with(['event' => fn ($q) => $q->withTrashed()])
            ->whereIn('id', $inscriptionIds)
            ->get()
            ->keyBy('id');

        // IDs de notificações "failed" (para identificar reenvios antigos sem resent_from)
        $failedKeys = Notification::where('status', 'failed')
            ->whereIn('recipient', $notifications->pluck('recipient')->unique())
            ->get(['recipient', 'channel'])
            ->map(fn ($n) => $n->recipient.'|'.$n->channel)
            ->unique()
            ->toArray();

        $rateLimitPendingCount = $this->rateLimitPendingQuery($periodFilter)->count();
        $rateLimitInFlightCount = $this->rateLimitInFlightQuery($periodFilter)->count();
        $rateLimitCompletedRecentlyCount = $this->rateLimitCompletedRecentlyCount($periodFilter);

        return view('admin.notifications.index', compact('notifications', 'counts', 'resentKeys', 'events', 'inscriptions', 'failedKeys', 'rateLimitPendingCount', 'rateLimitInFlightCount', 'rateLimitCompletedRecentlyCount'));
    }

    /**
     * Reenviar notificação que falhou.
     */
    public function resend(Notification $notification, NotificationResendService $resender)
    {
        $success = $resender->resend($notification);

        if ($success) {
            return redirect()
                ->back()
                ->with('success', 'Notificação reenviada com sucesso!');
        }

        return redirect()
            ->back()
            ->with('error', 'Falha ao reenviar a notificação. Verifique os logs.');
    }

    /**
     * Enfileira reenvio em lote de notificações que falharam por limite de envio
     * do Titan (Hourly Quota Exceeded) e ainda não foram reenviadas com sucesso.
     *
     * Cada job é despachado com delay incremental de 20 segundos — espalhando
     * cerca de 180 envios/hora, abaixo do limite de 200/hora do Titan.
     */
    public function bulkResendRateLimit(AdminPeriodFilter $periodFilter)
    {
        $candidates = $this->rateLimitPendingQuery($periodFilter)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($candidates->isEmpty()) {
            return redirect()
                ->back()
                ->with('info', 'Nenhuma notificação pendente de reenvio por limite de envio.');
        }

        $count = 0;
        foreach ($candidates as $notification) {
            try {
                $metadata = $notification->metadata ?? [];
                $metadata['resend_queued_at'] = now()->toIso8601String();
                $notification->metadata = $metadata;
                $notification->save();

                ResendFailedNotification::dispatch($notification)
                    ->delay(now()->addSeconds($count * 20));
                $count++;
            } catch (\Throwable $e) {
                Log::error('Falha ao enfileirar reenvio', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $estimatedMinutes = (int) ceil(($count * 20) / 60);
        $msg = "{$count} reenvios enfileirados em segundo plano.";
        if ($estimatedMinutes >= 1) {
            $msg .= " Tempo estimado para concluir: ~{$estimatedMinutes} min (1 envio a cada 20s para respeitar o limite do Titan).";
        }

        return redirect()
            ->back()
            ->with('success', $msg);
    }

    /**
     * Query base: notificações com falha por rate limit do Titan, ainda não reenviadas com sucesso.
     */
    /**
     * Notificações que foram enfileiradas para reenvio nas últimas 2h
     * mas ainda não tiveram o reenvio bem-sucedido (estão "em vôo").
     */
    /**
     * Padrões de erro considerados "recuperáveis" — falhas de infraestrutura
     * (rate limit, auth, mailer mal configurado) que podem ser corrigidas
     * e reenviadas em massa. Não inclui erros do destinatário (mailbox inexistente,
     * caixa cheia, etc.) que não adianta reenviar.
     */
    private const RECOVERABLE_ERROR_PATTERNS = [
        // Sender-side limits do Titan (qualquer 5.4.6 é limite/quota/abuse do remetente)
        '%5.4.6%',
        // Falhas temporárias SMTP (4xx por definição são retryable)
        '%421 %',
        '%Expected response code "220"%',
        // Autenticação (Titan suspenso, senha errada, etc.)
        '%Failed to authenticate%',
        '%authentication failed%',
        // Conexão/rede
        '%Connection could not be established%',
        '%Connection refused%',
        '%Connection timed out%',
        '%has been closed unexpectedly%',
        // Configuração de mailer (caso da migração Resend)
        '%Mailer [%] is not defined%',
    ];

    private function applyRecoverableErrorFilter($query)
    {
        return $query->where(function ($q) {
            foreach (self::RECOVERABLE_ERROR_PATTERNS as $pattern) {
                $q->orWhere('error_message', 'like', $pattern);
            }
        });
    }

    private function rateLimitInFlightQuery(?AdminPeriodFilter $periodFilter = null)
    {
        $resentIds = Notification::where('status', 'sent')
            ->whereNotNull('metadata->resent_from')
            ->get(['metadata'])
            ->pluck('metadata.resent_from')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $inFlightCutoff = now()->subHours(2)->toIso8601String();

        $query = Notification::where('status', 'failed')
            ->whereNotNull('metadata->resend_queued_at')
            ->where('metadata->resend_queued_at', '>=', $inFlightCutoff)
            ->when(! empty($resentIds), fn ($q) => $q->whereNotIn('id', $resentIds));

        $this->applyRecoverableErrorFilter($query);

        if ($periodFilter) {
            $periodFilter->applyToDate($query, 'created_at');
        }

        return $query;
    }

    /**
     * Quantos reenvios foram concluídos com sucesso nas últimas 2 horas.
     * Usado para mostrar "X de Y já processados" no card de progresso.
     */
    private function rateLimitCompletedRecentlyCount(?AdminPeriodFilter $periodFilter = null): int
    {
        $query = Notification::where('status', 'sent')
            ->whereNotNull('metadata->resent_from')
            ->where('created_at', '>=', now()->subHours(2));

        if ($periodFilter) {
            $periodFilter->applyToDate($query, 'created_at');
        }

        return $query->count();
    }

    private function rateLimitPendingQuery(?AdminPeriodFilter $periodFilter = null)
    {
        $resentIds = Notification::where('status', 'sent')
            ->whereNotNull('metadata->resent_from')
            ->get(['metadata'])
            ->pluck('metadata.resent_from')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // Janela de "em vôo": notificações enfileiradas nas últimas 2 horas
        // são consideradas em processamento. Após 2h sem sucesso, voltam para fila de pendentes.
        $inFlightCutoff = now()->subHours(2)->toIso8601String();

        $query = Notification::where('status', 'failed')
            ->when(! empty($resentIds), fn ($q) => $q->whereNotIn('id', $resentIds))
            ->where(function ($q) use ($inFlightCutoff) {
                $q->whereNull('metadata->resend_queued_at')
                    ->orWhere('metadata->resend_queued_at', '<', $inFlightCutoff);
            });

        $this->applyRecoverableErrorFilter($query);

        if ($periodFilter) {
            $periodFilter->applyToDate($query, 'created_at');
        }

        return $query;
    }

}
