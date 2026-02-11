<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Inscription;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Cards de ação rápida
        $stats = [
            'pending' => Inscription::where('status', 'pendente')->count(),
            'awaiting_proof' => Inscription::where('status', 'aprovado')->whereNull('payment_proof')->count(),
            'proof_sent' => Inscription::where('status', 'aprovado')->whereNotNull('payment_proof')->count(),
            'confirmed' => Inscription::where('status', 'confirmado')->count(),
            'waitlisted' => Inscription::where('status', 'fila_de_espera')->count(),
            'total_inscriptions' => Inscription::count(),
        ];

        // Próximo encontro (destaque)
        $next_event = Event::where('status', 'published')
            ->where('date', '>=', now())
            ->orderBy('date', 'asc')
            ->withCount('inscriptions')
            ->first();

        // Encontros ativos (publicados e futuros)
        $active_events = Event::where('status', 'published')
            ->where('date', '>=', now())
            ->orderBy('date', 'asc')
            ->withCount('inscriptions')
            ->get();

        // Contadores de encontros
        $events_counts = [
            'active' => $active_events->count(),
            'past' => Event::where('date', '<', now())->count(),
            'total' => Event::count(),
        ];

        // Inscrições recentes
        $recent_inscriptions = Inscription::with('event')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Inscrições por encontro (cidade + mês/ano)
        $meses = [
            'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
        ];

        $inscriptions_by_city = Inscription::join('events', 'inscriptions.event_id', '=', 'events.id')
            ->select('events.id as event_id', 'events.city', 'events.date', DB::raw('COUNT(*) as count'))
            ->whereNotNull('events.city')
            ->whereNull('events.deleted_at')
            ->groupBy('events.id', 'events.city', 'events.date')
            ->orderBy('events.date', 'desc')
            ->get()
            ->map(function ($item) use ($meses) {
                $date = \Carbon\Carbon::parse($item->date);
                $item->label = $item->city.' - '.$meses[$date->month - 1].'/'.$date->year;

                return $item;
            });

        return view('admin.dashboard', compact(
            'stats',
            'next_event',
            'active_events',
            'events_counts',
            'recent_inscriptions',
            'inscriptions_by_city'
        ));
    }
}
