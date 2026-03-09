@extends('layouts.app')

@section('title', 'Gerenciar Encontros')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
    <div class="px-4 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mt-6">Encontros</h1>
            <a href="{{ route('admin.events.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                Novo Encontro
            </a>
        </div>

        {{-- Total Geral Arrecadado --}}
        <div class="mb-8 bg-white border border-green-200 rounded-xl p-5 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-sm text-gray-500 font-medium uppercase tracking-wide">Total Geral Arrecadado</p>
                <p class="text-xs text-gray-400 mt-0.5">Soma de todas as contribuições confirmadas</p>
            </div>
            <p class="text-2xl font-bold text-green-700">
                R$ {{ number_format($totalArrecadadoGeral, 2, ',', '.') }}
            </p>
        </div>

        {{-- Próximos Encontros --}}
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                Próximos Encontros
                <span class="ml-2 text-sm font-normal text-gray-500">({{ $upcoming->count() }})</span>
            </h2>

            @if($upcoming->count() > 0)
                <div class="bg-white shadow-lg border border-gray-200 overflow-x-auto sm:rounded-md">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-100 border-b border-gray-300">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider w-16"></th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Cidade</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Capacidade</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Inscrições</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Total Arrecadado</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-300">
                            @foreach($upcoming as $event)
                                <tr class="hover:bg-gray-50 transition-colors cursor-pointer"
                                    onclick="window.location='{{ route('admin.events.show', $event) }}'">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                            </svg>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900">{{ $event->city ?? $event->title }}</div>
                                        @if($event->title && $event->city && $event->title !== $event->city)
                                            <div class="text-xs text-gray-500">{{ $event->title }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        {{ $event->date->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <span class="{{ $event->isFull() ? 'text-red-600 font-bold' : '' }}">
                                            {{ $event->confirmed_count }} / {{ $event->capacity ?: '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        {{ $event->inscriptions_count ?? 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        @if($event->total_arrecadado)
                                            <span class="font-medium text-green-700">R$ {{ number_format($event->total_arrecadado, 2, ',', '.') }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded
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
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="bg-white shadow border border-gray-200 sm:rounded-md p-8 text-center text-gray-500">
                    Nenhum encontro próximo agendado.
                </div>
            @endif
        </div>

        {{-- Encontros Realizados --}}
        @if($past->count() > 0)
        <div x-data="{ open: false }">
            <button @click="open = !open" class="w-full flex items-center justify-between text-left mb-3 group">
                <h2 class="text-lg font-semibold text-gray-500 flex items-center">
                    <span class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span>
                    Encontros Realizados
                    <span class="ml-2 text-sm font-normal">({{ $past->count() }})</span>
                </h2>
                <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" x-collapse>
                <div class="bg-white shadow-lg border border-gray-200 overflow-x-auto sm:rounded-md opacity-80">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-100 border-b border-gray-300">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider w-16"></th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Cidade</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Capacidade</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Inscrições</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Total Arrecadado</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-300">
                            @foreach($past as $event)
                                <tr class="hover:bg-gray-50 transition-colors cursor-pointer"
                                    onclick="window.location='{{ route('admin.events.show', $event) }}'">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                            </svg>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-600">{{ $event->city ?? $event->title }}</div>
                                        @if($event->title && $event->city && $event->title !== $event->city)
                                            <div class="text-xs text-gray-400">{{ $event->title }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $event->date->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $event->confirmed_count }} / {{ $event->capacity ?: '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $event->inscriptions_count ?? 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($event->total_arrecadado)
                                            <span class="font-medium text-green-600">R$ {{ number_format($event->total_arrecadado, 2, ',', '.') }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-600">
                                            Realizado
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
        {{-- Encontros Excluídos --}}
        @if($deleted->count() > 0)
        <div x-data="{ open: false }" class="mt-8">
            <button @click="open = !open" class="w-full flex items-center justify-between text-left mb-3 group">
                <h2 class="text-lg font-semibold text-red-400 flex items-center">
                    <span class="w-2 h-2 bg-red-400 rounded-full mr-2"></span>
                    Encontros Excluídos
                    <span class="ml-2 text-sm font-normal">({{ $deleted->count() }})</span>
                </h2>
                <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" x-collapse>
                <div class="bg-white shadow-lg border border-gray-200 overflow-x-auto sm:rounded-md opacity-60">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-100 border-b border-gray-300">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider w-16"></th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Cidade</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Inscrições</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Excluído em</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-300">
                            @foreach($deleted as $event)
                                <tr class="hover:bg-red-50 transition-colors cursor-pointer" onclick="window.location='{{ route('admin.events.show', $event) }}'">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-red-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-red-400 line-through hover:text-red-600">{{ $event->city ?? $event->title }}</div>
                                        @if($event->title && $event->city && $event->title !== $event->city)
                                            <div class="text-xs text-gray-300">{{ $event->title }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                        {{ $event->date->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                        {{ $event->inscriptions_count ?? 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                        {{ $event->deleted_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded bg-red-50 text-red-400">
                                            Excluído
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
