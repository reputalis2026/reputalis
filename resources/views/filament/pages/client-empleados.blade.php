<x-filament-panels::page>
    @php
        $employees = $this->getEmployees();
    @endphp

    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
        Estos son los empleados configurados para tu cliente.
    </p>

    @if ($employees->isEmpty())
        <x-filament::section>
            <div class="fi-ta-empty-state px-6 py-12 mx-auto grid max-w-lg justify-items-center text-center">
                <div class="fi-ta-empty-state-icon-ctn mb-4 rounded-full bg-gray-100 dark:bg-gray-500/20 p-3">
                    <x-filament::icon icon="heroicon-o-user-group" class="h-6 w-6 text-gray-500 dark:text-gray-400" />
                </div>
                <h3 class="fi-ta-empty-state-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    No hay empleados
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    No hay empleados configurados para este cliente.
                </p>
            </div>
        </x-filament::section>
    @else
        <div class="space-y-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                Empleados ({{ $employees->count() }})
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
                                    class="h-20 w-20 rounded-full object-cover ring-2 ring-gray-200 dark:ring-white/10 sm:h-24 sm:w-24"
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
                            @if ($employee->alias)
                                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $employee->alias }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-filament-panels::page>
