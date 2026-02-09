@extends('layouts.app')

@section('title', 'Esqueci minha senha')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Esqueceu sua senha?
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Digite seu e-mail e enviaremos um link para redefinir sua senha
            </p>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                {{ session('success') }}
            </div>
        @endif

        <form class="mt-8 space-y-6" action="{{ route('password.email') }}" method="POST">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">E-mail</label>
                <input 
                    id="email" 
                    name="email" 
                    type="email" 
                    required 
                    autofocus
                    value="{{ old('email') }}"
                    class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                    placeholder="seu@email.com"
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Enviar Link de Redefinição
                </button>
            </div>

            <div class="text-center space-y-2">
                <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-500 text-sm">
                    Voltar para login
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
