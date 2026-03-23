<x-filament-panels::page
    @class([
        'fi-resource-empleados-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
        'fi-resource-record-' . $record->getKey(),
    ])
>
    @php
        $employees = $this->getEmployees();
        $canEdit = $this->canEditEmpleados();
    @endphp

    @if (!$canEdit)
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            Estos son los empleados configurados para tu cliente.
        </p>
    @endif

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
                    @if ($canEdit)
                        Añade empleados a este cliente para poder asociarlos a encuestas más adelante.
                    @else
                        No hay empleados configurados para este cliente.
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
                            Añadir empleado
                        </x-filament::button>
                    </div>
                @endif
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
                        @if ($canEdit)
                            <div class="mt-auto flex flex-wrap items-center justify-center gap-1.5 border-t border-gray-200 px-3 py-2.5 dark:border-white/10">
                                @php
                                    $token = $employee->nfcTokens?->token;
                                    $surveyUrl = $token ? url('/survey/nfc/' . $token) : null;
                                @endphp
                                <x-filament::button
                                    tag="a"
                                    :href="\App\Filament\Resources\EmployeeResource::getUrl('edit', ['record' => $employee])"
                                    size="sm"
                                    color="gray"
                                    icon="heroicon-o-pencil-square"
                                    outlined
                                >
                                    Editar
                                </x-filament::button>
                                <x-filament::button
                                    size="sm"
                                    color="danger"
                                    icon="heroicon-o-trash"
                                    outlined
                                    wire:click="deleteEmployee('{{ $employee->id }}')"
                                    wire:confirm="¿Eliminar a {{ $employee->name }}? Esta acción no se puede deshacer."
                                >
                                    Eliminar
                                </x-filament::button>

                                @if ($surveyUrl)
                                    <x-filament::button
                                        size="sm"
                                        color="gray"
                                        icon="heroicon-o-clipboard-document"
                                        outlined
                                        onclick="navigator.clipboard.writeText(@json($surveyUrl)).then(() => { window.dispatchEvent(new CustomEvent('notificationSent', { detail: { notification: { title: 'Enlace de encuesta copiado', status: 'success' } } })); });"
                                    >
                                        Copiar enlace
                                    </x-filament::button>
                                @endif
                            </div>
                        @elseif ($employee->nfcTokens?->token)
                            <div class="mt-auto flex flex-wrap items-center justify-center gap-1.5 border-t border-gray-200 px-3 py-2.5 dark:border-white/10">
                                @php
                                    $surveyUrl = url('/survey/nfc/' . $employee->nfcTokens->token);
                                @endphp
                                <x-filament::button
                                    size="sm"
                                    color="gray"
                                    icon="heroicon-o-clipboard-document"
                                    outlined
                                    onclick="navigator.clipboard.writeText(@json($surveyUrl)).then(() => { window.dispatchEvent(new CustomEvent('notificationSent', { detail: { notification: { title: 'Enlace de encuesta copiado', status: 'success' } } })); });"
                                >
                                    Copiar enlace
                                </x-filament::button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if (!$canEdit)
        <div class="mt-6">
            <x-filament::button
                tag="a"
                :href="\App\Filament\Pages\Dashboard::getUrl()"
                color="gray"
                icon="heroicon-o-arrow-left"
                outlined
            >
                Volver al dashboard
            </x-filament::button>
        </div>
    @endif
</x-filament-panels::page>
