<?php

namespace App\Filament\Resources\Roles\Tables;

use App\Models\Role;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Builder;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RolesTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                BadgeColumn::make('guard_name')
                    ->label('Portal')
                    ->color('primary')
                    ->sortable()
                    ->searchable(),
                BadgeColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions'),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
                SelectFilter::make('guard_name')
                    ->label('Portal')
                    ->options([
                        'admin' => 'Admin',
                        'merchant' => 'Merchant',
                    ])
                    ->searchable()
                    ->preload()
                    ->visible(fn () => (Filament::auth()->user() instanceof \App\Models\Admin)),
                SelectFilter::make('id')
                    ->label('Roles')
                    ->options(function () {
                        $user = Filament::auth()->user();

                        // Merchant panel → merchant roles only
                        if ($user instanceof \App\Models\Merchant) {
                            return Role::where('guard_name', 'merchant')
                                ->pluck('name', 'id');
                        }

                        // Admin panel → all roles
                        return Role::pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
            ])
            ->recordActions([
                EditAction::make()
                    ->color('warning')
                    ->label('')
                    ->tooltip('Edit')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('roles_permissions.update', Filament::getCurrentPanel()->getAuthGuard())),
                DeleteAction::make()
                    ->color('danger')
                    ->label('')
                    ->tooltip('Delete')
                    ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('roles_permissions.delete', Filament::getCurrentPanel()->getAuthGuard())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth(Filament::getCurrentPanel()->getAuthGuard())->user()?->hasPermissionTo('roles_permissions.delete', Filament::getCurrentPanel()->getAuthGuard())),
                ]),
            ]);
    }
}
