<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Inscription;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

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
    public function index(Request $request)
    {
        $query = Notification::orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('recipient', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->filled('event_id')) {
            $inscriptionIds = Inscription::where('event_id', $request->event_id)->pluck('id')->toArray();
            if (count($inscriptionIds) > 0) {
                $query->whereRaw(
                    'JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.inscription_id")) IN (' . implode(',', $inscriptionIds) . ')'
                );
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('inscription_status')) {
            $inscriptionIds = Inscription::where('status', $request->inscription_status)->pluck('id')->toArray();
            if (count($inscriptionIds) > 0) {
                $query->whereRaw(
                    'JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.inscription_id")) IN (' . implode(',', $inscriptionIds) . ')'
                );
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $notifications = $query->paginate(25)->appends($request->query());

        $counts = [
            'total' => Notification::count(),
            'sent' => Notification::where('status', 'sent')->count(),
            'failed' => Notification::where('status', 'failed')->count(),
            'skipped' => Notification::where('status', 'skipped')->count(),
        ];

        $resentKeys = Notification::where('status', 'sent')
            ->get(['recipient', 'channel'])
            ->map(fn ($n) => $n->recipient . '|' . $n->channel)
            ->unique()
            ->values()
            ->toArray();

        $events = Event::orderBy('date', 'desc')->get(['id', 'title', 'city', 'date']);

        $inscriptionIds = $notifications->pluck('metadata.inscription_id')->filter()->unique()->toArray();
        $inscriptions = Inscription::with('event')
            ->whereIn('id', $inscriptionIds)
            ->get()
            ->keyBy('id');

        return view('admin.notifications.index', compact('notifications', 'counts', 'resentKeys', 'events', 'inscriptions'));
    }

    /**
     * Reenviar notificação que falhou.
     */
    public function resend(Notification $notification)
    {
        $success = false;

        if ($notification->type === 'email') {
            $inscriptionId = $notification->metadata['inscription_id'] ?? null;
            $inscription = $inscriptionId ? Inscription::with('event')->find($inscriptionId) : null;

            if ($inscription && $inscription->event && $notification->channel !== 'general') {
                $event = $inscription->event;
                $statusUrl = route('inscricao.status', $inscription->token);
                $viewMap = [
                    'inscription_received' => 'emails.inscription-received',
                    'inscription_approved' => 'emails.inscription-approved',
                    'inscription_waitlisted' => 'emails.inscription-waitlisted',
                    'inscription_confirmed' => 'emails.inscription-confirmed',
                    'inscription_rejected' => 'emails.inscription-rejected',
                    'inscription_cancelled' => 'emails.inscription-cancelled',
                ];
                $view = $viewMap[$notification->channel] ?? null;
                $viewData = ['inscription' => $inscription, 'event' => $event, 'statusUrl' => $statusUrl];

                $success = $this->notificationService->sendEmail(
                    $notification->recipient,
                    $notification->subject ?? 'De Casa em Casa',
                    $notification->message,
                    null,
                    $notification->channel,
                    $notification->metadata ?? [],
                    $view,
                    $viewData
                );
            } else {
                $success = $this->notificationService->sendEmail(
                    $notification->recipient,
                    $notification->subject ?? 'De Casa em Casa',
                    $notification->message,
                    null,
                    $notification->channel,
                    $notification->metadata ?? []
                );
            }
        } elseif ($notification->type === 'whatsapp') {
            $success = $this->notificationService->sendWhatsApp(
                $notification->recipient,
                $notification->message,
                null,
                $notification->channel,
                $notification->metadata ?? []
            );
        }

        if ($success) {
            return redirect()
                ->back()
                ->with('success', 'Notificação reenviada com sucesso!');
        }

        return redirect()
            ->back()
            ->with('error', 'Falha ao reenviar a notificação. Verifique os logs.');
    }
}
