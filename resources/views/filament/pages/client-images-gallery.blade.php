@php
    $rows = $this->clientRows;
    $selected = $this->getSelectedClient();
    $selectedEmployee = $this->getSelectedEmployee();
@endphp

<x-filament-panels::page>
    <style>
        .client-images-accordion {
            display: flex;
            flex-direction: column;
            gap: .4rem;
        }

        .client-images-accordion-item {
            overflow: hidden;
            border-radius: .55rem;
            border: 1px solid rgba(229, 231, 235, 1);
            background: #fff;
        }

        .client-images-accordion-trigger {
            display: flex;
            width: 100%;
            align-items: center;
            gap: .65rem;
            padding: .55rem .7rem;
            text-align: left;
            transition: background-color .15s ease;
        }

        .client-images-accordion-trigger:hover {
            background: #f9fafb;
        }

        .client-images-accordion-trigger.is-open {
            background: #fffbeb;
        }

        .client-images-accordion-logo {
            display: flex;
            width: 2.55rem;
            height: 2.55rem;
            flex: 0 0 2.55rem;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: .35rem;
            background: #e0f2fe;
            color: #0369a1;
            font-size: .8rem;
            font-weight: 700;
        }

        .client-images-accordion-logo img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .client-images-accordion-meta {
            min-width: 0;
            flex: 1 1 auto;
        }

        .client-images-accordion-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: .875rem;
            font-weight: 600;
            color: #111827;
        }

        .client-images-accordion-code {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: .7rem;
            color: #6b7280;
        }

        .client-images-accordion-chevron {
            display: flex;
            width: 1.25rem;
            height: 1.25rem;
            flex: 0 0 1.25rem;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            transition: transform .15s ease;
        }

        .client-images-accordion-trigger.is-open .client-images-accordion-chevron {
            transform: rotate(90deg);
            color: #d97706;
        }

        .client-images-accordion-body {
            border-top: 1px solid rgba(229, 231, 235, 1);
            padding: 1rem .85rem 1.1rem;
            background: #fafafa;
        }

        .client-images-employee-folders {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem 1rem;
        }

        .client-images-employee-folder {
            display: flex;
            min-width: 7.5rem;
            max-width: 10rem;
            align-items: center;
            gap: .55rem;
            border-radius: .5rem;
            border: 1px solid rgba(229, 231, 235, 1);
            background: #fff;
            padding: .45rem .55rem;
            text-align: left;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .client-images-employee-folder:hover {
            border-color: #fcd34d;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .05);
        }

        .client-images-employee-folder-avatar {
            display: flex;
            width: 2.55rem;
            height: 2.55rem;
            flex: 0 0 2.55rem;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: .35rem;
            background: #e0f2fe;
            color: #0369a1;
            font-size: .8rem;
            font-weight: 700;
        }

        .client-images-employee-folder-avatar img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dark .client-images-accordion-item {
            border-color: rgba(255, 255, 255, .1);
            background: rgb(17 24 39);
        }

        .dark .client-images-accordion-trigger:hover {
            background: rgb(31 41 55);
        }

        .dark .client-images-accordion-trigger.is-open {
            background: rgba(245, 158, 11, .08);
        }

        .dark .client-images-accordion-logo,
        .dark .client-images-employee-folder-avatar {
            background: rgb(31 41 55);
            color: #7dd3fc;
        }

        .dark .client-images-accordion-name {
            color: #f9fafb;
        }

        .dark .client-images-accordion-code {
            color: #9ca3af;
        }

        .dark .client-images-accordion-body {
            border-top-color: rgba(255, 255, 255, .08);
            background: rgb(3 7 18);
        }

        .dark .client-images-employee-folder {
            border-color: rgba(255, 255, 255, .1);
            background: rgb(17 24 39);
        }
    </style>

    <div class="space-y-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-[16rem] flex-1">
                {{ $this->form }}
            </div>
            @if ($this->clientId)
                <x-filament::button color="gray" wire:click="clearFilter" icon="heroicon-o-x-mark">
                    {{ __('panel.client_images.clear_filter') }}
                </x-filament::button>
            @endif
            <x-filament::button
                color="gray"
                tag="a"
                href="{{ \App\Filament\Pages\AdditionalTools::getUrl() }}"
                icon="heroicon-o-arrow-left"
            >
                {{ __('panel.client_images.back_to_tools') }}
            </x-filament::button>
        </div>

        @if ($rows->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ __('panel.client_images.empty_all') }}
                </p>
            </div>
        @else
            <div class="client-images-accordion">
                @foreach ($rows as $row)
                    @php
                        $client = $row['client'];
                        $isOpen = $this->clientId === $client->id;
                    @endphp

                    <div class="client-images-accordion-item" wire:key="client-row-{{ $client->id }}">
                        <button
                            type="button"
                            wire:click="toggleClient('{{ $client->id }}')"
                            @class(['client-images-accordion-trigger', 'is-open' => $isOpen])
                            aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                        >
                            <div class="client-images-accordion-logo">
                                @if ($row['logo_url'])
                                    <img
                                        src="{{ $row['logo_url'] }}"
                                        alt="{{ $client->namecommercial }}"
                                        loading="lazy"
                                    />
                                @else
                                    {{ $row['initials'] }}
                                @endif
                            </div>

                            <div class="client-images-accordion-meta">
                                <div class="client-images-accordion-name">
                                    {{ $client->namecommercial }}
                                </div>
                                <div class="client-images-accordion-code">
                                    {{ $client->code }}
                                </div>
                            </div>

                            <span class="client-images-accordion-chevron" aria-hidden="true">
                                <x-filament::icon icon="heroicon-m-chevron-right" class="h-4 w-4" />
                            </span>
                        </button>

                        @if ($isOpen && $selected)
                            <div class="client-images-accordion-body space-y-5">
                                @if ($selectedEmployee)
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                                {{ $selectedEmployee->name }}
                                            </h3>
                                            <p class="text-xs text-gray-500">
                                                {{ trans_choice('panel.client_images.images_count', $this->employeeImages->count(), ['count' => $this->employeeImages->count()]) }}
                                            </p>
                                        </div>
                                        <x-filament::button size="sm" color="gray" wire:click="clearEmployee" icon="heroicon-o-arrow-left">
                                            {{ __('panel.client_images.back_to_employees') }}
                                        </x-filament::button>
                                    </div>

                                    @include('filament.pages.partials.client-image-thumbs', ['images' => $this->employeeImages, 'size' => 'lg'])
                                @else
                                    <section class="space-y-2">
                                        <div>
                                            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                {{ __('panel.client_images.section_business') }}
                                            </h4>
                                            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
                                                {{ __('panel.client_images.section_business_help') }}
                                            </p>
                                        </div>

                                        @if ($this->businessImages->isEmpty())
                                            <div class="rounded-lg border border-dashed border-gray-300 px-4 py-5 text-center text-xs text-gray-500 dark:border-gray-700">
                                                {{ __('panel.client_images.empty_business') }}
                                            </div>
                                        @else
                                            @include('filament.pages.partials.client-image-thumbs', ['images' => $this->businessImages, 'size' => 'lg'])
                                        @endif
                                    </section>

                                    <section class="space-y-2">
                                        <div>
                                            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                {{ __('panel.client_images.section_employees') }}
                                            </h4>
                                            <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">
                                                {{ __('panel.client_images.section_employees_help') }}
                                            </p>
                                        </div>

                                        @if ($this->employeeFolders->isEmpty())
                                            <div class="rounded-lg border border-dashed border-gray-300 px-4 py-5 text-center text-xs text-gray-500 dark:border-gray-700">
                                                {{ __('panel.client_images.empty_employees') }}
                                            </div>
                                        @else
                                            <div class="client-images-employee-folders">
                                                @foreach ($this->employeeFolders as $folder)
                                                    <button
                                                        type="button"
                                                        wire:click="openEmployee('{{ $folder['employee']->id }}')"
                                                        class="client-images-employee-folder"
                                                    >
                                                        <div class="client-images-employee-folder-avatar">
                                                            @if ($folder['current_url'])
                                                                <img
                                                                    src="{{ $folder['current_url'] }}"
                                                                    alt="{{ $folder['employee']->name }}"
                                                                    loading="lazy"
                                                                />
                                                            @else
                                                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($folder['employee']->name, 0, 1)) }}
                                                            @endif
                                                        </div>
                                                        <div class="min-w-0">
                                                            <p class="truncate text-xs font-semibold text-gray-950 dark:text-white">
                                                                {{ $folder['employee']->name }}
                                                            </p>
                                                            <p class="truncate text-[11px] text-amber-700 dark:text-amber-300">
                                                                {{ trans_choice('panel.client_images.images_count', $folder['count'], ['count' => $folder['count']]) }}
                                                            </p>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </section>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
