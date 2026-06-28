@php
    $metrics = $metrics ?? [];
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($metrics as $metric)
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ $metric['label'] }}
            </p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                {{ $metric['value'] }}
            </p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $metric['description'] }}
            </p>
        </div>
    @endforeach
</div>
