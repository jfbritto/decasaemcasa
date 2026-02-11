@extends('layouts.app')

@section('title', 'Inscreva-se - De Casa em Casa')
@section('og_title', 'Inscreva-se - De Casa em Casa')
@section('og_description', 'Inscreva-se para participar de um encontro único. Uma turnê que acontece dentro de casas reais, com pessoas reais.')
@section('meta_description', 'Inscreva-se para participar de um encontro único da turnê De Casa em Casa. Música ao vivo em lares reais.')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-amber-50 to-white py-8" x-data="inscriptionForm()">

    {{-- Header --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">De Casa em Casa</h1>
        <p class="text-lg text-gray-600">Turnê</p>
    </div>

    {{-- Progress Steps --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 mb-8">
        <div class="flex items-center justify-center space-x-2 sm:space-x-4">
            <template x-for="(stepName, index) in ['Local', 'Manifesto', 'Dados', 'Termos']" :key="index">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold transition-colors duration-300"
                         :class="step > index + 1 ? 'bg-green-500 text-white' : (step === index + 1 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500')">
                        <template x-if="step > index + 1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </template>
                        <template x-if="step <= index + 1">
                            <span x-text="index + 1"></span>
                        </template>
                    </div>
                    <span class="ml-1 text-xs sm:text-sm font-medium hidden sm:inline"
                          :class="step === index + 1 ? 'text-indigo-600' : 'text-gray-500'"
                          x-text="stepName"></span>
                    <template x-if="index < 3">
                        <div class="w-8 sm:w-12 h-0.5 mx-1 sm:mx-2" :class="step > index + 1 ? 'bg-green-500' : 'bg-gray-200'"></div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <form method="POST" action="{{ route('inscricao.store') }}" @submit="handleSubmit($event)">
        @csrf

        {{-- Etapa 1: Seleção de Cidade/Data --}}
        <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Onde e quando nos encontramos?</h2>
                    <p class="text-gray-600 mb-6">Selecione a cidade e data do encontro que deseja participar.</p>

                    @if($events->count() > 0)
                        <div class="space-y-3">
                            @foreach($events as $event)
                                <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200 hover:border-indigo-300 hover:bg-indigo-50"
                                       :class="formData.event_id == '{{ $event->id }}' ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200' : 'border-gray-200'">
                                    {{-- Ícone casinha --}}
                                    <div class="w-14 h-14 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-7 h-7 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                        </svg>
                                    </div>
                                    <input type="radio" name="event_id" value="{{ $event->id }}"
                                           x-model="formData.event_id"
                                           class="h-5 w-5 text-indigo-600 border-gray-300 focus:ring-indigo-500 ml-4 flex-shrink-0">
                                    <div class="ml-4 flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-lg font-semibold text-gray-900">
                                                {{ $event->city ?? $event->title }}
                                            </span>
                                            <span class="text-sm font-medium text-indigo-600">
                                                {{ $event->date->format('d/M') }}
                                            </span>
                                        </div>
                                        @if($event->title && $event->city && $event->title !== $event->city)
                                            <p class="text-sm text-gray-500 mt-1">{{ $event->title }}</p>
                                        @endif
                                        @if($event->capacity > 0)
                                            <p class="text-xs mt-1 {{ $event->isFull() ? 'text-red-500' : 'text-gray-400' }}">
                                                @if($event->isFull())
                                                    Vagas esgotadas
                                                @else
                                                    {{ $event->available_spots }} {{ $event->available_spots === 1 ? 'vaga' : 'vagas' }} restante{{ $event->available_spots === 1 ? '' : 's' }}
                                                @endif
                                            </p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="mt-4 text-gray-500">Nenhum encontro disponível no momento.</p>
                        </div>
                    @endif

                    @error('event_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="nextStep()" :disabled="!formData.event_id"
                                class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                            Continuar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Etapa 2: Manifesto --}}
        <div x-show="step === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 mb-4">
                            <svg class="w-8 h-8 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">De Casa em Casa</h2>
                    </div>

                    <blockquote class="border-l-4 border-amber-400 pl-6 py-4 bg-amber-50 rounded-r-xl">
                        <p class="text-lg text-gray-700 italic leading-relaxed">
                            "De Casa em Casa é uma turnê que acontece onde a vida acontece. Dentro de casas reais, com pessoas reais, criando um encontro inédito e poderoso: uma música familiar, na sua expressão mais espontânea, ao vivo, autêntica, dentro da experiência mais sagrada que existe, na bênção de estar em família."
                        </p>
                    </blockquote>

                    <div class="mt-8 flex justify-between">
                        <button type="button" @click="prevStep()"
                                class="px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-all duration-200">
                            Voltar
                        </button>
                        <button type="button" @click="nextStep()"
                                class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-all duration-200">
                            Continuar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Etapa 3: Dados do Participante --}}
        <div x-show="step === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Seus dados</h2>
                    <p class="text-gray-600 mb-6">Preencha com atenção. Essas informações são necessárias para a curadoria e logística.</p>

                    <div class="space-y-5">
                        {{-- Nome Completo --}}
                        <div>
                            <label for="full_name" class="block text-sm font-semibold text-gray-700 mb-1">Nome Completo *</label>
                            <input type="text" name="full_name" id="full_name" x-model="formData.full_name"
                                   class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-4 py-3"
                                   placeholder="Seu nome completo" value="{{ old('full_name') }}">
                            @error('full_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- CPF --}}
                        <div>
                            <label for="cpf" class="block text-sm font-semibold text-gray-700 mb-1">CPF *</label>
                            <input type="text" name="cpf" id="cpf" x-model="formData.cpf"
                                   @input="maskCpf($event)"
                                   maxlength="14"
                                   class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-4 py-3"
                                   placeholder="000.000.000-00" value="{{ old('cpf') }}">
                            <p class="mt-1 text-xs text-gray-500">Para segurança e lista na porta da casa.</p>
                            @error('cpf')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Data de Nascimento --}}
                        <div>
                            <label for="birth_date" class="block text-sm font-semibold text-gray-700 mb-1">Data de Nascimento *</label>
                            <input type="date" name="birth_date" id="birth_date" x-model="formData.birth_date"
                                   class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-4 py-3"
                                   value="{{ old('birth_date') }}">
                            @error('birth_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Bairro/Cidade --}}
                        <div>
                            <label for="city_neighborhood" class="block text-sm font-semibold text-gray-700 mb-1">Bairro / Cidade *</label>
                            <input type="text" name="city_neighborhood" id="city_neighborhood" x-model="formData.city_neighborhood"
                                   class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-4 py-3"
                                   placeholder="Ex: Savassi, Belo Horizonte" value="{{ old('city_neighborhood') }}">
                            <p class="mt-1 text-xs text-gray-500">Essencial para a logística da turnê.</p>
                            @error('city_neighborhood')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            {{-- WhatsApp --}}
                            <div>
                                <label for="whatsapp" class="block text-sm font-semibold text-gray-700 mb-1">WhatsApp *</label>
                                <input type="text" name="whatsapp" id="whatsapp" x-model="formData.whatsapp"
                                       @input="maskWhatsApp($event)"
                                       maxlength="15"
                                       class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-4 py-3"
                                       placeholder="(00) 00000-0000" value="{{ old('whatsapp') }}">
                                <p class="mt-1 text-xs text-gray-500">Onde receberá o feedback.</p>
                                @error('whatsapp')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- E-mail --}}
                            <div>
                                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">E-mail *</label>
                                <input type="email" name="email" id="email" x-model="formData.email"
                                       class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-4 py-3"
                                       placeholder="seu@email.com" value="{{ old('email') }}">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Instagram --}}
                        <div>
                            <label for="instagram" class="block text-sm font-semibold text-gray-700 mb-1">Instagram</label>
                            <input type="text" name="instagram" id="instagram" x-model="formData.instagram"
                                   class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-4 py-3"
                                   placeholder="@seuusuario" value="{{ old('instagram') }}">
                            @error('instagram')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Motivação / História --}}
                        <div>
                            <label for="motivation" class="block text-sm font-semibold text-gray-700 mb-1">Sua História: Por que você quer participar? *</label>
                            <textarea name="motivation" id="motivation" x-model="formData.motivation"
                                      rows="5"
                                      class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-4 py-3"
                                      placeholder="Conte-nos um pouco sobre você e por que deseja fazer parte desse encontro...">{{ old('motivation') }}</textarea>
                            @error('motivation')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-8 flex justify-between">
                        <button type="button" @click="prevStep()"
                                class="px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-all duration-200">
                            Voltar
                        </button>
                        <button type="button" @click="nextStep()" :disabled="!isStep3Valid()"
                                class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                            Continuar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Etapa 4: Termos --}}
        <div x-show="step === 4" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Termo de Responsabilidade</h2>
                    <p class="text-gray-600 mb-6">Leia com atenção antes de confirmar sua inscrição.</p>

                    <div class="bg-gray-50 rounded-xl p-6 mb-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Termos e Consentimento "De Casa em Casa":</h3>
                        <ul class="space-y-4 text-gray-700 text-sm leading-relaxed">
                            <li class="flex items-start">
                                <span class="inline-block w-2 h-2 bg-amber-400 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                <span><strong>Conduta:</strong> Declaro que as informações são verdadeiras e estou ciente de que o evento ocorre em ambiente familiar, comprometendo-me com os valores éticos e morais e as regras da casa.</span>
                            </li>
                            <li class="flex items-start">
                                <span class="inline-block w-2 h-2 bg-amber-400 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                <span><strong>Imagem e Som:</strong> Autorizo o uso gratuito de minha imagem e voz captadas no evento para conteúdos da Turnê e do App.</span>
                            </li>
                            <li class="flex items-start">
                                <span class="inline-block w-2 h-2 bg-amber-400 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                <span><strong>Privacidade (LGPD):</strong> Autorizo o tratamento dos meus dados para logística, segurança e comunicações da turnê.</span>
                            </li>
                        </ul>
                    </div>

                    <label class="flex items-start p-4 border-2 rounded-xl cursor-pointer transition-all duration-200"
                           :class="formData.terms_accepted ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                        <input type="checkbox" name="terms_accepted" value="1" x-model="formData.terms_accepted"
                               class="h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 mt-0.5">
                        <span class="ml-3 text-sm text-gray-700 leading-relaxed">
                            Declaro que as informações são verdadeiras. Estou ciente de que o evento ocorre em ambiente familiar, comprometendo-me com os valores éticos e morais e as regras da casa. Autorizo o uso de minha imagem/voz e o tratamento de dados conforme a LGPD.
                        </span>
                    </label>

                    @error('terms_accepted')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="mt-8 flex justify-between">
                        <button type="button" @click="prevStep()"
                                class="px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-all duration-200">
                            Voltar
                        </button>
                        <button type="submit" :disabled="!formData.terms_accepted"
                                class="px-8 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                            Enviar Inscrição
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function inscriptionForm() {
    return {
        step: {{ $errors->any() ? 3 : 1 }},
        formData: {
            event_id: '{{ old("event_id", "") }}',
            full_name: '{{ old("full_name", "") }}',
            cpf: '{{ old("cpf", "") }}',
            birth_date: '{{ old("birth_date", "") }}',
            city_neighborhood: '{{ old("city_neighborhood", "") }}',
            whatsapp: '{{ old("whatsapp", "") }}',
            email: '{{ old("email", "") }}',
            instagram: '{{ old("instagram", "") }}',
            motivation: `{!! addslashes(old("motivation", "")) !!}`,
            terms_accepted: {{ old("terms_accepted") ? 'true' : 'false' }},
        },

        nextStep() {
            if (this.step < 4) this.step++;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        prevStep() {
            if (this.step > 1) this.step--;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        isStep3Valid() {
            return this.formData.full_name.trim() !== '' &&
                   this.formData.cpf.trim() !== '' &&
                   this.formData.birth_date.trim() !== '' &&
                   this.formData.city_neighborhood.trim() !== '' &&
                   this.formData.whatsapp.trim() !== '' &&
                   this.formData.email.trim() !== '' &&
                   this.formData.motivation.trim().length >= 10;
        },

        maskCpf(event) {
            let value = event.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            if (value.length > 9) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
            } else if (value.length > 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
            } else if (value.length > 3) {
                value = value.replace(/(\d{3})(\d{1,3})/, '$1.$2');
            }
            this.formData.cpf = value;
            event.target.value = value;
        },

        maskWhatsApp(event) {
            let value = event.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            if (value.length > 6) {
                value = value.replace(/(\d{2})(\d{5})(\d{1,4})/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/(\d{2})(\d{1,5})/, '($1) $2');
            }
            this.formData.whatsapp = value;
            event.target.value = value;
        },

        handleSubmit(event) {
            if (!this.formData.terms_accepted) {
                event.preventDefault();
                return false;
            }
            return true;
        }
    };
}
</script>
@endsection
