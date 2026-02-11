@extends('emails.layout')

@section('subject', 'Inscrição recebida - De Casa em Casa')

@section('badge')
<span style="display:inline-block; background-color:#fef3c7; color:#92400e; padding:6px 20px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
    Inscrição Recebida
</span>
@endsection

@section('content')
<p style="margin:0 0 16px; color:#1a2e6e; font-size:16px; line-height:1.6;">
    Olá <strong>{{ $inscription->full_name }}</strong>,
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Recebemos sua história! Estamos em fase de curadoria. Como os lugares são limitados e em lares, fazemos essa leitura com carinho.
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Aguarde nosso retorno — avisaremos assim que tivermos novidades.
</p>
<p style="margin:0; padding:12px 16px; background-color:#fef3c7; border-radius:8px; color:#92400e; font-size:13px; line-height:1.6;">
    <strong>Lembrete:</strong> Cada pessoa deve fazer sua própria inscrição, incluindo crianças e acompanhantes. Caso alguém queira te acompanhar, essa pessoa precisa preencher a própria inscrição.
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
@section('cta_text', 'Acompanhar Minha Inscrição')
