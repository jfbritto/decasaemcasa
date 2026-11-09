<?php

namespace App\Notifications\Push;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;

/**
 * A API HTTP do serviço de push do Expo (spec 2026-11-08-app-mobile-design,
 * AM23), copiada do HelpCheck (App\Notifications\Push\ExpoPushClient):
 *
 * - POST de até 100 mensagens; a resposta traz um ticket por mensagem, na
 *   ordem enviada ({status: ok, id} ou {status: error, details: {error}});
 * - com a "enhanced push security" da conta do Expo, cada pedido leva
 *   Authorization: Bearer EXPO_ACCESS_TOKEN (vazio, vai sem);
 * - 429 e 5xx são passageiros e são tentados de novo; erro de rede no envio
 *   não (o Expo pode ter recebido, e o mesmo push chegaria duas vezes).
 *
 * Nunca lança para o chamador: falha vira log (sem token nem texto) e o lote
 * fica sem tickets. Em teste, sem Http::preventStrayRequests() é
 * LogicException: um teste que esqueceu o Http::fake() falha em vez de
 * chamar o exp.host.
 */
final class ExpoPushClient
{
    public const SEND_URL = 'https://exp.host/--/api/v2/push/send';

    public const MESSAGES_PER_REQUEST = 100;

    public const BACKOFF_MS = [1000, 4000];

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>> o ticket de cada mensagem, pelo índice em $messages
     */
    public function send(array $messages): array
    {
        $messages = array_values($messages);
        $tickets = [];

        foreach (array_chunk($messages, self::MESSAGES_PER_REQUEST, preserve_keys: true) as $batch) {
            $answer = $this->post(array_values($batch));
            $data = $answer['data'] ?? null;

            if (! is_array($data) || ! array_is_list($data) || count($data) !== count($batch)) {
                if ($answer !== null) {
                    Log::warning('Expo push: a resposta não tem um ticket por mensagem.', [
                        'messages' => count($batch),
                        'tickets' => is_array($data) ? count($data) : null,
                    ]);
                }

                continue;
            }

            foreach (array_keys($batch) as $position => $index) {
                $tickets[$index] = is_array($data[$position]) ? $data[$position] : [];
            }
        }

        return $tickets;
    }

    /**
     * @param  list<array<string, mixed>>  $body
     * @return array<mixed>|null
     */
    private function post(array $body): ?array
    {
        if (app()->runningUnitTests() && ! self::strayRequestsPrevented()) {
            throw new LogicException('Teste nunca chama o push do Expo: Http::preventStrayRequests() e Http::fake() antes.');
        }

        $accessToken = config('services.expo.access_token');

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->when(is_string($accessToken) && $accessToken !== '', fn ($request) => $request->withToken($accessToken))
                ->connectTimeout(5)
                ->timeout(15)
                ->retry(self::BACKOFF_MS, when: fn (Throwable $error) => self::temporary($error), throw: false)
                ->post(self::SEND_URL, $body);
        } catch (ConnectionException $error) {
            Log::warning('Expo push: o serviço não respondeu.', ['error' => $error->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Expo push: o serviço recusou o pedido.', ['status' => $response->status()]);

            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : null;
    }

    /**
     * O Http::preventStrayRequests() está ligado? O Laravel 10 não tem o
     * getter (preventingStrayRequests veio no 11): lê a flag do Factory.
     */
    private static function strayRequestsPrevented(): bool
    {
        $factory = Http::getFacadeRoot();

        return (bool) (fn () => $this->preventStrayRequests ?? false)->call($factory);
    }

    private static function temporary(Throwable $error): bool
    {
        if ($error instanceof RequestException) {
            $status = $error->response->status();

            return $status === 429 || $status >= 500;
        }

        return false;
    }
}
