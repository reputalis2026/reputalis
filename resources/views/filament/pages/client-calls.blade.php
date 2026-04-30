<x-filament-panels::page>
    <div class="flex flex-col gap-y-6">
        <div class="space-y-1">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                {{ __('calls.pending_heading') }}
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                {{ __('calls.pending_description') }}
            </p>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>

