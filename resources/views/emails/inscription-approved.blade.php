@extends('emails.layout')

@section('subject', 'Aprovado(a)! Garanta seu lugar na sala - De Casa em Casa')

@section('badge')
<span style="display:inline-block; background-color:#dbeafe; color:#1e40af; padding:6px 20px; border-radius:20px; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
    Aprovado(a)!
</span>
@endsection

@section('content')
@php
    $deadlineLabel = $inscription->payment_deadline_label;
    $deadlineTime = $inscription->payment_deadline_at?->format('H\hi');
    $deadlineDate = $inscription->payment_deadline_at?->format('d/m');
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fef3c7; border:1px solid #f59e0b; border-radius:8px; margin-bottom:20px;">
    <tr>
        <td style="padding:12px 16px; text-align:center;">
            <p style="margin:0; color:#92400e; font-size:14px; font-weight:800; letter-spacing:0.5px;">
                &#9203; AÇÃO NECESSÁRIA: SUA VAGA EXPIRA EM {{ mb_strtoupper($deadlineLabel) }}
            </p>
        </td>
    </tr>
</table>
<p style="margin:0 0 16px; color:#1a2e6e; font-size:16px; line-height:1.6;">
    Olá <strong>{{ $inscription->full_name }}</strong>,
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    A boa notícia é que sua participação foi oficialmente <strong style="color:#1a2e6e;">aprovada</strong>. A notícia urgente é que você precisa agir agora.
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    Como o nosso espaço é muito intimista e temos uma lista de espera enorme de pessoas querendo participar, <strong>sua vaga ficará reservada no seu nome apenas por {{ $deadlineLabel }}@if($deadlineTime) (até às {{ $deadlineTime }} de {{ $deadlineDate }})@endif</strong>. Se não recebermos o seu comprovante dentro desse prazo, sua inscrição voltará automaticamente para a fila de espera e seu lugar na sala será liberado para a próxima pessoa.
</p>
<p style="margin:0 0 8px; color:#1a2e6e; font-size:15px; line-height:1.7; font-weight:700;">
    Siga os 2 passos abaixo para não perder seu lugar na sala:
</p>
<p style="margin:0 0 16px; color:#4a4639; font-size:15px; line-height:1.7;">
    <strong>1.</strong> Faça sua contribuição usando a Chave Pix informada no quadro abaixo.<br>
    <strong>2.</strong> Com o comprovante salvo no celular, clique no botão para anexá-lo e finalizar.
</p>
@if(config('services.pix.key'))
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef2ff; border:1px solid #c7d2fe; border-radius:8px;">
    <tr>
        <td style="padding:16px;">
            <p style="margin:0 0 6px; color:#1a2e6e; font-size:13px; font-weight:600;">Chave Pix para contribuição:</p>
            <p style="margin:0 0 4px; color:#4f46e5; font-size:16px; font-weight:700; font-family:monospace;">{{ config('services.pix.key') }}</p>
            <p style="margin:0 0 8px; color:#9a9384; font-size:12px;">{{ config('services.pix.holder') }}</p>
            <p style="margin:0; color:#6b7280; font-size:12px; font-style:italic;">Meu sonho era fazer uma bilheteria que subvertesse a lógica do entretenimento e nos colocasse no campo da arte, no fluxo da dádiva. Assim, essa é uma oportunidade para você deixar fluir a generosidade! Esse encontro tem valor inestimável e o preço é sugerido: R$ 100,00 (cem reais). Contribua conforme mandar seu coração, segundo as suas condições e recursos, <strong>mas finalize agora para assegurar seu lugar.</strong></p>
        </td>
    </tr>
</table>
@endif
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f3ed; border-left:3px solid #e88a2d; border-radius:6px; margin-top:16px;">
    <tr>
        <td style="padding:14px 16px;">
            <p style="margin:0 0 6px; color:#1a2e6e; font-size:13px; font-weight:700;">Não consegue contribuir com o valor de referência neste momento?</p>
            <p style="margin:0; color:#4a4639; font-size:13px; line-height:1.6;">
                Antes de fazer o Pix, você pode solicitar uma <strong>contribuição social</strong> — é na mesma página do botão abaixo. Conte brevemente sua situação e o valor que consegue contribuir — nossa equipe analisa e retorna com a resposta. <em>Enquanto sua solicitação estiver em análise, o prazo fica pausado. A vaga só é confirmada após aprovação da solicitação e envio do comprovante.</em>
            </p>
        </td>
    </tr>
</table>
<p style="margin:16px 0 0; color:#92400e; font-size:14px; line-height:1.6;">
    &#9200; O cronômetro já está rodando. Nos vemos lá?
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
@section('cta_text', 'Já fiz o Pix: enviar comprovante')
