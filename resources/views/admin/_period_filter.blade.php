<div class="bg-indigo-50 border-b border-indigo-100" x-data="{
    period: '{{ $adminPeriodFilter->getPeriod() }}',
    start: '{{ $adminPeriodFilter->getStartString() ?? '' }}',
    end: '{{ $adminPeriodFilter->getEndString() ?? '' }}',
    submit() { this.$refs.form.submit(); }
}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5">
        <form method="POST" action="{{ route('admin.filtro-periodo') }}" x-ref="form" class="flex flex-wrap items-center gap-3">
            @csrf

            <div class="flex items-center gap-2 text-sm">
                <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="font-semibold text-indigo-900">Período:</span>
            </div>

            <select name="period" x-model="period"
                    @change="if (period !== 'personalizado') submit()"
                    class="rounded-lg border-indigo-200 bg-white text-sm font-medium text-indigo-900 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 pl-3 pr-8">
                @foreach($adminPeriodFilter->getOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <div x-show="period === 'personalizado'" x-transition class="flex flex-wrap items-center gap-2">
                <input type="date" name="start_date" x-model="start"
                       class="rounded-lg border-indigo-200 bg-white text-sm py-1.5 px-2">
                <span class="text-indigo-700 text-sm">até</span>
                <input type="date" name="end_date" x-model="end"
                       class="rounded-lg border-indigo-200 bg-white text-sm py-1.5 px-2">
                <button type="submit"
                        class="px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                    Aplicar
                </button>
            </div>

            <span class="text-xs text-indigo-700/80 hidden sm:inline">
                {{ $adminPeriodFilter->getRangeText() }}
            </span>

            @if(! $adminPeriodFilter->isDefault())
                <button type="button"
                        @click="period = '30d'; start = ''; end = ''; submit()"
                        class="ml-auto text-xs text-indigo-700 hover:text-indigo-900 underline">
                    Voltar ao padrão
                </button>
            @endif
        </form>
    </div>
</div>
