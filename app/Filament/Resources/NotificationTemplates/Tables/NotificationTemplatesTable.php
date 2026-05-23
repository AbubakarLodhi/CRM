<?php

namespace App\Filament\Resources\NotificationTemplates\Tables;

use App\Filament\Resources\NotificationTemplates\NotificationTemplateResource;
use App\Models\NotificationTemplate;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotificationTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('events')
                    ->label('Events')
                    ->badge()
                    ->separator(',')
                    ->formatStateUsing(fn ($state, NotificationTemplate $record) => $record->eventLabels()),

                TextColumn::make('channels')
                    ->label('Channels')
                    ->badge()
                    ->separator(',')
                    ->formatStateUsing(fn ($state, NotificationTemplate $record) => $record->channelLabels()),

                TextColumn::make('merchant_id')
                    ->label('Scope')
                    ->formatStateUsing(fn ($state) => $state ? 'Merchant' : 'System'),

                TextColumn::make('subject')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Default')
                    ->boolean()
                    ->color(fn ($state) => $state ? 'primary' : 'danger'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordUrl(fn (NotificationTemplate $record) =>
                auth(Filament::getCurrentPanel()->getAuthGuard())
                    ->user()
                    ?->hasPermissionTo('notification_templates.update', Filament::getCurrentPanel()->getAuthGuard())
                    ? NotificationTemplateResource::getUrl('edit', [
                        'record' => $record,
                    ])
                    : null
            )
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('notification_templates.update', Filament::getCurrentPanel()->getAuthGuard())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('notification_templates.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ]),
            ]);
    }
}
