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
use Illuminate\Support\Str;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        $guard = Filament::getCurrentPanel()->getAuthGuard();

        return $table
            ->columns([

//                ImageColumn::make('profilePhoto.photo_url')
//                    ->label('Photo')
//                    ->size(50)
//                    ->square()
//                    ->getStateUsing(function (User $record) {
//                        if (! $record->profilePhoto) {
//                            return asset('images/placeholder.jpg');
//                        }
//
//                        $path = $record->profilePhoto->photo_url;
//
//                        if (! Storage::disk('public')->exists($path)) {
//                            return asset('images/placeholder.jpg');
//                        }
//
//                        return asset('storage/' . $path);
//                    }),

             TextColumn::make('name')
                    ->limit(30)
                    ->extraAttributes(['class' => 'max-w-xs truncate'])
                    ->tooltip(fn (User $record) => $record->name)
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->limit(30)
                    ->extraAttributes(['class' => 'max-w-xs truncate'])
                    ->tooltip(fn (User $record) => $record->email)
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->limit(30)
                    ->extraAttributes(['class' => 'max-w-xs truncate'])
                    ->tooltip(fn (User $record) => $record->merchant?->name)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('businesses')
                    ->label('Businesses')
                    ->badge()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('primary')
                    ->getStateUsing(function (User $record) {
                        $names = $record->businesses
                            ->pluck('name')
                            ->map(fn (string $name) => Str::limit($name, 20));

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
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('success')
                    ->getStateUsing(function (User $record) {
                        $names = $record->branches
                            ->pluck('name')
                            ->map(fn (string $name) => Str::limit($name, 20));

                        $visible = $names->take(2);
                        $hiddenCount = $names->count() - $visible->count();

                        if ($hiddenCount > 0) {
                            $visible->push('+' . $hiddenCount);
                        }

                        return $visible->toArray();
                    })
                    ->sortable(false),
                TextColumn::make('roles')
                    ->label('Roles')
                    ->badge()
                    ->color('secondary')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(function (User $record) {
                        // get role names assigned to this user
                        $names = $record->roles
                            ->pluck('name')
                            ->map(fn (string $name) => Str::limit($name, 20));

                        $visible = $names->take(2); // show first 2 roles
                        $hiddenCount = $names->count() - $visible->count();

                        if ($hiddenCount > 0) {
                            $visible->push('+' . $hiddenCount); // show "+N" if more roles
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
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        $user = Filament::auth()->user();

                        // Merchant owner → all businesses
                        if ($user->id === $user->merchant_id) {
                            return \App\Models\Business::query()
                                ->where('merchant_id', $user->merchant_id)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        }

                        // Staff → only assigned businesses
                        return $user->businesses()
                            ->orderBy('businesses.name')
                            ->pluck('businesses.name', 'businesses.id')
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (empty($data['values'])) {
                            return;
                        }

                        $query->whereHas('businesses', function ($q) use ($data) {
                            $q->whereIn('businesses.id', $data['values']);
                        });
                    }),

                SelectFilter::make('branches')
                    ->label('Branches')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        $user = Filament::auth()->user();

                        // Merchant owner → all branches
                        if ($user->id === $user->merchant_id) {
                            return \App\Models\Branch::query()
                                ->where('merchant_id', $user->merchant_id)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        }

                        // Staff → only assigned branches
                        return $user->branches()
                            ->orderBy('branches.name')
                            ->pluck('branches.name', 'branches.id')
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (empty($data['values'])) {
                            return;
                        }

                        $query->whereHas('branches', function ($q) use ($data) {
                            $q->whereIn('branches.id', $data['values']);
                        });
                    }),

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
                    ->tooltip('Edit')
                    ->visible(fn () => auth($guard)->user()?->hasPermissionTo('users.update', $guard)),
                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
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
