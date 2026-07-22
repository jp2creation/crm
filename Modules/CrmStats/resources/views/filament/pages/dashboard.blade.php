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

        @if ($apiError)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                Donnees en cache - Mise a jour impossible, veuillez reessayer plus tard.
            </div>
        @endif

        {{ $this->headerWidgets }}

        <div class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-950">Top 5 clients</h2>
                        <p class="text-sm text-gray-500">{{ $period['label'] }}</p>
                    </div>
                    <a href="{{ \Modules\CrmStats\Filament\Pages\StatsClients::getUrl() }}" class="text-sm font-semibold text-primary-700">
                        Voir tout
                    </a>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($topClients as $client)
                        <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-100 px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-900">{{ $client->client_name }}</p>
                                <p class="text-xs text-gray-500">{{ $client->invoice_count }} facture(s)</p>
                            </div>
                            <p class="shrink-0 text-sm font-semibold text-primary-700">
                                {{ \Illuminate\Support\Number::currency((float) $client->total, 'EUR', locale: 'fr') }}
                            </p>
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-gray-500">
                            Aucune donnee commerciale sur cette periode.
                        </p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-950">Familles produits</h2>
                        <p class="text-sm text-gray-500">CA et evolution vs periode precedente</p>
                    </div>
                    <a href="{{ \Modules\CrmStats\Filament\Pages\StatsProductFamilies::getUrl() }}" class="text-sm font-semibold text-primary-700">
                        Details
                    </a>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($families as $family)
                        <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-100 px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-900">{{ $family->product_family }}</p>
                                <p class="text-xs text-gray-500">{{ $family->invoice_count }} facture(s)</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-primary-700">{{ \Illuminate\Support\Number::currency((float) $family->total, 'EUR', locale: 'fr') }}</p>
                                <p @class([
                                    'text-xs font-semibold',
                                    'text-emerald-600' => $family->evolution >= 0,
                                    'text-red-600' => $family->evolution < 0,
                                ])>
                                    {{ $family->evolution >= 0 ? '+' : '' }}{{ $family->evolution }} %
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-gray-500">
                            Aucune famille produit a afficher.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>

        {{ $this->footerWidgets }}
    </div>
</x-filament-panels::page>
