<x-filament-panels::page>
    @php
        $readOnly = $this->getPuntosReadOnlyData();
    @endphp
    <x-filament::section>
        <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
            Estos son los datos configurados para tu cliente. No puedes modificarlos desde aquí.
        </p>
        <div class="space-y-4">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Modo de puntuación</p>
                <p class="mt-1 text-base font-medium text-gray-950 dark:text-white">
                    {{ $readOnly['display_mode_label'] }}
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Título del bloque</p>
                <p class="mt-1 text-base font-medium text-gray-950 dark:text-white">
                    {{ $readOnly['title'] }}
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Respuestas</p>
                <ul class="mt-2 list-inside list-disc space-y-1 text-base text-gray-700 dark:text-gray-300">
                    @forelse ($readOnly['options'] as $label)
                        <li>{{ $label }}</li>
                    @empty
                        <li class="text-gray-500 dark:text-gray-400">No hay respuestas configuradas.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
