<?php

namespace App\Support;

use App\Models\Client;
use App\Models\PanelMessage;
use App\Models\PanelMessageRecipient;
use App\Models\User;
use Illuminate\Support\Str;

class PanelMessageService
{
    /**
     * Crea un mensaje "Cliente pendiente de activación" y lo envía a todos los SuperAdmin.
     * Llamar cuando un distribuidor crea un cliente.
     */
    public static function notifyClientPendingActivation(Client $client): PanelMessage
    {
        $sender = $client->createdBy;
        $clientName = $client->namecommercial ?: $client->code;
        $messageId = (string) Str::uuid();

        $message = PanelMessage::create([
            'id' => $messageId,
            'type' => PanelMessage::TYPE_CLIENT_PENDING_ACTIVATION,
            'sender_user_id' => $sender?->id,
            'client_id' => $client->id,
            'title' => 'Cliente pendiente de activación',
            'body' => "El distribuidor ha creado el cliente «{$clientName}» (código {$client->code}). Actívalo desde la ficha del cliente para que pueda usar la plataforma.",
        ]);

        $superAdmins = User::where('role', User::ROLE_SUPERADMIN)->pluck('id');
        foreach ($superAdmins as $userId) {
            PanelMessageRecipient::create([
                'panel_message_id' => $message->id,
                'user_id' => $userId,
            ]);
        }

        // El distribuidor que creó el cliente también ve el mensaje en su bandeja
        if ($sender && $sender->id) {
            PanelMessageRecipient::create([
                'panel_message_id' => $message->id,
                'user_id' => $sender->id,
            ]);
        }

        return $message;
    }

    /**
     * Crea un mensaje "El SuperAdmin ha activado el cliente X" y lo envía al distribuidor que lo creó.
     * Llamar cuando un SuperAdmin pasa un cliente de inactivo a activo.
     */
    public static function notifyClientActivated(Client $client): ?PanelMessage
    {
        $distributor = $client->createdBy;
        if (! $distributor || ! $distributor->isDistributor()) {
            return null;
        }

        $clientName = $client->namecommercial ?: $client->code;

        $messageId = (string) Str::uuid();
        $message = PanelMessage::create([
            'id' => $messageId,
            'type' => PanelMessage::TYPE_CLIENT_ACTIVATED,
            'sender_user_id' => null,
            'client_id' => $client->id,
            'title' => 'Cliente activado',
            'body' => "El superadmin ha activado el cliente {$clientName}.",
        ]);

        PanelMessageRecipient::create([
            'panel_message_id' => $message->id,
            'user_id' => $distributor->id,
        ]);

        return $message;
    }
}
