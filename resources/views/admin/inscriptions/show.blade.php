@extends('layouts.app')

@section('title', 'Detalhes da Inscrição - Painel Admin')

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                @php
                    $from = request('from');
                    if ($from === 'dashboard') {
                        $backUrl = route('admin.dashboard');
                        $backLabel = 'Voltar para dashboard';
                    } elseif ($from && str_starts_with($from, 'event:')) {
                        $eventId = (int) str_replace('event:', '', $from);
                        $backUrl = route('admin.eventos.show', $eventId);
                        $backLabel = 'Voltar para encontro';
                    } else {
                        $backUrl = route('admin.inscricoes.index');
                        $backLabel = 'Voltar para lista';
                    }
                @endphp
                <a href="{{ $backUrl }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center mb-2">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    {{ $backLabel }}
                </a>
                <h1 class="text-2xl font-bold text-gray-900">{{ $inscription->full_name }}</h1>
            </div>
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold
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
                            <dd class="text-sm mt-1">
                                @php
                                    $handle = ltrim(str_replace(['https://instagram.com/', 'https://www.instagram.com/', 'http://instagram.com/', 'http://www.instagram.com/'], '', $inscription->instagram), '@/ ');
                                    $handle = rtrim($handle, '/');
                                @endphp
                                <a href="https://www.instagram.com/{{ $handle }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-800 inline-flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                    {{ '@' . $handle }}
                                </a>
                            </dd>
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

                {{-- Histórico de Ações --}}
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Histórico de Ações</h2>
                    @if($activityLogs->count() > 0)
                        <div class="relative">
                            {{-- Linha vertical da timeline --}}
                            <div class="absolute left-3 top-2 bottom-2 w-0.5 bg-gray-200"></div>

                            <div class="space-y-4">
                                @foreach($activityLogs as $log)
                                    <div class="relative flex items-start gap-3 pl-1">
                                        {{-- Ícone colorido --}}
                                        <div class="relative z-10 flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center
                                            @if(str_contains($log->action, 'aprovar')) bg-blue-100
                                            @elseif(str_contains($log->action, 'rejeitar')) bg-red-100
                                            @elseif(str_contains($log->action, 'confirmar')) bg-green-100
                                            @elseif(str_contains($log->action, 'fila_espera')) bg-orange-100
                                            @elseif(str_contains($log->action, 'bulk')) bg-indigo-100
                                            @else bg-gray-100
                                            @endif">
                                            @if(str_contains($log->action, 'aprovar'))
                                                <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            @elseif(str_contains($log->action, 'rejeitar'))
                                                <svg class="w-3 h-3 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                            @elseif(str_contains($log->action, 'confirmar'))
                                                <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                            @elseif(str_contains($log->action, 'fila_espera'))
                                                <svg class="w-3 h-3 text-orange-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                            @else
                                                <svg class="w-3 h-3 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                            @endif
                                        </div>

                                        {{-- Conteúdo --}}
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-900">{{ $log->description }}</p>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                @if($log->user)
                                                    por <span class="font-medium">{{ $log->user->name }}</span> &mdash;
                                                @endif
                                                {{ $log->created_at->format('d/m/Y H:i') }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 text-center py-3">Nenhuma ação registrada.</p>
                    @endif
                </div>

                {{-- Comprovante de Pagamento --}}
                @if($inscription->payment_proof)
                <div class="bg-white rounded-xl shadow p-6" x-data="{ lightbox: false }">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Comprovante de Pagamento</h2>
                    <div class="border rounded-xl overflow-hidden">
                        @php
                            $extension = pathinfo($inscription->payment_proof, PATHINFO_EXTENSION);
                        @endphp
                        @if(in_array(strtolower($extension), ['jpg', 'jpeg', 'png']))
                            <img src="{{ Storage::url($inscription->payment_proof) }}" alt="Comprovante"
                                 class="w-full max-h-96 object-contain bg-gray-100 cursor-pointer hover:opacity-90 transition-opacity"
                                 @click="lightbox = true">

                            {{-- Lightbox fullscreen --}}
                            <div x-show="lightbox" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4"
                                 @click.self="lightbox = false" @keydown.escape.window="lightbox = false"
                                 style="display: none;">
                                <button @click="lightbox = false" class="absolute top-4 right-4 text-white hover:text-gray-300 transition-colors z-10">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                <img src="{{ Storage::url($inscription->payment_proof) }}" alt="Comprovante"
                                     class="max-w-full max-h-full object-contain rounded-lg shadow-2xl">
                            </div>
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
                <div class="bg-white rounded-xl shadow p-6" x-data>
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
                            <form method="POST" action="{{ route('admin.inscricoes.rejeitar', $inscription) }}">
                                @csrf
                                <button type="button" style="background-color:#dc2626;color:#fff;" class="w-full py-2.5 font-medium rounded-xl hover:opacity-80 transition-colors"
                                        @click="Swal.fire({ title: 'Rejeitar inscrição?', text: 'O participante será notificado sobre a rejeição.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280', confirmButtonText: 'Sim, rejeitar', cancelButtonText: 'Cancelar' }).then((result) => { if (result.isConfirmed) $el.closest('form').submit() })">
                                    Rejeitar Inscrição
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
                                <div class="text-center py-3 bg-yellow-50 rounded-xl border border-yellow-200 mb-3">
                                    <p class="text-sm text-yellow-700">Aguardando envio do comprovante pelo participante.</p>
                                </div>
                                <form method="POST" action="{{ route('admin.inscricoes.send-reminder', $inscription) }}">
                                    @csrf
                                    <button type="submit" class="w-full py-2.5 bg-amber-500 text-white font-medium rounded-xl hover:bg-amber-600 transition-colors">
                                        Enviar Lembrete
                                    </button>
                                </form>
                            @endif
                        @elseif($inscription->isWaitlisted())
                            <form method="POST" action="{{ route('admin.inscricoes.aprovar', $inscription) }}">
                                @csrf
                                <button type="submit" class="w-full py-2.5 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 transition-colors">
                                    Aprovar da Fila de Espera
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.inscricoes.rejeitar', $inscription) }}">
                                @csrf
                                <button type="button" style="background-color:#dc2626;color:#fff;" class="w-full py-2.5 font-medium rounded-xl hover:opacity-80 transition-colors"
                                        @click="Swal.fire({ title: 'Rejeitar inscrição?', text: 'O participante será notificado sobre a rejeição.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280', confirmButtonText: 'Sim, rejeitar', cancelButtonText: 'Cancelar' }).then((result) => { if (result.isConfirmed) $el.closest('form').submit() })">
                                    Rejeitar Inscrição
                                </button>
                            </form>
                        @elseif($inscription->isConfirmed())
                            <div class="text-center py-3 bg-green-50 rounded-xl border border-green-200">
                                <p class="text-sm text-green-700 font-medium">Participação confirmada!</p>
                                @if($inscription->confirmed_at)
                                    <p class="text-xs text-green-600 mt-1">Confirmado em {{ $inscription->confirmed_at->format('d/m/Y H:i') }}</p>
                                @endif
                            </div>
                        @elseif($inscription->isRejected())
                            <div class="text-center py-3 bg-red-50 rounded-xl border border-red-200">
                                <p class="text-sm text-red-700 font-medium">Inscrição rejeitada</p>
                            </div>
                        @elseif($inscription->isCancelled())
                            <div class="text-center py-3 bg-gray-50 rounded-xl border border-gray-200">
                                <p class="text-sm text-gray-700 font-medium">Inscrição cancelada pelo participante</p>
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
