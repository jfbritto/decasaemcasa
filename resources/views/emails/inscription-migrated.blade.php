@extends('emails.layout')

@section('subject', 'Sua inscrição foi transferida - De Casa em Casa')

@section('badge')
<span style="display:inline-block; background-color:#e0e7ff; color:#3730a3; padding:6px 20px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
    Transferência de Evento
</span>
@endsection

@section('content')
<p style="margin:0 0 16px; color:#1a2e6e; font-size:16px; line-height:1.6;">
    Olá <strong>{{ $inscription->full_name }}</strong>,
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Sua inscrição foi transferida do encontro <strong>{{ $originEventName }}</strong> para o encontro <strong>{{ $destinationEvent->city ?? $destinationEvent->title }}</strong>.
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Sua contribuição e dados foram mantidos. Confira os detalhes do novo encontro abaixo.
</p>
@endsection

@section('event_info')
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    @if($inscription->isConfirmed() && $destinationEvent->full_address)
    <tr>
        <td style="padding-bottom:12px;">
            <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="32" valign="top" style="padding-right:10px;">
                        <span style="font-size:18px;">&#128205;</span>
                    </td>
                    <td valign="top">
                        <p style="margin:0 0 2px; color:#9a9384; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Endereço</p>
                        <p style="margin:0; color:#1a2e6e; font-size:14px; font-weight:500;">{{ $destinationEvent->full_address }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endif
    @if($destinationEvent->arrival_time)
    <tr>
        <td style="padding-bottom:12px;">
            <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="32" valign="top" style="padding-right:10px;">
                        <span style="font-size:18px;">&#128336;</span>
                    </td>
                    <td valign="top">
                        <p style="margin:0 0 2px; color:#9a9384; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Horário de Chegada</p>
                        <p style="margin:0; color:#1a2e6e; font-size:14px; font-weight:500;">{{ $destinationEvent->arrival_time }}</p>
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
                        <p style="margin:0 0 2px; color:#9a9384; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Data</p>
                        <p style="margin:0; color:#1a2e6e; font-size:14px; font-weight:500;">{{ $destinationEvent->date->format('d/m/Y') }} — {{ $destinationEvent->city }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@endsection

@section('cta_url', $statusUrl)
@section('cta_text', 'Acompanhar Inscrição')
