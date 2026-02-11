@extends('layouts.app')

@section('title', 'Notificações - Painel Admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Histórico de Notificações</h1>
        </div>

        {{-- Contadores --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow p-4 text-center">
                <p class="text-2xl font-bold text-gray-900">{{ $counts['total'] }}</p>
                <p class="text-sm text-gray-500">Total</p>
            </div>
            <div class="bg-green-50 rounded-xl shadow p-4 text-center border border-green-200">
                <p class="text-2xl font-bold text-green-700">{{ $counts['sent'] }}</p>
                <p class="text-sm text-green-600">Enviadas</p>
            </div>
            <div class="bg-red-50 rounded-xl shadow p-4 text-center border border-red-200">
                <p class="text-2xl font-bold text-red-700">{{ $counts['failed'] }}</p>
                <p class="text-sm text-red-600">Falharam</p>
            </div>
            <div class="bg-gray-50 rounded-xl shadow p-4 text-center border border-gray-200">
                <p class="text-2xl font-bold text-gray-700">{{ $counts['skipped'] }}</p>
                <p class="text-sm text-gray-600">Ignoradas</p>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded-xl shadow p-4 mb-6">
            <form method="GET" action="{{ route('admin.notificacoes.index') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Buscar por destinatário, assunto ou mensagem..."
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>
                <div class="sm:w-36">
                    <select name="type" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">Todos os canais</option>
                        <option value="email" {{ request('type') === 'email' ? 'selected' : '' }}>Email</option>
                        <option value="whatsapp" {{ request('type') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                    </select>
                </div>
                <div class="sm:w-36">
                    <select name="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">Todos os status</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Enviada</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Falhou</option>
                        <option value="skipped" {{ request('status') === 'skipped' ? 'selected' : '' }}>Ignorada</option>
                    </select>
                </div>
                <div class="sm:w-44">
                    <select name="channel" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">Todos os tipos</option>
                        <option value="inscription_received" {{ request('channel') === 'inscription_received' ? 'selected' : '' }}>Inscrição Recebida</option>
                        <option value="inscription_approved" {{ request('channel') === 'inscription_approved' ? 'selected' : '' }}>Inscrição Aprovada</option>
                        <option value="inscription_waitlisted" {{ request('channel') === 'inscription_waitlisted' ? 'selected' : '' }}>Fila de Espera</option>
                        <option value="inscription_confirmed" {{ request('channel') === 'inscription_confirmed' ? 'selected' : '' }}>Confirmação</option>
                        <option value="inscription_rejected" {{ request('channel') === 'inscription_rejected' ? 'selected' : '' }}>Rejeição</option>
                        <option value="inscription_cancelled" {{ request('channel') === 'inscription_cancelled' ? 'selected' : '' }}>Cancelamento</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
                    Filtrar
                </button>
                @if(request()->hasAny(['search', 'type', 'status', 'channel']))
                    <a href="{{ route('admin.notificacoes.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium text-center">
                        Limpar
                    </a>
                @endif
            </form>
        </div>

        {{-- Lista --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            @if($notifications->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Canal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tipo</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Destinatário</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Assunto</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Data</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($notifications as $notification)
                                <tr class="hover:bg-gray-50" x-data="{ showDetails: false }">
                                    <td class="px-4 py-3">
                                        @if($notification->type === 'email')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Email
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                WhatsApp
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        @php
                                            $channelLabels = [
                                                'inscription_received' => 'Inscrição Recebida',
                                                'inscription_approved' => 'Aprovação',
                                                'inscription_waitlisted' => 'Fila de Espera',
                                                'inscription_confirmed' => 'Confirmação',
                                                'inscription_rejected' => 'Rejeição',
                                                'inscription_cancelled' => 'Cancelamento',
                                                'general' => 'Geral',
                                            ];
                                        @endphp
                                        {{ $channelLabels[$notification->channel] ?? $notification->channel }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $notification->recipient }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 max-w-xs truncate">
                                        {{ $notification->subject ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($notification->status === 'sent')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Enviada
                                            </span>
                                        @elseif($notification->status === 'failed')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800" title="{{ $notification->error_message }}">
                                                Falhou
                                            </span>
                                        @elseif($notification->status === 'skipped')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800" title="{{ $notification->error_message }}">
                                                Ignorada
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        {{ $notification->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            @if($notification->status === 'failed' || $notification->status === 'skipped')
                                                <form method="POST" action="{{ route('admin.notificacoes.resend', $notification) }}" class="inline"
                                                      x-on:submit.prevent="if(confirm('Reenviar esta notificação?')) $el.submit()">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700" title="Reenviar">
                                                        Reenviar
                                                    </button>
                                                </form>
                                            @endif
                                            <button @click="showDetails = !showDetails" class="px-3 py-1 border border-gray-300 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50" title="Ver detalhes">
                                                Detalhes
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                {{-- Detalhes expandíveis --}}
                                <tr x-show="showDetails" x-transition>
                                    <td colspan="7" class="px-4 py-3 bg-gray-50">
                                        <div class="space-y-2">
                                            <div>
                                                <p class="text-xs font-semibold text-gray-500 uppercase">Mensagem:</p>
                                                <p class="text-sm text-gray-700 whitespace-pre-line mt-1">{{ Str::limit($notification->message, 500) }}</p>
                                            </div>
                                            @if($notification->error_message)
                                                <div>
                                                    <p class="text-xs font-semibold text-red-500 uppercase">Erro:</p>
                                                    <p class="text-sm text-red-700 mt-1">{{ $notification->error_message }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t">
                    {{ $notifications->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <p class="mt-4 text-gray-500">Nenhuma notificação encontrada.</p>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
