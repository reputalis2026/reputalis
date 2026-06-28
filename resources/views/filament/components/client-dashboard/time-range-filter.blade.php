@php
    $rangeTypes = $rangeTypes ?? [];
    $activeRangeType = $activeRangeType ?? 'all';
    $isCustomRange = $isCustomRange ?? false;
    $rangeContextSummary = $rangeContextSummary ?? [
        'total_surveys' => 0,
        'date_from' => __('common.placeholders.empty'),
        'date_to' => __('common.placeholders.empty'),
    ];
@endphp

<aside class="h-fit overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="client-dashboard-filter-header">
        <h3>{{ __('client.dashboard.filters.heading') }}</h3>
    </div>

    <div class="space-y-3 p-4">
        <label class="block">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('client.dashboard.filters.range_type') }}
            </span>
            <select
                wire:change="setRangeType($event.target.value)"
                class="mt-1 block w-full rounded-lg border-gray-300 bg-white py-1.5 text-sm shadow-sm transition focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            >
                @foreach ($rangeTypes as $key => $label)
                    <option value="{{ $key }}" @selected($activeRangeType === $key)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </label>

        @if ($isCustomRange)
            <div class="grid grid-cols-1 gap-2.5">
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('client.dashboard.filters.from') }}
                    </span>
                    <input
                        type="date"
                        wire:model.live="date_from"
                        class="mt-1 block w-full rounded-lg border-gray-300 py-1.5 text-sm shadow-sm transition focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </label>

                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('client.dashboard.filters.until') }}
                    </span>
                    <input
                        type="date"
                        wire:model.live="date_to"
                        class="mt-1 block w-full rounded-lg border-gray-300 py-1.5 text-sm shadow-sm transition focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </label>
            </div>
        @endif

        <div class="client-dashboard-main-summary-metric rounded-xl bg-gray-50 p-3 ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
            <p class="client-dashboard-metric-label">
                {{ __('client.dashboard.filters.found_surveys') }}
            </p>
            <p class="client-dashboard-metric-value client-dashboard-metric-value-lg mt-1">
                {{ $rangeContextSummary['total_surveys'] }}
            </p>
        </div>

        @if ($activeRangeType === 'today')
            <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">
                {{ $rangeContextSummary['date_from'] }}
            </p>
        @elseif (! $isCustomRange)
            <dl class="grid grid-cols-2 gap-3">
                <div>
                    <dt class="client-dashboard-metric-label">
                        {{ __('client.dashboard.filters.from') }}
                    </dt>
                    <dd class="client-dashboard-metric-value mt-1 text-sm">
                        {{ $rangeContextSummary['date_from'] }}
                    </dd>
                </div>
                <div>
                    <dt class="client-dashboard-metric-label">
                        {{ __('client.dashboard.filters.until') }}
                    </dt>
                    <dd class="client-dashboard-metric-value mt-1 text-sm">
                        {{ $rangeContextSummary['date_to'] }}
                    </dd>
                </div>
            </dl>
        @endif
    </div>
</aside>
