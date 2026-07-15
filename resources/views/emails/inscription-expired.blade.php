@extends('emails.layout')

@section('subject', 'Sua inscrição voltou para a fila de espera - De Casa em Casa')

@section('badge')
<span style="display:inline-block; background-color:#ffedd5; color:#9a3412; padding:6px 20px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
    Fila de Espera
</span>
@endsection

@section('content')
<p style="margin:0 0 16px; color:#1a2e6e; font-size:16px; line-height:1.6;">
    Olá <strong>{{ $inscription->full_name }}</strong>,
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Não identificamos o envio do seu comprovante dentro do prazo de <strong>{{ $inscription->payment_deadline_label }}</strong> e, conforme avisamos no e-mail de aprovação, sua vaga foi liberada para a próxima pessoa da fila.
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Sua inscrição <strong>não foi cancelada</strong>: ela voltou para a nossa <strong>fila de espera</strong>. Se um lugar na sala se abrir, sua participação poderá ser aprovada novamente — você receberá um novo aviso, com um novo prazo.
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Já fez o Pix e não conseguiu anexar o comprovante a tempo? Fique tranquilo(a): nossa equipe pode reativar sua aprovação. Acompanhe sua inscrição pelo link abaixo.
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
@section('cta_text', 'Acompanhar Inscrição')
