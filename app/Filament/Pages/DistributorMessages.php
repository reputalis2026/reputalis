<?php

namespace App\Filament\Pages;

use App\Models\PanelMessage;
use App\Models\PanelMessageRecipient;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DistributorMessages extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Mensajes';

    protected static ?string $title = 'Bandeja de mensajes';

    protected static ?string $navigationGroup = 'Comunicación';

    protected static string $view = 'filament.pages.distributor-messages';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isDistributor() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isDistributor() === true;
    }

    protected function getTableQuery(): Builder
    {
        return PanelMessageRecipient::query()
            ->where('user_id', auth()->id())
            ->with(['panelMessage.client', 'panelMessage.sender'])
            ->join('panel_messages', 'panel_message_recipients.panel_message_id', '=', 'panel_messages.id')
            ->orderByDesc('panel_messages.created_at')
            ->select('panel_message_recipients.*');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getTableQuery())
            ->columns([
                TextColumn::make('panelMessage.created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('panelMessage.type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (?string $state) => $state ? self::typeLabel($state) : '—'),
                TextColumn::make('panelMessage.client.namecommercial')
                    ->label('Cliente')
                    ->placeholder('—'),
                IconColumn::make('read_at')
                    ->label('Leído')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->actions([
                Action::make('ver')
                    ->label('Ver')
                    ->modalHeading(fn (PanelMessageRecipient $record) => $record->panelMessage?->title ?? 'Mensaje')
                    ->modalContent(fn (PanelMessageRecipient $record) => view('filament.pages.partials.message-modal', [
                        'recipient' => $record,
                        'message' => $record->panelMessage,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->action(function (PanelMessageRecipient $record): void {
                        $record->markAsRead();
                    }),
            ])
            ->emptyStateHeading('No hay mensajes')
            ->emptyStateDescription('Aquí verás cuando crees un cliente pendiente de activación o cuando el superadmin active uno.')
            ->emptyStateIcon('heroicon-o-envelope');
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            PanelMessage::TYPE_CLIENT_PENDING_ACTIVATION => 'Cliente pendiente de activación',
            PanelMessage::TYPE_CLIENT_ACTIVATED => 'Cliente activado',
            default => $type,
        };
    }
}
