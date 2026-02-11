@extends('emails.layout')

@section('subject', 'Lembrete: Envie seu comprovante - De Casa em Casa')

@section('badge')
<span style="display:inline-block; background-color:#dbeafe; color:#1e40af; padding:6px 20px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
    Lembrete
</span>
@endsection

@section('content')
<p style="margin:0 0 16px; color:#1a2e6e; font-size:16px; line-height:1.6;">
    Olá <strong>{{ $inscription->full_name }}</strong>,
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Sua participação no encontro <strong>De Casa em Casa</strong> foi aprovada! Para garantir sua cadeira na sala, envie o comprovante de pagamento.
</p>

@if(config('services.pix.key'))
<div style="background-color:#eef2ff; border-radius:8px; padding:16px; margin:0 0 16px;">
    <p style="margin:0 0 8px; color:#4a4639; font-size:14px;"><strong>Chave Pix:</strong></p>
    <p style="margin:0 0 4px; color:#4338ca; font-size:16px; font-weight:700; font-family:monospace;">{{ config('services.pix.key') }}</p>
    <p style="margin:0; color:#9a9384; font-size:12px;">{{ config('services.pix.holder') }}</p>
    <p style="margin:8px 0 0; color:#9a9384; font-size:12px;">Você define o valor que faz sentido pra você.</p>
</div>
@endif

<p style="margin:0; color:#4a4639; font-size:15px; line-height:1.7;">
    Envie o comprovante pelo link abaixo para que possamos confirmar sua vaga.
</p>
@endsection

@section('event_info')
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td width="40" valign="top" style="padding-right:14px;">
            <div style="width:36px; height:36px; background-color:#1a2e6e; border-radius:8px; text-align:center; line-height:36px; color:#e88a2d; font-size:16px;">
                &#127968;
            </div>
        </td>
        <td valign="top">
            <p style="margin:0 0 4px; color:#1a2e6e; font-size:15px; font-weight:700;">{{ $event->city }}</p>
            <p style="margin:0; color:#9a9384; font-size:13px;">{{ $event->date->format('d/m/Y') }}</p>
        </td>
    </tr>
</table>
@endsection

@section('cta_url', $statusUrl)
@section('cta_text', 'Enviar Comprovante')
