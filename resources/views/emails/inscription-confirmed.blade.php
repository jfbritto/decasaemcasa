@extends('emails.layout')

@section('subject', 'Confirmado! Prepare o coração - De Casa em Casa')

@section('badge')
<span style="display:inline-block; background-color:#d1fae5; color:#065f46; padding:6px 20px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
    Confirmado(a)!
</span>
@endsection

@section('content')
<p style="margin:0 0 16px; color:#3d3a34; font-size:16px; line-height:1.6;">
    Olá <strong>{{ $inscription->full_name }}</strong>,
</p>
<p style="margin:0 0 16px; color:#5c584f; font-size:15px; line-height:1.7;">
    Que alegria ter você conosco! Sua presença está <strong style="color:#065f46;">confirmada</strong>. Prepare o coração para um encontro especial.
</p>
<p style="margin:0; color:#5c584f; font-size:14px; line-height:1.6; font-style:italic;">
    Confira os detalhes do encontro abaixo.
</p>
@endsection

@section('event_info')
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    @if($event->full_address)
    <tr>
        <td style="padding-bottom:12px;">
            <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="32" valign="top" style="padding-right:10px;">
                        <span style="font-size:18px;">&#128205;</span>
                    </td>
                    <td valign="top">
                        <p style="margin:0 0 2px; color:#8a8578; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Endereço</p>
                        <p style="margin:0; color:#3d3a34; font-size:14px; font-weight:500;">{{ $event->full_address }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endif
    @if($event->arrival_time)
    <tr>
        <td style="padding-bottom:12px;">
            <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="32" valign="top" style="padding-right:10px;">
                        <span style="font-size:18px;">&#128336;</span>
                    </td>
                    <td valign="top">
                        <p style="margin:0 0 2px; color:#8a8578; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Horário de Chegada</p>
                        <p style="margin:0; color:#3d3a34; font-size:14px; font-weight:500;">{{ $event->arrival_time }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endif
    <tr>
        <td>
            <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="32" valign="top" style="padding-right:10px;">
                        <span style="font-size:18px;">&#128197;</span>
                    </td>
                    <td valign="top">
                        <p style="margin:0 0 2px; color:#8a8578; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Data</p>
                        <p style="margin:0; color:#3d3a34; font-size:14px; font-weight:500;">{{ $event->date->format('d/m/Y') }} — {{ $event->city }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@endsection

@section('cta_url', $statusUrl)
@section('cta_text', 'Ver Detalhes do Encontro')
