<x-filament-panels::page>
    @php
        /** @var \App\Models\Client $record */
        $record = $this->getRecord();
        $csatSummary = $this->getCsatSummary();
        $surveySummary = $this->getSurveySummary();
        $employeesSummary = $this->getEmployeesSummary();
        $callsSummary = $this->getCallsSummary();
        $surveyStatusClasses = match ($surveySummary['status']) {
            'configured' => 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20',
            'incomplete' => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-300 dark:ring-yellow-500/20',
            default => 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-500/10 dark:text-gray-300 dark:ring-gray-500/20',
        };
    @endphp

    <div class="space-y-6">
        <section class="space-y-4" data-dashboard-section="csat-summary">
            <div>
                <h2 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ __('client.dashboard.csat.heading') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('client.dashboard.csat.description') }}
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($csatSummary as $metric)
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
        </section>

        <section class="space-y-4" data-dashboard-section="operational-status">
            <div>
                <h2 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ __('client.dashboard.operations.heading') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('client.dashboard.operations.description') }}
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <article class="flex flex-col justify-between rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ __('client.dashboard.survey.heading') }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('client.dashboard.survey.description') }}
                                </p>
                            </div>
                            <span class="inline-flex shrink-0 items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $surveyStatusClasses }}">
                                {{ $surveySummary['status_label'] }}
                            </span>
                        </div>

                        <dl class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ __('client.dashboard.survey.mode') }}
                                </dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $surveySummary['mode_label'] }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ __('client.dashboard.survey.answers') }}
                                </dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ trans_choice('client.dashboard.survey.answers_count', $surveySummary['options_count'], ['count' => $surveySummary['options_count']]) }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <a
                        href="{{ \App\Filament\Resources\ClientResource::getUrl('puntos-de-mejora', ['record' => $record]) }}"
                        class="mt-5 text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                    >
                        {{ __('client.dashboard.actions.view_survey') }}
                    </a>
                </article>

                <article class="flex flex-col justify-between rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                {{ __('client.dashboard.employees.heading') }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('client.dashboard.employees.description') }}
                            </p>
                        </div>

                        <dl class="grid grid-cols-3 gap-3">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ __('client.dashboard.employees.active') }}
                                </dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">
                                    {{ $employeesSummary['active'] }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ __('client.dashboard.employees.inactive') }}
                                </dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">
                                    {{ $employeesSummary['inactive'] }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ __('client.dashboard.employees.total') }}
                                </dt>
                                <dd class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">
                                    {{ $employeesSummary['total'] }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <a
                        href="{{ \App\Filament\Resources\ClientResource::getUrl('empleados', ['record' => $record]) }}"
                        class="mt-5 text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                    >
                        {{ __('client.dashboard.actions.view_employees') }}
                    </a>
                </article>

                @if ($callsSummary)
                    <article class="flex flex-col justify-between rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ __('client.dashboard.calls.heading') }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('client.dashboard.calls.description') }}
                                    </p>
                                </div>

                                @if ($callsSummary['next_overdue'])
                                    <span class="inline-flex shrink-0 items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20">
                                        {{ __('client.dashboard.calls.overdue') }}
                                    </span>
                                @endif
                            </div>

                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        {{ __('client.dashboard.calls.last') }}
                                    </dt>
                                    <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ $callsSummary['last_call_at'] ? $callsSummary['last_call_at']->format('d/m/Y H:i') : __('common.placeholders.empty') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        {{ __('client.dashboard.calls.next') }}
                                    </dt>
                                    <dd @class([
                                        'mt-1 text-sm font-semibold',
                                        'text-red-700 dark:text-red-300' => $callsSummary['next_overdue'],
                                        'text-gray-950 dark:text-white' => ! $callsSummary['next_overdue'],
                                    ])>
                                        {{ $callsSummary['next_call_at'] ? $callsSummary['next_call_at']->format('d/m/Y H:i') : __('common.placeholders.empty') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        {{ __('client.dashboard.calls.total') }}
                                    </dt>
                                    <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ $callsSummary['total'] }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <a
                            href="{{ \App\Filament\Resources\ClientResource::getUrl('llamadas', ['record' => $record]) }}"
                            class="mt-5 text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                        >
                            {{ __('client.dashboard.actions.view_calls') }}
                        </a>
                    </article>
                @endif
            </div>
        </section>

        {{-- V2: charts, time series, recent activity and comparisons can be added as new sections below. --}}
    </div>
</x-filament-panels::page>
