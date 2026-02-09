@extends('layouts.app')

@section('title', $event->title)

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
    <div class="px-4 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">{{ $event->city ?? $event->title }}</h1>
            <div class="flex gap-2">
                <a href="{{ route('admin.events.edit', $event) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                    Editar
                </a>
                <a href="{{ route('admin.events.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                    Voltar
                </a>
            </div>
        </div>

        {{-- Imagem de capa do evento --}}
        @if($event->image)
            <div class="rounded-lg overflow-hidden shadow-md mb-6">
                <img src="{{ asset('storage/' . $event->image) }}"
                     alt="{{ $event->title }}"
                     class="w-full h-56 object-cover">
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Informações do Encontro</h2>
                <div class="space-y-3">
                    @if($event->title && $event->city && $event->title !== $event->city)
                    <div>
                        <p class="text-sm text-gray-500">Título</p>
                        <p class="text-gray-900">{{ $event->title }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-sm text-gray-500">Cidade</p>
                        <p class="text-gray-900">{{ $event->city ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Data</p>
                        <p class="text-gray-900">{{ $event->date->format('d/m/Y H:i') }}</p>
                    </div>
                    @if($event->arrival_time)
                    <div>
                        <p class="text-sm text-gray-500">Horário de Chegada</p>
                        <p class="text-gray-900">{{ $event->arrival_time }}</p>
                    </div>
                    @endif
                    @if($event->full_address)
                    <div>
                        <p class="text-sm text-gray-500">Endereço (SECRETO)</p>
                        <p class="text-gray-900 bg-red-50 border border-red-200 rounded p-2 text-sm">{{ $event->full_address }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-sm text-gray-500">Descrição</p>
                        <p class="text-gray-900">{{ $event->description ?? 'Sem descrição' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <span class="px-2 py-1 rounded text-xs
                            @if($event->status === 'published') bg-green-100 text-green-800
                            @elseif($event->status === 'draft') bg-gray-100 text-gray-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($event->status) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Estatísticas de Inscrições</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500">Capacidade</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $event->capacity ?: 'Ilimitada' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total de Inscrições</p>
                        <p class="text-2xl font-bold text-indigo-600">{{ $inscriptionStats['total'] }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-yellow-50 rounded-lg p-3 text-center">
                            <p class="text-xl font-bold text-yellow-700">{{ $inscriptionStats['pendente'] }}</p>
                            <p class="text-xs text-yellow-600">Pendentes</p>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-3 text-center">
                            <p class="text-xl font-bold text-blue-700">{{ $inscriptionStats['aprovado'] }}</p>
                            <p class="text-xs text-blue-600">Aprovados</p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-3 text-center">
                            <p class="text-xl font-bold text-green-700">{{ $inscriptionStats['confirmado'] }}</p>
                            <p class="text-xs text-green-600">Confirmados</p>
                        </div>
                        <div class="bg-orange-50 rounded-lg p-3 text-center">
                            <p class="text-xl font-bold text-orange-700">{{ $inscriptionStats['fila_de_espera'] }}</p>
                            <p class="text-xs text-orange-600">Fila de Espera</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Lista de Inscrições do evento --}}
        <div class="mt-6 bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold">Inscrições deste Encontro</h2>
                <a href="{{ route('admin.inscricoes.index', ['city' => $event->city]) }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                    Ver todas com filtro
                </a>
            </div>
            @if($event->inscriptions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Nome</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">WhatsApp</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($event->inscriptions->sortByDesc('created_at') as $inscription)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ route('admin.inscricoes.show', $inscription) }}" class="font-medium text-indigo-600 hover:text-indigo-800">
                                            {{ $inscription->full_name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $inscription->whatsapp }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 text-xs rounded-full font-medium
                                            @if($inscription->isPending()) bg-yellow-100 text-yellow-800
                                            @elseif($inscription->isApproved()) bg-blue-100 text-blue-800
                                            @elseif($inscription->isConfirmed()) bg-green-100 text-green-800
                                            @elseif($inscription->isWaitlisted()) bg-orange-100 text-orange-800
                                            @endif">
                                            {{ $inscription->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ route('admin.inscricoes.show', $inscription) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500">Nenhuma inscrição para este encontro.</p>
            @endif
        </div>
    </div>
</div>
@endsection
