import { EMPTY_FORM, toPayload, validateRegistration, type RegistrationForm } from '@/domain/registration-form';

const NOW = new Date('2026-11-08T12:00:00Z');

const valid: RegistrationForm = {
  full_name: 'Maria Velunora',
  cpf: '529.982.247-25',
  birth_date: '04/03/1992',
  city_neighborhood: 'Centro, Vitória',
  whatsapp: '(27) 99999-0000',
  instagram: '@maria',
  motivation: 'Quero viver esse encontro com o coração aberto.',
};

describe('validateRegistration', () => {
  it('formulário certo não tem erro', () => {
    expect(validateRegistration(valid, NOW)).toEqual({});
  });

  it('vazio pede cada campo com a mensagem da web', () => {
    expect(validateRegistration(EMPTY_FORM, NOW)).toEqual({
      full_name: 'Informe seu nome completo.',
      cpf: 'Informe seu CPF.',
      birth_date: 'Informe sua data de nascimento.',
      city_neighborhood: 'Informe seu bairro e cidade.',
      whatsapp: 'Informe seu WhatsApp.',
      motivation: 'Conte-nos sua história.',
    });
  });

  it('CPF incompleto e inválido', () => {
    expect(validateRegistration({ ...valid, cpf: '529.982' }, NOW).cpf).toBe('O CPF deve conter 11 dígitos.');
    expect(validateRegistration({ ...valid, cpf: '123.456.789-00' }, NOW).cpf).toBe('O CPF informado é inválido.');
  });

  it('data de nascimento', () => {
    expect(validateRegistration({ ...valid, birth_date: '31/02/1990' }, NOW).birth_date).toBe('Digite a data como DD/MM/AAAA.');
    expect(validateRegistration({ ...valid, birth_date: '01/01/2027' }, NOW).birth_date).toBe('A data de nascimento deve ser anterior a hoje.');
  });

  it('WhatsApp com DDD e história com 10 caracteres', () => {
    expect(validateRegistration({ ...valid, whatsapp: '99999-000' }, NOW).whatsapp).toBe('Informe o WhatsApp com DDD.');
    expect(validateRegistration({ ...valid, motivation: 'curta' }, NOW).motivation).toBe('Sua história deve ter pelo menos 10 caracteres.');
  });

  it('o Instagram é opcional', () => {
    expect(validateRegistration({ ...valid, instagram: '' }, NOW)).toEqual({});
  });
});

describe('toPayload', () => {
  it('manda a data como AAAA-MM-DD, aparado, com os termos aceitos e sem e-mail', () => {
    const payload = toPayload(3, { ...valid, full_name: '  Maria Velunora ' });

    expect(payload).toEqual({
      event_id: 3,
      full_name: 'Maria Velunora',
      cpf: '529.982.247-25',
      birth_date: '1992-03-04',
      city_neighborhood: 'Centro, Vitória',
      whatsapp: '(27) 99999-0000',
      instagram: '@maria',
      motivation: 'Quero viver esse encontro com o coração aberto.',
      terms_accepted: true,
    });
    expect(payload).not.toHaveProperty('email');
  });
});
