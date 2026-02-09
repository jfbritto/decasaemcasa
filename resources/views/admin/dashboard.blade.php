@extends('layouts.app')

@section('title', 'Dashboard - De Casa em Casa')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Dashboard - De Casa em Casa</h1>

        {{-- Estatísticas --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow p-5">
                <p class="text-sm text-gray-500">Encontros Ativos</p>
                <p class="text-3xl font-bold text-indigo-600">{{ $stats['active_events'] }}</p>
                <p class="text-xs text-gray-400 mt-1">de {{ $stats['total_events'] }} total</p>
            </div>
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl shadow p-5">
                <p class="text-sm text-yellow-600">Pendentes</p>
                <p class="text-3xl font-bold text-yellow-700">{{ $stats['pending_inscriptions'] }}</p>
                <p class="text-xs text-yellow-500 mt-1">aguardando curadoria</p>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-xl shadow p-5">
                <p class="text-sm text-blue-600">Aprovados</p>
                <p class="text-3xl font-bold text-blue-700">{{ $stats['approved_inscriptions'] }}</p>
                <p class="text-xs text-blue-500 mt-1">aguardando pagamento</p>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-xl shadow p-5">
                <p class="text-sm text-green-600">Confirmados</p>
                <p class="text-3xl font-bold text-green-700">{{ $stats['confirmed_inscriptions'] }}</p>
                <p class="text-xs text-green-500 mt-1">participações garantidas</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Inscrições recentes --}}
            <div class="bg-white rounded-xl shadow">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Inscrições Recentes</h2>
                    <a href="{{ route('admin.inscricoes.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Ver todas</a>
                </div>
                <div class="divide-y">
                    @forelse($recent_inscriptions as $inscription)
                        <div class="px-6 py-3 flex items-center justify-between">
                            <div>
                                <a href="{{ route('admin.inscricoes.show', $inscription) }}" class="font-medium text-gray-900 hover:text-indigo-600">
                                    {{ $inscription->full_name }}
                                </a>
                                <p class="text-xs text-gray-500">
                                    {{ $inscription->event->city ?? $inscription->event->title }} - {{ $inscription->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                @if($inscription->isPending()) bg-yellow-100 text-yellow-800
                                @elseif($inscription->isApproved()) bg-blue-100 text-blue-800
                                @elseif($inscription->isConfirmed()) bg-green-100 text-green-800
                                @elseif($inscription->isWaitlisted()) bg-orange-100 text-orange-800
                                @endif">
                                {{ $inscription->status_label }}
                            </span>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-gray-500">Nenhuma inscrição ainda.</div>
                    @endforelse
                </div>
            </div>

            {{-- Inscrições por cidade --}}
            <div class="bg-white rounded-xl shadow">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">Inscrições por Cidade</h2>
                </div>
                <div class="divide-y">
                    @forelse($inscriptions_by_city as $item)
                        <div class="px-6 py-3 flex items-center justify-between">
                            <span class="font-medium text-gray-900">{{ $item->city }}</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-indigo-100 text-indigo-800">
                                {{ $item->count }}
                            </span>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-gray-500">Nenhum dado disponível.</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
