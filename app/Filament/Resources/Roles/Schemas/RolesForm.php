<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RolesForm
{
    public static function configure(Schema $schema): Schema
    {
        $currentGuard = Filament::getCurrentPanel()->getAuthGuard();

        $guardLabels = [
            'admin' => 'admin',
            'merchant' => 'merchant',
            'staff' => 'staff',
        ];
        return $schema
            ->columns(1)
            ->components([
                Section::make('Role Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Role Name')
                            ->required()
                            ->maxLength(255),
//                         //   ->unique(ignoreRecord: true),
//                        Select::make('guard_name')
//                            ->label('Portal')
//                            ->required()
//                            ->options([
//                                $currentGuard => $guardLabels[$currentGuard] ?? ucfirst($currentGuard),
//                            ])
//                            ->default($currentGuard)
//                            ->hidden()  // keeps the value in the form submission but not visible to user
//                            ->reactive(),

        ])
                    ->columns(1),

                Section::make('Permissions')
                    ->schema([
                        Fieldset::make('Assign Permissions')
                            ->schema(static::getPermissionRows())
                            ->columns(1)
                            ->statePath('permissions')
                            ->reactive(),
                    ]),
            ]);
    }

    protected static function getPermissionRows(): array
    {
        $modules = [
            'dashboard' => 'Dashboard',
            'users' => 'Users',
            'admins' => 'Admins',
            'settings' => 'Settings',
            'roles_permissions' => 'Roles & Permissions',
            'merchants' => 'Merchants',
            'merchant_settings' => 'Merchant Settings',
            'businesses' => 'Businesses',
            'orders' => 'Orders',
            'branches' => 'Branches',
            'categories' => 'Categories',
            'customers' => 'Customers',
        ];

        $actions = ['view', 'create', 'update', 'delete'];
        $rows = [];

        foreach ($modules as $key => $label) {
            $rows[] = Grid::make()
                ->schema([
                    Checkbox::make("{$key}.select_all")
                        ->label($label)
                        ->afterStateUpdated(function ($state, callable $set) use ($key, $actions) {
                            foreach ($actions as $action) {
                                $set("{$key}.{$action}", $state);
                            }
                        })
                        ->reactive()
                        ->dehydrated(false),

                    ...collect($actions)->map(fn ($action) =>
                    Checkbox::make("{$key}.{$action}")
                        ->label(ucfirst($action))
                        ->default(false)
                        ->reactive()
                    )->toArray(),
                ])
                ->columns(5);
        }

        return $rows;
    }

}
