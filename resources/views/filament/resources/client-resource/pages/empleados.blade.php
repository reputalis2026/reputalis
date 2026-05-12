<x-filament-panels::page
    @class([
        'fi-resource-empleados-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
        'fi-resource-record-' . $record->getKey(),
    ])
>
    @php
        $employees = $this->getEmployees();
        $employeesCount = $this->getEmployeesCount();
        $canEdit = $this->canEditEmpleados();
        $tabs = [
            'active' => __('employees.tabs.active'),
            'inactive' => __('employees.tabs.inactive'),
        ];
    @endphp

    @if (!$canEdit)
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            {{ __('employees.intro_read_only') }}
        </p>
    @endif

    @if ($employeesCount === 0)
        <x-filament::section>
            <div class="fi-ta-empty-state px-6 py-12 mx-auto grid max-w-lg justify-items-center text-center">
                <div class="fi-ta-empty-state-icon-ctn mb-4 rounded-full bg-gray-100 dark:bg-gray-500/20 p-3">
                    <x-filament::icon icon="heroicon-o-user-group" class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                </div>
                <h3 class="fi-ta-empty-state-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ __('employees.empty.heading') }}
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if ($canEdit)
                        {{ __('employees.empty.editable') }}
                    @else
                        {{ __('employees.empty.read_only') }}
                    @endif
                </p>
                @if ($canEdit)
                    <div class="mt-6">
                        <x-filament::button
                            tag="a"
                            :href="\App\Filament\Resources\EmployeeResource::getUrl('create') . '?client_id=' . $record->id"
                            color="primary"
                            icon="heroicon-o-plus"
                        >
                            {{ __('employees.actions.add') }}
                        </x-filament::button>
                    </div>
                @endif
            </div>
        </x-filament::section>
    @else
        <div class="space-y-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('employees.count', ['count' => $employeesCount]) }}
            </p>

            <div class="flex flex-wrap gap-2">
                @foreach ($tabs as $key => $label)
                    <button
                        type="button"
                        wire:click="switchEmployeeStatusTab('{{ $key }}')"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                        @class([
                            'bg-primary-600 text-white shadow hover:bg-primary-700 focus:ring-primary-500 dark:bg-primary-500 dark:hover:bg-primary-600' => $employeeStatusTab === $key,
                            'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' => $employeeStatusTab !== $key,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <x-filament::section>
                <x-slot name="heading">{{ $tabs[$employeeStatusTab] ?? $tabs['active'] }}</x-slot>

                @if ($employees->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('employees.empty.read_only') }}</p>
                @else
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($employees as $employee)
                            <div
                                class="fi-section mx-auto flex w-full max-w-xs flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                            >
                                <div @class([
                                    'flex flex-col items-center p-4 text-center',
                                    'opacity-75' => ! $employee->is_active,
                                ])>
                                    @if ($employee->photo)
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($employee->photo) }}"
                                            alt="{{ $employee->name }}"
                                            @class([
                                                'h-20 w-20 rounded-full object-cover ring-2 ring-gray-200 dark:ring-white/10 sm:h-24 sm:w-24',
                                                'grayscale' => ! $employee->is_active,
                                            ])
                                        />
                                    @else
                                        @php
                                            $words = array_filter(explode(' ', $employee->name));
                                            $initials = $words
                                                ? strtoupper(mb_substr($words[0], 0, 1) . (isset($words[1]) ? mb_substr($words[1], 0, 1) : ''))
                                                : '?';
                                        @endphp
                                        <div
                                            class="flex h-20 w-20 items-center justify-center rounded-full bg-gray-200 text-base font-semibold text-gray-600 ring-2 ring-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:ring-white/10 sm:h-24 sm:w-24 sm:text-lg"
                                        >
                                            {{ $initials }}
                                        </div>
                                    @endif
                                    <h3 class="mt-3 text-base font-semibold text-gray-950 dark:text-white sm:mt-3">
                                        {{ $employee->name }}
                                    </h3>
                                    @if ($employee->position)
                                        <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-300">
                                            {{ $employee->position }}
                                        </p>
                                    @endif
                                </div>
                                <div class="mt-auto flex flex-wrap items-center justify-center gap-1.5 border-t border-gray-200 px-3 py-2.5 dark:border-white/10">
                                    @php
                                        $token = $employee->nfcTokens?->token;
                                        $surveyUrl = $token ? url('/survey/nfc/' . $token) : null;
                                    @endphp
                                    @if ($canEdit)
                                        <x-filament::button
                                            tag="a"
                                            :href="\App\Filament\Resources\EmployeeResource::getUrl('edit', ['record' => $employee])"
                                            size="sm"
                                            color="gray"
                                            icon="heroicon-o-pencil-square"
                                            outlined
                                        >
                                            {{ __('common.actions.edit') }}
                                        </x-filament::button>
                                        @if (! $employee->is_active)
                                            <x-filament::button
                                                size="sm"
                                                color="danger"
                                                icon="heroicon-o-trash"
                                                outlined
                                                wire:click="deleteEmployee('{{ $employee->id }}')"
                                                wire:confirm="{{ __('employees.actions.delete_confirm', ['name' => $employee->name]) }}"
                                            >
                                                {{ __('common.actions.delete') }}
                                            </x-filament::button>
                                        @endif
                                    @endif
                                    @if ($surveyUrl)
                                        <x-filament::button
                                            size="sm"
                                            color="gray"
                                            icon="heroicon-o-clipboard-document"
                                            outlined
                                            data-copy-url="{{ $surveyUrl }}"
                                            onclick="window.reputalisCopyEmployeeSurveyUrl(this.dataset.copyUrl)"
                                        >
                                            {{ __('common.actions.copy_link') }}
                                        </x-filament::button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>
        </div>
    @endif

    <div
        id="employee-copy-feedback"
        class="fixed bottom-6 right-6 z-50 hidden rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-lg"
        role="status"
        aria-live="polite"
    ></div>

    <script>
        window.reputalisCopyEmployeeSurveyUrl = async function (url) {
            const successMessage = @js(__('employees.actions.link_copied'));
            const errorMessage = @js(__('employees.form.copy_failed'));
            const showFeedback = (message) => {
                const el = document.getElementById('employee-copy-feedback');
                if (!el) {
                    return;
                }
                el.textContent = message;
                el.classList.remove('hidden');
                window.clearTimeout(window.reputalisEmployeeCopyFeedbackTimer);
                window.reputalisEmployeeCopyFeedbackTimer = window.setTimeout(() => {
                    el.classList.add('hidden');
                }, 2500);
            };
            const fallbackCopy = () => {
                const textarea = document.createElement('textarea');
                textarea.value = url;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'fixed';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                textarea.select();
                const copied = document.execCommand('copy');
                document.body.removeChild(textarea);
                if (!copied) {
                    throw new Error('Copy command failed');
                }
            };

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(url);
                } else {
                    fallbackCopy();
                }
                window.dispatchEvent(new CustomEvent('notificationSent', {
                    detail: { notification: { title: successMessage, status: 'success' } },
                }));
                showFeedback(successMessage);
            } catch (e) {
                try {
                    fallbackCopy();
                    showFeedback(successMessage);
                } catch (fallbackError) {
                    showFeedback(errorMessage);
                }
            }
        };
    </script>

    @if (!$canEdit)
        <div class="mt-6">
            <x-filament::button
                tag="a"
                :href="\App\Filament\Pages\Dashboard::getUrl()"
                color="gray"
                icon="heroicon-o-arrow-left"
                outlined
            >
                {{ __('common.actions.back_to_dashboard') }}
            </x-filament::button>
        </div>
    @endif
</x-filament-panels::page>
