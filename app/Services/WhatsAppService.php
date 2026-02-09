<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class WhatsAppService
{
    private ?Client $client = null;

    /**
     * Envia uma mensagem WhatsApp via Twilio.
     *
     * @throws \Exception se o envio falhar
     */
    public function send(string $to, string $message): bool
    {
        if (! $this->isEnabled()) {
            Log::debug('WhatsApp desativado. Mensagem não enviada.', ['to' => $to]);

            return false;
        }

        $client = $this->getClient();
        $from = 'whatsapp:'.config('services.twilio.whatsapp_from');
        $toFormatted = 'whatsapp:'.$this->formatPhoneNumber($to);

        $client->messages->create($toFormatted, [
            'from' => $from,
            'body' => $message,
        ]);

        return true;
    }

    /**
     * Verifica se o serviço de WhatsApp está habilitado.
     */
    public function isEnabled(): bool
    {
        return filter_var(config('services.twilio.whatsapp_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Formata número de telefone brasileiro para formato internacional E.164.
     * Ex: (31) 99999-9999 → +5531999999999
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove tudo que não é dígito
        $digits = preg_replace('/\D/', '', $phone);

        // Se já começa com 55 e tem 12-13 dígitos, assume que já tem código do país
        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            return '+'.$digits;
        }

        // Adiciona código do Brasil
        return '+55'.$digits;
    }

    /**
     * Retorna o client Twilio (lazy-loaded).
     */
    private function getClient(): Client
    {
        if ($this->client === null) {
            $sid = config('services.twilio.sid');
            $token = config('services.twilio.auth_token');

            $this->client = new Client($sid, $token);
        }

        return $this->client;
    }
}
