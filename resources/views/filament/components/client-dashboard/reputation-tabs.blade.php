@php
    $tabs = $tabs ?? [];
    $activeTab = $activeTab ?? 'internal';
@endphp
@unless (\App\Support\ClientPanel::isActive())
<div class="flex flex-wrap gap-2" role="tablist" aria-label="{{ __('client.dashboard.tabs.aria_label') }}">
    @foreach ($tabs as $key => $tab)
        <button
            type="button"
            wire:click="switchReputationTab('{{ $key }}')"
            role="tab"
            aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
            @class([
                'rounded-xl px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900',
                'bg-primary-600 text-white shadow-sm hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600' => $activeTab === $key,
                'bg-white text-gray-700 ring-1 ring-gray-950/10 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-gray-800' => $activeTab !== $key,
            ])
        >
            <span class="inline-flex items-center gap-2">
                <span>{{ $tab['label'] }}</span>
                @if (! empty($tab['badge']))
                    <span @class([
                        'inline-flex min-w-[1.25rem] items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-bold leading-none',
                        'bg-white/20 text-white' => $activeTab === $key,
                        'bg-danger-500 text-white' => $activeTab !== $key,
                    ])>
                        {{ $tab['badge'] }}
                    </span>
                @endif
            </span>
        </button>
    @endforeach
</div>
@endunless
