@extends('layouts.app')

@section('title', 'Inscrições - Painel Admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Curadoria de Inscrições</h1>
            <a href="{{ route('admin.inscricoes.export-csv', request()->query()) }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Exportar CSV
            </a>
        </div>

        {{-- Contadores --}}
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow p-4 text-center">
                <p class="text-2xl font-bold text-gray-900">{{ $counts['total'] }}</p>
                <p class="text-sm text-gray-500">Total</p>
            </div>
            <div class="bg-yellow-50 rounded-xl shadow p-4 text-center border border-yellow-200">
                <p class="text-2xl font-bold text-yellow-700">{{ $counts['pendente'] }}</p>
                <p class="text-sm text-yellow-600">Pendentes</p>
            </div>
            <div class="bg-blue-50 rounded-xl shadow p-4 text-center border border-blue-200">
                <p class="text-2xl font-bold text-blue-700">{{ $counts['aprovado'] }}</p>
                <p class="text-sm text-blue-600">Aprovados</p>
            </div>
            <div class="bg-green-50 rounded-xl shadow p-4 text-center border border-green-200">
                <p class="text-2xl font-bold text-green-700">{{ $counts['confirmado'] }}</p>
                <p class="text-sm text-green-600">Confirmados</p>
            </div>
            <div class="bg-orange-50 rounded-xl shadow p-4 text-center border border-orange-200">
                <p class="text-2xl font-bold text-orange-700">{{ $counts['fila_de_espera'] }}</p>
                <p class="text-sm text-orange-600">Fila de Espera</p>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded-xl shadow p-4 mb-6">
            <form method="GET" action="{{ route('admin.inscricoes.index') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Buscar por nome, email ou CPF..."
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>
                <div class="sm:w-48">
                    <select name="city" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">Todas as cidades</option>
                        @foreach($cities as $city)
                            <option value="{{ $city }}" {{ request('city') === $city ? 'selected' : '' }}>{{ $city }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:w-40">
                    <select name="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">Todos os status</option>
                        <option value="pendente" {{ request('status') === 'pendente' ? 'selected' : '' }}>Pendente</option>
                        <option value="aprovado" {{ request('status') === 'aprovado' ? 'selected' : '' }}>Aprovado</option>
                        <option value="confirmado" {{ request('status') === 'confirmado' ? 'selected' : '' }}>Confirmado</option>
                        <option value="fila_de_espera" {{ request('status') === 'fila_de_espera' ? 'selected' : '' }}>Fila de Espera</option>
                        <option value="rejeitado" {{ request('status') === 'rejeitado' ? 'selected' : '' }}>Rejeitado</option>
                        <option value="cancelado" {{ request('status') === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
                    Filtrar
                </button>
                @if(request()->hasAny(['search', 'city', 'status']))
                    <a href="{{ route('admin.inscricoes.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium text-center">
                        Limpar
                    </a>
                @endif
            </form>
        </div>

        {{-- Lista de Inscrições --}}
        <div class="bg-white rounded-xl shadow overflow-hidden"
             x-data="{
                selectedIds: [],
                selectAll: false,
                toggleAll() {
                    if (this.selectAll) {
                        this.selectedIds = [{{ $inscriptions->pluck('id')->implode(',') }}];
                    } else {
                        this.selectedIds = [];
                    }
                },
                get hasSelection() { return this.selectedIds.length > 0; }
             }">

            {{-- Barra de ações em lote --}}
            <div x-show="hasSelection" x-transition class="bg-indigo-50 border-b border-indigo-200 px-4 py-3 flex items-center justify-between">
                <span class="text-sm text-indigo-700 font-medium" x-text="selectedIds.length + ' inscrição(ões) selecionada(s)'"></span>
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('admin.inscricoes.bulk-action') }}" class="inline"
                          x-on:submit.prevent="if(confirm('Confirma a ação em lote?')) { $el.submit() }">
                        @csrf
                        <input type="hidden" name="action" value="aprovar">
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="inscription_ids[]" :value="id">
                        </template>
                        <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700">
                            Aprovar Selecionados
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.inscricoes.bulk-action') }}" class="inline"
                          x-on:submit.prevent="if(confirm('Confirma a ação em lote?')) { $el.submit() }">
                        @csrf
                        <input type="hidden" name="action" value="fila_espera">
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="inscription_ids[]" :value="id">
                        </template>
                        <button type="submit" class="px-3 py-1.5 bg-orange-500 text-white text-xs font-medium rounded-lg hover:bg-orange-600">
                            Mover p/ Fila
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.inscricoes.bulk-action') }}" class="inline"
                          x-on:submit.prevent="if(confirm('Tem certeza que deseja rejeitar as inscrições selecionadas? Os participantes serão notificados.')) { $el.submit() }">
                        @csrf
                        <input type="hidden" name="action" value="rejeitar">
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="inscription_ids[]" :value="id">
                        </template>
                        <button type="submit" class="px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700">
                            Rejeitar Selecionados
                        </button>
                    </form>
                </div>
            </div>

            @if($inscriptions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">
                                    <input type="checkbox" x-model="selectAll" @change="toggleAll()"
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <a href="{{ route('admin.inscricoes.index', array_merge(request()->query(), ['sort' => 'full_name', 'direction' => request('sort') === 'full_name' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}"
                                       class="hover:text-indigo-600 flex items-center gap-1">
                                        Nome
                                        @if(request('sort') === 'full_name')
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="{{ request('direction') === 'asc' ? 'M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z' : 'M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' }}" clip-rule="evenodd"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Encontro</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">WhatsApp</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <a href="{{ route('admin.inscricoes.index', array_merge(request()->query(), ['sort' => 'status', 'direction' => request('sort') === 'status' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}"
                                       class="hover:text-indigo-600 flex items-center gap-1">
                                        Status
                                        @if(request('sort') === 'status')
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="{{ request('direction') === 'asc' ? 'M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z' : 'M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' }}" clip-rule="evenodd"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Comprovante</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    <a href="{{ route('admin.inscricoes.index', array_merge(request()->query(), ['sort' => 'created_at', 'direction' => request('sort') === 'created_at' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}"
                                       class="hover:text-indigo-600 flex items-center gap-1">
                                        Data
                                        @if(request('sort') === 'created_at' || !request('sort'))
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="{{ request('direction', 'desc') === 'asc' ? 'M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z' : 'M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' }}" clip-rule="evenodd"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($inscriptions as $inscription)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" value="{{ $inscription->id }}"
                                               x-model.number="selectedIds"
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-4 py-3">
                                        <div>
                                            <a href="{{ route('admin.inscricoes.show', $inscription) }}" class="font-medium text-indigo-600 hover:text-indigo-800">
                                                {{ $inscription->full_name }}
                                            </a>
                                            <p class="text-xs text-gray-500">{{ $inscription->email }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ route('admin.events.show', $inscription->event) }}" class="font-medium text-indigo-600 hover:text-indigo-800">
                                            {{ $inscription->event->city ?? $inscription->event->title }}
                                        </a>
                                        <p class="text-xs text-gray-500">{{ $inscription->event->date->format('d/m/Y') }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $inscription->whatsapp }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($inscription->isPending()) bg-yellow-100 text-yellow-800
                                            @elseif($inscription->isApproved()) bg-blue-100 text-blue-800
                                            @elseif($inscription->isConfirmed()) bg-green-100 text-green-800
                                            @elseif($inscription->isWaitlisted()) bg-orange-100 text-orange-800
                                            @elseif($inscription->isRejected()) bg-red-100 text-red-800
                                            @elseif($inscription->isCancelled()) bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $inscription->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($inscription->payment_proof)
                                            <span class="inline-flex items-center text-green-600">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                Enviado
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        {{ $inscription->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            @if($inscription->isPending())
                                                <form method="POST" action="{{ route('admin.inscricoes.aprovar', $inscription) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700" title="Aprovar">
                                                        Aprovar
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.inscricoes.fila-espera', $inscription) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1 bg-orange-500 text-white text-xs font-medium rounded-lg hover:bg-orange-600" title="Fila de Espera">
                                                        Fila
                                                    </button>
                                                </form>
                                            @elseif($inscription->isApproved() && $inscription->payment_proof)
                                                <form method="POST" action="{{ route('admin.inscricoes.confirmar', $inscription) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700" title="Confirmar Pagamento">
                                                        Confirmar
                                                    </button>
                                                </form>
                                            @elseif($inscription->isWaitlisted())
                                                <form method="POST" action="{{ route('admin.inscricoes.aprovar', $inscription) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700" title="Aprovar da Fila">
                                                        Aprovar
                                                    </button>
                                                </form>
                                            @endif
                                            <a href="{{ route('admin.inscricoes.show', $inscription) }}" class="px-3 py-1 border border-gray-300 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50" title="Ver detalhes">
                                                Ver
                                            </a>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Motivação expandível --}}
                                <tr x-data="{ showMotivation: false }">
                                    <td colspan="8" class="px-4 py-0">
                                        <button @click="showMotivation = !showMotivation"
                                                class="text-xs text-indigo-500 hover:text-indigo-700 py-1 flex items-center">
                                            <svg class="w-3 h-3 mr-1 transition-transform" :class="showMotivation ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                            <span x-text="showMotivation ? 'Ocultar história' : 'Ler história'"></span>
                                        </button>
                                        <div x-show="showMotivation" x-collapse class="pb-3">
                                            <div class="bg-amber-50 rounded-lg p-3 text-sm text-gray-700 italic border-l-3 border-amber-400">
                                                "{{ $inscription->motivation }}"
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t">
                    {{ $inscriptions->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="mt-4 text-gray-500">Nenhuma inscrição encontrada.</p>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
