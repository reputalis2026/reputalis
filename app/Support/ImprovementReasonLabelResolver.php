<?php

namespace App\Support;

use App\Models\Client;
use App\Models\ImprovementReason;

class ImprovementReasonLabelResolver
{
    /**
     * Textos por defecto por código (cuando no hay personalización por cliente).
     * Se pueden sustituir por traducciones en lang/ más adelante.
     */
    protected static array $defaultLabels = [
        'waiting_time' => 'Tiempo de espera',
        'sympathy' => 'Trato y simpatía',
        'information' => 'Información recibida',
        'product_availability' => 'Disponibilidad de producto',
        'other' => 'Otro',
    ];

    /**
     * Devuelve el texto visible del motivo de mejora para un cliente.
     * Si el cliente tiene etiqueta personalizada (y el distribuidor la configuró), se usa esa; si no, el texto por defecto.
     */
    public static function labelForClient(?Client $client, string $code): string
    {
        if ($client) {
            $custom = \App\Models\ClientImprovementReasonLabel::query()
                ->where('client_id', $client->id)
                ->where('improvement_reason_code', $code)
                ->value('label');

            if (filled($custom)) {
                return $custom;
            }
        }

        return self::$defaultLabels[$code] ?? $code;
    }

    /**
     * Devuelve todos los códigos de motivos de mejora activos con su etiqueta para un cliente.
     * Útil para listados en encuestas o API.
     *
     * @return array<string, string> [ 'waiting_time' => 'Tiempo de espera', ... ]
     */
    public static function labelsForClient(?Client $client): array
    {
        $reasons = ImprovementReason::where('is_active', true)->orderBy('code')->get(['code']);

        $result = [];
        foreach ($reasons as $reason) {
            $result[$reason->code] = self::labelForClient($client, $reason->code);
        }

        return $result;
    }

    /**
     * Registra o actualiza textos por defecto (para uso en lang o seeders).
     */
    public static function setDefaultLabel(string $code, string $label): void
    {
        self::$defaultLabels[$code] = $label;
    }
}
