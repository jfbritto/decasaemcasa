@extends('emails.layout')

{{-- Divulgação do app (spec 2026-11-12-email-divulgacao-app-design, DA6). Os botões das lojas vêm do layout. --}}

@section('subject', 'O De Casa em Casa agora tem app')

@section('badge')
<span style="display:inline-block; background-color:#e0e7ff; color:#1a2e6e; padding:6px 20px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
    Novidade
</span>
@endsection

@section('content')
<p style="margin:0 0 16px; color:#1a2e6e; font-size:16px; line-height:1.6;">
@if (filled($name))
    Olá <strong>{{ $name }}</strong>,
@else
    Olá!
@endif
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Temos uma novidade: o De Casa em Casa agora tem app, para iPhone e Android.
</p>
<p style="margin:0 0 8px; color:#4a4639; font-size:15px; line-height:1.7;">
    Pelo app você acompanha suas inscrições de perto:
</p>
<ul style="margin:0 0 16px; padding-left:20px; color:#4a4639; font-size:15px; line-height:1.7;">
    <li>vê em que etapa está cada inscrição;</li>
    <li>quando for aprovada, vê o prazo e o Pix e envia o comprovante pelo celular;</li>
    <li>com seu lugar na sala garantido, encontra o endereço e o horário do encontro;</li>
    <li>se ativar as notificações, recebe os avisos da equipe na hora.</li>
</ul>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Para entrar, use este mesmo e-mail (<strong>{{ $email }}</strong>): você recebe um código de 6 números, sem senha.
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Os botões para baixar estão logo abaixo.
</p>
<p style="margin:0; color:#4a4639; font-size:15px; line-height:1.7;">
    Um abraço da<br>
    <strong>Equipe De Casa em Casa</strong>
</p>
@endsection
