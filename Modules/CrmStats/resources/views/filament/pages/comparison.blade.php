<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($presets as $preset => $label)
                <button
                    type="button"
                    wire:click="setPreset('{{ $preset }}')"
                    @class([
                        'rounded-lg px-4 py-2 text-sm font-semibold transition',
                        'bg-primary-600 text-white shadow-sm' => $filters['preset'] === $preset,
                        'border border-gray-200 bg-white text-gray-700 hover:border-primary-200 hover:text-primary-700' => $filters['preset'] !== $preset,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-gray-500">Indicateur A</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($metrics as $metric => $label)
                        <button
                            type="button"
                            wire:click="setMetric('left_metric', '{{ $metric }}')"
                            @class([
                                'rounded-lg px-3 py-2 text-sm font-semibold',
                                'bg-primary-600 text-white' => $filters['left_metric'] === $metric,
                                'border border-gray-200 bg-white text-gray-700' => $filters['left_metric'] !== $metric,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <p class="mt-6 text-3xl font-bold text-gray-950">
                    {{ $filters['left_metric'] === 'total_revenue' || $filters['left_metric'] === 'average_basket'
                        ? \Illuminate\Support\Number::currency($comparison['left']['value'], 'EUR', locale: 'fr')
                        : \Illuminate\Support\Number::format($comparison['left']['value'], locale: 'fr') }}
                </p>
                <p class="text-sm text-gray-500">{{ $comparison['left']['label'] }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-gray-500">Indicateur B</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($metrics as $metric => $label)
                        <button
                            type="button"
                            wire:click="setMetric('right_metric', '{{ $metric }}')"
                            @class([
                                'rounded-lg px-3 py-2 text-sm font-semibold',
                                'bg-primary-600 text-white' => $filters['right_metric'] === $metric,
                                'border border-gray-200 bg-white text-gray-700' => $filters['right_metric'] !== $metric,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <p class="mt-6 text-3xl font-bold text-gray-950">
                    {{ $filters['right_metric'] === 'total_revenue' || $filters['right_metric'] === 'average_basket'
                        ? \Illuminate\Support\Number::currency($comparison['right']['value'], 'EUR', locale: 'fr')
                        : \Illuminate\Support\Number::format($comparison['right']['value'], locale: 'fr') }}
                </p>
                <p class="text-sm text-gray-500">{{ $comparison['right']['label'] }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-gray-500">Ecart relatif</p>
            <p @class([
                'mt-2 text-4xl font-bold',
                'text-emerald-600' => $comparison['delta'] >= 0,
                'text-red-600' => $comparison['delta'] < 0,
            ])>
                {{ $comparison['delta'] >= 0 ? '+' : '' }}{{ $comparison['delta'] }} %
            </p>
        </div>
    </div>
</x-filament-panels::page>
