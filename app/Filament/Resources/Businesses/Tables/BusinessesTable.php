<?php

namespace App\Filament\Resources\Businesses\Tables;

use App\Models\Business;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class BusinessesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                ImageColumn::make('logo.photo_url')
                    ->label('Logo')
                    ->size(50)
                    ->square()
                    ->getStateUsing(function (Business $record) {
                        if (! $record->logo) {
                            return asset('images/placeholder.jpg');
                        }

                        $path = $record->logo->photo_url;

                        // File missing on disk → placeholder
                        if (! Storage::disk('public')->exists($path)) {
                            return asset('images/placeholder.jpg');
                        }

                        // ✅ Correct public URL
                        return asset('storage/' . $path);
                    }),
                IconColumn::make('status')
                    ->label('Active')
                    ->color('primary')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->filters([

            ])
            ->recordUrl(fn (Business $record) =>
            auth(Filament::getCurrentPanel()->getAuthGuard())
                ->user()
                ?->hasPermissionTo('businesses.update', Filament::getCurrentPanel()->getAuthGuard())
                ? \App\Filament\Resources\Users\UserResource::getUrl('edit', [
                'record' => $record,
            ])
                : null
            )

            ->recordActions([
                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('businesses.update', Filament::getCurrentPanel()->getAuthGuard())),
                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('businesses.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('businesses.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
