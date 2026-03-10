@php
    $heading = 'Clientes';
    $tabs = [
        'activos' => 'Clientes activos',
        'inactivos' => 'Clientes inactivos',
        'baja_proxima' => 'Clientes baja próxima',
    ];
    if ($showDistributorsTab ?? false) {
        $tabs['distribuidores'] = 'Distribuidores';
    }
    $isActivos = ($activeTab ?? 'activos') === 'activos';
    $isInactivos = ($activeTab ?? '') === 'inactivos';
    $isBajaProxima = ($activeTab ?? '') === 'baja_proxima';
    $isDistribuidores = ($activeTab ?? '') === 'distribuidores';

    $gridColsActivos = '1fr 6rem 6rem 5rem 5rem';
    $gridColsInactivos = '6rem 6rem 1fr 6rem';
    $gridColsBajaProxima = '1fr 6rem 6rem 6rem';
    $gridColsDistribuidores = '1fr 6rem 6rem 5rem 6rem';
@endphp

<x-filament-widgets::widget class="fi-wi-clients-overview">
    <div class="grid gap-y-4">
        <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
            {{ $heading }}
        </h3>

        {{-- Tres botones de pestaña --}}
        <div class="flex flex-wrap gap-2">
            @foreach ($tabs as $key => $label)
                <button
                    type="button"
                    wire:click="switchTab('{{ $key }}')"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    @class([
                        'bg-primary-600 text-white shadow hover:bg-primary-700 focus:ring-primary-500 dark:bg-primary-500 dark:hover:bg-primary-600' => $activeTab === $key,
                        'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' => $activeTab !== $key,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800/50">
            @if ($isDistribuidores && ($showDistributorsTab ?? false))
                {{-- Pestaña Distribuidores (solo superadmin) --}}
                @if ($distributors->isNotEmpty())
                    <div
                        class="grid items-center border-b border-gray-200 bg-gray-50/80 px-4 py-3 text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                        style="grid-template-columns: {{ $gridColsDistribuidores }}; gap: 1rem;"
                    >
                        <span>Distribuidor</span>
                        <span>Fecha inicio</span>
                        <span>Fecha fin</span>
                        <span>Estado</span>
                        <span>Teléfono</span>
                    </div>
                    @foreach ($distributors as $dist)
                        <a
                            href="{{ \App\Filament\Resources\DistributorResource::getUrl('view', ['record' => $dist->id]) }}"
                            class="grid items-center border-b border-l-4 border-gray-200 border-l-sky-500 px-4 py-3 transition last:border-b-0 hover:bg-gray-50 dark:border-gray-700 dark:border-l-sky-400 dark:hover:bg-gray-700/50"
                            style="grid-template-columns: {{ $gridColsDistribuidores }}; gap: 1rem;"
                        >
                            <span class="min-w-0 truncate font-medium text-gray-950 dark:text-white">{{ $dist->name }}</span>
                            <span class="min-w-0 text-sm text-gray-600 dark:text-gray-300">{{ $dist->fecha_inicio ?? '—' }}</span>
                            <span class="min-w-0 text-sm text-gray-600 dark:text-gray-300">{{ $dist->fecha_fin ?? '—' }}</span>
                            <span class="min-w-0 text-sm">
                                @if ($dist->is_active)
                                    <span class="text-success-600 dark:text-success-400">Activo</span>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">Inactivo</span>
                                @endif
                            </span>
                            <span class="min-w-0 text-sm text-gray-600 dark:text-gray-300">{{ $dist->telefono ?? '—' }}</span>
                        </a>
                    @endforeach
                @else
                    <div class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        No hay distribuidores.
                    </div>
                @endif
            @elseif ($clients->isNotEmpty())
                @if ($isActivos)
                    {{-- Cabecera tabla activos (sin Estado) --}}
                    <div
                        class="grid items-center border-b border-gray-200 bg-gray-50/80 px-4 py-3 text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                        style="grid-template-columns: {{ $gridColsActivos }}; gap: 1rem;"
                    >
                        <span>Cliente</span>
                        <span>Fecha inicio</span>
                        <span>Fecha fin</span>
                        <span>Encuestas hoy</span>
                        <span>% satisfechos</span>
                    </div>
                    @foreach ($clients as $client)
                        <a
                            href="{{ \App\Filament\Resources\ClientResource::getUrl('view', ['record' => $client->id]) }}"
                            class="grid items-center border-b border-l-4 border-gray-200 border-l-emerald-500 px-4 py-3 transition last:border-b-0 hover:bg-gray-50 dark:border-gray-700 dark:border-l-emerald-400 dark:hover:bg-gray-700/50"
                            style="grid-template-columns: {{ $gridColsActivos }}; gap: 1rem;"
                        >
                            <span class="min-w-0 truncate font-medium text-gray-950 dark:text-white">{{ $client->name }}</span>
                            <span class="min-w-0 text-sm text-gray-600 dark:text-gray-300">{{ $client->fecha_inicio ?? '—' }}</span>
                            <span class="min-w-0 text-sm text-gray-600 dark:text-gray-300">{{ $client->fecha_fin ?? '—' }}</span>
                            <span class="min-w-0 text-sm font-medium text-gray-950 dark:text-white">{{ $client->encuestas_hoy }}</span>
                            <span class="min-w-0 text-sm font-medium text-gray-950 dark:text-white">{{ $client->satisfied_pct !== null ? $client->satisfied_pct . '%' : '—' }}</span>
                        </a>
                    @endforeach
                @elseif ($isInactivos)
                    {{-- Cabecera tabla inactivos: fecha inicio, fecha fin, nombre, teléfono --}}
                    <div
                        class="grid items-center border-b border-gray-200 bg-gray-50/80 px-4 py-3 text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                        style="grid-template-columns: {{ $gridColsInactivos }}; gap: 1rem;"
                    >
                        <span>Fecha inicio</span>
                        <span>Fecha fin</span>
                        <span>Cliente</span>
                        <span>Teléfono</span>
                    </div>
                    @foreach ($clients as $client)
                        <a
                            href="{{ \App\Filament\Resources\ClientResource::getUrl('view', ['record' => $client->id]) }}"
                            class="grid items-center border-b border-l-4 border-gray-200 border-l-gray-400 px-4 py-3 transition last:border-b-0 hover:bg-gray-50 dark:border-gray-700 dark:border-l-gray-500 dark:hover:bg-gray-700/50"
                            style="grid-template-columns: {{ $gridColsInactivos }}; gap: 1rem;"
                        >
                            <span class="min-w-0 text-sm text-gray-600 dark:text-gray-300">{{ $client->fecha_inicio ?? '—' }}</span>
                            <span class="min-w-0 text-sm text-gray-600 dark:text-gray-300">{{ $client->fecha_fin ?? '—' }}</span>
                            <span class="min-w-0 truncate font-medium text-gray-950 dark:text-white">{{ $client->name }}</span>
                            <span class="min-w-0 text-sm text-gray-600 dark:text-gray-300">{{ $client->telefono ?? '—' }}</span>
                        </a>
                    @endforeach
                @else
                    {{-- Baja próxima: cliente, fecha inicio, fecha fin, teléfono --}}
                    <div
                        class="grid items-center border-b border-gray-200 bg-gray-50/80 px-4 py-3 text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                        style="grid-template-columns: {{ $gridColsBajaProxima }}; gap: 1rem;"
                    >
                        <span>Cliente</span>
                        <span>Fecha inicio</span>
                        <span>Fecha fin</span>
                        <span>Teléfono</span>
                    </div>
                    @foreach ($clients as $client)
                        <a
                            href="{{ \App\Filament\Resources\ClientResource::getUrl('view', ['record' => $client->id]) }}"
                            class="grid items-center border-b border-l-4 border-gray-200 border-l-amber-500 px-4 py-3 transition last:border-b-0 hover:bg-gray-50 dark:border-gray-700 dark:border-l-amber-400 dark:hover:bg-gray-700/50"
                            style="grid-template-columns: {{ $gridColsBajaProxima }}; gap: 1rem;"
                        >
                            <span class="min-w-0 truncate font-medium text-gray-950 dark:text-white">{{ $client->name }}</span>
                            <span class="min-w-0 text-sm text-gray-600 dark:text-gray-300">{{ $client->fecha_inicio ?? '—' }}</span>
                            <span class="min-w-0 text-sm font-medium text-gray-950 dark:text-white">{{ $client->fecha_fin ?? '—' }}</span>
                            <span class="min-w-0 text-sm text-gray-600 dark:text-gray-300">{{ $client->telefono ?? '—' }}</span>
                        </a>
                    @endforeach
                @endif
            @else
                <div class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    No hay clientes en esta sección.
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
