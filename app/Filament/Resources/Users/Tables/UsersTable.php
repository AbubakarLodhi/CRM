<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Payrolls\PayrollResource;
use Illuminate\Support\Facades\Storage;
use App\Models\PermissionModule;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        return $table
            ->columns([

                ImageColumn::make('profilePhoto.photo_url')
                    ->label('Photo')
                    ->size(50)
                    ->square()
                    ->getStateUsing(function (User $record) {
                        if (! $record->profilePhoto) {
                            return asset('images/placeholder.jpg');
                        }

                        $path = $record->profilePhoto->photo_url;

                        if (! Storage::disk('public')->exists($path)) {
                            return asset('images/placeholder.jpg');
                        }

                        return asset('storage/' . $path);
                    }),

             TextColumn::make('name')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('businesses')
                    ->label('Businesses')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('primary')
                    ->getStateUsing(function (User $record) {
                        $names = $record->businesses->pluck('name');

                        $visible = $names->take(2);
                        $hiddenCount = $names->count() - $visible->count();

                        if ($hiddenCount > 0) {
                            $visible->push('+' . $hiddenCount);
                        }

                        return $visible->toArray();
                    })
                    ->sortable(false),



                TextColumn::make('branches')
                    ->label('Branches')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('success')
                    ->getStateUsing(function (User $record) {
                        $names = $record->branches->pluck('name');

                        $visible = $names->take(2);
                        $hiddenCount = $names->count() - $visible->count();

                        if ($hiddenCount > 0) {
                            $visible->push('+' . $hiddenCount);
                        }

                        return $visible->toArray();
                    })
                    ->sortable(false),


                IconColumn::make('is_active')
                    ->color('primary')
                    ->boolean(),
                BadgeColumn::make('status')
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'verified',
                        'danger'  => 'rejected',
                    ])
                    ->sortable()
                    ->toggleable(),
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

                SelectFilter::make('businesses')
                    ->label('Businesses')
                    ->relationship('businesses', 'name')
                    ->searchable()
                    ->multiple()
                    ->preload(),

                SelectFilter::make('branches')
                    ->label('Branches')
                    ->relationship('branches', 'name')
                    ->searchable()
                    ->multiple()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive')
                    ->placeholder('All'),
            ])
            ->recordUrl(fn (User $record) =>
            auth(Filament::getCurrentPanel()->getAuthGuard())
                ->user()
                ?->hasPermissionTo('users.update', Filament::getCurrentPanel()->getAuthGuard())
                ? \App\Filament\Resources\Users\UserResource::getUrl('edit', [
                'record' => $record,
            ])
                : null
            )

            ->recordActions([
                Action::make('manage_payroll')
                    ->color('success')
                    ->icon('heroicon-o-currency-dollar')
                    ->label('')
                    ->tooltip('Manage Payroll')
                    ->url(fn (User $record) => PayrollResource::getUrl('index', ['user_id' => $record->id]))
                    ->visible(function () use ($guard) {
                        $user = auth($guard)->user();

                        if (! $user) {
                            return false;
                        }
                        if (! PermissionModule::isEnabledForCurrentMerchant('payrolls')) {
                            return false;
                        }

                        // 🔐 Permission gate
                        return $user->hasPermissionTo('payrolls.view', $guard);
                    }),
                Action::make('create_payroll')
                    ->color('info')
                    ->icon('heroicon-o-plus')
                    ->label('')
                    ->tooltip('Create Payroll')
                    ->url(fn (User $record) => PayrollResource::getUrl('create', ['user_id' => $record->id]))
                    ->visible(function () use ($guard) {
                        $user = auth($guard)->user();

                        if (! $user) {
                            return false;
                        }
                        if (! PermissionModule::isEnabledForCurrentMerchant('payrolls')) {
                            return false;
                        }

                        // 🔐 Permission gate
                        return $user->hasPermissionTo('payrolls.create', $guard);
                    }),

                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit Staff')
                    ->visible(fn () => auth($guard)->user()?->hasPermissionTo('users.update', $guard)),
                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete Staff')
                    ->visible(fn () => auth($guard)->user()?->hasPermissionTo('users.delete', $guard)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth($guard)->user()?->hasPermissionTo('users.delete', $guard)),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
