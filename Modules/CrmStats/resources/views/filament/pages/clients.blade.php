<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
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

            <div class="flex flex-wrap items-center gap-2">
                @foreach ($statuses as $status => $label)
                    <button
                        type="button"
                        wire:click="setStatus({{ $status === null ? 'null' : "'{$status}'" }})"
                        @class([
                            'rounded-lg px-3 py-2 text-sm font-semibold transition',
                            'bg-gray-950 text-white' => $filters['status'] === $status,
                            'border border-gray-200 bg-white text-gray-700 hover:border-primary-200 hover:text-primary-700' => $filters['status'] !== $status,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Client</th>
                            <th class="px-4 py-3">CA total</th>
                            <th class="px-4 py-3">Commandes</th>
                            <th class="px-4 py-3">Panier moyen</th>
                            <th class="px-4 py-3">Derniere commande</th>
                            <th class="px-4 py-3">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($clients as $client)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $client->client_name }}</td>
                                <td class="px-4 py-3">{{ \Illuminate\Support\Number::currency((float) $client->total, 'EUR', locale: 'fr') }}</td>
                                <td class="px-4 py-3">{{ $client->invoice_count }}</td>
                                <td class="px-4 py-3">{{ \Illuminate\Support\Number::currency((float) $client->average_basket, 'EUR', locale: 'fr') }}</td>
                                <td class="px-4 py-3">{{ $client->last_invoice_date ? \Carbon\CarbonImmutable::parse($client->last_invoice_date)->format('d/m/Y') : '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ $client->client_status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                                    Aucun client pour cette periode.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
