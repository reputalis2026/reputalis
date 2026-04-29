<x-filament-panels::page>
    @php
        $readOnly = $this->getSurveyReadOnlyData();
    @endphp

    <x-filament::section>
        <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
            {{ __('client.survey.read_only_intro') }}
        </p>
        <div class="space-y-4">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('client.survey.default_locale') }}</p>
                <p class="mt-1 text-base font-medium text-gray-950 dark:text-white">
                    {{ strtoupper($readOnly['default_locale']) }}
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('client.survey.display_mode') }}</p>
                <p class="mt-1 text-base font-medium text-gray-950 dark:text-white">
                    {{ $readOnly['display_mode_label'] }}
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('client.survey.positive_scores') }}</p>
                <p class="mt-1 text-base font-medium text-gray-950 dark:text-white">
                    {{ $readOnly['positive_scores_label'] }}
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('client.survey.positive_scores_help') }}
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('client.survey.main_question') }}</p>
                <dl class="mt-2 grid gap-3 md:grid-cols-3">
                    @foreach (['es', 'pt', 'en'] as $locale)
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __("survey.language_names.{$locale}") }}</dt>
                            <dd class="mt-1 text-base font-medium text-gray-950 dark:text-white">{{ $readOnly['survey_questions'][$locale] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('client.survey.block_title') }}</p>
                <dl class="mt-2 grid gap-3 md:grid-cols-3">
                    @foreach (['es', 'pt', 'en'] as $locale)
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __("survey.language_names.{$locale}") }}</dt>
                            <dd class="mt-1 text-base font-medium text-gray-950 dark:text-white">{{ $readOnly['titles'][$locale] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('client.survey.answers') }}</p>
                <div class="mt-2 space-y-3 text-base text-gray-700 dark:text-gray-300">
                    @forelse ($readOnly['options'] as $option)
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div class="grid gap-3 md:grid-cols-3">
                                @foreach (['es', 'pt', 'en'] as $locale)
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __("survey.language_names.{$locale}") }}</p>
                                        <p class="mt-1">{{ $option[$locale] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400">{{ __('client.survey.no_answers') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
