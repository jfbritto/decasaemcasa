import { isValidCpf } from '@/domain/cpf';
import { brDateToIso, isPastIsoDate } from '@/domain/masks';
import type { InscriptionPayload } from '@/types/api';

// O formulário de inscrição do app (spec 2026-11-08-app-mobile-design,
// AM16): os campos da web menos o e-mail (o da conta). O app avisa antes de
// enviar o que a API recusaria; as mensagens são as do servidor
// (App\Inscriptions\InscriptionRules::messages), e a API confere de novo.

export type RegistrationForm = {
  full_name: string;
  cpf: string;
  /** DD/MM/AAAA, como a pessoa digita. */
  birth_date: string;
  city_neighborhood: string;
  whatsapp: string;
  instagram: string;
  motivation: string;
};

export type RegistrationErrors = Partial<Record<keyof RegistrationForm | 'terms_accepted', string>>;

export const EMPTY_FORM: RegistrationForm = {
  full_name: '',
  cpf: '',
  birth_date: '',
  city_neighborhood: '',
  whatsapp: '',
  instagram: '',
  motivation: '',
};

export function validateRegistration(form: RegistrationForm, now: Date = new Date()): RegistrationErrors {
  const errors: RegistrationErrors = {};

  if (!form.full_name.trim()) errors.full_name = 'Informe seu nome completo.';

  const cpfDigits = form.cpf.replace(/\D/g, '');
  if (!cpfDigits) errors.cpf = 'Informe seu CPF.';
  else if (cpfDigits.length !== 11) errors.cpf = 'O CPF deve conter 11 dígitos.';
  else if (!isValidCpf(cpfDigits)) errors.cpf = 'O CPF informado é inválido.';

  const birth = brDateToIso(form.birth_date);
  if (!form.birth_date.trim()) errors.birth_date = 'Informe sua data de nascimento.';
  else if (!birth) errors.birth_date = 'Digite a data como DD/MM/AAAA.';
  else if (!isPastIsoDate(birth, now)) errors.birth_date = 'A data de nascimento deve ser anterior a hoje.';

  if (!form.city_neighborhood.trim()) errors.city_neighborhood = 'Informe seu bairro e cidade.';

  const phone = form.whatsapp.replace(/\D/g, '');
  if (!phone) errors.whatsapp = 'Informe seu WhatsApp.';
  else if (phone.length < 10) errors.whatsapp = 'Informe o WhatsApp com DDD.';

  if (!form.motivation.trim()) errors.motivation = 'Conte-nos sua história.';
  else if (form.motivation.trim().length < 10) errors.motivation = 'Sua história deve ter pelo menos 10 caracteres.';

  return errors;
}

export function toPayload(eventId: number, form: RegistrationForm): InscriptionPayload {
  return {
    event_id: eventId,
    full_name: form.full_name.trim(),
    cpf: form.cpf.trim(),
    birth_date: brDateToIso(form.birth_date) ?? form.birth_date,
    city_neighborhood: form.city_neighborhood.trim(),
    whatsapp: form.whatsapp.trim(),
    instagram: form.instagram.trim(),
    motivation: form.motivation.trim(),
    terms_accepted: true,
  };
}
