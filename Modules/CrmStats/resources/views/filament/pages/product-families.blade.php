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

        {{ $this->headerWidgets }}

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Famille</th>
                            <th class="px-4 py-3">CA total</th>
                            <th class="px-4 py-3">Volume</th>
                            <th class="px-4 py-3">Commandes</th>
                            <th class="px-4 py-3">Evolution</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($families as $family)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $family->product_family }}</td>
                                <td class="px-4 py-3">{{ \Illuminate\Support\Number::currency((float) $family->total, 'EUR', locale: 'fr') }}</td>
                                <td class="px-4 py-3">{{ \Illuminate\Support\Number::format((float) $family->quantity, locale: 'fr') }}</td>
                                <td class="px-4 py-3">{{ $family->invoice_count }}</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-emerald-50 text-emerald-700' => $family->evolution >= 0,
                                        'bg-red-50 text-red-700' => $family->evolution < 0,
                                    ])>
                                        {{ $family->evolution >= 0 ? '+' : '' }}{{ $family->evolution }} %
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                                    Aucune famille produit pour cette periode.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
