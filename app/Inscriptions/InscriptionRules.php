<?php

namespace App\Inscriptions;

/**
 * As regras dos dados de uma inscrição, as mesmas na web e no app (spec
 * 2026-11-08-app-mobile-design, AM16 e AM17). Saíram do InscriptionController
 * sem mudar nenhuma mensagem.
 *
 * A validação do CPF é a única regra que o app também aplica (AM30): os dois
 * lados leem os casos de tests/fixtures/cpf-cases.json.
 */
final class InscriptionRules
{
    /**
     * Regras dos dados do participante. No app o e-mail é o da conta, já
     * conferido pelo código de acesso: lá ele não é pedido ($withEmail false).
     *
     * @return array<string, mixed>
     */
    public static function rules(bool $withEmail = true): array
    {
        $rules = [
            'event_id' => 'required|exists:events,id',
            'full_name' => 'required|string|max:255',
            'cpf' => ['required', 'string', 'max:14', function ($attribute, $value, $fail) {
                $cpf = self::cleanCpf((string) $value);
                if (strlen($cpf) !== 11) {
                    $fail('O CPF deve conter 11 dígitos.');

                    return;
                }
                if (! self::validCpf($cpf)) {
                    $fail('O CPF informado é inválido.');
                }
            }],
            'birth_date' => 'required|date|before:today',
            'city_neighborhood' => 'required|string|max:255',
            'whatsapp' => 'required|string|max:20',
            'email' => ['required', 'email', 'max:255', function ($attribute, $value, $fail) {
                $suggestion = self::emailTypoSuggestion($value);
                if ($suggestion) {
                    $fail("Verifique seu e-mail. Você quis dizer {$suggestion}?");

                    return;
                }
                if (! app(MailDomainChecker::class)->acceptsMail($value)) {
                    $fail('O domínio deste e-mail não recebe mensagens. Verifique se digitou corretamente.');
                }
            }],
            'instagram' => 'nullable|string|max:255',
            'motivation' => 'required|string|min:10',
            'terms_accepted' => 'required|accepted',
        ];

        if (! $withEmail) {
            unset($rules['email']);
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'event_id.required' => 'Selecione o encontro que deseja participar.',
            'event_id.exists' => 'O encontro selecionado não existe.',
            'full_name.required' => 'Informe seu nome completo.',
            'cpf.required' => 'Informe seu CPF.',
            'birth_date.required' => 'Informe sua data de nascimento.',
            'birth_date.before' => 'A data de nascimento deve ser anterior a hoje.',
            'city_neighborhood.required' => 'Informe seu bairro e cidade.',
            'whatsapp.required' => 'Informe seu WhatsApp.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'motivation.required' => 'Conte-nos sua história.',
            'motivation.min' => 'Sua história deve ter pelo menos 10 caracteres.',
            'terms_accepted.required' => 'Você deve aceitar os termos.',
            'terms_accepted.accepted' => 'Você deve aceitar os termos.',
        ];
    }

    public static function cleanCpf(string $cpf): string
    {
        return (string) preg_replace('/\D/', '', $cpf);
    }

    /**
     * Dígitos verificadores do CPF.
     */
    public static function validCpf(string $cpf): bool
    {
        $cpf = self::cleanCpf($cpf);

        if (strlen($cpf) !== 11) {
            return false;
        }

        // CPFs com todos os dígitos iguais são inválidos
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        if ((int) $cpf[9] !== $digit1) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        return (int) $cpf[10] === $digit2;
    }

    /**
     * Erro comum no domínio do e-mail: devolve o e-mail corrigido, ou null.
     */
    public static function emailTypoSuggestion(?string $email): ?string
    {
        if (! $email || ! str_contains($email, '@')) {
            return null;
        }

        [$local, $domain] = explode('@', strtolower(trim($email)), 2);
        if (! $local || ! $domain) {
            return null;
        }

        $typoMap = [
            'gmail.com.br' => 'gmail.com',
            'gmail.con' => 'gmail.com',
            'gmail.cm' => 'gmail.com',
            'gmial.com' => 'gmail.com',
            'gmal.com' => 'gmail.com',
            'gmaill.com' => 'gmail.com',
            'gnail.com' => 'gmail.com',
            'gemail.com' => 'gmail.com',
            'hotmail.com.br' => 'hotmail.com',
            'hotmail.con' => 'hotmail.com',
            'hotmial.com' => 'hotmail.com',
            'hotmal.com' => 'hotmail.com',
            'hotmai.com' => 'hotmail.com',
            'hormail.com' => 'hotmail.com',
            'yahoo.con' => 'yahoo.com.br',
            'yhoo.com' => 'yahoo.com',
            'yhaoo.com' => 'yahoo.com',
            'outlook.con' => 'outlook.com',
            'outlok.com' => 'outlook.com',
            'outloook.com' => 'outlook.com',
            'icloud.con' => 'icloud.com',
            'iclound.com' => 'icloud.com',
        ];

        if (isset($typoMap[$domain])) {
            return $local.'@'.$typoMap[$domain];
        }

        // Terminação .con (no lugar de .com)
        if (str_ends_with($domain, '.con')) {
            return $local.'@'.substr($domain, 0, -4).'.com';
        }

        // Terminação .cm (no lugar de .com)
        if (str_ends_with($domain, '.cm') && ! str_ends_with($domain, '.com')) {
            return $local.'@'.substr($domain, 0, -3).'.com';
        }

        return null;
    }
}
