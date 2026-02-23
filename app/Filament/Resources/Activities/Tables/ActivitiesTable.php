<?php

namespace App\Filament\Resources\Activities\Tables;

use App\Filament\Resources\Activities\Support\ActivityPerformer;
use App\Models\Merchant;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('event')
                    ->badge()
                    ->color(fn (?string $state) => match (strtolower((string) $state)) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'restored' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('actor')
                    ->label('Performed By')
                    ->getStateUsing(fn ($record) => ActivityPerformer::resolve($record))
                    ->searchable(),

                TextColumn::make('user_type')
                    ->label('Actor Type')
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return 'System';
                        }

                        return class_basename($state);
                    })
                    ->toggleable(),

                TextColumn::make('auditable_type')
                    ->label('Entity')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('auditable_id')
                    ->label('Entity ID')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('url')
                    ->label('URL')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->url)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'restored' => 'Restored',
                    ]),

                SelectFilter::make('user_type')
                    ->label('Actor Type')
                    ->options([
                        Merchant::class => 'Merchant',
                        User::class => 'Staff',
                    ]),

                Filter::make('created_at_range')
                    ->label('Activity Date')
                    ->schema([
                        DatePicker::make('from_date')
                            ->label('From Date'),
                        DatePicker::make('to_date')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['to_date'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
