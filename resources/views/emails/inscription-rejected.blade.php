@extends('emails.layout')

@section('subject', 'Sobre sua inscrição - De Casa em Casa')

@section('badge')
<span style="display:inline-block; background-color:#fee2e2; color:#991b1b; padding:6px 20px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
    Não aprovada
</span>
@endsection

@section('content')
<p style="margin:0 0 16px; color:#1a2e6e; font-size:16px; line-height:1.6;">
    Olá <strong>{{ $inscription->full_name }}</strong>,
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Obrigado por se inscrever para o encontro <strong>De Casa em Casa</strong>.
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Como os encontros acontecem dentro de casas e com um número bem pequeno de pessoas, algumas inscrições acabam não seguindo para esta edição. Desta vez, não conseguimos confirmar a sua participação no encontro de <strong>{{ $event->city }}</strong>.
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Mas queremos que você saiba que ficamos muito felizes com o seu interesse em caminhar com a gente. Quem sabe em outra casa, em outra mesa, a gente consiga se encontrar.
</p>
<p style="margin:0; color:#4a4639; font-size:15px; line-height:1.7;">
    Seguimos por aqui, na torcida para que nossos caminhos se cruzem em breve.<br><br>
    Um abraço da<br>
    <strong>Equipe De Casa em Casa</strong>
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
@section('cta_text', 'Ver Minha Inscrição')
