<?php

namespace App\Filament\Resources\Branches\Tables;

use App\Filament\Resources\Branches\BranchResource;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Merchant;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BranchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('countries.name')
                    ->label('Countries')
                    ->badge()
                    ->color('primary')
                    ->toggleable()
                    ->separator(', ')
                    ->getStateUsing(function (Branch $record) {
                        $names = $record->countries()->pluck('name');

                        $visible = $names->take(2);
                        $hiddenCount = $names->count() - $visible->count();

                        if ($hiddenCount > 0) {
                            $visible->push('+' . $hiddenCount);
                        }

                        return $visible->toArray();
                    }),

                TextColumn::make('cities.name')
                    ->label('Cities')
                    ->color('primary')
                    ->badge()
                    ->toggleable()
                    ->separator(', ')
                    ->getStateUsing(function (Branch $record) {
                        $names = $record->cities()->pluck('name');

                        $visible = $names->take(2);
                        $hiddenCount = $names->count() - $visible->count();

                        if ($hiddenCount > 0) {
                            $visible->push('+' . $hiddenCount);
                        }

                        return $visible->toArray();
                    }),
                BadgeColumn::make('status')
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'verified',
                        'danger'  => 'rejected',
                    ])
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('primary')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),



                TextColumn::make('business.name')
                    ->label('Business')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),

                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive')
                    ->placeholder('All'),

                SelectFilter::make('business_id')
                    ->label('Businesses')
                    ->relationship(
                        'business',
                        'name',
                        modifyQueryUsing: function (Builder $query) {
                            $user = Filament::auth()->user();

                            if ($user instanceof \App\Models\Merchant) {
                                $query->where('merchant_id', $user->id);
                            }

                        }
                    )
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        Branch::STATUS_PENDING => 'Pending',
                        Branch::STATUS_VERIFIED => 'Verified',
                        Branch::STATUS_REJECTED => 'Rejected',
                    ])
                    ->label('Status')
            ])
            ->recordUrl(fn (Branch $record) =>
            auth(Filament::getCurrentPanel()->getAuthGuard())
                ->user()
                ?->hasPermissionTo('branches.update', Filament::getCurrentPanel()->getAuthGuard())
                ? BranchResource::getUrl('edit', [
                'record' => $record,
            ])
                : null
            )

            ->recordActions([
                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('branches.update', Filament::getCurrentPanel()->getAuthGuard())),
                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('branches.delete', Filament::getCurrentPanel()->getAuthGuard())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('branches.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
