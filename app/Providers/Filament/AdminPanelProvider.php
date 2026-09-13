<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\EditProfile;
use App\Http\SetPanelLocale;
use App\Support\ClientPanel;
use App\Support\PanelLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->spa()
            ->login(Login::class)
            ->profile(EditProfile::class)
            ->brandName(function (): string {
                if (ClientPanel::isActive()) {
                    return 'Reputalis';
                }

                return (string) config('app.name');
            })
            // Evita que el nombre/logo del cliente sea un enlace clicable.
            ->homeUrl(function (): ?string {
                if (auth()->user()?->isClientOwner()) {
                    return null;
                }

                return url('/admin');
            })
            ->colors([
                'primary' => Color::Amber,
            ])
            ->sidebarWidth('18rem')
            ->navigationGroups(ClientPanel::navigationGroups())
            ->navigationItems(ClientPanel::navigationItems())
            ->userMenuItems([
                'language_es' => MenuItem::make()
                    ->label(fn (): string => $this->languageMenuLabel('es'))
                    ->url(fn (): string => route('panel.language.switch', ['locale' => 'es']))
                    ->icon('heroicon-o-language'),
                'language_en' => MenuItem::make()
                    ->label(fn (): string => $this->languageMenuLabel('en'))
                    ->url(fn (): string => route('panel.language.switch', ['locale' => 'en']))
                    ->icon('heroicon-o-language'),
                'language_pt' => MenuItem::make()
                    ->label(fn (): string => $this->languageMenuLabel('pt'))
                    ->url(fn (): string => route('panel.language.switch', ['locale' => 'pt']))
                    ->icon('heroicon-o-language'),
            ])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.components.client-panel-theme')->render(),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): string => view('filament.components.client-sidebar-footer')->render(),
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => view('filament.components.panel-loading-overlay-markup')->render(),
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_BEFORE,
                fn (): string => view('filament.components.panel-loading-overlay-script')->render(),
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_BEFORE,
                fn (): string => view('filament.components.client-dashboard-charts-script')->render(),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetPanelLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    private function languageMenuLabel(string $locale): string
    {
        $language = PanelLocale::supported()[$locale];
        $currentSuffix = app()->getLocale() === $locale ? ' '.__('panel.language.current_suffix') : '';

        return "{$language['flag']} {$language['native']}{$currentSuffix}";
    }
}
