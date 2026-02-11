<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        $notifications = $query->paginate(25)->appends($request->query());

        $counts = [
            'total' => Notification::count(),
            'sent' => Notification::where('status', 'sent')->count(),
            'failed' => Notification::where('status', 'failed')->count(),
            'skipped' => Notification::where('status', 'skipped')->count(),
        ];

        return view('admin.notifications.index', compact('notifications', 'counts'));
    }

    /**
     * Reenviar notificação que falhou.
     */
    public function resend(Notification $notification)
    {
        if ($notification->status !== 'failed' && $notification->status !== 'skipped') {
            return redirect()
                ->back()
                ->with('error', 'Apenas notificações com falha ou ignoradas podem ser reenviadas.');
        }

        $success = false;

        if ($notification->type === 'email') {
            $success = $this->notificationService->sendEmail(
                $notification->recipient,
                $notification->subject ?? 'De Casa em Casa',
                $notification->message,
                null,
                $notification->channel,
                $notification->metadata ?? []
            );
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
