<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::withCount(['inscriptions'])
            ->orderBy('date', 'asc')
            ->paginate(15);

        return view('admin.events.index', compact('events'));
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

        Event::create($validated);

        return redirect()->route('admin.events.index')
            ->with('success', 'Encontro criado com sucesso!');
    }

    public function show(Event $event)
    {
        $event->load('inscriptions');

        $inscriptionStats = [
            'total' => $event->inscriptions()->count(),
            'pendente' => $event->inscriptions()->where('status', 'pendente')->count(),
            'aprovado' => $event->inscriptions()->where('status', 'aprovado')->count(),
            'confirmado' => $event->inscriptions()->where('status', 'confirmado')->count(),
            'fila_de_espera' => $event->inscriptions()->where('status', 'fila_de_espera')->count(),
        ];

        return view('admin.events.show', compact('event', 'inscriptionStats'));
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

        return redirect()->route('admin.events.index')
            ->with('success', 'Encontro atualizado com sucesso!');
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return redirect()->route('admin.events.index')
            ->with('success', 'Encontro excluído com sucesso!');
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
