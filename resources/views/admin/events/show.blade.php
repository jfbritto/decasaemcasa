@extends('layouts.app')

@section('title', $event->title)

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
    <div class="px-4 sm:px-0">

        {{-- Tarja de evento excluído --}}
        @if($event->trashed())
            <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 mb-6 flex items-center gap-3">
                <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <div>
                    <p class="text-red-800 font-semibold">Este encontro foi excluído</p>
                    <p class="text-red-600 text-sm">Excluído em {{ $event->deleted_at->format('d/m/Y \à\s H:i') }}. As informações estão disponíveis apenas para consulta.</p>
                </div>
            </div>
        @endif

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 {{ $event->trashed() ? 'line-through text-gray-500' : '' }}">{{ $event->city ?? $event->title }}</h1>
            <div class="flex flex-wrap gap-2">
                @unless($event->trashed())
                    @if($inscriptionStats['confirmado'] > 0)
                        <button type="button" onclick="document.getElementById('modal-custom-message').style.display='flex'" style="background-color:#7c3aed;color:#fff;" class="px-3 py-2 text-sm sm:text-base sm:px-4 rounded-md hover:opacity-80 text-center">
                            Enviar Mensagem
                        </button>
                    @endif
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
                    @if($event->isFull() && ($inscriptionStats['pendente'] + $inscriptionStats['aprovado']) > 0)
                        <form method="POST" action="{{ route('admin.events.notify-event-full', $event) }}" class="inline" x-data>
                            @csrf
                            <button type="button" style="background-color:#ea580c;color:#fff;" class="px-3 py-2 text-sm sm:text-base sm:px-4 rounded-md hover:opacity-80 text-center"
                                    @click="Swal.fire({ title: 'Notificar vagas esgotadas?', html: '{{ $inscriptionStats['pendente'] + $inscriptionStats['aprovado'] }} inscritos serão <strong>movidos para Fila de Espera</strong> e notificados por email.', icon: 'question', showCancelButton: true, confirmButtonColor: '#ea580c', cancelButtonColor: '#6b7280', confirmButtonText: 'Sim, notificar', cancelButtonText: 'Cancelar' }).then((result) => { if (result.isConfirmed) $el.closest('form').submit() })">
                                Notificar Esgotado
                            </button>
                        </form>
                    @endif
                @endunless
                <a href="{{ route('admin.events.index') }}" class="bg-gray-600 text-white px-3 py-2 text-sm sm:text-base sm:px-4 rounded-md hover:bg-gray-700 text-center">
                    Voltar
                </a>
            </div>
        </div>

        {{-- Indicador de emails na fila (evento esgotado) --}}
        @if($pendingEmailsCount > 0)
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-sm text-amber-800">
                    <strong>{{ $pendingEmailsCount }}</strong> {{ $pendingEmailsCount === 1 ? 'email de vagas esgotadas aguardando envio' : 'emails de vagas esgotadas aguardando envio' }} na fila.
                    <span class="text-amber-600">Atualize a página para acompanhar.</span>
                </p>
            </div>
        @endif

        {{-- Indicador de mensagens customizadas na fila --}}
        @if($pendingCustomMessageCount > 0)
            <div class="bg-purple-50 border border-purple-200 rounded-xl p-4 mb-6 flex items-center gap-3">
                <svg class="w-5 h-5 text-purple-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-sm text-purple-800">
                    <strong>{{ $pendingCustomMessageCount }}</strong> {{ $pendingCustomMessageCount === 1 ? 'mensagem customizada aguardando envio' : 'mensagens customizadas aguardando envio' }} na fila.
                    <span class="text-purple-600">Atualize a página para acompanhar.</span>
                </p>
            </div>
        @endif

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
                            @if($event->trashed())
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Excluído</span>
                            @else
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
                            @endif
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

                {{-- Mini-cards --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-white rounded-lg shadow-md p-4 text-center" style="border-top:4px solid #eab308;">
                        <p class="text-2xl font-bold" style="color:#a16207;">{{ $inscriptionStats['pendente'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Pendentes</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-4 text-center" style="border-top:4px solid #3b82f6;">
                        <p class="text-2xl font-bold" style="color:#1d4ed8;">{{ $inscriptionStats['aguardando_pix'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Aguardando Pix</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-4 text-center" style="border-top:4px solid #6366f1;">
                        <p class="text-2xl font-bold" style="color:#4338ca;">{{ $inscriptionStats['comprovante_enviado'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Comprovante Enviado</p>
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
                    <div class="col-span-2 bg-white rounded-lg shadow-md p-4 text-center" style="border-top:4px solid #9ca3af;">
                        <p class="text-2xl font-bold" style="color:#374151;">{{ $inscriptionStats['cancelado'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Cancelados</p>
                    </div>
                </div>

                {{-- Card destaque: Total Arrecadado --}}
                <div class="bg-green-50 border border-green-200 rounded-lg shadow-md p-6 text-center">
                    <p class="text-xs text-green-600 uppercase tracking-wide font-semibold mb-1">Total Arrecadado</p>
                    <p class="text-3xl font-bold" style="color:#15803d;">R$ {{ number_format($inscriptionStats['total_arrecadado'], 2, ',', '.') }}</p>
                    @if($inscriptionStats['confirmado'] > 0)
                        <p class="text-xs text-green-500 mt-2">de {{ $inscriptionStats['confirmado'] }} participante{{ $inscriptionStats['confirmado'] > 1 ? 's' : '' }} confirmado{{ $inscriptionStats['confirmado'] > 1 ? 's' : '' }}</p>
                    @endif
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
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Comprovante</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Contribuição</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($inscriptions as $inscription)
                                <tr class="hover:bg-gray-50 cursor-pointer transition-colors"
                                    onclick="window.location='{{ route('admin.inscricoes.show', $inscription) }}?from=event:{{ $event->id }}'">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $inscription->full_name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $inscription->whatsapp }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 text-xs rounded-full font-medium"
                                            @if($inscription->isPending()) style="background-color:#fef9c3;color:#854d0e;"
                                            @elseif($inscription->isApproved()) style="background-color:#dbeafe;color:#1e40af;"
                                            @elseif($inscription->isConfirmed()) style="background-color:#dcfce7;color:#166534;"
                                            @elseif($inscription->isWaitlisted()) style="background-color:#ffedd5;color:#9a3412;"
                                            @elseif($inscription->isRejected()) style="background-color:#fee2e2;color:#991b1b;"
                                            @elseif($inscription->isCancelled()) style="background-color:#f3f4f6;color:#374151;"
                                            @endif>
                                            {{ $inscription->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($inscription->payment_proof)
                                            <span class="inline-flex items-center" style="color:#16a34a;">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                Enviado
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($inscription->contribution_amount)
                                            <span class="font-medium" style="color:#15803d;">R$ {{ number_format($inscription->contribution_amount, 2, ',', '.') }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Cards para mobile --}}
                <div class="sm:hidden space-y-3">
                    @foreach($inscriptions as $inscription)
                        <a href="{{ route('admin.inscricoes.show', $inscription) }}?from=event:{{ $event->id }}" class="block bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition-colors">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-900">{{ $inscription->full_name }}</p>
                                <span class="px-2 py-0.5 text-xs rounded-full font-medium"
                                    @if($inscription->isPending()) style="background-color:#fef9c3;color:#854d0e;"
                                    @elseif($inscription->isApproved()) style="background-color:#dbeafe;color:#1e40af;"
                                    @elseif($inscription->isConfirmed()) style="background-color:#dcfce7;color:#166534;"
                                    @elseif($inscription->isWaitlisted()) style="background-color:#ffedd5;color:#9a3412;"
                                    @elseif($inscription->isRejected()) style="background-color:#fee2e2;color:#991b1b;"
                                    @elseif($inscription->isCancelled()) style="background-color:#f3f4f6;color:#374151;"
                                    @endif>
                                    {{ $inscription->status_label }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <p class="text-xs text-gray-500">{{ $inscription->whatsapp }}</p>
                                <div class="flex items-center gap-2">
                                    @if($inscription->contribution_amount)
                                        <span class="text-xs font-medium" style="color:#15803d;">R$ {{ number_format($inscription->contribution_amount, 2, ',', '.') }}</span>
                                    @endif
                                    @if($inscription->payment_proof)
                                        <span class="inline-flex items-center text-xs" style="color:#16a34a;">
                                            <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                            Comprovante
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Paginação --}}
                <div class="mt-4 border-t pt-4">
                    <p class="text-sm text-gray-600 mb-2">
                        Exibindo <span class="font-medium">{{ $inscriptions->firstItem() }}</span> a <span class="font-medium">{{ $inscriptions->lastItem() }}</span> de <span class="font-medium">{{ $inscriptions->total() }}</span> resultados
                    </p>
                    @if($inscriptions->hasPages())
                        {{ $inscriptions->links() }}
                    @endif
                </div>
            @else
                <p class="text-gray-500">Nenhuma inscrição para este encontro.</p>
            @endif
        </div>

    </div>
</div>

@endsection

@push('modals')
{{-- Modal: Enviar Mensagem para Confirmados --}}
<div id="modal-custom-message" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.55); align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; border-radius:12px; box-shadow:0 20px 60px rgba(0,0,0,0.3); width:100%; max-width:520px; overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid #e5e7eb;">
            <div>
                <h3 style="margin:0; font-size:15px; font-weight:700; color:#111827;">Enviar Mensagem</h3>
                <p style="margin:2px 0 0; font-size:12px; color:#6b7280;">{{ $inscriptionStats['confirmado'] }} confirmado(s) · {{ $event->city }}</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-custom-message').style.display='none'" style="background:none; border:none; cursor:pointer; color:#9ca3af; padding:4px;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.events.send-custom-message', $event) }}">
            @csrf
            <div style="padding:20px; display:flex; flex-direction:column; gap:14px;">
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px; text-transform:uppercase; letter-spacing:.5px;">Assunto</label>
                    <input type="text" name="subject" required maxlength="255"
                           placeholder="Ex: Informações importantes sobre o encontro"
                           style="width:100%; box-sizing:border-box; border:1px solid #d1d5db; border-radius:8px; padding:8px 12px; font-size:13px; outline:none;"
                           onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#d1d5db'">
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px; text-transform:uppercase; letter-spacing:.5px;">Mensagem</label>
                    <textarea name="body" required maxlength="5000" rows="7"
                              placeholder="Digite a mensagem para os participantes..."
                              style="width:100%; box-sizing:border-box; border:1px solid #d1d5db; border-radius:8px; padding:8px 12px; font-size:13px; outline:none; resize:vertical; font-family:inherit;"
                              onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#d1d5db'"></textarea>
                    <p style="margin:4px 0 0; font-size:11px; color:#9ca3af;">Cada parágrafo separado por linha em branco será um bloco no email.</p>
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; padding:14px 20px; border-top:1px solid #e5e7eb; background:#f9fafb;">
                <button type="button" onclick="document.getElementById('modal-custom-message').style.display='none'"
                        style="padding:8px 16px; font-size:13px; color:#374151; background:#e5e7eb; border:none; border-radius:8px; cursor:pointer;">
                    Cancelar
                </button>
                <button type="button"
                        onclick="Swal.fire({ title: 'Enviar mensagem?', html: 'A mensagem será enviada para <strong>{{ $inscriptionStats['confirmado'] }} participante(s) confirmado(s)</strong>.', icon: 'question', showCancelButton: true, confirmButtonColor: '#7c3aed', cancelButtonColor: '#6b7280', confirmButtonText: 'Sim, enviar', cancelButtonText: 'Cancelar' }).then((result) => { if (result.isConfirmed) this.closest('form').submit() })"
                        style="padding:8px 16px; font-size:13px; color:#fff; background:#7c3aed; border:none; border-radius:8px; cursor:pointer;">
                    Enviar Mensagem
                </button>
            </div>
        </form>
    </div>
</div>
@endpush
