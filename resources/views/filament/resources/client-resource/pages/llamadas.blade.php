<x-filament-panels::page>
    @php
        /** @var \App\Models\Client $client */
        $client = $this->getRecord();
        $lastCallAt = $client->last_call_at;
        $nextCallAt = $client->next_call_at;
        $nextOverdue = $nextCallAt ? $nextCallAt->isPast() : false;
    @endphp

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Última llamada
                </p>
                <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">
                    {{ $lastCallAt ? $lastCallAt->format('d/m/Y H:i') : 'Sin llamadas aún' }}
                </p>
            </div>

            <div
                class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
            >
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Próxima llamada
                    </p>
                    @if($nextOverdue)
                        <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-200">
                            Vencida
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-base font-semibold {{ $nextOverdue ? 'text-red-700 dark:text-red-300' : 'text-gray-900 dark:text-white' }}">
                    {{ $nextCallAt ? $nextCallAt->format('d/m/Y H:i') : '—' }}
                </p>
            </div>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>

