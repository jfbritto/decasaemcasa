@extends('layouts.app')

@section('title', 'Status da Inscrição - De Casa em Casa')
@section('og_title', 'Acompanhe sua Inscrição - De Casa em Casa')
@section('og_description', 'Acompanhe o status da sua inscrição para o encontro De Casa em Casa.')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-amber-50 to-white py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Status da Inscrição</h1>
            <p class="text-gray-600 mt-1">{{ $inscription->full_name }}</p>
        </div>

        {{-- Imagem de capa do evento --}}
        @if($inscription->event->image)
            <div class="rounded-2xl overflow-hidden shadow-lg mb-6">
                <img src="{{ asset('storage/' . $inscription->event->image) }}"
                     alt="{{ $inscription->event->title }}"
                     class="w-full h-48 sm:h-56 object-cover">
            </div>
        @endif

        {{-- Status Card --}}
        <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 mb-6">

            {{-- Status Badge --}}
            <div class="text-center mb-6">
                @if($inscription->isPending())
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                        Pendente - Em Curadoria
                    </span>
                @elseif($inscription->isApproved())
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Aprovado - Aguardando Pagamento
                    </span>
                @elseif($inscription->isConfirmed())
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Confirmado
                    </span>
                @elseif($inscription->isWaitlisted())
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-orange-100 text-orange-800">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                        Fila de Espera
                    </span>
                @elseif($inscription->isRejected())
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        Não Aprovada
                    </span>
                @elseif($inscription->isCancelled())
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-gray-100 text-gray-800">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        Cancelada por você
                    </span>
                @endif
            </div>

            {{-- Evento Info --}}
            <div class="bg-gray-50 rounded-xl p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-900">{{ $inscription->event->city ?? $inscription->event->title }}</p>
                        <p class="text-sm text-gray-600">{{ $inscription->event->date->format('d/m/Y') }}</p>
                    </div>
                    @if($inscription->event->title && $inscription->event->city && $inscription->event->title !== $inscription->event->city)
                        <p class="text-sm text-gray-500">{{ $inscription->event->title }}</p>
                    @endif
                </div>
            </div>

            {{-- Mensagem por Status --}}
            <div class="rounded-xl p-5 mb-6
                @if($inscription->isPending()) bg-yellow-50 border border-yellow-200
                @elseif($inscription->isApproved()) bg-blue-50 border border-blue-200
                @elseif($inscription->isConfirmed()) bg-green-50 border border-green-200
                @elseif($inscription->isWaitlisted()) bg-orange-50 border border-orange-200
                @elseif($inscription->isRejected()) bg-red-50 border border-red-200
                @elseif($inscription->isCancelled()) bg-gray-50 border border-gray-200
                @endif">

                @if($inscription->isPending())
                    <p class="text-gray-700 leading-relaxed">
                        Recebemos sua história! Estamos em fase de curadoria. Como os lugares são limitados e em lares, fazemos essa leitura com carinho. Aguarde nosso retorno.
                    </p>
                @elseif($inscription->isApproved())
                    <p class="text-gray-700 leading-relaxed">
                        Tudo pronto! Sua participação foi aprovada. Para garantir sua cadeira na sala, envie o comprovante de pagamento abaixo.
                    </p>
                @elseif($inscription->isConfirmed())
                    <p class="text-gray-700 leading-relaxed">
                        Que alegria ter você conosco! Prepare o coração!
                    </p>
                @elseif($inscription->isWaitlisted())
                    <p class="text-gray-700 leading-relaxed">
                        Recebemos sua história e ficamos muito felizes! No momento, as cadeiras para este encontro já foram preenchidas. Vamos manter seu contato em nossa "Fila de Espera"; caso haja alguma desistência ou uma nova data por perto, avisaremos você.
                    </p>
                @elseif($inscription->isRejected())
                    <p class="text-gray-700 leading-relaxed">
                        Agradecemos muito o interesse em participar do encontro De Casa em Casa. Infelizmente, não conseguimos incluir sua participação nesta edição. Fique de olho nas próximas edições!
                    </p>
                @elseif($inscription->isCancelled())
                    <p class="text-gray-700 leading-relaxed">
                        Você cancelou sua inscrição. Esperamos te ver em uma próxima edição!
                    </p>
                @endif
            </div>

            {{-- Upload de Comprovante (só se aprovado) --}}
            @if($inscription->isApproved())
                <div class="border-2 border-dashed border-blue-300 rounded-xl p-6 mb-6">
                    <h3 class="font-semibold text-gray-900 mb-3">Enviar Comprovante de Pagamento</h3>

                    @if(config('services.pix.key'))
                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-4">
                        <p class="text-sm text-gray-700 mb-3"><strong>Chave Pix para contribuição:</strong></p>
                        <div class="bg-white rounded-lg border border-indigo-200 px-4 py-3">
                            <p class="font-mono text-sm font-semibold text-indigo-700 break-all" id="pix-key">{{ config('services.pix.key') }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ config('services.pix.holder') }}</p>
                            <button onclick="navigator.clipboard.writeText(document.getElementById('pix-key').textContent.trim()); this.textContent = '✓ Copiado!'; setTimeout(() => this.textContent = 'Copiar chave Pix', 2000);"
                                    class="mt-3 w-full py-2 bg-indigo-100 text-indigo-700 text-sm font-semibold rounded-lg hover:bg-indigo-200 transition-colors">
                                Copiar chave Pix
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Você define o valor que faz sentido pra você.</p>
                    </div>
                    @endif

                    @if($inscription->payment_proof)
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                            <div class="flex items-center text-green-700">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <span class="text-sm font-medium">Comprovante já enviado. Aguardando confirmação da equipe.</span>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('inscricao.upload-comprovante', $inscription->token) }}" enctype="multipart/form-data"
                          x-data="{ uploading: false, fileName: '' }"
                          @submit="uploading = true">
                        @csrf
                        <div class="mb-4">
                            <label for="payment_proof" class="block text-sm text-gray-600 mb-2">
                                {{ $inscription->payment_proof ? 'Enviar novo comprovante (substituir):' : 'Selecione o comprovante (imagem ou PDF, max 5MB):' }}
                            </label>
                            <input type="file" name="payment_proof" id="payment_proof" accept=".jpg,.jpeg,.png,.pdf"
                                   @change="fileName = $event.target.files[0]?.name || ''"
                                   class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
                            <p x-show="fileName" x-text="'Arquivo selecionado: ' + fileName" class="mt-1 text-xs text-indigo-600"></p>
                            @error('payment_proof')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" :disabled="uploading"
                                class="w-full py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 disabled:opacity-70 disabled:cursor-not-allowed transition-all duration-200 flex items-center justify-center">
                            <template x-if="!uploading">
                                <span>Enviar Comprovante</span>
                            </template>
                            <template x-if="uploading">
                                <span class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Enviando... Aguarde
                                </span>
                            </template>
                        </button>
                    </form>
                </div>
            @endif

            {{-- Endereço (só se confirmado) --}}
            @if($inscription->isConfirmed())
                <div class="bg-green-50 border-2 border-green-300 rounded-xl p-6">
                    <h3 class="font-semibold text-green-800 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Informações do Encontro
                    </h3>

                    @if($inscription->event->full_address)
                        {{-- Alerta de sigilo --}}
                        <div class="bg-red-50 border border-red-300 rounded-lg p-3 mb-4">
                            <p class="text-sm text-red-800 font-semibold flex items-start">
                                <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                Não divulgue este endereço. Este encontro é secreto. Somente pessoas com nome na lista poderão entrar.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <p class="text-gray-700">
                                <strong>Endereço:</strong> {{ $inscription->event->full_address }}
                            </p>
                            @if($inscription->event->arrival_time)
                                <p class="text-gray-700">
                                    <strong>Horário de Chegada:</strong> {{ $inscription->event->arrival_time }}
                                </p>
                            @endif
                            <p class="text-gray-700">
                                <strong>Data:</strong> {{ $inscription->event->date->format('d/m/Y') }}
                            </p>
                        </div>
                    @else
                        <p class="text-gray-600">As informações de endereço serão disponibilizadas em breve.</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Botão de Cancelamento --}}
        @if(!$inscription->isCancelled() && !$inscription->isRejected() && !$inscription->isConfirmed())
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6" x-data="{ confirmCancel: false }">
            <div x-show="!confirmCancel">
                <button @click="confirmCancel = true" class="w-full py-3 border-2 border-red-300 text-red-600 font-medium rounded-xl hover:bg-red-50 transition-colors text-sm">
                    Cancelar minha inscrição
                </button>
            </div>
            <div x-show="confirmCancel" x-transition class="space-y-3">
                <p class="text-sm text-gray-700 text-center">Tem certeza que deseja cancelar sua inscrição? Esta ação não pode ser desfeita.</p>
                <div class="flex gap-3">
                    <button @click="confirmCancel = false" class="flex-1 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors text-sm">
                        Não, manter
                    </button>
                    <form method="POST" action="{{ route('inscricao.cancel', $inscription->token) }}" class="flex-1">
                        @csrf
                        <button type="submit" style="background-color:#dc2626;color:#fff;" class="w-full py-2.5 font-medium rounded-xl hover:opacity-80 transition-colors text-sm">
                            Sim, cancelar
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- Link para salvar --}}
        <div class="text-center">
            <p class="text-sm text-gray-500 mb-2">Salve este link para acompanhar o status da sua inscrição:</p>
            <div class="bg-white rounded-xl border border-gray-200 p-3 flex items-center justify-between">
                <input type="text" value="{{ route('inscricao.status', $inscription->token) }}" readonly
                       class="flex-1 text-sm text-gray-600 bg-transparent border-none focus:ring-0 truncate" id="status-url">
                <button onclick="navigator.clipboard.writeText(document.getElementById('status-url').value); this.textContent = 'Copiado!'; setTimeout(() => this.textContent = 'Copiar', 2000);"
                        class="ml-2 px-3 py-1 bg-indigo-100 text-indigo-600 text-sm font-medium rounded-lg hover:bg-indigo-200 transition-colors">
                    Copiar
                </button>
            </div>
        </div>

    </div>
</div>
@endsection
