<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\ClientImprovementConfig;
use Filament\Pages\Page;

class ClientPuntosDeMejora extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?string $navigationLabel = 'Encuesta';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.client-puntos-de-mejora';

    protected static ?string $title = 'Tu encuesta';

    public ?Client $client = null;

    public function mount(): void
    {
        $this->client = $this->resolveClient();
        if (! $this->client) {
            abort(404);
        }
    }

    protected function resolveClient(): ?Client
    {
        $user = auth()->user();
        if (! $user || ! $user->isClientOwner()) {
            return null;
        }

        return $user->ownedClient;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user?->isClientOwner() === true && $user->ownedClient !== null;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isClientOwner() === true && $user->ownedClient !== null;
    }

    /**
     * Datos para la vista de solo lectura: título y lista de respuestas.
     *
     * @return array{title: string, display_mode_label: string, options: array<int, string>}
     */
    public function getPuntosReadOnlyData(): array
    {
        $client = $this->client;
        if (! $client) {
            return ['title' => '¿En qué podemos mejorar?', 'display_mode_label' => 'Números', 'options' => []];
        }
        $config = $client->improvementConfig;
        $options = $config ? $config->options()->orderBy('sort_order')->orderBy('created_at')->get() : collect();
        $mode = ClientImprovementConfig::normalizeDisplayMode($config?->display_mode);

        return [
            'title' => $config?->title ?? '¿En qué podemos mejorar?',
            'display_mode_label' => $mode === ClientImprovementConfig::DISPLAY_MODE_FACES ? 'Caritas' : 'Números',
            'options' => $options->pluck('label')->values()->all(),
        ];
    }
}
