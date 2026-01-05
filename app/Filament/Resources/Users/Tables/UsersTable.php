<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Payrolls\PayrollResource;
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
                ImageColumn::make('profile_photo')
                    ->label('Photo')
                    ->size(50)
                    ->square()
                    ->getStateUsing(fn (User $record) => $record->icon
                        ? asset('storage/'.$record->profilePhoto->photo_url)
                        : asset('images/placeholder.jpg')
                    ),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('businesses.name')
                    ->label('Businesses')
                    ->badge()
                    ->color('primary')
                    ->separator(', ')
                    ->sortable(false),


                TextColumn::make('branches.name')
                    ->label('Branches')
                    ->badge()
                    ->color('success')
                    ->separator(', ')
                    ->sortable(false),

                IconColumn::make('is_active')
                    ->color('primary')
                    ->boolean(),
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'verified',
                        'danger' => 'rejected',
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
                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
                SelectFilter::make('merchant_id')
                    ->label('Merchants')
                    ->relationship(
                        'merchant',
                        'name',
                    )
                    ->searchable()
                    ->preload()
                    ->visible(fn () => (Filament::auth()->user() instanceof \App\Models\Admin)),
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
            ->recordActions([
                Action::make('manage_payroll')
                    ->color('success')
                    ->icon('heroicon-o-currency-dollar')
                    ->label('')
                    ->tooltip('Manage Payroll')
                    ->url(fn (User $record) => PayrollResource::getUrl('index', ['user_id' => $record->id]))
                    ->visible(fn () => auth($guard)->user()?->hasPermissionTo('payrolls.view', $guard)),

                Action::make('create_payroll')
                    ->color('info')
                    ->icon('heroicon-o-plus')
                    ->label('')
                    ->tooltip('Create Payroll')
                    ->url(fn (User $record) => PayrollResource::getUrl('create', ['user_id' => $record->id]))
                    ->visible(fn () => auth($guard)->user()?->hasPermissionTo('payrolls.create', $guard)),

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
            ]);
    }
}
