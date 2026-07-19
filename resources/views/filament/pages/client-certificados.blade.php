<x-filament-panels::page>
    <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center dark:border-gray-700 dark:bg-gray-900">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-600 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20">
            <x-filament::icon icon="heroicon-o-academic-cap" class="h-6 w-6" />
        </div>

        <h3 class="mt-4 text-base font-semibold text-gray-950 dark:text-white">
            {{ __('client.certificates.title') }}
        </h3>

        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
            {{ __('client.certificates.placeholder') }}
        </p>
    </div>
</x-filament-panels::page>
