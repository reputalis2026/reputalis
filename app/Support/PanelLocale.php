<?php

namespace App\Support;

class PanelLocale
{
    public const SESSION_KEY = 'panel_locale';

    /**
     * @return array<string, array{native: string, label: string, flag: string}>
     */
    public static function supported(): array
    {
        return [
            'es' => ['native' => 'Español', 'label' => __('panel.language.es'), 'flag' => '🇪🇸'],
            'en' => ['native' => 'English', 'label' => __('panel.language.en'), 'flag' => '🇬🇧'],
            'pt' => ['native' => 'Português', 'label' => __('panel.language.pt'), 'flag' => '🇵🇹'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(static::supported())
            ->mapWithKeys(fn (array $locale, string $key): array => [$key => "{$locale['flag']} {$locale['native']}"])
            ->all();
    }

    public static function resolve(?string $locale): string
    {
        if (is_string($locale) && array_key_exists($locale, static::supported())) {
            return $locale;
        }

        $defaultLocale = (string) config('app.locale', 'en');

        return array_key_exists($defaultLocale, static::supported()) ? $defaultLocale : 'es';
    }
}
