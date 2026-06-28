<x-filament-panels::page>
    @php
        $employees = $this->getEmployees();
    @endphp

    <style>
        .client-employees-photo,
        .client-employees-photo-placeholder {
            display: flex;
            width: 5rem;
            height: 5rem;
            flex: 0 0 5rem;
        }

        .client-employees-photo {
            border-radius: 9999px;
            object-fit: cover;
            box-shadow: 0 0 0 2px rgba(229, 231, 235, 1);
        }

        .client-employees-photo-placeholder {
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .15rem;
            border-radius: 9999px;
            background: linear-gradient(180deg, #f8fafc 0%, #e5e7eb 100%);
            color: #94a3b8;
            box-shadow: inset 0 0 0 2px rgba(148, 163, 184, .22);
        }

        .client-employees-photo-placeholder svg {
            width: 2rem;
            height: 2rem;
        }

        .client-employees-photo-placeholder span {
            font-size: .56rem;
            font-weight: 700;
            line-height: 1;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .dark .client-employees-photo-placeholder {
            background: linear-gradient(180deg, rgb(55 65 81) 0%, rgb(31 41 55) 100%);
            color: #cbd5e1;
            box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .12);
        }

        .dark .client-employees-photo {
            box-shadow: 0 0 0 2px rgba(255, 255, 255, .1);
        }

        @media (min-width: 640px) {
            .client-employees-photo,
            .client-employees-photo-placeholder {
                width: 6rem;
                height: 6rem;
                flex-basis: 6rem;
            }

            .client-employees-photo-placeholder svg {
                width: 2.35rem;
                height: 2.35rem;
            }
        }
    </style>

    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
        {{ __('employees.subtitle') }}
    </p>

    @if ($employees->isEmpty())
        <x-filament::section>
            <div class="fi-ta-empty-state px-6 py-12 mx-auto grid max-w-lg justify-items-center text-center">
                <div class="fi-ta-empty-state-icon-ctn mb-4 rounded-full bg-gray-100 dark:bg-gray-500/20 p-3">
                    <x-filament::icon icon="heroicon-o-user-group" class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                </div>
                <h3 class="fi-ta-empty-state-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ __('employees.empty.heading') }}
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('employees.empty.read_only') }}
                </p>
            </div>
        </x-filament::section>
    @else
        <div class="space-y-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('employees.count', ['count' => $employees->count()]) }}
            </p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($employees as $employee)
                    <div
                        class="fi-section mx-auto flex w-full max-w-xs flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                    >
                        <div class="flex flex-col items-center p-4 text-center">
                            @if ($employee->photo)
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($employee->photo) }}"
                                    alt="{{ $employee->name }}"
                                    class="client-employees-photo"
                                />
                            @else
                                <div class="client-employees-photo-placeholder" aria-label="{{ __('employees.no_photo') }}">
                                    <x-filament::icon icon="heroicon-o-user" />
                                    <span>{{ __('employees.no_photo') }}</span>
                                </div>
                            @endif
                            <h3 class="mt-3 text-base font-semibold text-gray-950 dark:text-white sm:mt-3">
                                {{ $employee->name }}
                            </h3>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-filament-panels::page>
