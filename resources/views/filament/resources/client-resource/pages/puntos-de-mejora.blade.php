<x-filament-panels::page
    @class([
        'fi-resource-puntos-de-mejora-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
        'fi-resource-record-' . $record->getKey(),
    ])
>
    @if ($this->canEditPuntos())
        <x-filament-panels::form
            id="form"
            :wire:key="$this->getId() . '.forms.data'"
            wire:submit="save"
        >
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </x-filament-panels::form>
    @else
        {{-- Rol cliente: vista de solo lectura, sin formulario --}}
        @php
            $readOnly = $this->getPuntosReadOnlyData();
        @endphp
        <x-filament::section>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                Estos son los datos configurados para tu cliente. No puedes modificarlos desde aquí.
            </p>
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Idioma por defecto</p>
                    <p class="mt-1 text-base font-medium text-gray-950 dark:text-white">
                        {{ strtoupper($readOnly['default_locale']) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Modo de puntuación</p>
                    <p class="mt-1 text-base font-medium text-gray-950 dark:text-white">
                        {{ $readOnly['display_mode_label'] }}
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Valoraciones positivas</p>
                    <p class="mt-1 text-base font-medium text-gray-950 dark:text-white">
                        {{ $readOnly['positive_scores_label'] }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Las valoraciones no marcadas irán al punto de mejora.
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pregunta principal</p>
                    <dl class="mt-2 grid gap-3 md:grid-cols-3">
                        @foreach (['es' => 'Español', 'pt' => 'Portugués', 'en' => 'Inglés'] as $locale => $label)
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $label }}</dt>
                                <dd class="mt-1 text-base font-medium text-gray-950 dark:text-white">{{ $readOnly['survey_questions'][$locale] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Título del bloque</p>
                    <dl class="mt-2 grid gap-3 md:grid-cols-3">
                        @foreach (['es' => 'Español', 'pt' => 'Portugués', 'en' => 'Inglés'] as $locale => $label)
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $label }}</dt>
                                <dd class="mt-1 text-base font-medium text-gray-950 dark:text-white">{{ $readOnly['titles'][$locale] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Respuestas</p>
                    <div class="mt-2 space-y-3 text-base text-gray-700 dark:text-gray-300">
                        @forelse ($readOnly['options'] as $option)
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <div class="grid gap-3 md:grid-cols-3">
                                    @foreach (['es' => 'Español', 'pt' => 'Portugués', 'en' => 'Inglés'] as $locale => $label)
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $label }}</p>
                                            <p class="mt-1">{{ $option[$locale] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 dark:text-gray-400">No hay respuestas configuradas.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="mt-6">
                <x-filament::button
                    tag="a"
                    :href="filament()->getUrl()"
                    color="gray"
                    outlined
                >
                    Volver al dashboard
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
