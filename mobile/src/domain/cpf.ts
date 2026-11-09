// Validação do CPF: a única regra que o app também aplica (spec
// 2026-11-08-app-mobile-design, AM30), para avisar antes de enviar. A mesma
// do App\Inscriptions\InscriptionRules::validCpf: os dois lados passam pelos
// casos de tests/fixtures/cpf-cases.json.

export function cleanCpf(value: string): string {
  return value.replace(/\D/g, '');
}

export function isValidCpf(value: string): boolean {
  const cpf = cleanCpf(value);
  if (cpf.length !== 11) return false;
  // Todos os dígitos iguais são inválidos.
  if (/^(\d)\1{10}$/.test(cpf)) return false;

  const digit = (length: number): number => {
    let sum = 0;
    for (let i = 0; i < length; i++) sum += Number(cpf[i]) * (length + 1 - i);
    const remainder = sum % 11;
    return remainder < 2 ? 0 : 11 - remainder;
  };

  return digit(9) === Number(cpf[9]) && digit(10) === Number(cpf[10]);
}
