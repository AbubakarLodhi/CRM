<?php

namespace App\Filament\Resources\Activities\Tables;

use App\Filament\Resources\Activities\Schemas\ActivityInfolist;
use App\Filament\Resources\Activities\Support\ActivityPerformer;
use App\Models\Merchant;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

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
                        'created'  => 'success',
                        'updated'  => 'info',
                        'deleted'  => 'danger',
                        'restored' => 'warning',
                        default    => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('actor')
                    ->label('Performed By')
                    ->getStateUsing(fn ($record) => ActivityPerformer::resolve($record))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHasMorph(
                            'user',
                            [Merchant::class, User::class],
                            fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"),
                        );
                    }),

                TextColumn::make('user_type')
                    ->label('Actor Type')
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return 'System';
                        }

                        return class_basename($state);
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('auditable_type')
                    ->label('Entity Type')
                    ->formatStateUsing(fn (?string $state): string => $state ? Str::headline(class_basename($state)) : '-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('entity_name')
                    ->label('Entity')
                    ->getStateUsing(fn ($record) => ActivityInfolist::resolveAuditableEntityValue($record))
                    ->placeholder('-')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHasMorph(
                            'auditable',
                            '*',
                            fn (Builder $q): Builder => $q->where('name', 'like', "%{$search}%"),
                        );
                    }),

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
                        'created'  => 'Created',
                        'updated'  => 'Updated',
                        'deleted'  => 'Deleted',
                        'restored' => 'Restored',
                    ]),

                SelectFilter::make('user_type')
                    ->label('Actor Type')
                    ->options([
                        Merchant::class => 'Merchant',
                        User::class     => 'Staff',
                    ]),

                SelectFilter::make('auditable_type')
                    ->label('Entity Type')
                    ->searchable()
                    ->options(function (): array {
                        return \App\Models\Audit::query()
                            ->select('auditable_type')
                            ->distinct()
                            ->pluck('auditable_type')
                            ->mapWithKeys(fn (string $type) => [$type => Str::headline(class_basename($type))])
                            ->toArray();
                    }),

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
                ViewAction::make()
                    ->visible(fn () =>
                        auth(Filament::getCurrentPanel()->getAuthGuard())
                            ->user()?->hasPermissionTo('audits.view', Filament::getCurrentPanel()->getAuthGuard())
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
