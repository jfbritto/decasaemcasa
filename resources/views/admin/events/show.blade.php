@extends('layouts.app')

@section('title', $event->title)

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
    <div class="px-4 sm:px-0">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $event->city ?? $event->title }}</h1>
            <div class="flex flex-wrap gap-2">
                <a target="_blank" href="{{ route('admin.events.participantes-pdf', $event) }}" style="background-color:#dc2626;color:#fff;" class="px-3 py-2 text-sm sm:text-base sm:px-4 rounded-md hover:opacity-80 text-center">
                    Lista de Presença
                </a>
                <a href="{{ route('admin.events.edit', $event) }}" class="bg-indigo-600 text-white px-3 py-2 text-sm sm:text-base sm:px-4 rounded-md hover:bg-indigo-700 text-center">
                    Editar
                </a>
                <form method="POST" action="{{ route('admin.events.duplicate', $event) }}" class="inline">
                    @csrf
                    <button type="submit" style="background-color:#f59e0b;color:#fff;" class="px-3 py-2 text-sm sm:text-base sm:px-4 rounded-md hover:opacity-80 text-center">
                        Duplicar
                    </button>
                </form>
                <a href="{{ route('admin.events.index') }}" class="bg-gray-600 text-white px-3 py-2 text-sm sm:text-base sm:px-4 rounded-md hover:bg-gray-700 text-center">
                    Voltar
                </a>
            </div>
        </div>

        {{-- Grid: Info (esquerda) + Stats (direita) --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">

            {{-- Coluna esquerda: Informações do Encontro --}}
            <div class="lg:col-span-7">
                <div class="bg-white rounded-lg shadow-md overflow-hidden h-full">
                    {{-- Banner dentro do card --}}
                    @if($event->image)
                        <img src="{{ asset('storage/' . $event->image) }}"
                             alt="{{ $event->title }}"
                             class="w-full h-48 sm:h-56 object-cover">
                    @endif

                    <div class="p-4 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg sm:text-xl font-semibold">Informações do Encontro</h2>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                @if($event->status === 'published') bg-green-100 text-green-800
                                @elseif($event->status === 'draft') bg-gray-100 text-gray-800
                                @else bg-red-100 text-red-800
                                @endif">
                                @if($event->status === 'published') Publicado
                                @elseif($event->status === 'draft') Rascunho
                                @elseif($event->status === 'cancelled') Cancelado
                                @elseif($event->status === 'finished') Finalizado
                                @else {{ ucfirst($event->status) }}
                                @endif
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            @if($event->title && $event->city && $event->title !== $event->city)
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Título</p>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $event->title }}</p>
                            </div>
                            @endif
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Cidade</p>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $event->city ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Data</p>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $event->date->format('d/m/Y H:i') }}</p>
                            </div>
                            @if($event->arrival_time)
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Horário de Chegada</p>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $event->arrival_time }}</p>
                            </div>
                            @endif
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Capacidade</p>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ $event->capacity ?: 'Ilimitada' }}</p>
                            </div>
                        </div>

                        @if($event->full_address)
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Endereço (SECRETO)</p>
                            <p class="text-sm text-gray-900 bg-red-50 border border-red-200 rounded p-2">{{ $event->full_address }}</p>
                        </div>
                        @endif

                        @if($event->description)
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Descrição</p>
                            <p class="text-sm text-gray-700 leading-relaxed">{{ $event->description }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Coluna direita: Estatísticas --}}
            <div class="lg:col-span-5 space-y-4">
                {{-- Card destaque: Total --}}
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg shadow-md p-6 text-center">
                    <p class="text-xs text-indigo-500 uppercase tracking-wide font-semibold mb-1">Total de Inscritos</p>
                    <p class="text-5xl font-bold text-indigo-600">{{ $inscriptionStats['total'] }}</p>
                    @if($event->capacity > 0)
                        <p class="text-sm text-indigo-400 mt-2">de {{ $event->capacity }} vagas</p>
                        <div class="mt-3 w-full bg-indigo-200 rounded-full h-2">
                            <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min(100, round(($inscriptionStats['total'] / $event->capacity) * 100)) }}%"></div>
                        </div>
                    @endif
                </div>

                {{-- Mini-cards 3x2 --}}
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-white rounded-lg shadow-md p-4 text-center" style="border-top:4px solid #eab308;">
                        <p class="text-2xl font-bold" style="color:#a16207;">{{ $inscriptionStats['pendente'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Pendentes</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-4 text-center" style="border-top:4px solid #3b82f6;">
                        <p class="text-2xl font-bold" style="color:#1d4ed8;">{{ $inscriptionStats['aprovado'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Aprovados</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-4 text-center" style="border-top:4px solid #22c55e;">
                        <p class="text-2xl font-bold" style="color:#15803d;">{{ $inscriptionStats['confirmado'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Confirmados</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-4 text-center" style="border-top:4px solid #f97316;">
                        <p class="text-2xl font-bold" style="color:#c2410c;">{{ $inscriptionStats['fila_de_espera'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Fila de Espera</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-4 text-center" style="border-top:4px solid #ef4444;">
                        <p class="text-2xl font-bold" style="color:#b91c1c;">{{ $inscriptionStats['rejeitado'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Rejeitados</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-4 text-center" style="border-top:4px solid #9ca3af;">
                        <p class="text-2xl font-bold" style="color:#374151;">{{ $inscriptionStats['cancelado'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Cancelados</p>
                    </div>
                </div>
            </div>

        </div>

        {{-- Lista de Inscrições (full width) --}}
        <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                <h2 class="text-lg sm:text-xl font-semibold">Inscrições deste Encontro</h2>
                <a href="{{ route('admin.inscricoes.index', ['event_id' => $event->id]) }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                    Ver todas com filtro →
                </a>
            </div>
            @if($inscriptions->count() > 0)
                {{-- Tabela para desktop --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Nome</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">WhatsApp</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($inscriptions as $inscription)
                                <tr class="hover:bg-gray-50 cursor-pointer transition-colors"
                                    onclick="window.location='{{ route('admin.inscricoes.show', $inscription) }}'">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $inscription->full_name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $inscription->whatsapp }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 text-xs rounded-full font-medium
                                            @if($inscription->isPending()) bg-yellow-100 text-yellow-800
                                            @elseif($inscription->isApproved()) bg-blue-100 text-blue-800
                                            @elseif($inscription->isConfirmed()) bg-green-100 text-green-800
                                            @elseif($inscription->isWaitlisted()) bg-orange-100 text-orange-800
                                            @elseif($inscription->isRejected()) bg-red-100 text-red-800
                                            @elseif($inscription->isCancelled()) bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $inscription->status_label }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Cards para mobile --}}
                <div class="sm:hidden space-y-3">
                    @foreach($inscriptions as $inscription)
                        <a href="{{ route('admin.inscricoes.show', $inscription) }}" class="block bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition-colors">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-900">{{ $inscription->full_name }}</p>
                                <span class="px-2 py-0.5 text-xs rounded-full font-medium
                                    @if($inscription->isPending()) bg-yellow-100 text-yellow-800
                                    @elseif($inscription->isApproved()) bg-blue-100 text-blue-800
                                    @elseif($inscription->isConfirmed()) bg-green-100 text-green-800
                                    @elseif($inscription->isWaitlisted()) bg-orange-100 text-orange-800
                                    @elseif($inscription->isRejected()) bg-red-100 text-red-800
                                    @elseif($inscription->isCancelled()) bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $inscription->status_label }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ $inscription->whatsapp }}</p>
                        </a>
                    @endforeach
                </div>

                {{-- Paginação --}}
                <div class="mt-4 border-t pt-4">
                    {{ $inscriptions->links() }}
                </div>
            @else
                <p class="text-gray-500">Nenhuma inscrição para este encontro.</p>
            @endif
        </div>

    </div>
</div>
@endsection
