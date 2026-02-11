@extends('layouts.app')

@section('title', 'Dashboard - De Casa em Casa')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Dashboard</h1>

        {{-- Linha 1: Cards de ação rápida --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-8">
            <a href="{{ route('admin.inscricoes.index', ['status' => 'pendente']) }}" class="bg-yellow-50 border border-yellow-200 rounded-xl shadow p-4 sm:p-5 hover:shadow-md hover:border-yellow-300 transition-all group">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-sm text-yellow-600 font-medium">Pendentes</p>
                    <svg class="w-4 h-4 text-yellow-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
                <p class="text-3xl font-bold text-yellow-700">{{ $stats['pending'] }}</p>
                <p class="text-xs text-yellow-500 mt-1">aguardando curadoria</p>
            </a>

            <a href="{{ route('admin.inscricoes.index', ['status' => 'aprovado', 'comprovante' => 'pendente']) }}" class="bg-blue-50 border border-blue-200 rounded-xl shadow p-4 sm:p-5 hover:shadow-md hover:border-blue-300 transition-all group">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-sm text-blue-600 font-medium">Aguardando Pix</p>
                    <svg class="w-4 h-4 text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
                <p class="text-3xl font-bold text-blue-700">{{ $stats['awaiting_proof'] }}</p>
                <p class="text-xs text-blue-500 mt-1">sem comprovante</p>
            </a>

            <a href="{{ route('admin.inscricoes.index', ['status' => 'aprovado', 'comprovante' => 'enviado']) }}" class="bg-indigo-50 border border-indigo-200 rounded-xl shadow p-4 sm:p-5 hover:shadow-md hover:border-indigo-300 transition-all group">
                <div class="flex items-center justify-between mb-1">
                    <p class="text-sm text-indigo-600 font-medium">Comprovante Enviado</p>
                    <svg class="w-4 h-4 text-indigo-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
                <p class="text-3xl font-bold text-indigo-700">{{ $stats['proof_sent'] }}</p>
                <p class="text-xs text-indigo-500 mt-1">aguardando confirmação</p>
            </a>

            <div class="bg-green-50 border border-green-200 rounded-xl shadow p-4 sm:p-5">
                <p class="text-sm text-green-600 font-medium mb-1">Confirmados</p>
                <p class="text-3xl font-bold text-green-700">{{ $stats['confirmed'] }}</p>
                <p class="text-xs text-green-500 mt-1">participações garantidas</p>
            </div>
        </div>

        {{-- Linha 2: Próximo encontro + Panorama --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">

            {{-- Próximo Encontro (destaque) --}}
            <div class="lg:col-span-7">
                @if($next_event)
                    <div class="bg-white rounded-xl shadow overflow-hidden h-full">
                        @if($next_event->image)
                            <img src="{{ asset('storage/' . $next_event->image) }}" alt="{{ $next_event->title }}" class="w-full h-40 sm:h-48 object-cover">
                        @endif
                        <div class="p-5 sm:p-6">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Próximo</span>
                                <span class="text-xs text-gray-400">{{ $next_event->date->diffForHumans() }}</span>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 mb-1">{{ $next_event->city ?? $next_event->title }}</h2>
                            @if($next_event->title && $next_event->city && $next_event->title !== $next_event->city)
                                <p class="text-sm text-gray-500 mb-3">{{ $next_event->title }}</p>
                            @endif

                            <div class="grid grid-cols-3 gap-4 mb-4">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase tracking-wide">Data</p>
                                    <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $next_event->date->format('d/m/Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase tracking-wide">Horário</p>
                                    <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $next_event->arrival_time ?? $next_event->date->format('H:i') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase tracking-wide">Inscritos</p>
                                    <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $next_event->inscriptions_count }} / {{ $next_event->capacity ?: '∞' }}</p>
                                </div>
                            </div>

                            @if($next_event->capacity > 0)
                                <div class="mb-4">
                                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                                        <span>Ocupação</span>
                                        <span>{{ round(($next_event->confirmed_count / $next_event->capacity) * 100) }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-indigo-600 h-2 rounded-full transition-all" style="width: {{ min(100, round(($next_event->confirmed_count / $next_event->capacity) * 100)) }}%"></div>
                                    </div>
                                </div>
                            @endif

                            <a href="{{ route('admin.events.show', $next_event) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                                Ver encontro
                                <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="bg-white rounded-xl shadow p-8 text-center h-full flex flex-col items-center justify-center">
                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-gray-500 font-medium">Nenhum encontro agendado</p>
                        <a href="{{ route('admin.events.create') }}" class="mt-3 text-sm text-indigo-600 hover:text-indigo-800">Criar novo encontro</a>
                    </div>
                @endif
            </div>

            {{-- Panorama dos Encontros --}}
            <div class="lg:col-span-5 space-y-4">
                <div class="bg-white rounded-xl shadow">
                    <div class="px-5 py-4 border-b">
                        <h2 class="text-base font-semibold text-gray-900">Encontros Ativos</h2>
                    </div>
                    @if($active_events->count() > 0)
                        <div class="divide-y">
                            @foreach($active_events as $event)
                                <a href="{{ route('admin.events.show', $event) }}" class="px-5 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors group">
                                    <div>
                                        <span class="text-sm font-medium text-gray-900 group-hover:text-gray-700">{{ $event->city ?? $event->title }}</span>
                                        <p class="text-xs text-gray-500">{{ $event->date->format('d/m/Y') }}</p>
                                    </div>
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $event->isFull() ? 'bg-red-100 text-red-700' : 'bg-indigo-100 text-indigo-700' }}">
                                        {{ $event->inscriptions_count }} / {{ $event->capacity ?: '∞' }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="px-5 py-6 text-center text-gray-500 text-sm">Nenhum encontro ativo no momento.</div>
                    @endif
                </div>

                {{-- Resumo de encontros --}}
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-white rounded-xl shadow p-3 text-center">
                        <p class="text-xl font-bold text-indigo-600">{{ $events_counts['active'] }}</p>
                        <p class="text-[10px] sm:text-xs text-gray-500">Ativos</p>
                    </div>
                    <div class="bg-white rounded-xl shadow p-3 text-center">
                        <p class="text-xl font-bold text-gray-500">{{ $events_counts['past'] }}</p>
                        <p class="text-[10px] sm:text-xs text-gray-500">Realizados</p>
                    </div>
                    <div class="bg-white rounded-xl shadow p-3 text-center">
                        <p class="text-xl font-bold text-gray-900">{{ $events_counts['total'] }}</p>
                        <p class="text-[10px] sm:text-xs text-gray-500">Total</p>
                    </div>
                </div>
            </div>

        </div>

        {{-- Linha 3: Inscrições Recentes + Por Cidade --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

            {{-- Inscrições recentes --}}
            <div class="bg-white rounded-xl shadow">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Inscrições Recentes</h2>
                    <a href="{{ route('admin.inscricoes.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Ver todas</a>
                </div>
                <div class="divide-y">
                    @forelse($recent_inscriptions as $inscription)
                        <a href="{{ route('admin.inscricoes.show', $inscription) }}" class="px-6 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors group">
                            <div>
                                <span class="font-medium text-gray-900 group-hover:text-gray-700 text-sm">
                                    {{ $inscription->full_name }}
                                </span>
                                <p class="text-xs text-gray-500">
                                    {{ $inscription->event->city ?? $inscription->event->title }} · {{ $inscription->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0
                                @if($inscription->isPending()) bg-yellow-100 text-yellow-800
                                @elseif($inscription->isApproved()) bg-blue-100 text-blue-800
                                @elseif($inscription->isConfirmed()) bg-green-100 text-green-800
                                @elseif($inscription->isWaitlisted()) bg-orange-100 text-orange-800
                                @endif">
                                {{ $inscription->status_label }}
                            </span>
                        </a>
                    @empty
                        <div class="px-6 py-8 text-center text-gray-500 text-sm">Nenhuma inscrição ainda.</div>
                    @endforelse
                </div>
            </div>

            {{-- Inscrições por encontro --}}
            <div class="bg-white rounded-xl shadow">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-base font-semibold text-gray-900">Inscrições por Encontro</h2>
                </div>
                <div class="divide-y">
                    @forelse($inscriptions_by_city as $item)
                        <a href="{{ route('admin.inscricoes.index', ['event_id' => $item->event_id]) }}" class="px-6 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors group">
                            <span class="font-medium text-gray-900 group-hover:text-gray-700 text-sm">{{ $item->label }}</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-indigo-100 text-indigo-800">
                                {{ $item->count }}
                            </span>
                        </a>
                    @empty
                        <div class="px-6 py-8 text-center text-gray-500 text-sm">Nenhum dado disponível.</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
