<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\PermissionModule;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

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
        $enabledModules = PermissionModule::enabledForCurrentMerchant();

        $actions = ['view', 'create', 'update', 'delete'];
        $rows = [];

        foreach ($enabledModules as $module) {
            $label = ucfirst(str_replace('_', ' ', $module));

            $rows[] = Grid::make()
                ->schema([
                    Checkbox::make("{$module}.select_all")
                        ->label($label)
                        ->afterStateUpdated(function ($state, callable $set) use ($module, $actions) {
                            foreach ($actions as $action) {
                                $set("{$module}.{$action}", $state);
                            }
                        })
                        ->reactive()
                        ->dehydrated(false),

                    ...collect($actions)->map(fn ($action) =>
                    Checkbox::make("{$module}.{$action}")
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
