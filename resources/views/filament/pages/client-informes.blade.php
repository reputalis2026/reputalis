<x-filament-panels::page>
    <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center dark:border-gray-700 dark:bg-gray-900">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-sky-50 text-sky-600 ring-1 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/20">
            <x-filament::icon icon="heroicon-o-document-chart-bar" class="h-6 w-6" />
        </div>

        <h3 class="mt-4 text-base font-semibold text-gray-950 dark:text-white">
            {{ __('client.reports.title') }}
        </h3>

        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
            {{ __('client.reports.placeholder') }}
        </p>
    </div>
</x-filament-panels::page>
