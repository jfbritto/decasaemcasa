<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * O código de acesso ao app (spec 2026-11-08-app-mobile-design, AM3).
 * Enviado na hora, fora da fila: a pessoa espera o código na tela. Não entra
 * no log de notificações do painel (o código não pode ser reenviado de lá).
 */
class ParticipantLoginCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly int $ttlMinutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Seu código de acesso: {$this->code} - De Casa em Casa",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.app-login-code',
        );
    }
}
