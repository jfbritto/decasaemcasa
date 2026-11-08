@extends('layouts.app')

@section('title', 'Excluir conta do app - De Casa em Casa')
@section('og_title', 'Excluir conta do app - De Casa em Casa')
@section('og_description', 'Como excluir a sua conta do app De Casa em Casa e pedir a remoção dos seus dados.')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-amber-50 to-white py-10">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-10 text-gray-700 leading-relaxed space-y-6">
            <h1 class="text-3xl font-bold text-gray-900">Excluir a conta do app</h1>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Pelo app</h2>
                <ol class="list-decimal pl-6 space-y-1">
                    <li>Abra o app <strong>De Casa em Casa</strong> e entre com o seu e-mail.</li>
                    <li>Toque em <strong>Perfil</strong>.</li>
                    <li>Toque em <strong>Excluir minha conta</strong> e confirme.</li>
                </ol>
                <p class="mt-2">
                    A conta do app é apagada na hora, junto com os acessos dos seus aparelhos e o identificador de
                    notificações.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">E as minhas inscrições?</h2>
                <p>
                    As inscrições nos encontros são o registro da turnê e também existem pelo site, por isso não são
                    apagadas junto com a conta do app. Para pedir a remoção delas e dos seus dados, fale com a equipe pelo
                    WhatsApp abaixo, informando o e-mail usado na inscrição. Atendemos o pedido em até 15 dias.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Sem acesso ao app?</h2>
                <p>
                    Fale com a equipe pelo WhatsApp
                    <a href="{{ config('services.support.whatsapp_link') }}" target="_blank" rel="noopener" class="text-indigo-600 underline">{{ config('services.support.whatsapp_number') }}</a>
                    e pediremos a exclusão da conta e das inscrições ligadas ao seu e-mail.
                </p>
            </section>

            <p class="text-sm text-gray-500">
                <a href="{{ route('privacidade') }}" class="text-indigo-600 underline">Política de Privacidade</a>
            </p>
        </div>
    </div>
</div>
@endsection
