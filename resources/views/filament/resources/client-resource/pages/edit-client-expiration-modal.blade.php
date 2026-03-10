@if ($showExpirationConfirmModal)
    <div
        class="fi-modal fi-width-screen block fixed inset-0 z-50"
        aria-modal="true"
        role="dialog"
    >
        {{-- Overlay --}}
        <div
            class="fixed inset-0 z-40 bg-gray-950/50 dark:bg-gray-950/75"
            wire:click="closeExpirationConfirmModal"
        ></div>

        {{-- Container --}}
        <div class="fixed inset-0 z-40 overflow-y-auto p-4">
            <div class="relative grid min-h-full grid-rows-[1fr_auto_1fr] justify-items-center sm:grid-rows-[1fr_auto_3fr]">
                {{-- Modal window --}}
                <div
                    class="fi-modal-window relative row-start-2 flex w-full max-w-sm cursor-default flex-col rounded-xl bg-white shadow-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mx-auto"
                >
                    {{-- Header --}}
                    <div class="fi-modal-header flex gap-x-5 px-6 pt-6 pb-6 items-center">
                        <div class="flex-1 min-w-0">
                            <h2 class="fi-modal-heading text-lg font-semibold text-gray-950 dark:text-white">
                                Has cambiado la fecha de expiración
                            </h2>
                            <p class="fi-modal-description mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Has modificado la fecha de fin del cliente. Si continúas, se actualizará la fecha de expiración del contrato. ¿Quieres guardar estos cambios?
                            </p>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="fi-modal-footer w-full px-6 pb-6 flex flex-wrap items-center gap-3 flex-row-reverse border-t border-gray-200 dark:border-white/10 pt-5 -mt-px">
                        <x-filament::button
                            color="primary"
                            wire:click="confirmExpirationSave"
                        >
                            Aceptar
                        </x-filament::button>
                        <x-filament::button
                            color="gray"
                            wire:click="closeExpirationConfirmModal"
                        >
                            Cancelar
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
