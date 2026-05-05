@extends('emails.layout')

@section('subject', 'Solicitação de contribuição social aprovada - De Casa em Casa')

@section('badge')
<span style="display:inline-block; background-color:#dcfce7; color:#166534; padding:6px 20px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
    Solicitação aprovada
</span>
@endsection

@section('content')
<p style="margin:0 0 16px; color:#1a2e6e; font-size:16px; line-height:1.6;">
    Olá <strong>{{ $inscription->full_name }}</strong>,
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Sua <strong>solicitação de contribuição social</strong> foi <strong style="color:#166534;">aprovada</strong>! Combinamos o valor de:
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#dcfce7; border:1px solid #86efac; border-radius:8px; margin-bottom:16px;">
    <tr>
        <td style="padding:16px; text-align:center;">
            <p style="margin:0; color:#166534; font-size:22px; font-weight:800;">{{ $amountFormatted }}</p>
        </td>
    </tr>
</table>
@if($inscription->social_request_admin_message)
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f3ed; border-left:3px solid #e88a2d; border-radius:6px; margin-bottom:16px;">
    <tr>
        <td style="padding:14px 16px;">
            <p style="margin:0 0 6px; color:#1a2e6e; font-size:12px; font-weight:700; text-transform:uppercase;">Mensagem da equipe</p>
            <p style="margin:0; color:#4a4639; font-size:14px; line-height:1.6; white-space:pre-line;">{{ $inscription->social_request_admin_message }}</p>
        </td>
    </tr>
</table>
@endif
@if(config('services.pix.key'))
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef2ff; border:1px solid #c7d2fe; border-radius:8px;">
    <tr>
        <td style="padding:16px;">
            <p style="margin:0 0 6px; color:#1a2e6e; font-size:13px; font-weight:600;">Chave Pix para contribuição:</p>
            <p style="margin:0 0 4px; color:#4f46e5; font-size:16px; font-weight:700; font-family:monospace;">{{ config('services.pix.key') }}</p>
            <p style="margin:0; color:#9a9384; font-size:12px;">{{ config('services.pix.holder') }}</p>
        </td>
    </tr>
</table>
@endif
<p style="margin:16px 0 0; color:#4a4639; font-size:15px; line-height:1.7;">
    Após o pagamento, envie o comprovante pelo link abaixo para garantir sua vaga.
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
