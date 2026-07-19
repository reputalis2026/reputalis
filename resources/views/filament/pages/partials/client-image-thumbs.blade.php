@php
    /** @var \Illuminate\Support\Collection|array $images */
    $images = collect($images);
    $size = $size ?? 'sm';
    $isLg = $size === 'lg';
@endphp

<style>
    .client-images-thumbs {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem 1rem;
        align-items: flex-start;
    }

    .client-images-thumb {
        display: flex;
        width: 4.25rem;
        flex-direction: column;
        align-items: center;
        gap: .35rem;
    }

    .client-images-thumbs.is-lg .client-images-thumb {
        width: 6.5rem;
        gap: .45rem;
    }

    .client-images-thumb-frame {
        position: relative;
        display: flex;
        width: 2.55rem;
        height: 2.55rem;
        flex: 0 0 2.55rem;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-radius: .35rem;
        background: #e0f2fe;
        box-shadow: 0 0 0 1px rgba(229, 231, 235, 1);
    }

    .client-images-thumbs.is-lg .client-images-thumb-frame {
        width: 5rem;
        height: 5rem;
        flex-basis: 5rem;
        border-radius: .45rem;
    }

    .client-images-thumb-frame.is-current {
        box-shadow: 0 0 0 2px #f59e0b;
    }

    .client-images-thumb-frame img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .client-images-thumb-badge {
        position: absolute;
        left: -0.15rem;
        top: -0.35rem;
        border-radius: .2rem;
        background: #f59e0b;
        padding: .1rem .2rem;
        font-size: .55rem;
        font-weight: 700;
        line-height: 1;
        text-transform: uppercase;
        color: #fff;
    }

    .client-images-thumbs.is-lg .client-images-thumb-badge {
        left: .2rem;
        top: .2rem;
        font-size: .62rem;
        padding: .15rem .3rem;
    }

    .client-images-thumb-download {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .2rem;
        border-radius: .3rem;
        background: #f3f4f6;
        padding: .15rem .35rem;
        font-size: .62rem;
        font-weight: 600;
        line-height: 1.1;
        color: #374151;
        text-decoration: none;
        white-space: nowrap;
    }

    .client-images-thumbs.is-lg .client-images-thumb-download {
        font-size: .7rem;
        padding: .25rem .45rem;
    }

    .client-images-thumb-download:hover {
        background: #fff7ed;
        color: #9a3412;
    }

    .dark .client-images-thumb-frame {
        background: rgb(31 41 55);
        box-shadow: 0 0 0 1px rgba(255, 255, 255, .1);
    }

    .dark .client-images-thumb-frame.is-current {
        box-shadow: 0 0 0 2px #f59e0b;
    }

    .dark .client-images-thumb-download {
        background: rgb(31 41 55);
        color: #e5e7eb;
    }

    .dark .client-images-thumb-download:hover {
        background: rgba(245, 158, 11, .12);
        color: #fcd34d;
    }
</style>

@if ($images->isEmpty())
    <div class="rounded-lg border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500 dark:border-gray-700">
        {{ __('panel.client_images.empty_client') }}
    </div>
@else
    <div @class(['client-images-thumbs', 'is-lg' => $isLg])>
        @foreach ($images as $image)
            <div class="client-images-thumb">
                <div @class(['client-images-thumb-frame', 'is-current' => $image['is_current']])>
                    <img
                        src="{{ $image['url'] }}"
                        alt="{{ $image['label'] }}"
                        loading="lazy"
                    />
                    @if ($image['is_current'])
                        <span class="client-images-thumb-badge">
                            {{ __('panel.client_images.current_badge') }}
                        </span>
                    @endif
                </div>
                <a
                    href="{{ $image['url'] }}"
                    download="{{ basename($image['path']) }}"
                    class="client-images-thumb-download"
                >
                    <x-filament::icon icon="heroicon-m-arrow-down-tray" class="h-3 w-3" />
                    {{ __('panel.client_images.download') }}
                </a>
            </div>
        @endforeach
    </div>
@endif
