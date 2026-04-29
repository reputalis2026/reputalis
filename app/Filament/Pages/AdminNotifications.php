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

class AdminNotifications extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    public static function getNavigationLabel(): string
    {
        return __('panel.notifications.navigation_label');
    }

    public function getTitle(): string
    {
        return __('panel.notifications.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel.navigation_groups.system');
    }

    protected static string $view = 'filament.pages.admin-notifications';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() === true;
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
                    ->label(__('common.fields.date'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('panelMessage.type')
                    ->label(__('common.fields.type'))
                    ->formatStateUsing(fn (?string $state) => $state ? self::typeLabel($state) : __('common.placeholders.empty')),
                TextColumn::make('panelMessage.client.namecommercial')
                    ->label(__('common.fields.client'))
                    ->placeholder(__('common.placeholders.empty')),
                TextColumn::make('panelMessage.sender.fullname')
                    ->label(__('panel.notifications.sender'))
                    ->formatStateUsing(fn ($state, PanelMessageRecipient $record) => $record->panelMessage?->sender?->getFilamentName() ?? __('common.placeholders.empty'))
                    ->placeholder(__('common.placeholders.empty')),
                IconColumn::make('read_at')
                    ->label(__('panel.notifications.read'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->actions([
                Action::make('ver')
                    ->label(__('common.actions.view'))
                    ->modalHeading(fn (PanelMessageRecipient $record) => $record->panelMessage?->title ?? __('panel.notifications.message'))
                    ->modalContent(fn (PanelMessageRecipient $record) => view('filament.pages.partials.message-modal', [
                        'recipient' => $record,
                        'message' => $record->panelMessage,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('common.actions.close'))
                    ->action(function (PanelMessageRecipient $record): void {
                        $record->markAsRead();
                    }),
            ])
            ->emptyStateHeading(__('panel.notifications.empty_heading'))
            ->emptyStateDescription(__('panel.notifications.empty_description'))
            ->emptyStateIcon('heroicon-o-bell');
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            PanelMessage::TYPE_CLIENT_PENDING_ACTIVATION => __('panel.notifications.types.client_pending_activation'),
            PanelMessage::TYPE_CLIENT_ACTIVATED => __('panel.notifications.types.client_activated'),
            default => $type,
        };
    }
}
