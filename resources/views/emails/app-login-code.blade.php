@extends('emails.layout')

@section('subject', 'Seu código de acesso - De Casa em Casa')

@section('badge')
<span style="display:inline-block; background-color:#fef3c7; color:#92400e; padding:6px 20px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
    Código de acesso
</span>
@endsection

@section('content')
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Use o código abaixo para entrar no app <strong>De Casa em Casa</strong>:
</p>
<p style="margin:0 0 20px; text-align:center;">
    <span style="display:inline-block; background-color:#fff7ed; border:2px solid #e88a2d; border-radius:12px; padding:14px 28px; color:#1a2e6e; font-size:32px; font-weight:800; letter-spacing:10px; font-family:'Courier New', monospace;">{{ $code }}</span>
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    O código vale por {{ $ttlMinutes }} minutos e só pode ser usado uma vez.
</p>
<p style="margin:0; padding:12px 16px; background-color:#fef3c7; border-radius:8px; color:#92400e; font-size:13px; line-height:1.6;">
    Não pediu este código? Pode ignorar este e-mail: ninguém entra na sua conta sem ele.
</p>
@endsection
