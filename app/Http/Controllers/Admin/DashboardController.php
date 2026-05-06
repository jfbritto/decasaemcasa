<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Inscription;
use App\Services\AdminPeriodFilter;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(AdminPeriodFilter $periodFilter)
    {
        // Cards de ação rápida (apenas eventos ativos: published ou draft + filtro de período)
        $activeEventScope = function ($query) use ($periodFilter) {
            $query->whereHas('event', function ($e) use ($periodFilter) {
                $e->whereIn('status', ['published', 'draft']);
                $periodFilter->applyToDate($e, 'date');
            });
        };
        $stats = [
            'pending' => Inscription::where('status', 'pendente')->where($activeEventScope)->count(),
            'awaiting_proof' => Inscription::where('status', 'aprovado')->whereNull('payment_proof')->where($activeEventScope)->count(),
            'proof_sent' => Inscription::where('status', 'aprovado')->whereNotNull('payment_proof')->where($activeEventScope)->count(),
            'confirmed' => Inscription::where('status', 'confirmado')->where($activeEventScope)->count(),
            'waitlisted' => Inscription::where('status', 'fila_de_espera')->where($activeEventScope)->count(),
            'total_inscriptions' => Inscription::where($activeEventScope)->count(),
        ];

        // Próximo encontro (destaque) — sempre futuro, mas respeita upper bound do período
        $nextEventQuery = Event::where('status', 'published')
            ->where('date', '>=', now())
            ->orderBy('date', 'asc')
            ->withCount('inscriptions');
        if ($periodFilter->getEnd()) {
            $nextEventQuery->where('date', '<=', $periodFilter->getEnd());
        }
        $next_event = $nextEventQuery->first();

        // Encontros ativos (publicados e futuros, dentro do período)
        $activeEventsQuery = Event::where('status', 'published')
            ->where('date', '>=', now())
            ->orderBy('date', 'asc')
            ->withCount('inscriptions');
        if ($periodFilter->getEnd()) {
            $activeEventsQuery->where('date', '<=', $periodFilter->getEnd());
        }
        $active_events = $activeEventsQuery->get();

        // Contadores de encontros (respeitam o filtro de período)
        $eventsBaseQuery = Event::query();
        $periodFilter->applyToDate($eventsBaseQuery, 'date');

        $events_counts = [
            'active' => $active_events->count(),
            'past' => (clone $eventsBaseQuery)->where('date', '<', now())->count(),
            'total' => (clone $eventsBaseQuery)->count(),
        ];

        // Inscrições recentes — filtradas pelo período do evento
        $recent_inscriptions = Inscription::with('event')
            ->whereHas('event', function ($e) use ($periodFilter) {
                $periodFilter->applyToDate($e, 'date');
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Inscrições por encontro (cidade + mês/ano)
        $meses = [
            'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
        ];

        $inscriptionsByCityQuery = Inscription::join('events', 'inscriptions.event_id', '=', 'events.id')
            ->select('events.id as event_id', 'events.city', 'events.date', DB::raw('COUNT(*) as count'))
            ->whereNotNull('events.city')
            ->whereNull('events.deleted_at')
            ->groupBy('events.id', 'events.city', 'events.date')
            ->orderBy('events.date', 'desc');
        $periodFilter->applyToDate($inscriptionsByCityQuery, 'events.date');
        $inscriptions_by_city = $inscriptionsByCityQuery->get()
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
