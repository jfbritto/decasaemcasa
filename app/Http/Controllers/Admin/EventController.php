<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index()
    {
        $upcoming = Event::withCount(['inscriptions'])
            ->where('date', '>=', now())
            ->orderBy('date', 'asc')
            ->get();

        $past = Event::withCount(['inscriptions'])
            ->where('date', '<', now())
            ->orderBy('date', 'desc')
            ->get();

        return view('admin.events.index', compact('upcoming', 'past'));
    }

    public function create()
    {
        return view('admin.events.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'date' => 'required|date|after:now',
            'city' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'arrival_time' => 'nullable|string|max:50',
            'capacity' => 'required|integer|min:1|max:10000',
            'status' => 'required|in:draft,published,cancelled',
        ]);

        $validated['slug'] = Str::slug($validated['title'].'-'.Str::random(4));

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('events', 'public');
        }

        $event = Event::create($validated);

        $eventName = $event->city ?? $event->title;
        ActivityLog::log('criar_evento', "Criou o encontro {$eventName}", $event);

        return redirect()->route('admin.events.index')
            ->with('success', 'Encontro criado com sucesso!');
    }

    public function show(Event $event)
    {
        $inscriptionStats = [
            'total' => $event->inscriptions()->count(),
            'pendente' => $event->inscriptions()->where('status', 'pendente')->count(),
            'aprovado' => $event->inscriptions()->where('status', 'aprovado')->count(),
            'confirmado' => $event->inscriptions()->where('status', 'confirmado')->count(),
            'fila_de_espera' => $event->inscriptions()->where('status', 'fila_de_espera')->count(),
            'rejeitado' => $event->inscriptions()->where('status', 'rejeitado')->count(),
            'cancelado' => $event->inscriptions()->where('status', 'cancelado')->count(),
        ];

        $inscriptions = $event->inscriptions()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.events.show', compact('event', 'inscriptionStats', 'inscriptions'));
    }

    public function edit(Event $event)
    {
        return view('admin.events.edit', compact('event'));
    }

    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'date' => 'required|date',
            'city' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'arrival_time' => 'nullable|string|max:50',
            'capacity' => 'required|integer|min:1|max:10000',
            'status' => 'required|in:draft,published,cancelled,finished',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('events', 'public');
        }

        $event->update($validated);

        $eventName = $event->city ?? $event->title;
        ActivityLog::log('atualizar_evento', "Atualizou o encontro {$eventName}", $event);

        return redirect()->route('admin.events.index')
            ->with('success', 'Encontro atualizado com sucesso!');
    }

    public function destroy(Event $event)
    {
        $eventName = $event->city ?? $event->title;
        ActivityLog::log('excluir_evento', "Excluiu o encontro {$eventName}", $event);

        $event->delete();

        return redirect()->route('admin.events.index')
            ->with('success', 'Encontro excluído com sucesso!');
    }

    public function duplicate(Event $event)
    {
        $newEvent = $event->replicate(['slug', 'confirmed_count', 'deleted_at']);
        $newEvent->slug = Str::slug($event->title.'-'.Str::random(4));
        $newEvent->title = $event->title.' (Cópia)';
        $newEvent->confirmed_count = 0;
        $newEvent->status = 'draft';
        $newEvent->date = now()->addMonth();
        $newEvent->save();

        return redirect()->route('admin.events.edit', $newEvent)
            ->with('success', 'Encontro duplicado com sucesso! Edite os dados conforme necessário.');
    }

    public function exportPdf(Event $event)
    {
        $event->load(['inscriptions' => function ($query) {
            $query->orderByRaw("FIELD(status, 'confirmado', 'aprovado', 'fila_de_espera', 'pendente')")
                ->orderBy('full_name');
        }]);

        $pdf = Pdf::loadView('admin.events.participantes-pdf', compact('event'))
            ->setPaper('a4', 'landscape');

        $filename = 'participantes-'.Str::slug($event->city ?? $event->title).'-'.now()->format('Y-m-d').'.pdf';

        return $pdf->stream($filename);
    }
}
