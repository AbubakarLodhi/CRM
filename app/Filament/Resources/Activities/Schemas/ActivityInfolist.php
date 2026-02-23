<?php

namespace App\Filament\Resources\Activities\Schemas;

use App\Filament\Resources\Activities\Support\ActivityPerformer;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Activity Information')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('event')
                            ->badge()
                            ->color(fn (?string $state) => match (strtolower((string) $state)) {
                                'created' => 'success',
                                'updated' => 'info',
                                'deleted' => 'danger',
                                'restored' => 'warning',
                                default => 'gray',
                            }),

                        TextEntry::make('performed_by')
                            ->label('Performed By')
                            ->getStateUsing(fn ($record) => ActivityPerformer::resolve($record)),

                        TextEntry::make('actor_type')
                            ->label('Actor Type')
                            ->getStateUsing(fn ($record) => $record->user_type ? class_basename((string) $record->user_type) : 'System'),

                        TextEntry::make('auditable_type')
                            ->label('Entity')
                            ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-'),

                        TextEntry::make('auditable_id')
                            ->label('Entity ID')
                            ->copyable(),

                        TextEntry::make('created_at')
                            ->label('Date & Time')
                            ->dateTime('d/m/Y H:i:s'),

                        TextEntry::make('url')
                            ->label('URL')
                            ->columnSpanFull()
                            ->placeholder('-'),

                        TextEntry::make('ip_address')
                            ->label('IP Address')
                            ->placeholder('-'),

                        TextEntry::make('tags')
                            ->label('Tags')
                            ->placeholder('-'),

                        TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),

                Section::make('Changes')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('old_values')
                            ->label('Old Values')
                            ->formatStateUsing(fn ($state) => self::toPrettyJson($state))
                            ->placeholder('{}'),

                        TextEntry::make('new_values')
                            ->label('New Values')
                            ->formatStateUsing(fn ($state) => self::toPrettyJson($state))
                            ->placeholder('{}'),
                    ]),
            ]);
    }

    protected static function toPrettyJson(mixed $state): string
    {
        if ($state === null || $state === '') {
            return '{}';
        }

        $decoded = is_array($state) ? $state : json_decode((string) $state, true);

        if (! is_array($decoded)) {
            return (string) $state;
        }

        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
