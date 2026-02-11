@extends('layouts.app')

@section('title', 'Editar Encontro')

@section('content')
<div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
    <div class="px-4 sm:px-0">
        <h1 class="text-3xl font-bold text-gray-900 mb-6 mt-6">Editar Encontro</h1>

        <form action="{{ route('admin.events.update', $event) }}" method="POST" enctype="multipart/form-data" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                    Título *
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="title" name="title" type="text" value="{{ old('title', $event->title) }}" required>
                @error('title')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                    Descrição
                </label>
                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="description" name="description" rows="4">{{ old('description', $event->description) }}</textarea>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="image">
                    Imagem
                </label>
                @if($event->image)
                    <div class="mb-3">
                        <img src="{{ asset('storage/' . $event->image) }}"
                             alt="Imagem atual"
                             class="w-40 h-28 object-cover rounded-lg border">
                        <p class="text-xs text-gray-500 mt-1">Envie outra imagem para substituir.</p>
                    </div>
                @endif
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="image" name="image" type="file" accept="image/*">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="date">
                        Data e Hora *
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="date" name="date" type="datetime-local" value="{{ old('date', $event->date->format('Y-m-d\TH:i')) }}" required>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="city">
                        Cidade *
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="city" name="city" type="text" value="{{ old('city', $event->city) }}" placeholder="Ex: Belo Horizonte" required>
                    @error('city')
                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="address">
                    Endereço Completo (SECRETO - só visível para confirmados)
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="address" name="address" type="text" value="{{ old('address', $event->full_address) }}" placeholder="Rua, número, bairro...">
                <p class="text-xs text-red-500 mt-1">Este endereço só será exibido para participantes com status CONFIRMADO.</p>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="arrival_time">
                        Horário de Chegada
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="arrival_time" name="arrival_time" type="text" value="{{ old('arrival_time', $event->arrival_time) }}" placeholder="Ex: 19h30">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="capacity">
                        Capacidade (lugares na casa) *
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="capacity" name="capacity" type="number" min="1" value="{{ old('capacity', $event->capacity) }}" required>
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
                    <option value="draft" {{ old('status', $event->status) === 'draft' ? 'selected' : '' }}>Rascunho</option>
                    <option value="published" {{ old('status', $event->status) === 'published' ? 'selected' : '' }}>Publicado</option>
                    <option value="cancelled" {{ old('status', $event->status) === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                    <option value="finished" {{ old('status', $event->status) === 'finished' ? 'selected' : '' }}>Finalizado</option>
                </select>
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('admin.events.show', $event) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Voltar
                </a>
                <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                    Atualizar Encontro
                </button>
            </div>
        </form>

        {{-- Zona de Perigo --}}
        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4 border-t-4 border-red-500">
            <h2 class="text-lg font-bold text-red-700 mb-2">Zona de Perigo</h2>
            <p class="text-sm text-gray-600 mb-4">Excluir este encontro removerá todas as inscrições associadas. Esta ação não pode ser desfeita.</p>
            <form method="POST" action="{{ route('admin.events.destroy', $event) }}"
                  x-data x-on:submit.prevent="Swal.fire({ title: 'Tem certeza?', text: 'O encontro {{ $event->city ?? $event->title }} e todas as inscrições serão excluídos permanentemente.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280', confirmButtonText: 'Sim, excluir', cancelButtonText: 'Cancelar' }).then((result) => { if (result.isConfirmed) $el.submit() })">
                @csrf
                @method('DELETE')
                <button type="submit" style="background-color:#dc2626;color:#fff;" class="hover:opacity-80 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Excluir Encontro
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
