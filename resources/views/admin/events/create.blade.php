@extends('layouts.app')

@section('title', 'Criar Encontro')

@section('content')
<div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
    <div class="px-4 sm:px-0">
        <h1 class="text-3xl font-bold text-gray-900 mb-6 mt-6">Criar Novo Encontro</h1>

        <form action="{{ route('admin.events.store') }}" method="POST" enctype="multipart/form-data" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            @csrf

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                    Título *
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="title" name="title" type="text" value="{{ old('title') }}" required>
                @error('title')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                    Descrição
                </label>
                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="description" name="description" rows="4">{{ old('description') }}</textarea>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="image">
                    Imagem
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="image" name="image" type="file" accept="image/*">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="date">
                        Data e Hora *
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="date" name="date" type="datetime-local" value="{{ old('date') }}" required>
                    @error('date')
                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="city">
                        Cidade *
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="city" name="city" type="text" value="{{ old('city') }}" placeholder="Ex: Belo Horizonte" required>
                    @error('city')
                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="address">
                    Endereço Completo (SECRETO - só visível para confirmados)
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="address" name="address" type="text" value="{{ old('address') }}" placeholder="Rua, número, bairro...">
                <p class="text-xs text-red-500 mt-1">Este endereço só será exibido para participantes com status CONFIRMADO.</p>
                @error('address')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="arrival_time">
                        Horário de Chegada
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="arrival_time" name="arrival_time" type="text" value="{{ old('arrival_time') }}" placeholder="Ex: 19h30">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="capacity">
                        Capacidade (lugares na casa) *
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="capacity" name="capacity" type="number" min="1" value="{{ old('capacity') }}" required>
                    @error('capacity')
                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="status">
                    Status *
                </label>
                <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="status" name="status" required>
                    <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Rascunho</option>
                    <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Publicado</option>
                    <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                </select>
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('admin.events.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Cancelar
                </a>
                <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                    Criar Encontro
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
