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
        $stats = [
            'total_events' => Event::count(),
            'active_events' => Event::where('status', 'published')->count(),
            'total_inscriptions' => Inscription::count(),
            'pending_inscriptions' => Inscription::where('status', 'pendente')->count(),
            'approved_inscriptions' => Inscription::where('status', 'aprovado')->count(),
            'confirmed_inscriptions' => Inscription::where('status', 'confirmado')->count(),
            'waitlisted_inscriptions' => Inscription::where('status', 'fila_de_espera')->count(),
        ];

        $recent_inscriptions = Inscription::with('event')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $events_by_status = Event::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        $inscriptions_by_city = Inscription::join('events', 'inscriptions.event_id', '=', 'events.id')
            ->select('events.id as event_id', 'events.city', DB::raw('COUNT(*) as count'))
            ->whereNotNull('events.city')
            ->whereNull('events.deleted_at')
            ->groupBy('events.id', 'events.city')
            ->orderBy('count', 'desc')
            ->get();

        return view('admin.dashboard', compact('stats', 'recent_inscriptions', 'events_by_status', 'inscriptions_by_city'));
    }
}
