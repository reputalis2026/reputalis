<x-filament-panels::page>
    <div class="flex flex-col gap-y-6">
        <div class="space-y-1">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                Llamadas pendientes
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Ordenado por la próxima llamada más cercana. Las llamadas vencidas se muestran en rojo.
            </p>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>

