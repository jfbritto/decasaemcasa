@extends('layouts.app')

@section('title', 'Política de Privacidade - De Casa em Casa')
@section('og_title', 'Política de Privacidade - De Casa em Casa')
@section('og_description', 'Como a turnê De Casa em Casa trata os seus dados no site e no app.')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-amber-50 to-white py-10">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-10 text-gray-700 leading-relaxed space-y-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Política de Privacidade</h1>
                <p class="text-sm text-gray-500 mt-1">Site e app De Casa em Casa. Atualizada em 08/11/2026.</p>
            </div>

            <p>
                Esta política explica quais dados a turnê <strong>De Casa em Casa</strong> coleta quando você se inscreve
                em um encontro pelo site ou pelo app, para que eles servem e quais são os seus direitos, conforme a
                Lei Geral de Proteção de Dados (Lei 13.709/2018).
            </p>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Quais dados coletamos</h2>
                <ul class="list-disc pl-6 space-y-1">
                    <li><strong>Dados da inscrição:</strong> nome completo, CPF, data de nascimento, bairro e cidade, WhatsApp, e-mail, Instagram (opcional) e a história que você conta sobre por que quer participar.</li>
                    <li><strong>Contribuição:</strong> o comprovante do Pix que você envia e, se pedir a contribuição social, a sua justificativa e o valor proposto.</li>
                    <li><strong>App:</strong> o seu e-mail (para o código de acesso) e, se você ativar os avisos, o identificador de notificações do seu aparelho. O app não acessa sua localização, seus contatos nem sua galeria sem a sua escolha: fotos e arquivos só são lidos quando você escolhe um comprovante para enviar.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Para que usamos</h2>
                <ul class="list-disc pl-6 space-y-1">
                    <li>Fazer a curadoria das inscrições e organizar quem participa de cada encontro.</li>
                    <li>Confirmar a contribuição e analisar pedidos de contribuição social.</li>
                    <li>Avisar você sobre cada mudança na sua inscrição, por e-mail e, se você ativar, por notificação no celular.</li>
                    <li>Garantir a segurança dos encontros, que acontecem dentro de casas: só pessoas confirmadas recebem o endereço.</li>
                </ul>
                <p class="mt-2">Não vendemos nem compartilhamos seus dados para publicidade.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Com quem os dados são compartilhados</h2>
                <p>
                    Apenas com os serviços que fazem o sistema funcionar: a hospedagem do site, o serviço de envio de
                    e-mails e o serviço de notificações do Expo (que entrega os avisos ao Apple Push Notification Service
                    e ao Firebase Cloud Messaging). Cada um recebe só o necessário para a sua função.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Por quanto tempo guardamos</h2>
                <p>
                    Os dados da inscrição ficam guardados enquanto forem necessários para a organização da turnê e para
                    cumprir obrigações legais. O identificador de notificações é apagado quando você sai do app ou exclui
                    a sua conta.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Seus direitos</h2>
                <p>
                    Você pode pedir acesso, correção ou exclusão dos seus dados a qualquer momento. No app, a opção
                    <strong>Perfil &gt; Excluir minha conta</strong> apaga a conta do app na hora.
                    Para remover também as suas inscrições, fale com a equipe.
                    <a href="{{ route('app.excluir-conta') }}" class="text-indigo-600 underline">Veja como excluir a conta</a>.
                </p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Fale com a equipe</h2>
                <p>
                    WhatsApp: <a href="{{ config('services.support.whatsapp_link') }}" target="_blank" rel="noopener" class="text-indigo-600 underline">{{ config('services.support.whatsapp_number') }}</a>
                </p>
            </section>
        </div>
    </div>
</div>
@endsection
