<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Inscription;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class InscriptionController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Lista de inscrições com filtros por cidade e status.
     */
    public function index(Request $request)
    {
        $query = Inscription::with('event')
            ->orderBy('created_at', 'desc');

        // Filtro por cidade
        if ($request->filled('city')) {
            $query->whereHas('event', function ($q) use ($request) {
                $q->where('city', $request->city);
            });
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

        // Cidades para o filtro
        $cities = Event::whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->pluck('city')
            ->sort();

        // Contadores
        $counts = [
            'total' => Inscription::count(),
            'pendente' => Inscription::where('status', 'pendente')->count(),
            'aprovado' => Inscription::where('status', 'aprovado')->count(),
            'confirmado' => Inscription::where('status', 'confirmado')->count(),
            'fila_de_espera' => Inscription::where('status', 'fila_de_espera')->count(),
        ];

        return view('admin.inscriptions.index', compact('inscriptions', 'cities', 'counts'));
    }

    /**
     * Detalhes de uma inscrição.
     */
    public function show(Inscription $inscription)
    {
        $inscription->load('event');

        return view('admin.inscriptions.show', compact('inscription'));
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

        $inscription->approve();

        // Notificar participante
        $this->notificationService->notifyInscriptionApproved($inscription);

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

        $inscription->waitlist();

        // Notificar participante
        $this->notificationService->notifyInscriptionWaitlisted($inscription);

        return redirect()
            ->back()
            ->with('success', "{$inscription->full_name} movido(a) para a fila de espera.");
    }

    /**
     * Confirmar pagamento.
     */
    public function confirm(Inscription $inscription)
    {
        if (! $inscription->isApproved()) {
            return redirect()
                ->back()
                ->with('error', 'Apenas inscrições aprovadas podem ter o pagamento confirmado.');
        }

        $inscription->confirm();

        // Notificar participante com endereço
        $this->notificationService->notifyInscriptionConfirmed($inscription);

        return redirect()
            ->back()
            ->with('success', "Pagamento de {$inscription->full_name} confirmado! Endereço enviado.");
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
}
