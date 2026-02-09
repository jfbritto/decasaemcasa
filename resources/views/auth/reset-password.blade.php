@extends('layouts.app')

@section('title', 'Redefinir senha')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Redefinir senha
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Digite sua nova senha abaixo
            </p>
        </div>

        <form class="mt-8 space-y-6" action="{{ route('password.store') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">E-mail</label>
                    <input 
                        id="email" 
                        type="email" 
                        value="{{ $email }}"
                        disabled
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 bg-gray-100 text-gray-500 rounded-md sm:text-sm"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Nova Senha</label>
                    <input 
                        id="password" 
                        name="password" 
                        type="password" 
                        required 
                        autofocus
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                        placeholder="Digite sua nova senha"
                    >
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirmar Nova Senha</label>
                    <input 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        type="password" 
                        required 
                        class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                        placeholder="Confirme sua nova senha"
                    >
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Redefinir Senha
                </button>
            </div>

            <div class="text-center">
                <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-500 text-sm">
                    Voltar para login
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
