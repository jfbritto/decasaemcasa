@extends('layouts.app')

@section('title', 'Detalhes da Inscrição - Painel Admin')

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <a href="{{ route('admin.inscricoes.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center mb-2">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Voltar para lista
                </a>
                <h1 class="text-2xl font-bold text-gray-900">{{ $inscription->full_name }}</h1>
            </div>
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold
                @if($inscription->isPending()) bg-yellow-100 text-yellow-800
                @elseif($inscription->isApproved()) bg-blue-100 text-blue-800
                @elseif($inscription->isConfirmed()) bg-green-100 text-green-800
                @elseif($inscription->isWaitlisted()) bg-orange-100 text-orange-800
                @endif">
                {{ $inscription->status_label }}
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Dados do Participante --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Informações Pessoais --}}
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Dados do Participante</h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nome Completo</dt>
                            <dd class="text-sm text-gray-900 mt-1">{{ $inscription->full_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">CPF</dt>
                            <dd class="text-sm text-gray-900 mt-1">{{ $inscription->formatted_cpf }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Data de Nascimento</dt>
                            <dd class="text-sm text-gray-900 mt-1">{{ $inscription->birth_date->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Bairro / Cidade</dt>
                            <dd class="text-sm text-gray-900 mt-1">{{ $inscription->city_neighborhood }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">WhatsApp</dt>
                            <dd class="text-sm text-gray-900 mt-1">{{ $inscription->whatsapp }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">E-mail</dt>
                            <dd class="text-sm text-gray-900 mt-1">{{ $inscription->email }}</dd>
                        </div>
                        @if($inscription->instagram)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Instagram</dt>
                            <dd class="text-sm text-gray-900 mt-1">{{ $inscription->instagram }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Inscrito em</dt>
                            <dd class="text-sm text-gray-900 mt-1">{{ $inscription->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Motivação / História --}}
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">História / Motivação</h2>
                    <div class="bg-amber-50 rounded-xl p-5 border-l-4 border-amber-400">
                        <p class="text-gray-700 leading-relaxed italic whitespace-pre-line">"{{ $inscription->motivation }}"</p>
                    </div>
                </div>

                {{-- Comprovante de Pagamento --}}
                @if($inscription->payment_proof)
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Comprovante de Pagamento</h2>
                    <div class="border rounded-xl overflow-hidden">
                        @php
                            $extension = pathinfo($inscription->payment_proof, PATHINFO_EXTENSION);
                        @endphp
                        @if(in_array(strtolower($extension), ['jpg', 'jpeg', 'png']))
                            <img src="{{ Storage::url($inscription->payment_proof) }}" alt="Comprovante" class="w-full max-h-96 object-contain bg-gray-100">
                        @elseif(strtolower($extension) === 'pdf')
                            <div class="p-4 bg-gray-50 text-center">
                                <svg class="mx-auto h-12 w-12 text-red-500 mb-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                </svg>
                                <a href="{{ Storage::url($inscription->payment_proof) }}" target="_blank"
                                   class="text-indigo-600 hover:text-indigo-800 font-medium">
                                    Visualizar PDF do Comprovante
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            {{-- Sidebar: Evento + Ações --}}
            <div class="space-y-6">

                {{-- Evento --}}
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Encontro</h2>
                    <div class="space-y-2">
                        <p class="font-medium text-gray-900">{{ $inscription->event->city ?? $inscription->event->title }}</p>
                        <p class="text-sm text-gray-600">{{ $inscription->event->date->format('d/m/Y') }}</p>
                        @if($inscription->event->title && $inscription->event->city && $inscription->event->title !== $inscription->event->city)
                            <p class="text-sm text-gray-500">{{ $inscription->event->title }}</p>
                        @endif
                        @if($inscription->event->capacity > 0)
                            <p class="text-sm text-gray-500">
                                Capacidade: {{ $inscription->event->confirmed_count }}/{{ $inscription->event->capacity }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Ações --}}
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Ações</h2>
                    <div class="space-y-3">
                        @if($inscription->isPending())
                            <form method="POST" action="{{ route('admin.inscricoes.aprovar', $inscription) }}">
                                @csrf
                                <button type="submit" class="w-full py-2.5 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 transition-colors">
                                    Aprovar Inscrição
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.inscricoes.fila-espera', $inscription) }}">
                                @csrf
                                <button type="submit" class="w-full py-2.5 bg-orange-500 text-white font-medium rounded-xl hover:bg-orange-600 transition-colors">
                                    Mover para Fila de Espera
                                </button>
                            </form>
                        @elseif($inscription->isApproved())
                            @if($inscription->payment_proof)
                                <form method="POST" action="{{ route('admin.inscricoes.confirmar', $inscription) }}">
                                    @csrf
                                    <button type="submit" class="w-full py-2.5 bg-green-600 text-white font-medium rounded-xl hover:bg-green-700 transition-colors">
                                        Confirmar Pagamento
                                    </button>
                                </form>
                            @else
                                <div class="text-center py-3 bg-yellow-50 rounded-xl border border-yellow-200">
                                    <p class="text-sm text-yellow-700">Aguardando envio do comprovante pelo participante.</p>
                                </div>
                            @endif
                        @elseif($inscription->isWaitlisted())
                            <form method="POST" action="{{ route('admin.inscricoes.aprovar', $inscription) }}">
                                @csrf
                                <button type="submit" class="w-full py-2.5 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 transition-colors">
                                    Aprovar da Fila de Espera
                                </button>
                            </form>
                        @elseif($inscription->isConfirmed())
                            <div class="text-center py-3 bg-green-50 rounded-xl border border-green-200">
                                <p class="text-sm text-green-700 font-medium">Participação confirmada!</p>
                                @if($inscription->confirmed_at)
                                    <p class="text-xs text-green-600 mt-1">Confirmado em {{ $inscription->confirmed_at->format('d/m/Y H:i') }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Notas do Admin --}}
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Notas Internas</h2>
                    <form method="POST" action="{{ route('admin.inscricoes.update-notes', $inscription) }}">
                        @csrf
                        @method('PATCH')
                        <textarea name="admin_notes" rows="4"
                                  class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                  placeholder="Adicione notas internas sobre esta inscrição...">{{ $inscription->admin_notes }}</textarea>
                        <button type="submit" class="mt-2 w-full py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                            Salvar Notas
                        </button>
                    </form>
                </div>

                {{-- Link da inscrição --}}
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-sm font-semibold text-gray-900 mb-2">Link de Status</h2>
                    <input type="text" value="{{ route('inscricao.status', $inscription->token) }}" readonly
                           class="w-full text-xs text-gray-600 bg-gray-50 border-gray-200 rounded-lg" onclick="this.select()">
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
