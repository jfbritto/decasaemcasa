<?php

namespace Tests\Unit;

use App\Inscriptions\InscriptionRules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Os casos do CPF divididos com o app (tests/fixtures/cpf-cases.json, spec
 * 2026-11-08-app-mobile-design, AM30): o app nunca aceita o que a API recusa.
 */
class CpfCasesTest extends TestCase
{
    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function cases(): iterable
    {
        $fixture = json_decode((string) file_get_contents(__DIR__.'/../fixtures/cpf-cases.json'), true, flags: JSON_THROW_ON_ERROR);

        foreach ($fixture['cases'] as $case) {
            yield ($case['input'] === '' ? '(vazio)' : $case['input']) => [$case['input'], $case['valid']];
        }
    }

    #[DataProvider('cases')]
    public function test_validacao_do_cpf(string $input, bool $valid): void
    {
        $this->assertSame($valid, InscriptionRules::validCpf($input));
    }
}
