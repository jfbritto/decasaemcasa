@extends('layouts.app')

@section('title', 'Verificar E-mail')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Verifique seu e-mail
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Enviamos um link de verificação para seu endereço de e-mail
            </p>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-6">
            <div class="text-center mb-6">
                <svg class="mx-auto h-16 w-16 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>

            <p class="text-gray-700 mb-4 text-center">
                Antes de continuar, por favor verifique seu e-mail para um link de verificação.
            </p>

            <p class="text-gray-600 mb-6 text-sm text-center">
                Se você não recebeu o e-mail, clique no botão abaixo para reenviar.
            </p>

            @auth
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Reenviar E-mail de Verificação
                    </button>
                </form>
            @else
                <div class="text-center space-y-4">
                    <p class="text-gray-600 text-sm">
                        Você precisa estar logado para reenviar o e-mail de verificação.
                    </p>
                    <a href="{{ route('login') }}" class="inline-block w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Fazer Login
                    </a>
                </div>
            @endauth
        </div>

        <div class="text-center">
            <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-500 text-sm">
                Voltar para login
            </a>
        </div>
    </div>
</div>
@endsection
