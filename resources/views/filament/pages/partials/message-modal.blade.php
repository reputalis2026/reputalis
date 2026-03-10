@props(['recipient', 'message'])

<div class="space-y-4">
    @if ($message?->body)
        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $message->body }}</p>
    @endif
    @if ($message?->client_id)
        <p class="pt-2">
            <a href="{{ \App\Filament\Resources\ClientResource::getUrl('edit', ['record' => $message->client_id]) }}"
               class="inline-flex items-center gap-1 text-primary-600 hover:underline dark:text-primary-400 filament-link">
                Editar cliente para activarlo →
            </a>
        </p>
    @endif
</div>
