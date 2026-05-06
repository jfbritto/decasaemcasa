@extends('layouts.app')

@section('title', 'Comprovante recebido - De Casa em Casa')
@section('og_title', 'Comprovante recebido - De Casa em Casa')
@section('og_description', 'Recebemos seu comprovante. Em breve, sua participação no encontro De Casa em Casa será confirmada.')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-amber-50 to-white py-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="bg-white rounded-2xl shadow-lg p-8 sm:p-10 text-center">

            {{-- Ícone de sucesso --}}
            <div class="mx-auto mb-6 w-20 h-20 rounded-full bg-green-100 flex items-center justify-center">
                <svg class="w-12 h-12 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-3">Deu tudo certo!</h1>
            <p class="text-lg text-gray-700 mb-2">
                Olá, <strong>{{ $inscription->full_name }}</strong>.
            </p>
            <p class="text-base text-gray-600 leading-relaxed mb-6">
                Recebemos seu comprovante de pagamento.
            </p>

            <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6 text-left">
                <p class="text-gray-800 leading-relaxed">
                    Agora é aguardar as <strong>coordenadas da nossa equipe</strong> para você chegar no endereço certinho e aproveitar esse encontro histórico.
                </p>
                <p class="text-gray-700 leading-relaxed mt-3 text-sm">
                    Assim que validarmos seu comprovante, você vai receber o endereço do encontro por e-mail — e também ficará disponível na sua página de status.
                </p>
            </div>

            @if($inscription->event)
            <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-900">{{ $inscription->event->city ?? $inscription->event->title }}</p>
                        <p class="text-sm text-gray-600">{{ $inscription->event->date->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <a href="{{ route('inscricao.status', $inscription->token) }}"
               class="inline-block w-full sm:w-auto px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-colors">
                Voltar para minha inscrição
            </a>
        </div>

        <p class="text-center text-sm text-gray-500 mt-6">
            Um abraço da<br>
            <strong>Equipe De Casa em Casa</strong>
        </p>
    </div>
</div>
@endsection
